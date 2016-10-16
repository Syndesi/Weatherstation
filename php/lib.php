<?php

// ----------------------------------------------
//             Lib.php - Version 1.0
//              Started in Sep. 2016
//  Author: Sören Klein, soerenklein98@gmail.com
// ----------------------------------------------

function getDB(){
  if(($db = mysqli_connect('localhost', 'jh16', 'sYwLLYFUdMpawBLM')) === false){
    abort('Access to database is invalid.', 2);
  }
  mysqli_set_charset($db, 'utf8');
  return $db;
}

function finish($out, $code = 0, $exitCode = 0){
  $json = [
    'success' => true,
    'code' => $code,
    'out' => $out
  ];
  echo(json_encode($json));
  exit($exitCode);
}

function abort($out, $code = 0, $exitCode = 1){
  $json = [
    'success' => false,
    'code' => $code,
    'out' => $out
  ];
  echo(json_encode($json));
  exit($exitCode);
}

function b64toInert($b64){
  // Umwandlung des normalen B64-Formates zu einem Linksicheren (keine / und =)
  $temp = str_replace('/', '_', $b64);
  $temp = str_replace('+', '-', $temp);
  return rtrim($temp, '=');
}

function inertToB64($inert){
  // Umwandlund des eigenen B64-Formates (keine '/' und '=') zum normalen Format
  $temp = str_replace('_', '/', $inert);
  $temp = str_replace('-', '+', $temp);
  return $temp;
}

function getNewId($length){
  // "Einzigartigkeit" auf 6 Bytes beschränkt -> 281.000.000.000.000 Möglichkeiten
  $key = random_bytes($length);
  $b64 = base64_encode($key);
  return b64toInert($b64);
}

function info($msg, $code = 0){
  // writes the information in a database or file
}

function error($msg, $code = 1){
  // writes the error in a database or file
}

function getDateTime(){
  // returns the current time
  $dateTime = new DateTime();
  return $dateTime->format('Y-m-d H:i:s');
}

function isCommandLineInterface(){
  // returns the environment type
  return (php_sapi_name() === 'cli');
}

function isWindows(){
  if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
    return true;
  }
  return false;
}

?>
