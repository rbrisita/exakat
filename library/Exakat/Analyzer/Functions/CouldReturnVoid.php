<?php
/*
 * Copyright 2012-2019 Damien Seguy – Exakat SAS <contact(at)exakat.io>
 * This file is part of Exakat.
 *
 * Exakat is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Exakat is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Exakat.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://exakat.io/>.
 *
*/

namespace Exakat\Analyzer\Functions;

use Exakat\Analyzer\Analyzer;

class CouldReturnVoid extends Analyzer {
    public function analyze() {
        // function foo() {} // empty function
        $this->atomIs('Function')
             ->outIs('BLOCK')
             ->noAtomInside('Return')
             ->back('first');
        $this->prepareQuery();

        // function foo() { } // always return ;
        $this->atomIs('Function')
             ->outIs('BLOCK')
             ->hasAtomInside('Return')
             ->not(
                $this->side()
                     ->filter(
                        $this->side()
                             ->atomInsideNoDefinition('Return')
                             ->outIs('RETURN')
                             ->atomIsNot('Void')
                     )
             )
             ->back('first');
        $this->prepareQuery();
    }
}

?>
