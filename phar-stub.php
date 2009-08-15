<?php

Phar::interceptFileFuncs();

$phar_name = dirname(__FILE__);
define('PAKE_DIR', $phar_name.'/lib/pake');

require PAKE_DIR.'/init.php';

$pake = pakeApp::get_instance();
$pake->run();
