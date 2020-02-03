<?php
use PHPUnit\Framework\TestCase;


class pakeGetoptTest extends TestCase
{
  public function test_simple()
  {
    $options = array(
      array('--test',  '-t', pakeGetopt::NO_ARGUMENT, "just a test."),
    );

    $g = new pakeGetopt($options);
    $g->parse('--test');
    $this->assertTrue($g->has_option('test'));
    try
    {
      $this->assertTrue($g->get_option('test'));
    }
    catch (pakeException $e)
    {
      $this->assertEquals('pakeGetopt: You cannot get a value for a NO_ARGUMENT option.', $e->getMessage());
    }

    $g->parse('-t');
    $this->assertTrue($g->has_option('test'));

    try
    {
      $g->parse('-v');
    }
    catch (pakeException $e)
    {
      $this->assertEquals('pakeGetopt: unrecognized option -v.', $e->getMessage());
    }
  }

  public function test_arguments()
  {
    $options = array(
      array('--test',  '-t', pakeGetopt::NO_ARGUMENT, "just a test."),
    );

    $g = new pakeGetopt($options);
    $g->parse('--test toto with arguments');
    $this->assertEquals(array('toto', 'with', 'arguments'), $g->get_arguments());

    $g->parse('toto with arguments');
    $this->assertEquals(array('toto', 'with', 'arguments'), $g->get_arguments());

    $g->parse('-- --toto with arguments');
    $this->assertEquals(array('--toto', 'with', 'arguments'), $g->get_arguments());

    $g->parse('-t -- --toto with arguments');
    $this->assertEquals(array('--toto', 'with', 'arguments'), $g->get_arguments());
  }

  public function test_options()
  {
    $options = array(
      array('--test',  '-t', pakeGetopt::REQUIRED_ARGUMENT, "just a test."),
      array('test1',  't1', pakeGetopt::REQUIRED_ARGUMENT, "just a test."),
      array('--opt',  '-o', pakeGetopt::OPTIONAL_ARGUMENT, "just a test."),
      array('--opt1',  '-p', pakeGetopt::NO_ARGUMENT, "just a test."),
      array('--opt2',  '-r', pakeGetopt::NO_ARGUMENT, "just a test."),
    );

    $g = new pakeGetopt($options);
    try
    {
      $g->parse('--test');
    }
    catch (pakeException $e)
    {
      $this->assertPattern('/requires an argument/', $e->getMessage());
    }

    $g->parse('--test=Toto');
    $this->assertEquals(true, $g->has_option('test'));
    $this->assertEquals('Toto', $g->get_option('test'));

    $g->parse('--test="Foo bar"');
    $this->assertEquals('Foo bar', $g->get_option('test'));

    $g->parse('--test=\'Foo  bar    bar\'');
    $this->assertEquals('Foo  bar    bar', $g->get_option('test'));

    $g->parse('-pr');
    $this->assertTrue($g->has_option('opt1'));
    $this->assertTrue($g->has_option('opt2'));

    $g->parse('-tFoo');
    $this->assertEquals('Foo', $g->get_option('test'));

    $g->parse('-t Foo');
    $this->assertEquals('Foo', $g->get_option('test'));

    $g->parse('-o Foo');
    $this->assertEquals('Foo', $g->get_option('opt'));

    $g->parse('-o -p');
    $this->assertTrue($g->has_option('opt'));
    $this->assertTrue($g->has_option('opt1'));

    $g->parse('-t "Foo bar"');
    $this->assertEquals('Foo bar', $g->get_option('test'));

    $g->parse('-t"Foo bar"');
    $this->assertEquals('Foo bar', $g->get_option('test'));

    $g->parse('-t\'Foo bar\'');
    $this->assertEquals('Foo bar', $g->get_option('test'));

    $g->parse('-t"Foo bar" --test1="Another test"');
    $this->assertEquals('Foo bar', $g->get_option('test'));
    $this->assertEquals('Another test', $g->get_option('test1'));

    $g->parse('-o');
    $this->assertTrue($g->get_option('opt'));

    $g->parse('--opt');
    $this->assertTrue($g->get_option('opt'));

    $g->parse('--opt="foo"');
    $this->assertEquals('foo', $g->get_option('opt'));
    $this->assertEquals('', $g->get_option('test'));
  }
}

?>
