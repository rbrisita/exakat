<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Classes_PropertyNeverUsed extends Analyzer {
    /* 9 methods */

    public function testClasses_PropertyNeverUsed01()  { $this->generic_test('Classes_PropertyNeverUsed.01'); }
    public function testClasses_PropertyNeverUsed02()  { $this->generic_test('Classes_PropertyNeverUsed.02'); }
    public function testClasses_PropertyNeverUsed03()  { $this->generic_test('Classes_PropertyNeverUsed.03'); }
    public function testClasses_PropertyNeverUsed04()  { $this->generic_test('Classes_PropertyNeverUsed.04'); }
    public function testClasses_PropertyNeverUsed05()  { $this->generic_test('Classes_PropertyNeverUsed.05'); }
    public function testClasses_PropertyNeverUsed06()  { $this->generic_test('Classes_PropertyNeverUsed.06'); }
    public function testClasses_PropertyNeverUsed07()  { $this->generic_test('Classes_PropertyNeverUsed.07'); }
    public function testClasses_PropertyNeverUsed08()  { $this->generic_test('Classes_PropertyNeverUsed.08'); }
    public function testClasses_PropertyNeverUsed09()  { $this->generic_test('Classes_PropertyNeverUsed.09'); }
}
?>