<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@php.net>
 * @copyright  2011 Alexey Zakhlestin <indeyets@php.net>
 * @license    see the LICENSE file included in the distribution
 */

require 'PEAR2/Autoload.php';

class pakePear2Task
{
    public static function import_default_tasks()
    {
        pake_desc('create a PEAR package');
        pake_task(__CLASS__.'::pear_package');
    }

    // Tasks

    // API
    public static function package_pear_package($package_xml_path, $target_dir)
    {
    }

    public static function install_pear_package($package, $channel = 'pear2.php.net', $upgrade_if_installed = true)
    {
        self::discover_channel($channel);

        $cfg = PEAR2\Pyrus\Config::current();
        $registry = $cfg->registry;

        $full_name = $channel.'/'.$package;
        $package = new PEAR2\Pyrus\Package($full_name);

        if (isset($registry->package[$full_name])) {
            if (true === $upgrade_if_installed and $package->isUpgradeable()) {
                $registry->replace($package);
            }
        } else {
            // install
            PEAR2\Pyrus\Installer::begin();
            PEAR2\Pyrus\Installer::prepare($package);
            PEAR2\Pyrus\Installer::commit();
            // $registry->package[] = $package;
        }
    }


    public static function local_pear($path, $callback)
    {
        $old_path = PEAR2\Pyrus\Config::current()->my_pear_path;

        $config = PEAR2\Pyrus\Config::singleton($path, true);
        PEAR2\Pyrus\Config::setCurrent($path);

        $retval = call_user_func($callback);

        PEAR2\Pyrus\Config::setCurrent($old_path);

        return $retval;
    }


    // helpers
    protected static function discover_channel($channel_name)
    {
        $cfg = PEAR2\Pyrus\Config::current();
        $registry = $cfg->channelregistry;

        if (isset($registry[$channel_name]))
            return; // already registered

        $channel_file = new PEAR2\Pyrus\ChannelFile('http://'.$channel_name.'/channel.xml', false ,true);
        $registry[] = $channel_file;
    }
}
