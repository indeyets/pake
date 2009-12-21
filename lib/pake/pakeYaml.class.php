<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */

class pakeYaml
{
    public static function load($input)
    {
        if (!file_exists($input))
            throw new pakeException('file not found: "'.$input.'"');

        $parser = new sfYamlParser();
        return $parser->parse(file_get_contents($input));
    }

    public static function dump($array)
    {
        $dumper = new sfYamlDumper();
        return $dumper->dump($array);
    }
}
