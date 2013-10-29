<?php

// force usage of local pake
$_self_path = realpath($_SERVER['PHP_SELF']);
$_newstyle_local = dirname(__FILE__).DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pake';

if ($_self_path != $_newstyle_local) {
    $php_exec = (isset($_SERVER['_']) and substr($_SERVER['_'], -4) != 'pake') ? $_SERVER['_'] : 'php';
    $args = '';

    if ($_SERVER['argc'] > 1) {
        array_shift($_SERVER['argv']); // removing pake.php
        $args_arr = array_map('escapeshellarg', $_SERVER['argv']);
        $args = ' '.implode(' ', $args_arr);
    }

    $force_tty = '';
    if (defined('PAKE_FORCE_TTY') or (DIRECTORY_SEPARATOR != '\\' and function_exists('posix_isatty') and @posix_isatty(STDOUT))) {
        $force_tty = ' --force-tty';
    }

    pake_echo_comment("oops… you're using installed pake. restarting with local version…");
    pake_sh(escapeshellarg($php_exec).' '.escapeshellarg($_newstyle_local).$force_tty.$args, true);

    die();
} else {
    pake_echo_comment("using local version of pake. good!");
}

/* registration */
pake_import('simpletest');

pake_task('phar');
pake_task('foo');


/**
 * Demo-task
 *
 * @param string $task
 * @param string $args
 * @return bool
 * @author Alexey Zakhlestin
 */
function run_foo($task, $args)
{
    $age = pake_input('How old are you?');
    pake_echo_comment("You are ".$age);
    // throw new Exception('test');
}

/**
 * create an executable PHAR-archive of Pake
 *
 * @return bool
 * @author Alexey Zakhlestin
 */
function run_phar()
{
    $finder = pakeFinder::type('any')
                ->ignore_version_control()
                ->name('phar-stub.php', '*.class.php', 'autoload.php', 'cli_init.php', 'pakeFunction.php', 'sfYaml*.php');

    pakeArchive::createPharArchive($finder, dirname(__FILE__), 'pake.phar', 'phar-stub.php', null, true);
}
