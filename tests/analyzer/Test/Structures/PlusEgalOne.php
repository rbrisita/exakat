<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Structures_PlusEgalOne extends Analyzer {
    /* 3 methods */

    public function testStructures_PlusEgalOne01()  { $this->generic_test('Structures_PlusEgalOne.01'); }
    public function testStructures_PlusEgalOne02()  { $this->generic_test('Structures_PlusEgalOne.02'); }
    public function testStructures_PlusEgalOne03()  { $this->generic_test('Structures_PlusEgalOne.03'); }
}
?>