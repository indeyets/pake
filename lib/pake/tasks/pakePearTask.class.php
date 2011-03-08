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

    // tasks
    public static function run_pear_package($task, $args)
    {
        $path = dirname(pakeApp::get_instance()->getPakefilePath());
        self::package_pear_package($path.'/package.xml', $path);
    }

    // API
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
                $results = pake_sh(escapeshellarg(pake_which('pear')).' package '.escapeshellarg($package_xml_path));
                if (pakeApp::get_instance()->get_verbose()) {
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

    public static function install_pear_package($package, $channel = 'pear.php.net', $upgrade_if_installed = true)
    {
        self::initPearClasses();

        $cfg = PEAR_Config::singleton();
        $need_sudo = (!is_writable($cfg->get('download_dir')) or !is_writable($cfg->get('php_dir')));

        $registry = $cfg->getRegistry();
        if ($registry->_channelExists($channel, true)) {
            if ($upgrade_if_installed === true) {
                // 1. update channel-info
                if ($need_sudo) {
                    pake_superuser_sh('pear channel-update '.escapeshellarg($channel));
                } else {
                    self::nativePearChannelUpdate($channel);
                }
            }
        } else {
            // 2. or discover channel
            if ($need_sudo) {
                pake_superuser_sh('pear channel-discover '.escapeshellarg($channel));
            } else {
                self::nativePearDiscover($channel);
            }
        }

        if (self::isInstalled($package, $channel)) {
            if ($upgrade_if_installed === true) {
                // 3. upgrade package
                if ($need_sudo) {
                    pake_superuser_sh('pear upgrade '.escapeshellarg($channel.'/'.$package), true);
                } else {
                    self::nativePearUpgrade($package, $channel);
                }
            }
        } else {
            // 4. or install it
            if ($need_sudo) {
                pake_superuser_sh('pear install '.escapeshellarg($channel.'/'.$package), true);
            } else {
                self::nativePearInstall($package, $channel);
            }
        }
    }

    public static function install_from_file($file, $package_name, $channel = '__uri')
    {
        if (self::isInstalled($package_name, $channel)) {
            return true;
        }

        // otherwise, let's install it!
        $pear = escapeshellarg(pake_which('pear'));
        pake_superuser_sh($pear.' install '.escapeshellarg($file));
    }

    public static function isInstalled($package, $channel)
    {
        self::initPearClasses();

        $cfg = PEAR_Config::singleton();
        $registry = $cfg->getRegistry();

        return $registry->_packageExists($package, $channel);
    }

    // helpers
    private static function nativePearDiscover($channel)
    {
        pake_echo_action('pear', 'discovering channel '.$channel);

        self::initPearClasses();

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

    private static function nativePearChannelUpdate($channel)
    {
        pake_echo_action('pear', 'updating channel info '.$channel);

        self::initPearClasses();

        $front = PEAR_Frontend::singleton('PEAR_Frontend_CLI');

        $cfg = PEAR_Config::singleton();
        $cmd = PEAR_Command::factory('channel-update', $cfg);

        ob_start();
        $result = $cmd->doUpdate('channel-update', array(), array($channel));
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
        pake_echo_action('pear', 'installing '.$channel.'/'.$package);

        self::initPearClasses();

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

    private static function nativePearUpgrade($package, $channel)
    {
        pake_echo_action('pear', 'upgrading '.$channel.'/'.$package);

        self::initPearClasses();

        $front = PEAR_Frontend::singleton('PEAR_Frontend_CLI');

        $cfg = PEAR_Config::singleton();
        $cmd = PEAR_Command::factory('upgrade', $cfg);

        ob_start();
        $result = $cmd->doInstall('upgrade', array(), array($channel.'/'.$package));
        ob_end_clean(); // we don't need output
        if ($result instanceof PEAR_Error) {
            throw new pakeException($result->getMessage());
        }
    }


    private static function initPearClasses()
    {
        if (!class_exists('PEAR_Config')) {
            @include 'PEAR/Registry.php'; // loads config, among other things
            if (!class_exists('PEAR_Config')) {
                throw new pakeException('PEAR subsystem is unavailable (not in include_path?)');
            }
        }

        if (!class_exists('PEAR_Command')) {
            @include 'PEAR/Command.php'; // loads frontend, among other things
            if (!class_exists('PEAR_Command')) {
                throw new pakeException('PEAR subsystem is unavailable (not in include_path?)');
            }
        }
    }
}
