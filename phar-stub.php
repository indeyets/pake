<?php

Phar::interceptFileFuncs();

$phar_name = dirname(__FILE__);
define('PAKE_DIR', $phar_name.'/lib/pake');

require PAKE_DIR.'/autoload.php';
require PAKE_DIR.'/cli_init.php';

$retval = pakeApp::get_instance()->run();

if (false === $retval) {
    exit(1);
}
