<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeRSync
{
    public static function mirror_dir($src_path, $target_path)
    {
        // trailing slash is required to copy contents of dir, not the dir itself
        if ($src_path[strlen($src_path)-1] != '/')
            $src_path .= '/';

        self::throwIfPathDoesNotExists($src_path);

        pake_mkdirs($target_path);

        pake_sh('rsync -az '.escapeshellarg($src_path).' '.escapeshellarg($target_path));
    }

    /**
     * put one or several local-paths onto server.
     * if $src is string it it treated as a source-dir of objects. if $src is array, it is treated as a list of objects
     *
     * @param mixed $src 
     * @param string $server_host 
     * @param string $remote_path 
     * @param string $rsync_login (note: this is rsync-login, not transport login)
     * @param string $transport (use 'ssh -l username' if specific ssh-login is required)
     * @return void
     * @author Jimi Dini
     */
    public static function sync_to_server($src, $server_host, $remote_path, $rsync_login = '', $transport = 'ssh')
    {
        if (strlen($rsync_login) > 0) {
            $rsync_login .= '@';
        }

        if (is_string($src)) {
            // sync contents of dir, so adding trailing slash
            if ($src[strlen($src)-1] != '/')
                $src .= '/';

            $src = array($src);
        } elseif (is_array($src)) {
            // syncing multiple objects, so removing trailing slashes
            $src = array_map(create_function('$path', 'return rtrim($path, "/");'), $src);
        }

        array_walk($src, array('self', 'throwIfPathDoesNotExists'));

        pake_sh('rsync -az -e '.escapeshellarg($transport).' '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg("{$rsync_login}{$server_host}:{$remote_path}")
        );
    }

    public static function sync_from_server($local_path, $server_host, $remote_paths, $rsync_login = '', $transport = 'ssh')
    {
        if (strlen($rsync_login) > 0) {
            $rsync_login .= '@';
        }

        pake_mkdirs($local_path);

        if (is_string($remote_paths)) {
            // sync contents of dir, so adding trailing slash
            if ($remote_paths[strlen($remote_paths)-1] != '/')
                $remote_paths .= '/';

            $remote_paths = array($remote_paths);
        } elseif (is_array($remote_paths)) {
            // syncing multiple objects, so removing trailing slashes
            $remote_paths = array_map(create_function('$path', 'return rtrim($path, "/");'), $remote_paths);
        }

        foreach ($remote_paths as &$remote_path) {
            $remote_path = $rsync_login.$server_host.':'.$remote_path;
        }

        pake_sh('rsync -az -e '.escapeshellarg($transport).' '
                .implode(' ', array_map('escapeshellarg', $remote_paths)).' '
                .escapeshellarg($local_path)
        );
    }


    // internal helpers follow
    private static function throwIfPathDoesNotExists($path)
    {
        if (!file_exists($path)) {
            throw new pakeException('source path "'.$path.'" does not exist');
        }
    }
}