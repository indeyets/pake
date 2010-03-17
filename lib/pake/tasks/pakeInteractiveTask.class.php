<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2010 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeInteractiveTask
{
    public static function import_default_tasks()
    {
        pake_desc('Display help on available commands');
        pake_task('pakeInteractiveTask::help');

        pake_desc('Quit interactive mode');
        pake_task('pakeInteractiveTask::quit');
    }

    public static function run_help()
    {
        pakeApp::get_instance()->display_tasks_and_comments();
    }

    public static function run_quit()
    {
        echo "Bye!\n";
        exit(pakeApp::QUIT_INTERACTIVE);
    }
}
