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
        $test_file_name = 'file.tpl';
        $test_file = $this->test_dir.DIRECTORY_SEPARATOR.$test_file_name;

        file_put_contents($test_file, '{token} {token2}');
        pake_replace_tokens($test_file_name, $this->test_dir, '{', '}', array('token' => 'hello', 'token2' => 'world'));

        $replaced = file_get_contents($test_file);

        $this->assertEqual('hello world', $replaced);
    }
}
