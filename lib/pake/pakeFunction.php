<?php

require_once 'pake/pakeException.class.php';
require_once 'pake/pakeYaml.class.php';
require_once 'pake/pakeGetopt.class.php';
require_once 'pake/pakeFinder.class.php';
require_once 'pake/pakeTask.class.php';
require_once 'pake/pakeFileTask.class.php';
require_once 'pake/pakeApp.class.php';

function pake_import($name, $import_default_tasks = true)
{
  $class_name = 'pake'.ucfirst(strtolower($name)).'Task';

  // plugin available?
  $plugin_path = '';
  foreach (pakeApp::get_plugin_dirs() as $dir)
  {
    if (file_exists($dir.DIRECTORY_SEPARATOR.$class_name.'.class.php'))
    {
      $plugin_path = $dir.DIRECTORY_SEPARATOR.$class_name.'.class.php';
      break;
    }
  }

  if ($plugin_path)
  {
    require_once $plugin_path;
  }
  else
  {
    throw new pakeException(sprintf('Plugin "%s" does not exist.', $name));
  }

  if ($import_default_tasks && is_callable($class_name, 'import_default_tasks'))
  {
    call_user_func(array($class_name, 'import_default_tasks'));
  }
}

function pake_task($name)
{
  $args = func_get_args();
  array_shift($args);
  pakeTask::define_task($name, $args);

  return $name;
}

function pake_alias($alias, $name)
{
  pakeTask::define_alias($alias, $name);

  return $alias;
}

function pake_desc($comment)
{
  pakeTask::define_comment($comment);
}

function pake_properties($property_file)
{
  $file = $property_file;
  if (!pakeFinder::isPathAbsolute($file))
  {
    $file = getcwd().DIRECTORY_SEPARATOR.$property_file;
  }

  if (file_exists($file))
  {
    pakeApp::get_instance()->set_properties(parse_ini_file($file, true));
  }
  else
  {
    throw new pakeException('Properties file does not exist.');
  }
}

function pake_file($name)
{
  $args = func_get_args();
  array_shift($args);
  pakeFileTask::define_task($name, $args);

  return $name;
}

function pake_mkdirs($path, $mode = 0777)
{
  $verbose = pakeApp::get_instance()->get_verbose();

  if (is_dir($path))
  {
    return true;
  }

  if ($verbose) echo '>> dir+      '.pakeApp::excerpt($path)."\n";

  return mkdir($path, $mode, true);
}

/*
  override => boolean
*/
function pake_copy($origin_file, $target_file, $options = array())
{
  $verbose = pakeApp::get_instance()->get_verbose();

  if (!array_key_exists('override', $options))
  {
    $options['override'] = false;
  }

  // we create target_dir if needed
  if (!is_dir(dirname($target_file)))
  {
    pake_mkdirs(dirname($target_file));
  }

  $most_recent = false;
  if (file_exists($target_file))
  {
    $stat_target = stat($target_file);
    $stat_origin = stat($origin_file);
    $most_recent = ($stat_origin['mtime'] > $stat_target['mtime']) ? true : false;
  }

  if ($options['override'] || !file_exists($target_file) || $most_recent)
  {
    if ($verbose) echo '>> file+     '.pakeApp::excerpt($target_file)."\n";
    copy($origin_file, $target_file);
  }
}

function pake_mirror($arg, $origin_dir, $target_dir, $options = array())
{
  $files = pakeApp::get_files_from_argument($arg, $origin_dir, true);

  foreach ($files as $file)
  {
    if (is_dir($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_mkdirs($target_dir.DIRECTORY_SEPARATOR.$file);
    }
    else if (is_file($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_copy($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file, $options);
    }
    else if (is_link($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_symlink($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file);
    }
    else
    {
      throw new pakeException(sprintf('Unable to determine "%s" type', $file));
    }
  }
}

function pake_remove($arg, $target_dir)
{
  $files = array_reverse(pakeApp::get_files_from_argument($arg, $target_dir));

  $verbose = pakeApp::get_instance()->get_verbose();

  foreach ($files as $file)
  {
    if (is_dir($file))
    {
      if ($verbose) echo '>> dir-      '.pakeApp::excerpt($file)."\n";

      rmdir($file);
    }
    else
    {
      if ($verbose) echo '>> file-     '.pakeApp::excerpt($file)."\n";

      unlink($file);
    }
  }
}

function pake_touch($arg, $target_dir)
{
  $files = pakeApp::get_files_from_argument($arg, $target_dir);

  $verbose = pakeApp::get_instance()->get_verbose();

  foreach ($files as $file)
  {
    if ($verbose) echo '>> file+     '.pakeApp::excerpt($file)."\n";

    touch($file);
  }
}

function pake_replace_tokens($arg, $target_dir, $begin_token, $end_token, $tokens)
{
  $files = pakeApp::get_files_from_argument($arg, $target_dir, true);

  $verbose = pakeApp::get_instance()->get_verbose();

  foreach ($files as $file)
  {
    $replaced = false;
    $content = file_get_contents($target_dir.DIRECTORY_SEPARATOR.$file);
    foreach ($tokens as $key => $value)
    {
      $content = str_replace($begin_token.$key.$end_token, $value, $content, $count);
      if ($count) $replaced = true;
    }

    if ($verbose && $replaced) echo '>> tokens    '.pakeApp::excerpt($target_dir.DIRECTORY_SEPARATOR.$file)."\n";

    file_put_contents($target_dir.DIRECTORY_SEPARATOR.$file, $content);
  }
}

function pake_symlink($origin_dir, $target_dir)
{
  $ok = false;
  if (is_link($target_dir))
  {
    if (readlink($target_dir) != $origin_dir)
    {
      unlink($target_dir);
    }
    else
    {
      $ok = true;
    }
  }

  if (!$ok)
  {
    $verbose = pakeApp::get_instance()->get_verbose();
    if ($verbose) echo '>> symlink+  '.pakeApp::excerpt($target_dir)."\n";
    symlink($origin_dir, $target_dir);
  }
}

function pake_chmod($arg, $target_dir, $mode, $umask = 0000)
{
  $current_umask = umask();
  umask($umask);

  $files = pakeApp::get_files_from_argument($arg, $target_dir, true);

  $verbose = pakeApp::get_instance()->get_verbose();

  foreach ($files as $file)
  {
    if ($verbose) printf('>> chmod %o '.pakeApp::excerpt($target_dir.DIRECTORY_SEPARATOR.$file)."\n", $mode);
    chmod($target_dir.DIRECTORY_SEPARATOR.$file, $mode);
  }

  umask($current_umask);
}

function pake_sh($cmd)
{
  $verbose = pakeApp::get_instance()->get_verbose();
  if ($verbose) echo ">> exec      $cmd\n";
  
  ob_start();
  passthru($cmd.' 2>&1', $return);
  $content = ob_get_contents();
  ob_end_clean();

  if ($return > 0)
  {
    throw new pakeException(sprintf('Problem executing command %s', $verbose ? "\n".$content : ''));
  }

  return $content;
}

function pake_strip_php_comments($arg)
{
  /* T_ML_COMMENT does not exist in PHP 5.
   * The following three lines define it in order to
   * preserve backwards compatibility.
   *
   * The next two lines define the PHP 5-only T_DOC_COMMENT,
   * which we will mask as T_ML_COMMENT for PHP 4.
   */
  if (!defined('T_ML_COMMENT'))
  {
    define('T_ML_COMMENT', T_COMMENT);
  }
  else
  {
    if (!defined('T_DOC_COMMENT')) define('T_DOC_COMMENT', T_ML_COMMENT);
  }

  $files = pakeApp::get_files_from_argument($arg);

  foreach ($files as $file)
  {
    if (!is_file($file)) continue;

    $source = file_get_contents($file);
    $output = '';

    $tokens = token_get_all($source);
    foreach ($tokens as $token)
    {
      if (is_string($token))
      {
        // simple 1-character token
        $output .= $token;
      }
      else
      {
        // token array
        list($id, $text) = $token;
        switch ($id)
        {
          case T_COMMENT:
          case T_ML_COMMENT: // we've defined this
          case T_DOC_COMMENT: // and this
            // no action on comments
            break;
          default:
          // anything else -> output "as is"
          $output .= $text;
          break;
        }
      }
    }

    file_put_contents($file, $output);
  }
}
