<?php
session_start();

require 'config.php';

// Connect to the database
function db_connect($use_remote = "") {
    if ($use_remote == "dev") {
        $db_host = DB_LOCAL_HOST;
        $db_username = DB_LOCAL_USER;
        $db_pass = DB_LOCAL_PASS;
        $db_name = DB_LOCAL_NAME;
    } else {
        $db_host = DB_REMOTE_HOST;
        $db_username = DB_REMOTE_USER;
        $db_pass = DB_REMOTE_PASS;
        $db_name = DB_REMOTE_NAME;
    }

    $conn = @mysql_connect($db_host, $db_username, $db_pass);

    if (!$conn) {
        die("Could not connect to MySQL: " . mysql_error());
    }
    
    $db_selected = @mysql_select_db($db_name, $conn);
    
    if (!$db_selected) {
        die("No database: " . mysql_error());
    }
    echo "Connected successfully to the database <br>";

    $_SESSION['con'] = $conn;

    return $conn;
}

// Execute a query
function db_query($query) {
    $conn = $_SESSION['con'];
    $result = mysql_query($query, $conn);

    if (!$result) {
        die("Query error: " . mysql_error($conn));
    }

    return $result;
}

// Fetch all results from a SELECT query
function db_fetch_all($result) {
    $rows = array();

    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
    } else {
        echo "No rows found.<br>";
    }

    return $rows;
}

// Insert a record into a table
function db_insert($table, $data) {
    $columns = implode(", ", array_keys($data));
    $values = implode("', '", array_map('mysql_real_escape_string', array_values($data)));

    $escape_table = mysql_real_escape_string($table);
    $query = sprintf("INSERT INTO '%s' ('%s') VALUES ('%s')",$escape_data, $columns, $values );

    return db_query($query);
}

// Update a record in a table
function db_update($table, $data, $where) {
    $set = "";
    foreach ($data as $column => $value) {
        $set .= "$column = '" . mysql_real_escape_string($value) . "', ";
    }
    $set = rtrim($set, ", ");
    $escape_table = mysql_real_escape_string($table);
    $escape_where = mysql_real_escape_string($where);
    $escape_data = mysql_real_escape_string($data);

    $query = sprintf("UPDATE '%s' SET '%s' WHERE '%s'", $escape_table, $escape_data, $escape_where);

    return db_query($query);
}

// Delete a record from a table
function db_delete($table, $where) {
    $escape_table = mysql_real_escape_string($table);
    $escape_where = mysql_real_escape_string($where);
    $query = sprintf("DELETE FROM '%s' WHERE '%s'", $escape_table, $escape_where);

    return db_query($query);
}

// Fetch records from a specific table
function db_fetch_table_records($table) {
    $escape_table = mysql_real_escape_string($table);
    $query = sprintf("SELECT * FROM `%s`", $escape_table);
    $result = db_query($query);
    
    return db_fetch_all($result);
}
function db_fetch_records_after_time($table, $time) {
    $escape_table = mysql_real_escape_string($table);
    $escape_time = mysql_real_escape_string($time);

    $query = sprintf("SELECT * FROM `%s` WHERE time > '%s'", $escape_table, $escape_time);
    $result = db_query($query);
    
    return db_fetch_all($result);
}

function db_fetch_last_ship_record($loopname) {
    $escape_loopname = mysql_real_escape_string($loopname);

    $query = sprintf("SELECT MAX(time) as last_date FROM standar_ships_records WHERE Loopname = '%s'",$escape_loopname);
    $result = db_query($query);
    if(!$result){
        $lastDate = null; 
    }else{
        $lastDateRow = mysql_fetch_assoc($result);
        $lastDate = $lastDateRow['last_date'] ;
    }
    
    return $lastDate;
}
function db_fetch_last_four_ship_records($loopname) {
    $escape_loopname = mysql_real_escape_string($loopname);

    $query = sprintf("SELECT * FROM standar_ships_records WHERE Loopname = '%s' ORDER BY time DESC LIMIT 3;",$escape_loopname);
    $result = db_query($query);
    
    
    return db_fetch_all($result);
}


// Close the connection
function db_close() {
    $conn = $_SESSION['con'];

    mysql_close($conn);
    unset($_SESSION['con']);
}
?>
