<?php

// ----------------------------------------------
//           Weather API - Version 1.0
//              Started in Oct. 2016
//          created at Jugend Hackt2016
// ----------------------------------------------

require_once realpath(dirname(__FILE__)).'/lib.php';

$sub      = (array_key_exists('sub', $_AJAX)      ? $_AJAX['sub']      : 'get');          // the subcommand
$data     = (array_key_exists('data', $_AJAX)     ? $_AJAX['data']     : 'syndesi.de');   // the sended data
$dataType = (array_key_exists('dataType', $_AJAX) ? $_AJAX['dataType'] : 'json');         // json or xml

// a basic help-information
if(array_key_exists('help', $_AJAX)){
  $help = [
    'arguments' => [
      'sub'           => 'The subcommand, normally get or insert.',
      'data'          => 'The data which should be saved or the search query.',
      'dataType'      => '"json" or "xml".'
    ]
  ];
  finish($help);
}

$db = getDB();
$db->select_db('jh16');

switch($sub){
  case 'get':
    get($db, $data);
    break;
  case 'place':
    placeToGeoId($db, 'Saarbrücker strasse berlin');
    break;
  case 'insert':
    $lat       = (array_key_exists('lat', $_AJAX)       ? $_AJAX['lat']       : false);                   // \
    $lng       = (array_key_exists('lng', $_AJAX)       ? $_AJAX['lng']       : false);                   //  |-> Liest die Geoposition aus
    $geoId     = (array_key_exists('geoId', $_AJAX)     ? $_AJAX['geoId']     : false);                   //  |
    $address   = (array_key_exists('address', $_AJAX)   ? $_AJAX['address']   : false);                   // /
    $value     = (array_key_exists('value', $_AJAX)     ? $_AJAX['value']     : false);                   // liest die Sensordaten ein
    $timestamp = (array_key_exists('timestamp', $_AJAX) ? $_AJAX['timestamp'] : date('Y-m-d H:i:s'));     // liest den Timestamp aus (oder nimmt die aktuelle Zeit)
    $tableName = (array_key_exists('tableName', $_AJAX) ? $_AJAX['tableName'] : false);                   // liest den Tabellennamen ein
    if($lat == ''){$lat = false;}
    if($lng == ''){$lng = false;}
    if($geoId == ''){$geoId = false;}
    if($address == ''){$address = false;}
    if($value == ''){$value = false;}
    if($timestamp == ''){$timestamp = false;}
    if($tableName == ''){$tableName = false;}
    if(!$tableName){abort('You have not specified the tableName (e.g. "temperature", "humidity" etc.)');} // überprüft, ob der Tabellenname angegeben wurde
    if(!$value){abort('No value given');}     // Ohne Messwert wird das Programm abgebrochen
    if($geoId === false){
      if($lat === false || $lng === false){
        if($address === false){
          abort('You have not specified the geolocation.');
        }
        echo('Place to Geo');
        $geoId = placeToGeoId($db, $address);
      } else {
        echo('Lat Lng to Geo');
        $geoId = getGeoId($db, $lat, $lng);
      }
    }
    $out = insert($db, $tableName, $geoId, $timestamp, $value);
    break;
  case 'init':
    echo('##### INIT #####');
    init($db);
    break;
  default:
    abort('No valid subcommand (sub) given.');
    break;
}

$return = [
  'sub' => $sub
];

finish($return);

function get($db, $data){
}

function placeToGeoId($db, $address){
  $key = 'AIzaSyDm1_z1YtrCx3HF2o5oyiL8hoRoIojqyL8';
  $address = str_replace(' ', '+', $address);
  $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&key='.$key;
  $json = json_decode(file_get_contents($url));
  $gps = $json->results[0]->geometry->location;
  $id = getGeoId($db, $gps->lat, $gps->lng);
  return $id;
}

// überprüft, ob ein GEO-Punkt vorhanden ist und gibt dessen ID zurück (oder false)
function checkGeoId($db, $lat, $lng, $accuracy){
  $out = false;
  if($ps = $db->prepare('SELECT * FROM `geo` WHERE `lat` BETWEEN ? AND ? AND `lng` BETWEEN ? AND ?')){
    $latl = $lat - $accuracy;
    $lath = $lat + $accuracy;
    $lngl = $lng - $accuracy;
    $lngh = $lng + $accuracy;
    $ps->bind_param('dddd', $latl, $lath, $lngl, $lngh);
    $ps->execute();
    $res = $ps->get_result();
    $row = $res->fetch_assoc();
    if($row > 0){
      $out = $row['id'];
    } // if not than the ID doesn't exists
    $ps->free_result();
    $ps->close();
  }
  return $out;
}

// erstellt eine neue Geo-ID
function createGeoId($db, $lat, $lng){
  $out = false;
  $sql = 'INSERT INTO `geo` (`id`, `lat`, `lng`) VALUES (?, ?, ?)';
  if($ps = $db->prepare($sql)){
    $null = NULL;
    $ps->bind_param('idd', $null, $lat, $lng);
    $ps->execute();
    $error = $ps->error;
    if($error){
      echo(mysqli_error($db));
    } else {
      $out = true;
    }
    $ps->close();
  }
}

// gibt immer eine Geo-ID zurück (ggf. wird eine neue erstellt)
function getGeoId($db, $lat, $lng, $accuracy = 0.001){
  $id = checkGeoId($db, $lat, $lng, $accuracy);
  if($id === false){
    createGeoId($db, $lat,$lng);
    $id = checkGeoId($db, $lat, $lng, $accuracy);
  }
  return $id;
}

// speichert eine Information in derDatenbank ab
function insert($db, $tableName, $geoId, $timestamp, $value){
  $tableName = 'data_'.$tableName;
  $out = false;
  initSensorTable($db, $tableName);
  $sql = 'INSERT INTO `'.$tableName.'` (`id`, `geoId`, `timestamp`, `value`) VALUES (?, ?, ?, ?)';
  if($ps = $db->prepare($sql)){
    $null = NULL;
    $ps->bind_param('iisd', $null, $geoId, $timestamp, $value);
    $ps->execute();
    $error = $ps->error;
    if($error){
      echo(mysqli_error($db));
    } else {
      $out = true;
    }
    $ps->close();
  }
  return $out;
}

function getData($db, $tableName, $start, $end){
  $tableName = 'data_'.$tableName;
  $out = false;
  if($ps = $db->prepare('SELECT * FROM ? WHERE `timestamp` BETWEEN ? AND ?')){
    $ps->bind_param('sss', $tableName, $start, $end);
    $ps->execute();
    $res = $ps->get_result();
    $row = $res->fetch_assoc();


    
    if($row > 0){
      $_SESSION['id'] = $row['id'];
      $out = true;
    }
    $ps->free_result();
    $ps->close();
  }
  return $out;
}

function initSensorTable($db, $tableName){
  $sql = 'CREATE TABLE IF NOT EXISTS `'.$tableName.'` ( `id` INT NOT NULL AUTO_INCREMENT , `geoId` INT NOT NULL , `timestamp` DATETIME NOT NULL , `value` DOUBLE NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';
  if($db->query($sql) === true){
    return true;
  } else {
    return false;
  }
}

function init($db){
  $geoTable = 'CREATE TABLE `geo` ( `id` INT NOT NULL AUTO_INCREMENT , `lat` DECIMAL(9,6) NOT NULL , `lng` DECIMAL(9,6) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';
  $testTable = 'CREATE TABLE IF NOT EXISTS `sensor` ( `id` INT NOT NULL AUTO_INCREMENT , `geoId` INT NOT NULL , `timestamp` DATETIME NOT NULL , `value` DOUBLE NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;';
  if($db->query($geoTable) === true){
    if($db->query($testTable) === true){
      echo("##### TABLES CREATED #####");
    } else {
      echo("##### ERROR SENSOR #####");
      return false;
    }
  } else {
    echo($db->error);
    echo("##### ERROR GEO #####");
  }
}

?>
