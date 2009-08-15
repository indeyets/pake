<?php

class pakeNumberCompareTest extends UnitTestCase
{
  public function test_simple()
  {
    // >20
    $c = new pakeNumberCompare('>20');
		$this->assertTrue($c->test(21));
		$this->assertTrue(!$c->test(20));
		$this->assertTrue(!$c->test(10));

    // <20
    $c = new pakeNumberCompare('<20');
		$this->assertTrue(!$c->test(21));
		$this->assertTrue(!$c->test(20));
		$this->assertTrue($c->test(19));

    // >= 20
    $c = new pakeNumberCompare('>=20');
		$this->assertTrue($c->test(21));
		$this->assertTrue($c->test(20));
		$this->assertTrue(!$c->test(10));

    // <=20
    $c = new pakeNumberCompare('<=20');
		$this->assertTrue(!$c->test(21));
		$this->assertTrue($c->test(20));
		$this->assertTrue($c->test(19));

    // 20
    $c = new pakeNumberCompare('20');
		$this->assertTrue(!$c->test(21));
		$this->assertTrue($c->test(20));
		$this->assertTrue(!$c->test(19));

    // K, M, G
    $c = new pakeNumberCompare('2K');
		$this->assertTrue($c->test(2000));
    $c = new pakeNumberCompare('2M');
		$this->assertTrue($c->test(2000000));
    $c = new pakeNumberCompare('2G');
		$this->assertTrue($c->test(2000000000));

    // Ki, Mi, Gi
    $c = new pakeNumberCompare('2Ki');
		$this->assertTrue($c->test(2048));
    $c = new pakeNumberCompare('2Mi');
		$this->assertTrue($c->test(2097152));
    $c = new pakeNumberCompare('2Gi');
		$this->assertTrue($c->test(2147483648));
	}
}

?>