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

        $log->logInfo( "Connected successfully to the database");
        $_SESSION['con'] = $conn;
        return $conn;
    } catch (Exception $e) {
        // Log error and stop script execution
        $log->logError($e->getMessage());
        die($e->getMessage());
    }
}

function db_fetch_all($result) {
    $rows = [];
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
// Function to execute queries
function db_query($log, $query) {
    try {
        $result = mysql_query($query);
        if (!$result) {
            throw new Exception(mysql_error());
        }
        return $result;
    } catch (Exception $e) {
        $log->logInfo("Query error: " . $query . " " . $e->getMessage());
        die("Query error: " . $e->getMessage());
    }
}

function fetch_data_for_graph($result) {
    $time = [];
    $cost_kw = [];
    $cost_kwH = [];
    $total_kw = [];
    $total_kwH = [];

    while ($row = mysql_fetch_assoc($result)) {
        $time[] = $row['time'];
        $cost_kw[] = $row['cost_kw'];
        $cost_kwH[] = $row['cost_kwH'];
        $total_kw[] = $row['total_kw'];
        $total_kwH[] = $row['total_kwh'];
    }

    return [
        'time' => $time,
        'cost_kw' => $cost_kw,
        'cost_kwH' => $cost_kwH,
        'total_kw' => $total_kw,
        'total_kwH' => $total_kwH
    ];
}


// this are unique, not by owner 
function fetch_last_30_days($log, $loopname) {
    $query = sprintf(
        "SELECT 
            loopname,
            ROUND(AVG(daily_costkw),2) AS avg_daily_cost_kw, 
            ROUND(AVG(daily_cost_kwh),2) AS avg_daily_cost_kwh, 
            ROUND(AVG(daily_total_kw),2) AS avg_daily_total_kw, 
            ROUND(AVG(daily_total_kwh),2) AS avg_daily_total_kwh
        FROM (
            SELECT 
                loopname,
                DATE(time) AS day,
                MAX(cost_kw) AS daily_costkw,
                SUM(cost_kwh) AS daily_cost_kwh,
                MAX(peak_kw + off_peak_kw) AS daily_total_kw,
                SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
            FROM 
                Standard_ship_records 
            WHERE 
                loopname = '%s' 
                AND time >= NOW() - INTERVAL 30 DAY
            GROUP BY 
                loopname, DATE(time)
        ) AS daily_sums
        GROUP BY 
            loopname;",
        mysql_real_escape_string($loopname)
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logDebug("Query failed");
        return false;
    }

    return db_fetch_all($result);
}

function fetch_last_year($log, $loopname) {
    $query = sprintf(
        "SELECT 
            loopname,
            ROUND(AVG(daily_costkw),2) AS avg_daily_cost_kw, 
            ROUND(AVG(daily_cost_kwh),2) AS avg_daily_cost_kwh, 
            ROUND(AVG(daily_total_kw),2) AS avg_daily_total_kw, 
            ROUND(AVG(daily_total_kwh),2) AS avg_daily_total_kwh
        FROM (
            SELECT 
                loopname,
                DATE(time) AS day,
                MAX(cost_kw) AS daily_costkw,
                SUM(cost_kwh) AS daily_cost_kwh,
                MAX(peak_kw + off_peak_kw) AS daily_total_kw,
                SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
            FROM 
                Standard_ship_records 
            WHERE 
                loopname = '%s' 
               AND time >= NOW() - INTERVAL 1 YEAR
            GROUP BY 
                loopname, DATE(time)
        ) AS daily_sums
        GROUP BY 
            loopname;",
        mysql_real_escape_string($loopname)
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logDebug("Query failed");
        return false;
    }

    return db_fetch_all($result);
}


function fetch_month_of_specific_year($log, $loopname, $year, $month) {
    $query = sprintf(
        "SELECT 
            loopname,
            ROUND(AVG(daily_costkw),2) AS avg_daily_cost_kw, 
            ROUND(AVG(daily_cost_kwh),2) AS avg_daily_cost_kwh, 
            ROUND(AVG(daily_total_kw),2) AS avg_daily_total_kw, 
            ROUND(AVG(daily_total_kwh),2) AS avg_daily_total_kwh
        FROM (
            SELECT 
                loopname,
                DATE(time) AS day,
                MAX(cost_kw) AS daily_costkw,
                SUM(cost_kwh) AS daily_cost_kwh,
                MAX(peak_kw + off_peak_kw) AS daily_total_kw,
                SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
            FROM 
                Standard_ship_records 
            WHERE 
                loopname = '%s' 
                AND YEAR(time) = %d 
                AND MONTH(time) = %d
            GROUP BY 
                loopname, DATE(time)
        ) AS daily_sums
        GROUP BY 
            loopname;",
        mysql_real_escape_string($loopname), $year, $month
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logDebug("Query failed");
        return false;
    }

    return db_fetch_all($result);
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