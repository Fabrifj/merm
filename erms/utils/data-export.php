<?php

require '../../erms/includes/KLogger.php';
include_once ('../../conn/mysql_connect-all.php');
include_once ('../../Auth/auth.php');

$log = new KLogger ( "log.txt" , KLogger::DEBUG );

$data = json_decode(file_get_contents('php://input'), true);

if(isset($_GET['user'])) {
  $user = $_GET['user'];
} else {
  $user = $data['user'];
}
if(isset($_GET['shipClass'])) {
  $class = $_GET['shipClass'];
} else {
  $class = $data['shipClass'];
}

if(!isAuthenticated(true) || !isPermitted($user, $class, null, true)) {
  $json = json_encode(array("error" => "Unauthorized"));
  header('Content-type: application/json');
  exit($json);
}

if(isset($_GET['file'])) {
  $file = $_GET['file'];
  $attachment = "/tmp/$file";
  if (file_exists($attachment)) {
      header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
      header("Cache-Control: public"); // needed for internet explorer
      header("Content-Type: text/csv");
      header("Content-Transfer-Encoding: Binary");
      header("Content-Length:".filesize($attachment));
      header("Content-Disposition: attachment; filename=$file");
      readfile($attachment);
      die();
  } else {
      die("Error: File not found.");
  }
}

$ship = mysql_real_escape_string(stripslashes($data['ship']));
$device = mysql_real_escape_string(stripslashes($data['ships_data'][$ship]['device']));
$local_export_start_date = mysql_real_escape_string(stripslashes($data['data_export_start_date_time']));
$local_export_end_date = mysql_real_escape_string(stripslashes($data['data_export_stop_date_time']));
$timezone = $data['ships_data'][$ship]['timezone'];

date_default_timezone_set('UTC');
$export_start_date = date('Y-m-d h:i:s', strtotime($local_export_start_date.$timezone));
$export_end_date = date('Y-m-d h:i:s', strtotime($local_export_end_date.$timezone));

$sql_fields = "SELECT * FROM $device WHERE time BETWEEN '$export_start_date' AND '$export_end_date' LIMIT 1";
$log->logInfo("[data-export]: $sql_fields");
$query_fields = mysql_query($sql_fields);
$has_at_least_one_row = mysql_num_rows($query_fields);

if(!$has_at_least_one_row) {
  $json = json_encode(array('error' => 'Data not available for that time range'));
  header('Content-type: application/json');
  exit($json);
}

$table_fields_count = mysql_num_fields($query_fields);

$outfile_column_names = "";
$field_names = "";

date_default_timezone_set($timezone);
$utc_offset = date('Z')/3600;

for ( $i = 0; $i < $table_fields_count; $i++ ) {
    $name = mysql_field_name($query_fields, $i);

    if($name == "time") {
      $field = "`time` + INTERVAL $utc_offset HOUR as local_time";
    }
    if($i == 0) {
      $name = "\"$name\"";
    } else {
      $field = ", `$name`";
      $name = ", \"$name\"";
    }

    $outfile_column_names .= $name;
    $field_names .= $field;
}

$outfile_name = str_replace(" ", "_", "$device-$local_export_start_date-$local_export_end_date.csv");
$outfile = "/tmp/$outfile_name";

$http = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'])? 'https://': 'http://';
$data_url = "$http".$_SERVER['HTTP_HOST']."/erms/utils/data-export.php?file=$outfile_name&user=$user&shipClass=$class";

if(file_exists($outfile)) {
  $json = json_encode(array('data_url' => $data_url));
  header('Content-type: application/json');
  exit($json);
}

$sql_export_csv = "SELECT $outfile_column_names
UNION ALL
SELECT $field_names
FROM $device
WHERE time BETWEEN '$export_start_date'
AND '$export_end_date'
INTO OUTFILE '$outfile'
FIELDS TERMINATED BY ','
ENCLOSED BY '\"'
LINES TERMINATED BY '\\n'";

$log->logInfo("[data-export]: $sql_export_csv");
$query_export_csv = mysql_query($sql_export_csv);

if(!$query_export_csv) {
  $json = json_encode(array('error' => "Error downloading table data"));
} else {
  $json = json_encode(array('data_url' => $data_url));
}

header('Content-type: application/json');
exit($json);
?>
