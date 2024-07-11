<?php
/**
 * Navis Energy Management
 * @author Carole Snow
 */

//....................................KLogger...............................
//include Logger.php";
$log = new KLogger ( "log.txt" , KLogger::DEBUG );

//.....................................End KLogger..........................
//set a session variable count to determin if this is first time on page.  Used to set default meter page time interval.
debugPrint('(init) START ');
$testLogger->logInfo("Start --  init_mgr");

$Ship_Table_Name = "";
$Time_Field = "time";
$module=$_REQUEST['module'];
$username=$_REQUEST['user'];
$shipClass= $_REQUEST['shipClass'];
$ship_override = $_REQUEST['ship'];
$ships= (isset($ship_override) && $ship_override != "") ? [$ship_override] : $_SESSION['user_data']['permittedShips'];
$shipDeviceClass = array();
$user_table = "Equate_User";
$Access_Level_Field = "Access_Level";
$Username_Field = "Username";
$annual_report =  $_REQUEST["month"] == "annual" ? 1 : 0;
$current_year = date("Y",strtotime("now")); //get the current year
$max_month = 1;
debugPrint('(init) UserName: ' .$username.' Module:'.$module.' Current year '.$current_year);
$log->logInfo('init.php logging UserName: ' .$username .' -- UserTable: ' .$user_table);

//$sql = "SELECT * FROM `equate_user` WHERE `Username` LIKE '%$username%'";
$sql = "SELECT * FROM Equate_User WHERE Username='$username'";
$log->logInfo($sql);

debugPrint("(init):request date start ".$_REQUEST['start_date_time']);

$RESULT = mysql_query($sql);

if(!$RESULT)
{
  MySqlFailure("access check failed for (".$username.")");
}

$row = mysql_fetch_array($RESULT);
$access_level =$row['Access_Level'];
$ship_group = $row["Ship_Group"];
$Title = $row["Title"];
$Meter_Name = $row["Title"];
$Company = $row["Company"];
$request_year = $_REQUEST["year"];

foreach ($ships AS $aq)
{
  $aquisuitetablename[] = $aq;

  // Currently this joined query is only used for bootstrapping
  // client data, but eventually we should be able to use it to
  // eliminate subsequent calls for duplicate data
  $sql = "SELECT $aq.devicetablename, $aq.deviceclass, $aq.SerialNumber, timezone.timezonephp, Aquisuite_List.utility, Equate_User.Title, Equate_User_Access.Ship_Class, Equate_User_Access.Owner, Aquisuite_List.loopname FROM $aq
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
  debugPrint('(init) ship: '.$row[0].' ship_class: '.$row[6].' owner: '.$row[7].' loopname: '.$row[8] );

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

$log->logInfo("Ship SQL: ".$sql);

$ship_count=count($ship);
$log->logInfo('ship count: ('.$ship_count.')');


switch($module) {
  case ERMS_Modules::PowerAndCostAnalysis: //mod 
    if($annual_report) {
      debugPrint('(init) Annual request Year '. $request_year);
      if ($current_year == $request_year)
      {
        $max_month = date("n",strtotime("-1 month"));
        if (date("n",strtotime("month")) == 1) //January
        {
          $request_year = date("Y",strtotime("-1 year", strtotime($request_year)));
          debugPrint('(init) Annual request -1 Year '.$request_year.' max month'.$max_month);
          $max_month = 12;
        }
      }
      else
      {
        $max_month = 12;
      }
      //get data range for annual report
      $save_startdate = date('F j, Y',strtotime("01/01/".$request_year));
      $endday = date("t",strtotime($max_month.'/01/'.$request_year));
      $save_enddate = date('F j, Y',strtotime($max_month.'/'.$endday.'/'.$request_year));
    }
  debugPrint('(init) Save Start '.$save_startdate.' End '.$save_enddate.' end day '.$endday);
    break;
  case ERMS_Modules::PerformanceTrending: //
    if($request_year == "last12") {
      $startingMonth = date("Y-m-01 00:00:00", strtotime("-12 months"));
      $endingMonth = date("Y-m-01 00:00:00");
    } else {
      $startingMonth = sprintf("%s-01-01 00:00:00", $request_year);
      $endingMonth = sprintf("%s-12-01 00:00:00", $request_year);
    }
    $save_startdate = date('F j, Y G:i:s T Y',strtotime($startingMonth)); //save original dates for bar chart title
    $save_enddate = date('F j, Y G:i:s T Y',(strtotime($endingMonth)));
    $baseline_calculated = false;
    break;
}

foreach ($ship AS $key => $ship) {
  $log->logInfo('Ship: ' . $ship);

  $device_class = device_class_check($ship);
  $ship_aquisuite = $aquisuitetablename[$key];

  $log->logInfo('Ship: ' . $ship);

  $Title = ship_name($ship_aquisuite);

  debugPrint('(init) loop '.$Title.' **************************************');
  debugPrint('(init) Report Year '. $request_year);

  $log->logInfo('aaGraphing from: '.$VAL["date_value_start"].' to: '.$VAL["date_value_end"]);

  // Be able to link the manager to individual ships
  $ship_home_path = sprintf('/upload/jp_graph/graphs/erms_module1.php?display=day&user=%s&module=mod1&ship=%s&shipClass=%s', $username, $ship_aquisuite, $ships_data[$ship_aquisuite]["ship_class"]);
  $ships_data[$ship_aquisuite]["home_path"] = $ship_home_path;
  $Ship_Link_Array[] = $ship_home_path;

  switch($module) {
  case ERMS_Modules::PowerAndCostAnalysis: //"mod1":
    // debugPrint('(init) mod values 30 '.$ship);
    // if (!$annual_report) {
    //   $VAL_30 = mod_values($Time_Field, $ship, 'month', $ship_count, $_REQUEST["month"],$request_year); //30 day summary for top of graph on individual ship page
    //   $VAL = &$VAL_30; //for multiple ship bar chart the monthly and initial values are the same
    //   debugPrint('(init) After mod values 30 '.$ship);
    //   if ($VAL["Avail_Data"] == true) {
    //     //echo 'aGraphing from: '.$VAL_30["date_value_start"].' to: '.$VAL_30["date_value_end"].'<br />';
    //     debugPrint('(init) Avail_Data TRUE');
    //     debugPrint('(init)'.$ship.' Lay Days '.$VAL_30["Lay_Days"].' val kWh_Day ',$VAL["kWh_day"]);

    //     $COST_30 = mod_cost($Time_Field,$ship,$VAL_30);
    //     debugPrint("(mod_cost): GRAND TOTAL LAY DAY ".$COST_30["Grand_Total_Lay_Day"]);
    //   } else {
    //     $COST_30["Grand_Total_Lay_Day"] = 0;
    //   }
    //   // Perspective goals as a percentage relative to the baselines
    //   //$g1 = 0.9;
    //   //$g2 = 0.8;
    //   $Ship_kWh_Average[] = $VAL["kWh_day"];
    //   $ships_data[$ship_aquisuite]["kWh_day"] = $VAL["kWh_day"];
    //   $Ship_Demand[] = $VAL["Peak_Demand"]*1;
    //   $ships_data[$ship_aquisuite]["Peak_Demand"] = $VAL["Peak_Demand"]*1;
    //   $Ship_daily_cost[] =$COST_30["Grand_Total_Lay_Day"];
    //   $ships_data[$ship_aquisuite]["Grand_Total_Lay_Day"] = $COST_30["Grand_Total_Lay_Day"];
    //   $Ship_laydays[] = $VAL["Lay_Days"];
    //   $ships_data[$ship_aquisuite]["Lay_Days"] = $VAL["Lay_Days"];

      $pattern = "/([a-zA-Z0-9])+_([a-zA-Z0-9_-])+/";
      $space=preg_match($pattern, $ship_aquisuite);
      if($space==1) {
        $TITLE=str_replace('_',' ',$ship_aquisuite);
        $TITLE=substr_replace($TITLE,'',-12);
        $TITLE=trim($TITLE);
      }

      $ships_data[$ship_aquisuite]["has_data"] = true;
      $ships_data[$ship_aquisuite]["has_all_lay_days"] = true;
      $S_avail = 0;

      if ($VAL["Lay_Days"] < 29) {
        $S_avail = 2;
        $ships_data[$ship_aquisuite]["has_all_lay_days"] = false;
      }

      if ($VAL["Avail_Data"] == 0) {
        $S_avail = 1;
        $ships_data[$ship_aquisuite]["has_data"] = false;
      }

      $Ship_available[] = $S_avail;
      // $Ship_available[] = ($VAL["Avail_Data"] == 0 ? 0 : 1);

      $Ship_Array[] = $TITLE;


      //echo $TITLE." start date: ".$VAL["date_value_start"]." end date: ".$VAL["date_value_end"]." Average kW: ".$VAL["Demand_avg"]." Peak Demand: ".$VAL["Peak_Demand"]."</br>";
      $save_startdate = date('F j, Y G:i:s T Y',strtotime($VAL["date_value_start"])); //save original dates for bar chart title
      $save_enddate = date('F j, Y G:i:s T Y',(strtotime($VAL["date_value_end"])));

    //   $VAL["Peak_Demand"] += $VAL["Peak_Demand"];
    //   $VAL_30["kWh_day"] += $VAL_30["kWh_day"];

    //   $Grand_Total_Lay_Day += $COST_30["Grand_Total_Lay_Day"];
    //   $Grand_Total_kWh += $COST_30["Grand_Total_kWh"];

    //   debugPrint('(init) Grand Total Lay Day 30['.$COST_30["Grand_Total_Lay_Day"].'] Grand Total Lay Day['.$Grand_Total_Lay_Day.']');
    //   debugPrint('(init) Grand Total kWh 30['.$COST_30["Grand_Total_kWh"].'] Grand Total kWh['.$Grand_Total_kWh.']');

    //   $Ships_Sum += $VAL["kW_sum"];
    //   $Ships_Sum_Count += $VAL["kW_count"];

    // } else {
    //   //Annual Report
    //   if (isset($VAL_YEAR))
    //     unset($VAL_YEAR);

    //   for ($imonth = 0;$imonth< $max_month; $imonth++) {
    //     $repMonth = sprintf("-%02d-01 00:00:00",$imonth+1);
    //     debugPrint('(init) Annual Month '.$repMonth);
    //     $VAL_YEAR[]=mod_values($Time_Field, $ship,'', $ship_count, $repMonth,$request_year);
    //     $VAL = $VAL_YEAR[0];
    //   }
    //   $valid_months = 0;
    //   if (isset($monthly_running_totals))
    //     unset($monthly_running_totals);
    //   if (isset($monthly_average))
    //     unset($monthly_average);
    //   if (isset($COST_YEAR))
    //     unset($COST_YEAR);

    //   debugPrint('(init) mod3 Annual Report Year ' . $request_year);
    //   for ($imonth = 0; $imonth < $max_month; $imonth++)
    //   {
    //     $repMonth = sprintf("-%02d-01 00:00:00", $imonth + 1);
    //     debugPrint('(init) mod3 COST Annual Month ' . $repMonth);
    //     $COST_YEAR[] = mod_cost($Time_Field,$ship,$VAL_YEAR[$imonth]);
    //     if ($VAL_YEAR[$imonth]["Lay_Days"] > 0) {
    //       $valid_months++;
    //     }
    //     $monthly_running_totals = annualRunningTotals($imonth, $monthly_running_totals,$VAL_YEAR, $COST_YEAR);
    //     debugPrint('(init) cost/kWh total '.$monthly_running_totals["Grand_Total_kWh"].' Months '.$valid_months);
    //   }
    //   debugPrint('(init)1 '.$ship.' Months '.$valid_months);
    //   $monthly_average = annualAverages($valid_months, $monthly_running_totals);

    //   $Ship_kWh_Average[] = $monthly_average["kWh_day"];
    //   $Ship_Demand[] = $monthly_average["Peak_Demand"];
    //   $Ship_daily_cost[] = $monthly_average["Grand_Total_Lay_Day"];
    //   $Ship_laydays[] = $monthly_running_totals["Lay_Days"];
    //   debugPrint('(init) ANNUAL kwh/day'.$monthly_average["kWh_day"].' Demand='.$monthly_average["Peak_Demand"].' Cost='.$monthly_average["Grand_Total_Lay_Day"].' Days='.$monthly_running_totals["Lay_Days"]);


    //   $pattern = "/([a-zA-Z0-9])+_([a-zA-Z0-9_-])+/";
    //   $space=preg_match($pattern, $aquisuitetablename[$key]);
    //   if($space==1)
    //   {
    //     $TITLE=str_replace('_',' ',$aquisuitetablename[$key]);
    //     $TITLE=substr_replace($TITLE,'',-12);
    //     $TITLE=trim($TITLE);
    //   }

    //   if ($monthly_running_totals["Lay_Days"] == 0) {
    //     $Ship_available[] = 1;
    //   } else {
    //     $Ship_available[] = 0;
    //   }
      // $Ship_Array[] = $TITLE;

    //   //echo $TITLE." start date: ".$VAL["date_value_start"]." end date: ".$VAL["date_value_end"]." Average kW: ".$VAL["Demand_avg"]." Peak Demand: ".$VAL["Peak_Demand"]."</br>";

    //   $VAL["Peak_Demand"] += $VAL["Peak_Demand"];
    //   $VAL_30["kWh_day"] += $VAL_30["kWh_day"];

    //   $Grand_Total_Lay_Day += $COST_30["Grand_Total_Lay_Day"];
    //   $Grand_Total_kWh += $COST_30["Grand_Total_kWh"];

    //   debugPrint('(init) Grand Total Lay Day 30['.$COST_30["Grand_Total_Lay_Day"].'] Grand Total Lay Day['.$Grand_Total_Lay_Day.']');
    //   debugPrint('(init) Grand Total kWh 30['.$COST_30["Grand_Total_kWh"].'] Grand Total kWh['.$Grand_Total_kWh.']');

    //   $Ships_Sum += $VAL["kW_sum"];
    //   $Ships_Sum_Count += $VAL["kW_count"];
    // }
    break;
  case ERMS_Modules::PerformanceTrending: // mod 8

      for ($imonth = 0;$imonth< 12; $imonth++) {
        $startingMonthTime = strtotime("+".$imonth." month", strtotime($startingMonth));
        $repYear = date('Y', $startingMonthTime);
        $repMonth = date("-m-01 00:00:00", $startingMonthTime);
        debugPrint('(init) Annual Month '.$repMonth);
        $VAL_30 = mod_values($Time_Field, $ship, 'month', $ship_count, $repMonth, $repYear); //30 day summary for top of graph on individual ship page
        $VAL = &$VAL_30; //for multiple ship bar chart the monthly and initial values are the same
        debugPrint('(init) After mod values 30 '.$ship);
        if ($VAL["Avail_Data"] == true) {
          //echo 'aGraphing from: '.$VAL_30["date_value_start"].' to: '.$VAL_30["date_value_end"].'<br />';
          debugPrint('(init) Avail_Data TRUE');
          debugPrint('(init)'.$ship.' Lay Days '.$VAL_30["Lay_Days"].' val kWh_Day ',$VAL["kWh_day"]);

          $COST_30 = mod_cost($Time_Field,$ship,$VAL_30);
          debugPrint("(mod_cost): GRAND TOTAL LAY DAY ".$COST_30["Grand_Total_Lay_Day"]);
        } else {
          $COST_30["Grand_Total_Lay_Day"] = 0;
        }

        $ships_data[$ship_aquisuite]["kWh_day"][] = $VAL["kWh_day"];
        $ships_data[$ship_aquisuite]["Peak_Demand"][] = $VAL["Peak_Demand"]*1;
        $ships_data[$ship_aquisuite]["Grand_Total_Lay_Day"][] = $COST_30["Grand_Total_Lay_Day"];

        if(!$baseline_calculated) {
          // calculate baseline and goals once for each month
          $formatted_month = date_parse($VAL["report_month"]);
          debugPrint(sprintf("report month in baseline %s", $VAL["report_month"]));
          $months[] = $VAL["report_month"];
          $metrics = array("kWh_day", "Peak_Demand", "Grand_Total_Lay_Day");

          // $baselines = get_monthly_baselines($ships_data[$ship_aquisuite]["owner"], $ships_data[$ship_aquisuite]["ship_class"], $ship_group, $metrics, $formatted_month['month']);
          // $Ship_kWh_Average_Baseline[] = ($baselines["kWh_day"]*1);
          // $Ship_kWh_Average_Baseline_G1[] = ($baselines["kWh_day"]*0.9);
          // $Ship_kWh_Average_Baseline_G2[] = ($baselines["kWh_day"]*0.8);
          // $Ship_Demand_Baseline[] = ($baselines["Peak_Demand"]*1);
          // $Ship_Demand_Baseline_G1[] = $baselines["Peak_Demand"]*0.9;
          // $Ship_Demand_Baseline_G2[] = $baselines["Peak_Demand"]*0.8;
          // $Ship_daily_cost_baseline[] = ($baselines["Grand_Total_Lay_Day"]*1);
          // $Ship_daily_cost_baseline_g1[] = $baselines["Grand_Total_Lay_Day"]*0.9;
          // $Ship_daily_cost_baseline_g2[] = $baselines["Grand_Total_Lay_Day"]*0.8;
          
          $Ship_kWh_Average_Baseline[] = 0;
          $Ship_kWh_Average_Baseline_G1[] = 0;
          $Ship_kWh_Average_Baseline_G2[] = 0;
          $Ship_Demand_Baseline[] = 0;
          $Ship_Demand_Baseline_G1[] = 0;
          $Ship_Demand_Baseline_G2[] = 0;
          $Ship_daily_cost_baseline[] = 0;
          $Ship_daily_cost_baseline_g1[] = 0;
          $Ship_daily_cost_baseline_g2[] = 0;
        }

        $ships_data[$ship_aquisuite]["Lay_Days"] = $VAL["Grand_Total_Lay_Day"];
        $ships_data[$ship_aquisuite]["has_data"] = true;
        $ships_data[$ship_aquisuite]["has_all_lay_days"] = true;
        $S_avail = 0;

        if ($VAL["Lay_Days"] < 29) {
         $S_avail = 2;
         $ships_data[$ship_aquisuite]["has_all_lay_days"] = false;
        }

        if ($VAL["Avail_Data"] == 0) {
         $S_avail = 1;
         $ships_data[$ship_aquisuite]["has_data"] = false;
        }

        $Ship_available[] = $S_avail;
        $Ship_available[] = ($VAL["Avail_Data"] == 0 ? 0 : 1);


        // echo $TITLE." start date: ".$VAL["date_value_start"]." end date: ".$VAL["date_value_end"]." Average kW: ".$VAL["Demand_avg"]." Peak Demand: ".$VAL["Peak_Demand"]."</br>";

        $VAL["Peak_Demand"] += $VAL["Peak_Demand"];
        $VAL_30["kWh_day"] += $VAL_30["kWh_day"];

        $Grand_Total_Lay_Day += $COST_30["Grand_Total_Lay_Day"];
        $Grand_Total_kWh += $COST_30["Grand_Total_kWh"];

        debugPrint('(init) Grand Total Lay Day 30['.$COST_30["Grand_Total_Lay_Day"].'] Grand Total Lay Day['.$Grand_Total_Lay_Day.']');
        debugPrint('(init) Grand Total kWh 30['.$COST_30["Grand_Total_kWh"].'] Grand Total kWh['.$Grand_Total_kWh.']');

        $Ships_Sum += $VAL["kW_sum"];
        $Ships_Sum_Count += $VAL["kW_count"];
      }
      $pattern = "/([a-zA-Z0-9])+_([a-zA-Z0-9_-])+/";
      $space=preg_match($pattern, $ship_aquisuite);
      if($space==1) {
        $TITLE=str_replace('_',' ',$ship_aquisuite);
        $TITLE=substr_replace($TITLE,'',-12);
        $TITLE=trim($TITLE);
      }
      $Ship_Array[] = $TITLE;
      $ship_home_path = sprintf('/upload/jp_graph/graphs/erms_module1.php?display=day&user=%s&module=mod1&ship=%s&shipClass=%s', $username, $ship_aquisuite, $ships_data[$ship_aquisuite]["ship_class"]);
      $ships_data[$ship_aquisuite]["home_path"] = $ship_home_path;
      $Ship_Link_Array[] = $ship_home_path;
      $baseline_calculated = true;
    break;
    case ERMS_Modules::EnergyMeterTrending: //"mod3"
      $VAL_30 = mod_values($Time_Field, $ship, '', $ship_count, $_REQUEST["month"],$request_year); //30 day summary for top of graph on individual ship page
      $VAL = &$VAL_30; //for multiple ship bar chart the monthly and initial values are the same
    break;
  }
}

switch($module) {
case ERMS_Modules::PowerAndCostAnalysis: //"mod1":
  //$Ships_Average = number_format($Ships_Sum/$Ships_Sum_Count);
  // $VAL["Peak_Demand"] = $VAL["Peak_Demand"]/$ship_count;
  // $VAL_30["kWh_day"] = $VAL_30["kWh_day"]/$ship_count;
  // $COST_30["Grand_Total_Lay_Day"] = $Grand_Total_Lay_Day/$ship_count;
  // $COST_30["Grand_Total_kWh"] = $Grand_Total_kWh/$ship_count;
  // $ship_data = $ships_data[$aquisuitetablename[$key]];
  // $formatted_month = date_parse($VAL["report_month"]);
  $metrics = array("kWh_day", "Peak_Demand", "Grand_Total_Lay_Day");

  // if(!$annual_report) {
  //   $baselines = get_monthly_baselines($ship_data["owner"], $ship_data["ship_class"], $ship_group, $metrics, $formatted_month['month']);
  // } else {
  //   $baselines = get_annual_baselines($ship_data["owner"], $ship_data["ship_class"], $ship_group, $metrics);
  // }

  // $b_kWh_d = ($baselines["kWh_day"]*1);
  // $b_kWh_dG1 = ($baselines["kWh_day"]*0.9);
  // $b_kWh_dG2 = ($baselines["kWh_day"]*0.8);
  // $b_pd = ($baselines["Peak_Demand"]*1);
  // $b_pdG1 = $baselines["Peak_Demand"]*0.9;
  // $b_pdG2 = $baselines["Peak_Demand"]*0.8;
  // $b_gtld = ($baselines["Grand_Total_Lay_Day"]*1);
  // $b_gtldG1 = $baselines["Grand_Total_Lay_Day"]*0.9;
  // $b_gtldG2 = $baselines["Grand_Total_Lay_Day"]*0.8;

  for($i = 0; $i < $ship_count; $i++) {
    $Ship_kWh_Average_Baseline[] = 0;
    $Ship_kWh_Average_Baseline_G1[] = 0;
    $Ship_kWh_Average_Baseline_G2[] = 0;
    $Ship_Demand_Baseline[] = 0;
    $Ship_Demand_Baseline_G1[] = 0;
    $Ship_Demand_Baseline_G2[] = 0;
    $Ship_daily_cost_baseline[] = 0;
    $Ship_daily_cost_baseline_g1[] = 0;
    $Ship_daily_cost_baseline_g2[] = 0;
  }

  // Fetch values from Standard_ship_records
  $_REQUEST["month"] =isset($_REQUEST["month"]) ? $_REQUEST["month"] : "month";
  $testLogger->logDebug($_REQUEST["month"] );
  
  switch ($_REQUEST["month"] ) {
    case "month":
      try {

        $save_startdate = date('F j, Y G:i:s T Y');
        $save_enddate = date('F j, Y G:i:s T Y', strtotime('-30 days'));
        $Ship_available = [];

        $Ship_kWh_Average = [];
        $Ship_Demand = [];
        $Ship_daily_cost = [];
          
        foreach ($ships as $aq) {
          $ship_data = fetch_last_30_days($testLogger, $ships_data[$aq]["loopname"]);
          if ($ship_data["avg_cost"] == 0) {
            $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }
          $Ship_kWh_Average[] = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);       
          $Ship_daily_cost[] = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
        }
      } catch (Exception $e) {
        $testLogger->logError("Error fetching data for the last 30 days: " . $e->getMessage());
      }
      break;
    
    case "annual":
      try {
        $save_startdate = date('F j, Y G:i:s T Y');
        $save_enddate = date('F j, Y G:i:s T Y', strtotime('-1 year'));
        $Ship_available = [];

        $Ship_kWh_Average = [];
        $Ship_Demand = [];
        $Ship_daily_cost = [];
          
        foreach ($ships as $aq) {
          $ship_data = fetch_Annual($testLogger, $ships_data[$aq]["loopname"]);
          if ($ship_data["avg_cost"] == 0) {
            $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }

          $Ship_kWh_Average[] = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);
          $Ship_daily_cost[] = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
        }
      } catch (Exception $e) {
        $testLogger->logError("Error fetching data for the annual report: " . $e->getMessage());
      }
      break;
    
    default:
      try {
        $year = isset($_REQUEST["year"]) ? intval($_REQUEST["year"]) : date('Y');
        $month = isset($_REQUEST["month"]) ? intval($_REQUEST["month"]) : date('m');
        
        $save_startdate = date('F j, Y G:i:s T', mktime(0, 0, 0, $month, 1, $year));
        $save_enddate = date('Y-m-t', strtotime($save_startdate));
        $Ship_available = [];

        $Ship_kWh_Average = [];
        $Ship_Demand = [];
        $Ship_daily_cost = [];
          
        foreach ($ships as $aq) {
          $ship_data = fetch_month_of_specific_year($testLogger, $ships_data[$aq]["loopname"], $_REQUEST["year"],$_REQUEST["month"] );
          if ($ship_data["avg_cost"] == 0) {
            $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }
          $Ship_kWh_Average[] = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);
          $Ship_daily_cost[] = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
        }
      } catch (Exception $e) {
        $testLogger->logError("Error fetching data for the default report: " . $e->getMessage());
      }
      break;
  }

  

  //
  $graph = [
    "ship" => $Ship_Array,
    "months" => $months,
    "ship_link" => $Ship_Link_Array,
    "ship_available" => $Ship_available,
    "dates" => [$save_startdate, $save_enddate]
  ];

  $graph["data"] = [
    [ "name" => "Consumption(kWh) Avg per Day Baseline",
      "values" => $Ship_kWh_Average_Baseline,
      "group" => "consumptionKWhAvg",
      "type" => "baseline"
    ],
    [ "name" => "Consumption(kWh) Avg per Day",
      "values" => $Ship_kWh_Average,
      "group" => "consumptionKWhAvg",
      "type" => "actual"
    ],
    [ "name" => "Consumption(kWh) Avg per Day Goal 1",
      "values" => $Ship_kWh_Average_Baseline_G1,
      "group" => "consumptionKWhAvg",
      "type" => "goal",
      "visible" => false
    ],
    [ "name" => "Consumption(kWh) Avg per Day Goal 2",
      "values" => $Ship_kWh_Average_Baseline_G2,
      "group" => "consumptionKWhAvg",
      "type" => "goal",
      "visible" => false
    ],
    [
      "name" => "On-Peak Demand Baseline",
      "values" => $Ship_Demand_Baseline,
      "group" => "onPeakDemand",
      "type" => "baseline",
      "yaxis" => 1
    ],
    [
      "name" => "On-Peak Demand",
      "values" => $Ship_Demand,
      "group" => "onPeakDemand",
      "type" => "actual",
      "yaxis" => 1
    ],
    [
      "name" => "On-Peak Demand Goal 1",
      "values" => $Ship_Demand_Baseline_G1,
      "group" => "onPeakDemand",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "On-Peak Demand Goal 2",
      "values" => $Ship_Demand_Baseline_G2,
      "group" => "onPeakDemand",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Baseline",
      "values" => $Ship_daily_cost_baseline,
      "group" => "costAvgPerDay",
      "type" => "baseline",
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day",
      "values" => $Ship_daily_cost,
      "group" => "costAvgPerDay",
      "type" => "actual",
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Goal 1",
      "values" => $Ship_daily_cost_baseline_g1,
      "group" => "costAvgPerDay",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Goal 2",
      "values" => $Ship_daily_cost_baseline_g2,
      "group" => "costAvgPerDay",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ]
  ];
  $metrics = [
    "values" => $VAL,
    "cost" => $COST_30
  ];
  $formattedMessage = print_r($metrics, true);
  $testLogger->logDebug($formattedMessage);


  // No idea what the following code section does for this module
// for ($imonth = 0; $imonth < $max_month; $imonth++)
// {
//   if ($annual_report)
//     $VAL = &$VAL_YEAR[$imonth];
//   foreach ($VAL AS $key => $value)
//   {
//     $sub_key = substr($key, 0, 1);
//     $time_key = substr($key, -4, 4);

//     if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
//     {
//       $VAL[$key] = number_format($value, $sub_key);
//     }
//     else if (is_numeric($value))
//     {
//       $VAL[$key] = number_format($value);
//       if ($key == "Lay_Days")
//         $VAL[$key] = number_format($value, 2); //keep 2 point precision
//       //debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value);

//     }

//     if ($time_key == "Time")
//     {
//       $VAL[$key] = date('Y-m-d H:i:s', strtotime($value."UTC"));
//       //debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value."UTC".' local '.$value);
//     }
//   }
//   if ($annual_report)
//     $VAL["report_month"] = "Annual";
// }

// debugPrint('(init) reset month: '.$VAL["save_startdate"].' date_value_start '.$VAL["date_value_start"]. 'report month '.$VAL["report_month"]);

// if ($annual_report)
// {
//   foreach ($monthly_average AS $key => $value)
//   {
//     $sub_key = substr($key, 0, 1);

//     if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
//     {
//       $monthly_average[$key] = number_format(sue, $sub_key);
//       //debugPrint('(init) key '.$key.' '.$monthly_average[$key].' value '.$value);

//     }
//     else if (is_numeric($value))
//     {
//       $monthly_average[$key] = number_format($value,2);
//       // debugPrint('(init) key '.$key.' '.$monthly_average[$key].' value '.$value);
//     }
//   }
//   foreach ($monthly_running_totals AS $key => $value)
//   {
//     $sub_key = substr($key, 0, 1);

//     if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
//     {
//       $monthly_running_totals[$key] = number_format($value, $sub_key);
//     }
//     else if (is_numeric($value))
//     {
//       $monthly_running_totals[$key] = number_format($value,2);
//       debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value);
//     }
//   }
// }
// for ($imonth = 0; $imonth < $max_month; $imonth++)
// {
//   if ($annual_report)
//     $COST = &$COST_YEAR[$imonth];
//   if (isset($COST))
//   {
//     foreach ($COST AS $key => $value)
//     {
//       $sub_U = substr($key, 0, 1);
//       if ($sub_U != "U" && is_numeric($value))
//       {
//         $COST[$key] = number_format($value, 2);
//       }
//     }
//   }
// }
  break;
case ERMS_Modules::PerformanceTrending: //"mod8":
  //$Ships_Average = number_format($Ships_Sum/$Ships_Sum_Count);
  //$VAL["Peak_Demand"] = $VAL["Peak_Demand"]/$ship_count;
  //$VAL_30["kWh_day"] = $VAL_30["kWh_day"]/$ship_count;
  //$COST_30["Grand_Total_Lay_Day"] = $Grand_Total_Lay_Day/$ship_count;
  //$COST_30["Grand_Total_kWh"] = $Grand_Total_kWh/$ship_count;

  // Get times ! 
  //               Fabri updates 
  $testLogger->logInfo("Mod8 ".$VAL["report_year"]);
  if($VAL["report_year"] =="2024"){
    $endDate = date('Y-m-d');
    $endDateSelected = date('Y-m');
  }else{
        // Start date is January 1st of the given year
    $endDate = $VAL['report_year']."-01-1";
    $endDateSelected = $VAL['report_year']."-01";

  }
  $startDateSelected = date('Y-m', strtotime("$endDate -12 months"));

  $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $months[] = date("F", strtotime("-$i month", strtotime($endDate)));
    }

  try{
    $ship_data = [];
    $kWh_day = [];
    $Peak_Demand = [];    
    $Grand_Total_Lay_Day = [];
    $Ship_available = [];
    foreach ($ships as $aq){
      $results =  fetch_year_ago_mod8($testLogger, $ships_data[$aq]["loopname"], $endDate);
      
      $testLogger->logDebug("Test query result");
      $formattedMessage = print_r($results, true);
      $testLogger->logDebug($formattedMessage);

      $Ship_available[] =1;
      $ships_data[$aq]["kWh_day"] = $results["avg_kwH"];
      $ships_data[$aq]["Peak_Demand"]= $results["avg_kw"];
      $ships_data[$aq]["Grand_Total_Lay_Day"] = $results["avg_cost"];

    }
  } catch (Exception $e) {
    $testLogger->logError("Error fetching data for the default report: " . $e->getMessage());
  }
  $testLogger->logDebug("Check path, add headers ");

  $graph = [
    "categories" => $months,
    "ship_link" => $Ship_Link_Array,
    "ship_available" => $Ship_available,
    "dates" => [$startDateSelected, $endDateSelected]
  ];
  $testLogger->logDebug("Check path, add data ");

  $graph["data"] = [
    [ "name" => "Consumption(kWh) Avg per Day Baseline",
      "values" => $Ship_kWh_Average_Baseline,
      "group" => "consumptionKWhAvg",
      "type" => "baseline",
      "visible" => false
    ],
    [ "name" => "Consumption(kWh) Avg per Day Goal 1",
      "values" => $Ship_kWh_Average_Baseline_G1,
      "group" => "consumptionKWhAvg",
      "type" => "goal",
      "visible" => false
    ],
    [ "name" => "Consumption(kWh) Avg per Day Goal 2",
      "values" => $Ship_kWh_Average_Baseline_G2,
      "group" => "consumptionKWhAvg",
      "type" => "goal",
      "visible" => false
    ],
    [
      "name" => "On-Peak Demand Baseline",
      "values" => $Ship_Demand_Baseline,
      "group" => "onPeakDemand",
      "type" => "baseline",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "On-Peak Demand Goal 1",
      "values" => $Ship_Demand_Baseline_G1,
      "group" => "onPeakDemand",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "On-Peak Demand Goal 2",
      "values" => $Ship_Demand_Baseline_G2,
      "group" => "onPeakDemand",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Baseline",
      "values" => $Ship_daily_cost_baseline,
      "group" => "costAvgPerDay",
      "type" => "baseline",
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Goal 1",
      "values" => $Ship_daily_cost_baseline_g1,
      "group" => "costAvgPerDay",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ],
    [
      "name" => "Cost Avg per Day Goal 2",
      "values" => $Ship_daily_cost_baseline_g2,
      "group" => "costAvgPerDay",
      "type" => "goal",
      "visible" => false,
      "yaxis" => 1
    ]
  ];
  foreach($ships_data as $aq => $data) {
    $graph["data"][] = [
      "name" => $data["title"]." Consumption(kWh) Avg per Day",
      "values" => $data["kWh_day"],
      "group" => "consumptionKWhAvg",
      "type" => "actual",
      "visible" => false
    ];
    $graph["data"][] = [
      "name" => $data["title"]." On-Peak Demand",
      "values" => $data["Peak_Demand"],
      "group" => "onPeakDemand",
      "type" => "actual",
      "visible" => false,
      "yaxis" => 1
    ];
    $graph["data"][] = [
      "name" => $data["title"]." Cost Avg per Day",
      "values" => $data["Grand_Total_Lay_Day"],
      "group" => "costAvgPerDay",
      "type" => "actual",
      "yaxis" => 1
    ];
  }
  $metrics = array("kWh_day", "Peak_Demand", "Grand_Total_Lay_Day");
  $metrics = [
    "values" => $VAL,
    "cost" => $COST_30
  ];
  // $formattedMessage = print_r($graph["data"], true);
  // $testLogger->logDebug($formattedMessage);

  // $formattedMessage = print_r($metrics, true);
  // $testLogger->logDebug($formattedMessage);

  break;



  case ERMS_Modules::EnergyMeterTrending: //"mod3":
    $startDate = date('F j, Y');
    $testLogger->logInfo("Mod3 ".$startDate);
    switch($VAL["display"]){
      case "day":
        $endDate =  date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        break;
      case "week":
        $endDate =  date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime('-1 week'));
        break;
      case "month":
        $endDate =  date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
        break;
      case "anydate":
        $startDate =  $VAL["date_value_start"];
        $endDate =  $VAL["date_value_end"];
        break;  
    }

    $testLogger->logInfo("Start date: ".$startDate." End date: ".$endDate); 
    try {      
      $selectedFields = isset($_POST['data1']) ? $_POST['data1'] : 'current';

      $units = EnergyMetrics::get_details($selectedFields);


      $field = $units["field"];
      $testLogger->logDebug("Fields: " . $field);

      // Convert start and end dates to timestamps
      $startTimestamp = strtotime($startDate);
      $endTimestamp = strtotime($endDate);
      
      if ($startTimestamp > $endTimestamp) {
          throw new Exception('Start date must be earlier than end date');
      }
  
      // Calculate interval between dates in seconds
      $intervalSeconds = round(($endTimestamp - $startTimestamp) / 286);
      $log_interval = $intervalSeconds*1000;
      $dates = getEvenlySpacedDates($startDate, $endDate, $intervalSeconds);
      // $units = EnergyMetrics::get_units($selectedField);

      $chartUnits = [];
      $chartUnits[] = $units;
  
      // Logging start date and end date
  
      $shipsData = [];
      foreach ($ships as $aq) {
        $shipValues = fetch_mod3_graph($testLogger, $field, $ships_data[$aq]["loopname"], $startDate, $endDate);
        $shipName = $ships_data[$aq]["title"];
        $timezone = "America/New_York";

        if (is_array($shipValues)) {
          $numericValues = array_map('floatval', $shipValues);
          $shipData = [
            "name" => $shipName,
            "values" => $shipValues,
            "units" => $units
          ];          
          $shipsData[] = $shipData;
        }
        else {
          $testLogger->logError("Error fetching ship data for $shipName: Data returned is not an array");
        }
        if(count($values) > 0) {
          $ships_data[$aq]["energy_meter_trending"] = array(
            "avg" => array_sum($shipValues)/count($shipValues),
            "units" => $units
          );
        }
      }
  
      $graph = [
          "times" => $dates,
          "timezone" => $timezone,
          "log_interval" => $log_interval ,
          "date_start" => $dates[0],
          "date_end" => $dates[count($dates) - 1],
          "units" => $chartUnits,
          "data" => $shipsData,
      ];
  
    } catch (Exception $e) {
        $testLogger->logError("Error fetching data for the default report: " . $e->getMessage());
    }
    
    
    // $graph=mod3_graph_multi($ships_data,$VAL["date_value_start"],$VAL["date_value_end"]);

  break;
}

#### NUMBER FORMATTING ####

$timezone = timezone($aquisuitetablename[0]);
date_default_timezone_set("$timezone");

//if user selected dates from UI set date back to saved local time
if ((isset($VAL["todo"]) and $VAL["display"]=="anydate") ||
  (isset($VAL["report"]) && $_REQUEST["month"]!=="month"))
{
  $VAL["date_value_start"] = $VAL["save_startdate"];
  $VAL["date_value_end"] = $VAL["save_enddate"];
  debugPrint('(init) reset month: '.$VAL["save_startdate"].' date_value_start '.$VAL["date_value_start"]. 'report month '.$VAL["report_month"]);
}





if(isset($VAL_30))
{
  foreach($VAL_30 AS $key => $value)
  {	$sub_key = substr($key,0,1);
  $time_key = substr($key,-4,4);

  if($sub_key> 0 && $sub_key< 3 && is_numeric($value))
  {
    $VAL_30[$key] = number_format($value,$sub_key);
  }
  else if(is_numeric($value))
  {
    $VAL_30[$key] = number_format($value);
  }
  if($time_key=="Time")
  {
    $VAL_30[$key] = date('Y-m-d H:i:s',strtotime($value."UTC"));
  }
  }
}


if(isset($COST_30))
{
  foreach($COST_30 AS $key => $value)
  {
    $sub_U = substr($key,0,1);
    if($sub_U!="U" && is_numeric($value))
    {
      $COST_30[$key] = number_format($value,2);
    }
  }
}

//$VAL["date_value_start"] = date('Y-m-d H:i:s', strtotime($saveDate));  //debug

$log->logInfo('SERVER: '.$_SERVER['REQUEST_URI']);
$log->logInfo('Mod3: '.stripos($_SERVER['REQUEST_URI'],'mod3'));
$log->logInfo('SHIP TABLE: '.$Ship_Table_Name);
$log->logInfo('SHIP TABLE: '.$Title);

$user = user_page();
$rights = $_REQUEST['rights'];

?>
