<?php
session_start();

require 'config.php';

// Connect to the database
// Function to connect to the database
function db_connect($log, $use_remote = "") {
    // Determine database connection parameters based on environment
    $db_host = $use_remote == "dev" ? DB_LOCAL_HOST : DB_REMOTE_HOST;
    $db_username = $use_remote == "dev" ? DB_LOCAL_USER : DB_REMOTE_USER;
    $db_pass = $use_remote == "dev" ? DB_LOCAL_PASS : DB_REMOTE_PASS;
    $db_name = $use_remote == "dev" ? DB_LOCAL_NAME : DB_REMOTE_NAME;
    
    echo "User " . $db_username;


    try {
        // Try to connect to the database
        $conn = @mysql_connect($db_host, $db_username, $db_pass);
        if (!$conn) {
            throw new Exception("Could not connect to MySQL: " . mysql_error());
        }

        // Select the database
        $db_selected = @mysql_select_db($db_name, $conn);
        if (!$db_selected) {
            throw new Exception("No database: " . mysql_error());
        }

        echo "Connected successfully to the database <br>";
        $_SESSION['con'] = $conn;
        return $conn;
    } catch (Exception $e) {
        // Log error and stop script execution
        $log->logInfo($e->getMessage());
        die($e->getMessage());
    }
}

// Function to execute queries
function db_query($log, $query) {
    // Intenta ejecutar la consulta
    try {
        $result = mysql_query($query);
        if (!$result) {
            throw new Exception(mysql_error());
        }
        return $result;
    } catch (Exception $e) {
        // Registra el error y detén la ejecución del script
        $log->logInfo("Query error: " . $query . " " . $e->getMessage());
        die("Query error: " . $e->getMessage());
    }
}


// Function to fetch all results from a SELECT query
function db_fetch_all($result) {
    $rows = [];
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Function to insert a record into a table
function db_insert($log, $table, $data) {
    $columns = implode(", ", array_keys($data));
    $values = implode("', '", array_map('mysql_real_escape_string', array_values($data)));

    $query = sprintf("INSERT INTO `%s` (`%s`) VALUES ('%s')",
        mysql_real_escape_string($table),
        $columns,
        $values
    );

    return db_query($log, $query);
}

// Function to update a record in a table
function db_update($log, $table, $data, $where) {
    $set = "";
    foreach ($data as $column => $value) {
        $set .= sprintf("%s = '%s', ", mysql_real_escape_string($column), mysql_real_escape_string($value));
    }
    $set = rtrim($set, ", ");

    $query = sprintf("UPDATE `%s` SET %s WHERE %s",
        mysql_real_escape_string($table),
        $set,
        $where
    );

    return db_query($log, $query);
}

// Function to delete a record from a table
function db_delete($log, $table, $where) {
    $query = sprintf("DELETE FROM `%s` WHERE %s",
        mysql_real_escape_string($table),
        $where
    );

    return db_query($log, $query);
}

// Function to fetch records from a specific table
function db_fetch_utility_rate($log, $utility) {
    $query = sprintf("SELECT * FROM `%s` ORDER BY Rate_Date_Start DESC LIMIT 1;",
        mysql_real_escape_string($utility)
    );
    $result = db_query($log, $query);
    return db_fetch_all($result);
}

// Function to fetch records from a specific table
function db_fetch_table_records($log, $table) {
    $query = sprintf("SELECT * FROM ( 
                SELECT * FROM `%s` ORDER BY time DESC LIMIT 5000
                ) sub ORDER BY time ASC;",
        mysql_real_escape_string($table)
    );
    $result = db_query($log, $query);
    return db_fetch_all($result);
}

// Function to fetch records from a table after a specific time
function db_fetch_records_after_time($log, $table, $time) {
    $query = sprintf("SELECT * FROM (
                SELECT * FROM `%s` WHERE time > '%s'
                ) sub ORDER BY time ASC;",
        mysql_real_escape_string($table),
        mysql_real_escape_string($time)
    );
    $result = db_query($log, $query);
    return db_fetch_all($result);
}

//Function to fetch the last record of a specific loop name
function db_fetch_last_ship_record($log, $loopname) {
    $query = sprintf("SELECT MAX(time) as last_date FROM Standard_ship_records WHERE Loopname = '%s';",
        mysql_real_escape_string($loopname)
    );
    $result = db_query($log, $query);
    $lastDate = null;
    if ($result) {
        $lastDateRow = mysql_fetch_assoc($result);
        $lastDate = isset($lastDateRow['last_date']) ? $lastDateRow['last_date'] : null;
    }
    return $lastDate;
}

// Function to fetch the last four records of a specific loop name
function db_fetch_last_three_ship_records($log, $loopname) {

    $query = sprintf("SELECT * FROM (
        SELECT * FROM Standard_ship_records WHERE Loopname = '%s' ORDER BY time DESC LIMIT 3
        ) sub ORDER BY time ASC;",
        mysql_real_escape_string($loopname)
    );
    $result = db_query($log, $query);
    return db_fetch_all($result);
}



// Function to insert standard records into the database
function db_insert_standar_records($log, $shipRecords) {
    $query_insert = "INSERT INTO Standard_ship_records (
        time, time_zone, error, energy_consumption, real_power, reactive_power,
        apparent_power, power_factor, current, real_power_phase_a, real_power_phase_b, real_power_phase_c,
        power_factor_phase_a, power_factor_phase_b, power_factor_phase_c, voltage_phase_ab, voltage_phase_bc,
        voltage_phase_ac, voltage_phase_an, voltage_phase_bn, voltage_phase_cn, current_phase_a, current_phase_b,
        current_phase_c, average_demand, maximum_demand, peak_kw, peak_kwh, off_peak_kw, off_peak_kwh, 
        cost_kw, cost_kwh, off_cost_kw, off_cost_kwh, accumulation,loopname
    ) VALUES ";
    
    
    $values = [];
    foreach ($shipRecords as $record) {
        $values[] = $record->getData();

    }
    $query = $query_insert . implode(', ', $values);
    $errors = 0;

    try {
        db_query($log, $query);
    } catch (Exception $e) {
        // Log batch insert error
        $log->logInfo("Error in batch insert: " . $e->getMessage());
        // Try individual inserts if batch insert fails
        foreach ($shipRecords as $record) {
            $query = $query_insert . $record->getData();
            try {
                db_query($log, $query);
            } catch (Exception $e) {
                $errors++;
                $log->logInfo("Error in individual insert: " . $e->getMessage() . ' - Data: ' . json_encode($record));
            }
        }
    }

    return $errors;
}
function time_zone_check($ship)
{
	// query for finding the correct
	// utility for a particular ship

	$sql = "SELECT timezoneaquisuite FROM Aquisuite_List WHERE aquisuitetablename='$ship'";
	$RESULT = mysql_query($sql);

	if(!$RESULT) {
		MySqlFailure("timeZone check failed");
	}

	$row_time=mysql_fetch_array($RESULT);
	$timeZone=$row_time=[0];

	return $timeZone[0];
}

// Function to close the connection
function db_close() {
    $conn = isset($_SESSION['con'])? $_SESSION['con'] : null;
    if ($conn) {
        mysql_close($conn);
        unset($_SESSION['con']);
    }
}
?>


