<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2012 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

/**
 * Simple PHPDoc parser
 *
 * @package pake
 * @author Alexey Zakhlestin
 */
class pakePHPDoc
{
    /**
     * Returns short and log description of function
     *
     * @param string $function_name
     * @return array
     * @author Alexey Zakhlestin
     */
    public static function getDescriptions($function_name)
    {
        if (is_string($function_name))
            $reflection = new ReflectionFunction($function_name);
        elseif (is_array($function_name))
            $reflection = new ReflectionMethod($function_name[0], $function_name[1]);
        else
            throw new LogicException();

        $comment = $reflection->getDocComment();

        $lines = explode("\n", $comment);
        $obj = new self($lines);

        return array(trim($obj->short_desc), trim($obj->long_desc));
    }


    private $lines;
    private $looking_for_short = true;
    private $short_lines_counter = 0;

    public $short_desc = '';
    public $long_desc = '';

    private function __construct($lines)
    {
        $this->lines = $lines;

        $this->filterDocblockLines();
        $this->parse();

    }

    private function filterDocblockLines()
    {
        $lines = array();

        foreach ($this->lines as $line) {
            if (false === $line = self::filterDocblockLine($line))
                continue;

            $lines[] = $line;
        }

        $this->lines = $lines;
    }

    private function parse()
    {
        foreach ($this->lines as $line) {
            if (substr($line, 0, 1) == '@')
                break; // tags started. stopping parsing

            if ($this->looking_for_short) {
                if (false === $this->parseShortString($line))
                    break;
            } else {
                $this->long_desc .= $this->parseLongDesc($line);
            }
        }
    }

    private static function filterDocblockLine($line)
    {
        $line = trim($line);

        if ($line == '/**' or $line == '*/')
            return false; // first or last line

        if (substr($line, 0, 1) != '*')
            return false; // not docblock, ignore.

        return trim(substr($line, 1));
    }

    private function parseShortString($line)
    {
        // Short description is not over, yet
        if (strlen($line) == 0) {
            $this->looking_for_short = false;
            return true;
        }

        if (++$this->short_lines_counter > 3) {
            // overriding, too long for the short description
            $this->short_lines_counter = 0;
            $this->short_desc = '';
            $this->long_desc = '';

            // restarting
            $this->parseShortString($this->lines[0]);
            $this->looking_for_short = false;
            array_shift($this->lines);
            $this->parse();
            return false;
        }

        $success = preg_match('/(.*\.)(\W.*)/', $line, $matches);
        if ($success !== false and $success > 0) {
            // found something
            $this->short_desc .= trim($matches[1]);
            $this->looking_for_short = false;
            $this->long_desc .= trim($matches[2]).' ';
            return true;
        }

        $this->short_desc .= $line.' ';
        return true;
    }

    private function parseLongDesc($line)
    {
        if (strlen($line) == 0)
            return "\n";

        return $line.' ';
    }
}
