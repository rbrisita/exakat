<?php

namespace Test\Classes;

use Test\Analyzer;

include_once dirname(__DIR__, 2).'/Test/Analyzer.php';

class SameNameAsFile extends Analyzer {
    /* 1 methods */

    public function testClasses_SameNameAsFile01()  { $this->generic_test('Classes/SameNameAsFile.01'); }
}
?>