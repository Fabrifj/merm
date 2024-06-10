<?php
//
//....................................KLogger...............................
require '../erms/includes/KLogger.php';
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
//.....................................End KLogger..........................

error_reporting (E_ALL ^ E_NOTICE);

require_once('../erms/includes/debugging.php');
require_once('../erms/includes/access_control.php');
include '../erms/includes/data_methods.php';
include '../erms/includes/energy_methods.php';
include_once ('../conn/mysql_connect-all.php');

function baseline_month_format($baseline_start_date, $days, $starter=false) {
    $start_date = date_create($baseline_start_date);
    $end_date = date_create($baseline_start_date);
    if(!$starter) {
      date_add($start_date, date_interval_create_from_date_string('1 year'));
      date_add($end_date, date_interval_create_from_date_string('1 year'));
    }
    date_add($end_date, date_interval_create_from_date_string("$days days"));
    // @TODO Currently assuming 5 min log period. Need to update to get log interval
    date_sub($end_date, date_interval_create_from_date_string('5 minutes'));
    $start_date = date_format($start_date, 'Y-m-d H:i:s');
    $end_date = date_format($end_date, 'Y-m-d H:i:s');

    return [
      "start_date" => $start_date,
      "end_date" => $end_date
    ];
}

function baseline_span_month($start_date, $end_date, $table) {
    $start_date_obj = date_create($start_date);
    $end_date_obj = date_create($end_date);
    $diff = date_diff($start_date_obj, $end_date_obj);
    $days_worth = $diff->format("%a") + 1;

    $sql="SELECT IF(month(time) <= month('$start_date'), DATE_SUB('$start_date', INTERVAL YEAR('$start_date') - year(time) YEAR), DATE_SUB('$start_date', INTERVAL year('$start_date') - year(DATE_ADD(time, INTERVAL 1 YEAR)) YEAR)) AS baseline_start_date from $table order by time limit 1";
    debugPrint("(baseline_span_month sql)".$sql);
    $query=mysql_query($sql);
    $row=mysql_fetch_row($query);
    $baseline_start_date = $row[0];
    $years_worth = (date('Y', strtotime($start_date)) - date('Y', strtotime($baseline_start_date))) + 1;
    $baseline_month_dates = baseline_month_format($baseline_start_date, $days_worth, true);
    $baseline_month_dates["years_worth"] = $years_worth;
    $baseline_month_dates["days_worth"] = $days_worth;

    return $baseline_month_dates;
}


function baseline_month_day_count($time, $table, $start_date, $end_date, $voltage_field, $max_value) {

  $baseline_date_info = baseline_span_month($start_date, $end_date, $table);
  $start_date = $baseline_date_info["start_date"];
  $end_date = $baseline_date_info["end_date"];
  $days_worth = $baseline_date_info["days_worth"];
  $baseline_lay_days = array();
  debugPrint("years worth: ".$baseline_date_info["years_worth"]);

  $real_power_limit = '';
  $hasRealPower = checkRealPower($table);

  if($hasRealPower) {
    $real_power_limit = "AND (`Real_Power`>10)";
  }

  for($i = 0; $i < $baseline_date_info["years_worth"]; $i++) {
    $sql="SELECT COUNT(`$time`) AS `layday` FROM $table WHERE (`$time` BETWEEN '$start_date' AND '$end_date') $real_power_limit";
    $RESULT=mysql_query($sql);

      	debugPrint('(baseline_day_count): '.$sql);

    if(!$RESULT) {
           MySqlFailure("day count failed ".$time);
    }

    $row=mysql_fetch_row($RESULT);
    debugPrint('layday value: '.$row[0]);
    $days = $row[0]/288.00;

    $baseline_lay_days[$start_date.','.$end_date] = $days;
    $nextDates = baseline_month_format($start_date, $days_worth);
    $start_date = $nextDates["start_date"];
    $end_date = $nextDates["end_date"];
  }

  return $baseline_lay_days;
}
/**
 * baseline_values()
 */
function baseline_values(
    $time,
    $alias,
    $time_alias,
    $operand,
    $value,
    $table,
    $start_date,
    $end_date
    )
{

    global $device_class;
    global $log;
    debugPrint("(baseline_values): value: $value, start_date: $start_date, end_date: $end_date");
    $baseline_date_info = baseline_span_month($start_date, $end_date, $table);
    $start_date = $baseline_date_info["start_date"];
    $end_date = $baseline_date_info["end_date"];
    $days_worth = $baseline_date_info["days_worth"];
    $baseline_vals = array();
    $baseline_times = array();
    $log->logInfo("(baseline_values): [start_date] $start_date, [end_date] $end_date");
    for($i = 0; $i < $baseline_date_info["years_worth"]; $i++) {
      $real_power_limit = '';
      $hasRealPower = checkRealPower($table);

      if($hasRealPower) {
        $real_power_limit = "AND (`Real_Power`>10)";
      }

      if($value=="Power_Factor" && $operand!=='AVG')
      {
          $sql="SELECT DATE_FORMAT($time, '%a %b %e, %Y, %H:%i:%S') AS `cdate`, $value, `time` FROM $table WHERE ($value=(SELECT $operand($value) FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit AND ($value>0))) AND ($time BETWEEN '$start_date' AND '$end_date') ORDER BY TIME LIMIT 1";
      }
      else if($operand!=='AVG' && $operand!=='SUM' && $operand!=='COUNT')
      {
          $sql="SELECT DATE_FORMAT($time, '%a %b %e, %Y, %H:%i:%S') AS `cdate`, $value, `time` FROM $table WHERE ($value=(SELECT $operand($value) FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit)) AND ($time BETWEEN '$start_date' AND '$end_date') ORDER BY TIME LIMIT 1";
      }
      else
      {
          $sql="SELECT $operand($value) AS $alias FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit";
      }
  	$RESULT=mysql_query($sql);

      debugPrint("(baseline values A) ".$sql);

  	if(!$RESULT)
  	{
          continue;
  	} else {
          $value_result=mysql_fetch_array($RESULT);
          $key = $alias != ''? $alias: $value;
          $time_key = $time_alias != ''? $time_alias: 'cdate';

          if($value_result[$key]) {
            debugPrint("(baseline_values) appending next baseline val ".$value_result[$key]);
            $baseline_vals[$start_date.','.$end_date] = $value_result[$key];

            if($value_result[$time_key]) {
              debugPrint("(baseline_values) appending next baseline time ".$value_result[$time_key]);
              $baseline_times[$start_date.','.$end_date] = $value_result[$time_key];
            }
          }
  	}

        $nextDates = baseline_month_format($start_date, $days_worth);
        $start_date = $nextDates["start_date"];
        $end_date = $nextDates["end_date"];
    }

    if(count($baseline_times) == 0) {
      return $baseline_vals;
    }

    return [
      "values" => $baseline_vals,
      "times" => $baseline_times
    ];
}

function baseline_billvalues($baseline_peak_demand_time, $baseline_peak_demand_values, $baseline_off_peak_demand_values, $Time_Field, $ship, $Power_Factor_Field) {
  $baseline_2_pf_demand = array();
  $baseline_pf_billed_demand = array();
  $baseline_peak_billed_demand = array();
  $baseline_off_peak_billed_demand = array();

  foreach($baseline_peak_demand_time as $time_range_key => $peak_demand_time) {
    $input_time = date('Y-m-d H:i:s',strtotime($peak_demand_time));
    $bill_demand = billvalues($Time_Field, $ship, $input_time);
    $peak_demand = $baseline_peak_demand_values[$time_range_key];
    $off_peak_demand = $baseline_off_peak_demand_values[$time_range_key];
    $bill_demand_values = calculate_bill_values($bill_demand, $peak_demand, $off_peak_demand, $Power_Factor_Field);
    $baseline_2_pf_demand[$time_range_key] = $bill_demand_values["2_PF_Demand"];
    $baseline_pf_billed_demand[$time_range_key] = $bill_demand_values["PF_Billed_Demand"];
    $baseline_peak_billed_demand[$time_range_key] = $bill_demand_values["Peak_Billed_Demand"];
    $baseline_off_peak_billed_demand[$time_range_key] = $bill_demand_values["Off_Peak_Billed_Demand"];
  }

  return array(
    "baseline_2_pf_demand" => $baseline_2_pf_demand,
    "baseline_pf_billed_demand" => $baseline_pf_billed_demand,
    "baseline_peak_billed_demand" => $baseline_peak_billed_demand,
    "baseline_off_peak_billed_demand" => $baseline_off_peak_billed_demand
  );
}
/**
 * baseline_cost
 */

function baseline_cost($utility, $val, $ship, $Time_Field) {
  global $log;

  $baseline_peak_demand_values = $val["baseline_peak_demand_values"];
  $baseline_peak_demand_times = $val["baseline_peak_demand_times"];
  $baseline_peak_billed_demand = $val["baseline_peak_billed_demand"];
  $baseline_off_peak_billed_demand = $val["baseline_off_peak_billed_demand"];
  $baseline_off_peak_demand_values = $val["baseline_off_peak_demand_values"];
  $baseline_off_peak_demand_times = $val["baseline_off_peak_demand_times"];
  $baseline_month_lay_days = $val["baseline_month_lay_days"];
  $baseline_peak_kwh_totals = $val["baseline_peak_kwh_totals"];
  $baseline_off_peak_kwh_totals = $val["baseline_off_peak_kwh_totals"];
  $baseline_kvar_demand_values = $val["baseline_kvar_demand_values"];

  $baseline_cost = array();
  foreach($baseline_month_lay_days as $lay_day_date_ref => $Lay_Days) {
    debugPrint("(baseline_cost): Lay_Days $Lay_Days");
    if($Lay_Days > 0) {
      // We're storing our baseline values with key format [date_value_start,date_value_end]
      $dates = explode(",",$lay_day_date_ref);
      $date_value_start = $dates[0];
      $date_value_end = $dates[1];
      $cost = utility_schedule_rates($utility, $date_value_start, $date_value_end);
      $Peak_Demand_Time = $baseline_peak_demand_times[$lay_day_date_ref];
      $Peak_Demand = $baseline_peak_demand_values[$lay_day_date_ref];
      $Peak_Billed_Demand = $baseline_peak_billed_demand[$lay_day_date_ref];
      $Off_Peak_Billed_Demand = $baseline_off_peak_billed_demand[$lay_day_date_ref];
      $Off_Peak_Demand_Time = $baseline_off_peak_demand_times[$lay_day_date_ref];
      $Off_Peak_Demand = $baseline_off_peak_demand_values[$lay_day_date_ref];
      $Peak_kWh_Total = $baseline_peak_kwh_totals[$lay_day_date_ref];
      $Off_Peak_kWh_Total = $baseline_off_peak_kwh_totals[$lay_day_date_ref];
      $kVAR_Demand = $baseline_kvar_demand_values[$lay_day_date_ref];
      $OPD_mtime = idate('m', strtotime($Off_Peak_Demand_Time));

      switch($utility) {
        case "Virginia_Dominion_Rates":

            $Utility=$cost['Utility'];
            $Rate=$cost['Rate'];
            $COST["U_Customer_Charge"]=$cost['Customer_Charge'];
            $COST["U_Demand_rkVA"]=$cost['Demand_rkVA'];
            $COST["U_Distribution_Demand"]=$cost['Distribution_Demand'];
            $COST["U_ESS_Adjustment_Charge"]=$cost['ESS_Adjustment_Charge'];
            $COST["U_Peak_kWh"]=$cost['Peak_kWh'];
            $COST["U_Off_Peak_kWh"]=$cost['Off_Peak_kWh'];
            $COST["U_Peak_kW"]=$cost['Peak_kW'];
            $COST["U_Off_Peak_kW"]=$cost['Off_Peak_kW'];
            $COST["U_Fuel_Charge"]=$cost['Fuel_Charge'];
            $COST["U_Rider_R_Peak_kW"]=$cost['Rider_R_Peak_kW'];
            $COST["U_Rider_S_Peak_kW"]=$cost['Rider_S_Peak_kW'];
            $COST["U_Rider_T_Peak_kW"]=$cost['Rider_T_Peak_kW'];
            $COST["U_Rider_R_Credit"]=$cost['Rider_R_Credit'];
            $COST["U_Rider_S_Credit"]=$cost['Rider_S_Credit'];
            $COST["U_Rider_T_Credit"]=$cost['Rider_T_Credit'];
            $COST["U_Sales_kWh"]=$cost['Sales_kWh'];
            $COST["U_Tax_Rate_1"]=$cost['Tax_Rate_1'];
            $COST["U_Tax_Rate_2"]=$cost['Tax_Rate_2'];
            $COST["U_Tax_Rate_3"]=$cost['Tax_Rate_3'];
            $COST["U_Utility_tax"]=$cost['Utility_tax'];


            #### COST CALCULATIONS ####

            // The following cost calculations are based on
            // The Utility rate structure and the values
            // calculated above.

            $Peak_Month_Days = idate(t, strtotime($Peak_Demand_Time))/30;

            // kW Peak and Off-Peak Cost
            $COST["Peak_kW_Demand_Cost"] = max(545/3, $Peak_Demand, 100)*$COST["U_Peak_kW"]*$Peak_Month_Days;
            $COST["ESS_Adj_Cost"] = $Peak_Demand*$COST["U_ESS_Adjustment_Charge"]*$Peak_Month_Days;
            $COST["Other_ESS_Riders"] = $Peak_Demand*($COST["U_Rider_R_Peak_kW"]+$COST["U_Rider_S_Peak_kW"]+$COST["U_Rider_T_Peak_kW"])+$Peak_Demand*($COST["U_Rider_R_Credit"]+$COST["U_Rider_S_Credit"])*$Peak_Month_Days;
            $COST["Peak_kW_Cost"] = $COST["Peak_kW_Demand_Cost"]+$COST["ESS_Adj_Cost"]+$COST["Other_ESS_Riders"];

            $COST["Off_Peak_kW_Cost"] = 0;

            if($Off_Peak_Demand-($Peak_Demand*.9)>0)
            {
                $COST["Off_Peak_kW_Cost"] = $COST["U_Off_Peak_kW"]*($Off_Peak_Demand-($Peak_Demand*.9));
            }

            $COST["kVAR_Demand_Cost"] = $COST["U_Demand_rkVA"]*$kVAR_Demand*$Peak_Month_Days;
            $COST["Distribution_Demand_Cost"] = $COST["U_Distribution_Demand"]*$Peak_Demand*$Peak_Month_Days;
            $COST["Other_Demand_Cost"] = $COST["kVAR_Demand_Cost"]+$COST["Distribution_Demand_Cost"];

            $COST["Total_kW_Cost"] = $COST["Peak_kW_Cost"]+$COST["Off_Peak_kW_Cost"]+$COST["Other_Demand_Cost"];

            // kWh Peak and Off-Peak Cost
            if(!empty($Lay_Days))
            {
                $COST["Peak_kWh_Cost"] = $Peak_kWh_Total*$COST["U_Peak_kWh"];
                $COST["Off_Peak_kWh_Cost"] = $Off_Peak_kWh_Total*$COST["U_Off_Peak_kWh"];
                $kWh_Total = $Peak_kWh_Total+$Off_Peak_kWh_Total;
            }
            $COST["Fuel_Cost"] = $COST["U_Fuel_Charge"]*$kWh_Total;
            $COST["Sales_Cost"] = $COST["U_Sales_kWh"]*$kWh_Total;

            if($kWh_Total>50000)
            {
                $COST["Consumption_Tax"] = ($kWh_Total-50000)*$COST["U_Tax_Rate_3"]+(50000-2500)*$COST["U_Tax_Rate_2"]+2500*$COST["U_Tax_Rate_1"];
            }
            else if($kWh_Total>2500 && $kWh_Total< 50000)
            {
                $COST["Consumption_Tax"] = ($kWh_Total-2500)*$COST["U_Tax_Rate_2"]+2500*$COST["U_Tax_Rate_1"];
            }
            else
            {
                $COST["Consumption_Tax"] = $kWh_Total*$COST["U_Tax_Rate_1"];
            }
            $COST["Other_Energy_Cost"] = $COST["Fuel_Cost"]+$COST["Sales_Cost"]+$COST["Consumption_Tax"];

            $COST["Total_kWh_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Off_Peak_kWh_Cost"]+$COST["Other_Energy_Cost"];

            // Sub Totals before taxes and other fees.
            $COST["Total_Peak_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Peak_kW_Cost"];
            $COST["Total_Off_Peak_Cost"] = $COST["Off_Peak_kWh_Cost"]+$COST["Off_Peak_kW_Cost"];
            $COST["Total_Other_Cost"] = $COST["Other_Demand_Cost"]+$COST["Other_Energy_Cost"];

            $COST["Total_Cost"] = $COST["Total_Peak_Cost"]+$COST["Total_Off_Peak_Cost"]+$COST["Total_Other_Cost"];

            //Taxes and Other Fees
            $COST["Basic_Customer_Cost"] = $COST["U_Customer_Charge"]*$Peak_Month_Days;
            $COST["Taxes_Add_Fees"] = $COST["Basic_Customer_Cost"]+$COST["U_Utility_tax"];
            $COST["Taxes_and_Other"] = $COST["Taxes_Add_Fees"]+$COST["Total_Other_Cost"];

            //Grand Total After Taxes and Other Fees
            $COST["Grand_Total_Cost"] = $COST["Total_Cost"]+$COST["Taxes_Add_Fees"];
          break;
        case "SCE&G_Rates":
            $Peak_kWh_Field="Peak_kWh";

            $Utility = $cost[0];
            $Rate = $cost[1];
            $COST["U_Customer_Charge"] = $cost[2];
            $COST["U_Summer_Peak_Demand_kW"]= $cost[3];
            $COST["U_Non_Summer_Peak_Demand_kW"] = $cost[4];
            $COST["U_Off_Peak_Demand_kW"] = $cost[5];
            $COST["U_Summer_Peak_kWh"] = $cost[6];
            $COST["U_Non_Summer_Peak_kWh"] = $cost[7];
            $COST["U_Off_Peak_kWh_rate"] = $cost[8];
            $COST["U_Franchise_Fee"] = $cost[9];
            $COST["U_Summer_Start"] = idate(m,strtotime($cost[10]));
            $COST["U_Summer_End"] = idate(m,strtotime($cost[11]));
            $summer_start_date = $cost[10];
            $summer_end_date = $cost[11];

            if($OPD_mtime>=$COST["U_Summer_Start"] && $OPD_mtime<$COST["U_Summer_End"]) //summer
            {
                $COST["Peak_kW_Cost"] = $Peak_Billed_Demand*$COST["U_Summer_Peak_Demand_kW"];
            }
            else
            {
                $last_summer_high_demand_point = get_high_point($ship,$summer_start_date,$summer_end_date);
                $percent_last_summer = $last_summer_high_demand_point * .80; //get 80% of last summers high point;
                $billed_demand_value = max($Peak_Billed_Demand, $percent_last_summer); //billed demand value is greater of max demand for current time period or 80 percent of last summer's high demand
                $COST["Peak_kW_Cost"] = $billed_demand_value*$COST["U_Non_Summer_Peak_Demand_kW"]; //non-summer
                debugPrint('(energy_method:mod_cost) last summer high'.$last_summer_high_demand_point.' 80 percent= '.$percent_last_summer.' current demand '.$Peak_Billed_Demand.' using demand value'.$billed_demand_value);

            }

            $COST["Off_Peak_kW_Cost"] = $Off_Peak_Billed_Demand*$COST["U_Off_Peak_Demand_kW"];
            $COST["Total_kW_Cost"] = $COST["Peak_kW_Cost"]+$COST["Off_Peak_kW_Cost"];
            $COST["Other_Demand_Cost"] = 0;

            $COST["Peak_kWh_Cost"] = kWh_cost
                                    (
                                        'time',
                                        $date_value_start,
                                        $date_value_end,
                                        $Peak_kWh_Field,
                                        $ship,
                                        $COST["U_Summer_Start"],
                                        $COST["U_Summer_End"],
                                        $COST["U_Summer_Peak_kWh"],
                                        $COST["U_Non_Summer_Peak_kWh"],
                                        $Peak_kWh_Total
                                    );
            $COST["Off_Peak_kWh_Cost"] = $Off_Peak_kWh_Total*$COST["U_Off_Peak_kWh_rate"];
            $COST["Other_Energy_Cost"] = 0;

            debugPrint('mod_cost: Lay Days '.$Lay_Days);

            if(!empty($Lay_Days))
            {
                $COST["Total_kWh_Cost"] = ($COST["Peak_kWh_Cost"]+$COST["Off_Peak_kWh_Cost"]);
                $kWh_Total = ($Peak_kWh_Total+$Off_Peak_kWh_Total);
            }

            $COST["Total_Peak_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Peak_kW_Cost"];

            $COST["Total_Off_Peak_Cost"] = $COST["Off_Peak_kWh_Cost"]+$COST["Off_Peak_kW_Cost"];

            $COST["Total_Cost"] = $COST["Total_kW_Cost"]+$COST["Total_kWh_Cost"];

            if ($Lay_Days > 0)
            {
                $COST["Sub_Total_Cost"] = $COST["Total_Cost"]+$COST["U_Customer_Charge"];

                //Taxes & other fees
                $COST["Franchise_Fee_Cost"] = $COST["U_Franchise_Fee"]*$COST["Sub_Total_Cost"];
                $COST["Taxes_Add_Fees"] = $COST["Franchise_Fee_Cost"]+$COST["U_Customer_Charge"];
                $COST["Taxes_and_Other"] = $COST["Taxes_Add_Fees"];
            }

            //Grand Total
            $COST["Grand_Total_Cost"] = $COST["Total_Cost"]+$COST["Taxes_Add_Fees"];
            debugPrint('mod_cost: Grand total cost '.$COST["Grand_Total_Cost"].' Total Cost '.$COST["Total_Cost"].' taxes and other '. $COST["Taxes_and_Other"]." franchise ". $COST["Franchise_Fee_Cost"]);
            break;
      }

	if ($COST["Grand_Total_Cost"] > 0)
        {
	    $COST["Taxes_and_Other_Rate"] = ($COST["Taxes_and_Other"]/$COST["Grand_Total_Cost"])*100;
            $COST["Total_Demand_Rate"] = ($COST["Total_kW_Cost"]/$COST["Grand_Total_Cost"])*100;
	    $COST["Total_Energy_Rate"] = ($COST["Total_kWh_Cost"]/$COST["Grand_Total_Cost"])*100;
        }
        else
         {
	    $COST["Taxes_and_Other_Rate"] = 0;
            $COST["Total_Demand_Rate"] = 0;
	    $COST["Total_Energy_Rate"] = 0;
        }

        $COST["Grand_Total_Lay_Day"] = $Lay_Days <= 0 ? 0.0 : ($COST["Grand_Total_Cost"]/$Lay_Days); //set to zero if lays_days is zero

       debugPrint('(energy_methods nod_costs): Total Cost ['.$COST["Total_Cost"].'] Taxes ['.$COST["Taxes_Add_Fees"].'] Cost Grand Total['.$COST["Grand_Total_Cost"].'] Lay Days['.$Lay_Days.']'. 'total per day '.$COST["Grand_Total_Lay_Day"] );

	if(!empty($kWh_Total)) {
          $COST["Grand_Total_kWh"] = $COST["Grand_Total_Cost"]/$kWh_Total;
          $COST["Energy_Total_kWh"] = $COST["Total_kWh_Cost"]/$kWh_Total;
	} else {
          $COST["Grand_Total_kWh"] = 0;
          $COST["Energy_Total_kWh"] = 0;
        }

	if(!empty($Peak_Demand)) {
          $COST["Demand_Total_kW"] = $COST["Total_kW_Cost"]/$Peak_Demand;
	}
        debugPrint(sprintf("(baseline_cost): Grand_Total_Lay_Day for %s from %s to %s", $COST["Grand_Total_Lay_Day"], $date_value_start, $date_value_end));
        $baseline_cost["Grand_Total_Lay_Day,".$lay_day_date_ref] = $COST["Grand_Total_Lay_Day"];
    }
  }

  return $baseline_cost;
}
/** baseline_ship_class_values()
 *
 */
function baseline_ship_class_values($owner, $class, $access, $group) {
  global $log;

  $log->logInfo("(baseline_ship_class_values) --START--");
  $ships = ships_in_class($owner, $class, $access, $group);
  $ship_baselines = array();
  $months = 13;
  $month = 1;

  date_default_timezone_set('UTC');

  $cur_month = date('n');
  $cur_year = date('Y');
  for($month; $month < $months; $month++) {
    $month_baseline_running_total = array();
    foreach ($ships as $aq) {
      $device = main_device_info($aq);
      $ship = $device['devicetablename'];
      $device_class = $device['deviceclass'];

      debugPrint("cur_month $cur_month");
      if($month > $cur_month) {
        $year = date('Y', strtotime('-1 year' , strtotime ($cur_year)));
      } else {
        $year = $cur_year;
      }

      $month_date_string = $year."-".$month."-1 ".$device['timezonephp'];
      $utility = $device['utility'];
      $val["date_value_start"] = date('Y-m-d H:i:s', strtotime($month_date_string));
      $val["date_value_end"] = date('Y-m-t H:i:s', strtotime($month_date_string));

      $Voltage_Field = "Voltage_Line_to_Line";
      $Peak_kW_Field="Peak_kW";
      $Peak_kWh_Field="Peak_kWh";
      $Off_Peak_kW_Field="Off_Peak_kW";
      $Off_Peak_kWh_Field="Off_Peak_kWh";
      $Reactive_Power_Field = "Reactive_Power";
      $kVAR_Demand_Field = "30_Min_Reactive_kVAR";
      $Power_Factor_Field="Power_Factor";
      $Demand_Field = "15_Min_Demand_kW";

      $baseline_value = baseline_values('time','','','MAX',$Peak_kW_Field,$ship,$val["date_value_start"],$val["date_value_end"]);

      if(count($baseline_value['values']) > 0) {
        $val["Peak_Demand_Baseline"] = array_sum($baseline_value['values'])/count($baseline_value['values']);
        $month_baseline_running_total["Peak_Demand_Baseline"] += $val["Peak_Demand_Baseline"];
      }

      $val["baseline_peak_demand_values"] = $baseline_value["values"];
      $val["baseline_peak_demand_times"] = $baseline_value["times"];

      $baseline_value2 = baseline_values('time','','','MAX',$Off_Peak_kW_Field,$ship,$val["date_value_start"],$val["date_value_end"]);
      $val["baseline_off_peak_demand_values"] = $baseline_value2["values"];
      $val["baseline_off_peak_demand_times"] = $baseline_value2["times"];

      switch($utility) {
      	case "Virginia_Dominion_Rates":
                    $baseline_value6 = baseline_values('time','','','MAX',$kVAR_Demand_Field,$ship,$val["date_value_start"],$val["date_value_end"]);
                    $val["baseline_kvar_demand_values"] = $baseline_value6["values"];
                    $val["baseline_kvar_demand_times"] = $baseline_value6["times"];
                break;
      	case "SCE&G_Rates":
                   $baseline_bill_demand_values = baseline_billvalues($val["baseline_peak_demand_times"], $val["baseline_peak_demand_values"], $val["baseline_off_peak_demand_values"], 'time', $ship, $Power_Factor_Field);
                   $val = array_merge($val, $baseline_bill_demand_values);
                break;
      }

      $val["baseline_month_lay_days"] = baseline_month_day_count('time',$ship,$val["date_value_start"],$val["date_value_end"],$Voltage_Field,10);
      $val["baseline_peak_kwh_totals"] = baseline_values('time','Peaksum','','SUM',$Peak_kWh_Field,$ship,$val["date_value_start"],$val["date_value_end"]);
      $val["baseline_off_peak_kwh_totals"] = baseline_values('time','OffPeaksum','','SUM',$Off_Peak_kWh_Field,$ship,$val["date_value_start"],$val["date_value_end"]);

      // We're looking to get a historical average (baseline) for kWh / day for a given month
      $kWh_day_baseline_sum_month = 0;
      $lay_day_over_0_count = 0;

      foreach($val["baseline_month_lay_days"] as $lay_day_date_ref => $lay_days) {
        if($lay_days > 0) {
          debugPrint('(baseline_ship_class_values): lay_day_date_ref: '.$lay_day_date_ref.', lay_days > 0: '.$lay_days.', baseline_peak_kwh_total: '.$val["baseline_peak_kwh_totals"][$lay_day_date_ref].', baseline_off_peak_kwh_totals: '.$val["baseline_off_peak_kwh_totals"][$lay_day_date_ref]);
          $kWh_day_baseline_calc = ($val["baseline_peak_kwh_totals"][$lay_day_date_ref] + $val["baseline_off_peak_kwh_totals"][$lay_day_date_ref])/$lay_days;
          debugPrint('(baseline_ship_class_values): '.$kWh_day_baseline_calc);
          $kWh_day_baseline_sum_month += $kWh_day_baseline_calc;
          $lay_day_over_0_count += 1;
        }
      }

      if($lay_day_over_0_count > 0) {
        $val['kWh_day_baseline'] = $kWh_day_baseline_sum_month/$lay_day_over_0_count;
        debugPrint('kWh_day_baseline_sum: '.$kWh_day_baseline_sum_month);
        debugPrint('kWh_day_baseline_lay_day_count: '.count($val["baseline_month_lay_days"]));
        debugPrint('(kWh_day_baseline): '.$val["kWh_day_baseline"]);
        $log->logInfo("(kWh_day_baseline) ".$val["kWh_day_baseline"]);
        $month_baseline_running_total["kWh_day_baseline"] += $val["kWh_day_baseline"];
      }

      $baseline_cost = baseline_cost($utility, $val, $ship, 'time');
      if(count($baseline_cost) > 0) {
        $cost["Grand_Total_Lay_Day_Baseline"] = array_sum($baseline_cost)/count($baseline_cost);
        $month_baseline_running_total["Grand_Total_Lay_Day_Baseline"] += $cost["Grand_Total_Lay_Day_Baseline"];
      }
    }

    $kWh_day_class_baseline = $month_baseline_running_total["kWh_day_baseline"]/count($ships);
    $Peak_Demand_Class_Baseline = $month_baseline_running_total["Peak_Demand_Baseline"]/count($ships);
    $Grand_Total_Lay_Day_Class_Baseline = $month_baseline_running_total["Grand_Total_Lay_Day_Baseline"]/count($ships);
    $sql = "INSERT INTO baseline_values
      (id, month, year, name, metric, value, ship_class, ship_owner, ship_group)
      VALUES(NULL, $month, '$year', 'kWh Consumption Avg Per Day', 'kWh_day', ".$kWh_day_class_baseline.", '$class', '$owner', '$group'),
      (NULL, $month, '$year', 'Peak Demand', 'Peak_Demand', ".$Peak_Demand_Class_Baseline.", '$class', '$owner', '$group'),
      (NULL, $month, '$year', 'Cost Avg per Day', 'Grand_Total_Lay_Day', ".$Grand_Total_Lay_Day_Class_Baseline.", '$class', '$owner', '$group')";

    $log->logInfo("(baseline_ship_class_values): $sql");
    $res = mysql_query($sql);

    if(!$res) {
      $log->logError("Could not insert baseline values for $month");
    }
  }
}

$owner = $argv[1];
$class = $argv[2];
$access = 'Level_'.$argv[3];
$group = $argv[4];

baseline_ship_class_values($owner, $class, $access, $group);
?>
