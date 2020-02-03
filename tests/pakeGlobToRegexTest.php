<?php
use PHPUnit\Framework\TestCase;


class pakeGlobToRegexTest extends TestCase
{
  public function test_simple()
  {
    // absolute string
		$this->assertEquals(1, $this->match_glob('foo', 'foo'));
		$this->assertEquals(0, $this->match_glob('foo', 'foobar'));

    // * wildcard
		$this->assertEquals(1, $this->match_glob('foo.*', 'foo.'));
		$this->assertEquals(1, $this->match_glob('foo.*', 'foo.bar'));
		$this->assertEquals(0, $this->match_glob('foo.*', 'gfoo.bar'));

    // ? wildcard
		$this->assertEquals(1, $this->match_glob('foo.?p', 'foo.cp'));
		$this->assertEquals(0, $this->match_glob('foo.?p', 'foo.cd'));

    // .{alternation,or,something}
		$this->assertEquals(1, $this->match_glob('foo.{c,h}', 'foo.h'));
		$this->assertEquals(1, $this->match_glob('foo.{c,h}', 'foo.c'));
		$this->assertEquals(0, $this->match_glob('foo.{c,h}', 'foo.o'));

    // \escaping
		$this->assertEquals(1, $this->match_glob('foo.\\{c,h}\\*', 'foo.{c,h}*'));
		$this->assertEquals(0, $this->match_glob('foo.\\{c,h}\\*', 'foo.\\c'));

    // escape ()
		$this->assertEquals(1, $this->match_glob('foo.(bar)', 'foo.(bar)'));

    // strict . rule fail
		$this->assertEquals(0, $this->match_glob('*.foo',  '.file.foo'));

    // strict . rule match
		$this->assertEquals(1, $this->match_glob('.*.foo', '.file.foo'));

    // relaxed . rule
    pakeGlobToRegex::setStrictLeadingDot(false);
		$this->assertEquals(1, $this->match_glob('*.foo', '.file.foo'));
    pakeGlobToRegex::setStrictLeadingDot(true);

    // strict wildcard / fail
		$this->assertEquals(0, $this->match_glob('*.fo?',   'foo/file.fob'));

    // strict wildcard / match
		$this->assertEquals(1, $this->match_glob('*/*.fo?', 'foo/file.fob'));

    // relaxed wildcard /
    pakeGlobToRegex::setStrictWildcardSlash(false);
		$this->assertEquals(1, $this->match_glob('*.fo?', 'foo/file.fob'));
    pakeGlobToRegex::setStrictWildcardSlash(true);

    // more strict wildcard / fail
		$this->assertEquals(0, $this->match_glob('foo/*.foo', 'foo/.foo'));

    // more strict wildcard / match
		$this->assertEquals(1, $this->match_glob('foo/.f*',   'foo/.foo'));

    // relaxed wildcard /
    pakeGlobToRegex::setStrictWildcardSlash(false);
		$this->assertEquals(1, $this->match_glob('*.foo', 'foo/.foo'));
    pakeGlobToRegex::setStrictWildcardSlash(true);

    // properly escape +
		$this->assertEquals(1, $this->match_glob('f+.foo', 'f+.foo'));
		$this->assertEquals(0, $this->match_glob('f+.foo', 'ffff.foo'));

    // handle embedded \\n
		$this->assertEquals(1, $this->match_glob("foo\nbar", "foo\nbar"));
		$this->assertEquals(0, $this->match_glob("foo\nbar", "foobar"));

    // [abc]
		$this->assertEquals(1, $this->match_glob('test[abc]', 'testa'));
		$this->assertEquals(1, $this->match_glob('test[abc]', 'testb'));
		$this->assertEquals(1, $this->match_glob('test[abc]', 'testc'));
		$this->assertEquals(0, $this->match_glob('test[abc]', 'testd'));

    // escaping \$
		$this->assertEquals(1, $this->match_glob('foo$bar.*', 'foo$bar.c'));

    // escaping ^
		$this->assertEquals(1, $this->match_glob('foo^bar.*', 'foo^bar.c'));

    // escaping ^escaping |
		$this->assertEquals(1, $this->match_glob('foo|bar.*', 'foo|bar.c'));

    // {foo,{bar,baz}}
		$this->assertEquals(1, $this->match_glob('{foo,{bar,baz}}', 'foo'));
		$this->assertEquals(1, $this->match_glob('{foo,{bar,baz}}', 'bar'));
		$this->assertEquals(1, $this->match_glob('{foo,{bar,baz}}', 'baz'));
		$this->assertEquals(0, $this->match_glob('{foo,{bar,baz}}', 'foz'));
	}

  private function match_glob($glob, $text)
  {
    $regex = pakeGlobToRegex::glob_to_regex($glob);
    return preg_match($regex, $text);
  }
}

?>
