<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeGit
{
    public static function isRepository($path)
    {
        return is_dir($path.'/.git');
    }

    public static function clone_repository($src_url, $target_path)
    {
        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Git repository already');
        }

        if (file_exists($target_path)) {
            throw new pakeException('"'.$target_path.'" directory already exists. Can not clone git-repository there');
        }

        pake_sh('git clone '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
    }

    public static function pull($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Git repository');
        }

        pake_sh('git '.escapeshellarg('--git-dir='.$repository_path.'/.git').' pull');
    }
}
