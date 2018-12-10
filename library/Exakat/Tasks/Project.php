<?php
/*
 * Copyright 2012-2018 Damien Seguy – Exakat SAS <contact(at)exakat.io>
 * This file is part of Exakat.
 *
 * Exakat is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exakat is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Exakat.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://exakat.io/>.
 *
*/


namespace Exakat\Tasks;

use Exakat\Analyzer\Analyzer;
use Exakat\Analyzer\Themes;
use Exakat\Config;
use Exakat\Datastore;
use Exakat\Exakat;
use Exakat\Project as Projectname;
use Exakat\Exceptions\NoFileToProcess;
use Exakat\Exceptions\NoSuchProject;
use Exakat\Exceptions\ProjectNeeded;
use Exakat\Exceptions\InvalidProjectName;
use Exakat\Vcs\Vcs;

class Project extends Tasks {
    const CONCURENCE = self::NONE;

    private $project_dir = '.';

    protected $themesToRun = array('Analyze',
                                   'Preferences',
                                   );

    protected $reports = array();

    public function __construct($gremlin, $config, $subTask = self::IS_NOT_SUBTASK) {
        parent::__construct($gremlin, $config, $subTask);

        if (empty($this->reports)) {
            $this->reports = makeArray($config->project_reports);
        }
    }
    
    public function run() {
        $project = new Projectname($this->config->project);

        if (!$project->validate()) {
            throw new InvalidProjectName($project->getError());
        }

        $this->project_dir = "{$this->config->projects_root}/projects/$project";

        if ($this->config->project === 'default') {
            throw new ProjectNeeded();
        }

        if (!file_exists("{$this->config->projects_root}/projects/$project")) {
            throw new NoSuchProject($this->config->project);
        }

        display("Cleaning project\n");
        $clean = new Clean($this->gremlin, $this->config, Tasks::IS_SUBTASK);
        $clean->run();
        $this->datastore = new Datastore($this->config);
        // Reset datastore for the others
        Analyzer::$datastore = $this->datastore;

        display("Search for external libraries".PHP_EOL);
        $pathCache = "{$this->config->projects_root}/projects/$project/config.cache";
        if (file_exists($pathCache)) {
            unlink($pathCache);
        }
        $args = array ( 1 => 'findextlib',
                        2 => '-p',
                        3 => $this->config->project,
                        4 => '-u',
                        );

        $configThema = new Config($args);

        $analyze = new FindExternalLibraries($this->gremlin, $configThema, Tasks::IS_SUBTASK);
        $analyze->run();

        $this->addSnitch(array('step'    => 'External lib',
                               'project' => $this->config->project));
        unset($analyze);

        $this->logTime('Start');
        $this->addSnitch(array('step'    => 'Start',
                               'project' => $this->config->project));

        $audit_start = time();
        $this->datastore->addRow('hash', array('audit_start'    => $audit_start,
                                               'exakat_version' => Exakat::VERSION,
                                               'exakat_build'   => Exakat::BUILD,
                                               'php_version'    => $this->config->phpversion,
                                               'audit_name'     => $this->generateName(),
                                         ));

        $info = array();
        if (($vcsClass = Vcs::getVcs($this->config)) === 'EmptyCode') {
            $info['vcs_type'] = 'Standalone archive';
        } else {
            $info['vcs_type'] = strtolower($vcsClass);
            $info['vcs_url']  = $this->config->project_url;
            
            $vcs = new $vcsClass($this->config->project, $this->config->projects_root);
            if (method_exists($vcs, 'getBranch')) {
                $info['vcs_branch']      = $vcs->getBranch();
            }
            if (method_exists($vcs, 'getRevision')) {
                $info['vcs_revision']      = $vcs->getRevision();
            }
        }
        $this->datastore->addRow('hash', $info);
        
        $themesToRun = array($this->config->project_themes);
        $reportToRun = array();

        foreach($this->reports as $format) {
            $reportClass = "\Exakat\Reports\\$format";
            if (!class_exists($reportClass)) {
                continue;
            }
            $reportToRun[] = $format;
            $report = new $reportClass($this->config);
            
            $themesToRun[] = $report->dependsOnAnalysis();
            unset($report);
            gc_collect_cycles();
        }

        $themesToRun = array_merge(...$themesToRun);
        if (empty($themesToRun)) {
            // Default values
            $themesToRun = $this->themesToRun;
        } else {
            $themesToRun = array_unique($themesToRun);
        }

        display("Running project '$project'" . PHP_EOL);
        display("Running the following analysis : ".implode(', ', $themesToRun));
        display("Producing the following reports : ".implode(', ', $reportToRun));

        display("Cleaning DB" . PHP_EOL);
        $analyze = new CleanDb($this->gremlin, $this->config, Tasks::IS_SUBTASK);
        $analyze->run();
        unset($analyze);
        $this->logTime('CleanDb');
        $this->addSnitch(array('step'    => 'Clean DB',
                               'project' => $this->config->project));
        $this->gremlin->resetConnection();
        
        display("Running files".PHP_EOL);
        $analyze = new Files($this->gremlin, $this->config, Tasks::IS_SUBTASK);
        $analyze->run();
        unset($analyze);
        $this->logTime('Files');
        $this->addSnitch(array('step'    => 'Files',
                               'project' => $this->config->project));

        $this->checkTokenLimit();

        $analyze = new Load($this->gremlin, $this->config, Tasks::IS_SUBTASK);
        try {
            $analyze->run();
        } catch (NoFileToProcess $e) {
            $this->datastore->addRow('hash', array('init error' => $e->getMessage(),
                                                   'status'     => 'Error',
                                           ));
        }
        unset($analyze);
        display("Project loaded\n");
        $this->logTime('Loading');

        // Always run this one first
        $this->analyzeThemes(['First'], $audit_start, true);

        $sqliteFilePrevious = "{$this->config->projects_root}/projects/{$this->config->project}/dump-1.sqlite";
        $sqliteFileFinal = "{$this->config->projects_root}/projects/{$this->config->project}/dump.sqlite";
        if (file_exists($sqliteFileFinal)) {
            copy($sqliteFileFinal, $sqliteFilePrevious);
        }

        // Dump is a child process
        // initialization and first collection (action done once)
        display('Initial dump');
        $shell = $this->config->php.' '.$this->config->executable.' dump -p '.$this->config->project.' -T First -collect';
        shell_exec($shell);
        $this->logTime('Dumped and inited');

        if (empty($this->config->program)) {
            $this->analyzeThemes($themesToRun, $audit_start, $this->config->quiet);
        } else {
            $this->analyzeOne($this->config->program, $audit_start, $this->config->quiet);
        }

        display("Analyzed project".PHP_EOL);
        $this->logTime('Analyze');
        $this->addSnitch(array('step'    => 'Analyzed',
                               'project' => $this->config->project));

        $this->logTime('Analyze');

        $args = array ( 1 => 'dump',
                        2 => '-p',
                        3 => $this->config->project,
                    );
        $dumpConfig = new Config($args);

        $dump = new Dump($this->gremlin, $dumpConfig, Tasks::IS_SUBTASK);
        foreach($this->config->themas as $name => $analyzers) {
            $dump->checkThemes($name, $analyzers);
        }
        
        foreach($reportToRun as $format) {
            display("Reporting $format".PHP_EOL);
            $this->addSnitch(array('step'    => 'Report : '.$format,
                                   'project' => $this->config->project));

            try {
                $args = array ( 1 => 'report',
                                2 => '-p',
                                3 => $this->config->project,
                                4 => '-file',
                                5 => constant("\Exakat\Reports\\$format::FILE_FILENAME"),
                                6 => '-format',
                                7 => $format,
                                );

                $reportConfig = new Config($args);
    
                $report = new Report($this->gremlin, $reportConfig, Tasks::IS_SUBTASK);

                $report->run();
            } catch (\Throwable $e) {
                echo "Error while building $format in $format.\n";
            }
            unset($reportConfig);
        }

        display('Reported project'.PHP_EOL);
        
        // Reset cache from Themes
        Themes::resetCache();
        $this->logTime('Final');
        $this->removeSnitch();
        display('End' . PHP_EOL);
    }

    private function logTime($step) {
        static $log, $begin, $end, $start;

        if ($log === null) {
            $log = fopen("{$this->project_dir}/log/project.timing.csv", 'w+');
        }

        $end = microtime(true);
        if ($begin === null) {
            $begin = $end;
            $start = $end;
        }

        fwrite($log, $step."\t".($end - $begin)."\t".($end - $start).PHP_EOL);
        $begin = $end;
    }

    private function analyzeOne($analyzers, $audit_start, $quiet) {
        $this->addSnitch(array('step'    => 'Analyzer',
                               'project' => $this->config->project));

        $args = array ( 1 => 'analyze',
                        2 => '-p',
                        3 => $this->config->project,
                        4 => '-P',
                        5 => $analyzers,
                        6 => '-norefresh',
                        7 => '-u'
                        );
        if ($quiet === true) {
            $args[] = '-q';
        }

        try {
            $analyzeConfig = new Config($args);

            $analyze = new Analyze($this->gremlin, $analyzeConfig, Tasks::IS_SUBTASK);
            $analyze->run();
            unset($analyze);
            unset($analyzeConfig);
            $this->logTime('Analyze : '.(is_array($analyzers) ? implode(', ', $analyzers) : $analyzers));

            $args = array ( 1 => 'dump',
                            2 => '-p',
                            3 => $this->config->project,
                            4 => '-P',
                            5 => $analyzers,
                            6 => '-u',
                        );
            $dumpConfig = new Config($args);

            $audit_end = time();
            $query = 'g.V().count()';
            $res = $this->gremlin->query($query);
            if ($res instanceof \stdClass) {
                $nodes = $res->results[0];
            } else {
                $nodes = $res[0];
            }
            $query = 'g.E().count()';
            $res = $this->gremlin->query($query);
            if ($res instanceof \stdClass) {
                $links = $res->results[0];
            } else {
                $links = $res[0];
            }

            $this->datastore->addRow('hash', array('audit_end'    => $audit_end,
                                                   'audit_length' => $audit_end - $audit_start,
                                                   'graphNodes'   => $nodes,
                                                   'graphLinks'   => $links));

            $dump = new Dump($this->gremlin, $dumpConfig, Tasks::IS_SUBTASK);
            $dump->run();
            unset($dump);
            unset($dumpConfig);
        } catch (\Exception $e) {
            echo "Error while running the Analyzer {$this->config->project}.\nTrying next analysis.\n";
            file_put_contents("{$this->config->projects_root}/projects/{$this->config->project}/log/analyze.final.log", $e->getMessage());
        }
    }

    private function analyzeThemes($themes, $audit_start, $quiet) {
        if (empty($themes)) {
            $themes = $this->config->project_themes;
        }

        if (!is_array($themes)) {
            $themes = array($themes);
        }
        
        $availableThemes = $this->themes->listAllThemes();

        $diff = array_diff($themes, $availableThemes);
        if (!empty($diff)) {
            display("Ignoring the following unknown themes : ".implode(', ', $diff).PHP_EOL);
        }
        
        $themes = array_intersect($availableThemes, $themes);
        display("Running the following themes : ".implode(', ', $themes).PHP_EOL);

        global $VERBOSE;
        $oldVerbose = $VERBOSE;
        $VERBOSE = false;
        foreach($themes as $theme) {
            $this->addSnitch(array('step'    => 'Analyze : '.$theme,
                                   'project' => $this->config->project));
            $themeForFile = strtolower(str_replace(' ', '_', trim($theme, '"')));

            $args = array ( 1 => 'analyze',
                            2 => '-p',
                            3 => $this->config->project,
                            4 => '-T',
                            5 => $theme,
                            6 => '-norefresh',
                            7 => '-u'
                            );
            if ($quiet === true) {
                $args[] = '-q';
            }

            try {
                $analyzeConfig = new Config($args);

                $analyze = new Analyze($this->gremlin, $analyzeConfig, Tasks::IS_SUBTASK);
                $analyze->run();
                unset($analyze);
                unset($analyzeConfig);
                $this->logTime("Analyze : $theme");

                $args = array ( 1 => 'dump',
                                2 => '-p',
                                3 => $this->config->project,
                                4 => '-T',
                                5 => trim($theme, '"'), // No need to protect anymore, as this is internal
                                6 => '-u',
                            );

                $dumpConfig = new Config($args);

                $audit_end = time();
                $query = "g.V().count()";
                $res = $this->gremlin->query($query);
                if (isset($res->results)) {
                    $nodes = $res->results[0];
                } else {
                    $nodes = $res[0];
                }
                $query = "g.E().count()";
                $res = $this->gremlin->query($query);
                if (isset($res->results)) {
                    $links = $res->results[0];
                } else {
                    $links = $res[0];
                }

                $finalMark = array('audit_end'    => $audit_end,
                                   'audit_length' => $audit_end - $audit_start,
                                   'graphNodes'   => $nodes,
                                   'graphLinks'   => $links);
                $this->datastore->addRow('hash', $finalMark);
                
                $dump = new Dump($this->gremlin, $dumpConfig, Tasks::IS_SUBTASK);
                $dump->run();
                $dump->finalMark($finalMark);
                unset($dump);
                unset($dumpConfig);
                gc_collect_cycles();
                $this->logTime("Dumped : $theme");
            } catch (\Exception $e) {
                echo "Error while running the Analyze $theme.\nTrying next analysis.\n";
                file_put_contents("{$this->config->projects_root}/projects/{$this->config->project}/log/analyze.$themeForFile.final.log", $e->getMessage());
            }
        }
        $VERBOSE = $oldVerbose;
    }
    
    private function generateName() {
        $ini = parse_ini_file("{$this->config->dir_root}/data/audit_names.ini");
        
        $names = $ini['names'];
        $adjectives = $ini['adjectives'];
        
        shuffle($names);
        shuffle($adjectives);
        
        $x = mt_rand(0, PHP_INT_MAX);
        
        $name = $names[ $x % (count($names) - 1)];
        $adjective = $adjectives[ $x % (count($adjectives) - 1)];

        return ucfirst($adjective).' '.$name;
    }
}

?>
