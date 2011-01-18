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
pake_import('simpletest');
pake_import('pear');

pake_desc('create a single file with all pake classes. usage: pake compact [plugin1 [plugin2 […]]]');
pake_task('compact');

pake_desc('create an executable PHAR-archive of Pake');
pake_task('phar');

pake_desc('release a new pake version');
pake_task('release');

pake_task('foo');
pake_task('create_package_xml');
pake_task('obs');

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
    $options = pakeYaml::loadFile($_root.'/options.yaml');
    $version = $options['version'];

    pake_replace_tokens('lib/pake/pakeApp.class.php', $_root, 'const VERSION = \'', '\';', array(
        '1.1.DEV' => "const VERSION = '$version';"
    ));

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
        $content .= pake_read_file($file);
    }

    pake_replace_tokens(
        'lib/pake/pakeApp.class.php', $_root,        // file
        "const VERSION = '", "';",                   // dividers
        array(                                       // tokens
            $version => "const VERSION = '1.1.DEV';"
        )
    );

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

function run_create_pear_package()
{
    run_create_package_xml();

    $_root = dirname(__FILE__);
    $options = pakeYaml::loadFile($_root.'/options.yaml');
    $version = $options['version'];

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

function run_create_package_xml()
{
    $_root = dirname(__FILE__);
    $options = pakeYaml::loadFile($_root.'/options.yaml');
    $version = $options['version'];

    // create a pear package
    pake_echo_comment('creating PEAR package.xml for version "'.$version.'"');
    pake_copy($_root.'/package.xml.tmpl', $_root.'/package.xml', array('override' => true));

    // add class files
    $class_files = pakeFinder::type('file')->ignore_version_control()
                                           ->not_name('/^pakeApp.class.php$/')
                                           ->name('*.php')
                                           ->maxdepth(0)
                                           ->relative()
                                           ->in($_root.'/lib/pake');

    $task_files = pakeFinder::type('file')->ignore_version_control()
                                          ->name('*.php')
                                          ->relative()
                                          ->in($_root.'/lib/pake/tasks');

    $renames = '';
    $xml_classes = '';
    $task_classes = '';

    foreach ($class_files as $file) {
        $xml_classes .= '<file role="php" name="'.$file.'"/>'."\n";
        $renames .= '<install as="pake/'.$file.'" name="lib/pake/'.$file.'"/>'."\n";
    }

    foreach ($task_files as $file) {
        $task_classes .= '<file role="php" name="'.$file.'"/>'."\n";
        $renames .= '<install as="pake/tasks/'.$file.'" name="lib/pake/tasks/'.$file.'"/>'."\n";
    }

    // replace tokens
    pake_replace_tokens('package.xml', $_root, '##', '##', array(
        'PAKE_VERSION' => $version,
        'CURRENT_DATE' => date('Y-m-d'),
        'CLASS_FILES'  => $xml_classes,
        'TASK_FILES'   => $task_classes,
        'RENAMES'      => $renames,
    ));
}

function run_release($task)
{
    $_root = dirname(__FILE__);
    $options = pakeYaml::loadFile($_root.'/options.yaml');
    $version = $options['version'];

    pakeSimpletestTask::call_simpletest($task);

    if ($task->is_verbose())
        pake_echo_comment('releasing pake version "'.$version.'"');

    run_create_pear_package();
}

function run_obs($task)
{
    run_release($task);

    $_root = dirname(__FILE__);
    $options = pakeYaml::loadFile($_root.'/options.yaml');
    $version = $options['version'];

    $target = $_root.'/target';

    pake_sh('gunzip '.escapeshellarg($target.'/pake-'.$version.'.tgz'));
    pake_sh('tar -r -f '.escapeshellarg($target.'/pake-'.$version.'.tar').' -C '.escapeshellarg($_root).' debian');
    pake_sh('gzip '.escapeshellarg($target.'/pake-'.$version.'.tar'));
}
