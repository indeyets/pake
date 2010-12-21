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

    // helpers
    public static function isRepository($path)
    {
        return is_dir($path.'/.git');
    }

    private function git_run($command)
    {
        if (self::$needs_work_tree_workaround === true) {
            $cmd = '(cd '.escapeshellarg($this->repository_path).' && git '.$command.')';
        } else {
            $cmd = 'git';
            $cmd .= ' '.escapeshellarg('--git-dir='.$this->repository_path.'/.git');
            $cmd .= ' '.escapeshellarg('--work-tree='.$this->repository_path);
            $cmd .= ' '.$command;
        }

        try {
            pake_sh($cmd);
        } catch (pakeException $e) {
            if (strpos($e->getMessage(), 'cannot be used without a working tree') !== false) {
                pake_echo_error('Your version of git is buggy. Using workaround');
                self::$needs_work_tree_workaround = true;
                $this->git_run($command);
            }
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

        $cmd = 'git init -q';

        if (null !== $template_path) {
            $cmd .= ' '.escapeshellarg('--template='.$template_path);
        }

        $cmd .= ' '.escapeshellarg('--shared='.$shared);

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

        pake_sh('git clone -q '.escapeshellarg($src_url).' '.escapeshellarg($target_path));

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
