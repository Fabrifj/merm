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

$log->logInfo("Ship SQL: ".$sql);

$ship_count=count($ship);
$log->logInfo('ship count: ('.$ship_count.')');

if ($annual_report)
{
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
  debugPrint('(init) Save Start '.$save_startdate.' End '.$save_enddate.' end day '.$endday);
}

foreach ($ship AS $key => $ship)
{
  $log->logInfo('Ship: ' . $ship);

  $device_class = device_class_check($ship);

  $ship_aquisuite = $aquisuitetablename[$key];

  $log->logInfo('Ship: ' . $ship);

  $Title = ship_name($ship_aquisuite);

  debugPrint('(init) loop '.$Title.' **************************************');
  debugPrint('(init) Report Year '. $request_year);
  if ($ship_count >= 1)  //TESTING MGR
  {
    if ($annual_report)
    {
      if (isset($VAL_YEAR))
        unset($VAL_YEAR);

      for ($imonth = 0;$imonth< $max_month; $imonth++)
      {
        $repMonth = sprintf("-%02d-01 00:00:00",$imonth+1);
        debugPrint('(init) Annual Month '.$repMonth);
        $VAL_YEAR[]=mod_values($Time_Field, $ship,'', $ship_count, $repMonth,$request_year);
        $VAL = $VAL_YEAR[0];
      }
    }
    else
    {
      debugPrint('(init) request month '.$_REQUEST["month"]);
      $VAL=mod_values($Time_Field, $ship,'', $ship_count, $_REQUEST["month"],$request_year);
    }
  }


  $log->logInfo('aaGraphing from: '.$VAL["date_value_start"].' to: '.$VAL["date_value_end"]);


  switch($module)
  {
  case "mod2":
  case "mod7":
    $COST=mod_cost($Time_Field,$ship,$VAL);
    $VAL_30=mod_values($Time_Field,$ship,'month', $ship_count, $_REQUEST["month"],$request_year);
    $COST_30=mod_cost($Time_Field,$ship,$VAL_30);

    if($module=="mod2")
    {
      $Title = ship_name($aquisuitetablename[$key]);
      $shipsinclass = class_check($aquisuitetablename[$key], $access_level);
      $ship_class_count = count($shipsinclass);
      unset($aquisuitetablename);
      foreach ($shipsinclass AS $aquisuite)
      {
        $aquisuitetablename[] = $aquisuite;

        $sql = "SELECT devicetablename FROM $aquisuite WHERE deviceclass='2'";
        $result = mysql_query($sql);
        if(!$result)
        {
          MySqlFailure("Could not Find devicetable from ".$aquisuite);
        }
        $row = mysql_fetch_row($result);

        $ships_in_class[] = $row[0];
      }
      $Title = ship_name($aquisuitetablename[$key]);

      foreach ($ships_in_class AS $key => $ships)
      {
        $VAL_CLASS=mod_values($Time_Field,$ships,'', $ship_count, $_REQUEST["month"],$request_year);
        $kWh_day_class += $VAL_CLASS["kWh_day"];

        $COST_CLASS=mod_cost($Time_Field, $ships,$VAL_CLASS);
        $Grand_Total_Lay_Day_class += $COST_CLASS["Grand_Total_Lay_Day"];
        $Grand_Total_kWh_class += $COST_CLASS["Grand_Total_kWh"];
      }
      $kWh_day_class = number_format($kWh_day_class/$ship_class_count);
      $Grand_Total_Lay_Day_class = number_format($Grand_Total_Lay_Day_class/$ship_class_count,2);
      $Grand_Total_kWh_class = number_format($Grand_Total_kWh_class/$ship_class_count,2);
    }
    else
    {
      $utility = utility_check($aquisuitetablename[$key]);
      $Franchise_Fee = $COST["U_Franchise_Fee"]*100;
    }

    break;

  case ERMS_Modules::Overview: //mod 0
    // Energy Power and Cost Analysis
  case ERMS_Modules::PowerAndCostAnalysis: //"mod1":
    // Power Meter Data
  case ERMS_Modules::EnergyMeterData: //"mod3":
    $parts = explode('_', $ships[0]);
    $loopname = $parts[0] . '_' . $parts[1];
    $indicator =$loopname;
    $testLogger->logInfo(' MODE 3 Monthly Report ' . $loopname . " Display: ".$VAL["display"]);
    try {
      $selectedField1 = isset($_POST['data1']) ? $_POST['data1'] : 'current';
      $selectedField2 = isset($_POST['data2']) ? $_POST['data2'] : 'power_factor';

      $units1 = EnergyMetrics::get_details($selectedField1);
      $units2 = EnergyMetrics::get_details($selectedField2);


      $field1 = $units1["field"];
      $field2 = $units2["field"];
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

      $summaryReport = fetch_summary_report_mod3($log, $loopname, $startDate, $endDate);
      $testLogger->logDebug("Fields1: " . $field1 . " Field2: ".$field2);
    } catch (Exception $e) {
      $testLogger->logError("Error fetching EnergyMeters: " . $e->getMessage());
    }


    debugPrint('(init) mod values 30 '.$ship);
    if (!$annual_report)
    {
      $VAL_30 = mod_values($Time_Field, $ship, 'month', $ship_count, $_REQUEST["month"],$request_year); //30 day summary for top of graph on individual ship page

      debugPrint('(init) After mod values 30 '.$ship);
      if ($VAL["Avail_Data"] == true)
      {
        //echo 'aGraphing from: '.$VAL_30["date_value_start"].' to: '.$VAL_30["date_value_end"].'<br />';
        debugPrint('(init) Avail_Data TRUE');
        debugPrint('(init)'.$ship.' Lay Days '.$VAL_30["Lay_Days"].' val kWh_Day ',$VAL["kWh_day"]);

        $COST_30 = mod_cost($Time_Field,$ship,$VAL_30);
        debugPrint("(mod_cost): GRAND TOTAL LAY DAY ".$COST_30["Grand_Total_Lay_Day"]);
      }
      else
      {
        $COST_30["Grand_Total_Lay_Day"] = 0;
      }
    }
    else
    {
      //Annual Report
      $valid_months = 0;
      if (isset($monthly_running_totals))
        unset($monthly_running_totals);
      if (isset($monthly_average))
        unset($monthly_average);
      if (isset($COST_YEAR))
        unset($COST_YEAR);

      debugPrint('(init) mod3 Annual Report Year ' . $request_year);
      for ($imonth = 0; $imonth < $max_month; $imonth++)
      {
        $repMonth = sprintf("-%02d-01 00:00:00", $imonth + 1);
        debugPrint('(init) mod3 COST Annual Month ' . $repMonth);
        $COST_YEAR[] = mod_cost($Time_Field,$ship,$VAL_YEAR[$imonth]);
        if ($VAL_YEAR[$imonth]["Lay_Days"] > 0)
        {
          $valid_months++;
        }
        $monthly_running_totals = annualRunningTotals($imonth, $monthly_running_totals,$VAL_YEAR, $COST_YEAR);
        debugPrint('(init) cost/kWh total '.$monthly_running_totals["Grand_Total_kWh"].' Months '.$valid_months);
      }
      debugPrint('(init)1 '.$ship.' Months '.$valid_months);
      $monthly_average = annualAverages($valid_months, $monthly_running_totals);

      if($module == ERMS_Modules::Overview)
      {
        $Ship_kWh_Average[] = $monthly_average["kWh_day"];
        $Ship_Demand[] = $monthly_average["Peak_Demand"];
        $Ship_daily_cost[] = $monthly_average["Grand_Total_Lay_Day"];
        $Ship_laydays[] = $monthly_running_totals["Lay_Days"];
        debugPrint('(init) ANNUAL kwh/day'.$monthly_average["kWh_day"].' Demand='.$monthly_average["Peak_Demand"].' Cost='.$monthly_average["Grand_Total_Lay_Day"].' Days='.$monthly_running_totals["Lay_Days"]);


        $pattern = "/([a-zA-Z0-9])+_([a-zA-Z0-9_-])+/";
        $space=preg_match($pattern, $aquisuitetablename[$key]);
        if($space==1)
        {
          $TITLE=str_replace('_',' ',$aquisuitetablename[$key]);
          $TITLE=substr_replace($TITLE,'',-12);
          $TITLE=trim($TITLE);
        }

        if ($monthly_running_totals["Lay_Days"] == 0)
          $Ship_available[] = 1;
        else
          $Ship_available[] = 0;
        $Ship_Array[] = $TITLE;

        //echo $TITLE." start date: ".$VAL["date_value_start"]." end date: ".$VAL["date_value_end"]." Average kW: ".$VAL["Demand_avg"]." Peak Demand: ".$VAL["Peak_Demand"]."</br>";

        $VAL["Peak_Demand"] += $VAL["Peak_Demand"];
        $VAL_30["kWh_day"] += $VAL_30["kWh_day"];

        $Grand_Total_Lay_Day += $COST_30["Grand_Total_Lay_Day"];
        $Grand_Total_kWh += $COST_30["Grand_Total_kWh"];

        debugPrint('(init) Grand Total Lay Day 30['.$COST_30["Grand_Total_Lay_Day"].'] Grand Total Lay Day['.$Grand_Total_Lay_Day.']');
        debugPrint('(init) Grand Total kWh 30['.$COST_30["Grand_Total_kWh"].'] Grand Total kWh['.$Grand_Total_kWh.']');

        $Ships_Sum += $VAL["kW_sum"];
        $Ships_Sum_Count += $VAL["kW_count"];
      }
    }

    break;

  case "mod4":
    $VAL_30=mod_values($Time_Field,$ship,'month', $ship_count,  $_REQUEST["month"],$request_year);

    if(!empty($VAL_30["2_CO2_day"]))
    {
      $CO2_Current = number_format((($VAL["2_CO2_day"]-$VAL_30["2_CO2_day"])/$VAL_30["2_CO2_day"])*100);
    }
    break;
    // Water Meter Data
  case ERMS_Modules::WaterMeterData:
    /////

    /////


    break;
    // Monthly Reports
  case ERMS_Modules::MonthlyReports: //"mod6":
    $log->logInfo('mode 6a<br/>');
    debugPrint('(init) MODE 6 Monthly Report ' . $ship);
    // update 2024
    $testLogger->logInfo('mode 6');
    try {
      $parts = explode('_', $ships[0]);
      $loopname = $parts[0] . '_' . $parts[1];
      $indicator =$loopname;
            
      $year = isset($_REQUEST["year"]) ? intval($_REQUEST["year"]) : date('Y');
      $month = isset($_REQUEST["month"]) ? intval($_REQUEST["month"]) : $month = date('m');
            
      if ($month == 0) {
        $month = date('m'); 
      } else {
        $month = abs($month); 
      }   
        
      $shipData = fetch_monthly_report_mod6($testLogger, $loopname, $year, $month);
            
      $performance = fetch_last_30_days($testLogger, $loopname);
      $utility = utility_check($ships[0]);
      $utilityData = db_fetch_utility_rate($logger, $utility);
      $utilityRate = create_utility_class($logger,$utilityData[0]);
      $taxesAddFees = $utilityRate->getCustomerCharge();
      $totalCost = $shipData["TotalEnergyCharges"] + $shipData["TotalDemandCharges"] +$taxesAddFees ;

    } catch (Exception $e) {
      $testLogger->logError("Error fetching MonthlyReports: " . $e->getMessage());
    }
    break;
  }
}

if($ship_count==1){
  switch($module)
  {
  case "mod2":
  case "mod7":
    if($module=="mod2")
    {
      $graph=erms_bar_graph($Time_Field,$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
    }
    else
    {
      $data = array($COST["Total_kWh_Cost"],$COST["Total_kW_Cost"],$COST["Taxes_Add_Fees"]);
      $graph=erms_pie_graph($Time_Field,$data,$ship,$VAL["date_value_start"],$VAL["date_value_end"],$VAL["report_month"]);
    }
    break;
  case "mod4":
    $graph=erms_bar_graph($Time_Field,$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
    break;
  case ERMS_Modules::Overview: // mod0
    $ship_data = $ships_data[$aquisuitetablename[$key]];
    $formatted_month = date_parse($VAL["report_month"]);
    $metrics = array("kWh_day", "Peak_Demand", "Grand_Total_Lay_Day");

    if(!$annual_report) {
      $save_startdate = date('F j, Y G:i:s T Y',strtotime($VAL["date_value_start"])); //save original dates for bar chart title
      $save_enddate = date('F j, Y G:i:s T Y',(strtotime($VAL["date_value_end"])));
      $baselines = get_monthly_baselines($ship_data["owner"], $ship_data["ship_class"], $ship_group, $metrics, $formatted_month['month']);
      $Ship_kWh_Average = [$VAL["kWh_day"]];
      $Ship_Demand = [($VAL["Peak_Demand"]*1)];
      $Ship_daily_cost = [$COST_30["Grand_Total_Lay_Day"]];
      $values = $VAL;
      $cost = $COST_30;
      $log->logInfo("Got baseline data: [kWh_day]".$baselines["kWh_day"]);
    } else {
      $baselines = get_annual_baselines($ship_data["owner"], $ship_data["ship_class"], $ship_group, $metrics);
      $Ship_kWh_Average = [$Ship_kWh_Average[0]];
      $Ship_Demand = [$Ship_Demand[0]];
      $Ship_daily_cost = [$Ship_daily_cost[0]];
      $values = [
        "kWh_day" => $Ship_kWh_Average,
        "Peak_Demand" => $Ship_Demand,
        "Lay_Days" => $Ship_laydays
      ];
      $cost = [
        "Grand_Total_Lay_Day" => $Ship_daily_cost
      ];
    }
      $Ship_kWh_Average_Baseline = [($baselines["kWh_day"]*1)];
      $Ship_kWh_Average_Baseline_G1 = [($baselines["kWh_day"]*0.9)];
      $Ship_kWh_Average_Baseline_G2 = [($baselines["kWh_day"]*0.8)];
      $Ship_Demand_Baseline = [($baselines["Peak_Demand"]*1)];
      $Ship_Demand_Baseline_G1 = [($baselines["Peak_Demand"]*0.9)];
      $Ship_Demand_Baseline_G2 = [($baselines["Peak_Demand"]*0.8)];
      $Ship_daily_cost_baseline = [($baselines["Grand_Total_Lay_Day"]*1)];
      $Ship_daily_cost_baseline_g1 = [$baselines["Grand_Total_Lay_Day"]*0.9];
      $Ship_daily_cost_baseline_g2 = [$baselines["Grand_Total_Lay_Day"]*0.8];
      
      $display =isset($_REQUEST['month']) ? $_REQUEST['month'] : "month";

      $testLogger->logDebug("Mod6: ".$display );
      $parts = explode('_', $ships[0]);
      $loopname = $parts[0] . '_' . $parts[1];
    switch ($_REQUEST["month"] ) {
      case "month":
        try {

          $save_enddate = date('F j, Y G:i');
          $save_startdate = date('F j, Y G:i', strtotime('-30 days'));
          $Ship_available = [];

          $Ship_kWh_Average = [];
          $Ship_Demand = [];
          $Ship_daily_cost = [];
          $ship_data = fetch_last_30_days($testLogger, $loopname);
          if ($ship_data["avg_cost"] == 0) {
              $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }
          $Ship_kWh_Average[] = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);       
          $Ship_daily_cost[]= intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
          
        } catch (Exception $e) {
            $testLogger->logError("Error fetching data for the last 30 days: " . $e->getMessage());
        }
        break;     
      case "annual":
        try {
          $save_enddate = date('F j, Y G:i');
          $save_startdate = date('F j, Y G:i', strtotime('-1 year'));
          $Ship_available = [];

          $Ship_kWh_Average = [];
          $Ship_Demand = [];
          $Ship_daily_cost = [];
          $ship_data = fetch_Annual($testLogger, $loopname);

          if ($ship_data["avg_cost"] == 0) {
              $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }

          $Ship_kWh_Average = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);
          $Ship_daily_cost = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
          
        } catch (Exception $e) {
          $testLogger->logError("Error fetching data for the annual report: " . $e->getMessage());
        }
      break;
      default:
        try {
          $year = isset($_REQUEST["year"]) ? intval($_REQUEST["year"]) : date('Y');
          $month = isset($_REQUEST["month"]) ? intval($_REQUEST["month"]) : date('m');
          
          $save_startdate = date('F j, Y G:i', mktime(0, 0, 0, $month, 1, $year));
          $save_enddate = date('F j, Y G:i', mktime(23, 59, 59, $month + 1, 0, $year)); // 0th day of the next month gives us the last day of the current month
          
          $Ship_available = [];

          $Ship_kWh_Average = [];
          $Ship_Demand = [];
          $Ship_daily_cost = [];
          $ship_data = fetch_month_of_specific_year($testLogger, $loopname, $_REQUEST["year"],$_REQUEST["month"] );

          if ($ship_data["avg_cost"] == 0) {
              $Ship_available[] = 1;
          } else {
              $Ship_available[] = 0;
          }
          $Ship_kWh_Average[]= intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
          $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);
          $Ship_daily_cost[] = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
        } catch (Exception $e) {
          $testLogger->logError("Error fetching data for the default report: " . $e->getMessage());
        }
      break;
    }    
    $values = [
      "kWh_day" => $Ship_kWh_Average,
      "Peak_Demand" => $Ship_Demand,
      "Lay_Days" => $ship_data["days"]
    ];
    $cost = [
      "Grand_Total_Lay_Day" => $Ship_daily_cost
    ];


    
    $graph = [
      "ship" => $Title,
      "dates" => [$save_startdate, $save_enddate],
      "data" => [
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
      ]
    ];
    $metrics = [
      "values" => $values,
      "cost" => $cost
    ];
    break;
    // Energy Power and Cost Analysis
  case ERMS_Modules::PowerAndCostAnalysis: //"mod1":
    $parts = explode('_', $ships[0]);
    $loopname = $parts[0] . '_' . $parts[1]; 
    $startDate = date('F j, Y');
    $testLogger->logInfo("Mod1 ".$startDate);
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
    $testLogger->logInfo("Mod1 ".$loopname." display: ".$VAL["display"]." range:".$startDate." -- ".$endDate);
    $timezone = "America/New_York";

    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    if ($startTimestamp > $endTimestamp) {
      throw new Exception('Start date must be earlier than end date');
    }
    $intervalSeconds = round(($endTimestamp - $startTimestamp) / 286);
    $log_interval = $intervalSeconds*1000;
    $dates = getEvenlySpacedDates($startDate, $endDate, $intervalSeconds);
    $data = fetch_data_mod1($testLogger,$loopname, $startDate, $endDate );
    $peak_times = array();

    $graph=[
      "times" => $dates,
      "peak_times" => $peak_times,
      "timezone" => $timezone,
      "log_interval" => $log_interval,
      "date_start" => $dates[0],
      "date_end" => $dates[count($dates) - 1],
      "data" => array(
        "y1" => $data["realPower"],
        "y2" => $data["estimatedPower"]
      )
    ];
    break;
    // Energy Meter Data
  case ERMS_Modules::EnergyMeterData: //"mod3":
    $parts = explode('_', $ships[0]);
    $loopname = $parts[0] . '_' . $parts[1];
    $testLogger->logDebug("Mod3: ".$loopname );
    
    $ship_data = $ships_data[$aquisuitetablename[$key]];
    debugPrint('(init) erms line graph: ['.$VAL["date_value_start"].'] to: ['.$VAL["date_value_end"].'] (time meter end) ['.$VAL["Time_Meter_End"].']');
    $graph=mod3_graph($ship_data,$VAL["date_value_start"],$VAL["date_value_end"]);
    break;
    // Potable Water Meter
  case ERMS_Modules::WaterMeterData:   // mod5
    ////put the graph in when data is available
    //$graph=erms_line_graph($Time_Field,$ship,$VAL["date_value_start"],$VAL["Time_Meter_End"],$VAL["date_value_end"]);
    break;
    // Monthly Reports
  case ERMS_Modules::MonthlyReports: //"mod6":
    // $log->logInfo('mode 6b<br/>');
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
    break;
  }
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

for ($imonth = 0; $imonth < $max_month; $imonth++)
{
  if ($annual_report)
    $VAL = &$VAL_YEAR[$imonth];
  foreach ($VAL AS $key => $value)
  {
    $sub_key = substr($key, 0, 1);
    $time_key = substr($key, -4, 4);

    if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
    {
      $VAL[$key] = number_format($value, $sub_key);
    }
    else if (is_numeric($value))
    {
      $VAL[$key] = number_format($value);
      if ($key == "Lay_Days")
        $VAL[$key] = number_format($value, 2); //keep 2 point precision
      //debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value);

    }

    if ($time_key == "Time")
    {
      $VAL[$key] = date('Y-m-d H:i:s', strtotime($value."UTC"));
      //debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value."UTC".' local '.$value);
    }
  }
  if ($annual_report)
    $VAL["report_month"] = "Annual";
}

debugPrint('(init) reset month: '.$VAL["save_startdate"].' date_value_start '.$VAL["date_value_start"]. 'report month '.$VAL["report_month"]);

if ($annual_report)
{
  foreach ($monthly_average AS $key => $value)
  {
    $sub_key = substr($key, 0, 1);

    if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
    {
      $monthly_average[$key] = number_format($value, $sub_key);
      //debugPrint('(init) key '.$key.' '.$monthly_average[$key].' value '.$value);

    }
    else if (is_numeric($value))
    {
      $monthly_average[$key] = number_format($value,2);
      // debugPrint('(init) key '.$key.' '.$monthly_average[$key].' value '.$value);
    }
  }
  foreach ($monthly_running_totals AS $key => $value)
  {
    $sub_key = substr($key, 0, 1);

    if ($sub_key > 0 && $sub_key < 3 && is_numeric($value))
    {
      $monthly_running_totals[$key] = number_format($value, $sub_key);
    }
    else if (is_numeric($value))
    {
      $monthly_running_totals[$key] = number_format($value,2);
      debugPrint('(init) key '.$key.' '.$VAL[$key].' value '.$value);
    }
  }
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

for ($imonth = 0; $imonth < $max_month; $imonth++)
{
  if ($annual_report)
    $COST = &$COST_YEAR[$imonth];
  if (isset($COST))
  {
    foreach ($COST AS $key => $value)
    {
      $sub_U = substr($key, 0, 1);
      if ($sub_U != "U" && is_numeric($value))
      {
        $COST[$key] = number_format($value, 2);
      }
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
