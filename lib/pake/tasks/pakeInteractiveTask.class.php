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
        pake_task('pakeInteractiveTask::help_pake');

        pake_alias('?', 'pakeInteractiveTask::help_pake');

        pake_desc('Quit interactive mode');
        pake_task('pakeInteractiveTask::quit_pake');
    }

    public static function run_help_pake()
    {
        pakeApp::get_instance()->display_tasks_and_comments();
    }

    public static function run_quit_pake()
    {
        echo "Bye!\n";
        exit(pakeApp::QUIT_INTERACTIVE);
    }
}
