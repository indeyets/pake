<?php

class pakeFunctionTest extends UnitTestCase
{
    protected $test_dir;

    public function setUp()
    {
        pakeApp::get_instance()->do_option('quiet', null);

        $dir = dirname(__FILE__);
        $this->test_dir = $dir.DIRECTORY_SEPARATOR.'temporary-files';

        if (!is_dir($this->test_dir)) {
            mkdir($this->test_dir);
        }

        $this->_cleanup();
    }

    function tearDown()
    {
        $this->_cleanup();
        rmdir($this->test_dir);
    }

    private function _cleanup()
    {
        foreach (scandir($this->test_dir) as $entry) {
            $full_path = $this->test_dir.DIRECTORY_SEPARATOR.$entry;

            if (is_file($full_path)) {
                unlink($full_path);
            }
        }
    }

    public function test_pake_replace_tokens()
    {
        $test_file_name = 'file1.tpl';
        file_put_contents($this->test_dir.DIRECTORY_SEPARATOR.$test_file_name, '{token} {token2}');

        pake_replace_tokens($test_file_name, $this->test_dir, '{', '}', array('token' => 'hello', 'token2' => 'world'));

        $test_file = $this->test_dir.DIRECTORY_SEPARATOR.$test_file_name;
        $replaced = file_get_contents($test_file);
        $this->assertEqual('hello world', $replaced);
    }

    public function test_pake_replace_tokens_finder()
    {
        $test_file_names = array('file1.tpl', 'file2.tpl', 'file3.tpl');
        foreach ($test_file_names as $test_file_name) {
            file_put_contents($this->test_dir.DIRECTORY_SEPARATOR.$test_file_name, '{token} {token2}');
        }

        $files = pakeFinder::type('file')->relative()->in($this->test_dir);
        pake_replace_tokens($files, $this->test_dir, '{', '}', array('token' => 'hello', 'token2' => 'world'));

        foreach ($test_file_names as $test_file_name) {
            $test_file = $this->test_dir.DIRECTORY_SEPARATOR.$test_file_name;
            $replaced = file_get_contents($test_file);
            $this->assertEqual('hello world', $replaced);
        }
    }
}
