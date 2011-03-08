<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeSSH
{
    private $host;
    private $login;

    public function __construct($host, $login)
    {
        $this->host = $host;
        $this->login = $login;
    }

    public function copy_to_server($src, $remote_path)
    {
        if (is_string($src)) {
            $src = array($src);
        }

        array_walk($src, array('self', 'throwIfPathDoesNotExists'));

        pake_sh(escapeshellarg(pake_which('scp')).' -rC '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg($this->login.'@'.$this->host.':'.$remote_path)
        );
    }

    public function copy_from_server($src, $local_path)
    {
        if (is_string($src)) {
            $src = array($src);
        }

        pake_mkdirs($local_path);

        foreach ($src as &$remote_path) {
            $remote_path = $this->login.'@'.$this->host.':'.$remote_path;
        }

        pake_sh(escapeshellarg(pake_which('scp')).' -rC '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg($local_path)
        );
    }

    public function execute($command)
    {
        return pake_sh(escapeshellarg(pake_which('ssh')).' -C '.escapeshellarg($this->login.'@'.$this->host).' '.escapeshellarg($command));
    }

    // internal helpers follow
    private static function throwIfPathDoesNotExists($path)
    {
        if (!file_exists($path)) {
            throw new pakeException('source path "'.$path.'" does not exist');
        }
    }
}
