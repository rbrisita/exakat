<?php

$expected     = array('$d = [ ]', 
                      '$a = [ ]', 
                      '$e = [ ]', 
                      '$x->y = [ ]', 
                      '$b = [ ]'
);

$expected_not = array('$e[45]',
                      '$a[2]',
                      '$a[3]',
                      '$b[2]',
                      '$d[2]',
                      '$x->y[33]',
                      '$x->y3 = [ ]',
                      '$x->y[333]'
                      );

?>