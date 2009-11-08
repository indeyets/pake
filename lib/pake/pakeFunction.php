<?php

/**
 * @package    pake
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license    see the LICENSE file included in the distribution
 * @version    SVN: $Id$
 */

function pake_import($name, $import_default_tasks = true)
{
  $class_name = 'pake'.ucfirst(strtolower($name)).'Task';

  if (!class_exists($class_name)) {
    // plugin available?
    $plugin_path = '';
    foreach (pakeApp::get_plugin_dirs() as $dir) {
      if (file_exists($dir.'/'.$class_name.'.class.php')) {
        $plugin_path = $dir.'/'.$class_name.'.class.php';
        break;
      }
    }

    if (!$plugin_path) {
      throw new pakeException(sprintf('Plugin "%s" does not exist.', $name));
    }

    require_once $plugin_path;
  }

  if ($import_default_tasks && is_callable($class_name, 'import_default_tasks')) {
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
  if (is_dir($path)) {
    return true;
  }

  if (file_exists($path)) {
    throw new pakeException('Can not create directory at "'.$path.'" as place is already occupied by file');
  }

  pake_echo_action('dir+', $path);

  return @mkdir($path, $mode, true);
}

/*
  override => boolean
*/
function pake_copy($origin_file, $target_file, $options = array())
{
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
    pake_echo_action('file+', $target_file);
    copy($origin_file, $target_file);
  }
}

function pake_rename($origin, $target, $options = array())
{
  // we check that target does not exist
  if (is_readable($target))
  {
    throw new pakeException(sprintf('Cannot rename because the target "%" already exist.', $target));
  }

  pake_echo_action('rename', $origin.' > '.$target);
  rename($origin, $target);
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

  foreach ($files as $file)
  {
    if (is_dir($file) && !is_link($file))
    {
      pake_echo_action('dir-', $file);

      rmdir($file);
    }
    else
    {
      pake_echo_action(is_link($file) ? 'link-' : 'file-', $file);

      unlink($file);
    }
  }
}

function pake_touch($arg, $target_dir)
{
  $files = pakeApp::get_files_from_argument($arg, $target_dir);

  foreach ($files as $file)
  {
    pake_echo_action('file+', $file);

    touch($file);
  }
}

function pake_replace_tokens_to_dir($arg, $src_dir, $target_dir, $begin_token, $end_token, $tokens)
{
    $files = pakeApp::get_files_from_argument($arg, $src_dir, true);

    foreach ($files as $file)
    {
      $replaced = false;
      $content = file_get_contents($src_dir.DIRECTORY_SEPARATOR.$file);
      foreach ($tokens as $key => $value)
      {
        $content = str_replace($begin_token.$key.$end_token, $value, $content, $count);
        if ($count) $replaced = true;
      }

      pake_echo_action('tokens', $target_dir.DIRECTORY_SEPARATOR.$file);

      file_put_contents($target_dir.DIRECTORY_SEPARATOR.$file, $content);
    }
}

function pake_replace_tokens($arg, $target_dir, $begin_token, $end_token, $tokens)
{
    pake_replace_tokens_to_dir($arg, $target_dir, $target_dir, $begin_token, $end_token, $tokens);
}

function pake_symlink($origin_dir, $target_dir, $copy_on_windows = false)
{
  if (!function_exists('symlink') && $copy_on_windows)
  {
    $finder = pakeFinder::type('any')->ignore_version_control();
    pake_mirror($finder, $origin_dir, $target_dir);
    return;
  }

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
    pake_echo_action('link+', $target_dir);
    symlink($origin_dir, $target_dir);
  }
}

function pake_chmod($arg, $target_dir, $mode, $umask = 0000)
{
  $current_umask = umask();
  umask($umask);

  $files = pakeApp::get_files_from_argument($arg, $target_dir, true);

  foreach ($files as $file)
  {
    pake_echo_action(sprintf('chmod %o', $mode), $target_dir.DIRECTORY_SEPARATOR.$file);
    chmod($target_dir.DIRECTORY_SEPARATOR.$file, $mode);
  }

  umask($current_umask);
}

function pake_sh($cmd)
{
  $verbose = pakeApp::get_instance()->get_verbose();
  pake_echo_action('exec ', $cmd);

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

function pake_strip_php_comments($arg, $target_dir = '')
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

  $files = pakeApp::get_files_from_argument($arg, $target_dir);

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


function pake_input($question, $default = null)
{
    pake_echo($question);

    while (true) {
        if (null === $default)
            echo '[>] ';
        else
            echo '[> default="'.$default.'"] ';

        $fp = fopen('php://stdin', 'r');
        $retval = fgets($fp);
        fclose($fp);

        if (false === $retval) {
            echo "\n";
            continue;
        }

        $retval = rtrim($retval, "\r\n");

        if ('' === $retval) {
            if (null !== $default) {
                $retval = $default;
                break;
            }

            continue;
        }

        break;
    }

    return $retval;
}

function pake_select_input($question, array $options, $default = null)
{
    if (null !== $default) {
        if (!is_numeric($default))
            throw new UnexpectedValueException("Default is specified, but is not numeric");

        if (!isset($options[$default]))
            throw new UnexpectedValueException("Default is specified, but it is not one of options");
    }


    pake_echo($question);

    $i = 1;
    $options_strs = array();
    foreach ($options as $option) {
        $options_strs[] = '('.$i++.') '.$option;
    }

    pake_echo('  '.implode("\n  ", $options_strs));

    while (true) {
        if (null === $default)
            echo '[>] ';
        else
            echo '[> default="'.($default + 1).'"] ';

        $fp = fopen('php://stdin', 'r');
        $retval = fgets($fp);
        fclose($fp);

        if (false === $retval) {
            echo "\n";
            continue;
        }

        $retval = rtrim($retval, "\r\n");

        if ('' === $retval) {
            if (null === $default) {
                continue;
            }

            $retval = $options[$default];
        } else {
            if (!is_numeric($retval)) {
                pake_echo_error("Just enter number");
                continue;
            }

            if (!isset($options[$retval - 1])) {
                pake_echo_error("There is no option ".$retval);
                continue;
            }

            $retval = $options[$retval - 1];
        }

        break;
    }

    return $retval;
}

function pake_format_action($section, $text, $size = null)
{
    $width = 9 + strlen(pakeColor::colorize('', 'INFO'));
    return sprintf('>> %-'.$width.'s %s', pakeColor::colorize($section, 'INFO'), pake_excerpt($text, $size));
}

function pake_echo_action($section, $text)
{
    if (pakeApp::get_instance()->get_verbose()) {
        pake_echo(pake_format_action($section, $text));
    }
}

function pake_excerpt($text, $size = null)
{
    if (null === $size) {
        $cols = getenv('COLUMNS');

        if (false !== $cols)
            $size = $cols - 13;
        else
            $size = pakeApp::$MAX_LINE_SIZE;
    }

    if (strlen($text) < $size) {
        return $text;
    }

    $subsize = floor(($size - 3) / 2);

    return substr($text, 0, $subsize).pakeColor::colorize('...', 'INFO').substr($text, -$subsize);
}

function pake_echo($text)
{
    echo $text."\n";
}

function pake_echo_comment($text)
{
    if (pakeApp::get_instance()->get_verbose()) {
        pake_echo(sprintf(pakeColor::colorize('   # %s', 'COMMENT'), $text));
    }
}

function pake_echo_error($text)
{
    pake_echo(sprintf(pakeColor::colorize('   ! %s', 'ERROR'), $text));
}
