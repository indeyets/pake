<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeArchive
{
    public static function createArchive($arg, $origin_dir, $archive_file, $overwrite = false)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');

        if (false === $overwrite and file_exists($archive_file))
            return true;

        if (self::endsWith($archive_file, '.tar.gz')) {
            $archive_file = substr($archive_file, 0, -3);
            $compress = Phar::GZ;
            $extension = '.tar.gz';

            if (!extension_loaded('zlib')) {
                throw new pakeException('GZip compression method is not available on this system (install "zlib" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tgz')) {
            $archive_file = substr($archive_file, 0, -3).'tar';
            $compress = Phar::GZ;
            $extension = '.tgz';

            if (!extension_loaded('zlib')) {
                throw new pakeException('GZip compression method is not available on this system (install "zlib" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tar.bz2')) {
            $archive_file = substr($archive_file, 0, -4);
            $compress = Phar::BZ2;
            $extension = '.tar.bz2';

            if (!extension_loaded('bz2')) {
                throw new pakeException('BZip2 compression method is not available on this system (install "bzip2" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tar') or self::endsWith($archive_file, '.zip')) {
            $compress = Phar::NONE;
        } else {
            throw new pakeException("Only .zip, .tar, .tar.gz and .tar.bz2 archives are supported");
        }

        $files = pakeFinder::get_files_from_argument($arg, $origin_dir, true);

        pake_echo_action('file+', $archive_file);
        try {
            $arc = new PharData($archive_file);
            foreach ($files as $file) {
                $full_path = $origin_dir.'/'.$file;

                pake_echo_action('archive', '-> '.$file);
                if (is_dir($full_path))
                    $arc->addEmptyDir($file);
                else
                    $arc->addFile($full_path, $file);
            }

            if (Phar::NONE !== $compress) {
                $new_name = substr($archive_file, 0, -4).$extension;
                pake_echo_action('file+', $new_name);
                $arc->compress($compress, $extension);
                unset($arc);
                pake_remove($archive_file, '/');
            }
        } catch (PharException $e) {
            unset($arc);
            pake_remove($archive_file);
            throw $e;
        }
    }

    public static function createPharArchive($arg, $origin_dir, $archive_file, $stub = null, $web_stub = null, $overwrite = false)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');

        if (false === $overwrite and file_exists($archive_file))
            return true;

        if (!self::endsWith($archive_file, '.phar')) {
            throw new pakeException("Archive must have .phar extension");
        }

        $files = pakeFinder::get_files_from_argument($arg, $origin_dir, true);
        pake_echo_action('file+', $archive_file);
        try {
            $arc = new Phar($archive_file);
            foreach ($files as $file) {
                $full_path = $origin_dir.'/'.$file;

                pake_echo_action('phar', '-> '.$file);
                if (is_dir($full_path))
                    $arc->addEmptyDir($file);
                else
                    $arc->addFile($full_path, $file);
            }

            if (null !== $stub) {
                pake_echo_action('phar', '[stub] '.$stub.(null === $web_stub ? '' : ', '.$web_stub));
                $arc->setStub($arc->createDefaultStub($stub, $web_stub));
            }
        } catch (PharException $e) {
            unset($arc);
            pake_remove($archive_file);
            throw $e;
        }
    }

    public static function extractArchive($archive_file, $target_dir, $overwrite = false, $files = null)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');

        pake_mkdirs($target_dir);

        pake_echo_action('extract', $archive_file);
        $arc = new PharData($archive_file);
        $arc->extractTo($target_dir, $files, $overwrite);
    }

    private static function endsWith($string, $suffix)
    {
        $pos = strlen($string) - strlen($suffix);
        return (strpos($string, $suffix, $pos) == $pos);
    }
}
