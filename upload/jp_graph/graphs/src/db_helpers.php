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
        $log->logError("Query error: " . $query . " " . $e->getMessage());
        die("Query error: " . $e->getMessage());
    }
}

function getMax($a, $b) {
    if ($a > $b) {
        return $a;
    } else {
        return $b;
    }
}

function fetch_data_for_graph_mod1($log,$result) {

    $avg_cost = 0;
    $avg_kw  = 0;
    $avg_kwH = 0;

        // force convertion
    while ($row = mysql_fetch_assoc($result)) {
        // force convertion
        $max_cost_kw = (float)$row['max_cost_kw'];
        $max_off_cost_kw = (float)$row['max_off_cost_kw'];
        $days = (int)$row['days'];
        $daily_cost_kwh = (float)$row['avg_daily_cost_kwh'];
        $max_demand_kw = (float)$row['max_demand_kw'];
        $max_off_demand_kw = (float)$row['max_off_demand_kw'];
        $avg_daily_total_kwh = (float)$row['avg_daily_total_kwh'];

            // Calculate
        if ($days > 0) {
            $avg_demand = ($max_cost_kw + $max_off_cost_kw) / $days;

            $avg_cost += $avg_demand + $daily_cost_kwh;
            $avg_kw = getMax($max_demand_kw, $max_off_demand_kw);
            $avg_kwH += $avg_daily_total_kwh;
        } else {
            $log->logError("Error: 'days' is zero or less.\n");
        }
    }
     
    $log->logDebug("max_cost_kw:" . $max_cost_kw . ", max_off_cost_kw: " . $max_off_cost_kw . ", days: " . $days . ", daily_cost_kwh: " . $daily_cost_kwh . ", max_demand_kw: " . $max_demand_kw . ", max_off_demand_kw: " . $max_off_demand_kw . ", avg_daily_total_kwh: " . $avg_daily_total_kwh . "\n");

    return [
        'avg_cost' => $avg_cost,
        'avg_kw' => $avg_kw,
        'avg_kwH' => $avg_kwH,
    ];
}


// this are unique, not by owner 
function fetch_last_30_days($log, $loopname) {
    $query = sprintf(
        "SELECT 
                loopname,
                ROUND(MAX(max_demand_kw), 2) AS max_demand_kw, 
                ROUND(MAX(max_off_demand_kw), 2) AS max_off_demand_kw,
                ROUND(MAX(max_cost_kw), 2) AS max_cost_kw, 
                ROUND(MAX(max_off_cost_kw), 2) AS max_off_cost_kw,
                ROUND(AVG(daily_cost_kwh), 2) AS avg_daily_cost_kwh, 
                ROUND(AVG(daily_total_kwh), 2) AS avg_daily_total_kwh, 
                COUNT(*) AS days
            FROM (
                SELECT 
                    loopname,
                    DATE(time) AS day,
                    MAX(peak_kw) AS max_demand_kw,
                    MAX(off_peak_kw) AS max_off_demand_kw,
                    MAX(cost_kw) AS max_cost_kw,
                    MAX(off_cost_kw) AS max_off_cost_kw,
                    SUM(cost_kwh + off_cost_kwh) AS daily_cost_kwh,
                    SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
                FROM 
                    Standard_ship_records 
                WHERE 
                    loopname = '%s' 
                    AND time >= NOW() - INTERVAL 30 DAY
                GROUP BY 
                    loopname, DATE(time)
            ) AS daily_sums
            WHERE 
                daily_total_kwh > 0
            GROUP BY 
                loopname;",
        mysql_real_escape_string($loopname)
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return fetch_data_for_graph_mod1($log,$result);


}
function fetch_Annual($log, $loopname) {
    // Ensure that $loopname is defined and has a value
    $log->logDebug(" Loopname: " . $loopname);

    if (isset($loopname) && !empty($loopname)) {
        // Sanitize loopname
        $loopname_sanitized = mysql_real_escape_string($loopname);

        $query = sprintf(
            "SELECT 
                loopname,
                ROUND(AVG(max_demand_kw), 2) AS max_demand_kw,
                ROUND(AVG(max_off_demand_kw), 2) AS max_off_demand_kw,
                ROUND(AVG(max_cost_kw), 2) AS max_cost_kw,
                ROUND(AVG(max_off_cost_kw), 2) AS max_off_cost_kw,
                ROUND(AVG(avg_daily_cost_kwh), 2) AS avg_daily_cost_kwh,
                ROUND(AVG(avg_daily_total_kwh), 2) AS avg_daily_total_kwh,
                AVG(days) AS days
            FROM (
                SELECT 
                    loopname,
                    DATE_FORMAT(day, '%%Y-%%m') AS month,
                    MAX(max_demand_kw) AS max_demand_kw, 
                    MAX(max_off_demand_kw) AS max_off_demand_kw,
                    MAX(max_cost_kw) AS max_cost_kw, 
                    MAX(max_off_cost_kw) AS max_off_cost_kw,
                    AVG(daily_cost_kwh) AS avg_daily_cost_kwh, 
                    AVG(daily_total_kwh) AS avg_daily_total_kwh,
                    COUNT(*) AS days

                FROM (
                    SELECT 
                        loopname,
                        DATE(time) AS day,
                        MAX(peak_kw) AS max_demand_kw,
                        MAX(off_peak_kw) AS max_off_demand_kw,
                        MAX(cost_kw) AS max_cost_kw,
                        MAX(off_cost_kw) AS max_off_cost_kw,
                        SUM(cost_kwh + off_cost_kwh) AS daily_cost_kwh,
                        SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
                    FROM 
                        Standard_ship_records 
                    WHERE 
                        loopname = '%s'
                        AND time >= NOW() - INTERVAL 1 YEAR
                    GROUP BY 
                        loopname, DATE(time)
                ) AS daily_sums
                WHERE 
                    daily_total_kwh > 0
                GROUP BY 
                    loopname, month
            ) AS monthly_sums
            GROUP BY 
                loopname;",
            $loopname_sanitized
        );
        
        $result = db_query($log, $query);

        if (!$result) {
            $log->logError(" Query failed: " . mysql_error());
            return false;
        }

        return fetch_data_for_graph_mod1($log,$result);

    } else {
        // Handle the case where $loopname is not set or is empty
        $log->logError(" Error: loopname is not defined or is empty.");
        return false;
    }
}


function fetch_month_of_specific_year($log, $loopname, $year, $month) {
    $log->logDebug("Loopname: ". $loopname. " Year: ". $year. " Month: ". $month);
    $query = sprintf(
        "SELECT 
                loopname,
                ROUND(MAX(max_demand_kw), 2) AS max_demand_kw, 
                ROUND(MAX(max_off_demand_kw), 2) AS max_off_demand_kw,
                ROUND(MAX(max_cost_kw), 2) AS max_cost_kw, 
                ROUND(MAX(max_off_cost_kw), 2) AS max_off_cost_kw,
                ROUND(AVG(daily_cost_kwh), 2) AS avg_daily_cost_kwh, 
                ROUND(AVG(daily_total_kwh), 2) AS avg_daily_total_kwh, 
                COUNT(*) AS days
            FROM (
                SELECT 
                    loopname,
                    DATE(time) AS day,
                    MAX(peak_kw) AS max_demand_kw,
                    MAX(off_peak_kw) AS max_off_demand_kw,
                    MAX(cost_kw) AS max_cost_kw,
                    MAX(off_cost_kw) AS max_off_cost_kw,
                    SUM(cost_kwh + off_cost_kwh) AS daily_cost_kwh,
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
            WHERE 
                daily_total_kwh > 0
            GROUP BY 
                loopname;",
        mysql_real_escape_string($loopname), $year, $month
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return fetch_data_for_graph_mod1($log,$result);

}
function pad_with_zeros($array, $desired_length = 12) {
    $array_length = count($array);
    if ($array_length < $desired_length) {
        $zeros_to_add = $desired_length - $array_length;
        $array = array_merge(array_fill(0, $zeros_to_add, 0), $array);
    }
    return $array;
}

function fetch_data_for_graph_mod8($log,$result) {

    $avg_cost = [];
    $avg_kw  = [];
    $avg_kwH = [];

        // force convertion
    while ($row = mysql_fetch_assoc($result)) {
        // force convertion
        $max_cost_kw = (float)$row['max_cost_kw'];
        $max_off_cost_kw = (float)$row['max_off_cost_kw'];
        $days = (int)$row['days'];
        $daily_cost_kwh = (float)$row['avg_daily_cost_kwh'];
        $max_demand_kw = (float)$row['max_demand_kw'];
        $max_off_demand_kw = (float)$row['max_off_demand_kw'];
        $avg_daily_total_kwh = (float)$row['avg_daily_total_kwh'];

            // Calculate
        if ($days > 0) {
            $avg_demand = ($max_cost_kw + $max_off_cost_kw) / $days;

            $avg_cost[] = !empty($avg_demand + $daily_cost_kwh) ? $avg_demand + $daily_cost_kwh : 0;
            $avg_kw[] = !empty(max($max_demand_kw, $max_off_demand_kw)) ? max($max_demand_kw, $max_off_demand_kw) : 0;
            $avg_kwH[] = !empty($avg_daily_total_kwh) ? $avg_daily_total_kwh : 0;
        } else {
            $log->logError("Error: 'days' is zero or less.\n");
        }
    }
    // Ensure each array has exactly 12 values by padding with zeros if necessary
    $avg_cost = pad_with_zeros($avg_cost);
    $avg_kw = pad_with_zeros($avg_kw);
    $avg_kwH = pad_with_zeros($avg_kwH);

     
    return [
        'avg_cost' => $avg_cost,
        'avg_kw' => $avg_kw,
        'avg_kwH' => $avg_kwH,
    ];
}

function fetch_year_ago_mod8($log, $loopname, $startDate) {
    $log->logDebug("Loopname: " . $loopname . " StartDate: " . $startDate);

    $query = sprintf(
        "SELECT 
            loopname,
            DATE_FORMAT(day, '%%Y-%%m') AS month_year,
            ROUND(MAX(max_demand_kw), 2) AS max_demand_kw, 
            ROUND(MAX(max_off_demand_kw), 2) AS max_off_demand_kw,
            ROUND(MAX(max_cost_kw), 2) AS max_cost_kw, 
            ROUND(MAX(max_off_cost_kw), 2) AS max_off_cost_kw,
            ROUND(AVG(daily_cost_kwh), 2) AS avg_daily_cost_kwh, 
            ROUND(AVG(daily_total_kwh), 2) AS avg_daily_total_kwh, 
            COUNT(*) AS days
        FROM (
            SELECT 
                loopname,
                DATE_FORMAT(time, '%%Y-%%m-%%d') AS day,
                MAX(peak_kw) AS max_demand_kw,
                MAX(off_peak_kw) AS max_off_demand_kw,
                MAX(cost_kw) AS max_cost_kw,
                MAX(off_cost_kw) AS max_off_cost_kw,
                SUM(cost_kwh + off_cost_kwh) AS daily_cost_kwh,
                SUM(peak_kwh + off_peak_kwh) AS daily_total_kwh
            FROM 
                Standard_ship_records 
            WHERE 
                loopname = '%s' 
                AND time >= DATE_SUB('%s', INTERVAL 12 MONTH)
            GROUP BY 
                loopname, DATE_FORMAT(time, '%%Y-%%m-%%d')
        ) AS daily_sums
        WHERE 
            daily_total_kwh > 0
        GROUP BY 
            loopname, DATE_FORMAT(day, '%%Y-%%m')
        ORDER BY 
            month_year ASC;",
        mysql_real_escape_string($loopname), 
        mysql_real_escape_string($startDate)
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return fetch_data_for_graph_mod8($log, $result);
}

function fetch_data_for_graph_mod3($log, $result) {
    $values = [];

    while ($row = mysql_fetch_assoc($result)) {
        $avgValue = floatval($row["avg_value"]);
        
        $avgValue = round($avgValue, 1); 
        
        $values[] = $avgValue;
    }
     
    return $values;
}


function fetch_mod3_graph($log, $dbField, $loopname, $startDate, $endDate) {
    // Convert start and end dates to timestamps
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);


    // Calculate interval between dates in seconds
    $intervalSeconds = round(($endTimestamp - $startTimestamp) / 287);

    if (!isset($dbField) || empty($dbField)) {
        $sqlField = "current";
    } else {
        $sqlField = $dbField;
    }
    // Log interval seconds for debugging
    $log->logDebug("Field: " . $sqlField . " Loopname: " . $loopname . " Start: " . $startDate . " End: " . $endDate . " Interval Seconds: " . $intervalSeconds);

    $query = sprintf(
        "SELECT
            loopname,
            DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(time) / %d) * %d), '%%Y-%%m-%%d %%H:%%i:%%s') AS time_group,
            AVG(%s) AS avg_value
        FROM Standard_ship_records
        WHERE loopname = '%s'
            AND time BETWEEN '%s' AND '%s'
        GROUP BY loopname, time_group
        ORDER BY time_group ASC;",
        mysql_real_escape_string($intervalSeconds),
        mysql_real_escape_string($intervalSeconds),
        mysql_real_escape_string($sqlField),
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($startDate),
        mysql_real_escape_string($endDate)
    );

    // Execute the query
    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    // Fetch data for the graph
    return fetch_data_for_graph_mod3($log, $result);
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