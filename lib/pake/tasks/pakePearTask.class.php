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
        if (!class_exists('PEAR_Packager')) {
            @include('PEAR/Packager.php');

            if (!class_exists('PEAR_Packager')) {
                // falling back to cli-call
                $results = pake_sh('pear package');
                if ($task->is_verbose())
                {
                    echo $results;
                }
                return;
            }
        }

        $packager = new PEAR_Packager();
        $packager->debug = 0; // silence output
        $archive = $packager->package('package.xml', true);

        pake_echo_action('pear+', $archive);
    }

    public static function install_pear_package($package)
    {
        pake_superuser_sh('pear install '.escapeshellarg($package), true);
    }
}
