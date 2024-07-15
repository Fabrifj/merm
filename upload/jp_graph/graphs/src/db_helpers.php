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
        if ($days > 1) {
            $days -= 1;
        }
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
    return [
        'avg_cost' => $avg_cost,
        'avg_kw' => $avg_kw,
        'avg_kwH' => $avg_kwH,
        'days' => $days
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
function fetch_last_90_days($log, $loopname) {
    $query = sprintf(
        "SELECT 
            loopname,
            ROUND(AVG(max_demand_kw), 2) AS max_demand_kw, 
            ROUND(AVG(max_off_demand_kw), 2) AS max_off_demand_kw,
            ROUND(AVG(max_cost_kw), 2) AS avg_max_cost_kw, 
            ROUND(AVG(max_off_cost_kw), 2) AS max_off_cost_kw,
            ROUND(AVG(avg_daily_cost_kwh), 2) AS avg_daily_cost_kwh, 
            ROUND(AVG(avg_daily_total_kwh), 2) AS avg_daily_total_kwh, 
            SUM(days) AS total_days
        FROM (
            SELECT 
                loopname,
                DATE_FORMAT(time, '%Y-%m') AS month,
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
                    DATE(time) AS time,
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
                    AND time >= NOW() - INTERVAL 90 DAY
                GROUP BY 
                    loopname, DATE(time)
            ) AS daily_sums
            WHERE 
                daily_total_kwh > 0
            GROUP BY 
                loopname, DATE_FORMAT(time, '%Y-%m')
        ) AS monthly_sums
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
function fetch_month_of_specific_date($log, $loopname, $start_date, $end_date) {
    $log->logDebug("Loopname: ". $loopname. " start_date: ". $start_date. " end_date: ". $end_date);
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
                    AND time BETWEEN '%s' AND '%s'

                GROUP BY 
                    loopname, DATE(time)
            ) AS daily_sums
            WHERE 
                daily_total_kwh > 0
            GROUP BY 
                loopname;",
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($start_date),
        mysql_real_escape_string($end_date)
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return fetch_data_for_graph_mod1($log,$result);

}



function pad_with_zeros($log,$array, $desired_length = 12) {
    $array_length = count($array);
    if ($array_length < $desired_length) {
        $zeros_to_add = $desired_length - $array_length;
        $zeros = array_fill(0, $zeros_to_add, 0);
        $array = array_merge($zeros, $array);
    } else {
        $array = array_slice($array, -$desired_length); // Keep only the last 12 elements if array length exceeds 12
    }
    return $array;
}

function fetch_data_for_graph_mod8($log,$result) {

    $avg_cost = [];
    $avg_kw  = [];
    $avg_kwH = [];


    while ($row = mysql_fetch_assoc($result)) {

        $max_cost_kw = (float)$row['max_cost_kw'];
        $max_off_cost_kw = (float)$row['max_off_cost_kw'];
        $days = (int)$row['days'];
        $daily_cost_kwh = (float)$row['avg_daily_cost_kwh'];
        $max_demand_kw = (float)$row['max_demand_kw'];
        $max_off_demand_kw = (float)$row['max_off_demand_kw'];
        $avg_daily_total_kwh = (float)$row['avg_daily_total_kwh'];

        if ($days > 0) {
            $avg_demand = ($max_cost_kw + $max_off_cost_kw) / $days;

            $avg_cost[] = $avg_demand + $daily_cost_kwh;
            $avg_kw[] = max($max_demand_kw, $max_off_demand_kw);
            $avg_kwH[] = $avg_daily_total_kwh;
        } else {
            $log->logError("Error: 'days' is zero or less.\n");
            $avg_cost[] = 0;
            $avg_kw[] = 0;
            $avg_kwH[] = 0;
        }
    }

    // // Ensure each array has exactly 12 values by padding with zeros if necessary
    // $avg_cost = pad_with_zeros($log,$avg_cost);
    // $avg_kw = pad_with_zeros($log,$avg_kw);
    // $avg_kwH = pad_with_zeros($log,$avg_kwH);

     
    return [
        'avg_cost' => $avg_cost,
        'avg_kw' => $avg_kw,
        'avg_kwH' => $avg_kwH,
    ];
}

function fetch_year_ago_mod8($log, $loopname, $endDate) {
    $log->logDebug("Loopname: " . $loopname . " endDate: " . $endDate);

    $query = sprintf(
            "SELECT 
                loopname,
                MONTH(day) AS month_year,
                ROUND(MAX(max_demand_kw), 2) AS max_demand_kw, 
                ROUND(MAX(max_off_demand_kw), 2) AS max_off_demand_kw,
                ROUND(MAX(max_cost_kw), 2) AS max_cost_kw, 
                ROUND(MAX(max_off_cost_kw), 2) AS max_off_cost_kw,
                ROUND(AVG(daily_cost_kwh), 2) AS avg_daily_cost_kwh, 
                ROUND(AVG(daily_total_kwh), 2) AS avg_daily_total_kwh, 
                COUNT(CASE WHEN daily_total_kwh > 0 THEN 1 END) AS days
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
                    AND time >= DATE_SUB('%s', INTERVAL 12 MONTH)
                GROUP BY 
                    loopname, day
            ) AS daily_sums
            GROUP BY 
                loopname, MONTH(day)
            ORDER BY 
                month_year ASC;",
            mysql_real_escape_string($loopname), 
            mysql_real_escape_string($endDate)
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

function fetch_data_for_graph_mod6($log,$result) {

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
     
    return [
        'avg_cost' => $avg_cost,
        'avg_kw' => $avg_kw,
        'avg_kwH' => $avg_kwH,
    ];
}
function fetch_mod6_general_data($log,$loopname,$year, $month) {
    $query = sprintf(
        "SELECT 
            loopname,
            MAX(energy_consumption) AS energy_consumption, 
            MAX(accumulation) AS accumulation,
            SUM(sum_peak_kwh) AS sum_peak_kwh,
            SUM(sum_off_peak_kwh) AS sum_off_peak_kwh,
            AVG(avg_real_power) AS avg_real_power,
            AVG(avg_power_factor) AS avg_power_factor,
            MIN(min_power_factor) AS min_power_factor,
            MAX(max_power_factor) AS max_power_factor,
            MAX(max_demand_cost_kw) AS max_demand_cost_kw,
            MAX(max_off_demand_cost_kw) AS max_off_demand_cost_kw,
            SUM(sum_cost_kwh) AS sum_cost_kwh,
            SUM(sum_off_cost_kwh) AS sum_off_cost_kwh,
            COUNT(*) AS days
        FROM (
            SELECT 
                loopname,
                DATE(time) AS day,
                MAX(energy_consumption) AS energy_consumption, 
                MAX(accumulation) AS accumulation,
                SUM(peak_kwh) AS sum_peak_kwh,
                SUM(off_peak_kwh) AS sum_off_peak_kwh,
                AVG(real_power) AS avg_real_power,
                AVG(power_factor) AS avg_power_factor,
                MIN(power_factor) AS min_power_factor,
                MAX(power_factor) AS max_power_factor,
                MAX(cost_kw) AS max_demand_cost_kw,
                MAX(off_cost_kw) AS max_off_demand_cost_kw,
                SUM(cost_kwh) AS sum_cost_kwh,
                SUM(off_cost_kwh) AS sum_off_cost_kwh
            FROM 
                Standard_ship_records
            WHERE 
                loopname = '%s'
                AND YEAR(time) = '%d'
                AND MONTH(time) = '%d'
            GROUP BY 
                loopname, DATE(time)
        ) AS daily_sums
        WHERE 
            avg_real_power > 0
        GROUP BY 
            loopname;",
        mysql_real_escape_string($loopname), $year, $month
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return mysql_fetch_assoc($result);

}
function fetch_mod6_max_peak($log, $loopname, $year, $month) {
    $query = sprintf(
        "SELECT 
            s.loopname,
            IFNULL(s.max_peak_kw, 0) AS max_peak_kw,
            IFNULL(sr.time, 0) AS max_peak_time
        FROM (
            SELECT 
                loopname,
                MAX(peak_kw) AS max_peak_kw
            FROM 
                Standard_ship_records
            WHERE 
                loopname = '%s'
                AND YEAR(time) = '%d'
                AND MONTH(time) = '%d'
            GROUP BY 
                loopname
        ) AS s
        LEFT JOIN 
            Standard_ship_records sr ON sr.loopname = s.loopname AND sr.peak_kw = s.max_peak_kw
        WHERE 
            sr.loopname = '%s'
            AND YEAR(time) = '%d'
            AND MONTH(time) = '%d'
        ORDER BY 
            sr.time ASC
        LIMIT 1;",
        mysql_real_escape_string($loopname), $year, $month,
        mysql_real_escape_string($loopname), $year, $month
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return [
            'loopname' => $loopname,
            'max_peak_kw' => 0,
            'max_peak_time' => 0
        ];
    }

    $row = mysql_fetch_assoc($result);
    if (!$row) {
        return [
            'loopname' => $loopname,
            'max_peak_kw' => 0,
            'max_peak_time' => 0
        ];
    }

    return $row;
}

function fetch_mod6_max_off_peak($log, $loopname, $year, $month) {
    $log->logDebug($loopname." , ". $year." , ". $month);

    $query = sprintf(
        "SELECT 
            s.loopname,
            s.max_off_peak_kw,
            sr.time AS max_off_peak_time
        FROM (
            SELECT 
                loopname,
                MAX(off_peak_kw) AS max_off_peak_kw
            FROM 
                Standard_ship_records
            WHERE 
                loopname = '%s'
                AND YEAR(time) = '%d'
                AND MONTH(time) = '%d'
            GROUP BY 
                loopname
        ) AS s
        JOIN 
            Standard_ship_records sr ON sr.loopname = s.loopname AND sr.off_peak_kw = s.max_off_peak_kw
        WHERE 
            sr.loopname = '%s'
            AND YEAR(time) = '%d'
            AND MONTH(time) = '%d'
        ORDER BY 
            sr.time ASC
        LIMIT 1;",
        mysql_real_escape_string($loopname), $year, $month,
        mysql_real_escape_string($loopname), $year, $month
    );

    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return mysql_fetch_assoc($result);
}


function fetch_monthly_report_mod6($log, $loopname, $year, $month) {
    $log->logDebug("Loopname: " . $loopname . " Year: " . $year . " Month: " . $month);

    // Fetch general data
    $generalData = fetch_mod6_general_data($log, $loopname, $year, $month);

    // Fetch max peak data
    $maxPeak = fetch_mod6_max_peak($log, $loopname, $year, $month);

    // Fetch max off peak data
    $maxOffPeak = fetch_mod6_max_off_peak($log, $loopname, $year, $month);

    // Calculate maximum demand
    $maxDemand = max($generalData["max_demand_cost_kw"], $generalData["max_off_demand_cost_kw"]);

    // Calculate total peak kWh
    $totalPeak = $generalData["sum_peak_kwh"] + $generalData["sum_off_peak_kwh"];

    // Calculate total demand cost
    $totalDemandCost = $generalData["max_demand_cost_kw"] + $generalData["max_off_demand_cost_kw"];

    // Calculate total peak cost
    $totalPeakCost = $generalData["sum_cost_kwh"] + $generalData["sum_off_cost_kwh"];
    //	$VAL["2_Total_CO2"] =($VAL["kWh_Total"]*601.292474+$VAL["kWh_Total"]*0.899382565*310+$VAL["kWh_Total"]*0.01839836*21)/pow(10,6);

    $totalCO2 =($totalPeak*601.292474+$totalPeak*0.899382565*310+$totalPeak*0.01839836*21)/pow(10,6);

    // Build monthly report array
    $monthlyReport = [
        'Year' => $year,
        'Month' => $month,
        'EndOfMonthReading' => round(isset($generalData["energy_consumption"]) ? $generalData["energy_consumption"] : 0, 2),
        'TotalkWhConsumed' => round(isset($totalPeak) ? $totalPeak : 0, 2),
        'MaxOnPeakDemand' => round(isset($maxPeak["max_peak_kw"]) ? $maxPeak["max_peak_kw"] : 0, 2),
        'OnPeakBilledDemand' => round(isset($maxPeak["max_peak_kw"]) ? $maxPeak["max_peak_kw"] : 0, 2), // Placeholder, update with correct key if needed
        'TimeOfMaxOnPeakDemand' => (isset($maxPeak["max_peak_kw"]) && $maxPeak["max_peak_kw"] != 0) ? $maxPeak["max_peak_time"] : 0, // Assuming this is a date/time value
        'MaxOffPeakDemand' => round(isset($maxOffPeak["max_off_peak_kw"]) ? $maxOffPeak["max_off_peak_kw"] : 0, 2),
        'OffPeakBilledDemand' => round(isset($maxOffPeak["max_off_peak_kw"]) ? $maxOffPeak["max_off_peak_kw"] : 0, 2), // Placeholder, update with correct key if needed
        'TimeOfMaxOffPeakDemand' => isset($maxOffPeak["max_off_peak_time"]) ? $maxOffPeak["max_off_peak_time"] : 0, // Assuming this is a date/time value
        'LayDays' => isset($generalData["days"]) ? $generalData["days"] : 0,
        'OnPeakkWh' => round(isset($generalData["sum_peak_kwh"]) ? $generalData["sum_peak_kwh"] : 0, 2),
        'OffPeakkWh' => round(isset($generalData["sum_off_peak_kwh"]) ? $generalData["sum_off_peak_kwh"] : 0, 2),
        'AvgPower' => round(isset($generalData["avg_real_power"]) ? $generalData["avg_real_power"] : 0, 2),
        'BilledPowerFactor' => round(isset($generalData["avg_power_factor"]) ? $generalData["avg_power_factor"] : 0, 3),
        'AvgPowerFactor' => round(isset($generalData["avg_power_factor"]) ? $generalData["avg_power_factor"] : 0, 3),
        'LowestPowerFactor' => round(isset($generalData["min_power_factor"]) ? $generalData["min_power_factor"] : 0, 3),
        'HighestPowerFactor' => round(isset($generalData["max_power_factor"]) ? $generalData["max_power_factor"] : 0, 3),
        'TotalCO2' => round(isset($totalCO2) ? $totalCO2 : 0, 2), // Placeholder, update with actual calculation if applicable
        'OnPeakEnergyCharges' => round(isset($generalData["sum_cost_kwh"]) ? $generalData["sum_cost_kwh"] : 0, 2),
        'OffPeakEnergyCharges' => round(isset($generalData["sum_off_cost_kwh"]) ? $generalData["sum_off_cost_kwh"] : 0, 2),
        'OtherEnergyCharges' => round(0, 2), // Placeholder, update with actual calculation if applicable
        'TotalEnergyCharges' => round(isset($totalPeakCost) ? $totalPeakCost : 0, 2),
        'OnPeakDemandCharges' => round(isset($generalData["max_demand_cost_kw"]) ? $generalData["max_demand_cost_kw"] : 0, 2),
        'OffPeakDemandCharges' => round(isset($generalData["max_off_demand_cost_kw"]) ? $generalData["max_off_demand_cost_kw"] : 0, 2),
        'OtherDemandCharges' => round(0, 2), // Placeholder, update with actual calculation if applicable
        'TotalDemandCharges' => round(isset($totalDemandCost) ? $totalDemandCost : 0, 2),
        'TotalEstimatedBill' => round((isset($totalDemandCost) ? $totalDemandCost : 0) + (isset($totalPeakCost) ? $totalPeakCost : 0), 2),
        'FullBurdenPureDemandRate' => round((isset($maxDemand) && $maxDemand != 0) ? (isset($totalDemandCost) ? $totalDemandCost : 0) / $maxDemand : 0, 2),
        'FullBurdenPureEnergyRate' => round((isset($totalPeak) && $totalPeak != 0) ? (isset($totalPeakCost) ? $totalPeakCost : 0) / $totalPeak : 0, 2),
        'FullBurdenShorepowerRate' => round((isset($totalPeak) && $totalPeak != 0) ? ((isset($totalDemandCost) ? $totalDemandCost : 0) + (isset($totalPeakCost) ? $totalPeakCost : 0)) / $totalPeak : 0, 2),
    ];

    return $monthlyReport;
}
function fetch_mod3_max_field($log, $loopname, $field_name, $start_date, $end_date){
    $query = sprintf(
        "SELECT 
            s.loopname,
            ROUND(s.max_value,2) AS max_field,
            sr.time AS max_time
        FROM (
            SELECT 
                loopname,
                MAX(%s) AS max_value
            FROM 
                Standard_ship_records
            WHERE 
                loopname = '%s'
                AND time BETWEEN '%s' AND '%s'
            GROUP BY 
                loopname
        ) AS s
        JOIN 
            Standard_ship_records sr ON sr.loopname = s.loopname AND sr.%s = s.max_value
        WHERE 
            sr.loopname = '%s'
            AND sr.time BETWEEN '%s' AND '%s'
        ORDER BY 
            sr.time ASC
        LIMIT 1;",
        mysql_real_escape_string($field_name), 
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($start_date),
        mysql_real_escape_string($end_date),
        mysql_real_escape_string($field_name), 
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($start_date),
        mysql_real_escape_string($end_date)
    );
    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return mysql_fetch_assoc($result);
}

function fetch_mod3_general_data($log, $loopname, $start_date, $end_date){
    $query = sprintf(
        "SELECT 
            loopname,
            ROUND(SUM(peak_kwh),2) AS peak_kwh,
            ROUND(SUM(off_peak_kwh),2) AS off_peak_kwh,
            ROUND(AVG(power_factor),2) AS avg_power_factor,
            ROUND(MAX(power_factor),2) AS max_power_factor,
            ROUND(MIN(power_factor),2) AS min_power_factor,
            ROUND(AVG(current),2) AS current,
            ROUND(AVG(voltage_phase_ab),2) AS avg_voltage,
            ROUND(MAX(voltage_phase_ab),2) AS max_voltage,
            ROUND(MIN(voltage_phase_ab),2) AS min_voltage,
            ROUND(AVG(reactive_power),2) AS reactive_power
        FROM Standard_ship_records
        WHERE loopname='%s'
            AND time BETWEEN '%s' AND '%s'
        GROUP BY loopname;", 
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($start_date),
        mysql_real_escape_string($end_date)
    );
    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }

    return mysql_fetch_assoc($result);
}


function fetch_summary_report_mod3($log, $loopname, $start_date, $end_date) {
    $log->logDebug("Loopname: " . $loopname . " start_date: " . $start_date . " end_date: " . $end_date);

    // Fetch max current
    $maxCurrent = fetch_mod3_max_field($log, $loopname,"current" ,$start_date, $end_date);
    
    // Fetch max rective powe
    $maxReactivePower = fetch_mod3_max_field($log, $loopname,"reactive_power" ,$start_date, $end_date);

    // Fetch max peak data
    $maxPeak = fetch_mod3_max_field($log, $loopname,"peak_kw" , $start_date, $end_date);

    // Fetch max off peak data
    $maxOffPeak = fetch_mod3_max_field($log, $loopname,"off_peak_kw" , $start_date, $end_date);

    //Fetch general data
    $generalData = fetch_mod3_general_data($log, $loopname, $start_date, $end_date); 

    // Build sumarry report array
    $monthlyReport = [
        'Year' => $start_date,
        'Month' => $end_date,
        'OnPeakDemand' => isset($maxPeak["max_field"]) ? $maxPeak["max_field"] : 0,
        'TimeOnPeakDemand' => isset($maxPeak["max_time"]) ? $maxPeak["max_time"] : '1970-01-01 00:00:00',
        'OffPeakDemand' => isset($maxOffPeak["max_field"]) ? $maxOffPeak["max_field"] : 0, 
        'TimeOffPeakDemand' => isset($maxPeak["max_time"]) ? $maxOffPeak["max_time"] : '1970-01-01 00:00:00',
        'MaxCurrent' => isset($maxCurrent["max_field"]) ? $maxCurrent["max_field"] : 0, 
        'TimeMaxCurrent' => isset($maxCurrent["max_time"])!=0 ? $maxCurrent["max_time"] : '1970-01-01 00:00:00',
        'MaxReactivePower' => isset($maxReactivePower["max_field"]) ? $maxReactivePower["max_field"] : 0, 
        'TimeMaxReactivePower' => isset($maxReactivePower["max_time"])!=0 ? $maxReactivePower["max_time"] : '1970-01-01 00:00:00',
        'OnPeakEnergy' => $generalData["peak_kwh"],
        'OffPeakEnergy' => $generalData["off_peak_kwh"],
        'AvgPowerFactor' => $generalData["avg_power_factor"],
        'MaxPowerFactor' => $generalData["max_power_factor"],
        'MinPowerFactor' => $generalData["min_power_factor"],
        'AvgCurrent' => $generalData["current"],
        'AvgVoltage' => $generalData["avg_voltage"],
        'MaxVoltage' => $generalData["max_voltage"],
        'MinVoltage' => $generalData["min_voltage"],
        'AvgReactivePower' => $generalData["reactive_power"],
       
    ];

    return $monthlyReport;
}



// Function to fetch records from a specific table
function db_fetch_utility_rate($log, $utility) {
    $query = sprintf("SELECT * FROM `%s` ORDER BY Rate_Date_Start DESC LIMIT 1;",
        mysql_real_escape_string($utility)
    );
    $result = db_query($log, $query);
    return db_fetch_all($result);
}

function fetch_data_mod1($log,  $loopname, $startDate, $endDate){
    // Convert start and end dates to timestamps
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $intervalSeconds = round(($endTimestamp - $startTimestamp) / 288);
    $log->logDebug( " Loopname: " . $loopname . " Start: " . $startDate . " End: " . $endDate . " Interval Seconds: " . $intervalSeconds);
    $query = sprintf(
        "SELECT
            loopname,
            DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(time) / %d) * %d), '%%Y-%%m-%%d %%H:%%i:%%s') AS time_group,
            MAX(`peak_kw`+`off_peak_kw`) AS dkW,
      		AVG(`real_power`) AS real_power
        FROM Standard_ship_records
        WHERE loopname = '%s'
            AND time BETWEEN '%s' AND '%s'
        GROUP BY loopname, time_group
        ORDER BY time_group ASC;",
        mysql_real_escape_string($intervalSeconds),
        mysql_real_escape_string($intervalSeconds),
        mysql_real_escape_string($loopname),
        mysql_real_escape_string($startDate),
        mysql_real_escape_string($endDate)
    );
    $result = db_query($log, $query);

    if (!$result) {
        $log->logError("Query failed");
        return false;
    }
    $realpower = [];
    $powerDemand = [];
    while ($row = mysql_fetch_assoc($result)) {
        $realpower []= round($row["real_power"],2);
        $powerDemand []= round($row["dkW"],2);
    }
    // Fetch data for the graph
    $values = [
        "realPower" => $realpower,
        "estimatedPower" => $powerDemand
    ];
    return $values;
}

function  fetch_unitary_mod3_graph($log, $loopname,$field1, $field2, $startDate, $endDate){
   // Convert start and end dates to timestamps
   $startTimestamp = strtotime($startDate);
   $endTimestamp = strtotime($endDate);


   // Calculate interval between dates in seconds
   $intervalSeconds = round(($endTimestamp - $startTimestamp) / 287);

   if (!isset($field1) || empty($field1)) {
       $sqlField1 = "current";
   } else {
       $sqlField1 = $field1;
   }
   if (!isset($field2) || empty($field2)) {
    $sqlField2 = "power_factor";
    } else {
        $sqlField2 = $field2;
    }
   // Log interval seconds for debugging
   $log->logDebug("Fields: " . $sqlField1."--".$sqlField2 . " Loopname: " . $loopname . " Start: " . $startDate . " End: " . $endDate . " Interval Seconds: " . $intervalSeconds);

   $query = sprintf(
       "SELECT
           loopname,
           DATE_FORMAT(FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(time) / %d) * %d), '%%Y-%%m-%%d %%H:%%i:%%s') AS time_group,
           AVG(%s) AS avg_value_1,
           AVG(%s) AS avg_value_2
       FROM Standard_ship_records
       WHERE loopname = '%s'
           AND time BETWEEN '%s' AND '%s'
       GROUP BY loopname, time_group
       ORDER BY time_group ASC;",
       mysql_real_escape_string($intervalSeconds),
       mysql_real_escape_string($intervalSeconds),
       mysql_real_escape_string($sqlField1),
       mysql_real_escape_string($sqlField2),
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
   $values1 = [];
   $values2 = [];

    while ($row = mysql_fetch_assoc($result)) {
        $avgValue1 = floatval($row["avg_value_1"]);
        $avgValue2 = floatval($row["avg_value_2"]);
        
        $avgValue1 = round($avgValue1, 1); 
        $avgValue2 = round($avgValue2, 1); 
        
        $values1[] = $avgValue1;
        $values2[] = $avgValue2;

    }
     
    return [$values1, $values2];

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