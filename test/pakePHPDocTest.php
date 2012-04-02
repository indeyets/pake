<?php

class pakePHPDocTest extends UnitTestCase
{
    public function testSimple()
    {
        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test1');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('', $desc[0]);
        $this->assertEqual('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test2');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description', $desc[0]);
        $this->assertEqual('long description', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test3');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description still short description', $desc[0]);
        $this->assertEqual('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test4');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description', $desc[0]);
        $this->assertEqual('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test5');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description. still short description', $desc[0]);
        $this->assertEqual('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test6');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description. still short description', $desc[0]);
        $this->assertEqual('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test7');

        $this->assertEqual(2, count($desc));
        $this->assertEqual('short description.', $desc[0]);
        $this->assertEqual('long description', $desc[1]);
    }
}



function pakePHPDocTest_test1() {}

/**
 * short description
 *
 * long description
 */
function pakePHPDocTest_test2() {}

/**
 * short description
 * still short description
 */
function pakePHPDocTest_test3() {}

/**
 * short description
 * @something not a description
 */
function pakePHPDocTest_test4() {}

/**
 * short description.
 * still short description
 */
function pakePHPDocTest_test5() {}

/**
* short description.
* still short description
*/
function pakePHPDocTest_test6() {}

/**
* short description.
*
* long description
*/
function pakePHPDocTest_test7() {}
