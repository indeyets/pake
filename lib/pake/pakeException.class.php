<?php

/*
 * This file is part of the pake package.
 * (c) 2004, 2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) 2010 Alexey Zakhlestin <indeyets@gmail.com>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * pakeException is the base class for all pake related exceptions and
 * provides an additional method for printing up a detailed view of an
 * exception.
 *
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class pakeException extends Exception
{
    public static function strlen($string)
    {
        return function_exists('mb_strlen') ? mb_strlen($string) : strlen($string);
    }

    public static function render($e)
    {
        $isatty = pakeApp::isTTY();

        $title = '  ['.get_class($e).']  ';
        $len = self::strlen($title);

        $lines = array();

        foreach (explode("\n", $e->getMessage()) as $line) {
            if ($isatty) {
                $pieces = explode("\n", wordwrap($line, pakeApp::screenWidth() - 4, "\n", true));
            } else {
                $pieces = array($line);
            }

            foreach ($pieces as $piece) {
                $lines[] = '  '.$piece.'  ';
                $len = max(self::strlen($piece) + 4, $len);
            }
        }

        if ($isatty) {
            $messages = array(
                str_repeat(' ', $len),
                $title.str_repeat(' ', $len - self::strlen($title)),
            );
        } else {
            $messages = array('', $title);
        }

        foreach ($lines as $line) {
            if ($isatty) {
                $messages[] = $line.str_repeat(' ', $len - self::strlen($line));
            } else {
                $messages[] = $line;
            }
        }

        if ($isatty) {
            $messages[] = str_repeat(' ', $len);
        } else {
            $messages[] = '';
        }

        fwrite(STDERR, "\n");
        foreach ($messages as $message) {
            fwrite(STDERR, pakeColor::colorize($message, 'ERROR', STDERR)."\n");
        }
        fwrite(STDERR, "\n");

        $pake = pakeApp::get_instance();

        if ($pake->get_trace()) {
            fwrite(STDERR, "exception trace:\n");

            $trace = self::trace($e);
            for ($i = 0, $count = count($trace); $i < $count; $i++) {
                $class = (isset($trace[$i]['class']) ? $trace[$i]['class'] : '');
                $type = (isset($trace[$i]['type']) ? $trace[$i]['type'] : '');
                $function = $trace[$i]['function'];
                $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                fwrite(STDERR, pake_sprintf(" %s%s%s at %s:%s\n", $class, $type, $function, pakeColor::colorize($file, 'INFO', STDERR), pakeColor::colorize($line, 'INFO', STDERR)));
            }
        }

        fwrite(STDERR, "\n");
    }

    public static function trace($exception)
    {
        // exception related properties
        $trace = $exception->getTrace();
        array_unshift($trace, array(
            'function' => '',
            'file'     => ($exception->getFile() != null) ? $exception->getFile() : 'n/a',
            'line'     => ($exception->getLine() != null) ? $exception->getLine() : 'n/a',
            'args'     => array(),
        ));

        return $trace;
    }
}
