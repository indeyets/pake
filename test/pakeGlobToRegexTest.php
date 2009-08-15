<?php

class pakeGlobToRegexTest extends UnitTestCase
{
  public function test_simple()
  {
    // absolute string
		$this->assertTrue($this->match_glob('foo', 'foo'));
		$this->assertTrue(!$this->match_glob('foo', 'foobar'));

    // * wildcard
		$this->assertTrue($this->match_glob('foo.*', 'foo.'));
		$this->assertTrue($this->match_glob('foo.*', 'foo.bar'));
		$this->assertTrue(!$this->match_glob('foo.*', 'gfoo.bar'));

    // ? wildcard
		$this->assertTrue($this->match_glob('foo.?p', 'foo.cp'));
		$this->assertTrue(!$this->match_glob('foo.?p', 'foo.cd'));

    // .{alternation,or,something}
		$this->assertTrue($this->match_glob('foo.{c,h}', 'foo.h'));
		$this->assertTrue($this->match_glob('foo.{c,h}', 'foo.c'));
		$this->assertTrue(!$this->match_glob('foo.{c,h}', 'foo.o'));

    // \escaping
		$this->assertTrue($this->match_glob('foo.\\{c,h}\\*', 'foo.{c,h}*'));
		$this->assertTrue(!$this->match_glob('foo.\\{c,h}\\*', 'foo.\\c'));

    // escape ()
		$this->assertTrue($this->match_glob('foo.(bar)', 'foo.(bar)'));

    // strict . rule fail
		$this->assertTrue(!$this->match_glob('*.foo',  '.file.foo'));

    // strict . rule match
		$this->assertTrue($this->match_glob('.*.foo', '.file.foo'));

    // relaxed . rule
    pakeGlobToRegex::setStrictLeadingDot(false);
		$this->assertTrue($this->match_glob('*.foo', '.file.foo'));
    pakeGlobToRegex::setStrictLeadingDot(true);

    // strict wildcard / fail
		$this->assertTrue(!$this->match_glob('*.fo?',   'foo/file.fob'));

    // strict wildcard / match
		$this->assertTrue($this->match_glob('*/*.fo?', 'foo/file.fob'));

    // relaxed wildcard /
    pakeGlobToRegex::setStrictWildcardSlash(false);
		$this->assertTrue($this->match_glob('*.fo?', 'foo/file.fob'));
    pakeGlobToRegex::setStrictWildcardSlash(true);

    // more strict wildcard / fail
		$this->assertTrue(!$this->match_glob('foo/*.foo', 'foo/.foo'));

    // more strict wildcard / match
		$this->assertTrue($this->match_glob('foo/.f*',   'foo/.foo'));

    // relaxed wildcard /
    pakeGlobToRegex::setStrictWildcardSlash(false);
		$this->assertTrue($this->match_glob('*.foo', 'foo/.foo'));
    pakeGlobToRegex::setStrictWildcardSlash(true);

    // properly escape +
		$this->assertTrue($this->match_glob('f+.foo', 'f+.foo'));
		$this->assertTrue(!$this->match_glob('f+.foo', 'ffff.foo'));

    // handle embedded \\n
		$this->assertTrue($this->match_glob("foo\nbar", "foo\nbar"));
		$this->assertTrue(!$this->match_glob("foo\nbar", "foobar"));

    // [abc]
		$this->assertTrue($this->match_glob('test[abc]', 'testa'));
		$this->assertTrue($this->match_glob('test[abc]', 'testb'));
		$this->assertTrue($this->match_glob('test[abc]', 'testc'));
		$this->assertTrue(!$this->match_glob('test[abc]', 'testd'));

    // escaping \$
		$this->assertTrue($this->match_glob('foo$bar.*', 'foo$bar.c'));

    // escaping ^
		$this->assertTrue($this->match_glob('foo^bar.*', 'foo^bar.c'));

    // escaping ^escaping |
		$this->assertTrue($this->match_glob('foo|bar.*', 'foo|bar.c'));

    // {foo,{bar,baz}}
		$this->assertTrue($this->match_glob('{foo,{bar,baz}}', 'foo'));
		$this->assertTrue($this->match_glob('{foo,{bar,baz}}', 'bar'));
		$this->assertTrue($this->match_glob('{foo,{bar,baz}}', 'baz'));
		$this->assertTrue(!$this->match_glob('{foo,{bar,baz}}', 'foz'));
	}

  private function match_glob($glob, $text)
  {
    $regex = pakeGlobToRegex::glob_to_regex($glob);
    return preg_match($regex, $text);
  }
}

?>