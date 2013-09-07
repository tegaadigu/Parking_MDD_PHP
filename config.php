<?php
session_start();
// Remember the absolute path for convenience.
define('BASE_DIR', dirname(__FILE__) );
define('CONTROLLER_DIR', BASE_DIR."/controller");
define('VIEW_DIR', BASE_DIR."/view");
define('MODEL_DIR', BASE_DIR."/model");


$db['name'] = 'mdd2013';
$db['host'] = 'localhost';
$db['username'] = 'root';
$db['password'] = '';

try {
		$dbh = new PDO("mysql:host=".$db['host'].";dbname=".$db['name'], $db['username'], $db['password']);
		//echo"connected to database";
}
catch(PDOException $e)
{
  echo $e->getMessage();
}

?>