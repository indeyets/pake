<?php

class pakePearTask
{
  public static function import_default_tasks()
  {
    pake_desc('create a PEAR package');
    pake_task('pakePearTask::pear');
  }

  public static function run_pear($task, $args)
  {
    $results = pake_sh('pear package');
    if ($task->is_verbose())
    {
      echo $results;
    }
  }
}
