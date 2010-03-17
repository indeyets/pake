<?php

class pakeInput
{
    public static function getString($prompt = '> ')
    {
        while (true) {
            echo $prompt;

            $fp = fopen('php://stdin', 'r');
            $retval = fgets($fp);
            fclose($fp);

            // Ctrl-D = retry
            if (false === $retval) {
                echo "\n";
                continue;
            }

            return rtrim($retval, "\r\n");
        }
    }

    public static function getPassword($prompt = '>')
    {
        
    }
}
