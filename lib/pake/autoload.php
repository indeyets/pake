<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2012 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

if (!defined('PAKE_DIR'))
    define('PAKE_DIR', dirname(__FILE__));

function pake_autoloader($classname)
{
    static $classes = null;

    if (null === $classes) {
        $classes = array(
            'pakeException'     => PAKE_DIR.'/pakeException.class.php',
            'pakeYaml'          => PAKE_DIR.'/pakeYaml.class.php',
            'pakeGetopt'        => PAKE_DIR.'/pakeGetopt.class.php',
            'pakeGlobToRegex'   => PAKE_DIR.'/pakeGlobToRegex.class.php',
            'pakeFinder'        => PAKE_DIR.'/pakeFinder.class.php',
            'pakeNumberCompare' => PAKE_DIR.'/pakeNumberCompare.class.php',
            'pakeTask'          => PAKE_DIR.'/pakeTask.class.php',
            'pakeFileTask'      => PAKE_DIR.'/pakeFileTask.class.php',
            'pakeColor'         => PAKE_DIR.'/pakeColor.class.php',
            'pakeApp'           => PAKE_DIR.'/pakeApp.class.php',
            'pakeSubversion'    => PAKE_DIR.'/pakeSubversion.class.php',
            'pakeGit'           => PAKE_DIR.'/pakeGit.class.php',
            'pakeRSync'         => PAKE_DIR.'/pakeRSync.class.php',
            'pakeSSH'           => PAKE_DIR.'/pakeSSH.class.php',
            'pakeArchive'       => PAKE_DIR.'/pakeArchive.class.php',
            'pakeInput'         => PAKE_DIR.'/pakeInput.class.php',
            'pakeMercurial'     => PAKE_DIR.'/pakeMercurial.class.php',
            'pakeHttp'          => PAKE_DIR.'/pakeHttp.class.php',
            'pakeMySQL'         => PAKE_DIR.'/pakeMySQL.class.php',
            'pakePHPDoc'        => PAKE_DIR.'/pakePHPDoc.class.php',

            'sfYaml'            => PAKE_DIR.'/sfYaml/sfYaml.php',
            'sfYamlDumper'      => PAKE_DIR.'/sfYaml/sfYamlDumper.php',
            'sfYamlInline'      => PAKE_DIR.'/sfYaml/sfYamlInline.php',
            'sfYamlParser'      => PAKE_DIR.'/sfYaml/sfYamlParser.php',
        );
    }

    if (isset($classes[$classname]))
        require $classes[$classname];
}
spl_autoload_register('pake_autoloader');

// enabling pake's helper-functions
require PAKE_DIR.'/pakeFunction.php';
