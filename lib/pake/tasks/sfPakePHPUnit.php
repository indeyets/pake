<?php
  /**
   * @package symfony.plugins
   * @subpackage dgPHPUnitPlugin
   * @author Yannick Gauthier(yannick.gauthier@leoanrddg.com)
   * @version    SVN: $Id$
   */

  pake_desc('runs all PHPunit tests');                                         
  pake_task('phpunit-all', 'project_exists');

  pake_desc('runs PHPunit unit tests');
  pake_task('phpunit-unit', 'project_exists');

  pake_desc('runs PHPunit functional tests');
  pake_task('phpunit-functional', 'project_exists');

  pake_desc('created PHPunit test dir');
  pake_task('phpunit-create', 'project_exists');


  function run_phpunit_create($task, $args)
  {
    //Get appsdirectory!
    $dirs = sfFinder::type('directory')->ignore_version_control()->maxdepth(0)->relative()->in(sfConfig::get('sf_app_dir'));
	
    //base directory
    $base_dir = sfConfig::get('sf_test_dir').DIRECTORY_SEPARATOR.'phpunit';
    foreach($dirs as $app)
    {
        //Creating functional directory!   
        $functional_dir =  $base_dir. DIRECTORY_SEPARATOR .'functional'.DIRECTORY_SEPARATOR.$app;
        pake_echo(sprintf('# Checking for functional directory "%s"', $app));    

        if(!is_dir($functional_dir))
        {
           ob_start();
              mkdir($functional_dir, 0777, true);
              $error = ob_get_contents();
           ob_end_clean();
           
           if($error)
           {
             pake_echo(sprintf('# cannot create "%s"', $functional_dir));
           }
           else
           {
             pake_echo(sprintf('Folder "%s" successly created', $functional_dir));      
           }
        }
        else
        {
           pake_echo(sprintf('Folder "%s" already exist', $functional_dir));          
        }
        pake_echo('');
    }
	
	
	    ///Creating unit directory!
    $unit_dir = $base_dir . DIRECTORY_SEPARATOR .'unit';
    pake_echo('');  
    pake_echo('# Checking for unit directory');    
    if(!is_dir($unit_dir))
    {
        ob_start();   
            mkdir($unit_dir, 0777, true);
            $error = ob_get_contents();
       ob_end_clean();
       
       if($error)
       {
            pake_echo(sprintf('# cannot created "%s"', $unit_dir));
       }
       else
       {
            pake_echo(sprintf('Folder "%s" successly created', $unit_dir));   
       }
    }
    else
    {
       pake_echo(sprintf('Folder "%s" already exist', $unit_dir));          
    }
}
 
  
  function run_phpunit_unit($task, $args)
  {
    // set application
    $application = isset($args[0])?$args[0]:null;     
    _init_symfony($application);

	unset($args[0]);                        
    _runUnitTest($args);
  }
  
  function run_phpunit_functional($task, $args)
  {
    // set application
    $application = isset($args[0])?$args[0]:null;
    unset($args[0]); 
    
    //Init symfony!
    _init_symfony($application);

   
    //Check if selenium server is running!    
    $host = sfConfig::get('sf_selenium_rc_host');
    $port = sfConfig::get('sf_selenium_rc_port');

    $selenium_process = _check_selenium($host, $port);      
    if($selenium_process)  
    {
        pake_echo('# proceeding with tests..');
        pake_echo(''); 
    }
    else
    {
       throw new RunTimeException(sprintf('Server is not responding on %s:%s', $host, $port));
    }
      
    _runFunctionalTest($args);

  }

  function run_phpunit_all($task, $args)
  {
    //TODO voir le bug pour le testname quand base_dir est une array!

    $application = isset($args[0])?$args[0]:null;     
    _init_symfony($application);
     unset($args[0]);                        

    
    //lauching test
    $h = new dgPHPUnitHarness();
    $h->base_dir = array(_getTestDir('unit'), _getTestDir('functional'));

    $finder = pakeFinder::type('file')->ignore_version_control()->follow_link()->
      name('*Test.php');     
    
    $functionalTest = sfFinder::type('file')->name('*Test.php')->in(_getTestDir('functional')); 
     //Make sure selenium server is started!     
     if (count($functionalTest))
     {
        $host = sfConfig::get('sf_selenium_rc_host');
        $port = sfConfig::get('sf_selenium_rc_port');
        $process = _check_selenium($host, $port);
        if(!$process)
        {
            throw new RunTimeException(sprintf('Server is not responding on %s:%s', $host, $port));   
        }
     }
    
    //Start test
    pake_echo('# proceeding with tests..');
    pake_echo('');     
    $h->register($finder->in($h->base_dir));    
    $h->run();
  }

  function _check_selenium($host, $port)
  {

       
    pake_echo(sprintf('# Trying to connect to selenium server on %s:%s', $host, $port));       
    
    return  _probe_selenium($host, $port, 1);   
  }
  
  function _probe_selenium($host, $port, $attempts = 5, $timeout = 1)
  {   
    //Checking if server is statted!
    while (true)
    {
        $r = @fsockopen($host, $port, $errno, $errstr, $timeout);

      if (false !== $r)
      {
        fclose($r);
        return true;
      }
      else
      {
        if (--$attempts <= 0)
        {
          return false;
        }
        
        pake_echo('# .');
        sleep(1);        
      }
    }
       
    return false;
  }


  function _init_symfony($app = null)
  {
      if(!$app)
      {
            throw new Exception('You must provide your application name');      
      }
      
      //check if dir exist
      
      if(!is_dir(sfConfig::get('sf_app_dir'). $app))
      {
      
          throw new Exception(sprintf('There no application "%s" for this project.', $app));
      }    
      
    define('SF_ROOT_DIR', realpath(dirname(__file__) . '/../../../..'));
    define('SF_APP', $app);
    define('SF_ENVIRONMENT', 'test');
    define('SF_DEBUG', true);

    require_once (SF_ROOT_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR .
      SF_APP . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

    _init_phpunit();
  }


  function _runUnitTest($arguments = null)
  {     
   //Run test!
   
   if (isset($arguments[1]))
   {
      foreach ($arguments as $path)
      {
         $files = pakeFinder::type('file')->ignore_version_control()->follow_link()->
          name(basename($path) . 'Test.php')->in(_getTestDir('unit') .
          dirname($path));

            if(count($files) == 0 )
            {
                $not_exist[] = $path;
            }

            
            foreach ($files as $file)
            {
              $initialiser = new dgPHPUnitInitialiser();

              try
              {
                $initialiser->init($file);
              }
              catch (exception $e)
              {
                  pake_echo(sprintf('# TestSuite "%s" started.', $path));  
                  pake_echo(sprintf('Error: %s in file %s', $e->getMessage(), $file));  
                  pake_echo('');    
                continue;
              }
              $initialiser->run(new dgPHPUnitTestPrinterTapColour());
            }
          }
          
          if(isset($not_exist))
          {
             $files_to_string = implode(', ',$not_exist);
             pake_echo(sprintf('The following files couldn\'t be found: %s', $files_to_string));              
          }
    }
    else
    {
      $h = new dgPHPUnitHarness();
      $h->base_dir = _getTestDir('unit');

      $finder = pakeFinder::type('file')->ignore_version_control()->follow_link()->
        name('*Test.php');
      $h->register($finder->in($h->base_dir));

      $h->run();
    }
  
  } 

  
function _runFunctionalTest($arguments = null)
{
    if (isset($arguments[1]))
    {                              
      $dir = _getTestDir('functional');

        
      foreach ($arguments as $path)
      {
        $files = pakeFinder::type('file')->ignore_version_control()->follow_link()->
         name(basename($path) . 'Test.php')->in($dir);

         if(count($files) == 0 )
         {
             $not_exist[] = $path;
         }          
          
         foreach ($files as $file)
         {
           $initialiser = new dgPHPUnitInitialiser();
          try
          {
            $initialiser->init($file);
          }
          
          catch (exception $e)
          {
            pake_echo(sprintf('# TestSuite "%s" started.', $path));  
            pake_echo(sprintf('Error: %s in file %s', $e->getMessage(), $file));  
            pake_echo('');     
            continue;
          }

          $initialiser->run(new dgPHPUnitTestPrinterTapColour());
        }
      }
      
      if(isset($not_exist))
      {
        $files_to_string = implode(', ',$not_exist);
        pake_echo(sprintf('# The following files couldn\'t be found: %s', $files_to_string));
        pake_echo('');
      }
    }
    else
    {
      $h = new dgPHPUnitHarness();
      $h->base_dir = _getTestDir('functional');

      $finder = pakeFinder::type('file')->ignore_version_control()->follow_link()->
        name('*Test.php');
      $h->register($finder->in($h->base_dir));

      $h->run();
    } 
}

  
  function _init_phpunit()
  {
    //PEAR
    if (sfConfig::get('sf_phpunit_pear'))
    {     
      //Get PEAR PATH from include_path!
      $pear = explode(';', get_include_path());
      foreach($pear as $key => $value)
      {
        if(preg_match("/pear$/i", $value))
        {
            break;
        }
      }
     
      if (!is_dir($pear[$key] . DIRECTORY_SEPARATOR . 'PHPUnit'))
      {
        throw new RunTimeException(sprintf('PHPUnit Library in %s couldn\'t be found.', $pear[$key]));
      }
    }

    ///PHPUnit
    else
    {
      //Check if phpunit_lib_dir is set!
      if (sfConfig::get('sf_phpunit_lib_dir'))
      {
        _check_directory(sfConfig::get('sf_phpunit_lib_dir'));
      }
      else
      {
        throw new Exception('sf_phpunit_lib_dir is not configured in plugins/sfPHPUnitPlugin/config/settings.yml');
      }
    }
  }
  


  function _check_directory($dir_name)
  {
    //set include_path directory to lowercase!
    $include_path = explode(";", get_include_path());
    foreach ($include_path as $key => $value)
    {
      $include_path[$key] = strtolower($value);
    }

    $pear_dir = dirname($dir_name);
    if (is_dir($dir_name . DIRECTORY_SEPARATOR . 'PHPUnit'))
    {
      //check if path is already in include path, if not set include_path
      if (!array_search(strtolower($dir_name), $include_path))
      {
        set_include_path(get_include_path() . PATH_SEPARATOR . $dir_name);
      }
    }
    else
    {
      throw new Exception(sprintf('PHPUnit Library in %s couldn\'t be found.', $dir_name));
    }

    return;
  }
  
  //return dirname
  //get full path for test directory!
  function _getTestDir($type)
  {
      $test_dir = sfConfig::get('sf_test_dir').DIRECTORY_SEPARATOR.'phpunit';
      
    if($type == 'unit')
    {
      $test_dir .= DIRECTORY_SEPARATOR.$type;  
    }
    else
    {
        $test_dir .= DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.SF_APP;  
    }     
      
    if(!is_dir($test_dir))
    {
      throw new RunTimeException(sprintf('Test directory %s couldn\'t be found.' , $test_dir));              
    }
      
     return $test_dir;
  }