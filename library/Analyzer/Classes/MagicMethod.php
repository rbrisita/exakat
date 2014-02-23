<?php

namespace Analyzer\Classes;

use Analyzer;

class MagicMethod extends Analyzer\Analyzer {
    
    public function analyze() {
        $magicMethods = array(' __construct()', '__destruct()', 
                              '__call()', '__callStatic()', '__get()', '__set()', '__isset()', '__unset()', 
                              '__sleep()', '__wakeup()', 
                              '__toString()', 
                              '__invoke()', '__set_state()', '__clone');
        
        $this->atomIs("Class")
             ->outIs('BLOCK')
             ->outIs('CODE')
             ->atomInside('Function')
             ->outIs('NAME')
             ->code($magicMethods);
    }
}

?>