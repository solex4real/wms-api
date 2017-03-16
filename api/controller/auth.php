<?php
include_once('../model/auth.php');


 /**
 *Validate request from client
 */
 if(isset($_GET['method'])&&!empty($_GET['method'])){
		//Check if method exist
		if(function_exists($_GET['method'])){
			//Validate token
			$auth = new Auth();
			$token = $_REQUEST['token'];
			$result = $auth->validate_token($token);
			if($result['success']){
				$GLOBALS['user_id'] = $result['user_id'];
				$_GET['method']();
			}else{
				echo "You do not have permission";
			}
		}else{
			echo "Method Does not Exist";
		}
 }else{
		
}
	

	
//Test token for valid key
function test(){
	echo $GLOBALS['user_id'];
}
	
	
?>