<?php

include('config.php');
include ('controller/controller.php');

$cont = new controller;
/*
$fee[] = array("id" =>'62', "type" => 'hey xun', 'fee' => '1');

$fee[] = array("id" =>'63', "type" => 'hi', 'fee' => '1');

$fee[] = array("id" =>'64', "type" => 'hwe', 'fee' => '1');

$cont->update($fee, '179', 'testerdsd');
*/
//$cont->route('3.12766', '101.678');

if(isset($_REQUEST['action']))	{
	$action = $_REQUEST['action'];
	switch($action){
		case 'create_place':
			$name = $_REQUEST['name'];
			$lat = $_REQUEST['lat'];
			$lng = $_REQUEST['lng'];
			$fees = $_REQUEST['fees'];

			$cont->save_new_file($name, $lat, $lng, $fees);
		break;
		
		case 'edit_place':
			$place_id = $_REQUEST['id'];
			$name = $_REQUEST['name'];
			$fees = $_REQUEST['fees'];

			$log = json_encode($_POST);

			global $dbh;

			$dbh->query("INSERT INTO `log` (`log`) VALUES ('$log')");

			$cont->update($fees, $place_id, $name);
		break;

		case 'get_place':
			$lng = $_REQUEST['lng'];
			$lat = $_REQUEST['lat'];

			$cont->route($lat, $lng);
		break;
	}
}

//if($x){
//else
	//echo"hi";

//echo $cont;

