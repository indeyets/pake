<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009–2012 Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 */

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
