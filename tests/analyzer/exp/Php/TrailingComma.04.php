<?php

$expected     = array('foo(1, 2,  )', 
                      'foo(1, 2,  )(2, 3,  )', 
                      'foo(1, 2, 3,  )', 
                      'foo(1, 2, 3,  )(2, 3,  )', 
                      'foo(1, 2, 3, 4,  )', 
                      'foo(1, 2, 3, 4,  )(2, 3,  )', 
                      'foo(1, 2, 3, 4)(2, 3,  )', 
                      'foo(1, 2, 3)(2, 3,  )',
                      'foo(1, 2)(2, 3,  )', 
                     );

$expected_not = array('foo(1, 2  )',
                      'foo(1, 2, 3  )',
                      'foo(1, 2, 3, 4  )',
                     );

?>