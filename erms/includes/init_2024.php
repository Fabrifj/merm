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
        $metrics = array("kWh_day", "Peak_Demand", "Grand_Total_Lay_Day");
        $parts = explode('_', $ships[0]);
        $loopname = $parts[0] . '_' . $parts[1];
        $Ship_kWh_Average_Baseline[] = 0;
        $Ship_kWh_Average_Baseline_G1[] = 0;
        $Ship_kWh_Average_Baseline_G2[] = 0;
        $Ship_Demand_Baseline[] = 0;
        $Ship_Demand_Baseline_G1[] = 0;
        $Ship_Demand_Baseline_G2[] = 0;
        $Ship_daily_cost_baseline[] = 0;
        $Ship_daily_cost_baseline_g1[] = 0;
        $Ship_daily_cost_baseline_g2[] = 0;
          // Fetch values from Standard_ship_records
        $_REQUEST["month"] =isset($_REQUEST["month"]) ? $_REQUEST["month"] : "month";
        $testLogger->logDebug($_REQUEST["month"] );
    

        switch ($_REQUEST["month"] ) {
            $values = [
                "kWh_day" => 0,
                "Peak_Demand" => 0,
                "Lay_Days" => 0
              ];
              $cost = [
                "Grand_Total_Lay_Day" => 0
              ];
              
            case "month":
                try {

                    $save_startdate = date('F j, Y G:i:s T Y');
                    $save_enddate = date('F j, Y G:i:s T Y', strtotime('-30 days'));
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
                    $Ship_kWh_Average = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
                    $Ship_Demand = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);       
                    $Ship_daily_cost= intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
                    
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
                    
                    $save_startdate = date('F j, Y G:i:s T', mktime(0, 0, 0, $month, 1, $year));
                    $save_enddate = date('Y-m-t', strtotime($save_startdate));
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
                    $Ship_kWh_Average[] = intval(isset($ship_data["avg_kwH"]) ? $ship_data["avg_kwH"] : 0);
                    $Ship_Demand[] = intval(isset($ship_data["avg_kw"]) ? $ship_data["avg_kw"] : 0);
                    $Ship_daily_cost[] = intval((isset($ship_data["avg_cost"]) ? $ship_data["avg_cost"] : 0));
                } catch (Exception $e) {
                    $testLogger->logError("Error fetching data for the default report: " . $e->getMessage());
                }
                break;
        }
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

            $utility = utility_check($ships[0]);
            $utilityData = db_fetch_utility_rate($logger, $utility);
            $utilityRate = create_utility_class($logger,$utilityData[0]);

            $taxesAddFees = $utilityRate->getCustomerCharge();
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
