<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeGit
{
    private $repository_path;

    public function __construct($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Git repository');
        }

        $this->repository_path = $repository_path;
    }

    public function add($files = null)
    {
        if (null === $files) {
            $files = array('--all');
        } else {
            $files = pakeApp::get_files_from_argument($files, $this->repository_path, true);
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

    public function pull()
    {
        $this->git_run('pull -q');

        return $this;
    }

    // helpers
    public static function isRepository($path)
    {
        return is_dir($path.'/.git');
    }

    private function git_run($command)
    {
        pake_sh('git '.escapeshellarg('--git-dir='.$this->repository_path.'/.git').' '.$command);
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

    public static function clone_repository($src_url, $target_path)
    {
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
