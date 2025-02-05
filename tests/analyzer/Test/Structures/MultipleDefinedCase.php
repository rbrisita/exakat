<?php

namespace Test\Structures;

use Test\Analyzer;

include_once dirname(__DIR__, 2).'/Test/Analyzer.php';

class MultipleDefinedCase extends Analyzer {
    /* 4 methods */

    public function testStructures_MultipleDefinedCase01()  { $this->generic_test('Structures_MultipleDefinedCase.01'); }
    public function testStructures_MultipleDefinedCase02()  { $this->generic_test('Structures_MultipleDefinedCase.02'); }
    public function testStructures_MultipleDefinedCase03()  { $this->generic_test('Structures_MultipleDefinedCase.03'); }
    public function testStructures_MultipleDefinedCase04()  { $this->generic_test('Structures/MultipleDefinedCase.04'); }
}
?>