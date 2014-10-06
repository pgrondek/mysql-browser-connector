<?php

class connector
{
    public $action;

    public $server;
    public $user;
    public $password;

    private $database;

    private $link;
    private $is_connected;

    function connector($user, $password, $server)
    {
        $this->user = $user;
        $this->password = $password;
        $this->server = $server;
        $this->is_connected = false;
    }

    function connect()
    {
        if (!$this->is_connected) {
            $this->link = @mysql_connect($this->server, $this->user, $this->password);
            if (!$this->link) {
                return 'CANNOT connect: ' . mysql_error();
            } else {
                $this->is_connected = true;
                mysql_set_charset("utf8");
                return 'OK';
            }
        }
    }

    function selectDb($database)
    {
        $this->database = $database;

        mysql_select_db($this->database);
    }

    function dbList()
    {
        $db_list = mysql_list_dbs($this->link);

        for ($i = 0; $row = mysql_fetch_object($db_list); $i++)
            $list[$i] = $row->Database;

        return $list;
    }

    function tableList($database)
    {
        $result = mysql_list_tables($database);
        $num_rows = mysql_num_rows($result);

        for ($i = 0; $i < $num_rows; $i++)
            $list[$i] = mysql_tablename($result, $i);

        return $list;
    }

    function query($query)
    {
        $result_id = mysql_query($query);

        if (!$result_id) {
            echo mysql_error() . "\n";
        } else if ($info = mysql_info()) {
            echo $info;
        } else {
            echo OK . "\n";


            $numOfCols = mysql_num_fields($result_id);
            if ($numOfCols > 0) {
                for ($i = 0; $i < $numOfCols; $i++)
                    $field[$i] = mysql_field_name($result_id, $i);


                echo json_encode($field) . "\n";
                for ($i = 0; $row = mysql_fetch_row($result_id); $i++)
                    $result[$i] = $row;
                echo json_encode($result);
            }
        }
    }

    function disconnect()
    {
        if ($this->link)
            mysql_close($this->link);
    }

    function selectTable($table, $start, $limit)
    {
        $query = "SELECT * FROM `$table` LIMIT $start, $limit";
        $result_id = mysql_query($query);

        for ($i = 0; $row = mysql_fetch_row($result_id); $i++)
            $result[$i] = $row;

        return $result;
    }

    function fieldList($table)
    {
        $result_id = mysql_list_fields($this->database, $table);

        $numOfCols = mysql_num_fields($result_id);
        for ($i = 0; $i < $numOfCols; $i++)
            $result[$i] = mysql_field_name($result_id, $i);

        return $result;
    }

    function numRows($table)
    {
        $result_id = mysql_query("SELECT COUNT(*) as total_count FROM `$table`");

        $ret = mysql_fetch_object($result_id);
        return $ret->total_count;
    }

    function updateElement($table, $headerJSON, $oldValuesJSON, $newValuesJSON)
    {
        $header = json_decode($headerJSON);
        $oldValues = json_decode($oldValuesJSON);
        $newValues = json_decode($newValuesJSON);
        $query = "UPDATE `$table` SET ";

        for ($i = 0; $i < sizeof($header); $i++) {
            $query .= "`" . $header[$i] . "` = '" . $newValues[$i] . "' ";
            if ($i + 1 < sizeof($header))
                $query .= ", ";
        }
        $query .= " WHERE ( ";
        for ($i = 0; $i < sizeof($header); $i++) {
            $query .= "`" . $header[$i] . "` = '" . $oldValues[$i] . "' ";
            if ($i + 1 < sizeof($header))
                $query .= " && ";
        }
        $query .= ");";

        $result_id = mysql_query($query);

        if (!$result_id) {
            echo mysql_error();
        }

        $info = mysql_info();
        echo $info;
    }

    function addElement($table, $headerJSON, $valuesJSON)
    {
        $header = json_decode($headerJSON);
        $values = json_decode($valuesJSON);
        $query = "INSERT INTO `$table` (";

        for ($i = 0; $i < sizeof($header); $i++) {
            $query .= "`" . $header[$i] . "`";
            if ($i + 1 < sizeof($header))
                $query .= ", ";
        }
        $query .= ") VALUES ( ";
        for ($i = 0; $i < sizeof($header); $i++) {
            $query .= "'" . $values[$i] . "' ";
            if ($i + 1 < sizeof($header))
                $query .= " , ";
        }
        $query .= ");";

        $result_id = mysql_query($query);

        if (!$result_id) {
            echo mysql_error();
        } else {
            echo 'OK';
        }

        $info = mysql_info();
        echo $info;
    }

    function removeElement($table, $headerJSON, $valuesJSON)
    {
        $header = json_decode($headerJSON);
        $values = json_decode($valuesJSON);
        $query = "DELETE FROM `$table` WHERE (";

        for ($i = 0; $i < sizeof($header); $i++) {
            $query .= "`" . $header[$i] . "` = '" . $values[$i] . "' ";
            if ($i + 1 != sizeof($header))
                $query .= " AND ";
        }
        $query .= ");";

        $result_id = mysql_query($query);

        if (!$result_id) {
            echo mysql_error();
        } else {
            echo 'OK';
        }
    }
}

// DEBUG!!!
// $_POST = $_GET;

$action = $_POST['a'];

$server = 'localhost';
$user = $_POST['u'];
$password = $_POST['p'];

$database = $_POST['d'];
$table = $_POST['t'];
$start = $_POST['s'];
$limit = $_POST['l'];
$query = $_POST['q'];
$header = $_POST['h'];
$values = $_POST['v'];
$oldValues = $_POST['o'];

if (($user == null) || ($password == null) || ($action == null))
    die('Nope');


$con = new connector($user, $password, $server);

if($action=="login"){
    echo $con->connect();
} else {
    $con->connect();
    switch ($action) {
        case "dblist":
            $list = $con->dbList();
            echo json_encode($list);
            break;
        case "tablelist":
            echo json_encode($con->tableList($database));
            break;
        case "fieldlist":
            $con->selectDb($database);
            echo json_encode($con->fieldList($table));
            break;
        case "numrows":
            $con->selectDb($database);
            echo $con->numRows($table);
            break;
        case "query":
            if (isset($database))
                $con->selectDb($database);
            $con->query($query);
            break;
        case "getrows":
            $con->selectDb($database);
            echo json_encode($con->selectTable($table, $start, $limit));
            break;
        case "updateelement":
            $con->selectDb($database);
            $con->updateElement($table, $header, $oldValues, $values);
            break;
        case "addelement":
            $con->selectDb($database);
            $con->addElement($table, $header, $values);
            break;
        case "removeelement":
            $con->selectDb($database);
            $con->removeElement($table, $header, $values);
    }
}
$con->disconnect();
?>
