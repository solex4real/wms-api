<?php
include_once('../model/class.users.php');
include_once('../model/auth.php');

 /**
 *Validate request from client
 */
 if(isset($_GET['method'])&&!empty($_GET['method'])){
		//Check if method exist
		if(function_exists($_GET['method'])){
			$_GET['method']();
		}else{
			echo "Method Does not Exist";
		}
 }else{
		
}

//Check is usermane exist; return true/1 if it does
function username_validate(){
	$username = $_GET['username'];
	$user = new users();
	print($user->username_validate($username));
}

//Check is usermane exist; return true/1 if it does
function email_validate(){
	$email = $_GET['email'];
	$user = new users();
	print($user->email_validate($email));
}

/**
*User login
*Requires username and password
*returns json data of success and token/key if 
*user can login
*i.e {'success':true,'key':'dgiuUjed978'}
*Key will be used instead of user_id or username and password 
*/
function login(){
	$username = $_GET['username'];
	$password = $_GET['password'];
	if(!empty($username)&&!empty($password)){
		$user = new users();
		if($user->can_log_in($username,$password)){
			$result = $user->get_user(null, $username);
			//Create new auth
			$auth = new Auth();
			$data = $auth->create_auth($result['id']);
			if($data['success']){
				$data['message'] = "Successful!";
				print(json_encode($data));
			}else{
				$data['message'] = "Failed to get token";
				print(json_encode($data));
			}
		}else{
			print(json_encode(array('success'=>false,
				'message'=>"Invalid Username and Password")));
		}
	}else{
		print(json_encode(array('success'=>false,
				'message'=>"All fields required")));
	}
}

/**
*Register user
*And return success status and token
*i.e {'success':true,'key':'dgiuUjed978'}
*Key will be used instead of user_id or username and password 
*/
function register(){
	//Request data from client
	$name = $_GET['name'];
	$username = $_GET['username'];
	$password = $_GET['password'];
	$email = $_GET['email'];
	$phone = $_GET['phone'];
	$user = new users();
	//Check if they are valid input
	if($user->username_validate($username)&&!empty($username)&&
		$user->valid_validate($email)&&!empty($email)&&
		preg_match("/^[a-zA-Z ]*$/",$name)&&!empty($name)&&
		!empty($password)&&!empty($phone)
	){
		//Add user to database
		$data  = array(
			'username'=>$username,
			'name'=>$name,
			'password'=>$password,
			'email'=>$email,
			'phone'=>$phone
		);
		$result = $user->add_user($data);
		//If successful get user token
		if($result['success']){
			$auth = new Auth();
			$data = $auth->create_auth($result['user_id']);
			if($data['success']){
				$data['message'] = "Successful!";
				print(json_encode($data));
			}else{
				$data['message'] = "Successfully Registered, but failed to get token";
				print(json_encode($data));
			}
		}
	}else{
		$data = array('success'=>false,
			'message'=>"Failed!");
		print(json_encode($data));
	}
	
}

//Upload photo
function upload_picture(){
	if (empty($_FILES['picture'])) {
	 $data = array('success'=>false,
		'message'=>"Empty File");
		print(json_encode($data));
	} else if(!@is_array(getimagesize($_FILES['picture']['tmp_name']))){
		$data = array('success'=>false,
		'message'=>"Invalid File");
		print(json_encode($data));
	}else{
		//Get user id from token
		$auth = new Auth();
		$token = $_GET['token'];
		$result = $auth->validate_token($token);
		if($result['success']){
			//Uplaod user picture if token is valid
			$user = new users();
			$result = $user->upload_picture($result['user_id'],$_FILES['picture']);
			$data = array('success'=>$result,
				'message'=>"");
			print(json_encode($data));
		}else{
			$data = array('success'=>false,
				'message'=>"Invalid token");
			print(json_encode($data));
		}
	}
}

/**
*Update user profile
*This function can be user to update user info
*Do not use function to change user password
*See (change_password function) to change user password
*Takes in json data of key and values of info that needs to be changed
*/
function update_user(){
	$data = $_GET['data'];
	$token = $_GET['token'];
	$success = false;
	if(!empty($data)&&!empty($token)){
		//Convert user json data into array
		$data = json_decode($data);
		//Get user id from token
		$auth = new Auth();
		$token = $_GET['token'];
		//Validate user
		$result = $auth->validate_token($token);
		if($result['success']){
			$id = $result['user_id'];
			$user = new users();
			//Update user data
			if($user->update_user($id, $data)){
				$success = true;
			}
		}
	}
	print($success);
}

/**
*Change user password
*Takes in old password, new password, and user token
*/
function change_password(){
	$new_password = $_GET['new_password'];
	$old_password = $_GET['old_password'];
	$token = $_GET['token'];
	$success = false;
	if(!empty($data)&&!empty($token)){
		//Get user id from token
		$auth = new Auth();
		$token = $_GET['token'];
		//Validate user
		$result = $auth->validate_token($token);
		if($result['success']){
			$id = $result['user_id'];
			$user = new users();
			//Change user password
			if($user->change_password($id, $new_password, $password)){
				$success = true;
			}
		}
	}
	print($success);
}


/**
*Forgot password action
*requires user email address
*returns json of success
*/
function forgot_password(){
	$email = $_GET['email'];
	$success = false;
	if(!empty($email)){
		$user = new users();
		if($user->recovery_action($email)){
			$success = true;
		}
	}
	print(json_encode(array('success'=>$success)));
}

/**
*Password Reset
*Requires new password and recovery/link id
*If successful return
*/
function password_reset(){
	$new_password = $_GET['new_password'];
	$link_id = $_GET['link_id'];
	$email = $_GET['email'];
	$data = array('success'=>false);
	if(!empty($new_password)&&!empty($link_id)&&!empty($email)){
		$user = new users();
		$result = $user->recover_update_password($email, $new_password, $link_id);
		if($result['success']){
			//Create new auth
			$auth = new Auth();
			$data = $auth->create_auth($result['id']);
			if($data['success']){
				$data['message'] = "Successful!";
			}
		}
	}
	print(json_encode($data));
}

/**
*Logout function
*Takes in user token/key and removes it from database
*prints 1/true if successful
*/
function logout(){
	$token = $_GET['token'];
	$auth = new Auth();
	if($auth->remove_key($key)){
		print(true);
	}
	print(false);
}

//Test function
function test(){
	print("Test");
}


?>