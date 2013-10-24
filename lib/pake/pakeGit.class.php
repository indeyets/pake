<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeGit
{
    static $needs_work_tree_workaround = false;
    private $repository_path;

    public function __construct($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Git repository');
        }

        $this->repository_path = $repository_path;
    }

    public function getPath()
    {
        return $this->repository_path;
    }

    public function add($files = null)
    {
        if (null === $files) {
            $files = array('--all');
        } else {
            $files = pakeFinder::get_files_from_argument($files, $this->repository_path, true);
        }

        $this->git_run('add '.implode(' ', array_map('escapeshellarg', $files)));

        return $this;
    }

    public function commit($message = '', $all = false)
    {
        $this->git_run('commit -q -m '.escapeshellarg($message).($all ? ' -a' : ''));

        return $this;
    }

    public function checkout($branch)
    {
        $this->git_run('checkout -q -f '.escapeshellarg($branch));

        return $this;
    }

    public function pull($remote = null, $branch = null)
    {
        $cmd = 'pull -q';

        if (null !== $remote) {
            $cmd .= ' '.escapeshellarg($remote);

            if (null !== $branch) {
                $cmd .= ' '.escapeshellarg($branch);
            }
        }

        $this->git_run($cmd);

        return $this;
    }

    public function push($remote = null, $branch = null)
    {
        $cmd = 'push -q';

        if (null !== $remote) {
            $cmd .= ' '.escapeshellarg($remote);

            if (null !== $branch) {
                $cmd .= ' '.escapeshellarg($branch);
            }
        }

        $this->git_run($cmd);

        return $this;
    }

    public function logLast($number)
    {
        if (!is_numeric($number)) {
            throw new pakeException('pakeGit::logLast() takes number, as parameter');
        }

        return $this->log('-'.$number);
    }

    public function logSince($commit_hash, $till = 'HEAD')
    {
        return $this->log($commit_hash.'..'.$till);
    }

    public function log($suffix)
    {
        $cmd = 'log --format="%H%x00%an%x00%ae%x00%at%x00%s"'.' '.$suffix;
        $result = $this->git_run($cmd);

        $data = array();
        foreach (preg_split('/(\r\n|\n\r|\r|\n)/', $result) as $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }

            $pieces = explode(chr(0), $line);

            $data[] = array(
                'hash' => $pieces[0],
                'author' => array('name' => $pieces[1], 'email' => $pieces[2]),
                'time' => new DateTime('@'.$pieces[3]),
                'message' => $pieces[4]
            );
        }

        return $data;
    }

    public function remotes()
    {
        $result = $this->git_run('remote -v');

        $data = array();
        foreach (preg_split('/(\r\n|\n\r|\r|\n)/', $result) as $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }

            list($name, $tail) = explode("\t", $line, 2);

            if (strpos($tail, '(fetch)') == strlen($tail) - 7) {
                $data[$name]['fetch'] = substr($tail, 0, -7);
            } elseif (strpos($tail, '(push)') == strlen($tail) - 6) {
                $data[$name]['push'] = substr($tail, 0, -6);
            }
        }

        return $data;
    }

    // helpers
    public static function isRepository($path)
    {
        return is_dir($path.'/.git');
    }

    /**
     * Run git-command in context of repository
     *
     * This method is useful for implementing some custom command, not implemented by pake.
     * In cases when pake has native support for command, please use it, as it will provide better compatibility
     *
     * @param $command
     */
    public function git_run($command)
    {
        $git = escapeshellarg(pake_which('git'));

        if (self::$needs_work_tree_workaround === true) {
            $cmd = '(cd '.escapeshellarg($this->repository_path).' && '.$git.' '.$command.')';
        } else {
            $cmd = $git;
            $cmd .= ' --git-dir='.escapeshellarg($this->repository_path.'/.git');
            $cmd .= ' --work-tree='.escapeshellarg($this->repository_path);
            $cmd .= ' '.$command;
        }

        try {
            return pake_sh($cmd);
        } catch (pakeException $e) {
            if (strpos($e->getMessage(), 'cannot be used without a working tree') !== false ||
                // workaround for windows (using win7 and git 1.7.10)
                strpos($e->getMessage(), 'fatal: Could not switch to ') !== false) {
                pake_echo_error('Your version of git is buggy. Using workaround');
                self::$needs_work_tree_workaround = true;
                return $this->git_run($command);
            }

            throw $e;
        }
    }

    // new git-repo
    public static function init($path, $template_path = null, $shared = false)
    {
        pake_mkdirs($path);

        if (false === $shared)
            $shared = 'false';
        elseif (true === $shared)
            $shared = 'true';
        elseif (is_int($shared))
            $shared = sprintf("%o", $shared);

        $cmd = escapeshellarg(pake_which('git')).' init -q';

        if (null !== $template_path) {
            $cmd .= ' --template='.escapeshellarg($template_path);
        }

        $cmd .= ' --shared='.escapeshellarg($shared);

        $cwd = getcwd();
        chdir($path);
        chdir('.'); // hack for windows. see http://docs.php.net/manual/en/function.chdir.php#88617
        pake_sh($cmd);
        chdir($cwd);

        return new pakeGit($path);
    }

    public static function clone_repository($src_url, $target_path = null)
    {
        if (null === $target_path) {
            // trying to "guess" path
            $target_path = basename($src_url);

            // removing suffix
            if (substr($target_path, -4) === '.git')
                $target_path = substr($target_path, 0, -4);
        }

        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Git repository already');
        }

        if (file_exists($target_path)) {
            throw new pakeException('"'.$target_path.'" directory already exists. Can not clone git-repository there');
        }

        pake_sh(escapeshellarg(pake_which('git')).' clone -q '.escapeshellarg($src_url).' '.escapeshellarg($target_path));

        return new pakeGit($target_path);
    }

    // one-time operations
    public static function add_to_repo($repository_path, $files = null)
    {
        $repo = new pakeGit($repository_path);
        $repo->add($files);

        return $repo;
    }

    public static function commit_repo($repository_path, $message = '', $all = false)
    {
        $repo = new pakeGit($repository_path);
        $repo->commit($message, $all);

        return $repo;
    }

    public static function checkout_repo($repository_path, $branch)
    {
        $repo = new pakeGit($repository_path);
        $repo->checkout($branch);

        return $repo;
    }

    public static function pull_repo($repository_path)
    {
        $repo = new pakeGit($repository_path);
        $repo->pull();

        return $repo;
    }
}
