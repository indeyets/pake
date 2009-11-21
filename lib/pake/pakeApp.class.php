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
 * main pake class.
 *
 * This class is a singleton.
 *
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */
class pakeApp
{
    const VERSION = '1.1.DEV';

    public static $MAX_LINE_SIZE = 65;
    private static $PROPERTIES = array();
    protected static $PAKEFILES = array('pakefile', 'Pakefile', 'pakefile.php', 'Pakefile.php');
    protected static $PLUGINDIRS = array();
    protected static $OPTIONS = array(
        array('--dry-run',  '-n', pakeGetopt::NO_ARGUMENT,       "Do a dry run without executing actions."),
        array('--help',     '-H', pakeGetopt::NO_ARGUMENT,       "Display this help message."),
        array('--libdir',   '-I', pakeGetopt::REQUIRED_ARGUMENT, "Include LIBDIR in the search path for required modules."),
        array('--nosearch', '-N', pakeGetopt::NO_ARGUMENT,       "Do not search parent directories for the pakefile."),
        array('--prereqs',  '-P', pakeGetopt::NO_ARGUMENT,       "Display the tasks and dependencies, then exit."),
        array('--quiet',    '-q', pakeGetopt::NO_ARGUMENT,       "Do not log messages to standard output."),
        array('--pakefile', '-f', pakeGetopt::REQUIRED_ARGUMENT, "Use FILE as the pakefile."),
        array('--require',  '-r', pakeGetopt::REQUIRED_ARGUMENT, "Require MODULE before executing pakefile."),
        array('--tasks',    '-T', pakeGetopt::NO_ARGUMENT,       "Display the tasks and dependencies, then exit."),
        array('--trace',    '-t', pakeGetopt::NO_ARGUMENT,       "Turn on invoke/execute tracing, enable full backtrace."),
        array('--usage',    '-h', pakeGetopt::NO_ARGUMENT,       "Display usage."),
        array('--verbose',  '-v', pakeGetopt::NO_ARGUMENT,       "Log message to standard output (default)."),
        array('--version',  '-V', pakeGetopt::NO_ARGUMENT,       "Display the program version."),
    );

    private $opt = null;
    private $nosearch = false;
    private $trace = false;
    private $verbose = true;
    private $dryrun = false;
    private $nowrite = false;
    private $show_tasks = false;
    private $show_prereqs = false;
    private $pakefile = '';
    protected static $instance = null;

    protected function __construct()
    {
        self::$PLUGINDIRS[] = dirname(__FILE__).'/tasks';
    }

    public static function get_plugin_dirs()
    {
        return self::$PLUGINDIRS;
    }

    public function get_properties()
    {
        return self::$PROPERTIES;
    }

    public function set_properties($properties)
    {
        self::$PROPERTIES = $properties;
    }

    public static function get_instance()
    {
        if (!self::$instance)
            self::$instance = new pakeApp();

        return self::$instance;
    }

    public function get_verbose()
    {
        return $this->verbose;
    }

    public function get_trace()
    {
        return $this->trace;
    }

    public function get_dryrun()
    {
        return $this->dryrun;
    }

    public function run($pakefile = null, $options = null, $load_pakefile = true)
    {
        if ($pakefile) {
            self::$PAKEFILES = array($pakefile);
        }

        $this->handle_options($options);
        if ($load_pakefile) {
            $this->load_pakefile();
        }

        if ($this->show_tasks) {
            $this->display_tasks_and_comments();
            return;
        }

        if ($this->show_prereqs) {
            $this->display_prerequisites();
            return;
        }

        // parsing out options and arguments
        $args = $this->opt->get_arguments();
        $task_name = array_shift($args);

        $options = array();
        for ($i = 0, $max = count($args); $i < $max; $i++) {
            if (0 === strpos($args[$i], '--')) {
                if (false !== $pos = strpos($args[$i], '=')) {
                    $key = substr($args[$i], 2, $pos - 2);
                    $value = substr($args[$i], $pos + 1);
                } else {
                    $key = substr($args[$i], 2);
                    $value = true;
                }

                if ('[]' == substr($key, -2)) {
                    if (!isset($options[$key])) {
                        $options[$key] = array();
                    }

                    $options[$key][] = $value;
                } else {
                    $options[$key] = $value;
                }

                unset($args[$i]);
            }
        }
        $args = array_values($args);

        // generating abbreviations
        $abbreviated_tasks = pakeTask::get_abbreviated_tasknames();
        $task_name = pakeTask::get_full_task_name($task_name);
        if (!$task_name) {
            $task_name = 'default';
        }

        // does requested task correspond to full or abbreviated name?
        if (!array_key_exists($task_name, $abbreviated_tasks)) {
            throw new pakeException('Task "'.$task_name.'" is not defined.');
        }

        if (count($abbreviated_tasks[$task_name]) > 1) {
            throw new pakeException('Task "'.$task_name.'" is ambiguous ('.implode(', ', $abbreviated_tasks[$task_name]).').');
        }

        // init and run task
        $task = pakeTask::get($abbreviated_tasks[$task_name][0]);
        return $task->invoke($args, $options);
    }

    // Read and handle the command line options.
    public function handle_options($options = null)
    {
        $this->opt = new pakeGetopt(self::$OPTIONS);
        $this->opt->parse($options);
        foreach ($this->opt->get_options() as $opt => $value) {
            $this->do_option($opt, $value);
        }
    }

    // True if one of the files in RAKEFILES is in the current directory.
    // If a match is found, it is copied into @pakefile.
    public function have_pakefile()
    {
        $here = getcwd();
        foreach (self::$PAKEFILES as $file) {
            if (file_exists($here.'/'.$file)) {
                $this->pakefile = $here.'/'.$file;
                return true;
            }
        }

        return false;
    }

    public function load_pakefile()
    {
        $here = getcwd();
        while (!$this->have_pakefile()) {
            chdir('..');
            if (getcwd() == $here || $this->nosearch) {
                throw new pakeException(sprintf('No pakefile found (looking for: %s)', join(', ', self::$PAKEFILES))."\n");
            }

            $here = getcwd();
        }

        require_once($this->pakefile);
    }

    // Do the option defined by +opt+ and +value+.
    public function do_option($opt, $value)
    {
        switch ($opt) {
            case 'dry-run':
                $this->verbose = true;
                $this->nowrite = true;
                $this->dryrun = true;
                $this->trace = true;
                break;
            case 'help':
                $this->help();
                exit();
            case 'libdir':
                set_include_path($value.PATH_SEPARATOR.get_include_path());
                break;
            case 'nosearch':
                $this->nosearch = true;
                break;
            case 'prereqs':
                $this->show_prereqs = true;
                break;
            case 'quiet':
                $this->verbose = false;
                break;
            case 'pakefile':
                self::$PAKEFILES = array($value);
                break;
            case 'require':
                require $value;
                break;
            case 'tasks':
                $this->show_tasks = true;
                break;
            case 'trace':
                $this->trace = true;
                $this->verbose = true;
                break;
            case 'usage':
                $this->usage();
                exit();
            case 'verbose':
                $this->verbose = true;
                break;
            case 'version':
                echo sprintf('pake version %s', pakeColor::colorize(self::VERSION, 'INFO'))."\n";
                exit();
            default:
                throw new pakeException(sprintf("Unknown option: %s", $opt));
        }
    }

    // Display the program usage line.
    public function usage()
    {
        echo "pake [-f pakefile] {options} targets...\n".pakeColor::colorize("Try pake -H for more information", 'INFO')."\n";
    }

    // Display the rake command line help.
    public function help()
    {
        $this->usage();
        echo "\n";
        echo "available options:";
        echo "\n";

        foreach (self::$OPTIONS as $option) {
            list($long, $short, $mode, $comment) = $option;
            if ($mode == pakeGetopt::REQUIRED_ARGUMENT) {
                if (preg_match('/\b([A-Z]{2,})\b/', $comment, $match))
                    $long .= '='.$match[1];
            }
            printf("  %-20s (%s)\n", pakeColor::colorize($long, 'INFO'), pakeColor::colorize($short, 'INFO'));
            printf("      %s\n", $comment);
        }
    }

    // Display the tasks and dependencies.
    public function display_tasks_and_comments()
    {
        $width = 0;
        $tasks = pakeTask::get_tasks();
        foreach ($tasks as $name => $task) {
            $w = strlen(pakeTask::get_mini_task_name($name));
            if ($w > $width)
                $width = $w;
        }
        $width += strlen(pakeColor::colorize(' ', 'INFO'));

        echo "available pake tasks:\n";

        // display tasks
        $has_alias = false;
        ksort($tasks);
        foreach ($tasks as $name => $task) {
            if ($task->get_alias()) {
                $has_alias = true;
            }

            if (!$task->get_alias() and $task->get_comment()) {
                $mini_name = pakeTask::get_mini_task_name($name);
                printf('  %-'.$width.'s > %s'."\n", pakeColor::colorize($mini_name, 'INFO'), $task->get_comment().($mini_name != $name ? ' ['.$name.']' : ''));
            }
        }

        if ($has_alias) {
            print("\ntask aliases:\n");

            // display aliases
            foreach ($tasks as $name => $task) {
                if ($task->get_alias()) {
                    $mini_name = pakeTask::get_mini_task_name($name);
                    printf('  %-'.$width.'s = pake %s'."\n", pakeColor::colorize(pakeTask::get_mini_task_name($name), 'INFO'), $task->get_alias().($mini_name != $name ? ' ['.$name.']' : ''));
                }
            }
        }
    }

    // Display the tasks and prerequisites
    public function display_prerequisites()
    {
        foreach (pakeTask::get_tasks() as $name => $task) {
            echo "pake ".pakeTask::get_mini_task_name($name)."\n";
            foreach ($task->get_prerequisites() as $prerequisite) {
                echo "    $prerequisite\n";
            }
        }
    }
}
