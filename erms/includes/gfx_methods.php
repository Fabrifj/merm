<?php
 //....................................KLogger...............................
//include Logger.php";
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
$log->logInfo('gfx methods hello');

//.....................................End KLogger..........................

function calculate_mod1_graph_data($ship, $utility, $date_value_start, $date_value_end) {
  global $aquisuitetablename;
  global $device_class;
  global $key;
  global $log;

  $date_value_start = date('Y-m-d H:i:s', round(strtotime($date_value_start) / 300) * 300);
  $date_value_end = date('Y-m-d H:i:s', round(strtotime($date_value_end) / 300) * 300);

  $time_1 = strtotime($date_value_start);
  $time_2 = strtotime($date_value_end);

  debugPrint('erms_line_graph - Graphing Power & Cost Analysis...');
  $log->logInfo(sprintf("gfx:graphing from %s to %s\n",$date_value_start, $date_value_end));

  //$time_2 = strtotime($Time_Meter_End);
  $time_period = $time_2 - $time_1;

  $hour = 3600;
  $hour8 = $hour*8;
  $day = 86400;
  $week = $day*7;
  $divisor = 1;
  $log_interval = 300000;
  $log_interval_s = 300;

  $time_interval = ($time_period/$hour)/2;
  $Time_Field="time";
  $Peak_kW_Field="Peak_kW";
  $Off_Peak_kW_Field="Off_Peak_kW";
  $Real_Power_Field = "Real_Power";
  $real_power_limit = '';
  $hasRealPower = checkRealPower($ship);

  if($hasRealPower) {
    $real_power_limit = "AND (`Real_Power`>10)";
  }

  if($device_class == '17') {
    $Real_Power_Field = "Pulse2RateInst";
  }

  $utility = utility_check($aquisuitetablename[$key]);
  $rate_schedule = utility_schedule_rates($utility, $date_value_start, $date_value_end);
  $timezone = timezone($aquisuitetablename[$key]);
  date_default_timezone_set("$timezone");

  switch($utility) {
    case "SCE&G_Rates":
    case "Virginia_Dominion_Rates":
    case "Virginia_Electric_and_Power_Co":
    case "Entergy_NO_Rates":
      if($time_period>$day && $time_period<=$week) {
        $divisor = 10;
      } else if($time_period>$week) {
        $divisor = 20;
      } else {
        $divisor = 1;
      }

      $sql_graph = "SELECT
      		s.$Time_Field,
      		(s.$Peak_kW_Field + s.$Off_Peak_kW_Field) AS dkW,
      		s.$Real_Power_Field,
      		DATE_FORMAT(s.$Time_Field, '%a %b %e, %Y, %H:%i') AS fdate
      	FROM $ship s
      	WHERE
      		$Time_Field BETWEEN '$date_value_start' AND '$date_value_end'
      		$real_power_limit
      	ORDER BY s.$Time_Field";

      $RESULTS_graph=mysql_query($sql_graph);

      debugPrint('erms_line_graph - Utility: ('.$utility.')');
      debugPrint('erms_line_graph: ('.$sql_graph.')');

      $data1_graph_map = array();
      $data2_graph_map = array();

      if(!$RESULTS_graph) {
        echo "could not process mysql graph data request";
      } else {
        while($row=mysql_fetch_array($RESULTS_graph)) {
          date_default_timezone_set("UTC");
          // Take care of times that aren't logged right at the 5 min interval time
          $timestamp = round(strtotime($row['fdate']) / $log_interval_s) * $log_interval_s;
  	  $data1_graph_map["$timestamp"]= ($row["$Real_Power_Field"]*1);
  	  $data2_graph_map["$timestamp"]= ($row['dkW']*1);
      }

      $divised_time_interval = $log_interval_s * $divisor;
      $time_count = $time_period/$divised_time_interval;
      $peak_times = array();
      $inPeak = false;

      debugPrint("(calculate_mod1_graph_data) divised_time_interval: $divised_time_interval");

      for($counter = 0; $counter < $time_count; $counter++) {
          date_default_timezone_set("UTC");
          $timestamp_offset = $counter * $divised_time_interval;
          if($divisor > 1) {
            $timestamp_offset = $timestamp_offset - $log_interval_s;
          }
          $timestamp_s = strtotime($date_value_start);
          $timestamp = strtotime(date('Y-m-d H:i', $timestamp_s)) + $timestamp_offset;
          $timestamps[] = $timestamp;
          date_default_timezone_set("$timezone");
	  $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
	  $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
	  $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
          $time = date('Y-m-d H:i:s', $timestamp);
  	  $time_graph[]= $time;
          $value1 = 0;
          $value2 = 0;

          if(array_key_exists("$timestamp", $data1_graph_map)) {
            $value1 = $data1_graph_map[$timestamp];
          } else {
            debugPrint("Timestamp doesn't have val1 $timestamp");
          }
          if(array_key_exists("$timestamp", $data2_graph_map)) {
            $value2 = $data2_graph_map[$timestamp];
          } else {
            debugPrint("Timestamp doesn't have val2 $timestamp");
          }

          $data1_graph[] = $value1;
          $data2_graph[] = $value2;

          if($utility == "SCE&G_Rates") {
	    $Summer_Start = idate('m',strtotime($rate_schedule['Summer_Start']));
	    $Summer_End = idate('m',strtotime($rate_schedule['Summer_End']));
	    $Peak_Time_Summer_Start = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Start']));
	    $Peak_Time_Summer_Stop = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Stop']));
	    $Peak_Time_Non_Summer_Start = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Start']));
	    $Peak_Time_Non_Summer_Stop = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Stop']));
	    $Peak_Time_Non_Summer_Start2 = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Start2']));
	    $Peak_Time_Non_Summer_Stop2 = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Stop2']));
	    $MayOct_Start = idate('H',strtotime($rate_schedule['MayOct_Start']));
            $MayOct_End = idate('H',strtotime($rate_schedule['MayOct_End']));


            date_default_timezone_set("UTC");
            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }

            } else if((($month<$Summer_Start || $month>=$Summer_End) &&
		                ($week<6 && $week>0) &&
                               (($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop) ||
                                ($hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2)) &&
                                ($month !=5 && $month != 10) ) ||
                                (($month==5 || $month==10) && ($hour>=$MayOct_Start && $hour<$MayOct_End) ) ) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }
            } else {
              if($inPeak) {
                $inPeak = false;
                $peak_times[count($peak_times) - 1]["to"] = $time_graph[$counter -1];
              }
            }
          } else if ($utility == "Virginia_Dominion_Rates") {
            $Summer_Start = idate('m',strtotime($rate_schedule['Summer_Start']));
            $Summer_End = idate('m',strtotime($rate_schedule['Summer_End']));
            $Peak_Time_Summer_Start = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Start']));
            $Peak_Time_Summer_Stop = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Stop']));
            $Peak_Time_Non_Summer_Start = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Start']));
            $Peak_Time_Non_Summer_Stop = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Stop']));

            date_default_timezone_set("UTC");
            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }

            } else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && $hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }
            } else {
              if($inPeak) {
                $inPeak = false;
                $peak_times[count($peak_times) - 1]["to"] = $time_graph[$counter -1];
              }
            }
          } else if ($utility == "Virginia_Electric_and_Power_Co") {
            $Summer_Start = idate('m',strtotime($rate_schedule['Summer_Start']));
            $Summer_End = idate('m',strtotime($rate_schedule['Summer_End']));
            $Peak_Time_Summer_Start = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Start']));
            $Peak_Time_Summer_Stop = idate('H',strtotime($rate_schedule['Peak_Time_Summer_Stop']));
            $Peak_Time_Non_Summer_Start_AM = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Start_AM']));
            $Peak_Time_Non_Summer_Stop_AM = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Stop_AM']));
            $Peak_Time_Non_Summer_Start_PM = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Start_PM']));
            $Peak_Time_Non_Summer_Stop_PM = idate('H',strtotime($rate_schedule['Peak_Time_Non_Summer_Stop_PM']));

            date_default_timezone_set("UTC");
            $IS_SUMMER_MONTH = ($month>=$Summer_Start && $month<$Summer_End);
            $IS_PEAK_SUMMER_HOUR = ($hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop);
            $IS_NON_SUMMER_MONTH = ($month<$Summer_Start || $month>=$Summer_End);
            $IS_PEAK_NON_SUMMER_HOUR_AM = ($hour>=$Peak_Time_Non_Summer_Start_AM && $hour<$Peak_Time_Non_Summer_Stop_AM);
            $IS_PEAK_NON_SUMMER_HOUR_PM = ($hour>=$Peak_Time_Non_Summer_Start_PM && $hour<$Peak_Time_Non_Summer_Stop_PM);
            $IS_WEEKDAY = ($week<6 && $week>0);


            if($IS_SUMMER_MONTH && $IS_PEAK_SUMMER_HOUR && $IS_WEEKDAY) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }
            } else if($IS_NON_SUMMER_MONTH && $IS_WEEKDAY && ($IS_PEAK_NON_SUMMER_HOUR_AM || $IS_PEAK_NON_SUMMER_HOUR_PM)) {
              if(!$inPeak) {
                $inPeak = true;
                array_push($peak_times, array(
                  "from" => $time
                ));
              }
            } else {
              if($inPeak) {
                $inPeak = false;
                $peak_times[count($peak_times) - 1]["to"] = $time_graph[$counter -1];
              }
            }
          }
      }
    }

      break;
  }

  return array(
    "times" => $time_graph,
    "peak_times" => $peak_times,
    "timezone" => $timezone,
    "log_interval" => $log_interval * $divisor,
    "date_start" => $time_graph[0],
    "date_end" => $time_graph[count($time_graph) - 1],
    "data" => array(
      "y1" => $data1_graph,
      "y2" => $data2_graph
    )
  );
}


// We're passing $ships_data in here to ease in
// Adding the metric averages for the table
function mod3_graph_multi(&$ships_data,$date_value_start,$date_value_end) {
  $data1 = "Current";
  $points=$_REQUEST['datapts'];
  debugPrint('erms_line_graph: datapts='.$points.' request data1 ['.$_REQUEST['data1'].']');
  if(isset($points) and $points=="points") {
      $data1 = $_REQUEST['data1'];
  }
  debugPrint('erms_line_graph: data1 ['.$data1.']');
  $graph_data = array();
  $units_and_time_calced = false;
  foreach($ships_data as $aq => $ship_data) {
    $time_graph = array();
    $data=calculate_mod3_graph_data($ship_data,$data1,"",$date_value_start,$date_value_end);
    if(count($graph_data["times"]) == 0) {
      $graph_data["times"] = $data["times"];
    }
    if(empty($graph_data["timezone"])) {
      $graph_data["timezone"] = $data["timezone"];
    }
    if(empty($graph_data["log_interval"])) {
      $graph_data["log_interval"] = $data["log_interval"];
    }
    if(empty($graph_data["date_start"])) {
      $graph_data["date_start"] = $data["date_start"];
    }
    if(empty($graph_data["date_end"])) {
      $graph_data["date_end"] = $data["date_end"];
    }
    if(count($graph_data["units"]) == 0) {
      $graph_data["units"] = calculate_mod3_graph_units($data1,"",$aq);
    }
    $values = $data["data"][0];
    $units = $graph_data["units"][0];
    $graph_data["data"][] = array(
      "name" => $ship_data["title"],
      "values" => $values,
      "units" => $units
    );
    // appending to ships_data for convenience in building the data table
    if(count($values) > 0) {
      $ships_data[$aq]["energy_meter_trending"] = array(
        "avg" => array_sum($values)/count($values),
        "units" => $units
      );
    }
  }

  return $graph_data;
}

function mod3_graph($ship_data,$date_value_start,$date_value_end) {
  $time_graph=array();
  $data1 = "Power_Factor";
  $data2 = "Current";
  $points=$_REQUEST['datapts'];
  debugPrint('erms_line_graph: datapts='.$points.' request data1 ['.$_REQUEST['data1'].'] request data2 ['.$_REQUEST['data2'].']');
  if(isset($points) and $points=="points") {
      $data1 = $_REQUEST['data1'];
      $data2 = $_REQUEST['data2'];
  }

  debugPrint('erms_line_graph: data1 ['.$data1.'] data2 ['.$data2.']');
  $data=calculate_mod3_graph_data($ship_data,$data1,$data2,$date_value_start,$date_value_end);
  $units=calculate_mod3_graph_units($data1,$data2,$ship_data["aquisuite"]);
  $data["units"]=$units;
  return $data;
}

function calculate_mod3_graph_units($data1, $data2, $aquisuite) {
  $Field = "Field";
  $sql1="SELECT DISTINCT Field, name, units FROM `Device_Config` WHERE ($Field='$data1' OR $Field='$data2') AND `aquisuitetablename`='$aquisuite'";
  debugPrint("sql units [$sql1]");
  $RESULTS1 = mysql_query($sql1);
  if(!$RESULTS1) {
    echo "could not find device data";
  }
  $units = array();
  $y = 1;

  while($row=mysql_fetch_array($RESULTS1)) {
    $field = $row["Field"];
    $name = $row["name"];
    $unit = $row["units"];

    if($field=="30_Min_Reactive_kVAR") {
      $name="Reactive Power Demand";
      $unit="kilovoltamperes Reactive (kVAR)";
    }

    debugPrint("erms_line_graph: yaxis: $y title: $name units: $units");

    $units[] = array(
      "name" => $name,
      "units" => $unit,
      "field" => $field
    );
    $y++;
  }
  return $units;
}
function getEvenlySpacedDates($startDate, $endDate, $intervalSeconds) {
    try {
        // Calculate start and end timestamps
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        // Initialize array for storing dates
        $dates = [];

        // Add the start date
        $dates[] = date('Y-m-d H:i:s', $startTimestamp);

        // Generate evenly spaced dates
        $currentTimestamp = $startTimestamp;
        while ($currentTimestamp < $endTimestamp - $intervalSeconds) {
            $currentTimestamp += $intervalSeconds;
            $dates[] = date('Y-m-d H:i:s', $currentTimestamp);
        }

        // Add the end date
        $dates[] = date('Y-m-d H:i:s', $endTimestamp);

        return $dates;
    } catch (Exception $e) {
        throw $e;
    }
}



function calculate_mod3_graph_data($ship_data, $data1,$data2,$date_value_start,$date_value_end) {
  #### GRAPH TIME INTERVAL ####

  // determining the time values for
  // the graph x-axis time interval
  // and the divisor the frequency
  // of data for the graph

  global $log;
  global $time_graph;

  $device = $ship_data["device"];
  $device_class = $ship_data["class"];
  $timezone = $ship_data["timezone"];

  $date_value_start = date('Y-m-d H:i:s', round(strtotime($date_value_start) / 300) * 300);
  $date_value_end = date('Y-m-d H:i:s', round(strtotime($date_value_end) / 300) * 300);

  $time_1 = strtotime($date_value_start);
  $time_2 = strtotime($date_value_end);

  debugPrint('erms_line_graph - Graphing Energy Meter Data...');
  $log->logInfo(sprintf("gfx:graphing from %s to %s\n",$date_value_start, $date_value_end));

  $time_period = $time_2 - $time_1;
  $hour = 3600;
  $hour8 = $hour*8;
  $day = 86400;
  $week = $day*7;
  $divisor = 1;
  $log_interval = 300000;
  $log_interval_s = 300;

  if($time_period>$day && $time_period<=$week) {
    $divisor = 10;
  } else if($time_period>$week) {
    $divisor = 20;
  } else {
    $divisor = 1;
  }

  $time_interval = ($time_period/$hour)/2;
  $Time_Field="time";

  #### GRAPH DATA ####
  date_default_timezone_set("$timezone");

  $real_power_limit = '';
  $hasRealPower = checkRealPower($device);

  if($hasRealPower) {
    $real_power_limit = "AND (`Real_Power`>10)";
  }

  $data1_sql = "$data1 AS dat1,";
  $data2_sql = $data2 ? " $data2 AS dat2," : "";

  if($date_value_start!=$date_value_end) {
    $sql_graph = "SELECT $data1_sql $data2_sql DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i') AS fdate FROM $device WHERE ($Time_Field BETWEEN '$date_value_start' AND '$date_value_end') $real_power_limit ORDER BY $Time_Field";
  } else {
    $sql_graph = "SELECT $data1_sql $data2_sql DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i') AS fdate FROM $device WHERE ($Time_Field LIKE '%$date_value_start%') $real_power_limit ORDER BY $Time_Field";
  }

  $RESULTS_graph=mysql_query($sql_graph);
  debugPrint('erms_line_graph: sql_graph ['.$sql_graph.']');

  if(!$RESULTS_graph) {
    echo "could not process mysql graph data request";
  } else if( mysql_num_rows($RESULTS_graph) > 0 ) {
    while($row=mysql_fetch_array($RESULTS_graph)) {
      date_default_timezone_set("UTC");
      $timestamp = round(strtotime($row['fdate']) / $log_interval_s) * $log_interval_s;
      $data1_graph_map[$timestamp]=($row['dat1'])*1;
      if($data2) {
        $data2_graph_map[$timestamp]=($row['dat2'])*1;
      }
    }

    $divised_time_interval = $log_interval_s * $divisor;
    $time_count = $time_period/$divised_time_interval;

    debugPrint("time_period $time_period");
    debugPrint("divised_time_interval $divised_time_interval");
    debugPrint("divisor $divisor");
    // Graph needs to be a function of time and not available data points
    // so zeros need to be inserted for values that aren't available for a given data log
    for($counter = 0; $counter < $time_count; $counter++) {
      date_default_timezone_set("UTC");
      $timestamp_offset = $counter * $divised_time_interval;
      $timestamp_s = strtotime($date_value_start);
      $timestamp = strtotime(date('Y-m-d H:i', $timestamp_s)) + $timestamp_offset;
      $timestamps[] = $timestamp;
      date_default_timezone_set("$timezone");
      // Per ERMS Issues Access db item #7 - Energy Meter Data - Time in UTC (should be local)
      $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
      $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
      $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
      $time = date('Y-m-d H:i:s', $timestamp);
      $time_graph[]= $time;
      $value1 = 0;

      if(array_key_exists("$timestamp", $data1_graph_map)) {
        $value1 = $data1_graph_map[$timestamp];
      } else {
        debugPrint("Timestamp doesn't have val1 $timestamp");
      }

      $data1_graph[] = $value1;

      if($data2) {
        $value2 = 0;
        if(array_key_exists("$timestamp", $data2_graph_map)) {
          $value2 = $data2_graph_map[$timestamp];
        } else {
          debugPrint("Timestamp doesn't have val2 $timestamp");
        }
        $data2_graph[] = $value2;
      }
    }
  }

  //$sql = "SELECT timezoneaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetablename[$key]'";
  //$result = mysql_query($sql);
  //$row = mysql_fetch_row($result);
  //$timezoneaquisuite = trim($row[0]);
  //$Xaxis_Title = 'Time ('.$timezoneaquisuite.')';


  //if($Yaxis_Title==$Y2axis_Title) {
  //  debugPrint('erms_line_graph: Unset legend Y1 '.$Yaxis_Title.' Y2 '.$Y2axis_Title);
  //  unset($Y2axis_Title);
  //}
  $data = array($data1_graph);

  if($data2) {
    array_push($data, $data2_graph);
  }
  return array(
    "times" => $time_graph,
    "timezone" => $timezone,
    "log_interval" => $log_interval * $divisor,
    "date_start" => $time_graph[0],
    "date_end" => $time_graph[count($time_graph) - 1],
    "data" => $data,
  );
}

/**
 * erms_line_graph()
 *
 * @param mixed $Time_Field
 * @param mixed $ship
 * @param mixed $date_value_start
 * @param mixed $Time_Meter_End
 * @param mixed $date_value_end
 * @return
 */
function erms_line_graph($Time_Field,$ship,$date_value_start,$Time_Meter_End,$date_value_end)
{

    #### GRAPH TIME INTERVAL ####

	// determining the time values for
	// the graph x-axis time interval
	// and the divisor the frequency
	// of data for the graph

     global $log;


    debugPrint('Graphing from: ['.$date_value_start.'] to: ['.$date_value_end.'] (time meter end) ['.$Time_Meter_End.']');
	$time_1 = strtotime($date_value_start);
	$time_2 = strtotime($date_value_end);

       	$log->logInfo(sprintf("gfx:graphing from %s to %s meter end %s\n",$date_value_start, $date_value_end, $Time_Meter_End ));

        //$time_2 = strtotime($Time_Meter_End);
	$time_period = $time_2 - $time_1;

	$hour = 3600;
	$hour8 = $hour*8;
	$day = 86400;
	$week = $day*7;
	$divisor = 1;
        $log_interval_s = 300;

	$time_interval = ($time_period/$hour)/2;
	if($time_period>$week)
	{
    	$time_interval = ($time_period/$day)/3;
	}
	else if($time_period>$day && $time_period<=$week)
	{
    	$time_interval = $time_period/$day;
    	$time_interval = $time_interval*2;
	}
	else if ($time_period == $hour)
	{
    	$time_interval = 3;
	}
	else if ($time_period <= $hour8)
	{
    	$time_interval = 6;
	}
	else if ($time_period <= $hour8*2)
	{
    	$time_interval = 9;
	}

	if($time_period>$day && $time_period<=$week)
	{
    	$divisor=$hour/2;
    	//$divisor=20;
	}
	else if($time_period>$week)
	{
    	$divisor=$hour*2;
    	//$divisor=3;
	}

	#### GRAPH DATA ####

	// Column fields used in utility query
	$Peak_kW_Field="Peak_kW";
	$Off_Peak_kW_Field="Off_Peak_kW";
	$Real_Power_Field = "Real_Power";
	$Demand_Field = "15_Min_Demand_kW";
	$Air_Temp_Field = "Air_Temperature_Degrees_F";

	global $key;
	global $aquisuitetablename;

	$utility = utility_check($aquisuitetablename[$key]);
	$module = $_REQUEST['module'];
	$timezone = timezone($aquisuitetablename[$key]);
	date_default_timezone_set("$timezone");

	global $device_class;
        $real_power_limit = '';
        $hasRealPower = checkRealPower($ship);

        if($hasRealPower) {
          $real_power_limit = "AND (`Real_Power`>10)";
        }

	switch($module)
	{
        // Energy Power and Cost Analysis
        case ERMS_Modules::PowerAndCostAnalysis:
            debugPrint('erms_line_graph - Graphing Power & Cost Analysis...');

            if($device_class == '17')
            {
            	// Column fields used in utility query
            	$Peak_kW_Field="Peak_kW";
            	$Off_Peak_kW_Field="Off_Peak_kW";
            	$Real_Power_Field = "Pulse2RateInst";
            	//$Demand_Field = "15_Min_Demand_kW";
            	//$Air_Temp_Field = "Air_Temperature_Degrees_F";
            }
    		switch($utility)
    		{
        		case "SCE&G_Rates":
        		case "Virginia_Dominion_Rates":
                	if($time_period>$week)
                	{
                    	$time_interval = ($time_period/$day)/3;
                	}
                	else if($time_period>$day && $time_period<=$week)
                	{
                    	$time_interval = $time_period/$day;
                    	$time_interval = $time_interval*2;
                	}
                	else if ($time_period == $hour)
                	{
                    	$time_interval = 3;
                	}
                	else if ($time_period <= $hour8)
                	{
                    	$time_interval = 6;
                	}
                	else if ($time_period <= $hour8*2)
                	{
                    	$time_interval = 9;
                	}

                	if($time_period>$day && $time_period<=$week)
                	{
                    	$divisor = 10;
                	}
                	else if($time_period>$week)
                	{
                    	$divisor = 20;
                	}
                    else
                    {
                        $divisor = 1;
                    }
                    $sql_graph =
                        "SELECT t.RowNumber, t.time, t.dkW, t.Real_Power, t.fdate
                            FROM
                            (
                            	SELECT
                            		@n := @n + 1 RowNumber,
                            		s.$Time_Field,
                            		(s.$Peak_kW_Field + s.$Off_Peak_kW_Field) AS dkW,
                            		s.$Real_Power_Field,
                            		DATE_FORMAT(s.$Time_Field, '%a %b %e, %Y, %H:%i') AS fdate
                            	FROM (select @n:=0) initvars, $ship s
                            	WHERE
                            		($Time_Field BETWEEN '$date_value_start' AND '$date_value_end')
                            		$real_power_limit
                            	ORDER BY s.$Time_Field
                            ) t
                            WHERE MOD(t.RowNumber, $divisor) = 0
                            ;
                        ";
        			$RESULTS_graph=mysql_query($sql_graph);

                    debugPrint('erms_line_graph - Utility: ('.$utility.')');
                    debugPrint('erms_line_graph: ('.$sql_graph.')');

                    if(!$RESULTS_graph)
        			{
                        echo "could not process mysql graph data request";
        			}
        			else
        			{
        				while($row=mysql_fetch_array($RESULTS_graph))
        				{
            				$time_graph[]=date('m/d/y H:i',strtotime($row['fdate']."UTC"));
                                 	//$log->logInfo(sprintf("gfx: %s\n",date('m/d/y H:i',strtotime($row['fdate']."UTC"))));
            				$data1_graph[]=$row["$Real_Power_Field"];
            				$data2_graph[]=$row['dkW'];
        				}
        			}
                    break;
                case "Nav_Fed_Rates":
        			$sql_graph="SELECT $Demand_Field AS dkW, $Real_Power_Field, $Air_Temp_Field, DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i:%S') AS fdate FROM $ship WHERE ($Time_Field BETWEEN '$date_value_start' AND '$date_value_end') AND ((MOD(UNIX_TIMESTAMP($Time_Field), $divisor) = 0)) ORDER BY $Time_Field";
        			$RESULTS_graph=mysql_query($sql_graph);

        			if(!$RESULTS_graph)
        			{
            			echo "could not process mysql graph data request";
        			}
        			else
        			{
        				while($row=mysql_fetch_array($RESULTS_graph))
        				{
            				//$time_graph[]=date('D M j, Y, H:i',strtotime($row['fdate']."UTC"));
                            $time_graph[]=date('m/d/y H:i',strtotime($row['fdate']."UTC"));
            				$data1_graph[]=$row["$Real_Power_Field"];
            				$data2_graph[]=$row['dkW'];
            				$data3_graph[]=$row["$Air_Temp_Field"];
        				}
        			     break;
        			}
            }

            $sql_graph_info = "SELECT * FROM ERMS_Line_Graph WHERE utility='$utility' AND module='$module'";
            $RESULT_info = mysql_query($sql_graph_info);

        	if(!$RESULT_info)
        	{
            	echo "could not process mysql graph data request";
        	}
        	else
        	{
            	$sql = "SELECT timezoneaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetablename[$key]'";
            	$result = mysql_query($sql);
            	$row = mysql_fetch_row($result);
            	$timezoneaquisuite = trim($row[0]);

            	$graph_info = mysql_fetch_array($RESULT_info);
            	// setting all graph titles and legends
            	$Title = $graph_info['Title'];
            	$Xaxis_Title = 'Time ('.$timezoneaquisuite.')';
            	$Yaxis_Title = $graph_info['Yaxis_Title'];
            	$Y2axis_Title = $graph_info['Y2axis_Title'];
            	$graph_display['Legend_Y1'] = $graph_info['Legend_Y1'];
            	$graph_display['Legend_Y2'] = $graph_info['Legend_Y2'];
            	$Legend_Y3 = $graph_info['Legend_Y3'];
              	//$log->logInfo(sprintf("gfx: x:%s y:%s y2:%s", $Xaxis_Title, $Yaxis_Title, $Y2axis_Title  ));
        	}
        	break;
        // Energy Meter Data
        case ERMS_Modules::EnergyMeterData: //"mod3":
            $points=$_REQUEST['datapts'];
             debugPrint('erms_line_graph: datapts='.$points.' request data1 ['.$_REQUEST['data1'].'] request data2 ['.$_REQUEST['data2'].']');
            if(isset($points) and $points=="points")
            {
                $data1 = $_REQUEST['data1'];
                if(strpos('(',$data1)!=0)
                {
                    $data1 = '`'.$data1.'`';
                }
                $data2 = $_REQUEST['data2'];
                if(strpos('(',$data2)!=0)
                {
                    $data2 = '`'.$data2.'`';
                }
            }
            else
            {
                $data1 = "Power_Factor";
                $data2 = "Current";
            }

           debugPrint('erms_line_graph: data1 ['.$data1.'] data2 ['.$data2.']');


    		if($date_value_start!=$date_value_end)
    		{
        		$sql_graph = "SELECT $data1 AS dat1, $data2 AS dat2, DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i:%S') AS fdate FROM $ship WHERE ($Time_Field BETWEEN '$date_value_start' AND '$date_value_end') $real_power_limit ORDER BY $Time_Field";
    		}
    		else
    		{
        		$sql_graph = "SELECT $data1 AS dat1, $data2 AS dat2, DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i:%S') AS fdate FROM $ship WHERE ($Time_Field LIKE '%$date_value_start%') $real_power_limit ORDER BY $Time_Field";
    		}
    		$RESULTS_graph=mysql_query($sql_graph);
    		debugPrint('erms_line_graph: sql_graph ['.$sql_graph.']');

    		if(!$RESULTS_graph)
    		{
        		echo "could not process mysql graph data request";
    		}
    		else
    		{
    			while($row=mysql_fetch_array($RESULTS_graph))
    			{
                          date_default_timezone_set("UTC");
                          $timestamp_s = strtotime($row['fdate']);
                          $timestamp = round(strtotime(date('Y-m-d H:i', $timestamp_s)) / $log_interval_s) * $log_interval_s;
        		  $data1_graph_map[$timestamp]=$row['dat1'];
        		  $data2_graph_map[$timestamp]=$row['dat2'];
    			}

                        $divised_time_interval = ($divisor == 1) ? $log_interval_s : $divisor;
                        $time_count = $time_period/$divised_time_interval;

                        debugPrint("time_period $time_period");
                        debugPrint("divised_time_interval $divised_time_interval");
                        debugPrint("divisor $divisor");
                        // Graph needs to be a function of time and not available data points
                        // so zeros need to be inserted for values that aren't available for a given data log
                        for($counter = 0; $counter < $time_count; $counter++) {
                            date_default_timezone_set("UTC");
                            $timestamp_offset = $counter * $divised_time_interval;
                            $timestamp_s = strtotime($date_value_start);
                            $timestamp = strtotime(date('Y-m-d H:i', $timestamp_s)) + $timestamp_offset;
                            $timestamps[] = $timestamp;
                            date_default_timezone_set("$timezone");
        		    // Per ERMS Issues Access db item #7 - Energy Meter Data - Time in UTC (should be local)
                            $time_graph[]= date('m/d/y H:i', $timestamp);
                            $value1 = 0;
                            $value2 = 0;

                            if(array_key_exists("$timestamp", $data1_graph_map)) {
                              $value1 = $data1_graph_map[$timestamp];
                            } else {
                              debugPrint("Timestamp doesn't have val1 $timestamp");
                            }

                            if(array_key_exists("$timestamp", $data2_graph_map)) {
                              $value2 = $data2_graph_map[$timestamp];
                            } else {
                              debugPrint("Timestamp doesn't have val2 $timestamp");
                            }

                            $data1_graph[] = $value1;
                            $data2_graph[] = $value2;
                        }
    		}

    		$Field = "Field";

    		$sql1="SELECT name, units FROM `Device_Config` WHERE $Field='$data1' AND `aquisuitetablename`='$aquisuitetablename[$key]'";
    		$RESULT1 = mysql_query($sql1);
    		debugPrint('erms_line_graph: sql device data ['.$sql1.']');

    		if(!$RESULT1)
    		{
    			echo "could not find device data";
    		}
    		$num_rows1 = mysql_num_rows($RESULT1);
    		if($num_rows1>0)
    		{
    			$row1=mysql_fetch_row($RESULT1);
    			$graph_display['Legend_Y1'] = $row1[0];
    			$Yaxis_Title = $row1[1];
                  	debugPrint('erms_line_graph: legend y1 '.$graph_display['Legend_Y1'].' Title '.$row1[1]);
    		}

    		$sql2="SELECT name, units FROM `Device_Config` WHERE $Field='$data2' AND `aquisuitetablename`='$aquisuitetablename[$key]'";
    		$RESULT2 = mysql_query($sql2);

    		if(!$RESULT2)
    		{
    			echo "could not find device data";
    		}

    		$num_rows2 = mysql_num_rows($RESULT2);
    		if($num_rows2>0)
    		{
    			$row2=mysql_fetch_row($RESULT2);
    			$graph_display['Legend_Y2'] = $row2[0];
    			$Y2axis_Title = $row2[1];
                       	debugPrint('erms_line_graph: legend y2 '.$graph_display['Legend_Y2'].' Title '.$Y2axis_Title);
    		}

    		$sql = "SELECT timezoneaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetablename[$key]'";
    		$result = mysql_query($sql);
    		$row = mysql_fetch_row($result);
    		$timezoneaquisuite = trim($row[0]);
    		$Xaxis_Title = 'Time ('.$timezoneaquisuite.')';

			if($data1=="30_Min_Reactive_kVAR")
			{
				$Legend_Y1="Reactive Power Demand";
				$Yaxis_Title="kilovoltamperes Reactive (kVAR)";
			}
			if($data2=="30_Min_Reactive_kVAR")
			{
				$Legend_Y2="Reactive Power Demand";
				$Y2axis_Title="kilovoltamperes Reactive (kVAR)";
			}

    		if($Yaxis_Title==$Y2axis_Title)
    		{
                 debugPrint('erms_line_graph: Unset legend Y1 '.$Yaxis_Title.' Y2 '.$Y2axis_Title);
    		  unset($Y2axis_Title);
    		}
            $Title = "Energy Meter Data";
            break;
                    // Water Meter Data

            $Title = "Water Meter Data";
            break;
    }

	$graph_display['data1']=$data1;
	$graph_display['data2']=$data2;

	// getting the date range for graph subtitle display
	$count=count($time_graph);

	$sub_start=$time_graph[0];
	$sub_end=$time_graph[$count-1];

	// date range subtitle variables
	$graph_display["Sub_title_start"] = date("F d Y",strtotime($sub_start));
	$graph_display["Sub_title_end"] = date("F d Y",strtotime($sub_end));
	$graph_display["fill"]=" to ";

	// adding and ending subtitle if there is a date range
	if($graph_display["Sub_title_start"] == $graph_display["Sub_title_end"])
	{
    	$graph_display["Sub_title_end"] ='';
    	$graph_display["fill"]='';
	}

	// delete old image files before creating a new one
	$png_files = glob("tmp/*.png");
	foreach($png_files AS $png)
	{
		if(!unlink($png))
		{
    		echo "unable to delete image $png";
		}
	}

	// setting the image file name
	$graph_display['graph'] = "tmp/".$ship.$Title.$Legend_Y2.$graph_display["Sub_title_start"].$graph_display["Sub_title_end"].".png";
	if(file_exists($graph_display['graph']))
    {
        unlink($graph_display['graph']);
    }

	// jpgraph included file paths
	require_once ('jpgraph/jpgraph.php');
	require_once ('jpgraph/jpgraph_line.php');
	require_once ('jpgraph/jpgraph_date.php');
       	require_once ('jpgraph/jpgraph_plotline.php');

	$graph_display['width'] = 800;
	$graph_display['height'] = 530;

	// create graph THIS IS OUR POWER LINE GRAPH THRU LINE 515
	$graph = new Graph($graph_display['width'],$graph_display['height'],"auto","auto");
	$graph->SetMargin(50,50,10,165);
	$graph->SetScale('textlin');
	//$graph->SetScale('intlin');
    //$graph->SetScale('textlin',0,0,-10,20);
    //$graph->SetScale('intlin',0,0,0,0);
    //$graph->xscale->ticks->Set(10,5);
    //$graph->SetTickDensity(TICKD_NORMAL, TICKD_VERYSPARSE);
    //$graph->SetScale('textint',0,0,0,0);
    //$graph->xaxis->scale->ticks->Set(2000);

    //$graph-> ->value->HideZero(false);
    $graph->SetMarginColor('gray');



	if(!empty($Y2axis_Title))
	{
	   $graph->SetYScale(0,'lin');
	}

	// Create a line plot from sql data array
	$lplot = new LinePlot($data1_graph);

	$sp1 = new LinePlot($data2_graph);

    $lplot->value->HideZero(false);
    $sp1->value->HideZero(false);

	if(!empty($data3_graph))
	{
    	$sp2 = new LinePlot($data3_graph);
        $sp2->value->HideZero(false);
	}
	// Add a plot for each array
	$graph->Add($lplot);

	if(!empty($data3_graph))
	{
    	$graph->Add($sp1);
    	$graph->AddY(0,$sp2);
    	$Y2axis_Color = '#009900';
	}
	else if(!empty($Y2axis_Title))
	{
    	$graph->AddY(0,$sp1);
    	$Y2axis_Color = 'red';
	}
	else
	{
    	$graph->Add($sp1);
	}
	// line 1 format & legend title
	$lplot->SetWeight(3);
	$lplot->SetColor("#151B54");
	$lplot->SetLegend($graph_display['Legend_Y1']);

	// line 2 format & legend title
	$sp1->SetWeight(1);
	$sp1->SetColor('red');
	$sp1->SetLegend($graph_display['Legend_Y2']);

	// line 3 format & legend title
	if(!empty($data3_graph))
	{
    	$sp2->SetWeight(2);
    	$sp2->SetColor('#009900');
    	$sp2->SetLegend($Legend_Y3);
	}
	// title
	$graph->title->Set($Title);
    //$graph->title->setIndent(false);
	$graph->title->SetColor("black");
	$graph->title->SetFont(FF_ARIAL,FS_BOLD,16);
	// subtitle
	$graph->subtitle->Set($graph_display["Sub_title_start"].$graph_display["fill"].$graph_display["Sub_title_end"]);

	// x-axis
	$graph->xaxis->SetPos("min");
	$graph->xaxis->SetColor("black");
	$graph->xaxis->SetTitle($Xaxis_Title,'center');
	$graph->xaxis->SetTitlemargin(100);
      	//$graph->xaxis->SetTitleSide(SIDE_BOTTOM); //
	$graph->xaxis->title->SetFont(FF_ARIAL,FS_BOLD,12);
	$graph->xaxis->SetTickLabels($time_graph);
	$graph->xaxis->SetLabelAngle(90);
	// $graph->xaxis->SetTextLabelInterval($time_interval);
	$graph->xaxis->SetTextTickInterval($time_interval);
	$graph->xaxis->HideTicks(true,false);

	// y-axis
	$graph->yaxis->SetPos("min");
	$graph->yaxis->SetColor("black");
	$graph->yaxis->Settitle($Yaxis_Title,'middle');
      	debugPrint('erms_line_graph: Y legend  '.$Yaxis_Title);

	$graph->yaxis->title->SetFont(FF_ARIAL,FS_BOLD,12);
	$graph->yaxis->SetTitlemargin(30);
	$graph->yaxis->HideTicks(true,false);

	$graph->ygrid->SetFill(true,'#EFEFEF@0.3','#EAEAEA@0.3');

	// y2-axis
	if(!empty($Y2axis_Title))
	{
	$graph->ynaxis[0]->SetColor($Y2axis_Color);
	$graph->ynaxis[0]->Settitle($Y2axis_Title,'middle');

	$graph->ynaxis[0]->title->SetFont(FF_ARIAL,FS_BOLD,12);
	$graph->ynaxis[0]->SetTitlemargin(40);
	$graph->ynaxis[0]->HideTicks(true,false);
	$graph->ynaxis[0]->title->SetAngle(270);
	}

	// legend settings
	$graph->legend->SetShadow('gray@0.4',3);
	$graph->legend->SetPos(0.05,0.01,'right','top');
	$graph->legend->SetLayout(LEGEND_VERT);

	// Save the Graph image
	$graph->Stroke($graph_display['graph']);

        /*** test single vertical Line
        $line = new PlotLine(VERTICAL,25,"black",5);
        $line->SetDirection(VERTICAL);
        $line->SetPosition(25);
        $graph->AddLine($line);
	$graph->Stroke();
        *********/

	return $graph_display;
}
/**
 * erms_bar_graph()
 *
 * @param mixed $time
 * @param mixed $table
 * @param mixed $date_value_start
 * @param mixed $date_value_end
 * @return
 */
function erms_bar_graph($time,$table,$date_value_start,$date_value_end){
	$module = $_REQUEST['module'];

	global $key;
	global $aquisuitetablename;
	global $device_class;

	$utility = utility_check($aquisuitetablename[$key]);
	$timezone = timezone($aquisuitetablename[$key]);
	date_default_timezone_set($timezone);

	$timestamp_start=strtotime($date_value_start);
	$timestamp_stop=strtotime($date_value_end);
	$count_days=($timestamp_stop-$timestamp_start)/86400;

	$Off_Peak_kWh_Field="Off_Peak_kWh";
	$Peak_kWh_Field="Peak_kWh";
	$Power_kWh_Field="Power_kWh";

        $real_power_limit = '';
        $hasRealPower = checkRealPower($table);

        if($hasRealPower) {
          $real_power_limit = "AND (`Real_Power`>10)";
        }

	$i=0;
	while($i<=$count_days)
	{

		$start_date = date("Y-m-d", strtotime("+$i day",strtotime($date_value_start)));

		switch($utility)
		{
		case "SCE&G_Rates":
		case "Virginia_Dominion_Rates":
		$sql="SELECT SUM(`$Peak_kWh_Field`+`$Off_Peak_kWh_Field`) AS `kWh` FROM `$table` WHERE (`$time` LIKE '%$start_date%') $real_power_limit";
		break;
		case "Nav_Fed_Rates":
		$sql="SELECT SUM($Power_kWh_Field) AS `kWh` FROM $table WHERE (`$time` LIKE '%$start_date%') $real_power_limit";
		break;
		}
		$RESULT=mysql_query($sql);
		if(!$RESULT)
		{
		echo "unable to execute values function for value: $value from $start_date to $end_date";
		}
		else
		{
		$value_result=mysql_fetch_array($RESULT);
		}
		if($module!="mod4")
		{
		$bar_data[] = $value_result['kWh'];
		}
		else
		{
		$bar_data[] = ($value_result['kWh']*601.292474+$value_result['kWh']*0.899382565*310+$value_result['kWh']*0.01839836*21)/pow(10,6);
		}
		$date[] = date("j-M", strtotime($start_date."UTC"));
		$i++;
	}
	$bar_data_title = "kilowatt hours (kWh)";
	$TITLE = "Energy Daily Consumption";

	if($module=='mod4')
	{
	$bar_data_title = "Carbon Dioxide (MT)";
	$TITLE = "CO2 Daily Consumption";
	}
	$time_interval = 1;
	if($count_days>=15)
	{
	$time_interval = $count_days/15;
	}
	require_once ('jpgraph/jpgraph.php');
	require_once ('jpgraph/jpgraph_bar.php');

	$graph_display["Sub_title_start"] = (date("F j Y", strtotime(current($date))));
	$graph_display["Sub_title_end"] = (date("F j Y", strtotime(end($date))));
	$graph_display["fill"] = " to ";

	if($graph_display["Sub_title_start"]==$graph_display["Sub_title_end"])
	{
	$graph_display["Sub_title_end"]='';
	$graph_display["fill"]='';
	}

	// delete old image files before creating a new one
		$png_files = glob("tmp/*.png");
		foreach($png_files AS $png)
		{
			if(!unlink($png))
			{
			echo "unable to delete image $png";
			}
		}

	$graph_display['width'] = 800;
	$graph_display['height'] = 350;

	$graph_display['graph'] = "tmp/".$table.$Title.$graph_display["Sub_title_start"].$graph_display["Sub_title_end"].".png";
	// Create the graph. These two calls are always required
	$graph = new Graph($graph_display['width'],$graph_display['height'],'auto');
	$graph->SetMargin(80,80,50,75);
	$graph->SetScale("textlin");

	$graph->SetBox(false);

	// title
	$graph->title->Set($TITLE);
	$graph->title->SetColor("black");
	$graph->title->SetFont(FF_ARIAL,FS_BOLD,16);

	// subtitle
	$graph->subtitle->Set($graph_display["Sub_title_start"].$graph_display["fill"].$graph_display["Sub_title_end"]);

	$graph->ygrid->SetColor('gray');
	$graph->ygrid->SetFill(false);
	$graph->xaxis->SetTickLabels($date);
	$graph->xaxis->SetTextTickInterval($time_interval);
	$graph->xaxis->SetLabelAngle(45);

	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);
	$graph->yaxis->SetPos("min");
	$graph->yaxis->SetColor("black");
	$graph->yaxis->Settitle("$bar_data_title",'middle');
	$graph->yaxis->SetTitlemargin(45);

	// Create the bar plots
	$b1plot = new BarPlot($bar_data);

	// ...and add it to the graPH
	$graph->Add($b1plot);

	$b1plot->SetColor("black");
	$b1plot->SetFillColor("#386AA9");


	// Display the graph
	$graph->Stroke($graph_display['graph']);

	return $graph_display;
}
/**
 * erms_multibar_graph()
 *
 * @param mixed $data1y
 * @param mixed $data2y
 * @param mixed $titles
 * @return
 */
function erms_multibar_graph($datay,$datay2,$datay3,$titles, $ship_avail, $startdate, $enddate)
{
	require_once ('jpgraph/jpgraph.php');
	require_once ('jpgraph/jpgraph_bar.php');

	$ship_count = count($titles);

			$png_files = glob("tmp/*.png");
			foreach($png_files AS $png)
			{
				if(!unlink($png))
				{
				echo "unable to delete image $png";
				}
			}

		$graph_display['width'] = 1050;
		$graph_display['height'] = 750;

		$graph_display['graph'] = "tmp/"."Port_Engineer_Graph".time().".png"; //time gives unique name so browser reloads it instead of using cache

	// Create the graph.
	$graph = new Graph($graph_display['width'],$graph_display['height'],'auto');
	$graph->SetMargin(80,80,50,75);

	// Setup Y and Y2 scales with some "grace"
	$graph->SetScale("textlin");
	$graph->SetY2Scale("lin");
	$graph->yaxis->scale->SetGrace(1);
	//$graph->y2axis->scale->SetGrace(0);

	$graph->ygrid->SetColor('gray','lightgray@0.5');

	// Setup graph colors
	$graph->SetMarginColor('white');
	$graph->yaxis->SetColor('black');
	$graph->y2axis->SetColor('black');
	$graph->yaxis->title->Set('Consumption (kWh)');

	$graph->yaxis->title->SetMargin(10);
	$graph->y2axis->title->Set('Demand(kW) / Cost($)');
	$graph->y2axis->title->SetMargin(20);

	$graph->yaxis->title->SetFont(FF_ARIAL,FS_BOLD, 12);
	$graph->yaxis->title->SetColor("#E3D108");
	$graph->y2axis->title->SetFont(FF_ARIAL,FS_BOLD, 12);
	$graph->y2axis->title->SetColor("#116FCC");

	$barsubtitle = $startdate.' to '.$enddate;
	$graph->subtitle->Set($barsubtitle);

	if ($ship_count < 5)
		$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,12);
	else
		$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,10);


	for ($i=0;$i<$ship_count;$i++)
	 {
		$datazero[] = 0;  //set up dummy array
	    if ($ship_avail[$i]==1)
	    	$label_colors_array[] = "red";
		else
	   		$label_colors_array[] = "black";
	 }
	$graph->xaxis->SetTickLabels($titles, $label_colors_array );

	$bplotzero = new BarPlot($datazero);  // Create the "dummy" 0 bplot

	// Create the "Y" axis group
	$ybplot1 = new BarPlot($datay);
	$ybplot1->value->Show();

	$ybplot1->SetFillColor("#FFCC00");
	$ybplot1->SetColor("#FFCC00");
	$ybplot1->SetLegend("Consumption(kWh) Avg per Day");

	// Create the "Y2" axis group
	$ybplot2 = new BarPlot($datay2);
	$ybplot2->value->Show();

	$ybplot2->SetLegend("On-Peak Demand");

	$ybplot3 = new BarPlot($datay3);
	$ybplot3->value->Show();

     $ybplot3->SetLegend("Cost Avg per Day");

    $graph->legend->SetColor('black','gray');

	$graph->legend->SetFont(FF_ARIAL,FS_NORMAL, 10);
	//$graph->legend->SetColumns(1);
	$graph->legend->SetPos(0.5,0.85,'center','bottom');

    $graph->legend->SetMarkAbsSize(8);
	$graph->title->Set("Ship Energy Cost and Key Drivers");
	$graph->title->SetFont(FF_ARIAL,FS_NORMAL, 12);

	$ybplot = new GroupBarPlot(array($ybplot1,$bplotzero, $bplotzero));

	$y2bplot = new GroupBarPlot(array($bplotzero,$ybplot2, $ybplot3));

	// Add the grouped bar plots to the graph
	$graph->Add($ybplot);
	$graph->AddY2($y2bplot);

	$ybplot1->SetFillColor("#E3D108");
	$ybplot1->SetColor("#E3D108");
	$ybplot2->SetFillColor("#11cccc");
	$ybplot2->SetColor("#11cccc");
	$ybplot3->SetFillColor("#1111cc");
	$ybplot3->SetColor("#1111cc");

	// Display the graph
	$graph->Stroke($graph_display['graph']);

	return $graph_display;
}

/**
 * erms_pie_graph()
 *
 * @param mixed $Time_Field
 * @param mixed $data
 * @param mixed $ship
 * @param mixed $date_value_start
 * @param mixed $date_value_end
 * @param mixed $report_month
 * @return
 */
function erms_pie_graph(
        $Time_Field,
        $data,
        $ship,
        $date_value_start,
        $date_value_end,
        $report_month
    )
{
	$divisor=86400;
	$timezone = timezone($ship);
	date_default_timezone_set("$timezone");

    //echo 'Charting from: '.$date_value_start.' to: '.$date_value_end.' (time field) '.$Time_Field.' (month) '.$report_month.'<br />';

	global $device_class;
        $real_power_limit = '';
        $hasRealPower = checkRealPower($ship);

        if($hasRealPower) {
          $real_power_limit = "AND (`Real_Power`>10)";
        }

	$sql_graph="SELECT DATE_FORMAT($Time_Field, '%a %b %e, %Y, %H:%i') AS fdate FROM $ship WHERE ($Time_Field BETWEEN '$date_value_start' AND '$date_value_end') AND ((MOD(UNIX_TIMESTAMP($Time_Field), $divisor) = 0)) $real_power_limit ORDER BY $Time_Field";
	$RESULTS_graph=mysql_query($sql_graph);
	if(!$RESULTS_graph)
	{
    	echo "could not process mysql graph data request";
	}
	while($row=mysql_fetch_array($RESULTS_graph))
	{
    	$time_graph[]=date('D M j, Y, H:i',strtotime($row['fdate']."UTC"));
	}

	require_once ('jpgraph/jpgraph.php');
	require_once ('jpgraph/jpgraph_pie.php');
	require_once ('jpgraph/jpgraph_pie3d.php');

	// getting the date range for graph subtitle display
	$count=count($time_graph);
	$sub_start=$time_graph[0];
	$sub_end=$time_graph[$count-1];

	// date range subtitle variables
	$graph_display["Sub_title_start"] = date("F d Y",strtotime($sub_start));
	$graph_display["Sub_title_end"] = date("F d Y",strtotime($sub_end));
	$graph_display["fill"]=" to ";
	$Title = "Utility Cost Distribution";

	// adding and ending subtitle if there is a date range
	if($graph_display["Sub_title_start"] == $graph_display["Sub_title_end"])
	{
    	$graph_display["Sub_title_end"] ='';
    	$graph_display["fill"]='';
	}

	if($report_month!="Last 30 Days")
	{
    	$year = $_REQUEST['year'];
	}

	// delete old image files before creating a new one
	$png_files = glob("tmp/*.png");
	foreach($png_files AS $png)
	{
		if(!unlink($png))
		{
    		echo "unable to delete image $png";
		}
	}

	$graph_display['width'] = 650;
	$graph_display['height'] = 411;

	$graph_display['graph'] = "tmp/".$ship.$Title.$graph_display["Sub_title_start"].$graph_display["Sub_title_end"].".png";

	// Some data
	// $data = array($Total_kWh_Cost,$Total_kW_Cost,$Taxes_Add_Fees);

	// Create the Pie Graph.
	// $graph = new PieGraph(835,325);
	$graph = new PieGraph($graph_display['width'],$graph_display['height']);
	$graph->SetMargin(75,50,30,150);
	// $graph->SetMarginColor('black');

	$theme_class= new VividTheme;
	$graph->SetTheme($theme_class);

	// Set A title for the plot
	$graph->title->Set($Title);
	$graph->title->SetColor("black");
	$graph->title->SetFont(FF_ARIAL,FS_BOLD,16);

	$graph->subtitle->Set($graph_display["Sub_title_start"].$graph_display["fill"].$graph_display["Sub_title_end"]);
	$graph->subtitle->SetColor("#383838");

	// Create
	$p1 = new PiePlot3D($data);
	$graph->Add($p1);
	// $graph->SetMargin(20,20,20,20);
	$legends = array('Total Energy Charges','Total Demand Charges','Taxes and Other Fees');

	$p1->SetLegends($legends);
	$p1->ShowBorder();
	$p1->SetColor('black');
	$p1->SetSize(0.33);
	// $p1->SetCenter(0.25);
	$p1->SetSliceColors(array('#039','red','gray'));
	$p1->value->SetFont(FF_ARIAL,FS_NORMAL,10);
	$p1->ExplodeAll(4);
	// $p1->SetLabelMargin(0);
	$p1->SetLabels(array("%.1f%%","%.1f%%","%.1f%%",),1);
	$p1->value->SetColor('black');

	// $graph->legend->SetPos(.80,.70,'left','top');
	// $graph->legend->SetLayout(LEGEND_VERT);

	$graph->Stroke($graph_display['graph']);

	return $graph_display;
}

?>
