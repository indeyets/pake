<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 */

/**
 *
 * Allow to build rules to find files and directories.
 *
 * All rules may be invoked several times, except for ->in() method.
 * Some rules are cumulative (->name() for example) whereas others are destructive
 * (most recent value is used, ->maxdepth() method for example).
 *
 * All methods return the current pakeFinder object to allow easy chaining:
 *
 * $files = pakeFinder::type('file')->name('*.php')->in(.);
 *
 * Interface loosely based on perl File::Find::Rule module.
 *
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */
class pakeFinder
{
    private $type = 'file';
    private $patterns = array();
    private $not_patterns = array();
    private $names = array();
    private $prunes = array();
    private $discards = array();
    private $execs = array();
    private $mindepth = 0;
    private $sizes = array();
    private $maxdepth = 1000000;
    private $relative = false;
    private $follow_link = false;
    private $search_dir = '';

    /**
     * Constructor shouldn't be called manually
     */
    protected function __construct()
    {
    }

    /**
     * Factory, which prepares new pakeFinder objects
     * Sets the type of elements to return.
     *
     * @param  string "directory" or "file" or "any" (for both file and directory)
     * @return pakeFinder
     */
    public static function type($name)
    {
        $finder = new pakeFinder();

        if (strtolower(substr($name, 0, 3)) == 'dir') {
            $finder->type = 'directory';
        } else {
            if (strtolower($name) == 'any') {
                $finder->type = 'any';
            } else {
                $finder->type = 'file';
            }
        }

        return $finder;
    }

    /**
     * Sets maximum directory depth.
     *
     * Finder will descend at most $level levels of directories below the starting point.
     *
     * @param  integer $level
     * @return pakeFinder
     */
    public function maxdepth($level)
    {
        $this->maxdepth = $level;

        return $this;
    }

    /**
     * Sets minimum directory depth.
     *
     * Finder will start applying tests at level $level.
     *
     * @param  integer $level
     * @return pakeFinder
     */
    public function mindepth($level)
    {
        $this->mindepth = $level;

        return $this;
    }

    public function get_type()
    {
        return $this->type;
    }

    /*
    * glob, patterns (must be //) or strings
    */
    private function to_regex($str)
    {
        if ($str[0] == '/' && $str[strlen($str) - 1] == '/') {
            return $str;
        } else {
            return pakeGlobToRegex::glob_to_regex($str);
        }
    }

    private function args_to_array($arg_list, $not = false)
    {
        $list = array();

        for ($i = 0; $i < count($arg_list); $i++) {
            if (is_array($arg_list[$i])) {
                foreach ($arg_list[$i] as $arg) {
                    $list[] = array($not, $this->to_regex($arg));
                }
            } else {
                $list[] = array($not, $this->to_regex($arg_list[$i]));
            }
        }

        return $list;
    }

    /**
     * converts ant-pattern to PCRE regex
     *
     * @param string $pattern 
     * @return string
     */
    private function pattern_to_regex($pattern)
    {
        if (substr($pattern, -1) == '/') {
            $pattern .= '**';
        }

        $regex = '|^';
        foreach (explode('/', $pattern) as $i => $piece) {
            if ($i > 0) {
                $regex .= preg_quote('/', '|');
            }

            if ('**' == $piece) {
                $regex .= '.*';
            } else {
                $regex .= str_replace(array('?', '*'), array('[^/]', '[^/]*'), $piece);
            }
        }
        $regex .= '$|';

        return $regex;
    }

    /**
     * Mimics ant pattern matching.
     *
     * @see http://ant.apache.org/manual/dirtasks.html#patterns
     * @return pakeFinder
     */
    public function pattern()
    {
        $patterns = func_get_args();

        foreach (func_get_args() as $pattern) {
            $this->patterns[] = $this->pattern_to_regex($pattern);
        }

        return $this;
    }

    /**
     * Mimics ant pattern matching. (negative match)
     *
     * @see http://ant.apache.org/manual/dirtasks.html#patterns
     * @return pakeFinder
     */
    public function not_pattern()
    {
        $patterns = func_get_args();

        foreach (func_get_args() as $pattern) {
            $this->not_patterns[] = $this->pattern_to_regex($pattern);
        }

        return $this;
    }

    /**
     * Adds rules that files must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->name('*.php')
     * $finder->name('/\.php$/') // same as above
     * $finder->name('test.php')
     *
     * @return pakeFinder
     */
    public function name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args));

        return $this;
    }

    /**
     * Adds rules that files must not match.
     *
     * @see    ->name()
     * @return pakeFinder
     */
    public function not_name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args, true));

        return $this;
    }

    /**
     * Adds tests for file sizes.
     *
     * $finder->size('> 10K');
     * $finder->size('<= 1Ki');
     * $finder->size(4);
     *
     * @return pakeFinder
     */
    public function size()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i++) {
            $this->sizes[] = new pakeNumberCompare($args[$i]);
        }

        return $this;
    }

    /**
     * Traverses no further.
     *
     * @return pakeFinder
     */
    public function prune()
    {
        $args = func_get_args();
        $this->prunes = array_merge($this->prunes, $this->args_to_array($args));

        return $this;
    }

    /**
     * Discards elements that matches.
     *
     * @return pakeFinder
     */
    public function discard()
    {
        $args = func_get_args();
        $this->discards = array_merge($this->discards, $this->args_to_array($args));

        return $this;
    }

    /**
     * Ignores version control directories.
     *
     * Currently supports subversion, CVS, DARCS, Gnu Arch, Monotone, Bazaar-NG, Git, Mercurial
     *
     * @return pakeFinder
     */
    public function ignore_version_control()
    {
        $ignores = array('.svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg');

        return $this->discard($ignores)->prune($ignores);
    }

    /**
     * Executes function or method for each element.
     *
     * Element match if functino or method returns true.
     *
     * $finder->exec('myfunction');
     * $finder->exec(array($object, 'mymethod'));
     *
     * @return pakeFinder
     */
    public function exec()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i++) {
            if (is_array($args[$i]) && !method_exists($args[$i][0], $args[$i][1])) {
                throw new pakeException('Method ' . $args[$i][1] . ' does not exist for object ' . $args[$i][0]);
            } else {
                if (!is_array($args[$i]) && !function_exists($args[$i])) {
                    throw new pakeException('Function ' . $args[$i] . ' does not exist.');
                }
            }

            $this->execs[] = $args[$i];
        }

        return $this;
    }

    /**
     * Returns relative paths for all files and directories.
     *
     * @return pakeFinder
     */
    public function relative()
    {
        $this->relative = true;

        return $this;
    }

    /**
     * Symlink following.
     *
     * @return object current sfFinder object
     */
    public function follow_link()
    {
        $this->follow_link = true;

        return $this;
    }

    /**
     * Searches files and directories which match defined rules.
     *
     * @return array list of files and directories
     */
    public function in()
    {
        $files = array();
        $here_dir = getcwd();
        $numargs = func_num_args();
        $arg_list = func_get_args();

        // first argument is an array?
        if ($numargs == 1 && is_array($arg_list[0])) {
            $arg_list = $arg_list[0];
            $numargs = count($arg_list);
        }

        $dirs = array();
        for ($i = 0; $i < $numargs; $i++) {
            if ($argDirs = glob($arg_list[$i])) {
                $dirs = array_merge($dirs, $argDirs);
            }
        }

        foreach ($dirs as $dir)
        {
            $real_dir = realpath($dir);

            // absolute path?
            if (!self::isPathAbsolute($real_dir)) {
                $dir = $here_dir . DIRECTORY_SEPARATOR . $real_dir;
            } else {
                $dir = $real_dir;
            }

            if (!is_dir($real_dir)) {
                continue;
            }

            $this->search_dir = $dir;

            if ($this->relative) {
                $files = array_merge($files, str_replace($dir . DIRECTORY_SEPARATOR, '', $this->search_in($dir)));
            } else {
                $files = array_merge($files, $this->search_in($dir));
            }
        }

        return array_unique($files);
    }

    private function search_in($dir, $depth = 0)
    {
        if ($depth > $this->maxdepth) {
            // we're too deep already
            return array();
        }

        if (is_link($dir) && !$this->follow_link) {
            // we're not allowed to follow links
            return array();
        }

        if (!is_dir($dir)) {
            // we can't search for files inside of file
            return array();
        }

        $files = array();

        // iterating over directory contents
        $current_dir = opendir($dir);
        while (false !== $entryname = readdir($current_dir)) {
            if ($entryname == '.' || $entryname == '..') {
                continue;
            }

            $current_entry = $dir.DIRECTORY_SEPARATOR.$entryname;

            if (is_link($current_entry) && !$this->follow_link) {
                // we're not allowed to follow links
                continue;
            }

            if (is_dir($current_entry)) {
                if (($this->type == 'directory' || $this->type == 'any')
                        && ($depth >= $this->mindepth)
                        && !$this->is_discarded($dir, $entryname)
                        && $this->matches_patterns($dir, $entryname)
                        && $this->matches_names($dir, $entryname)
                        && $this->exec_ok($dir, $entryname)
                ) {
                    $files[] = realpath($current_entry);
                }

                // if dir-name is not "pruned", dive deeper
                if (!$this->is_pruned($dir, $entryname)) {
                    $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                }
            } else {
                if (($this->type == 'file' || $this->type == 'any')
                        && ($depth >= $this->mindepth)
                        && !$this->is_discarded($dir, $entryname)
                        && $this->matches_patterns($dir, $entryname)
                        && $this->matches_names($dir, $entryname)
                        && $this->size_is_ok($dir, $entryname)
                        && $this->exec_ok($dir, $entryname)
                ) {
                    $files[] = realpath($current_entry);
                }
            }
        }
        closedir($current_dir);

        return $files;
    }

    private function matches_patterns($dir, $entry)
    {
        // patterns always use posix-style paths
        $full_name = str_replace(DIRECTORY_SEPARATOR, '/', $dir.'/'.$entry);

        // patterns are always "relative"
        $full_name = substr($full_name, strlen($this->search_dir) + 1);

        // match negative patterns
        foreach ($this->not_patterns as $regex) {
            if (preg_match($regex, $full_name) == 1) {
                return false;
            }
        }

        // match positive patterns
        foreach ($this->patterns as $regex) {
            if (preg_match($regex, $full_name) == 1) {
                return true;
            }
        }

        if (count($this->patterns) > 0) {
            return false;
        } else {
            return true;
        }
    }

    private function matches_names($dir, $entry)
    {
        if (!count($this->names)) {
            return true;
        }

        $one_not_name_rule = false;
        $one_name_rule = false;

        // at first, we filter out those which match ->not_name()
        foreach ($this->names as $args) {
            list($not, $regex) = $args;
            if ($not) {
                $one_not_name_rule = true;
                if (preg_match($regex, $entry)) {
                    return false;
                }
            }
        }

        // then, we choose those which match ->name()
        foreach ($this->names as $args) {
            list($not, $regex) = $args;
            if (!$not) {
                $one_name_rule = true;
                if (preg_match($regex, $entry)) {
                    return true;
                }
            }
        }

        // finally, we decide what to do with the rest
        if ($one_name_rule) {
            // there is at least one ->name() rule which didn't match
            return false;
        } else {
            return true;
        }
    }

    private function size_is_ok($dir, $entry)
    {
        if (!count($this->sizes)) {
            return true;
        }

        if (!is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
            return true;
        }

        $filesize = filesize($dir . DIRECTORY_SEPARATOR . $entry);
        foreach ($this->sizes as $number_compare) {
            /** @var $number_compare pakeNumberCompare */
            if (!$number_compare->test($filesize)) {
                return false;
            }
        }

        return true;
    }

    private function is_pruned($dir, $entry)
    {
        if (!count($this->prunes)) {
            return false;
        }

        foreach ($this->prunes as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    private function is_discarded($dir, $entry)
    {
        if (!count($this->discards)) {
            return false;
        }

        foreach ($this->discards as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }

        return false;
    }

    private function exec_ok($dir, $entry)
    {
        if (!count($this->execs)) {
            return true;
        }

        foreach ($this->execs as $exec) {
            if (!call_user_func_array($exec, array($dir, $entry))) {
                return false;
            }
        }

        return true;
    }

    public static function isPathAbsolute($path)
    {
        if ($path{0} == '/' || $path{0} == '\\' ||
            (strlen($path) > 3 && ctype_alpha($path{0}) &&
             $path{1} == ':' &&
             ($path{2} == '\\' || $path{2} == '/')
            )
        ) {
            return true;
        }

        return false;
    }


    public static function get_files_from_argument($arg, $target_dir = '', $relative = false)
    {
        $files = array();

        if (is_array($arg)) {
            if (strlen($target_dir) > 0) {
                foreach ($arg as $path) {
                    $files[] = $target_dir.'/'.$path;
                }
            } else {
                $files = $arg;
            }
        } elseif (is_string($arg)) {
            if (strlen($target_dir) > 0) {
                $files[] = $target_dir.'/'.$arg;
            } else {
                $files[] = $arg;
            }
        } elseif ($arg instanceof pakeFinder) {
            /** @var $arg pakeFinder */
            $files = $arg->in($target_dir);
        } else {
            throw new pakeException('Wrong argument type (must be a list, a string or a pakeFinder object).');
        }

        if ($relative and $target_dir) {
            $files = preg_replace('/^' . preg_quote(realpath($target_dir), '/') . '/', '', $files);

            // remove leading /
            $files = array_map(create_function('$f', 'return 0 === strpos($f, DIRECTORY_SEPARATOR) ? substr($f, 1) : $f;'), $files);
        }

        return $files;
    }
}
