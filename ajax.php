<?php
error_reporting(E_ALL);

// ----------------------------------------------
//             Ajax.php - Version 1.1
//              Started in Sep. 2016
//              inspired by Golem.de
//  Author: Sören Klein, soerenklein98@gmail.com
// ----------------------------------------------

require_once 'php/lib.php';

//header('Access-Control-Allow-Origin: *');
//header('Access-Control-Allow-Credentials: true');

$args = [];

if(isset($_GET) && 0 < count($_GET)){
  $args = $_GET;
}elseif(isset($_POST) && 0 < count($_POST)){
  $args = $_POST;
}

if(count($args) == 0){
  abort('No arguments passed.');
}
if(!isset($args['command'])){
  abort('No command set.');
}

$command = $args['command'];

if(!preg_match('/[a-zA-Z]+/', $command)) {
  abort('Command contains invalid characters.');
}

$file = 'php/'.$command.'.php';

if(!file_exists($file)){
  abort('Command does not exist.');
}

$_AJAX = [];

foreach($args as $k => $v){
  if($k != 'command'){
    $_AJAX[$k] = $v;
  }
}

function throwMissingArg($argName){
  abort('Argument '.$argName.' is missing.', 1);
}

include_once $file;

// ---------------------
//  Here the file works
// ---------------------

finish('Program has ended. Have a nice day :D');

?>
