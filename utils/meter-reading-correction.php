<?php
//
//....................................KLogger...............................
require '../erms/includes/KLogger.php';
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
//.....................................End KLogger..........................

error_reporting (E_ALL ^ E_NOTICE);

require_once('../erms/includes/debugging.php');
//require_once('../erms/includes/access_control.php');
//include '../erms/includes/data_methods.php';
//include '../erms/includes/energy_methods.php';
include_once ('../conn/mysql_connect-all.php');


function old_meter_correction($time_overlap, $table) {
  $sql="SELECT time, Energy_Consumption
        FROM $table
        WHERE time <= '$time_overlap'
        ORDER by time DESC";
  $query=mysql_query($sql);
  $new_device_meter_previous_value=NULL;
  $old_device_meter_previous_value=NULL;
  while ($row = mysql_fetch_assoc($query)) {
    if(!$old_device_meter_previous_value) {
      $old_device_meter_previous_value = $row['Energy_Consumption'];
      continue;
    } else if (!$new_device_meter_previous_value) {
      $new_device_meter_previous_value=$row['Energy_Consumption'];
      continue;
    } else {
      $corrected_meter_value = $new_device_meter_previous_value - ($old_device_meter_previous_value - $row['Energy_Consumption']);
      $time = $row['time'];
      $sql_update = "UPDATE $table
         SET Energy_Consumption=$corrected_meter_value
         WHERE time='$time'";
      debugPrint(sprintf("update sql: %s", $sql_update));
      mysql_query($sql_update);
    }

    $old_device_meter_previous_value = $row['Energy_Consumption'];
    $new_device_meter_previous_value = $corrected_meter_value;
  }

  $sql="DELETE FROM $table WHERE time='$time_overlap'";
  $query=mysql_query($sql);

  if(!$query) {
    echo "Unable to delete $time record from $table";
  }
}

$table1 = "Cape_Knox_001EC6001635__device001_class2";
$time1 = "2018-10-24 13:35:08";
old_meter_correction($time1, $table1);
$table2 = "Cape_Kennedy_001EC6001433__device001_class2";
$time2 = "2018-12-11 17:30:01";
old_meter_correction($time2, $table2);
?>
