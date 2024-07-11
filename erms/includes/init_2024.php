<?php
$Ship_Table_Name = "";
$Time_Field = "time";
$module=$_REQUEST['module'];
$username=$_REQUEST['user'];
$shipClass= $_REQUEST['shipClass'];
$ship_override = $_REQUEST['ship'];
$ships= (isset($ship_override) && $ship_override != "") ? [$ship_override] : $_SESSION['user_data']['permittedShips'];
$shipDeviceClass = array();
if (!isset( $_SESSION["meterpagetime"]))
  $_SESSION["meterpagetime"] = 0;
else if (isset( $_SESSION["meterpagetime"]) && ($module == "mod3"))
{
  if ($_SESSION["meterpagetime"] <= 0)
  {
    $_REQUEST['display'] = "month";
    debugPrint('SESSION:'.$_SESSION["meterpagetime"]);
  }
  $_SESSION["meterpagetime"]++;
}
$user_table = "Equate_User";
$Access_Level_Field = "Access_Level";
$Username_Field = "Username";
$annual_report =  $_REQUEST["month"] == "annual" ? 1 : 0;
$current_year = date("Y",strtotime("now")); //get the current year
$max_month = 1;

foreach ($ships AS $aq)
{
  $aquisuitetablename[] = $aq;

  // Currently this joined query is only used for bootstrapping
  // client data, but eventually we should be able to use it to
  // eliminate subsequent calls for duplicate data
  $sql = "SELECT $aq.devicetablename, $aq.deviceclass, $aq.SerialNumber, timezone.timezonephp, Aquisuite_List.utility, Equate_User.Title, Equate_User_Access.Ship_Class, Equate_User_Access.Owner Aquisuite_List.loopname FROM $aq
          LEFT JOIN Aquisuite_List
          ON Aquisuite_List.SerialNumber = $aq.SerialNumber
          LEFT JOIN timezone
          ON Aquisuite_List.timezoneaquisuite = timezone.timezoneaquisuite
          LEFT JOIN Equate_User
          ON Equate_User.aquisuitetablename = Aquisuite_List.aquisuitetablename
          LEFT JOIN Equate_User_Access
          ON Equate_User.aquisuitetablename = Equate_User_Access.aquisuitetablename
          WHERE $aq.function='main_utility'";
  $result = mysql_query($sql);

  if(!$result)
  {
    MySqlFailure("Could not Find devicetable from ".$aq);
  }
  $row = mysql_fetch_row($result);

  $ship[] = $row[0];
  $shipDeviceClass[] = $row[1];
  debugPrint('(init) ship: '.$row[0].' ship_class: '.$row[6].' owner: '.$row[7]);

  $ships_data[$aq] = array(
    "aquisuite" => $aq,
    "device" => $row[0],
    "class" => $row[1],
    "timezone" => $row[3],
    "utility" => $row[4],
    "title" => $row[5],
    "ship_class" => $row[6],
    "owner" => $row[7],
    "loopname" => $row[8]

  );
}

switch($module){
    case "mod0":
        break;
    case "mod1":
        break;
    case "mod3":
        break;
    case "mod6":
        $testLogger->logInfo('mode 6');
        // update 2024
        try {
        $parts = explode('_', $ships[0]);
        $loopname = $parts[0] . '_' . $parts[1];
    
        $year = isset($_REQUEST["year"]) ? intval($_REQUEST["year"]) : date('Y');
        $month = isset($_REQUEST["month"]) ? intval($_REQUEST["month"]) : 0;
        
        if ($month == 0) {
            $month = date('m'); 
        } else {
            $month = abs($month); 
        }   
    
        $shipData = fetch_monthly_report_mod6($testLogger, $loopname, $year, $month);
        $formattedMessage = print_r($shipData, true);
        $testLogger->logDebug($formattedMessage);
    
        $performance = fetch_last_30_days($testLogger, $loopname);
        // graph
    // $data   =
    //   array
    //   (
    //     $shipData["TotalEnergyCharges"],
    //     $shipData["TotalDemandCharges"],
    //     1
    //   );
    // if (!$annual_report)
    //   $graph  =
    //   erms_pie_graph
    //   (
    //     $Time_Field,
    //     $data,
    //     $ship,
    //     $VAL["date_value_start"],
    //     $VAL["date_value_end"],
    //     $VAL["report_month"]
    //   );
        } catch (Exception $e) {
            $testLogger->logError("Error fetching MonthlyReports: " . $e->getMessage());
        }
        break;  
}


?>
