<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeSubversion
{
    public static function isRepository($path)
    {
        return is_dir($path.'/.svn');
    }

    public static function checkout($src_url, $target_path)
    {
        pake_mkdirs($target_path);

        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Subversion repository already');
        }

        if (count(pakeFinder::type('any')->in($target_path)) > 0) {
            throw new pakeException('"'.$target_path.'" directory is not empty. Can not checkout there');
        }

        pake_echo_action('svn checkout', $target_path);
        if (extension_loaded('svn')) {
            $result = svn_checkout($src_url, $target_path);

            if (false === $result) {
                throw new pakeException('Couldn\'t checkout "'.$src_url.'" repository');
            }
        } else {
            pake_sh('svn checkout '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        }
    }

    public static function update($path)
    {
        if (!self::isRepository($path)) {
            throw new pakeException('"'.$path.'" directory is not a Subversion repository');
        }

        pake_echo_action('svn update', $path);
        if (extension_loaded('svn')) {
            $result = svn_update($path);

            if (false === $result) {
                throw new pakeException('Couldn\'t update "'.$path.'" repository');
            }
        } else {
            pake_sh('svn update '.escapeshellarg($path));
        }
    }

    public static function export($src_url, $target_path)
    {
        if (count(pakeFinder::type('any')->in($target_path)) > 0) {
            throw new pakeException('"'.$target_path.'" directory is not empty. Can not export there');
        }

        pake_echo_action('svn export', $target_path);
        if (extension_loaded('svn')) {
            $result = svn_export($src_url, $target_path, false);

            if (false === $result) {
                throw new pakeException('Couldn\'t export "'.$src_url.'" repository');
            }
        } else {
            pake_sh('svn export '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        }
    }
}
