<?php
include_once('../config/class.database.php');
	
class users{
	public $link;
	public $database_name;
	public $base_url;
	//Connect to database
	function __construct(){
		$db_connection = new dbConnection();
		$this->database_name = $db_connection->database();
		$this->base_url = $db_connection->base_url();
		$this->link = $db_connection->connect();
		return $this->link;
	}
	
	public function index(){

	}
	
	/**
	*Login validation 
	*Requires username and password and returns true if valid
	*/
	function can_log_in($username, $password) {
		$query = $this->link->prepare("SELECT `password`, `salt` FROM `users` WHERE `username` = :username");
		$query->bindParam(':username',$username);
		$query->execute();
		if ($query->rowCount () > 0) {
			//Check password with bycrpt and salt
			$result = $query->fetch();
			$password1 = $result['password'];
			$salt = $result['salt'];
			$hash = crypt($password, '$2y$12$' . $salt);
			if($password1 == crypt($password, $hash)){
				return true;
			}
			return false;
		} else {
			return false;
		}
	}
	
	//Validates username if valid and does not exist in database 
	function username_validate($username) {
		$query = $this->link->prepare( "SELECT * FROM `users` WHERE `username` = :username");
		$query->bindParam(':username',$username);
		$query->execute();
		if ($query->rowCount() > 0) {
			return false;
		}
		// each array entry is an special char allowed
		// besides the ones from ctype_alnum
		$allowed = array(".", "-", "_");
		//Check if it is a valid username
		  if ( ctype_alnum( str_replace($allowed, '', $username ) ) ) {
			return true;
		} 
		return false;
	}
	
	//Check if email exist for validation
	function email_validate($email) {
		$query = $this->link->prepare( "SELECT * FROM `users` WHERE `email` = :email");
		$query->bindParam(':email',$email);
		$query->execute();
		if ($query->rowCount() > 0) {
			return false;
		}
		//Check if it is a valid email
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		return true;
	}
	
	/**
	*Add new user to database
	*Parameter: data array
	*contains: name, username, password, email and phone
	*Returns array of success status and user id
	*/
	function add_user($data) {
		$date_created = date ( 'd/m/Y H:i' );
		$date_modified = date ( 'd/m/Y H:i' );
		// salt for bcrypt needs to be 22 base64 characters (but just [./0-9A-Za-z]), see http://php.net/crypt
		$salt = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
		$hash = crypt($data['password'], '$2y$12$' . $salt);
		//Perform Query 
		$query = $this->link->prepare( "INSERT INTO `users` (`name`,`username`,`password`,`salt`,`email`,
			`phone`,`date_created`,`date_modified`) VALUES (?,?,?,?,?,?,?,?)" );
		$query->execute($data['name'],$data['username'],$hash,$salt,$data['email'],$data['phone'],
			$date_created,$date_modified);
		if ($query->rowCount () > 0) {
			return array("success"=>true,
				"id"=>$this->link->lastInsertId());
		}
		return array("success"=>false,
		"id"=>0);
	}
	
	/**
	*Get user data from database
	*Requires user id or username
	*/
	function get_user($id='',$username='') {
		$query = $this->link->prepare("SELECT * FROM users WHERE `id` = :id OR `username` = :username");
		$query->bindParam(':id',$id);
		$query->bindParam(':username',$username);
		$query->execute();
		if ($query->rowCount () > 0) {
			return $query->fetch();
		}else{
			
		}
		return false;
	}
	
	/**
	*Update/Change user information
	*This fucntion can be used to change any data on the user profile
	*expect for password. (See 'change_password' function to update password)
	*It takes in the user id as its first parameter
	*Then it takes in an array of key and values of columns that needs to be changed
	*i.e array('name'=>$name)
	*Returns true if successful
	*/
	function update_user($id, $data){
		$str = "UPDATE `users` SET";
		$values = array();
		//Build array string 
		foreach($data as $name => $value){
			$str .= ' '.$name.' = :'.$name.','; // the :$name part is the placeholder, e.g. :phone
			$values[':'.$name] = $value; // save the placeholder
		}
		$str = substr($str, 0, -1)." WHERE `id` = '$id';"; // remove last , and add a ;
		//Perform query
		$query = $this->dbh->prepare($str);
		$query->execute($values); // bind placeholder array to the query and execute everything
		if($query->rowCount() > 0){
			return true;
		}
		return false;
	}
	
	/**
	*Upload/Change user profile picture to directory
	*Takes in file and user id and image file
	*Returns true if success
	*/
	function upload_picture($id, $image){
		//upload image thumb if it exist 
		if($image!=null){
			$temp = explode(".",$image["name"]);
			$imagepath = "wms/images/users/".$id . '.' .end($temp);
			$file = $image;
			if(move_uploaded_file($image['tmp_name'],$_SERVER['DOCUMENT_ROOT'].$imagepath)) {
				$query = $this->link->prepare("UPDATE `users` 
				SET `icon_path`=?  WHERE `id`='$id'" );
				$values = array($imagepath);
				$query->execute($values);
				if($query->rowCount() > 0){
					return true;
				}
			} 
		}
		return false;
	}
	
	
	/**
	*Change user password
	*Requires user id, new password and old password
	*Returns true if successful 
	*/
	function change_password($id, $new_password, $password){
		$date = date ( 'd/m/Y H:i' );
		//Get hashed password from database
		$query = $this->link->prepare("SELECT `password`, `salt` FROM `users` WHERE `id` = :id");
		$query->bindParam(':id',$id);
		$query->execute();
		//Change password
		if ($query->rowCount () > 0) {
			//Check password with bycrpt and salt
			$result = $query->fetch();
			$password1 = $result['password'];
			$salt = $result['salt'];
			$hash = crypt($password, '$2y$12$' . $salt);
			if($password1 == crypt($password,$hash)){
				$hash = crypt($new_password, '$2y$12$' . $salt);
				//Perform query 
				$query = $this->link->prepare("UPDATE `users` SET `password`=?, `date_modified`=? WHERE `id` = '$id'");
				$values = array($hash,$date);
				$query->execute($values);
				if ($query->rowCount () > 0) {
					return true;
				}
				return false;
			}
			return false;
		} else {
			return false;
		}
		
	}
	
	/**
	*For password recovery, an ID needs to be sent to user's email
	*This recovery id will be used to create a new password for user
	*Below are the functions required to perform the action
	*/
	
	/*
	*This function sends a recovery id to user's email address
	*It takes the user email as a parameter
	*returns true if action is success performed
	*/
	function recovery_action($email){
		//Initailize return data	
		$data = array('success'=>false);
		//Get user's name and id
		$query = $this->link->prepare("SELECT `name`, `username`, `id` FROM `users` WHERE `email` = :email");
		$query->bindParam(':email',$email);
		$query->execute();
		//If email if valid, fetch user data
		if($query->rowCount() > 0){
			$result = $query->fetch();
			$name = $result['name'];
			$username = $result['username'];
			$id = $result['id'];
			//Check database for existing password recovery/link id
			$query = $this->link->prepare("SELECT * FROM `forgot_password` WHERE `user_id` = :id");
			$query->bindParam(':id',$id);
			$query->execute();
			$link_id = $this->generateRandomString(12);
			$data['email'] = $email;
			$data['name'] = $name;
			$data['id'] = $id;
			$data['link_id'] = $link_id;
			//If there is a link then reset
			if($query->rowCount() > 0){
				$query = $this->link->query("UPDATE `forgot_password` SET `link_id` = '$link_id' 
					WHERE `user_id` = '$id'");
				if($query->rowCount() > 0){
					$data['success'] = true;
					//Send Recovery Email
					if($this->send_recovery_id($data)){
						return true;
					}
				}
			}else{
				//Create a new link
				$query = $this->link->prepare('INSERT INTO `forgot_password` (`user_id`,`link_id`) 
					VALUES (?,?)');
				$values = array($id, $link_id);
				$query->execute($values);
				if($query->rowCount() > 0){
					$data['success'] = true;
					//Send Recovery Email
					if($this->send_recovery_id($data)){
						return true;
					}
				}
			}
		}
		return false;
	}
	
	/**Validates recovery/link id 
	*This fucntion is performed to ensure the provided user id 
	*matches the recovery/link id before password reset
	*/
	function recover_valilidate($user_id,$link_id){
		$query = $this->link->prepare("SELECT * FROM `forgot_password` WHERE 
			`user_id` = :user_id AND `link_id` = :link_id");
		$values = array(':user_id'=>$user_id,':link_id'=>$link_id);
		$query->execute($values);
		if ($query->rowCount () > 0) {
			return true;
		}
		return false;
	}
	
	/**
	*Takes in user id, new password, and recovery id/ link id
	*/
	function recover_update_password($email, $new_password, $link_id){
		$date = date ( 'd/m/Y H:i' );
		//Get user id from database using recovery/link id
		/*
		$query = $this->link->prepare("SELECT `user_id`, `link_id` FROM `forgot_password` WHERE 
			`user_id` = :user_id AND `link_id` = :link_id");
		$values = array(':user_id'=>$user_id,':link_id'=>$link_id);
		$query->execute($values);
		*/
		$data = array('success'=>false);
		//Get user id
		$query  = $this->link->prepare("SELECT `id` FROM `users` WHERE `email` = :email");
		$query->bindParam(':email',$email);
		$query->execute();
		$id = 0;
		if($query->rowCount() > 0){
			$result = $query->fetch();
			$id = $result['id'];
			if(!$this->recover_valilidate($id,$link_id)){
				return $data;
			}
		}else{
			return $data;
		}
		//Reset password
		if ($query->rowCount () > 0) {
			$salt = substr(strtr(base64_encode(openssl_random_pseudo_bytes(22)), '+', '.'), 0, 22);
			$hash = crypt($new_password, '$2y$12$' . $salt);
			//Change/Update hashed user password password 
			$query = $this->link->prepare("UPDATE `users` SET `password`=?, `salt`=?, `date_modified`=? WHERE `id` = '$id'");
			$values = array($hash,$salt,$date);
			$query->execute($values);
			if ($query->rowCount () > 0) {
				$val = $this->remove_link_id($id,$link_id);
				$data['success'] = true;
				$data['id'] = $id;
				return $data;
			}
		}
		return $data;
	}
	
	//Removes recovery/link id from database after password has been reset
	private function remove_link_id($user_id,$link_id){
		$query = $this->link->query("DELETE FROM `forgot_password` WHERE `user_id` = '$link_id'");
		if ($query->rowCount () > 0) {
			return true;
		}
		return false;
	}
	
	/**
	* Send email to user to recover password
	* requires array data of name, username,
	* email, and recovery/link id
	*/
	function send_recovery_id($data){
		//Define user parameters
		$name = $data['name'];
		$id = $data['id'];
		$link_id = $data['link_id'];
		$email = $data['email'];
		//Define email parameters
		$to  = $email;
		$subject = 'Password Recovery';
		$headers = 'From: services@whosmyserver.com' . "\r\n" .
			'Reply-To: services@whosmyserver.com' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		$message = "Hi ".$name."\n\n".
		"Use the link below to reset your password\n".
		$this->base_url."main/recover_password/".$id."/".$link_id."\n".
		"or enter this id on your mobile app: ".$link_id;
		if(mail($to, $subject, $message, $headers)){
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