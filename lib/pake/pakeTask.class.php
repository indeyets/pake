<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */
 
/**
 *
 * .
 *
 * .
 *
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */
class pakeTask
{
  protected static $TASKS = array();
  protected static $ALIAS = array();
  protected static $last_comment = '';
  protected $prerequisites = array();
  protected $name = '';
  protected $comment = '';
  protected $already_invoked = false;
  protected $trace = null;
  protected $verbose = null;
  protected $dryrun = null;
  protected $alias = '';

  public function __construct($task_name)
  {
    $this->name = $task_name;
    $this->comment = '';
    $this->prerequisites = array();
    $this->already_invoked = false;
    $pake = pakeApp::get_instance();
    $this->trace = $pake->get_trace();
    $this->dryrun = $pake->get_dryrun();
    $this->verbose = $pake->get_verbose();
  }

  public function is_verbose()
  {
    return $this->verbose;
  }

  public function enhance($deps = null)
  {
    if (!$deps) return;

    if (is_array($deps))
    {
      $this->prerequisites = array_merge($this->prerequisites, $deps);
    }
    else
    {
      $this->prerequisites[] = $deps;
    }
  }

  public static function get_tasks()
  {
    $tasks = pakeTask::$TASKS;
    // we merge tasks and aliases
    foreach (pakeTask::$ALIAS as $alias => $name)
    {
      if (!array_key_exists($name, $tasks))
      {
        throw new pakeException('Task "'.$name.'" cannot be cloned to "'.$alias.'" because it does not exist.');
      }

      $alias_task = clone $tasks[$name];
      $alias_task->alias = $name;
      $alias_task->name = $alias;
      $tasks[$alias] = $alias_task;
    }

    return $tasks;
  }

  public static function get_abbreviated_tasknames()
  {
      return self::abbrev(array_keys(self::get_tasks()));
  }

  public function get_property($name, $section = null)
  {
    $properties = pakeApp::get_instance()->get_properties();

    if ($section)
    {
      if (!array_key_exists($section, $properties) || !array_key_exists($name, $properties[$section]))
      {
        throw new pakeException('Property "'.$section.'/'.$name.'" does not exist.');
      }
      else
      {
        return $properties[$section][$name];
      }
    }
    else
    {
      if (!array_key_exists($name, $properties))
      {
        throw new pakeException('Property "'.$name.'" does not exist.');
      }
      else
      {
        return $properties[$name];
      }
    }
  }

  public function get_alias()
  {
    return $this->alias;
  }

  public function get_prerequisites()
  {
    return $this->prerequisites;
  }

  public function get_name()
  {
    return $this->name;
  }

  public function get_comment()
  {
    return $this->comment;
  }

  // Format the trace flags for display.
  private function format_trace_flags()
  {
    $flags = array();
    if (!$this->already_invoked)
    {
      $flags[] = 'first_time';
    }
    if (!$this->is_needed())
    {
      $flags[] = 'not_needed';
    }

    return (count($flags)) ? '('.join(', ', $flags).')' : '';
  }

  public function invoke($args, $options)
  {
    if ($this->trace)
    {
      pake_echo_action('invoke', $this->name.' '.$this->format_trace_flags());
    }

    // return if already invoked
    if ($this->already_invoked) return;
    $this->already_invoked = true;

    // run prerequisites
    $tasks = self::get_tasks();
    foreach ($this->prerequisites as $prerequisite)
    {
      $real_prerequisite = self::get_full_task_name($prerequisite);
      if (array_key_exists($real_prerequisite, $tasks))
      {
        $tasks[$real_prerequisite]->invoke($args, $options);
      }
      else
      {
        throw new pakeException('Prerequisite "'.$prerequisite.'" does not exist.');
      }
    }

    // only run if needed
    if ($this->is_needed())
    {
      return $this->execute($args, $options);
    }
  }

  public function execute($args, $options)
  {
    if ($this->dryrun)
    {
      pake_echo_action('execute', '(dry run) '.$this->name);
      return;
    }

    if ($this->trace)
    {
      pake_echo_action('execute', $this->name);
    }

    // action to run
    $function = ($this->get_alias() ? $this->get_alias() : $this->get_name());
    if ($pos = strpos($function, '::'))
    {
      $function = array(substr($function, 0, $pos), preg_replace('/\-/', '_', 'run_'.strtolower(substr($function, $pos + 2))));
      if (!is_callable($function))
      {
        throw new pakeException('Task "'.$function[1].'" is defined but with no action defined.');
      }
    }
    else
    {
      $function = preg_replace('/\-/', '_', 'run_'.strtolower($function));
      if (!function_exists($function))
      {
        throw new pakeException('Task "'.$this->name.'" is defined but with no action defined.');
      }
    }

    // execute action
    return call_user_func_array($function, array($this, $args, $options));
  }

  public function is_needed()
  {
    return true;
  }

  public function timestamp()
  {
    $max = 0;
    foreach ($this->prerequisites as $prerequisite)
    {
      $t = pakeTask::get($prerequisite)->timestamp();
      if ($t > $max) $max = $t;
    }

    return ($max ? $max : time());
  }

  public static function define_task($name, $deps = null)
  {
     $task = pakeTask::lookup($name, 'pakeTask');
     $task->add_comment();
     $task->enhance($deps);
  }

  public static function define_alias($alias, $name)
  {
    self::$ALIAS[$alias] = $name;
  }

  public static function lookup($task_name, $class = 'pakeTask')
  {
    $tasks = self::get_tasks();
    $task_name = self::get_full_task_name($task_name);
    if (!array_key_exists($task_name, $tasks))
    {
      pakeTask::$TASKS[$task_name] = new $class($task_name);
    }

    return pakeTask::$TASKS[$task_name];
  }

  public static function get($task_name)
  {
    $tasks = self::get_tasks();
    $task_name = self::get_full_task_name($task_name);
    if (!array_key_exists($task_name, $tasks))
    {
      throw new pakeException('Task "'.$task_name.'" is not defined.');
    }

    return $tasks[$task_name];
  }

  public static function get_full_task_name($task_name)
  {
    foreach (self::get_tasks() as $task)
    {
      $mini_task_name = self::get_mini_task_name($task->get_name());
      if ($mini_task_name == $task_name)
      {
        return $task->get_name();
      }
    }

    return $task_name;
  }

  /**
   * removes classname and colons, if those are present
   * abc => abc
   * abc::def => def
   *
   * @param string $task_name 
   * @return void
   * @author Jimi Dini
   */
  public static function get_mini_task_name($task_name)
  {
    $is_method_task = strpos($task_name, '::');
    return ((false !== $is_method_task) ? substr($task_name, $is_method_task + 2) : $task_name);
  }

  public static function define_comment($comment)
  {
    pakeTask::$last_comment = $comment;
  }

  public function add_comment()
  {
    if (!pakeTask::$last_comment) return;
    if ($this->comment)
    {
      $this->comment .= ' / ';
    }

    $this->comment .= pakeTask::$last_comment;
    pakeTask::$last_comment = '';
  }



  /**
   * gets array of words as input and returns array, where shortened words are keys and arrays of corresponding full-words are values.
   * For example:
   * input: array('abc', 'abd')
   * output: array('a' => array('abc', 'abd'), 'ab' => array('abc', 'abd'), 'abc' => array('abc'), 'abd' => array('abd'))
   *
   * @param array $options
   * @return array
   * @author Jimi Dini
   */
  public static function abbrev(array $options)
  {
      $abbrevs = array();
      $table = array();

      foreach ($options as $option) {
          $option = pakeTask::get_mini_task_name($option);

          for ($len = (strlen($option)) - 1; $len > 0; --$len) {
              $abbrev = substr($option, 0, $len);

              if (!array_key_exists($abbrev, $table))
                  $table[$abbrev] = 1;
              else
                  ++$table[$abbrev];

              $seen = $table[$abbrev];
              if ($seen == 1) {
                  // we're the first word so far to have this abbreviation.
                  $abbrevs[$abbrev] = array($option);
              } elseif ($seen == 2) {
                  // we're the second word to have this abbreviation, so we can't use it.
                  //unset($abbrevs[$abbrev]);
                  $abbrevs[$abbrev][] = $option;
              } else {
                  // we're the third word to have this abbreviation, so skip to the next word.
                  continue;
              }
          }
      }

      // Non-abbreviations always get entered, even if they aren't unique
      foreach ($options as $option) {
          $abbrevs[$option] = array($option);
      }

      return $abbrevs;
  }
}
