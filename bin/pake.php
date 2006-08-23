<?php

// set classpath
if (getenv('PHP_CLASSPATH'))
{
  set_include_path(dirname(__FILE__).'/../lib'.PATH_SEPARATOR.getenv('PHP_CLASSPATH').PATH_SEPARATOR.get_include_path());
}
else
{
  set_include_path(dirname(__FILE__).'/../lib'.PATH_SEPARATOR.get_include_path());
}

include_once('pake/pakeFunction.php');

// register our default exception handler
function pake_exception_default_handler($exception)
{
  $e = new pakeException();
  $e->render($exception);
}
set_exception_handler('pake_exception_default_handler');

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME']))
{
  $pake = pakeApp::get_instance();
  $pake->run();
}
