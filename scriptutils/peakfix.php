<?php
/*
 *  FIle: peakfix.php
 *  Author: Carole Snow
 * 
 * Desc: This file contails functions to process relay output on Acquisuite Modules
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/
/*
 *  peakfix.php is used to correct peak and off-peak values in the database.  This script is contains hard coded 
 * variables that need to be changed foe each ship.  Flags need to be turned off/on for each section you wish
 * to execute depending on the problem with the data.
 */


/** define constants   **/
const ONE_MIN = 60;
const FIVE_MIN = 300;
const FIFTEEN_MIN = 900;
const ONE_HOUR = 3600;

/**-----------------------------------------------------------------------------------------------------------------
 * reporting function be used to report and terminate.
 */
function ReportFailure($szReason)
{   
    Header("WWW-Authenticate: Basic realm=\"UploadRealm\"");    // realm name is actually ignored by the AcquiSuite.
    Header("HTTP/1.0 406 Not Acceptable");                      // generate a 400 series http server error response.

    $szNotes = sprintf("Rejected logfile upload"); 			 // print out some information about what failed, for the benifit of the log file.
    printf("FAILURE: $szReason\n");                			 // report failure to client.
    printf("NOTES:   $szNotes\n");

    ob_end_flush(); // send cached stuff, and stop caching. we've already kicked out the last header line.
    
    exit;
}


/*----------------------------------------------------------------------------------------------------*/
/* mySQL reporting function. terminates if error is fatal and prints out mySQL warnings for failure.
*/
function MySqlFailure($Reason)
{	
       	echo "mySQL FAILURE:"."</br>";
	$con = $_SESSION['con'];		// used for getting the mysql connector for error printing
	$sql_errno = mysql_errno($con); 
	
	if($sql_errno>0)
	{	
		echo "mySQL FAILURE: $Reason"."</br>";
		echo  "mySQL FAILURE: $Reason"."</br>".$sql_errno. ": " . mysql_error($con) . "</br>";
             	ob_end_flush();   // send any cached stuff and stop caching. 
                sleep(1); 
		exit;
	}
	else
	{
		
		// the following counts the number of warnings in the query and prints them out.
		
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
								$value = $value." ";	// build all of the warnings into one.
							}
							$value_repeat = $value;		// make sure the warning isn't repeated.
						}
						echo "mySQL WARNING: $value"."</br>";		// print out the warnings
					}
				}
			}
		}
	}
}

/*----------------------------------------------------------------------------------------------------*/
/*	the timezone function searches the Aquisuite_List for the aquisuite timezone and then matches it
	in the timezone table with a PHP recognized timezone. 
*/
function timezone($ship, $log, $LOOPNAME)
{
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";		// get the aquisuite timezone in the Aquisuite_List
	$result = mysql_query($sql);
	if(!$result)
	{
            	$log->logInfo(sprintf("%s:timezone mysql select failed\n", $LOOPNAME)); 
		MySqlFailure("could not find $tzaquisuite from $ship ");
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {
            $log->logInfo(sprintf("%s:timezone rows failed\n", $LOOPNAME)); 
        }

	$timezoneaquisuite = $row[0];

	$sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";		// get the PHP recognized timezone value
	$result = mysql_query($sql);
        if(!$result)
	{
             	$log->logInfo(sprintf("%s:timezone mysql select result failed\n", $LOOPNAME)); 
		MySqlFailure("could not locate timezone ");
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {   
            $log->logInfo(sprintf("%s:timezone rows2 failed\n", $LOOPNAME)); 
        }    
	$timezone = $row[0];

	return $timezone;
}
function reportdata( $devicetablename, $wrongtime)
 {
     global $log;
     global $LOOPNAME;
     global $timezone;  
     global  $Summer_Start;
     global $Summer_End; 
     global  $Peak_Time_Summer_Start;
     global  $Peak_Time_Summer_Stop;
     global  $Peak_Time_Non_Summer_Start;
      global  $Peak_Time_Non_Summer_Stop;
     global   $Peak_Time_Non_Summer_Start2;
     global   $Peak_Time_Non_Summer_Stop2;
     

            $EC_kWh='Energy_Consumption';
            
            if($_REQUEST['MODBUSDEVICECLASS']==27 && $_REQUEST['SERIALNUMBER']=="001EC6001433") 
            {
                    $EC_kWh = 'Shore_Power_(kWh)';
            }
            $Peak_kW="Peak_kW";
            $Peak_kWh="Peak_kWh";
            $Off_Peak_kW="Off_Peak_kW";
            $Off_Peak_kWh="Off_Peak_kWh";

           //get data for only one year to reduce amount of data      
            $start = date('Y-m-d H:i:s',strtotime('2014-01-01 00:00:00'));
            $now = date('Y-m-d H:i:s');           

            $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s  FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kW, $Off_Peak_kW, $Peak_kWh, $Off_Peak_kWh, $devicetablename, $start, $now);
           $log->logInfo(sprintf("%s \n", $sql_update) );
           
            $RESULT_update = mysql_query($sql_update);
            if (!$RESULT_update) $log->logInfo(sprintf("%s:SQL Result failure \n", $LOOPNAME) ); 

            $rows_remaining = mysql_num_rows($RESULT_update);
               if (!$rows_remaining)  $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME) ); 

            while($row=mysql_fetch_array($RESULT_update))
            {
                    // Put the device table contents into an array 
                    $time[] = $row['time'];		// UTC Time
                    $time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));		// Converting the time into device timezone)
                    $kWh[] = $row["$EC_kWh"];		// Running Total Energy Consumption kWh
                    $peak_kW_value[]=$row["$Peak_kW"];
                    $off_kW_value[]=$row["$Off_Peak_kW"];
                    $peak_kWh_value[]=$row["$Peak_kWh"];
                    $off_kWh_value[]=$row["$Off_Peak_kWh"];
                // $log->logInfo(sprintf("time %s %s %f\n", $row['time'], date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC)),$row["$EC_kWh"]) ); 

            }
            $countd=count($time_d);		// count timezone array.
		
            $i=-1;
            while($i<=$countd-2)
            {	
                $i++;

                $Power=$kWh[$i-1] - $kWh[$i];		// Peak kWh
                $Power_time=$time[$i-1];			// Peak kWh time
                
                
                if ($kWh[$i] == $kWh[$i-1])
                   continue;

                date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                $month = idate(m, strtotime(date('Y-m-d H:i:s', $Power_time)));
                $hour = idate(H,strtotime(date('Y-m-d H:i:s', $Power_time)));
                $week = idate(w,strtotime(date('Y-m-d H:i:s', $Power_time)));
                date_default_timezone_set("UTC"); //set timezone back to UTC
               //  $log->logInfo(sprintf("D15 time %s %d %s m %d h %d w %d\n",$demand_15_time,$timestamp_15,date('Y-m-d H:i:s', $timestamp_15), $month, $hour, $week) );           

                $peak=FALSE;
                if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
                {
                 $peak= TRUE;
                }
                else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
                {
                 $peak = TRUE;
                }
                
                if ($Power > 0)
                {
                    
                    
                    $zeros = 0;
                    if ($peak_kW_value[$i] == 0) $zeros++;
                    if ($off_kW_value[$i] == 0) $zeros++;
                    if ($peak_kWh_value[$i]  == 0) $zeros++;
                    if ($off_kWh_value[$i]  == 0) $zeros++;

                    if ($zeros > 2) 
                      $log->logInfo(sprintf("over 2 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 

                     if (($peak_kW_value[$i] != 0) && ($off_kWh_value[$i] != 0)) 
                        $log->logInfo(sprintf("1 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 

                     if (($peak_kWh_value[$i] != 0) && ($off_kW_value[$i] != 0)) 
                       $log->logInfo(sprintf("2 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 

                     if ($i > 1)
                     {    
                       if (($peak_kW_value[$i] > 0) and  ($peak_kW_value[$i-1] > 0))
                       {    
                         $maxPeak_kw = ($peak_kW_value[$i-1] * 1.5);
                          if ($peak_kW_value[$i] > $maxPeak_kw) 
                           $log->logInfo(sprintf("3 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 
                       }
                        if (($peak_kWh_value[$i] > 0) and  ($peak_kWh_value[$i-1] > 0))
                       {    
                         $maxPeak_kwh = ($peak_kWh_value[$i-1] * 1.5);
                         if ($peak_kWh_value[$i] > $maxPeak_kwh) 
                          $log->logInfo(sprintf("4 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 
                       }  
                      if (($off_kW_value[$i] > 0) and  ($off_kW_value[$i-1] > 0))
                       {    
                         $max_kw = ($off_kW_value[$i-1] * 1.5);
                         if ($off_kW_value[$i] > $max_kw) 
                          $log->logInfo(sprintf("5 %s RP %f  peak %f off %f peakH %f offH %f\n", $Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 
                       }  
                      if (($off_kWh_value[$i] > 0) and  ($off_kWh_value[$i-1] > 0))
                       {    
                         $max_kw = ($off_kWh_value[$i-1] * 1.5);
                         if ($off_kWh_value[$i] > $max_kw)
                          $log->logInfo(sprintf("6 max %f %s RP %f  peak %f off %f peakH %f offH %f\n", $max_kw,$Power_time, $Power, $peak_kW_value[$i], $off_kW_value[$i], $peak_kWh_value[$i], $off_kWh_value[$i])); 
                       }  
                    }
              }  
           }    
       //$log->logInfo(sprintf("%s: ReCalculate END\n", $LOOPNAME) ); 
    
}/*end function*/

function fixkWZeros($aquisuitetable, $devicetablename,$utility, $wrongtime,$maxValue)
 {
     global $log;
     global $LOOPNAME;
     global $timezone;  
     global  $Summer_Start;
     global $Summer_End; 
     global  $Peak_Time_Summer_Start;
     global  $Peak_Time_Summer_Stop;
     global  $Peak_Time_Non_Summer_Start;
      global  $Peak_Time_Non_Summer_Stop;
     global   $Peak_Time_Non_Summer_Start2;
     global   $Peak_Time_Non_Summer_Stop2;

    if ($utility=="SCE&G_Rates")
    {
            $db = 'bwolff_eqoff';
            $EC_kWh='Energy_Consumption';
            
            if($_REQUEST['MODBUSDEVICECLASS']==27 && $_REQUEST['SERIALNUMBER']=="001EC6001433") 
            {
                    $EC_kWh = 'Shore_Power_(kWh)';
            }
            $Peak_kW="Peak_kW";
            $Off_Peak_kW="Off_Peak_kW";

           //get data for only one year to reduce amount of data      
            $OneHourAgo = date('Y-m-d H:i:s', strtotime('-1 Hour', strtotime($wrongtime)));
            $PlusHour = date('Y-m-d H:i:s',strtotime('+1 Hour', strtotime($wrongtime)));
            $sql_update = sprintf("SELECT time, `%s` FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $devicetablename, $OneHourAgo, $PlusHour);
          //$log->logInfo(sprintf("fix KW date %s maxvalue %f \n", $wrongtime , $maxValue) ); 
          // $log->logInfo(sprintf("%s\n", $sql_update) ); 
           
            $RESULT_update = mysql_query($sql_update);
            if (!$RESULT_update) $log->logInfo(sprintf("%s:SQL Result failure \n", $LOOPNAME) ); 

            $rows_remaining = mysql_num_rows($RESULT_update);
               if (!$rows_remaining)  $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME) ); 

            while($row=mysql_fetch_array($RESULT_update))
            {
                    // Put the device table contents into an array 
                    $time[] = $row['time'];		// UTC Time
                    $time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));		// Converting the time into device timezone)
                    $kWh[] = $row["$EC_kWh"];		// Running Total Energy Consumption kWh
                // $log->logInfo(sprintf("time %s %s %f\n", $row['time'], date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC)),$row["$EC_kWh"]) ); 

            }
            $countd=count($time_d);		// count timezone array.
		
            $startProcess = FALSE;
            $i=-1;
            while($i<=$countd-2)
            {	
                $i++;

                if($i==0){continue;}	// skip the first value 
                if (($time[$i] != $wrongtime) && (!$startProcess)) 
               {    
                   continue;
                }    
                else
               {    
                   $startProcess = TRUE;
               }  

                if($kWh[$i]!==0 && $kWh[$i-1]>$kWh[$i])		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
                {	
                        $Power=$kWh[$i-1] - $kWh[$i];		// Peak kWh
                        $Power_time=$time[$i-1];			// Peak kWh time

                        $interval=strtotime($time[$i-1]) - strtotime($time[$i]);		// get the data logging time interval
                     //   $log->logInfo(sprintf("demand interval %d %s - %s\n",$interval, $time[$i-1], $time[$i]) ); 

                        $demand_15 = '';		// set the demand to empty unless the data logging time period is within the correct constraints.
                        if($interval>150 && $interval<210)
                        {	if($i>4)
                                {
                                    if($kWh[$i]>0 && $kWh[$i-5]>$kWh[$i])
                                    {
                                            $demand_15=($kWh[$i-5] - $kWh[$i])*4;
                                            $demand_15_time=$time[$i-5];
                                            $timestamp_15 = strtotime($time_d[$i-5]);
                                    }
                                }
                        }
                        else if($interval>240 && $interval<360)
                        {	if($i>2)
                                {
                                    if($kWh[$i]>0 && $kWh[$i-3]>$kWh[$i])
                                    {
                                            $demand_15=($kWh[$i-3] - $kWh[$i])*4;
                                            $demand_15_time=$time[$i-3];
                                            $timestamp_15 = strtotime($time_d[$i-3]);
                                    // $log->logInfo(sprintf("demand15 %f time %s\n", $demand_15, $demand_15_time) ); 

                                    }
                                }
                        }
                        else if($interval>780 && $interval<1020)
                        {	
                                if($i>1)
                                {
                                        if($kWh[$i]>0 && $kWh[$i-1]>$kWh[$i])
                                        {
                                                $demand_15=($kWh[$i-1] - $kWh[$i])*4;
                                                $demand_15_time=$time[$i-1];
                                                 $timestamp_15 = strtotime($time_d[$i-1]);
                                         }
                                 }
                         }

                        // only update the demand if the value has been assigned. 

                        if(!empty($demand_15))
                         {
                            
                            date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                            $month = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                            $hour = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                            $week = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                            date_default_timezone_set("UTC"); //set timezone back to UTC
                           //  $log->logInfo(sprintf("D15 time %s %d %s m %d h %d w %d\n",$demand_15_time,$timestamp_15,date('Y-m-d H:i:s', $timestamp_15), $month, $hour, $week) );           

                            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
                            {
                             $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
                            }
                            else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
                            {
                             $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
                            }
                            else
                            {
                                $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
                            }

                            $sql_kW_check="SELECT time, $Peak_kW, $Off_Peak_kW FROM $devicetablename WHERE time='$demand_15_time'";
                            $value_kW_check=mysql_query($sql_kW_check);
                            if (!$value_kW_check)  $log->logInfo(sprintf("%s:SQL value_KW_check failure \n", $LOOPNAME) ); 

                            $value=mysql_fetch_array($value_kW_check);
                            if (!$value)   $log->logInfo(sprintf("%s:SQL value2 failure \n", $LOOPNAME) ); 

                            $O_kW_value=$value["$Peak_kW"];
                            $OP_kW_value=$value["$Off_Peak_kW"];

                            //$log->logInfo(sprintf("%s %s kW peak %f %f\n", $Power_time, $demand_15_time,$O_kW_value, $OP_kW_value) ); 

                            if ($maxValue > 0)
                            {        
                                if (($O_kW_value > $maxValue ) || ($OP_kW_value > $maxValue))
                                {
                                //    $log->logInfo(sprintf("demand_15 %s  %s %s", $demand_15_time,$demand_15, $sql_peak_kW) ); 
                                // turn sql query on or off to execute the changes
                               //$kW_peak_query=mysql_query($sql_peak_kW);	
                             // if(!$kW_peak_query)  $log->logInfo(sprintf("%s:SQL kw_peak_query failure \n", $LOOPNAME) ); 
                               //  $log->logInfo(sprintf("demand_15  %s", $sql_peak_kW) ); 
                                  if ($O_kW_value > $maxValue)  
                                   $log->logInfo(sprintf("Update %s from %f to %f \n",$demand_15_time, $O_kW_value, $demand_15) ); 
                                  else
                                     $log->logInfo(sprintf("Update %s from %f to %f \n",$demand_15_time, $OP_kW_value, $demand_15) ); 
                                }  
                              }
                             else
                             {
                              if (($O_kW_value==0 ) && ($OP_kW_value==0))
                                {
                                //    $log->logInfo(sprintf("demand_15 %s  %s %s", $demand_15_time,$demand_15, $sql_peak_kW) ); 
                                // turn sql query on or off to execute the changes
                               //$kW_peak_query=mysql_query($sql_peak_kW);	
                              //if(!$kW_peak_query)  $log->logInfo(sprintf("%s:SQL kw_peak_query failure \n", $LOOPNAME) ); 
                               //  $log->logInfo(sprintf("demand_15  %s", $sql_peak_kW) ); 
                                   $log->logInfo(sprintf("Update %s to %f \n", $demand_15_time, $demand_15) ); 
                                }     
                             }    
                          }
                        }
                    }
            }
       //$log->logInfo(sprintf("%s: ReCalculate END\n", $LOOPNAME) ); 
    
}/*end function*/

 function fixkWHZeros($aquisuitetable, $devicetablename,$utility, $wrongtime)
 {
     global $log;
     global $LOOPNAME;
    
    $timezone = timezone($aquisuitetable, $log, $LOOPNAME);		// check for current time zone
    //$log->logInfo(sprintf("%s timezone %s \n", $aquisuitetable,$timezone) ); //debug
			
    $sql_rates = "SELECT * FROM `$utility`";
    $rate_q = mysql_query($sql_rates);
    if(!$rate_q)    MySqlFailure("unable to process mysql request");

    if ($utility=="SCE&G_Rates")
    {
        $cost=mysql_fetch_array($rate_q);
        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
        $Summer_Start = idate(m,strtotime($cost[10]));
        $Summer_End = idate(m,strtotime($cost[11]));
        $Peak_Time_Summer_Start = idate(H,strtotime($cost[12]));
        $Peak_Time_Summer_Stop = idate(H,strtotime($cost[13]));
        $Peak_Time_Non_Summer_Start = idate(H,strtotime($cost[14]));
        $Peak_Time_Non_Summer_Stop = idate(H,strtotime($cost[15]));
        $Peak_Time_Non_Summer_Start2 = idate(H,strtotime($cost[16]));
        $Peak_Time_Non_Summer_Stop2 = idate(H,strtotime($cost[17]));
          //  $log->logInfo(sprintf("%s:nonsummer startH %d stopH %d 2start %d 2stop %d \n", $LOOPNAME, $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
           //          $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ) ); 
            date_default_timezone_set("UTC"); //go back to UTC time

            // Device table utility columns and database.
            $db = 'bwolff_eqoff';
            $EC_kWh='Energy_Consumption';
            
            if($_REQUEST['MODBUSDEVICECLASS']==27 && $_REQUEST['SERIALNUMBER']=="001EC6001433") 
            {
                    $EC_kWh = 'Shore_Power_(kWh)';
            }
            $Peak_kW="Peak_kW";
            $Peak_kWh="Peak_kWh";
            $Off_Peak_kW="Off_Peak_kW";
            $Off_Peak_kWh="Off_Peak_kWh";

           //get data for only one year to reduce amount of data      
            $OneHourAgo = date('Y-m-d H:i:s', strtotime('-1 Hour', strtotime($wrongtime)));
            $PlusHour = date('Y-m-d H:i:s',strtotime('+1 Hour', strtotime($wrongtime)));
            $sql_update = sprintf("SELECT time, `%s` FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $devicetablename, $OneHourAgo, $PlusHour);
          // $log->logInfo(sprintf("fix date %s\n", $wrongtime) ); 
           //$log->logInfo(sprintf("%s\n", $sql_update) ); 
           
            $RESULT_update = mysql_query($sql_update);
            if (!$RESULT_update) $log->logInfo(sprintf("%s:SQL Result failure \n", $LOOPNAME) ); 

            $rows_remaining = mysql_num_rows($RESULT_update);
               if (!$rows_remaining) $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME) ); 

            while($row=mysql_fetch_array($RESULT_update))
            {
                    // Put the device table contents into an array 
                    $time[] = $row['time'];		// UTC Time
                    $time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));		// Converting the time into device timezone)
                    $kWh[] = $row["$EC_kWh"];		// Running Total Energy Consumption kWh
            }
            $countd=count($time_d);		// count timezone array.
            $i=-1;
            while($i<=$countd-2)
            {	
                $i++;
                if($i==0){continue;}	// skip the first value 
                
                if ($time[$i-1] != $wrongtime)
                    continue;

                if($kWh[$i]!==0 && $kWh[$i-1]>$kWh[$i])		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
                {	
                        $Power=$kWh[$i-1] - $kWh[$i];		// Peak kWh
                        $Power_time=$time[$i-1];			// Peak kWh time

                        $sql_check="SELECT time, $Peak_kWh, $Off_Peak_kWh FROM $devicetablename WHERE time='$Power_time'";
                        $value_check=mysql_query($sql_check);
                         if (!$value_check)     $log->logInfo(sprintf("%s:SQL value_check failure \n", $LOOPNAME) ); 
                        $value=mysql_fetch_array($value_check);
                         if (!$value)  $log->logInfo(sprintf("%s:SQL value failure \n", $LOOPNAME) ); 
                        $O_kWh_value=$value["$Peak_kWh"];
                        $OP_kWh_value=$value["$Off_Peak_kWh"];
                        
                        if ($O_kWh_value==0 && $OP_kWh_value==0)
                        {
                            // $log->logInfo(sprintf("%s kWh peak %f off %f \n", $Power_time, $O_kWh_value, $OP_kWh_value) ); 

                            date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                            $timestamp = strtotime($time_d[$i-1].' UTC');	// timestamp the time in the device timezone format
                            // set up the month hour and week for the correct timezone
                            $month = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp)));
                            $hour = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp)));
                            $week = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp)));
                            date_default_timezone_set("UTC"); //set timezone back to UTC

                           //$log->logInfo(sprintf("timeUTC %s %d %s Local m %d h %d w %d\n",$time_d[$i - 1],$timestamp, date('Y-m-d H:i:s', $timestamp), $month, $hour, $week) );                   

                            // determine whether the kWh value has occured during Peak or Off Peak time.
                            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
                            {
                                $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                            else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
                            {
                                 $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                            else
                            {
                                $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                                    
                            // turn sql query on or off to execute the changes
                           //$peak_query=mysql_query($sql_peak_kWh);
                         // $log->logInfo(sprintf("%s %s\n",$Power_time, $sql_peak_kWh) ); 
                          //if(!$peak_query)   $log->logInfo(sprintf("%s:SQL peak_query failure \n", $LOOPNAME) ); 
                          $log->logInfo(sprintf("Update %s to %f \n", $Power_time, $Power) ); 
                         // break;
                        }
                    }
            }    
	}
      // $log->logInfo(sprintf("%s: kwH END\n", $LOOPNAME) ); 
    
}/*end function*/

function fixkWHLarge($aquisuitetable, $devicetablename,$utility, $wrongtime, $maxKW)
 {
     global $log;
     global $LOOPNAME;
    
    $timezone = timezone($aquisuitetable, $log, $LOOPNAME);		// check for current time zone
			
    $sql_rates = "SELECT * FROM `$utility`";
    $rate_q = mysql_query($sql_rates);
    if(!$rate_q)    MySqlFailure("unable to process mysql request");

    if ($utility=="SCE&G_Rates")
    {
        $cost=mysql_fetch_array($rate_q);
        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
        $Summer_Start = idate(m,strtotime($cost[10]));
        $Summer_End = idate(m,strtotime($cost[11]));
        $Peak_Time_Summer_Start = idate(H,strtotime($cost[12]));
        $Peak_Time_Summer_Stop = idate(H,strtotime($cost[13]));
        $Peak_Time_Non_Summer_Start = idate(H,strtotime($cost[14]));
        $Peak_Time_Non_Summer_Stop = idate(H,strtotime($cost[15]));
        $Peak_Time_Non_Summer_Start2 = idate(H,strtotime($cost[16]));
        $Peak_Time_Non_Summer_Stop2 = idate(H,strtotime($cost[17]));
          //  $log->logInfo(sprintf("%s:nonsummer startH %d stopH %d 2start %d 2stop %d \n", $LOOPNAME, $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
           //          $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ) ); 
            date_default_timezone_set("UTC"); //go back to UTC time

            // Device table utility columns and database.
            $db = 'bwolff_eqoff';
            $EC_kWh='Energy_Consumption';
            
            if($_REQUEST['MODBUSDEVICECLASS']==27 && $_REQUEST['SERIALNUMBER']=="001EC6001433") 
            {
                    $EC_kWh = 'Shore_Power_(kWh)';
            }
            $Peak_kWh="Peak_kWh";
            $Off_Peak_kWh="Off_Peak_kWh";

           //get data for only one year to reduce amount of data      
            $OneHourAgo = date('Y-m-d H:i:s', strtotime('-1 Hour', strtotime($wrongtime)));
            $PlusHour = date('Y-m-d H:i:s',strtotime('+1 Hour', strtotime($wrongtime)));
            $sql_update = sprintf("SELECT time, `%s` FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $devicetablename, $OneHourAgo, $PlusHour);
           $log->logInfo(sprintf("fix date %s\n", $wrongtime) ); 
           //$log->logInfo(sprintf("%s\n", $sql_update) ); 
           
            $RESULT_update = mysql_query($sql_update);
            if (!$RESULT_update) $log->logInfo(sprintf("%s:SQL Result failure \n", $LOOPNAME) ); 

            $rows_remaining = mysql_num_rows($RESULT_update);
               if (!$rows_remaining) $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME) ); 

            while($row=mysql_fetch_array($RESULT_update))
            {
                    // Put the device table contents into an array 
                    $time[] = $row['time'];		// UTC Time
                    $time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));		// Converting the time into device timezone)
                    $kWh[] = $row["$EC_kWh"];		// Running Total Energy Consumption kWh
            }
            $countd=count($time_d);		// count timezone array.
            $i=-1;
            while($i<=$countd-2)
            {	
                $i++;
                if($i==0){continue;}	// skip the first value 
                
                if ($time[$i-1] != $wrongtime)
                    continue;

                if($kWh[$i]!==0 && $kWh[$i-1]>$kWh[$i])		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
                {	
                        $Power=$kWh[$i-1] - $kWh[$i];		// Peak kWh
                        $Power_time=$time[$i-1];			// Peak kWh time

                        $sql_check="SELECT time, $Peak_kWh, $Off_Peak_kWh FROM $devicetablename WHERE time='$Power_time'";
                        $value_check=mysql_query($sql_check);
                         if (!$value_check)     $log->logInfo(sprintf("%s:SQL value_check failure \n", $LOOPNAME) ); 
                        $value=mysql_fetch_array($value_check);
                         if (!$value)  $log->logInfo(sprintf("%s:SQL value failure \n", $LOOPNAME) ); 
                        $O_kWh_value=$value["$Peak_kWh"];
                        $OP_kWh_value=$value["$Off_Peak_kWh"];
                        
                        
                        if (($O_kWh_value > $maxKW) ||  ($OP_kWh_value > $maxKW))
                        {
                             $log->logInfo(sprintf("%s kWh peak %f off %f \n", $Power_time, $O_kWh_value, $OP_kWh_value) ); 

                            date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                            $timestamp = strtotime($time_d[$i-1].' UTC');	// timestamp the time in the device timezone format
                            // set up the month hour and week for the correct timezone
                            $month = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp)));
                            $hour = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp)));
                            $week = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp)));
                            date_default_timezone_set("UTC"); //set timezone back to UTC

                           //$log->logInfo(sprintf("timeUTC %s %d %s Local m %d h %d w %d\n",$time_d[$i - 1],$timestamp, date('Y-m-d H:i:s', $timestamp), $month, $hour, $week) );                   

                            // determine whether the kWh value has occured during Peak or Off Peak time.
                            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
                            {
                                $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                            else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
                            {
                                 $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                            else
                            {
                                $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                            }
                            // turn sql query on or off to execute the changes

                           //$peak_query=mysql_query($sql_peak_kWh);
                          //$log->logInfo(sprintf("%s %s\n",$Power_time, $sql_peak_kWh) ); 
                         //  if(!$peak_query)   $log->logInfo(sprintf("%s:SQL peak_query failure \n", $LOOPNAME) ); 
                          $log->logInfo(sprintf("Update %s to %f \n", $Power_time, $Power) ); 
                         // break;
                        }
                    }
            }    
	}
      // $log->logInfo(sprintf("%s: kwH END\n", $LOOPNAME) ); 
    
}/*end function*/
 

function debug_log()
{
    require '../erms/includes/KLogger.php';
    $log = new KLogger ( "fixlog.txt" , KLogger::DEBUG );   // klogger debug everything
   return $log;
}

/* ================================================================================================================== */
/*
    BEGIN MAIN SCRIPT HERE !
*/

include "../conn/mysql_pconnect-all.php"; // mySQL database connector.

   //$aquisuitetable = "Cape_Wrath_001EC6001FFB";
   //$devicetablename = "Cape_Wrath_001EC6001FFB__device001_class2";  
  // $LOOPNAME = "Cape_Wrath";  //set loopname
   //$aquisuitetable = "Cape_Decision_001EC6000AD8";
   //$devicetablename = "Cape_Decision_001EC6000AD8__device001_class2";  
   //$LOOPNAME = "Cape_Decision";  //set loopname
    $aquisuitetable = " Cape_Diamond_001EC6000AB7";
   $devicetablename = " Cape_Diamond_001EC6000AB7__device001_class2";  
   $LOOPNAME = " Cape_Diamond";  //set loopname
   
    $utility = "SCE&G_Rates";
    
    
    date_default_timezone_set('UTC'); /** set the timezone to UTC for all date and time functions **/
   
    $log = debug_log($LOOPNAME);
     
    $log->logInfo( sprintf("%s aquisuitetable %s devicetablename %s\n", $LOOPNAME, $aquisuitetable, $devicetablename));

    $db = 'bwolff_eqoff';
    $timezone = timezone($aquisuitetable, $log, $LOOPNAME);		// check for current time zone

    $log->logInfo(sprintf("%s: %s timezone %s \n", $LOOPNAME, $aquisuitetable,$timezone) ); 

    $sql_rates = "SELECT * FROM `$utility`";
    if(!$sql_rates)
    {
        $log->logInfo(sprintf("%s unable to process select sql_rates %s", $LOOPNAME, $sql_rates)); 
    }
    $rate_q = mysql_query($sql_rates);
    if(!$rate_q)
    {
        $log->logInfo(sprintf("%s unable to process mysql request", $LOOPNAME) ); 
    }

    $cost=mysql_fetch_array($rate_q);
    if(!$cost)
    {
        $log->logInfo(sprintf("%s unable to process mysql fetch cost", $LOOPNAME) ); 
    }
    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $utility) ); //debug

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

    $Summer_Start = idate(m,strtotime($cost[10]));
    $Summer_End = idate(m,strtotime($cost[11]));
    $Peak_Time_Summer_Start = idate(H,strtotime($cost[12]));
    $Peak_Time_Summer_Stop = idate(H,strtotime($cost[13]));
    $Peak_Time_Non_Summer_Start = idate(H,strtotime($cost[14]));
    $Peak_Time_Non_Summer_Stop = idate(H,strtotime($cost[15]));
    $Peak_Time_Non_Summer_Start2 = idate(H,strtotime($cost[16]));
    $Peak_Time_Non_Summer_Stop2 = idate(H,strtotime($cost[17]));

    $log->logInfo(sprintf("%s:sumMonth %d sumendMonth %d summer startH %d stopH %d  nonsumstart %d nonsumend %d start2 %d stop2 %d \n",
         LOOPNAME, $Summer_Start, $Summer_End, $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop,
         $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
         $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ) ); 

    date_default_timezone_set("UTC"); //go back to UTC time

//get data for only one year to reduce amount of data 
$rows_remaining = 0;
//  $feb = date('Y-m-d H:i:s',strtotime('2014-03-10 00:00:00'));
$feb = date('Y-m-d H:i:s',strtotime('2014-01-01 00:00:00'));
//$now = date('Y-m-d H:i:s',strtotime('2014-03-10 23:55:00'));
$now = date('Y-m-d H:i:s');           

$sql_update = sprintf("SELECT time, Energy_Consumption, Peak_kW, Peak_kWh, Off_Peak_kW, Off_Peak_kWh FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time", $devicetablename, $feb, $now);
$log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update) ); 

$RESULT_update = mysql_query($sql_update);
if (!$RESULT_update)
   $log->logInfo(sprintf("%s:SQL Result failure \n", $LOOPNAME) ); 

$rows_remaining = mysql_num_rows($RESULT_update);
if (!$rows_remaining)
   $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME) ); 

$log->logInfo(sprintf("%s:num rows %d\n", $LOOPNAME,  $rows_remaining) ); 

while($row=mysql_fetch_array($RESULT_update))
{
  $timeUTC[] = $row['time'];		// UTC Time
  $energy[] = $row['Energy_Consumption'];
  $time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));		// Converting the time into device timezone
  $peakKW[] = $row['Peak_kW'];	
  $peakKWh[] = $row['Peak_kWh'];
  $offpeakKW[] = $row['Off_Peak_kW'];	
  $offpeakKWh[] = $row['Off_Peak_kWh'];
 }
$max_rows = $rows_remaining - 1; 

$updateDone = false;
$zeros = 0;
$peakfix = 0;
$zerosKW = 0;
$zeroslargeKW = 0;
$toobig = 0;
$recalcnum = 0;
$i=0;
while($i <= $max_rows)
{	
   date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
    $timestamp = strtotime($time_d[$i].' UTC');	// timestamp the time in the device timezone format
     // set up the month hour and week for the correct timezone
   $month = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp)));
   $hour = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp)));
   $week = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp)));
   date_default_timezone_set("UTC"); //set timezone back to UTC

    
   $reportit  = TRUE;
   if ($reportit)
   {
      reportdata($devicetablename,$timeUTC[$i]);
      break;
   }
    
   
  
    $fixZeroKW  = FALSE;
   if ($fixZeroKW)
   {
       if (($peakKW[$i] == 0) && ($offpeakKW[$i] == 0))
       {
         //   $log->logInfo(sprintf("%s peak % off %f %f\n",$timeUTC[$i], $peakKW[$i], $offpeakKW[$i], $energy[$i] )); 
            $zerosKW++;
           if (($peakKW[$i] == 0) && ($offpeakKW[$i] == 0))
              fixkWZeros($aquisuitetable, $devicetablename, $utility, $timeUTC[$i], 0);
       }
   }
     
   $fixLargeKW  = FALSE;
   if ($fixLargeKW)
   {
       if ($i > 1)
       {   
          if (($peakKW[$i] > 0) && ($peakKW[$i-1] > 0))
           {    
           $maxPeak_kw = $peakKW[$i-1] * 1.5;
            if ($peakKW[$i] > $maxPeak_kw)
            {
              $zeroslargeKW++;
               fixkWZeros($aquisuitetable, $devicetablename, $utility, $timeUTC[$i], $maxPeak_kw);
            }
           }
         elseif (($offpeakKW[$i] > 0) && ($offpeakKW[$i-1] > 0))
           {    
           $maxOffPeak_kw = $offpeakKW[$i-1] * 1.5;
            if ($offpeakKW[$i] > $maxOffPeak_kw)
            {
                $zeroslargeKW++;
               fixkWZeros($aquisuitetable, $devicetablename, $utility, $timeUTC[$i], $maxOffPeak_kw);
           }
         } 
        
       }
   }
   
        
   $fixlargeKWh  = FALSE;
   if ($fixlargeKWh)
   {
       if ($i > 1)
       {    
           if (($peakKWh[$i] > 0) && ($peakKWh[$i-1] > 0))
           {    
           $maxPeak_kwH = $peakKWh[$i-1] *1.5;
            if ($peakKWh[$i] > $maxPeak_kwH)
            {
                $toobig++;
               fixkWHLarge($aquisuitetable, $devicetablename, $utility, $timeUTC[$i], $maxPeak_kwH);
            }
           }
           elseif (($offpeakKWh[$i] > 0) && ($offpeakKWh[$i-1] > 0))
           {    
           $maxOffPeak_kwH = $offpeakKWh[$i-1] *1.5;
            if ($offpeakKWh[$i] > $maxOffPeak_kwH)
            {
                $toobig++;
               fixkWHLarge($aquisuitetable, $devicetablename, $utility, $timeUTC[$i], $maxOffPeak_kwH);
           }
         }
       }
   }
   
   
   $fixZeroKWh  = FALSE;
   if ($fixZeroKWh)
   {
        $zeros++;
        $recalcnum++;
        //$log->logInfo(sprintf("Fix Zero kWh")); 

        if (($peakKWh[$i] == 0) && ($offpeakKWh[$i] == 0))
        {
           // $log->logInfo(sprintf("%s peak %f %f off %f %f %f\n",$timeUTC[$i], $peakKW[$i], $peakKWh[$i], $offpeakKW[$i], $offpeakKWh[$i], $energy[$i] )); 
            fixkWHZeros($aquisuitetable, $devicetablename, $utility, $timeUTC[$i]);
        }

       // if ($recalcnum >= 50)
       // {   
       //    $updateDone = true;
       //     break;
       // }   
   }
   
   $fixPeakandOffPeak = FALSE;
   if ($fixPeakandOffPeak)
   {    
        if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
        {
           if ((($peakKW[$i] == 0) || ($peakKWh[$i] == 0)) && (($offpeakKW[$i] != 0) || ($offpeakKWh[$i] != 0)))
              { 
                   // $log->logInfo(sprintf("%s:summer date %s %d %s m %d h %d w %d\n", $LOOPNAME,$time_d[$i],$timestamp, date('Y-m-d H:i:s', $timestamp), $month, $hour, $week) );   
                  if (($peakKWh[$i] == 0) && ($offpeakKWh[$i] != 0)) 
                  {
                     $log->logInfo(sprintf("SUMMER %s update Peak kwH %f  off KwH %f \n", $timeUTC[$i], $peakKWh[$i], $offpeakKWh[$i]));                   
                     $sql="UPDATE `$db`.`$devicetablename` SET Peak_kWh='$offpeakKWh[$i]', Off_Peak_kWh=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                     // $log->logInfo(sprintf("%s:%s\n", $LOOPNAME, $sql) );  
                      // $my_query=mysql_query($sql);	
                      //  if(!$my_query)
                      //   {
                      //     $log->logInfo(sprintf("%s:SQL peak_query failure 1 \n", $LOOPNAME) ); 
                      //   }
                         $peakfix++;
                  }
                  if (($peakKW[$i] == 0) && ($offpeakKW[$i] != 0))
                  {
                   $log->logInfo(sprintf("SUMMER %s update Peak kW %f off kW %f \n",$timeUTC[$i], $peakKW[$i], $offpeakKW[$i]));                   
                    $sql="UPDATE `$db`.`$devicetablename` SET Peak_kW='$offpeakKW[$i]', Off_Peak_kW=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                   // $log->logInfo(sprintf("%s\n", $sql) );   
                  //   $my_query=mysql_query($sql);	
                   //    if(!$my_query)
                   //    {
                   //     $log->logInfo(sprintf("%s:SQL peak_query failure 2 \n", $LOOPNAME) ); 
                   //    }
                      $peakfix++;
                  }
              }
        }
        else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
        {
              if ((($peakKW[$i] == 0) || ($peakKWh[$i] == 0)) && (($offpeakKW[$i] != 0) || ($offpeakKWh[$i] != 0)))
              {
                  if (($peakKWh[$i] == 0) && ($offpeakKWh[$i] != 0)) 
                  {
                     $log->logInfo(sprintf("PeakNonSum %s update Peak kwH %f  off KwH %f \n", $timeUTC[$i], $peakKWh[$i], $offpeakKWh[$i]));                   
                     $sql="UPDATE `$db`.`$devicetablename` SET Peak_kWh='$offpeakKWh[$i]', Off_Peak_kWh=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                      //$log->logInfo(sprintf("%s\n", $sql) );  
                      //$my_query=mysql_query($sql);	
                      //  if(!$my_query)
                       //  {
                       //    $log->logInfo(sprintf("%s:SQL peak_query failure 12 \n", $LOOPNAME) ); 
                        // }
                          $peakfix++;
                  }  
                 if (($peakKW[$i] == 0) && ($offpeakKW[$i] != 0))
                  {
                   $log->logInfo(sprintf("PeakNonSum %s update Peak kW %f off kW %f \n",$timeUTC[$i], $peakKW[$i], $offpeakKW[$i]));                   
                    $sql="UPDATE `$db`.`$devicetablename` SET Peak_kW='$offpeakKW[$i]', Off_Peak_kW=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                    //$log->logInfo(sprintf("%s\n", $sql) );   
                    // $my_query=mysql_query($sql);	
                   // if(!$my_query)
                   // {
                   //   $log->logInfo(sprintf("%s:SQL peak_query failure 2 \n", $LOOPNAME) ); 
                   // }
                  $peakfix++;
                  }
              }
        }
        else
        {
            if ((($offpeakKW[$i] == 0) || ($offpeakKWh[$i] == 0)) && (($peakKW[$i] != 0) || ($peakKWh[$i] != 0)))
                  {   
                     // $log->logInfo(sprintf("%s:date %s %d %s m %d h %d w %d\n", $LOOPNAME,$time_d[$i],$timestamp, date('Y-m-d H:i:s', $timestamp), $month, $hour, $week) ); 
                      if (($offpeakKWh[$i] == 0) && ($peakKWh[$i] != 0)) 
                      {    
                          $log->logInfo(sprintf("OFFPEAK %s update OFF kWh %f  peak kWh %f\n", $timeUTC[$i], $offpeakKWh[$i], $peakKWh[$i] ));                   
                          $sql="UPDATE `$db`.`$devicetablename` SET Off_Peak_kWh='$peakKWh[$i]', Peak_kWh=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                          //$log->logInfo(sprintf("%s\n",  $sql) ); 
                     //     $my_query=mysql_query($sql);	
                      //      if(!$my_query)
                      //      {
                      //        $log->logInfo(sprintf("%s:SQL peak_query failure 3 \n", $LOOPNAME) ); 
                      //      }
                          $peakfix++;
                      }
                      if (($offpeakKW[$i] == 0) && ($peakKW[$i] != 0))
                      {    
                          $log->logInfo(sprintf("OFFPEAK %s update OFF kW %f  peak kW %f \n", $timeUTC[$i], $offpeakKW[$i], $peakKW[$i] ));                   
                          $sql="UPDATE `$db`.`$devicetablename` SET Off_Peak_kW='$peakKW[$i]', Peak_kW=0 WHERE `$devicetablename`.`time`='$timeUTC[$i]';";
                         // $log->logInfo(sprintf("%s\n" $sql) );  
                          // $my_query=mysql_query($sql);	
                         //   if(!$my_query)
                          //  {
                          //    $log->logInfo(sprintf("%s:SQL peak_query failure 4 \n", $LOOPNAME) ); 
                          //  }
                            $peakfix++;
                      }
                  }
        }         
    }//fix peak off 

 $i++;
}//while

$log->logInfo(sprintf("peakfix %d zeros %d  too big %d zeroKW %d too bigKW %d\n",$peakfix,$zeros, $toobig, $zerosKW , $zeroslargeKW)); 
    
   
?>
