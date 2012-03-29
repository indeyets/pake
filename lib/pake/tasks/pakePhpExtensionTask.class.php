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
        'clean' =>          array('Clean all temporary files', array('pakePhpExtensionTask::_clean_build', 'pakePhpExtensionTask::_clean_config')),
        'configure' =>      array(null, array()),
        'build' =>          array(null, array('pakePhpExtensionTask::configure')),
        'install' =>        array('configure, build and install extension. (options: --with-phpize, --with-php-config)', array('pakePhpExtensionTask::build')),
        'reconfigure' =>    array('reconfigure. needed if you change config.m4 file (options: --with-phpize, --with-php-config)', array('pakePhpExtensionTask::_clean_build', 'pakePhpExtensionTask::configure')),
        'test' =>           array('run tests', array('pakePhpExtensionTask::build')),
        '_clean_build' =>   array(null, array()),
        '_clean_config' =>  array(null, array()),
    );

    public static function import_default_tasks()
    {
        foreach (self::$tasks as $taskname => $taskdata) {
            if ($taskdata[0] !== null)
                pake_desc($taskdata[0]);

            call_user_func_array('pake_task', array_merge(array(__CLASS__.'::'.$taskname), $taskdata[1]));
        }
    }

    public static function run_configure($task, $args, $long_args)
    {
        $dir = dirname(pakeApp::get_instance()->getPakefilePath());
        $cfg_file = $dir.'/'.__CLASS__.'.yaml';

        $need_to_write = true;

        if (isset($long_args['with-phpize'])) {
            $phpize = $long_args['with-phpize'];
        } elseif (file_exists($cfg_file)) {
            $cfg_data = pakeYaml::loadFile($cfg_file);
            $phpize = $cfg_data['phpize'];
            $need_to_write = false;
        } else {
            $phpize = pake_which('phpize');
        }

        if (!file_exists($phpize))
            throw new pakeException('"'.$phpize.'" is not available');

        if (isset($long_args['with-php-config'])) {
            $php_config = $long_args['with-php-config'];
            $need_to_write = true;
        } elseif (isset($cfg_data)) {
            $php_config = $cfg_data['php_config'];
        } else {
            $php_config = dirname($phpize).'/php-config';
            $need_to_write = true;
        }

        if (!file_exists($php_config))
            throw new pakeException('"'.$php_config.'" is not available');

        if (!file_exists('configure'))
            pake_sh(escapeshellarg($phpize));

        if (!file_exists('Makefile')) {
            pake_sh(escapeshellarg(realpath('configure')).' --with-php-config='.escapeshellarg($php_config));
        }

        if ($need_to_write) {
            pakeYaml::emitFile(array('phpize' => $phpize, 'php_config' => $php_config), $cfg_file);
        }
    }

    public static function run_reconfigure() {} // virtual task

    public static function run_build()
    {
        pake_sh(escapeshellarg(pake_which('make')), true);
    }

    public static function run_install()
    {
        pake_superuser_sh('make install');
    }

    public static function run_clean() {}
    public static function run__clean_build($task, $args, $long_args)
    {
        $dir = dirname(pakeApp::get_instance()->getPakefilePath());
        $cfg_file = $dir.'/'.__CLASS__.'.yaml';

        if (file_exists('Makefile'))
            pake_sh(escapeshellarg(pake_which('make')).' distclean');

        if (file_exists('configure')) {
            if (isset($long_args['with-phpize'])) {
                $phpize = $long_args['with-phpize'];
            } elseif (file_exists($cfg_file)) {
                $cfg_data = pakeYaml::loadFile($cfg_file);
                $phpize = $cfg_data['phpize'];
            } else {
                $phpize = pake_which('phpize');
            }

            if (!file_exists($phpize))
                throw new pakeException('"'.$phpize.'" is not available');

            pake_sh(escapeshellarg($phpize).' --clean');
        }
    }

    public static function run__clean_config()
    {
        $dir = dirname(pakeApp::get_instance()->getPakefilePath());
        $cfg_file = $dir.'/'.__CLASS__.'.yaml';

        if (file_exists($cfg_file)) {
            pake_remove($cfg_file, '');
        }
    }

    public static function run_test($task)
    {
        $php_cgi = '';

        $_php_cgi = self::_get_php_executable().'-cgi';
        if (file_exists($_php_cgi)) {
            $php_cgi = ' '.escapeshellarg('TEST_PHP_CGI_EXECUTABLE='.$_php_cgi);
        }

        pake_echo_comment('Running test-suite. This can take awhile…');
        pake_sh(escapeshellarg(pake_which('make')).' test NO_INTERACTION=1'.$php_cgi);
        pake_echo_comment('Done');

        $path = dirname(pakeApp::get_instance()->getPakefilePath()).'/tests';
        $files = pakeFinder::type('file')->ignore_version_control()->relative()->name('*.diff')->in($path);

        if (count($files) == 0) {
            pake_echo('   All tests PASSed!');
            return true;
        }

        pake_echo_error('Following tests FAILed:');
        foreach ($files as $file) {
            $phpt_file = substr($file, 0, -4).'phpt';

            $_lines = file($path.'/'.$phpt_file);
            $description = $_lines[1];
            unset($_lines);

            pake_echo('     '.$phpt_file.' ('.rtrim($description).')');
        }

        return false;
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
