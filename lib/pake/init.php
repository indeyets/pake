<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
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

// register our default exception handler
function pake_exception_default_handler($exception)
{
  pakeException::render($exception);
  exit(1);
}
set_exception_handler('pake_exception_default_handler');

mb_internal_encoding('utf-8');

// fix php behavior if using cgi php
// from http://www.sitepoint.com/article/php-command-line-1/3
if (false !== strpos(PHP_SAPI, 'cgi'))
{
   // handle output buffering
   @ob_end_flush();
   ob_implicit_flush(true);

   // PHP ini settings
   set_time_limit(0);
   ini_set('track_errors', true);
   ini_set('html_errors', false);
   ini_set('magic_quotes_runtime', false);

   // define stream constants
   define('STDIN', fopen('php://stdin', 'r'));
   define('STDOUT', fopen('php://stdout', 'w'));
   define('STDERR', fopen('php://stderr', 'w'));

   // change directory
   if (isset($_SERVER['PWD']))
   {
     chdir($_SERVER['PWD']);
   }

   // close the streams on script termination
   register_shutdown_function(create_function('', 'fclose(STDIN); fclose(STDOUT); fclose(STDERR); return true;'));
}

// enabling pake's helper-functions
require PAKE_DIR.'/pakeFunction.php';
