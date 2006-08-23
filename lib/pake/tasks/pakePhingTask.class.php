<?php

class pakePhingTask
{
  public static function import_default_tasks()
  {
  }

  public static function call_phing($task, $target, $build_file = '', $options = array())
  {
    $args = array();
    foreach ($options as $key => $value)
    {
      $args[] = "-D$key=$value";
    }

    if ($build_file)
    {
      $args[] = '-f';
      $args[] = realpath($build_file);
    }

    if (!$task->is_verbose())
    {
      $args[] = '-q';
    }

    if (is_array($target))
    {
      $args = array_merge($args, $target);
    }
    else
    {
      $args[] = $target;
    }

    include_once 'phing/Phing.php';
    if (!class_exists('Phing'))
    {
      throw new pakeException('You must install Phing to use this task. (pear install http://phing.info/pear/phing-current.tgz)');
    }

    Phing::startup();
    Phing::setProperty('phing.home', getenv('PHING_HOME'));

    try
    {
      $m = new Phing();
      $m->execute($args);
      $m->runBuild();
    }
    catch (Exception $e)
    {
      throw new Exception($e->getMessage());
    }
  }
}
