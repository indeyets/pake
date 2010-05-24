<?php

// force usage of local pake
if ($_SERVER['PHP_SELF'] != dirname(__FILE__).'/bin/pake.php') {
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
    pake_sh(escapeshellarg($php_exec).' '.escapeshellarg(dirname(__FILE__).'/bin/pake.php').$force_tty.$args, true);

    die();
} else {
    pake_echo_comment("using local version of pake. good!");
}

/* registration */
pake_import('simpletest', false);
pake_import('pear');

pake_desc('create a single file with all pake classes. usage: pake compact [plugin1 [plugin2 […]]]');
pake_task('compact');

pake_desc('create an executable PHAR-archive of Pake');
pake_task('phar');

pake_desc('release a new pake version');
pake_task('release');

pake_task('foo');
pake_task('create_package_xml');

function run_foo($task, $args)
{
    $age = pake_input('How old are you?');
    pake_echo_comment("You are ".$age);
    // throw new Exception('test');
}

/* tasks */
/**
 * To be able to include a plugin in pake_runtime.php, you have to use include_once for external dependencies
 * and require_once for internal dependencies (for other included PI or pake classes) because we strip 
 * all require_once statements
 */
function run_compact($task, $args)
{
    $_root = dirname(__FILE__);

    // core-files
    $files = array(
        $_root.'/lib/pake/init.php',
        $_root.'/lib/pake/pakeFunction.php',
    );

    // adding pake-classes library
    $files = array_merge($files, pakeFinder::type('file')->name('*.class.php')->maxdepth(0)->in($_root.'/lib/pake'));
    // adding sfYaml library
    $files = array_merge($files, pakeFinder::type('file')->name('*.php')->in($_root.'/lib/pake/sfYaml'));

    $plugins = $args;
    foreach ($plugins as $plugin_name) {
        $files[] = $_root.'/lib/pake/tasks/pake'.$plugin_name.'Task.class.php';
    }

    // starter
    $files[] = $_root.'/bin/pake.php';

    // merge all files
    $content = '';
    foreach ($files as $file) {
        $content .= file_get_contents($file);
    }

    // strip require_once statements
    $content = preg_replace('/^\s*require(?:_once)?[^$;]+;/m', '', $content);

    // replace windows and mac format with unix format
    $content = str_replace(array("\r\n"), "\n", $content);

    // strip php tags
    $content = preg_replace(array("/<\?php/", "/<\?/", "/\?>/"), '', $content);

    // replace multiple new lines with a single newline
    $content = preg_replace(array("/\n\s+\n/s", "/\n+/s"), "\n", $content);

    $content = "#!/usr/bin/env php\n<?php\n".trim($content)."\n";

    $target_dir = $_root.'/target';
    pake_mkdirs($target_dir);

    $target = $target_dir.'/pake';
    if (!file_put_contents($target, $content)) {
        throw new pakeException('Failed to write to "'.$target.'"');
    }
    pake_echo_action('file+', $target);

    // strip all comments
    pake_strip_php_comments($target);

    pake_chmod('pake', $target_dir, 0755);
}

function run_phar()
{
    $finder = pakeFinder::type('any')->ignore_version_control()->name('phar-stub.php', '*.class.php', 'init.php', 'pakeFunction.php');
    pakeArchive::createPharArchive($finder, dirname(__FILE__), 'pake.phar', 'phar-stub.php', null, true);
}

function run_create_pear_package($task, $args)
{
    if (!isset($args[0]) || !$args[0]) {
        throw new pakeException('You must provide pake version to release (1.2.X for example).');
    }

    run_create_package_xml($task, $args);

    $_root = dirname(__FILE__);
    $version = $args[0];

    pake_replace_tokens('lib/pake/pakeApp.class.php', $_root, 'const VERSION = \'', '\';', array(
        '1.1.DEV' => "const VERSION = '$version';"
    ));

    // run packager
    try {
        pakePearTask::package_pear_package($_root.'/package.xml', $_root.'/target');
    } catch (pakeException $e) {
    }

    // cleanup
    pake_remove('package.xml', $_root);
    pake_replace_tokens(
        'lib/pake/pakeApp.class.php', $_root,        // file
        "const VERSION = '", "';",                   // dividers
        array(                                       // tokens
            $version => "const VERSION = '1.1.DEV';"
        )
    );

    if (isset($e))
        throw $e;
}

function run_create_package_xml($task, $args)
{
    if (!isset($args[0]) || !$args[0]) {
        throw new pakeException('You must provide pake version to release (1.2.X for example).');
    }

    $_root = dirname(__FILE__);
    $version = $args[0];

    // create a pear package
    pake_echo_comment('creating PEAR package.xml for version "'.$version.'"');
    pake_copy($_root.'/package.xml.tmpl', $_root.'/package.xml', array('override' => true));

    // add class files
    $class_files = pakeFinder::type('file')->ignore_version_control()->not_name('/^pakeApp.class.php$/')->name('*.php')->relative()->in($_root.'/lib');
    $xml_classes = '';
    foreach ($class_files as $file) {
        $dir_name  = dirname($file);
        $file_name = basename($file);
        $xml_classes .= '<file role="php" baseinstalldir="'.$dir_name.'" install-as="'.$file_name.'" name="lib/'.$file.'"/>'."\n";
    }

    // replace tokens
    pake_replace_tokens('package.xml', $_root, '##', '##', array(
        'PAKE_VERSION' => $version,
        'CURRENT_DATE' => date('Y-m-d'),
        'CLASS_FILES'  => $xml_classes,
    ));
}

function run_release($task, $args)
{
    if (!empty($args[0])) {
        $version = $args[0];
    } else {
        $version = pake_input('Please specify version');
        array_unshift($args, $version);
    }

    pakeSimpletestTask::call_simpletest($task);

    if ($task->is_verbose())
        pake_echo_comment('releasing pake version "'.$version.'"');

    run_create_pear_package($task, $args);
}
