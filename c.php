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
			if(!$this->is_connected){
				$this->link = @mysql_connect($this->server, $this->user, $this->password);
				if(!$this->link){
					return 'CANNOT connect: '.mysql_error();
				}
				else{
					$this->is_connected = true;
					mysql_set_charset("utf8");
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
		
		function selectTable($table, $start, $limit){
			$query = "SELECT * FROM `$table` LIMIT $start, $limit";
			$result_id = mysql_query($query);
			
			for($i=0;$row=mysql_fetch_row($result_id);$i++)
				$result[$i] = $row;
			
			return $result;
		}
		
		function fieldList($table){
            $result_id = mysql_list_fields($this->database, $table);
			
			$numOfCols = mysql_num_fields($result_id); 
			for($i=0;$i<$numOfCols;$i++)
				$result[$i] = mysql_field_name($result_id, $i);

			return $result;
		}
			
		function numRows($table){
		    $result_id = mysql_query("SELECT COUNT(*) as total_count FROM `$table`");

		    $ret = mysql_fetch_object($result_id);
            return $ret->total_count;
	    }
	    
	    function updateElement($table, $headerJSON, $oldValuesJSON, $newValuesJSON){
	        $header = json_decode($headerJSON);
	        $oldValues = json_decode($oldValuesJSON);
	        $newValues = json_decode($newValuesJSON);
	        $query = "UPDATE `$table` SET ";
	        
	        for($i = 0;$i < sizeof($header); $i++){
	            $query .= "`".$header[$i]."` = '".$newValues[$i]."' ";
	            if($i+1 < sizeof($header))
	                $query .= ", ";                
	        }
	        $query .= " WHERE ( ";
	        for($i = 0;$i < sizeof($header); $i++){
	            $query .= "`".$header[$i]."` = '".$oldValues[$i]."' ";
	            if($i+1 < sizeof($header))
	                $query .= " && ";
	        }
	        $query .= ");";
	        
	        $result_id = mysql_query($query);
	        
	        if( !$result_id ){
	            echo mysql_error();
	        }
	        
	        $info = mysql_info();
	        echo  $info ;
	    }
	    
	    function addElement($table, $headerJSON, $valuesJSON){
	        $header = json_decode($headerJSON);
	        $values = json_decode($valuesJSON);
	        $query = "INSERT INTO `$table` (";
	        
	        for($i = 0;$i < sizeof($header); $i++){
	            $query .= "`".$header[$i]."`";
	            if($i+1 < sizeof($header))
	                $query .= ", ";                
	        }
	        $query .= ") VALUES ( ";
	        for($i = 0;$i < sizeof($header); $i++){
	            $query .= "'".$values[$i]."' ";
	            if($i+1 < sizeof($header))
	                $query .= " , ";
	        }
	        $query .= ");";
	        
	        $result_id = mysql_query($query);
	        
	        if( !$result_id ){
	            echo mysql_error();
	        } else {
	            echo OK;
	        }
	        
	        $info = mysql_info();
	        echo  $info ;
	    }
	}

	$action 	= $_GET['a'];
	
	$server		= 'localhost';
	$user 		= $_GET['u'];
	$password	= $_GET['p'];
	
	$database	= $_GET['d'];
	$table		= $_GET['t'];
	$start      = $_GET['s'];
	$limit      = $_GET['l'];
	$query 		= $_GET['q'];
	$header     = $_GET['h'];
	$values     = $_GET['v'];
	$oldValues  = $_GET['o'];
	
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
			echo json_encode($list);
			break;
		case "tablelist":
			$con->connect();
			echo json_encode($con->tableList($database));
			break;
		case "fieldlist":
		    $con->connect();
		    $con->selectDb($database);
		    echo json_encode($con->fieldList($table));
		    break;
	    case "numrows":
	        $con->connect();
	        $con->selectDb($database);
	        echo $con->numRows($table);
	        break;
		case "query":
			$con->connect();
			$con->query($query);
			break;
		case "getrows":
			$con->connect();
			$con->selectDb($database);
			echo json_encode($con->selectTable($table, $start, $limit));
			break;
		case "updateelement":
		    $con->connect();
			$con->selectDb($database);
		    $con->updateElement($table, $header, $oldValues, $values);
    		break;
		case "addelement":
		    $con->connect();
		    $con->selectDb($database);
		    $con->addElement($table, $header, $values);
		    break;
	}
	
	$con->disconnect();
?>
