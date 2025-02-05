<?php
/*
 * Copyright 2012-2019 Damien Seguy – Exakat SAS <contact(at)exakat.io>
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


namespace Exakat\Query\DSL;

use Exakat\Query\Query;
use Exakat\Analyzer\Analyzer;
use Exakat\Data\Dictionary;

class CodeIs extends DSL {
    public function run() {
        list($code, $translate, $caseSensitive) = func_get_args();

        if (is_array($code) && empty($code)) {
            return new Command(Query::STOP_QUERY);
        }
        
        $col = $caseSensitive === Analyzer::CASE_INSENSITIVE ? 'lccode' : 'code';
        
        if ($translate === Analyzer::TRANSLATE) {
            $translatedCode = array();
            $code = makeArray($code);
            $translatedCode = $this->dictCode->translate($code, $caseSensitive === Analyzer::CASE_INSENSITIVE ? Dictionary::CASE_INSENSITIVE : Dictionary::CASE_SENSITIVE);

            if (empty($translatedCode)) {
                return new Command(Query::STOP_QUERY);
            }
            
            return new Command("filter{ it.get().value(\"$col\") in ***; }", array($translatedCode));
        } else {
            return new Command("filter{ it.get().value(\"$col\") in ***; }", array(makeArray($code)));
        }
    }
}
?>
