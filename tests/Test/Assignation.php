<?php

namespace Test;

include_once(dirname(dirname(__DIR__)).'/library/Autoload.php');
spl_autoload_register('Autoload::autoload_test');
spl_autoload_register('Autoload::autoload_phpunit');

class Assignation extends Tokenizeur {
    /* 20 methods */
    public function testAssignation01()  { $this->generic_test('Assignation.01'); }
    public function testAssignation02()  { $this->generic_test('Assignation.02'); }
    public function testAssignation03()  { $this->generic_test('Assignation.03'); }
    public function testAssignation04()  { $this->generic_test('Assignation.04'); }
    public function testAssignation05()  { $this->generic_test('Assignation.05'); }
    public function testAssignation06()  { $this->generic_test('Assignation.06'); }
    public function testAssignation07()  { $this->generic_test('Assignation.07'); }
    public function testAssignation08()  { $this->generic_test('Assignation.08'); }
    public function testAssignation09()  { $this->generic_test('Assignation.09'); }
    public function testAssignation10()  { $this->generic_test('Assignation.10'); }
    public function testAssignation11()  { $this->generic_test('Assignation.11'); }
    public function testAssignation12()  { $this->generic_test('Assignation.12'); }
    public function testAssignation13()  { $this->generic_test('Assignation.13'); }
    public function testAssignation14()  { $this->generic_test('Assignation.14'); }
    public function testAssignation15()  { $this->generic_test('Assignation.15'); }
    public function testAssignation16()  { $this->generic_test('Assignation.16'); }
    public function testAssignation17()  { $this->generic_test('Assignation.17'); }
    public function testAssignation18()  { $this->generic_test('Assignation.18'); }
    public function testAssignation19()  { $this->generic_test('Assignation.19'); }
    public function testAssignation20()  { $this->generic_test('Assignation.20'); }
}
?>