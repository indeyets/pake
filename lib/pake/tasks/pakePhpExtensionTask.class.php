<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2010 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakePhpExtensionTask
{
    public static $tasks = array(
        'clean' =>          array('Clean all temporary files', array()),
        'configure' =>      array(null, array()),
        'build' =>          array(null, array('pakePhpExtensionTask::configure')),
        'install' =>        array('configure, build and install extension', array('pakePhpExtensionTask::build')),
        'reconfigure' =>    array('reconfigure (needed if you change config.m4 file)', array('pakePhpExtensionTask::clean', 'pakePhpExtensionTask::configure')),
        'test' =>           array('run tests', array('pakePhpExtensionTask::build')),
    );

    public static function import_default_tasks()
    {
        foreach (self::$tasks as $taskname => $taskdata) {
            if ($taskdata[0] !== null)
                pake_desc($taskdata[0]);

            call_user_func_array('pake_task', array_merge(array(__CLASS__.'::'.$taskname), $taskdata[1]));
        }
    }

    public static function run_configure()
    {
        if (!file_exists('configure'))
            pake_sh('phpize');

        if (!file_exists('Makefile')) {
            pake_sh(realpath('configure'));
        }
    }

    public static function run_reconfigure() {} // virtual task

    public static function run_build()
    {
        pake_sh('make', true);
    }

    public static function run_install()
    {
        pake_superuser_sh('make install');
    }

    public static function run_clean()
    {
        if (file_exists('Makefile'))
            pake_sh('make distclean');

        if (file_exists('configure'))
            pake_sh('phpize --clean');
    }

    public static function run_test($task)
    {
        $php_cgi = '';

        $_php_cgi = self::_get_php_executable().'-cgi';
        if (file_exists($_php_cgi)) {
            $php_cgi = ' '.escapeshellarg('TEST_PHP_CGI_EXECUTABLE='.$_php_cgi);
        }

        pake_echo_comment('Running test-suite. This can take awhile…');
        pake_sh('make test NO_INTERACTION=1'.$php_cgi);
        pake_echo_comment('Done');

        $path = dirname(pakeApp::get_instance()->getPakefilePath()).'/tests';
        $files = pakeFinder::type('file')->ignore_version_control()->relative()->name('*.diff')->in($path);

        if (count($files) == 0) {
            pake_echo('   All tests PASSed!');
            return;
        }

        pake_echo_error('Following tests FAILed:');
        foreach ($files as $file) {
            $phpt_file = substr($file, 0, -4).'phpt';

            $_lines = file($path.'/'.$phpt_file);
            $description = $_lines[1];
            unset($_lines);

            pake_echo('     '.$phpt_file.' ('.rtrim($description).')');
        }
    }

    private static function _get_php_executable()
    {
        static $php_exec = null;

        if (null === $php_exec) {
            $root = dirname(pakeApp::get_instance()->getPakefilePath());

            if (!file_exists($root.'/Makefile')) {
                throw new LogicException("Makefile is missing. You have to build extension before testing it!");
            }

            $makefile_rows = file($root.'/Makefile');
            foreach ($makefile_rows as $row) {
                if (strpos($row, 'PHP_EXECUTABLE = ') !== 0)
                    continue;

                $row = rtrim($row);
                $parts = explode(' = ', $row);
                if (!isset($parts[1]) or strlen($parts[1]) == 0)
                    continue;

                $php_exec = $parts[1];
                break;
            }
            unset($makefile_rows);
        }

        if (!$php_exec) {
            // Fallback
            $php_exec = 'php';
        }

        return $php_exec;
    }
}
