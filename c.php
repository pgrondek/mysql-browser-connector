<?php

	class connector{
		public $action;
		
		public $server;
		public $user;
		public $password;
		
		private $database;
		
		private $link;
		private $is_connected;
		
		function connector($user, $password, $server){
			$this->user		= $user;
			$this->password	= $password;
			$this->server	= $server;
			$this->is_connected = false;
		}
		
		function connect(){
			if(!$this->connected){
				$this->link = @mysql_connect($this->server, $this->user, $this->password);
				if(!$this->link){
					return 'CANNOT connect: '.mysql_error();
				}
				else{
					$this->is_connected = true;
					return 'OK';
				}
			}
		}
		
		function selectDb($database){
			$this->database = $database;
			
			mysql_select_db($this->database);
		}
		
		function dbList(){
			$db_list = mysql_list_dbs($this->link);
			
			for($i = 0; $row = mysql_fetch_object($db_list);$i++)
				$list[$i] = $row->Database;
			
			return $list;
		}
		
		function tableList($database){
			$result = mysql_list_tables($database);
			$num_rows = mysql_num_rows($result);
			
			for($i=0;$i<$num_rows;$i++)
				$list[$i] = mysql_tablename($result, $i);
				
			return $list;
		}
		
		function query($query){
			$result = mysql_query($query);
			return $result;
		}
		
		function disconnect(){
			if($this->link)
				mysql_close($this->link);
		}
		
		function selectTable($table){
			$query = "SELECT * FROM `$table`";
			$result_id = mysql_query($query);
			
			for($i=0;$row=mysql_fetch_row($result_id);$i++)
				$result[$i] = $row;
			
			return $result;
		}
		
		function fieldList($table){
			$query = "SELECT * FROM `$table` LIMIT 0,1";
			$result_id = mysql_query($query);
			
			$numOfCols = mysql_num_fields($result_id); 
			for($i=0;$i<$numOfCols;$i++)
				$result[$i] = mysql_field_name($result_id, $i);
			
			return $result;
		}
	}

	$action 	= $_GET['a'];
	
	$server		= 'localhost';
	$user 		= $_GET['u'];
	$password	= $_GET['p'];
	
	$database	= $_GET['d'];
	$table		= $_GET['t'];
	
	$query 		= $_GET['q'];
	
	if(($user == null) || ($password == null) || ($action == null))
		die('Nope');
		
	$con = new connector($user, $password, $server);
	
	switch ($action){
		case "login":
			echo $con->connect();
			break;
		case "dblist":
			$con->connect();
			$list = $con->dbList();
			print_r($list);
			break;
		case "tablelist":
			$con->connect();
			print_r($con->tableList($database));
			break;
		case "dblist":
			$con->connect();
			print_r($con->dbList());
			break;
		case "query":
			$con->connect();
			$con->query($query);
			break;
		case "select":
			$con->connect();
			$con->selectDb($database);
			print_r($con->fieldList($table));
			print_r($con->selectTable($table));
			break;
	}
	
	$con->disconnect();
?>