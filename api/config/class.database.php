<?php

class dbconnection{
	protected $db_conn;
	public $db_name = 'wmsdata';
	public $db_user = 'solex';
	public $db_pass = '500gram';
	public $db_host = 'localhost';
	//Base url
	public $base_url = 'http://localhost/wms-api/api';
	public $options = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	);
	//echo"test";
	function connect(){
	try{
		$this->db_conn = new PDO("mysql:host=$this->db_host;dbname=$this->db_name"
		,$this->db_user,$this->db_pass,$this->options);
		return $this->db_conn;
		}
		catch(PDOException $e){
		
		return $e->getMessage();
		}
	}
	
	function database(){
		return $this->db_name;
	}
	
	function base_url(){
		return $this->base_url;
	}
}


?>