<?php

class controller	{

	/*
	* function to save newly added data to database
	* @param name (string), lat (float), lng (float),
		fee (decimal [13,2]), type(string), rule (string)
	* @return array
	*/

	public function save_new_file($name, $lat, $lng, $fees)	{
		
		//print_r($fees);

		//die;

		global $dbh;

		header('Content-Type: application/json');

		$dbh->query("INSERT INTO `place` (`id` ,`latitude` ,`longitude` ,`name` ,`google_place_id`) VALUES (NULL ,  '".$lat."',  '".$lng."',  '".$name."',  '');");

		$place_id =  $dbh->lastInsertId();

		foreach ($fees as $fee_ ) {

			$dbh->query("INSERT INTO `fees` (`id` ,`rule` ,`fee` ,`type` ,`place_id`) VALUES (NULL ,  '".$fee_['rule']."',  '".$fee_['fee']."',  '".$fee_['type']."',  '".$place_id."');");
		
		}

		$data = $dbh->query("SELECT * FROM `place` WHERE `id` = '".$place_id."'");

		$data_ = $data->fetch();

		$data = $dbh->query("SELECT * FROM `fees` WHERE `place_id` = '".$place_id."'");
	
		$typeer = $data->fetchAll();

		foreach ($typeer as $typer) {
			$data_['fees'][] = array($typer);
		}

		$data_ = array("results" => $data_);

		echo json_encode($data_);
	
	}

	/*
	* function to retreive route from google API on a 15miles radius from user's current position
	* @param latitude (float), longitude (float)
	* @return send json formated string
	*/

	public function route($latitude, $longitude)	{
		
		header('Content-Type: application/json');

		$json = file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=".$latitude.",".$longitude."&radius=1500&types=parking&sensor=false&key=AIzaSyCb_PM-zPaxKJUKVrhdMmW4g1GJfPjR07k");

		$json = json_decode($json, true);

		$data = array();

		

		foreach ($json['results'] as $key) {
			$data[] = array("lng" => $key['geometry']['location']['lng'], "lat" => $key['geometry']['location']['lat'], "name" => $key['name'], "google_place_id" => $key['id']);
		}

		//print_r($data);

		foreach ($data as $value) {
			if(!$this->check_gp_id($value['google_place_id']))
				$this->save_nt_exists($value['name'], $value['lat'], $value['lng'], $value['google_place_id']);
			else{}
		}

		$route_arr = array();

		$route_arr = array("results" => $this->getRoute($latitude, $longitude));

		echo json_encode($route_arr);

	}


	/*
	* function to retrieve route on a 25miles radius from database
	* @param latitude (float), longitude (float)
	* @return array
	*/

	public function getRoute($latitude, $longitude)	{

		global $dbh;

		try {

			$gr = $dbh->query("SELECT *, ( 6371 * acos( cos( radians(".$latitude.") ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(".$longitude.") ) + sin( radians(".$latitude.") ) * sin( radians( latitude ) ) ) ) AS distance FROM place HAVING distance < 15 ORDER BY distance LIMIT 0 , 20;");
			$get = $gr->fetchAll();

			foreach ($get as &$key) {
				$fees_q = $dbh->query("SELECT * FROM  `fees` WHERE `place_id` IN ('".$key['id']."') ");
				$key['fees'] = $fees_q->fetchAll();
			}

				return $get;

				
			} catch (Exception $e) {
				echo $e->getMessage();
		}
	}


	/*
	* functon to save parking places that does not exist in database
	* @param name (string), latitude (float), longitude (float), google_place_id (string)
	* @return boolean
	*/

	public function save_nt_exists($name, $latitude, $longitude, $google_place_id) {

		global $dbh;
		
		//echo"here";

		if(isset($name) && isset($latitude) && isset($longitude))	{
				$dbh->query("INSERT INTO `place` (`id` ,`latitude` ,`longitude` ,`name` ,`google_place_id`) VALUES (NULL ,  '".$latitude."',  '".$longitude."',  '".$name."',  '".$google_place_id."');");
				return true;
			}
		else{
			return false;
		}
	}


	/*
	* function to delete a parking place from the database
	* @param parking id
	* @return json
	*/

	public function delete($id) 	{

		header('Content-Type: application/json');

		global $dbh;

		if($this->is_valid_id($id))	{

			$dbh->query("DELETE FROM `place` WHERE `id` = '$id'");
		
			echo json_encode(array("status" => 1, "message" => 'Success data deleted'));
		}
		else 	{
			echo json_encode(array("status" => 0, "message" => "Error invalid id"));
		}
	}


	/*
	* function to update fee table
	* @param fee id (int), place_id (int), name (string), type(string), fee (decimal [13,2] )
	* return void;
	*/
	public function update($fees, $place_id, $name) 	{

		header('Content-Type: application/json');
		
		global $dbh;

		if($this->is_valid_id($place_id, 'place'))	{
			$temp = $dbh->query("SELECT * FROM `place` WHERE `id` = '$place_id'");
			$temp_row = $temp->fetch();
			//print_r($temp_row);
			if(empty($name)){
				$name = $temp_row['name'];
			}

			$dbh->query("UPDATE `place` SET `name` = '$name' WHERE `id` = '$place_id'");
		}

		foreach ($fees as $fee_) {
			$fee_id = $fee_['id']; 
			$fee_type = $fee_['type']; 
			$fee_fee = $fee_['fee'];  
			$temp = $dbh->query("SELECT * FROM `fees` WHERE `id` = '".$fee_id."'");
			$temp_row_ = $temp->fetch();

			$dbh->query("UPDATE `fees` SET `fee`='".$fee_fee."', `type`= '".$fee_type."' WHERE `id` = '".$fee_id."'");

		}

		
		$temp = $dbh->query("SELECT * FROM `place` WHERE `id` = '$place_id'");
		$temp_row = $temp->fetch();

		foreach ($fees as $fee_ ) {
			$fees_key[] = $fee_['id'];
		}

		$fees_key_arr = implode("','", $fees_key);

		$qr = $dbh->query("SELECT * FROM `fees` WHERE `id` IN('".$fees_key_arr."')");

		$qr_ = $qr->fetchAll();

		$temp_row['fees'] = $qr_; 

		
		//print_r($qr_);

		$temp_row = array("results" => $temp_row);
		
		//print_r($temp_row);
		echo json_encode($temp_row);

	}


	/*
	* function checks if id is valid
	* @param id (int), tb_name (string)
	* @return boolean
	*/
	private function is_valid_id($id, $tb_name) 	{
		global $dbh;
		$get_ = $dbh->query("SELECT * FROM ".$tb_name." WHERE `id` = $id");
		$res = $get_->fetch();
		if($res)
		{
			return true;
		}
		return false;

	}

	/*
	* function that checks if a google place id currently exists
	* @param string 
	* @return boolean
	*/
	private function check_gp_id($google_place_id) 	{
		global $dbh;

		try{
				$quer = $dbh->query("SELECT COUNT(google_place_id) AS num FROM `place` WHERE `google_place_id` = '$google_place_id'");
				$res = $quer->fetch();
				if($res[0]['num'] > 0){
					return true;
				}
		}
		catch(Exception $e){
			echo $e->getMessage();
		}
		return false;
	}
}