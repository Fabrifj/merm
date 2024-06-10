<?php

const ONE_MIN = 60;
const FIVE_MIN = 300;
const FIFTEEN_MIN = 900;
const ONE_HOUR = 3600;

const METER_DISABLED = 0;
const METER_ENABLED = 1;
const METER_PULSE_ENABLED = 2;
const METER_OUTPUT_ONLY_ENABLED = 3;

const MAX_DEMAND = 1000;

function MySqlFailure($Reason)
{
  global $con;
  global $log;

  $sql_errno = mysql_errno($con);

  if($sql_errno>0) {
    $log->error("(MySQL Error): $Reason");
    $log->error("(MySQL Error): $sql_errno: ".mysql_error($con));
    ob_end_flush();   // send any cached stuff and stop caching.
    sleep(1);
    exit;
  } else {
    // the following counts the number of warnings in the query and prints them out.
    $warningCountResult = mysql_query("SELECT @@warning_count");

    if ($warningCountResult) {
      $warningCount = mysql_fetch_row($warningCountResult);
      if($warningCount[0] > 0) {
        $warningDetailResult = mysql_query("SHOW WARNINGS");
        if ($warningDetailResult ) {
          while ($warning = mysql_fetch_array($warningDetailResult)) {
            foreach ($warning AS $key => $value) {
              if($value!==$value_repeat) {
                $value = $value." ";	// build all of the warnings into one.
              }

              $value_repeat = $value;		// make sure the warning isn't repeated.
            }

            $log->warning("(MySQL Warning): $value");		// print out the warnings
          }
        }
      }
    }
  }
}

function utility_check($ship) {
  // query for finding the correct
  // utility for a particular ship

  $sql = "SELECT utility FROM Aquisuite_List WHERE aquisuitetablename='$ship'";
  $RESULT = mysql_query($sql);

  if(!$RESULT) {
    MySqlFailure("utility check failed");
  }

  $row_utility=mysql_fetch_array($RESULT);
  $utility=$row_utility[0];

  return $utility;
}

function getMeterState($LOOPNAME,$aquisuitetable,$devicetablename)
{
  global $log;

  $status = METER_ENABLED;	//default
  $sql = "SELECT * FROM $aquisuitetable WHERE devicetablename='$devicetablename'";
  $result = mysql_query($sql);
  $log->logInfo(sprintf("getMeterState: %s \n",$sql));

  if(!$result) {
    $log->logInfo(sprintf("%s:Unable to select meter status [%s]\n",$LOOPNAME,$sql));
    MySqlFailure("unable to select devicetable from $devicetablename");
    return $status;
  }

  $row = mysql_fetch_array($result);
  if ($row) {
    $status = $row['meter_status'];
  } else {
    $log->logInfo(sprintf("%s:Unable to read meter status [%s]\n",$LOOPNAME,$sql));
  }

  return $status;
}

function  getLogInterval($aquisuitetable, $loopname) {
  global $log;

  $log_interval = 5; // set default logperiod in minutes
  $sql = "SELECT logperiod FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetable'";	// get log period from Aquisuite_List
  $result = mysql_query($sql);
  $log->logInfo(sprintf("%s:Log interval sql %s\n", $loopname, $sql));

  if(!$result) {
    $log->logInfo(sprintf("%s:Log interval Select failed %s\n", $loopname, $sql));
    MySqlFailure("GETLOGINTERVAL failed");
  }

  $row = mysql_fetch_row($result);
  if (!$row) {
    $log->logInfo(sprintf("%s:Log interval Fetch failed\n", $loopname));
    MySqlFailure("GETLOGINTERVAL failed");
  }

  $log->logInfo(sprintf("%s:Log interval %d minutes\n", $loopname, $log_interval));
  $log_interval = $row[0] * ONE_MIN; //log interval in seconds
  $log->logInfo(sprintf("%s:Log interval %d seconds\n", $loopname, $log_interval));

  return $log_interval;
}

function timezone($ship, $loopname) {
  global $log;

  $tzaquisuite = "timezoneaquisuite";

  $sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";		// get the aquisuite timezone in the Aquisuite_List
  $result = mysql_query($sql);

  if(!$result)
  {
    $log->logInfo(sprintf("%s:timezone mysql select failed\n", $loopname));
    MySqlFailure("could not find $tzaquisuite from $ship ");
  }
  $row = mysql_fetch_row($result);
  if (!$row)
  {
    $log->logInfo(sprintf("%s:timezone rows failed\n", $loopname));
  }

  $timezoneaquisuite = $row[0];

  $sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";		// get the PHP recognized timezone value
  $result = mysql_query($sql);

  if(!$result)
  {
    $log->logInfo(sprintf("%s:timezone mysql select result failed\n", $loopname));
    MySqlFailure("could not locate timezone ");
  }
  $row = mysql_fetch_row($result);
  if (!$row)
  {
    $log->logInfo(sprintf("%s:timezone rows2 failed\n", $loopname));
  }
  $timezone = $row[0];

  return $timezone;
}

function fixTimeGap($loopname, $devicetablename, $demand_time, $log_interval,$power, $pulse_meter, $pulse_demand_str) {
  global $log;

  $sql_check="SELECT * FROM $devicetablename WHERE time='$demand_time'";
  $log->logInfo(sprintf("%s:fixtimegap sql %s", $loopname, $sql_check));

  if ($value_check = mysql_query($sql_check))
  {
    if ($value = mysql_fetch_array($value_check))
    {
      if (!$pulse_meter)
      {
        $retvalue = $value["Average_Demand"];
        $log->logInfo(sprintf("%s:fixtimegap not pulse value %f", $loopname, $retvalue));
      }
      else
      {
        $retvalue = $value["$pulse_demand_str"];
        $log->logInfo(sprintf("%s:fixtimegap pulse value[%f]", $loopname,$retvalue));
      }
      return round($retvalue,2);
    }
  }

  if ($log_interval > 0)
    $log_interval_minutes = ($log_interval / ONE_MIN);
  else
    $log_interval_minutes = 5; //default 5 minutes
  $calc_kw = ($power / ($log_interval_minutes)) * 60; //get average for hour;
  $calc_kw = round($calc_kw,2);
  $log->logInfo(sprintf("%s:Avg not avail kWh power=%f int %d calc kW %f\n",  $loopname, $power, $log_interval,$calc_kw));

  return($calc_kw);     //need to add check for max possible kWh
}

function lastUpdatedEntry($LOOPNAME, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW) {
  global $log;

  $log->logInfo(sprintf("%s:start Last Updated Entry\n",$LOOPNAME));

  $someTimeAgo  = date('Y-m-d 00:00:00',strtotime('-1 week'));
  $sql_check = sprintf("SELECT time,%s,%s,%s,%s FROM %s WHERE time LIKE '%s'", $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo );
  $log->logInfo(sprintf("%s: %s\n",$LOOPNAME, $sql_check));

  $value_check=mysql_query($sql_check);
  if (!$value_check)
  {
    $log->logInfo(sprintf("%s:LastUpdatedEntry SQL Result failure 6 months ago", $LOOPNAME));
    $someTimeAgo = date('Y-m-d H:i:s',strtotime('-6 month'));
  }
  else
  {
    $value=mysql_fetch_array($value_check);
    if (!$value)
    {
      $log->logInfo(sprintf("%s:LastUpdatedEntry  SQL value failure 6 months ago", $LOOPNAME));
      $someTimeAgo = date('Y-m-d H:i:s',strtotime('-6 month'));
    }
    else   if ((($value["$Peak_kWh"]> 0) || ($value["$Off_Peak_kWh"]> 0)) && (($value["$Peak_kW"] > 0) || ($value["$Off_Peak_kW"] > 0)))
    {
      $log->logInfo(sprintf("%s:LastUpdatedEntry one week ago \n", $LOOPNAME));
    }
  }

  return $someTimeAgo;
}

function utility_cost($loopname, $aquisuitetable, $devicetablename) {
  global $log;
  global $device_class;
  global $serial_number;

  // DEVICE CLASS 2 Class 27 is series of inputs that could be defined to various
  // meters. Here a class 27 pulse meter reading kWh which will serve the same function as a class 2.

  $log->logInfo(sprintf("%s: Start New Utility Cost\n", $loopname) );

  $meterStatus =  getMeterState($loopname,$aquisuitetable,$devicetablename);
  $log->logInfo(sprintf("%s: Meter Status %d device table %s\n", $loopname, $meterStatus, $devicetablename ));
  if ($meterStatus ==  METER_DISABLED)
    return;

  if($device_class!=2 && $device_class!=27)
  {
    $log->logInfo(sprintf("%s: Utility_cost return\n", $loopname) );
    $log->logInfo(sprintf("%s: Utility_cost return modbusclass %d \n", $loopname,$device_class));
    return;
  }

  $utility = utility_check($aquisuitetable); // check to see if device has an associated utility table

  $log->logInfo(sprintf("%s:utility check %s\n", $loopname, $utility));

  if(empty($utility))
  {
    $log->logInfo(sprintf("%s: Utility Unavailable\n",$loopname));
  }

  $db = 'bwolff_eqoff';
  $timezone = timezone($aquisuitetable, $loopname);		// check for current time zone
  $pulse_demand_str = '';
  $pulse_meter = FALSE;

  //$log->logInfo(sprintf("%s: %s timezone %s \n", $loopname, $aquisuitetable,$timezone));

  $sql_rates = "SELECT * FROM `$utility`";
  $NowDate = date('Y-m-d H:i:s',strtotime('now')); //get recent rate schedule
  $sql_rates = sprintf("SELECT * FROM `$utility` WHERE Rate_Date_End >= '%s' AND Rate_Date_Start <= '%s'", $NowDate, $NowDate);

  $rate_q = mysql_query($sql_rates);
  if(!$rate_q) { $log->logInfo( sprintf("%s unable to process select mysql request %s", $loopname, $sql_rates)); return;  }

  $log_interval = getLogInterval($aquisuitetable, $loopname);
  $log->logInfo(sprintf("%s: %s timezone %s log interval %d \n", $loopname, $aquisuitetable, $timezone, $log_interval));

  $sqlLogData = "SELECT time FROM `$devicetablename` ORDER BY time DESC LIMIT 1";
  $log->logInfo( sprintf("%s utility_cost [%s]", $loopname, $sqlLogData));
  $resultLog = mysql_query($sqlLogData);
  if (mysql_num_rows($resultLog) > 0)
  {
    $lastLoggedData = mysql_fetch_row($resultLog);
    $lastDataPoint = $lastLoggedData[0];
    $log->logInfo( sprintf("%s utility_cost: Last Logged Data %s", $loopname, $lastDataPoint));
  }
  else
  {
    $lastDataPoint = $NowDate;
    $log->logInfo( sprintf("%s utility_cost: Default Last Logged Data %s", $loopname, $lastDataPoint));
  }

  // SCE&G UTILITY RATE 24
  if ($utility=="SCE&G_Rates")
  {
    $cost=mysql_fetch_array($rate_q);
    if(!$cost) { echo "unable to process mysql fetch cost" .  $sql_rates . "\n"; exit;  }

    $log->logInfo( sprintf("%s: %s\n", $loopname, $utility));

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

    $Summer_Start = idate('m',strtotime($cost[10]));
    $Summer_End = idate('m',strtotime($cost[11]));
    $Peak_Time_Summer_Start = idate('H',strtotime($cost[12]));
    $Peak_Time_Summer_Stop = idate('H',strtotime($cost[13]));
    $Peak_Time_Non_Summer_Start = idate('H',strtotime($cost[14]));
    $Peak_Time_Non_Summer_Stop = idate('H',strtotime($cost[15]));
    $Peak_Time_Non_Summer_Start2 = idate('H',strtotime($cost[16]));
    $Peak_Time_Non_Summer_Stop2 = idate('H',strtotime($cost[17]));
    $MayOct_Start = idate('H',strtotime($cost[18]));
    $MayOct_End = idate('H',strtotime($cost[19]));
    $log->logInfo(sprintf("%s: rate start %s rate end %s  MayStart %s MayEnd %s\n", $loopname, $cost[20], $cost[21] , $MayOct_Start, $MayOct_End));

    //$log->logInfo( sprintf("%s:sumMonth %d sumendMonth %d summer startH %d stopH %d  nonsumstart %d nonsumend %d start2 %d stop2 %d \n",
    // $loopname, $Summer_Start, $Summer_End, $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop,
    // $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
    // $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ));

    date_default_timezone_set("UTC"); //go back to UTC time

    $db = 'bwolff_eqoff';
    $EC_kWh='Energy_Consumption';
    $Peak_kW="Peak_kW";
    $Peak_kWh="Peak_kWh";
    $Off_Peak_kW="Off_Peak_kW";
    $Off_Peak_kWh="Off_Peak_kWh";

    if($device_class==27) //pulse meters set real power
      $pulse_meter = TRUE;
    else
      $pulse_meter = FALSE;
    if($device_class==27 && $serial_number=="001EC6001635") //Cape Knox
    {
      $EC_kWh = 'Shore_Power';
      $rp = 'Real_Power';
      $pulse_demand_str = 'Shore_Power_Demand';
    } else if($device_class==27 && $serial_number=="001EC6001433") //Cape Kennedy
    {
      $EC_kWh = 'Shore_Power_(kWh)';
      $rp = 'Real_Power';
      $pulse_demand_str = 'Shore_Power_(kWh)_Demand';
    }
    //$log->logInfo( sprintf("%s: %s\n", $loopname, $EC_kWh));
    $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));
    $someTimeAgo = lastUpdatedEntry($loopname, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW );
    $sql_update = sprintf("SELECT time, `%s` ,`%s` ,`%s` , `%s` , `%s`  FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW,$devicetablename, $someTimeAgo, $NowPlusDay);

    $log->logInfo( sprintf("%s: %s\n", $loopname, $sql_update));

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)
    {
      $log->logInfo( sprintf("%s: ERROR SQL Select %s\n", $loopname, $sql_update));
      echo sprintf("%s:SQL Result failure \n", $loopname);
      MySqlFailure("invalid msql_query");
      return;
    }

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining) $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $loopname));

    while($row=mysql_fetch_array($RESULT_update))
    {
      $time[] = $row['time'];		// UTC Time
      $kWh[] = $row["$EC_kWh"];		// Running Total Energy Consumption kWh
      $O_kWh_value[] = $row["$Peak_kWh"];
      $OP_kWh_value[] = $row["$Off_Peak_kWh"];
      $O_kW_value[] = $row["$Peak_kW"];
      $OP_kW_value[] = $row["$Off_Peak_kW"];
    }

    $i=-1;
    $countd = 0;
    if (isset($time))
      $countd=count($time);

    $log->logInfo(sprintf("%s: loop for %d \n", $loopname, $countd));
    while($i<=$countd-15) //array bounds check
    {
      $i++;

      if(($kWh[$i]!==0 )&& ($kWh[$i] > $kWh[$i+1]))		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
      {
        $Power=$kWh[$i] - $kWh[$i+1];		// Peak kWh
        $Power_time=$time[$i];			// Peak kWh time


        //$log->logInfo(sprintf("%s: loop %d +1[%d] %s +1[%s] \n", $loopname, $i,($i+1), $time[$i],$time[$i+1]));

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion of peak times
        $timestamp = strtotime($time[$i].'UTC');	// timestamp the time in the device timezone format
        // set up the month hour and week for the correct timezone
        $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
        $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
        date_default_timezone_set("UTC"); //set timezone back to UTC

        // determine whether the kWh value has occured during Peak or Off Peak time.
        // Set the query for the device utility column update.
        if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
        {
          $timeIsPeak = TRUE;
          $sql_str="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power'";

        }
        else if  ((($month<$Summer_Start || $month>=$Summer_End) &&
          ($week<6 && $week>0) &&
          (($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop) ||
          ($hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2)) &&
          ($month !=5 && $month != 10) ) ||
          (($month==5 || $month==10) && ($hour>=$MayOct_Start && $hour<$MayOct_End) ) )
        {
          $timeIsPeak = TRUE;
          $sql_str="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power'";
        }
        else
        {
          $timeIsPeak = FALSE;
          $sql_str="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power'";
        }
        if($pulse_meter) //pulse meters set real power
        {
          $t_int = strtotime($time[$i]) - strtotime($time[$i+1]);
          $rp_value = ($Power/($t_int/ONE_HOUR));
          $sql_peak_kWh = sprintf("%s, `%s`=%f WHERE %s.`time`='%s';",$sql_str, $rp, $rp_value, $devicetablename, $Power_time);
        }
        else
        {
          $sql_peak_kWh = sprintf("%s WHERE %s.`time`='%s';",$sql_str, $devicetablename, $Power_time);
        }
        $log->logInfo(sprintf("%s sce-g:Sql [%s] \n", $loopname,  $sql_peak_kWh ));


        if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0))
        {
          $peak_query=mysql_query($sql_peak_kWh);
          // For Aquisuite full debug mode prints out information to tell the user if the kWh utility columns have been updated.
          if(!$peak_query)
          {
            $log->logInfo(sprintf("%s:Error not updated %s %f %s  \n", $loopname, $Power_time, $Power, $sql_peak_kWh ));
            echo "$devicetablename $Power_time : $Power not updated"."</br>";
          }
          else
          {
            echo "$devicetablename $Power_time : $Power updated"."</br>";
          }
        }


        $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
        //$log->logInfo(sprintf("%s: calculatedinterval %d \n", $loopname, $interval));

        // the Demand kW formula varies based on the data logging time interval.
        if ($log_interval == 300) // 5 minute log
        {
          if($i<($countd-3)) //array bounds check
          {
            $demand_15=($kWh[$i] - $kWh[$i+3])*4;
            $demand_15_time=$time[$i];
            if ((strtotime($time[$i]) - strtotime($time[$i+3]) != 900 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) //900 is 15 minutes
            {
              $demand_15 = fixTimeGap($loopname, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_15_time, $log_interval, $demand_15,(strtotime($time[$i]) - strtotime($time[$i+3]))));
            }
          }
        }
        else  if ($log_interval == 60) // 1 minute log
        {
          if($i<($countd-15)) //array bounds check
          {
            $demand_15=($kWh[$i] - $kWh[$i+15])*4;
            $demand_15_time=$time[$i];
            if ((strtotime($time[$i]) - strtotime($time[$i+15]) != 900 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0))  //900 is 15 minutes
            {
              $demand_15 = fixTimeGap($loopname, $devicetablename, $demand_15_time, $log_interval, $Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15));
            }
          }
        }
        else
        {
          $demand_15_time=$time[$i];
          $demand_15 = fixTimeGap($loopname, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
          //echo sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15);
        }

        if(!empty($demand_15))     // only update the demand if the value has been assigned.
        {

          if($timeIsPeak)
          {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
          }
          else
          {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
          }

          if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0))
          {
            $kW_peak_query=mysql_query($sql_peak_kW);

            if(!$kW_peak_query)
            {
              echo "$devicetablename $loopname $demand_15_time : $demand_15 not updated SQL kw peak query fail"."</br>";
              $log->logInfo(sprintf("%s: %s %s not updated SQL kw peak query fail \n", $loopname, $utility,$demand_15_time ));
            }
            else
            {
              echo "$devicetablename $demand_15_time : $demand_15 updated"."</br>";
            }


          }
          else
          {
            echo "$devicetablename $demand_15_time ALREADY UPDATED-- Peak kW : $O_kW_value[$i] -- Off_Peak_kW : $OP_kW_value[$i]"."\n</br>";
            break;
          }
        }
        else
        {
          $log->logInfo(sprintf("%s: %s Demand not set\n", $loopname, $utility));
        }
      }
    }
    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value);//clear arrays as precaution
  } //end SCE&G utility

  // VIRGINIA DOMINION RATE GS 3
  if ($utility=="Virginia_Dominion_Rates")
  {
    $cost=mysql_fetch_array($rate_q);
    if (!$cost)  { $log->logInfo(sprintf("%s:util fetch array sql error",$loopname)); }

    $log->logInfo(sprintf("%s: %s\n", $loopname, $utility));

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

    $Summer_Start = idate('m',strtotime($cost['Summer_Start']));
    $Summer_End = idate('m',strtotime($cost['Summer_End']));
    $Peak_Time_Summer_Start = idate('H',strtotime($cost['Peak_Time_Summer_Start']));
    $Peak_Time_Summer_Stop = idate('H',strtotime($cost['Peak_Time_Summer_Stop']));
    $Peak_Time_Non_Summer_Start = idate('H',strtotime($cost['Peak_Time_Non_Summer_Start']));
    $Peak_Time_Non_Summer_Stop = idate('H',strtotime($cost['Peak_Time_Non_Summer_Stop']));
    date_default_timezone_set("UTC"); //go back to UTC time

    $db = 'bwolff_eqoff';
    $EC_kWh='Energy_Consumption';
    $Peak_kW="Peak_kW";
    $Peak_kWh="Peak_kWh";
    $Off_Peak_kW="Off_Peak_kW";
    $Off_Peak_kWh="Off_Peak_kWh";
    $RP_kVAR="Reactive_Power";
    $kVAR_30="30_Min_Reactive_kVAR";

    $log->logInfo(sprintf("%s: check for last update entry \n", $loopname ));

    $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));
    $someTimeAgo = lastUpdatedEntry($loopname, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW);

    $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $RP_kVAR, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW, $kVAR_30, $devicetablename, $someTimeAgo, $NowPlusDay);

    $log->logInfo(sprintf("%s: %s\n", $loopname, $sql_update));

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)
    {
      $log->logInfo(sprintf("%s:Utility SQL select failure \n", $loopname));
      MySqlFailure("invalid msql_query");
      return;
    }

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining) {  $log->logInfo(sprintf("%s:Utility SQL rows remaining failure \n", $loopname));}

    while($row=mysql_fetch_array($RESULT_update))    	// changed to assoc array $row=mysql_fetch_array($RESULT_update)
    {
      if (isset($row['time']))
      {
        $time[] = $row['time'];
      }

      if (isset($row["$EC_kWh"]))
      {
        $kWh[] = $row["$EC_kWh"];
      }
      if (isset($row["$RP_kVAR"]))
      {
        $kVAR[] = $row["$RP_kVAR"];
      }
      $kVAR30_value[] =  $row["$kVAR_30"];
      $O_kWh_value[] = $row["$Peak_kWh"];
      $OP_kWh_value[] = $row["$Off_Peak_kWh"];
      $O_kW_value[] = $row["$Peak_kW"];
      $OP_kW_value[] = $row["$Off_Peak_kW"];
    }

    $i=-1;
    $countd = 0;
    if (isset($time))
      $countd=count($time);

    $log->logInfo(sprintf("%s:Loop for %d\n",$loopname,$countd));
    while ($i<=$countd-15) //array bounds check
    {
      $i++;

      if($kWh[$i]>0 && $kWh[$i]>$kWh[$i+1])
      {

        $Power=$kWh[$i] - $kWh[$i+1];
        $Power_time=$time[$i];

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
        $timestamp = strtotime($time[$i].' UTC');
        // set up the month hour and week for the correct timezone
        $month = idate('m',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
        date_default_timezone_set("UTC"); //set timezone back to UTC

        //$log->logInfo(sprintf("%s:date time_d %s %d m %d h %d w %d\n", $loopname,$time[$i - 1], $timestamp, $month, $hour, $week) );


        if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
        {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        }
        else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && $hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop)
        {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        }
        else
        {
          $timeIsPeak = FALSE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        }


        if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0))
        {
          $peak_query=mysql_query($sql_peak_kWh);

          if(!$peak_query)
          {
            echo "$devicetablename $Power_time : $Power not updated kWh"."</br>";
            $log->logInfo(sprintf("%s: Not Updated kWh %s %f\n", $loopname, $Power_time, $Power) );
          }
          else
          {
            echo "$devicetablename $Power_time : $Power updated kWh"."</br>";
            //$log->logInfo(sprintf("%s: kWh %s %f\n", $loopname, $Power_time, $Power) );
          }
        }

        $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
        //$log->logInfo(sprintf("%s: calculated interval %d\n", $loopname, $interval) );

        if ($log_interval == 300) // 5 minute log
        {
          if($i<($countd-6)) //array bounds check
          {
            $kVAR_Sum = $kVAR[$i+1]+$kVAR[$i+2]+$kVAR[$i+3]+$kVAR[$i+4]+$kVAR[$i+5]+$kVAR[$i+6];
            $kVAR_Avg = $kVAR_Sum/6;
            $kVAR_time = $time[$i];

            $demand_30 = ($kWh[$i] - $kWh[$i+6])*2;
            $demand_30_time = $time[$i];
            if ((strtotime($time[$i]) - strtotime($time[$i+6]) != 1800 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) //1800sec = 30 minutes
            {
              $demand_30 = fixTimeGap($loopname, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_30_time, $log_interval, $demand_30,(strtotime($time[$i]) - strtotime($time[$i+6]))));
            }
            $log->logInfo(sprintf("%s: 30 min demand %s %f\n", $loopname, $demand_30_time, $demand_30) );
          }
        }
        else
        {
          $kVAR_Sum = '';
          $demand_30_time = $time[$i];
          $demand_30 = fixTimeGap($loopname, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
          $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_30));
        }

        if (!empty($kVAR_Sum))
        {
          $sql_kVAR="UPDATE `$db`.`$devicetablename` SET `$kVAR_30`='$kVAR_Avg' WHERE `$devicetablename`.`time`='$kVAR_time';";

          if ($kVAR30_value[$i] == 0)
          {
            $sql_kVAR_demand=mysql_query($sql_kVAR);

            if(!$sql_kVAR_demand)
            {
              $log->logInfo(sprintf("%s:utilityCost [%s]\n", $loopname, $sql_kVAR) );
              $log->logInfo(sprintf("%s:utilityCost 30 min reactive kvar not updated\n", $loopname) );
              echo "$devicetablename $kVAR_time : $kVAR_Avg 30 Min Reactive kVAR not updated"."</br>";
            }
            else
            {
              echo "$devicetablename $kVAR_time : $kVAR_Avg 30 Min Reactive kVAR updated"."</br>";
            }
          }
        }

        if(!empty($demand_30))
        {
          if ($timeIsPeak)
          {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          }
          else
          {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          }

          if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0))
          {
            $kW_peak_query=mysql_query($sql_peak_kW);

            if(!$kW_peak_query)
            {
              $log->logInfo(sprintf("%s:utilityCost [%s]\n", $loopname, $kW_peak_query) );
              $log->logInfo(sprintf("%s:DEMAND30 not updated\n", $loopname) );
              echo "$demand_30_time : $demand_30 not updated kW"."\n</br>";
            }
            else
            {
              echo "$demand_30_time : $demand_30 updated kW"."\n</br>";
            }
          }
          else
          {
            echo "$devicetablename $demand_30_time ALREADY UPDATED-- Peak kW : $O_kW_value[$i] -- Off_Peak_kW : $OP_kW_value[$i]"."</br>";
            break;
          }
        }
      }
    }
    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value, $kVAR, $kVAR30_value);//clear arrays as precaution
  }//end va rates

  if($utility=="Virginia_Electric_and_Power_Co") {
    $cost=mysql_fetch_array($rate_q);

    if (!$cost) {
      $log->logInfo(sprintf("%s:util fetch array sql error",$loopname));
    }

    $log->logInfo(sprintf("%s: %s\n", $loopname, $utility));

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

    $Summer_Start = idate('m',strtotime($cost['Summer_Start']));
    $Summer_End = idate('m',strtotime($cost['Summer_End']));
    $Peak_Time_Summer_Start = idate('H',strtotime($cost['Peak_Time_Summer_Start']));
    $Peak_Time_Summer_Stop = idate('H',strtotime($cost['Peak_Time_Summer_Stop']));
    $Peak_Time_Non_Summer_Start_AM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Start_AM']));
    $Peak_Time_Non_Summer_Stop_AM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Stop_AM']));
    $Peak_Time_Non_Summer_Start_PM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Start_PM']));
    $Peak_Time_Non_Summer_Stop_PM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Stop_PM']));
    date_default_timezone_set("UTC"); //go back to UTC time

    $db = 'bwolff_eqoff';
    $EC_kWh='Energy_Consumption';
    $Peak_kW="Peak_kW";
    $Peak_kWh="Peak_kWh";
    $Off_Peak_kW="Off_Peak_kW";
    $Off_Peak_kWh="Off_Peak_kWh";

    $log->logInfo(sprintf("%s: check for last update entry \n", $loopname ));

    $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));
    $someTimeAgo = lastUpdatedEntry($loopname, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW);

    $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo, $NowPlusDay);

    $log->logInfo(sprintf("%s: %s\n", $loopname, $sql_update));

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)
    {
      $log->logInfo(sprintf("%s:Utility SQL select failure \n", $loopname));
      MySqlFailure("invalid msql_query");
      return;
    }

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining) {  $log->logInfo(sprintf("%s:Utility SQL rows remaining failure \n", $loopname));}

    while($row=mysql_fetch_array($RESULT_update))    	// changed to assoc array $row=mysql_fetch_array($RESULT_update)
    {
      if (isset($row['time']))
      {
        $time[] = $row['time'];
      }

      if (isset($row["$EC_kWh"]))
      {
        $kWh[] = $row["$EC_kWh"];
      }

      $O_kWh_value[] = $row["$Peak_kWh"];
      $OP_kWh_value[] = $row["$Off_Peak_kWh"];
      $O_kW_value[] = $row["$Peak_kW"];
      $OP_kW_value[] = $row["$Off_Peak_kW"];
    }

    $i=-1;
    $countd = 0;
    if (isset($time))
      $countd=count($time);

    $log->logInfo(sprintf("%s:Loop for %d\n",$loopname,$countd));
    while ($i<=$countd-15) //array bounds check
    {
      $i++;

      if($kWh[$i]>0 && $kWh[$i]>$kWh[$i+1])
      {

        $Power=$kWh[$i] - $kWh[$i+1];
        $Power_time=$time[$i];

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
        $timestamp = strtotime($time[$i].' UTC');
        // set up the month hour and week for the correct timezone
        $month = idate('m',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
        date_default_timezone_set("UTC"); //set timezone back to UTC

        //$log->logInfo(sprintf("%s:date time_d %s %d m %d h %d w %d\n", $loopname,$time[$i - 1], $timestamp, $month, $hour, $week) );

        $IS_SUMMER_MONTH = ($month>=$Summer_Start && $month<$Summer_End);
        $IS_PEAK_SUMMER_HOUR = ($hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop);
        $IS_NON_SUMMER_MONTH = ($month<$Summer_Start || $month>=$Summer_End);
        $IS_PEAK_NON_SUMMER_HOUR_AM = ($hour>=$Peak_Time_Non_Summer_Start_AM && $hour<$Peak_Time_Non_Summer_Stop_AM);
        $IS_PEAK_NON_SUMMER_HOUR_PM = ($hour>=$Peak_Time_Non_Summer_Start_PM && $hour<$Peak_Time_Non_Summer_Stop_PM);
        $IS_WEEKDAY = ($week<6 && $week>0);

        if($IS_SUMMER_MONTH && $IS_PEAK_SUMMER_HOUR && $IS_WEEKDAY) {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        } else if($IS_NON_SUMMER_MONTH && $IS_WEEKDAY && ($IS_PEAK_NON_SUMMER_HOUR_AM || $IS_PEAK_NON_SUMMER_HOUR_PM)) {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        } else {
          $timeIsPeak = FALSE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        }


        if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0)) {
          $peak_query=mysql_query($sql_peak_kWh);

          if(!$peak_query) {
            $log->logInfo(sprintf("%s: Not Updated kWh %s %f\n", $loopname, $Power_time, $Power) );
          } else {
            $log->logInfo(sprintf("%s: kWh %s %f\n", $loopname, $Power_time, $Power) );
          }
        } else {
          $log->logInfo(sprintf("%s: kWh"));
        }

        $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
        //$log->logInfo(sprintf("%s: calculated interval %d\n", $loopname, $interval) );
        // 5 minute log
        if ($log_interval == 300) {
          //array bounds check
          if($i<($countd-6)) {
            $demand_30 = ($kWh[$i] - $kWh[$i+6])*2;
            $demand_30_time = $time[$i];
            //1800sec = 30 minutes
            if ((strtotime($time[$i]) - strtotime($time[$i+6]) != 1800 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) {
              $demand_30 = fixTimeGap($loopname, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_30_time, $log_interval, $demand_30,(strtotime($time[$i]) - strtotime($time[$i+6]))));
            }
            $log->logInfo(sprintf("%s: 30 min demand %s %f\n", $loopname, $demand_30_time, $demand_30) );
          }
        } else {
          $demand_30_time = $time[$i];
          $demand_30 = fixTimeGap($loopname, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
          $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_30));
        }

        if(!empty($demand_30)) {
          if ($timeIsPeak) {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          } else {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          }

          if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0)) {
            $kW_peak_query=mysql_query($sql_peak_kW);

            if(!$kW_peak_query) {
              $log->logInfo(sprintf("%s:utilityCost [%s]\n", $loopname, $kW_peak_query) );
              $log->logInfo(sprintf("%s:DEMAND30 not updated\n", $loopname) );
            } else {
              $log->logInfo(sprintf("%s: 30 min demand updated", $loopname));
            }
          } else {
              $log->logInfo(sprintf("%s: 30 min demand already updated", $loopname));
            break;
          }
        }
      }
    }
    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value, $kVAR, $kVAR30_value);//clear arrays as precaution
  }

  $log->logInfo(sprintf("%s: Utility_cost END\n", $loopname) );
}

require_once '../erms/includes/KLogger.php';
require_once '../conn/mysql_pconnect-all.php';

$log = new KLogger ("log", KLOGGER::DEBUG);

$devicetablename = $argv[1];

$aquisuitetable = explode("__", $devicetablename)[0];
$find_aquisuite_data = "SELECT Aquisuite_List.loopname, Aquisuite_List.SerialNumber, $aquisuitetable.modbusdevicenumber, $aquisuitetable.deviceclass FROM Aquisuite_List
                  LEFT JOIN $aquisuitetable
                  ON $aquisuitetable.SerialNumber = Aquisuite_List.SerialNumber
                  WHERE Aquisuite_List.aquisuitetablename='$aquisuitetable'
                  AND $aquisuitetable.function = 'main_utility'";
$log->logInfo("(main) find_aquisuite_data sql: $find_aquisuite_data");
$q = mysql_query($find_aquisuite_data);

if(!$q) {
    MySqlFailure("(main): Could not find loopname");
}

$row = mysql_fetch_row($q);
$loopname = $row[0];
$serial_number = $row[1];
$device_number = $row[2];
$device_class = $row[3];

utility_cost($loopname, $aquisuitetable, $devicetablename);

?>
