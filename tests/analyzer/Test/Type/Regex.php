<?php

namespace Test\Type;

use Test\Analyzer;

include_once './Test/Analyzer.php';

class Regex extends Analyzer {
    /* 4 methods */

    public function testType_Regex01()  { $this->generic_test('Type/Regex.01'); }
    public function testType_Regex02()  { $this->generic_test('Type/Regex.02'); }
    public function testType_Regex03()  { $this->generic_test('Type/Regex.03'); }
    public function testType_Regex04()  { $this->generic_test('Type/Regex.04'); }
}
?>