<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2012 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

error_log('Using pake/init.php file is deprecated. Please consider switching to Composer or using pake/autoload.php instead', E_USER_DEPRECATED);

require dirname(__FILE__).'/autoload.php';
require dirname(__FILE__).'/cli_init.php';
