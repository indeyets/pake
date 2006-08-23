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

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME']))
{
  $pake = pakeApp::get_instance();
  try
  {
    $pake->run();
  }
  catch (pakeException $e)
  {
    echo "ERROR: pake - ".$e->getMessage()."\n";
  }
}
