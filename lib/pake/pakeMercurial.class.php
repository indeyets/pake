<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2010 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeMercurial
{
    private $repository_path;

    public function __construct($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Mercurial repository');
        }

        $this->repository_path = $repository_path;
    }

    public function getPath()
    {
        return $this->repository_path;
    }

    public function pull()
    {
        $this->hg_run('pull -u');

        return $this;
    }

    public function add($files = null)
    {
        if (null === $files) {
            $files = array('--all');
        } else {
            $files = pakeFinder::get_files_from_argument($files, $this->repository_path, true);
        }

        $this->hg_run('add '.implode(' ', array_map('escapeshellarg', $files)));

        return $this;
    }

    public function commit($message = '')
    {
        $this->hg_run('commit -m '.escapeshellarg($message));

        return $this;
    }

    public function switch_branch($branch_name)
    {
        $this->hg_run('update -C '.escapeshellarg($branch_name));

        return $this;
    }

    // helpers
    public static function isRepository($path)
    {
        return is_dir($path.'/.hg');
    }

    private function hg_run($command)
    {
        $cmd = 'hg -q';
        $cmd .= ' --cwd '.escapeshellarg($this->repository_path);
        $cmd .= ' '.$command;

        pake_sh($cmd);
    }

    // new mercurial-repo
    public static function init($path)
    {
        pake_mkdirs($path);

        pake_sh('hg init -q '.escapeshellarg($path));

        return new pakeMercurial($path);
    }

    public static function clone_repository($src_url, $target_path = null)
    {
        if (null === $target_path) {
            // trying to "guess" path
            $target_path = basename($src_url);

            // removing suffix
            if (substr($target_path, -3) === '.hg')
                $target_path = substr($target_path, 0, -3);
        }

        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Mercurial repository already');
        }

        if (file_exists($target_path)) {
            throw new pakeException('"'.$target_path.'" directory already exists. Can not clone Mercurial-repository there');
        }

        pake_mkdirs($target_path);
        pake_sh('hg clone -q '.escapeshellarg($src_url).' '.escapeshellarg($target_path));

        return new pakeMercurial($target_path);
    }

    // one-time operations
    public static function add_to_repo($repository_path, $files = null)
    {
        $repo = new pakeMercurial($repository_path);
        $repo->add($files);

        return $repo;
    }

    public static function commit_repo($repository_path, $message = '')
    {
        $repo = new pakeMercurial($repository_path);
        $repo->commit($message);

        return $repo;
    }

    public static function pull_repo($repository_path)
    {
        $repo = new pakeMercurial($repository_path);
        $repo->pull();

        return $repo;
    }
}
