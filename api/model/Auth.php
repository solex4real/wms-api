<?php
include_once('../config/class.database.php');
class Auth{
	public $link;
	public $database_name;
	//Connect to database to access authentication data
	function __construct(){
		$db_connection = new dbConnection();
		$this->database_name = $db_connection->database();
		$this->link = $db_connection->connect();
		return $this->link;
	}
	/**
	*Validates token/key if it exist
	*Return: user id that corresponds to token
	*/
	public function validate_token($key){
		$query = $this->link->prepare("SELECT * FROM `auth` WHERE `key` = :key");
		$values = array(':key'=>$key);
		$query->execute($values);
		//Check if key exist in database
		if($query->rowCount() > 0){
			$result = $query->fetch();
			return array('success'=>true,'user_id'=>$result['user_id']);
		}
		return array('success'=>false,'user_id'=>0);
	}
	
	/**
	*Validates token/key for user id if it exist 
	*Return: true or false
	*/
	public function validate_user_token($key, $user_id){
		$query = $this->link->prepare("SELECT * FROM `auth` WHERE `key` = :key AND `user_id` = :user_id");
		$values = array(':user_id'=>$user_id,':key'=>$key);
		$query->execute($values);
		//Check if key exist in database
		if($query->rowCount() > 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 *Creates key/token
	 *Requires user id
	 */
	public function create_auth($user_id, $permission=667){
		$key = $this->generate_key();
		$query = $this->link->prepare("INSERT INTO `auth` (`key`,`permission`,`user_id`) VALUES (?, ?, ?)");
		$values = array($key, $permission, $user_id);
		$query->execute($values);
		if($query->rowCount() > 0){
			return array('success'=>true,'key'=>$key);
		}else{
			return array('success'=>false,'key'=>"");
		}
	}
	
	//Get user credentials based on authentication key/token
	public function get_credentials($key){
		$query = $this->link->prepare("SELECT * FROM `auth` WHERE `key` = :key");
		$query->bindParam(':key',$key);
		$query->execute();
		$result = $query->fetch();
		return $result;
	}
	
	/**
	*Generate random values to create authentication key
	*/
	public function generate_key(){
		$key = $this->generateRandomString(12);
		$query = $this->link->query("SELECT * FROM `auth` WHERE `key` = '$key'");
		//Check if key exist in database
		if($query->rowCount() == 0){
			return $key;
		}else{
			$this->generate_key();
		}
	}
	//Delete token/key
	public function remove_key($key){
		$query = $this->link->prepare("DELETE FROM `auth` WHERE `key` =  :key");
		$query->bindParam(':key',$key);
		$query->execute();
		if($query->rowCount() > 0){
			return true;
		}
		return false;
	}
	
	//Radmon String generator
	public function generateRandomString($length = 10) {
		return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
		ceil($length/strlen($x)) )),1,$length);
	}

}


?>