<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeSimpletestTask
{
    public static function import_default_tasks()
    {
        pake_desc('launch project test suite');
        pake_task('pakeSimpletestTask::test');
    }

    public static function call_simpletest(pakeTask $task, $type = 'text', $dirs = array())
    {
        if (!class_exists('TestSuite')) {
            throw new pakeException('You must install SimpleTest to use this task.');
        }

        SimpleTest::ignore('UnitTestCase');

        $base_test_dir = 'test';
        $test_dirs = array();

        // run tests only in these subdirectories
        if ($dirs) {
            foreach ($dirs as $dir) {
                $test_dirs[] = $base_test_dir . DIRECTORY_SEPARATOR . $dir;
            }
        } else {
            $test_dirs[] = $base_test_dir;
        }

        $files = pakeFinder::type('file')->name('*Test.php')->in($test_dirs);

        if (count($files) == 0) {
            throw new pakeException('No test to run.');
        }

        $test = new TestSuite('Test suite in (' . implode(', ', $test_dirs) . ')');
        foreach ($files as $file) {
            $test->addFile($file);
        }

        ob_start();
        if ($type == 'html') {
            $result = $test->run(new HtmlReporter());
        } else if ($type == 'xml') {
            $result = $test->run(new XmlReporter());
        } else {
            $result = $test->run(new TextReporter());
        }
        $content = ob_get_contents();
        ob_end_clean();

        if ($task->is_verbose()) {
            echo $content;
        }
    }

    /**
     * Run the testsuite
     *
     * One of 'text', 'html', 'xml' can be given as first argument to select output type. Default is 'text'.
     * Further arguments specify directories which have test-files. By-default, "test" is used as directory.
     */
    public static function run_test(pakeTask $task, $args)
    {
        $types = array('text', 'html', 'xml');
        $type = 'text';
        if (array_key_exists(0, $args) && in_array($args[0], $types)) {
            $type = array_shift($args);
        }

        $dirs = array();
        if (is_array($args) && array_key_exists(0, $args)) {
            $dirs[] = $args[0];
        }

        self::call_simpletest($task, $type, $dirs);
    }
}
