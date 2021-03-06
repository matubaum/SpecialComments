#!/usr/bin/php
<?php


/**************************************************************
 Autoloading function
 ***************************************************************/

function default_autoload($class_name) {
    $base = dirname(__FILE__). '/lib/*';
    $path[] = $base;
    
    while(count($path) != 0){
        $v = array_shift($path);
        foreach(glob($v) as $item){
            if (is_dir($item))
                $path[] = $item . '/*';
            elseif (is_file($item)){
                 if(basename($item) == $class_name . '.php'){
                     require_once $item;
                     return;
                 }
            }
        }
    }
}



/**************************************************************
 Retrieving attributes and parameters from CLI 
***************************************************************/

$directory_target = $directory_origin = '';
$arg_section = false;
$args = array();
$pars = array();

foreach($argv as $arg){
    if($arg_section === false && basename($arg) !== basename(__FILE__)){
        continue;
    }

    if($arg_section == false){
        $arg_section = true; 
        continue;
    }

    if(substr($arg, 0, 2) === '--'){
      if(false !== strpos($arg,'=')){
          list($name, $value) = split('=',substr($arg, 2));
          $args[$name] = $value;
      }else{
          printf('Invalid argument %s', $arg); die;
      }
    }else{
        $pars[] = $arg;
    }
}


/**************************************************************
 Validations for Command
***************************************************************/

if(count($pars)<2){
    printf('This command requires 2 parameters: generate.php SOURCE_DIRECTORY TARGET_DIRECTORY'); die;
}

list($source_directory, $target_directory) = $pars;

if(false === is_dir($source_directory)){
    printf("'%s' is not a valid directory\n", $source_directory); die;
}elseif(false === is_readable($source_directory)){
    printf("The directory '%s' is not readable\n", $source_directory); die;
}

if(false === is_dir($target_directory)){
    printf("'%s' is not a valid directory\n", $target_directory); die;
}elseif(false === is_readable($target_directory)){
    printf("The directory '%s' is not readable\n", $target_directory); die;
}

$emitter =  (isset($args['emitter']))? $args['emitter'] : 'php';
$emitter_dir = dirname(__FILE__) . '/emitters/' . $emitter;
if(is_dir($emitter_dir) === false || is_readable($emitter_dir) === false){
    printf("'%s' is not a valid emitter\n", $emitter); die;
}


define('EMITTER_DIR', realpath($emitter_dir));
spl_autoload_register("default_autoload");
require_once EMITTER_DIR . '/init.php';



/**************************************************************
 Parsing files and creating functions
***************************************************************/

$creator_class = ucfirst($emitter). "TemplateCreator";
$con = new $creator_class;
$con->generateHelpersFromDir(realpath($source_directory), realpath($target_directory));
