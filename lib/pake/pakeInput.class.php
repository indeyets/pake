<?php

class pakeInput
{
    public static function getString($prompt = '> ', $retry_on_ctrld = true)
    {
        while (true) {
            echo $prompt;

            $fp = fopen('php://stdin', 'r');
            $retval = fgets($fp);
            fclose($fp);

            // Ctrl-D
            if (false === $retval) {
                echo "\n";

                if ($retry_on_ctrld) {
                    continue;
                }

                return false;
            }

            return rtrim($retval, "\r\n");
        }
    }

    public static function getPassword($prompt = '>')
    {
        
    }
}
