<?php
use PHPUnit\Framework\TestCase;

class pakeFinderTest extends TestCase
{
  private $dir = '';

  public function setUp()
  {
    $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR.'finder_tests';
  }

  public function test_simple()
  {
    $finder = pakeFinder::type('any');
    $this->assertInstanceOf('pakeFinder', $finder);

    $finder = pakeFinder::type('file')->name('*.php')->prune('*.svn');
    $this->assertEquals(2, count($finder->in($this->dir)));
  }

  public function test_args_as_array()
  {
    $files1 = pakeFinder::type('any')->name('*.php', '*.txt')->prune('.svn')->in($this->dir);
    $files2 = pakeFinder::type('any')->name(array('*.php', '*.txt'))->prune('.svn')->in($this->dir);
    sort($files1);
    sort($files2);
    $this->assertEquals($files1, $files2);
  }

  public function test_file_and_directory()
  {
    $finder = pakeFinder::type('any');
    $this->assertInstanceOf('pakeFinder', $finder);

    $files_and_directories = pakeFinder::type('any')->prune('.svn')->in($this->dir);

    $files = pakeFinder::type('file')->prune('.svn')->in($this->dir);
    $directories = pakeFinder::type('directory')->prune('.svn')->in($this->dir);

    $a = array_merge($directories, $files);
    $b = $files_and_directories;
    sort($a);
    sort($b);
    $this->assertEquals($a, $b);

    $directories1 = pakeFinder::type('dir')->prune('.svn')->in($this->dir);
    $this->assertEquals($directories, $directories1);

    $files1 = pakeFinder::type('file')->prune('.svn')->in($this->dir);
    $this->assertEquals($files, $files1);
  }

  public function test_full()
  {
    $files = pakeFinder::type('file')->name('/^file/')->size('> 0K')->size('< 10K')->prune('/^dir*/')->prune('.svn')->in($this->dir);
    $this->assertEquals(1, count($files));

    $finder = pakeFinder::type('file');
    $finder->name('/^file/');
    $finder->size('> 0K');
    $finder->size('< 10K');
    $finder->prune('/^dir*/');
    $finder->prune('.svn');
    $files = $finder->in($this->dir);
    $this->assertEquals(1, count($files));
  }

  public function test_name()
  {
    $files = pakeFinder::type('any')->name('*.php')->prune('.svn')->in($this->dir);
    $this->assertEquals(2, count($files));
    $ok = true;
    foreach ($files as $file)
    {
      if (!preg_match('/\.php$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files = pakeFinder::type('any')->name('*.php', '*.txt')->prune('.svn')->in($this->dir);
    $this->assertEquals(3, count($files));
    $ok = true;
    foreach ($files as $file)
    {
      if (!preg_match('/\.(php|txt)$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files = pakeFinder::type('any')->name('*.php')->name('*.txt')->prune('.svn')->in($this->dir);
    $this->assertEquals(3, count($files));
    $ok = true;
    foreach ($files as $file)
    {
      if (!preg_match('/\.(php|txt)$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files = pakeFinder::type('any')->name('/\.php$/', '/\.txt$/')->prune('.svn')->in($this->dir);
    $this->assertEquals(3, count($files));
    $ok = true;
    foreach ($files as $file)
    {
      if (!preg_match('/\.(php|txt)$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files = pakeFinder::type('any')->name('file12.php')->prune('.svn')->in($this->dir);
    $this->assertEquals(1, count($files));
  }

  public function test_relative()
  {
    $files = pakeFinder::type('file')->name('*.php')->prune('.svn')->in($this->dir);
    $ok = true;
    foreach ($files as $file)
    {
      if (!preg_match('/^'.preg_quote($this->dir, '/').'/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files1 = pakeFinder::type('file')->relative()->name('*.php')->prune('.svn')->in($this->dir);
    $ok = true;
    foreach ($files1 as $file)
    {
      if (preg_match('/^'.preg_quote($this->dir, '/').'/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    sort($files);
    sort($files1);
    $ok = true;
    for ($i = 0; $i < count($files); $i++)
    {
      if (realpath($files[$i]) != realpath($this->dir.'/'.$files1[$i])) $ok = false;
    }
    $this->assertTrue($ok);
  }

  public function test_not_name()
  {
    $files = pakeFinder::type('any')->not_name('*.php')->prune('.svn')->in($this->dir);
    $ok = true;
    foreach ($files as $file)
    {
      if (preg_match('/\.php$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);

    $files1 = pakeFinder::type('any')->name('*.php')->prune('.svn')->in($this->dir);
    $files2 = pakeFinder::type('any')->prune('.svn')->in($this->dir);
    $files3 = array_merge($files, $files1);
    sort($files3);
    sort($files2);
    $this->assertEquals($files3, $files2);

    $files = pakeFinder::type('file')->name('file2*')->not_name('*.php')->prune('.svn')->relative()->in($this->dir);
    $ok = true;
    foreach ($files as $file)
    {
      if (preg_match('/\.php$/', $file)) $ok = false;
      else if (!preg_match('/(\/|\\\\|^)file2/', $file)) $ok = false;
    }
    $this->assertTrue($ok);
  }

  public function test_depth()
  {
    foreach (array(1, 2) as $mindepth)
    {
      $files = pakeFinder::type('any')->relative()->mindepth($mindepth)->prune('.svn')->in($this->dir);
      $ok = true;
      foreach ($files as $file)
      {
        if (substr_count($file, DIRECTORY_SEPARATOR) < $mindepth) $ok = false;
      }
      $this->assertTrue($ok);
    }

    foreach (array(1, 2) as $maxdepth)
    {
      $files = pakeFinder::type('any')->relative()->maxdepth($maxdepth)->prune('.svn')->in($this->dir);
      $ok = true;
      foreach ($files as $file)
      {
        if (substr_count($file, '/') > $maxdepth) $ok = false;
      }
      $this->assertTrue($ok);
    }

    foreach (array(1, 2) as $depth)
    {
      $files = pakeFinder::type('any')->relative()->maxdepth($depth)->mindepth($depth)->prune('.svn')->in($this->dir);
      $ok = true;
      foreach ($files as $file)
      {
        if (substr_count($file, DIRECTORY_SEPARATOR) != $depth) $ok = false;
      }
      $this->assertTrue($ok);
    }
  }

  public function test_discard()
  {
    $files = pakeFinder::type('any')->discard('*.php', '*.txt')->prune('.svn')->in($this->dir);
    $ok = true;
    foreach ($files as $file)
    {
      if (preg_match('/\.(php|txt)$/', $file)) $ok = false;
    }
    $this->assertTrue($ok);
  }

  public function test_prune()
  {
    $files = pakeFinder::type('any')->relative()->prune('/^dir*/')->prune('.svn')->in($this->dir);
    $ok = true;
    foreach ($files as $file)
    {
      if (substr_count($file, '/') != 0) $ok = false;
    }
    $this->assertTrue($ok);
  }

  public function test_size()
  {
    $files = pakeFinder::type('file')->size('> 100K')->prune('.svn')->in($this->dir);
    $this->assertEquals(array(), $files);

    $files = pakeFinder::type('file')->size('> 2K')->prune('.svn')->in($this->dir);
    $this->assertEquals(1, count($files));

    $files = pakeFinder::type('file')->size('> 2K')->size('< 3K')->prune('.svn')->in($this->dir);
    $this->assertEquals(0, count($files));
  }

  public function test_in()
  {
    $files = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir3', $this->dir.'/dir1/dir2/dir4');
    $files1 = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir3');
    $files2 = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir4');
    sort($files);
    $files3 = array_merge($files1, $files2);
    sort($files3);
    $this->assertEquals($files3, $files);

    $files4 = pakeFinder::type('file')->prune('.svn')->in(array($this->dir.'/dir1/dir2/dir3', $this->dir.'/dir1/dir2/dir4'));
    sort($files4);
    $this->assertEquals($files4, $files);

    $files = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir3', $this->dir.'/dir1/dir2/dir4', $this->dir.'/dir1/dir2/dir4');
    $files1 = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir3');
    $files2 = pakeFinder::type('file')->prune('.svn')->in($this->dir.'/dir1/dir2/dir4');
    sort($files);
    $files3 = array_merge($files1, $files2);
    sort($files3);
    $this->assertEquals($files3, $files);

    try
    {
      $files = pakeFinder::type('any')->prune('*.svn')->in('/nonexistantdirectory');
    }
    catch (pakeException $e)
    {
      $this->assertWantedPattern('/does not exist/', $e->getMessage());
    }
  }

  public function test_clone()
  {
    $finder = pakeFinder::type('any')->name('file*')->prune('.svn');
    $finder1 = clone $finder;
    $this->assertEquals($finder->in($this->dir), $finder1->in($this->dir));

    $finder->size('>1K');
    $this->assertNotEquals($finder->in($this->dir), $finder1->in($this->dir));

    $finder1->size('>1K');
    $this->assertEquals($finder->in($this->dir), $finder1->in($this->dir));
  }

  public function test_exec()
  {
    function mytest($dir, $entry)
    {
      return true;
    }

    $files = pakeFinder::type('any')->exec('mytest')->prune('.svn')->in($this->dir);
    $files1 = pakeFinder::type('any')->prune('.svn')->in($this->dir);
    $this->assertEquals($files, $files1);

    function mytest2($dir, $entry)
    {
      if (preg_match('/\.php$/', $entry)) return true;
    }

    $files = pakeFinder::type('any')->exec('mytest2')->prune('.svn')->in($this->dir);
    $files1 = pakeFinder::type('any')->name('*.php')->prune('.svn')->in($this->dir);
    $this->assertEquals($files, $files1);

    $files = pakeFinder::type('any')->exec(array($this, 'mytest3'))->prune('.svn')->in($this->dir);
    $files1 = pakeFinder::type('any')->prune('.svn')->in($this->dir);
    $this->assertEquals($files, $files1);
  }

  public function mytest3($dir, $entry)
  {
    return true;
  }

    public function test_pattern()
    {
        $files = pakeFinder::type('file')->pattern('dir1/*/*')->in($this->dir);
        $this->assertEquals(4, count($files));

        $files = pakeFinder::type('dir')->pattern('dir1/*/*')->in($this->dir);
        $this->assertEquals(2, count($files));

        $files = pakeFinder::type('any')->pattern('dir1/**/file41')->in($this->dir);
        $this->assertEquals(1, count($files));

        $files = pakeFinder::type('any')->pattern('dir1/**/dir3/')->in($this->dir);
        $this->assertEquals(1, count($files));

        $files = pakeFinder::type('any')->pattern('dir1/**/*')->in($this->dir);
        $this->assertEquals(8, count($files));

        $files = pakeFinder::type('any')->pattern('dir1/**/*')->not_pattern('dir1/*/dir?')->in($this->dir);
        $this->assertEquals(6, count($files));
    }

}

?>
