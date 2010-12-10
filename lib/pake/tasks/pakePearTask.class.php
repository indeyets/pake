<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */

class pakePearTask
{
    public static function import_default_tasks()
    {
        pake_desc('create a PEAR package');
        pake_task('pakePearTask::pear_package');
    }

    public static function run_pear_package($task, $args)
    {
        $path = dirname(pakeApp::get_instance()->getPakefilePath());
        self::package_pear_package($path.'/package.xml', $path);
    }

    public static function package_pear_package($package_xml_path, $target_dir)
    {
        if (!file_exists($package_xml_path)) {
            throw new pakeException('"'.$package_xml_path.'" file does not exist');
        }

        pake_mkdirs($target_dir);

        $current = getcwd();
        chdir($target_dir);

        if (!class_exists('PEAR_Packager')) {
            @include('PEAR/Packager.php');

            if (!class_exists('PEAR_Packager')) {
                // falling back to cli-call
                $results = pake_sh('pear package '.escapeshellarg($package_xml_path));
                if ($task->is_verbose()) {
                    echo $results;
                }

                chdir($current);
                return;
            }
        }

        $packager = new PEAR_Packager();
        $packager->debug = 0; // silence output
        $archive = $packager->package($package_xml_path, true);

        pake_echo_action('file+', $target_dir.'/'.$archive);

        chdir($current);
    }

    public static function install_pear_package($package, $channel = 'pear.php.net')
    {
        if (!class_exists('PEAR_Config')) {
            @include 'PEAR/Registry.php'; // loads config, among other things
            if (!class_exists('PEAR_Config')) {
                throw new pakeException('PEAR subsystem is unavailable (not in include_path?)');
            }
        }

        $cfg = PEAR_Config::singleton();
        $registry = $cfg->getRegistry();

        // 1. check if package is installed
        if ($registry->_packageExists($package, $channel)) {
            return true;
        }

        $need_sudo = (!is_writable($cfg->get('download_dir')) or !is_writable($cfg->get('php_dir')));

        // 2. if not installed, discover channel
        if (!$registry->_channelExists($channel, true)) {
            // sudo discover channel
            pake_echo_action('pear', 'discovering channel '.$channel);
            if ($need_sudo) {
                pake_superuser_sh('pear channel-discover '.escapeshellarg($channel));
            } else {
                self::nativePearDiscover($channel);
            }
        }

        // 3. install package
        pake_echo_action('pear', 'installing '.$channel.'/'.$package);
        if ($need_sudo) {
            pake_superuser_sh('pear install '.escapeshellarg($channel.'/'.$package), true);
        } else {
            self::nativePearInstall($package, $channel);
        }
    }


    // helpers
    private static function nativePearDiscover($channel)
    {
        if (!class_exists('PEAR_Command')) {
            @include 'PEAR/command.php'; // loads frontend, among other things
            if (!class_exists('PEAR_Command')) {
                throw new pakeException('PEAR subsystem is unavailable (not in include_path?)');
            }
        }

        $front = PEAR_Frontend::singleton('PEAR_Frontend_CLI');

        $cfg = PEAR_Config::singleton();
        $cmd = PEAR_Command::factory('channel-discover', $cfg);

        ob_start();
        $result = $cmd->doDiscover('channel-discover', array(), array($channel));
        ob_end_clean(); // we don't need output
        if ($result instanceof PEAR_Error) {
            $msg = $result->getMessage();
            $pos = strpos($msg, ' (');
            if (false !== $pos) {
                $msg = substr($msg, 0, $pos);
            }
            throw new pakeException($msg);
        }
    }

    private static function nativePearInstall($package, $channel)
    {
        if (!class_exists('PEAR_Command')) {
            @include 'PEAR/command.php'; // loads frontend, among other things
            if (!class_exists('PEAR_Command')) {
                throw new pakeException('PEAR subsystem is unavailable (not in include_path?)');
            }
        }

        $front = PEAR_Frontend::singleton('PEAR_Frontend_CLI');

        $cfg = PEAR_Config::singleton();
        $cmd = PEAR_Command::factory('install', $cfg);

        ob_start();
        $result = $cmd->doInstall('install', array(), array($channel.'/'.$package));
        ob_end_clean(); // we don't need output
        if ($result instanceof PEAR_Error) {
            throw new pakeException($result->getMessage());
        }
    }
}
