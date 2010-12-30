<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */

/**
 *
 * Numeric comparisons.
 *
 * pakeNumberCompare compiles a simple comparison to an anonymous
 * subroutine, which you can call with a value to be tested again.
 *
 * Now this would be very pointless, if pakeNumberCompare didn't understand
 * magnitudes.
 *
 * The target value may use magnitudes of kilobytes (C<k>, C<ki>),
 * megabytes (C<m>, C<mi>), or gigabytes (C<g>, C<gi>).  Those suffixed
 * with an C<i> use the appropriate 2**n version in accordance with the
 * IEC standard: http://physics.nist.gov/cuu/Units/binary.html
 *
 * based on perl Number::Compare module.
 *
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2009 Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 * @see        http://physics.nist.gov/cuu/Units/binary.html
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */
class pakeNumberCompare
{
  private static $magnitudes = null;
  private $test = '';

  public function __construct($test)
  {
    if (null === self::$magnitudes) {
      // initializing magnitutes-table
      self::$magnitudes = array(
         'k' =>           1000,
         'ki'=>           1024,
         'm' =>      1000*1000,
         'mi'=>      1024*1024,
         'g' => 1000*1000*1000,
         'gi'=> 1024*1024*1024,
      );
    }

    $this->test = $test;
  }

  public function test($number)
  {
    if (!preg_match('{^([<>]=?)?(.*?)([kmg]i?)?$}i', $this->test, $matches))
    {
      throw new pakeException('Don\'t understand "'.$this->test.'" as a test.');
    }

    $target = array_key_exists(2, $matches) ? $matches[2] : '';
    $magnitude = array_key_exists(3, $matches) ? $matches[3] : '';

    if ('' !== $magnitude) {
      $target *= self::$magnitudes[strtolower($magnitude)];
    }

    $comparison = array_key_exists(1, $matches) ? $matches[1] : '==';

    switch ($comparison)
    {
      case '==':
      case '':
        return ($number == $target);

      case '>':
        return ($number > $target);

      case '>=':
        return ($number >= $target);

      case '<':
        return ($number < $target);

      case '<=':
        return ($number <= $target);
    }

    return false;
  }
}

/*
== USAGE ==
 $cmp = new pakeNumberCompare(">1Ki");
 // is 1025 > 1024 ?
 if ($cmp->test(1025)) {
   ...
 }
*/
