<?php

 //....................................KLogger...............................
//include Logger.php";
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
$log->logInfo('data methods hello ship' .$ship);
//.....................................End KLogger..........................

function MySqlFailure($Reason) {
	$con = $_SESSION['con'];
	$sql_errno = mysql_errno($con);

	if($sql_errno>0)
	{
		echo "mySQL FAILURE $Reason"."</br>";
		echo  "mySQL FAILURE: $Reason"."</br>".$sql_errno. ": " . mysql_error($con) . "</br>";
	exit;
	}
	else
	{
		$warningCountResult = mysql_query("SELECT @@warning_count");
		if ($warningCountResult)
		{
			$warningCount = mysql_fetch_row($warningCountResult );
			if ($warningCount[0] > 0)
			{
				$warningDetailResult = mysql_query("SHOW WARNINGS");
				if ($warningDetailResult )
				{
					while ($warning = mysql_fetch_array($warningDetailResult))
					{
						foreach ($warning AS $key => $value)
						{
							if($value!==$value_repeat)
							{
								$value = $value." ";
							}
							$value_repeat = $value;
						}
						echo "mySQL WARNING: $value"."</br>";
					}
				}
			}
		}
	}
}

function makeDate($dateValue)
{
    $my_date = date('Y-m-d H:i', strtotime($dateValue));
 	return $my_date;
}

function checkRealPower($ship) {
  $sql = "SHOW COLUMNS FROM $ship LIKE 'Real_Power'";
  $result = mysql_query($sql);
  if (!$result) {
    debugPrint("[checkRealPower]: Error, SQL ($sql)");
  }

  return (mysql_num_rows($result)) ? true : false;
}

/**
 * get_data()
 *
 * @param mixed $ship
 * @return
 */
function get_data($ship)
{
	//debugPrint("(get_data) ship ".$ship);

// 	global $key; --
// 	global $aquisuitetablename;
//
// 	$device_classQ = mysql_query("SELECT deviceclass from $aquisuitetablename[$key] where function='main_utility'");
 	$device_classQ = mysql_query("SELECT devicetablename from $ship where function='main_utility'");

	if(!$device_classQ) {
 		MySqlFailure("could not aquire deviceclass");
 	}
 	$row=mysql_fetch_array($device_classQ);
 	$device_table_name = $row[0];
 	//debugPrint("(get_data) device class ".$device_class);


    $sql="SELECT * FROM Device_Config WHERE aquisuitetablename='$ship' AND devicetablename='$device_table_name'";

	//if($device_class!=2)
    //{
	//	$sql="SELECT * FROM `Device_Config` WHERE aquisuitetablename='Cape_Kennedy_001EC6001433' AND deviceclass='27' AND units!=''";
    //	//$sql="SELECT * FROM `Device_Config` WHERE aquisuitetablename='Cape_Kennedy_001EC6001433' AND deviceclass='27' AND units!=''";
	//}
	//if($device_class == 17)
    //{
    //    $sql="SELECT * FROM `Device_Config` WHERE aquisuitetablename='BSU_Portsmouth_0050C230EED9X' AND deviceclass='17' AND units!=''";
    //}
	$RESULT = mysql_query($sql);
	//debugPrint("(get_data) ".$sql);

	if(!$RESULT)
	{
	   MySqlFailure("get data failed ".$device);
	}

	while($row=mysql_fetch_array($RESULT))
	{
		$Field[] = $row['Field'];
		$Units[] = $row['units'];
		$Title[] = $row['name'];
	}
	$DATA['Field'] = ($Field);
	$DATA['Units'] = ($Units);
	$DATA['Title'] = ($Title);

	return $DATA;
}

/**
 * ship_name()
 *
 * @param mixed $ship
 * @return
 */
function ship_name($ship)
{
	debugPrint("(ship name) ".$ship);
    $Ship_Table_Name = $ship;
    $pattern = "/([a-zA-Z0-9])+_([a-zA-Z0-9_-])+/";
	$space=preg_match($pattern, $ship);
	if($space==1)
    {
    	$ship=str_replace('_',' ',$ship);
    	$ship=substr_replace($ship,'',-12);

    	debugPrint("(ship name) ".$ship);
    	return $ship;
    }

    $TITLE=substr_replace($TITLE,'',-12);
	debugPrint("(ship name) ".$TITLE);
}

/**
 * timezone()
 *
 * @param mixed $ship
 * @return
 */
function timezone($ship)
{
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";
	$result = mysql_query($sql);
	$row = mysql_fetch_row($result);
	$timezoneaquisuite = trim($row[0]);

	$sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";
	$result = mysql_query($sql);
	$row = mysql_fetch_row($result);
	$timezone = $row[0];

	return $timezone;

}
/**
 * value_find()
 *
 * @param mixed $time
 * @param mixed $value
 * @param mixed $table
 * @param mixed $date
 * @return
 */
function value_find($time,$value,$table,$date)
{
	$sql="SELECT $value FROM $table WHERE $time LIKE '%$date%'";
	$RESULT=mysql_query($sql);
	//debugPrint("(value_find) ".$sql);

	if(!$RESULT)
	{
	   MySqlFailure("value find failed ".$value);
	}

	$row=mysql_fetch_array($RESULT);
	$value_find=$row["$value"];

	return $value_find;
}

/**
 * values()
 *
 * @param mixed $time
 * @param mixed $col_alias1
 * @param mixed $operand
 * @param mixed $value
 * @param mixed $col_alias2
 * @param mixed $table
 * @param mixed $start_date
 * @param mixed $end_date
 * @return
 */
function values(
    $time,
    $col_alias1,
    $operand,
    $value,
    $col_alias2,
    $table,
    $start_date,
    $end_date
    )
{
    global $device_class;

    $real_power_limit = '';
    $hasRealPower = checkRealPower($table);

    if($hasRealPower) {
      $real_power_limit = "AND (`Real_Power`>10)";
    }

    if($value=="Power_Factor" && $operand!=='AVG') {
        $sql="SELECT DATE_FORMAT($time, '%a %b %e, %Y, %H:%i:%S') AS `cdate`, $value FROM $table WHERE ($value=(SELECT $operand($value) FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit AND ($value>0))) AND ($time BETWEEN '$start_date' AND '$end_date') LIMIT 1";
    } else if($operand!=='AVG' && $operand!=='SUM' && $operand!=='COUNT') {
        $sql="SELECT DATE_FORMAT($time, '%a %b %e, %Y, %H:%i:%S') AS $col_alias1, $value FROM $table WHERE ($value=(SELECT $operand($value) FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit)) AND ($time BETWEEN '$start_date' AND '$end_date') LIMIT 1";
    } else {
        $sql="SELECT $operand($value) AS $col_alias2 FROM $table WHERE ($time BETWEEN '$start_date' AND '$end_date') $real_power_limit";
    }

    $RESULT=mysql_query($sql);

    debugPrint("(values A) ".$sql);

	if(!$RESULT)
	{
        return 0;
	}
	else
	{
        $value_result=mysql_fetch_array($RESULT);
        //debugPrint("(values B) ".implode(',', $value_result));

        return $value_result;
	}
}

function get_annual_baselines($owner, $class, $group, $metrics) {
  global $log;

  $group_cond = ($group ? "AND ship_group='$group'" : "");

  for($i = 0; $i < count($metrics); $i++) {
    if($i == 0) {
      $metric_where .= "metric = '".$metrics[$i]."'";
    } else {
      $metric_where .= " OR metric = '".$metrics[$i]."'";
    }
  }

  $sql = sprintf("SELECT metric, AVG(value) as yearly_avg FROM baseline_values
                  WHERE ship_owner='%s'
                  $group_cond
                  AND ship_class='%s' AND (%s)
                  GROUP BY metric", $owner, $class, $metric_where);
  $log->logInfo("(get_annual_baselines): $sql");
  $res = mysql_query($sql);

  if(!$res) {
    $log->logInfo("(get_annual_baselines) query failed");
    MySqlFailure("Failed to retireve annual baseline values");
  }

  $baseline_metrics = array();

  while($row = mysql_fetch_assoc($res)) {
    $log->logInfo("(get_annual_baselines) metric:".$row['metric']. " yearly_avg: ".$row['yearly_avg']);
    $baseline_metrics[$row['metric']] = $row['yearly_avg'];
  }

  return $baseline_metrics;
}

function get_monthly_baselines($owner, $class, $group, $metrics, $month) {
  global $log;

  $group_cond = ($group ? "AND ship_group='$group'" : "");

  for($i = 0; $i < count($metrics); $i++) {
    if($i == 0) {
      $metric_where .= "metric = '".$metrics[$i]."'";
    } else {
      $metric_where .= " OR metric = '".$metrics[$i]."'";
    }
  }
  // Get the most recent baseline calculation
  $sql = sprintf("SELECT * FROM baseline_values
          WHERE ship_owner='%s'
          $group_cond
          AND ship_class='%s' AND month='%s' AND (%s)
          ORDER BY year DESC LIMIT %d", $owner, $class, $month, $metric_where, count($metrics));

  $log->logInfo("(get_monthly_baselines): $sql");

  $res = mysql_query($sql);

  if(!$res) {
    $log->logInfo("(get_monthly_baselines) query failed");
    MySqlFailure("Failed to retireve monthly baseline values");
  }

  $baseline_metrics = array();

  while($row = mysql_fetch_assoc($res)) {
    $log->logInfo("(get_monthly_baselines) metric:".$row['metric']. " yearly_avg: ".$row['yearly_avg']);
    $baseline_metrics[$row['metric']] = $row['value'];
  }

  return $baseline_metrics;
}

/**
 * kWh_cost()
 *
 * @param mixed $time
 * @param mixed $date_value_start
 * @param mixed $date_value_end
 * @param mixed $value
 * @param mixed $table
 * @param mixed $summer_start
 * @param mixed $summer_end
 * @param mixed $summer_rate
 * @param mixed $non_summer_rate
 * @param mixed $kWh
 * @return
 */
function kWh_cost(
    $time, $date_value_start, $date_value_end, $value, $table,
    $summer_start, $summer_end, $summer_rate, $non_summer_rate,
    $kWh, $aq='')
{
       global $log;
       global $key;
       global $aquisuitetablename;
       global $device_class;

       $aq = ($aq == '' ? $aquisuitetablename[$key] : $aq);

	$timezone=timezone($aq);
       date_default_timezone_set($timezone);
       $local_start_month  = idate(m, strtotime($date_value_start.' UTC'));
       $local_end_month  = idate(m, strtotime($date_value_end.' UTC'));
       date_default_timezone_set("UTC");


	$month1=add_0(idate(m, strtotime($date_value_start)));
	$day1=add_0(idate(t, strtotime($date_value_start)));
	$year1=idate(Y, strtotime($date_value_start));
	$month2=add_0(idate(m, strtotime($date_value_end)));
	$year2=idate(Y, strtotime($date_value_end));

        $hasRealPower = checkRealPower($table);

        if($hasRealPower) {
            	$real_power_limit = "AND (`Real_Power`>10)";
        }

	if ($local_start_month != $local_end_month)   // changed from to adjust for utc time diff if($month2!==$month1)
	{
		$date_value_start_m2 = "$year2-$month2-1 00:00:00";
		$sql_m2="SELECT SUM($value) AS 'tempsum' FROM $table WHERE ($time BETWEEN '$date_value_start_m2' AND '$date_value_end') $real_power_limit";

        debugPrint('(kWh_cost): ' . $sql_m2);

        $RESULT_m2 = mysql_query($sql_m2);

		if(!$RESULT_m2)
		{
    		MySqlFailure("kWh cost failed ".$value);
		}

		$kWh_m2 = mysql_fetch_array($RESULT_m2);
		$kWh_m2_cost = $kWh_m2['tempsum'];

		$date_value_end_m1 ="$year1-$month1-$day1 23:55:00";
		$sql_m1="SELECT SUM($value) AS 'tempsum' FROM $table WHERE ($time BETWEEN '$date_value_start' AND '$date_value_end_m1') $real_power_limit";
        debugPrint('kWh_cost(2): ' . $sql_m1);

		$RESULT_m1 = mysql_query($sql_m1);

		if(!$RESULT_m2)
		{
    		MySqlFailure("could not determine sum ".$value);
		}

		$kWh_m1 = mysql_fetch_array($RESULT_m1);
		$kWh_m1_cost = $kWh_m1['tempsum'];
        debugPrint('kWh_cost(3): ' . $kWh_m1_cost);

		if($month2>=$summer_start && $month2<$summer_end)
		{
			$kWh_summer_cost2 = $kWh_m2_cost*$summer_rate;
		}
		else
		{
			$kWh_non_summer_cost2= $kWh_m2_cost*$non_summer_rate;
		}
		if($month1>=$summer_start && $month1<$summer_end)
		{
			$kWh_summer_cost1 = $kWh_m1_cost*$summer_rate;
		}
		else
		{
			$kWh_non_summer_cost1= $kWh_m1_cost*$non_summer_rate;
		}
        $kWh_cost = $kWh_summer_cost2+$kWh_non_summer_cost2+$kWh_summer_cost1+$kWh_non_summer_cost1;

        //echo 'kWh_cost(4): ' . $kWh_cost . "</br>";
	}
	else
	{
		if($month1>=$summer_start && $month1<$summer_end)
		{
			$kWh_summer_cost = $kWh*$summer_rate;
		}
		else
		{
			$kWh_non_summer_cost= $kWh*$non_summer_rate;
		}

        $kWh_cost = $kWh_summer_cost+$kWh_non_summer_cost;
	}

	return $kWh_cost;
}

/**
 * billvalues()
 *
 * @param mixed $time
 * @param mixed $table
 * @param mixed $time_input
 * @return
 */
function billvalues($time, $table, $time_input)
{
	$sql = "SELECT * FROM $table WHERE $time='$time_input'";
	$RESULT=mysql_query($sql);

    debugPrint('billvalues[0]: ' . $sql);

    if(!$RESULT)
    {
        MySqlFailure("billvalues failed ".$time_input);
    }

    $value_result=mysql_fetch_array($RESULT);

    return $value_result;
}

function calculate_bill_values($bill_demand, $peak_demand, $off_peak_demand, $power_factor_field) {
  global $device_class;

  if($device_class=='27')
  {
      $bill_demand["$power_factor_field"] = 0.83; //hard coded power factor for Cape Ks Pulse Meters
  }

  $bill_values = array();

  $bill_values["2_PF_Demand"]=$bill_demand["$power_factor_field"]*100;
  $bill_values["PF_Billed_Demand"] = $bill_demand["$power_factor_field"];

  debugPrint('mod_values[PF_Billed_Demand] (' . $bill_values["PF_Billed_Demand"].')');

  if(!empty($bill_values["PF_Billed_Demand"]))
  {
      $bill_values["Peak_Billed_Demand"] = ($peak_demand/.85);
      $bill_values["Off_Peak_Billed_Demand"] = ($off_peak_demand/.85);
  }

  return $bill_values;
}

/**
 * utility_schedule()
 */
function utility_schedule_rates($utility, $date_value_end, $date_value_start) {

  global $log;

  $sql_rates = sprintf("SELECT * FROM `$utility` WHERE Rate_Date_End >= '%s' AND Rate_Date_Start <= '%s'", $date_value_end, $date_value_start);

  debugPrint('(utility_schedule_rates): '.$sql_rates);

  $rate_q = mysql_query($sql_rates);
  if(!$rate_q)
  {
     echo "unable to process mysql request\n"; echo "$sql_rates\n";
     $log->logInfo(sprintf("mod_cost: unable to process mysql request\n"));
     return false;
  }
  else
  {
     $rates = mysql_fetch_array($rate_q);
  }

  return $rates;
}
/**
 * get_high_point
 *
 *  @param mixed $ship
 *  @param mixed $starttime
 *  @param mixed $endtime
 * @return
 */
function get_high_point($ship,$starttime,$endtime)
{
    $Peak_kW_Field="Peak_kW";
    $value = values("time",'cdate','MAX',$Peak_kW_Field,'',$ship,$starttime,$endtime);

    $PD_time=$value['cdate'];
    $PD_mtime=idate('m', strtotime($VAL["Peak_Demand_Time"]));
    $PD_val = $value["$Peak_kW_Field"];
     debugPrint('(data_method:get_high_point) peak summer time['.$PD_time.'] '.$PD_val.' ship '.$ship);
     return $PD_val;
}

/**
 * day_count()
 *
 * @param mixed $time
 * @param mixed $table
 * @param mixed $time_start
 * @param mixed $time_stop
 * @param mixed $value
 * @param mixed $max_value
 * @return
 */
function day_count($time,$table,$time_start,$time_stop,$value,$max_value)
{
    global $device_class;

    $real_power_limit = '';
    $hasRealPower = checkRealPower($table);

    if($hasRealPower) {
      $real_power_limit = "AND (`Real_Power`>10)";
    }

    $sql="SELECT COUNT(`$time`) AS `layday` FROM $table WHERE (`$time` BETWEEN '$time_start' AND '$time_stop') $real_power_limit";
	$RESULT=mysql_query($sql);

		debugPrint('day_count: '.$sql);

    if(!$RESULT)
	{
	   MySqlFailure("day count failed ".$time);
	}

	$row=mysql_fetch_array($RESULT);
	$days = $row['layday']/288.00;

	return $days;
}
/**
 * add_0()
 *
 * @param mixed $date_number
 * @return
 */
function add_0($date_number)
{
    if($date_number<=9)
    {
        $date_number="0".$date_number;
    }
    return $date_number;
}

/**
 * date_op()
 *
 * @param mixed $time
 * @param mixed $date
 * @param mixed $value
 * @param mixed $time_interval
 * @param mixed $table
 * @param mixed $operand
 * @return
 */
function date_op($time,$date,$value,$time_interval,$table,$operand)
{
    if($operand=='1')
    {
        $op = "DATE_SUB";
    }
    else
    {
        $op = "DATE_ADD";
    }

    $sql="SELECT $op($time, INTERVAL $value $time_interval) AS timeinterval FROM $table WHERE $time LIKE '%$date%'";
    $RESULT=mysql_query($sql);

    //echo 'date_op: ' . $sql . "</br>";

    if(!$RESULT)
    {
        MySqlFailure("date interval failed ".$time_interval);
    }

    $row=mysql_fetch_array($RESULT);
    $result_date = $row['timeinterval'];

    return $result_date;
}

/**
 * date_range_alert()
 *
 * @param mixed $time
 * @param mixed $date_to_start
 * @param mixed $date_to_end
 * @param mixed $table
 * @param mixed $sort
 * @param int $error_action
 * @return
 */
function date_range_alert($time,$date_to_start,$date_to_end,$table,$sort, $error_action)
{
	if($date_to_start == $date_to_end)
	{
        $date_to_end = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s', strtotime($date_to_end)) . "+1 day"));
	}
	$Time_within_range = "SELECT $time FROM $table WHERE $time BETWEEN '$date_to_start' AND '$date_to_end' ORDER BY $time $sort";
	debugPrint('data_method date_range: '.$Time_within_range);
	$range_query = mysql_query($Time_within_range);

	if(!$range_query)
	{
        MySqlFailure("date range alert failed ".$time);
	}

	if(mysql_num_rows($range_query)==0)
	{
        $timezone = timezone($table);
        date_default_timezone_set($timezone);

        $alert_start_date = date("F d Y", strtotime($date_to_start."UTC"));
        $alert_end_date = date("F d Y", strtotime($date_to_end."UTC"));
        if ($error_action > 1)
        {
            return 0;  //return error if no data for a ship in multiple ship bar chart report
        }
        else
        {
?>
<script type="text/javascript">
    alert("There is no data available for <?php echo $alert_start_date ?> to <?php echo $alert_end_date ?>!\n\nPlease select an available data range");
    window.history.back();
</script>
<?php

  	      echo '//<script type="text/javascript">'."\n";
  	      echo '//<!--'."\n";
  	      echo '//alert("There is no data available for '.$alert_start_date.' to '.$alert_end_date.' Please select an available data range\n'.$Time_within_range.'");';
  	      echo '//window.history.back();'."\n";
  	      echo '// -->'."\n";
  	      echo '//</script>'."\n";
  	   }
	}
	else
	{
        $row = mysql_fetch_array($range_query);
        $date= $row["$time"];
	}
	return $date;
}


/**
 * annualAnnualRunningTotals()
 *
 * @param int $imonth
 * @param mixed $monthly_running_totals
 * @param mixed $VAL_YEAR
 * @param mixed $COST_YEAR
 * @return running totals array
 */
function annualRunningTotals($imonth, $monthly_running_totals, $VAL_YEAR, $COST_YEAR)
{
       $monthly_running_totals["kWh_day"] += $VAL_YEAR[$imonth]["kWh_day"];
       $monthly_running_totals["Grand_Total_Lay_Day"] += $COST_YEAR[$imonth]["Grand_Total_Lay_Day"];
       $monthly_running_totals["Grand_Total_kWh"] += $COST_YEAR[$imonth]["Grand_Total_kWh"];
       $monthly_running_totals["2_CO2_day"] += $VAL_YEAR[$imonth]["2_CO2_day"];
       $monthly_running_totals["kWh_Total"] += $VAL_YEAR[$imonth]["kWh_Total"];
       $monthly_running_totals["2_Total_CO2"] += $VAL_YEAR[$imonth]["2_Total_CO2"];
       $monthly_running_totals["Peak_Demand"] += $VAL_YEAR[$imonth]["Peak_Demand"];
       $monthly_running_totals["Off_Peak_Demand"] += $VAL_YEAR[$imonth]["Off_Peak_Demand"];
       $monthly_running_totals["Lay_Days"] += $VAL_YEAR[$imonth]["Lay_Days"];
       $monthly_running_totals["Peak_kWh_Total"] += $VAL_YEAR[$imonth]["Peak_kWh_Total"];
       $monthly_running_totals["Off_Peak_kWh_Total"] += $VAL_YEAR[$imonth]["Off_Peak_kWh_Total"];
       $monthly_running_totals["Peak_Billed_Demand"] += $VAL_YEAR[$imonth]["Peak_Billed_Demand"];
       $monthly_running_totals["Off_Peak_Billed_Demand"] += $VAL_YEAR[$imonth]["Off_Peak_Billed_Demand"];
       $monthly_running_totals["Demand_avg"] += $VAL_YEAR[$imonth]["Demand_avg"];
       $monthly_running_totals["2_PF_Demand"] += $VAL_YEAR[$imonth]["2_PF_Demand"];
       $monthly_running_totals["2_PF_Avg"] += $VAL_YEAR[$imonth]["2_PF_Avg"];
       $monthly_running_totals["2_PF_Min"] += $VAL_YEAR[$imonth]["2_PF_Min"];
       $monthly_running_totals["2_PF_Max"] += $VAL_YEAR[$imonth]["2_PF_Max"];

       $monthly_running_totals["Peak_kWh_Cost"] += $COST_YEAR[$imonth]["Peak_kWh_Cost"];
       $monthly_running_totals["Off_Peak_kWh_Cost"] += $COST_YEAR[$imonth]["Off_Peak_kWh_Cost"];
       $monthly_running_totals["Other_Energy_Cost"] += $COST_YEAR[$imonth]["Other_Energy_Cost"];
       $monthly_running_totals["Total_kWh_Cost"] += $COST_YEAR[$imonth]["Total_kWh_Cost"];
       $monthly_running_totals["Peak_kW_Cost"] += $COST_YEAR[$imonth]["Peak_kW_Cost"];
       $monthly_running_totals["Off_Peak_kW_Cost"] += $COST_YEAR[$imonth]["Off_Peak_kW_Cost"];
       $monthly_running_totals["Other_Demand_Cost"] += $COST_YEAR[$imonth]["Other_Demand_Cost"];
       $monthly_running_totals["Total_kW_Cost"] += $COST_YEAR[$imonth]["Total_kW_Cost"];
       $monthly_running_totals["Grand_Total_Cost"] += $COST_YEAR[$imonth]["Grand_Total_Cost"];
       $monthly_running_totals["Demand_Total_kW"] += $COST_YEAR[$imonth]["Demand_Total_kW"];
       $monthly_running_totals["Energy_Total_kWh"] += $COST_YEAR[$imonth]["Energy_Total_kWh"];

       return  $monthly_running_totals;

}

/**
 * zeroAnnualRunningTotals()
 *
 * @param int $imonth
 * @param mixed $monthly_running_totals
 * @param mixed $VAL_YEAR
 * @param mixed $COST_YEAR
 * @return running totals array
 */
function zeroRunningTotals($imonth, $monthly_running_totals, $VAL_YEAR, $COST_YEAR)
{
     debugPrint('(data_method Zero  Cost_Year\n');
     $VAL = &$COST_YEAR[$imonth];
    foreach ($VAL AS $key => $value)
       debugPrint('(data_method) key '.$key.' '.$VAL[$key].' value '.$value);

      debugPrint('(data_method Zero  Val_Year\n');
     $VAL = &$VAL_YEAR[$imonth];
    foreach ($VAL AS $key => $value)
       debugPrint('(data_method) key '.$key.' '.$VAL[$key].' value '.$value);

       $monthly_running_totals["kWh_day"] += $VAL_YEAR[$imonth]["kWh_day"];
       $monthly_running_totals["Grand_Total_Lay_Day"] += $COST_YEAR[$imonth]["Grand_Total_Lay_Day"];
       $monthly_running_totals["Grand_Total_kWh"] += $COST_YEAR[$imonth]["Grand_Total_kWh"];
       $monthly_running_totals["2_CO2_day"] += $VAL_YEAR[$imonth]["2_CO2_day"];
       $monthly_running_totals["kWh_Total"] += $VAL_YEAR[$imonth]["kWh_Total"];
       $monthly_running_totals["2_Total_CO2"] += $VAL_YEAR[$imonth]["2_Total_CO2"];
       $monthly_running_totals["Peak_Demand"] += $VAL_YEAR[$imonth]["Peak_Demand"];
       $monthly_running_totals["Off_Peak_Demand"] += $VAL_YEAR[$imonth]["Off_Peak_Demand"];
       $monthly_running_totals["Lay_Days"] += $VAL_YEAR[$imonth]["Lay_Days"];
       $monthly_running_totals["Peak_kWh_Total"] += $VAL_YEAR[$imonth]["Peak_kWh_Total"];
       $monthly_running_totals["Off_Peak_kWh_Total"] += $VAL_YEAR[$imonth]["Off_Peak_kWh_Total"];
       $monthly_running_totals["Peak_Billed_Demand"] += $VAL_YEAR[$imonth]["Peak_Billed_Demand"];
       $monthly_running_totals["Off_Peak_Billed_Demand"] += $VAL_YEAR[$imonth]["Off_Peak_Billed_Demand"];
       $monthly_running_totals["Demand_avg"] += $VAL_YEAR[$imonth]["Demand_avg"];
       $monthly_running_totals["2_PF_Demand"] += $VAL_YEAR[$imonth]["2_PF_Demand"];
       $monthly_running_totals["2_PF_Avg"] += $VAL_YEAR[$imonth]["2_PF_Avg"];
       $monthly_running_totals["2_PF_Min"] += $VAL_YEAR[$imonth]["2_PF_Min"];
       $monthly_running_totals["2_PF_Max"] += $VAL_YEAR[$imonth]["2_PF_Max"];

       $monthly_running_totals["Peak_kWh_Cost"] += $COST_YEAR[$imonth]["Peak_kWh_Cost"];
       $monthly_running_totals["Off_Peak_kWh_Cost"] += $COST_YEAR[$imonth]["Off_Peak_kWh_Cost"];
       $monthly_running_totals["Other_Energy_Cost"] += $COST_YEAR[$imonth]["Other_Energy_Cost"];
       $monthly_running_totals["Total_kWh_Cost"] += $COST_YEAR[$imonth]["Total_kWh_Cost"];
       $monthly_running_totals["Peak_kW_Cost"] += $COST_YEAR[$imonth]["Peak_kW_Cost"];
       $monthly_running_totals["Off_Peak_kW_Cost"] += $COST_YEAR[$imonth]["Off_Peak_kW_Cost"];
       $monthly_running_totals["Other_Demand_Cost"] += $COST_YEAR[$imonth]["Other_Demand_Cost"];
       $monthly_running_totals["Total_kW_Cost"] += $COST_YEAR[$imonth]["Total_kW_Cost"];
       $monthly_running_totals["Grand_Total_Cost"] += $COST_YEAR[$imonth]["Grand_Total_Cost"];
       $monthly_running_totals["Demand_Total_kW"] += $COST_YEAR[$imonth]["Demand_Total_kW"];
       $monthly_running_totals["Energy_Total_kWh"] += $COST_YEAR[$imonth]["Energy_Total_kWh"];

       return  $monthly_running_totals;

}


/**
 * annualAverages()
 *
 * @param int $valid_months
 * @param mixed $monthly_running_totals
 * @return running averages array
 */
function annualAverages($valid_months, $monthly_running_totals)
{
   if ($valid_months > 0 )
   {
    $monthly_average["kWh_day"] = ($monthly_running_totals["kWh_day"]/$valid_months);
    $monthly_average["Grand_Total_Lay_Day"] = ($monthly_running_totals["Grand_Total_Lay_Day"]/$valid_months);
    $monthly_average["Grand_Total_kWh"] = ($monthly_running_totals["Grand_Total_kWh"]/$valid_months);
    $monthly_average["2_CO2_day"] = ($monthly_running_totals["2_CO2_day"]/$valid_months);
    $monthly_average["kWh_Total"] = ($monthly_running_totals["kWh_Total"]/$valid_months);
    $monthly_average["2_Total_CO2"] = ($monthly_running_totals["2_Total_CO2"]/$valid_months);
    $monthly_average["Peak_Demand"] = ($monthly_running_totals["Peak_Demand"]/$valid_months);
    $monthly_average["Off_Peak_Demand"] = ($monthly_running_totals["Off_Peak_Demand"]/$valid_months);
    $monthly_average["Lay_Days"] = ($monthly_running_totals["Lay_Days"]/$valid_months);
    $monthly_average["Peak_kWh_Total"] = ($monthly_running_totals["Peak_kWh_Total"]/$valid_months);
    $monthly_average["Off_Peak_kWh_Total"] = ($monthly_running_totals["Off_Peak_kWh_Total"]/$valid_months);
    $monthly_average["Peak_Billed_Demand"] = ($monthly_running_totals["Peak_Billed_Demand"]/$valid_months);
    $monthly_average["Off_Peak_Billed_Demand"] = ($monthly_running_totals["Off_Peak_Billed_Demand"]/$valid_months);
    $monthly_average["Demand_avg"] = ($monthly_running_totals["Demand_avg"]/$valid_months);
    $monthly_average["2_PF_Demand"] = ($monthly_running_totals["2_PF_Demand"]/$valid_months);
    $monthly_average["2_PF_Avg"] = ($monthly_running_totals["2_PF_Avg"]/$valid_months);
    $monthly_average["2_PF_Min"] = ($monthly_running_totals["2_PF_Min"]/$valid_months);
    $monthly_average["2_PF_Max"] = ($monthly_running_totals["2_PF_Max"]/$valid_months);

    $monthly_average["Peak_kWh_Cost"] = ($monthly_running_totals["Peak_kWh_Cost"]/$valid_months);
    $monthly_average["Off_Peak_kWh_Cost"] = ($monthly_running_totals["Off_Peak_kWh_Cost"]/$valid_months);
    $monthly_average["Other_Energy_Cost"] = ($monthly_running_totals["Other_Energy_Cost"]/$valid_months);

    $monthly_average["Total_kWh_Cost"] = ($monthly_running_totals["Total_kWh_Cost"]/$valid_months);
    $monthly_average["Peak_kW_Cost"] = ($monthly_running_totals["Peak_kW_Cost"]/$valid_months);
    $monthly_average["Off_Peak_kW_Cost"] = ($monthly_running_totals["Off_Peak_kW_Cost"]/$valid_months);
    $monthly_average["Other_Demand_Cost"] = ($monthly_running_totals["Other_Demand_Cost"]/$valid_months);

    $monthly_average["Total_kW_Cost"] = ($monthly_running_totals["Total_kW_Cost"]/$valid_months);
    $monthly_average["Grand_Total_Cost"] = ($monthly_running_totals["Grand_Total_Cost"]/$valid_months);
    $monthly_average["Demand_Total_kW"] = ($monthly_running_totals["Demand_Total_kW"]/$valid_months);
    $monthly_average["Energy_Total_kWh"] = ($monthly_running_totals["Energy_Total_kWh"]/$valid_months);
    $monthly_average["Grand_Total_kWh"] = ($monthly_running_totals["Grand_Total_kWh"]/$valid_months);
   }
   else
   {
        $monthly_average["kWh_day"] = 0;
        $monthly_average["Grand_Total_Lay_Day"] = 0;
        $monthly_average["Grand_Total_kWh"] = 0;
        $monthly_average["2_CO2_day"] = 0;
        $monthly_average["kWh_Total"] = 0;
        $monthly_average["2_Total_CO2"] = 0;
        $monthly_average["Peak_Demand"] = 0;
        $monthly_average["Off_Peak_Demand"] = 0;
        $monthly_average["Lay_Days"] = 0;
        $monthly_average["Peak_kWh_Total"] = 0;
        $monthly_average["Off_Peak_kWh_Total"] = 0;
        $monthly_average["Peak_Billed_Demand"] = 0;
        $monthly_average["Off_Peak_Billed_Demand"] = 0;
        $monthly_average["Demand_avg"] = 0;
        $monthly_average["2_PF_Demand"] = 0;
        $monthly_average["2_PF_Avg"] = 0;
        $monthly_average["2_PF_Min"] = 0;
        $monthly_average["2_PF_Max"] = 0;

        $monthly_average["Peak_kWh_Cost"] = 0;
        $monthly_average["Off_Peak_kWh_Cost"] = 0;
        $monthly_average["Other_Energy_Cost"] = 0;

        $monthly_average["Total_kWh_Cost"] = 0;
        $monthly_average["Peak_kW_Cost"] = 0;
        $monthly_average["Off_Peak_kW_Cost"] = 0;
        $monthly_average["Other_Demand_Cost"] = 0;

        $monthly_average["Total_kW_Cost"] = 0;
        $monthly_average["Grand_Total_Cost"] = 0;
        $monthly_average["Demand_Total_kW"] = 0;
        $monthly_average["Energy_Total_kWh"] = 0;
        $monthly_average["Grand_Total_kWh"] = 0;
   }

    return $monthly_average;
}
?>
