<?php

namespace Test;

include_once(dirname(dirname(dirname(dirname(__DIR__)))).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');
spl_autoload_register('Autoload::autoload_library');

class Php_TrailingComma extends Analyzer {
    /* 4 methods */

    public function testPhp_TrailingComma01()  { $this->generic_test('Php/TrailingComma.01'); }
    public function testPhp_TrailingComma02()  { $this->generic_test('Php/TrailingComma.02'); }
    public function testPhp_TrailingComma03()  { $this->generic_test('Php/TrailingComma.03'); }
    public function testPhp_TrailingComma04()  { $this->generic_test('Php/TrailingComma.04'); }
}
?>