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
        $indicator =$loopname;
        $utility = utility_check($ships[0]);
        $year = isset($_REQUEST["year"]) ? intval($_REQUEST["year"]) : date('Y');
        $month = isset($_REQUEST["month"]) ? intval($_REQUEST["month"]) : $month = date('m');
        
        if ($month == 0) {
            $month = date('m'); 
        } else {
            $month = abs($month); 
        }   
    
        $shipData = fetch_monthly_report_mod6($testLogger, $loopname, $year, $month);
        $formattedMessage = print_r($shipData, true);
        $testLogger->logDebug($formattedMessage);
        
        $performance = fetch_last_30_days($testLogger, $loopname);

        $utilityRate = create_utility_class($logger,$utility);

        $taxesAddFees = UtilityRateFactory::$utilityRate->getCustomerCharge();
        $totalCost = $shipData["TotalEnergyCharges"] + $shipData["TotalDemandCharges"] +$taxesAddFees ;

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
