<?php
use PHPUnit\Framework\TestCase;


class pakePHPDocTest extends TestCase
{
    public function testSimple()
    {
        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test1');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('', $desc[0]);
        $this->assertEquals('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test2');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description', $desc[0]);
        $this->assertEquals('long description', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test3');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description still short description', $desc[0]);
        $this->assertEquals('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test4');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description', $desc[0]);
        $this->assertEquals('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test5');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description. still short description', $desc[0]);
        $this->assertEquals('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test6');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description. still short description', $desc[0]);
        $this->assertEquals('', $desc[1]);

        $desc = pakePHPDoc::getDescriptions('pakePHPDocTest_test7');

        $this->assertEquals(2, count($desc));
        $this->assertEquals('short description.', $desc[0]);
        $this->assertEquals('long description', $desc[1]);
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
