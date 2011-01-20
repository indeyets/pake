<?php

class pakeMySQL
{
    private $mode = null;
    private $db = null;
    private $more = null;

    public function __construct($login, $password, $host = 'localhost', $port = 3306)
    {
        if (extension_loaded('pdo_mysql')) {
            $this->mode = 'pdo';

            $this->db = new PDO('mysql:host='.$host.';port='.$port, $login, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif (extension_loaded('mysqli')) {
            $this->mode = 'mysqli';
            $this->db = new mysqli($host, $login, $password, '', $port);

            if ($this->db->connect_error) {
                throw new pakeException('MySQLi Connect Error ('.$this->db->connect_errno.') '.$this->db->connect_error);
            }
        } elseif (extension_loaded('mysql')) {
            $this->mode = 'mysql';
            $this->db = mysql_connect($host.':'.$port, $login, $password, true);

            if (false === $this->db) {
                throw new pakeException('MySQL Connect Error ('.mysql_errno().') '.mysql_error());
            }
        } else {
            $this->mode = 'cli';

            $this->db = pake_which('mysqladmin'); // will throw pakeException if not found
            $this->more = array('login' => $login, 'password' => $password, 'host' => $host, 'port' => $port);
        }
    }

    public function __destruct()
    {
        if ($this->mode == 'mysql') {
            mysql_close($this->db);
        }
    }

    public function createDatabase($name)
    {
        if ($this->mode == 'cli') {
            pake_sh($this->cliCommandPrefix().' create '.escapeshellarg($name));
        } else {
            $sql = 'CREATE DATABASE '.$name.' DEFAULT CHARACTER SET = utf8 DEFAULT COLLATE = utf8_unicode_ci';
            $this->sqlExec($sql);
        }
    }

    public function dropDatabase($name)
    {
        if ($this->mode == 'cli') {
            pake_sh($this->cliCommandPrefix().' drop '.escapeshellarg($name));
        } else {
            $sql = 'DROP DATABASE IF EXISTS '.$name;
            $this->sqlExec($sql);
        }
    }


    private function cliCommandPrefix()
    {
        return escapeshellarg($this->db)
                .' --force'
                .' '.escapeshellarg('--host='.$this->more['host'])
                .' '.escapeshellarg('--port='.$this->more['port'])
                .' '.escapeshellarg('--user='.$this->more['login'])
                .' '.escapeshellarg('--password='.$this->more['password']);
    }

    private function sqlExec($sql)
    {
        if ($this->mode == 'pdo') {
            $this->db->exec($sql);
            pake_echo_action('pdo_mysql', $sql);
        } elseif ($this->mode == 'mysqli') {
            $result = $this->db->real_query($sql);

            if (false === $result) {
                throw new pakeException('MySQLi Error ('.$this->db->errno.') '.$this->db->error);
            }
            pake_echo_action('mysqli', $sql);
        } elseif ($this->mode == 'mysql') {
            $result = mysql_query($sql, $this->db);

            if (false === $result) {
                throw new pakeException('MySQL Error ('.mysql_errno().') '.mysql_error());
            }
            pake_echo_action('mysql', $sql);
        }
    }
}
