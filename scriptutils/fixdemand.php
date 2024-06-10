<?php
/*
 *  FIle: fixdemand.php
 *  Author: Carole Snow
 * 
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/

/** define constants   **/
const ONE_MIN = 60;
const FIVE_MIN = 300;
const FIFTEEN_MIN = 900;
const ONE_HOUR = 3600;
const TEST_ONLY = 1;
const CORRECT = 2;
const MAX_DEMAND = 1000; 


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
/*	this function checks the Utility table the database to see if the aquisuitetablename has a utility associated with it.
*/
function utility_check($ship)
{
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

/*----------------------------------------------------------------------------------------------------*/
/*	this function returns the module's log period from the acquisuite table
*/
function  getLogInterval($aquisuitetable,$log,$LOOPNAME)
{
    $log_interval = 5; // set default logperiod in minutes
    $sql = "SELECT logperiod FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetable'";	// get log period from Aquisuite_List
    $result = mysql_query($sql);

    if(!$result)
    {
       MySqlFailure("GETLOGINTERVAL failed");
    }    
     $row = mysql_fetch_row($result);
     if (!$row)
     {
         MySqlFailure("GETLOGINTERVAL failed");
     }    
     //$log->logInfo(sprintf("%s:Log interval %d\\n", $LOOPNAME, $log_interval)); 
    $log_interval = $row[0] * 60; //log interval in seconds
    echo sprintf("Log interval %d\n", $log_interval);
    return $log_interval;
}

/*----------------------------------------------------------------------------------------------------*/
/*	the timezone function searches the Aquisuite_List for the aquisuite timezone and then matches it
	in the timezone table with a PHP recognized timezone. 
*/

function timezone($ship, $log, $LOOPNAME)
{
    global $log, $LOOPNAME;
    
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";		// get the aquisuite timezone in the Aquisuite_List
	$result = mysql_query($sql);
	if(!$result)
	{
            	//$log->logInfo(sprintf("%s:timezone mysql select failed\n", $LOOPNAME)); 
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {
            //$log->logInfo(sprintf("%s:timezone rows failed\n", $LOOPNAME)); 
        }

	$timezoneaquisuite = $row[0];

	$sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";		// get the PHP recognized timezone value
	$result = mysql_query($sql);
        if(!$result)
	{
             	$log->logInfo(sprintf("%s:timezone mysql select result failed\n", $LOOPNAME)); 
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {   
            //$log->logInfo(sprintf("%s:timezone rows2 failed\n", $LOOPNAME)); 
        }    
	$timezone = $row[0];

	return $timezone;
}

// This function check if a week is far enough to go back for updating kw and kwh values...otherwise go back 6 months
function lastUpdatedEntry($LOOPNAME, $log,$devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW)
{
     $someTimeAgo  = date('Y-m-d 00:00:00',strtotime('-1 week')); 
     $sql_check = sprintf("SELECT time,%s,%s,%s,%s FROM %s WHERE time LIKE '%s'", $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo );
     echo $sql_check."\n";
     $value_check=mysql_query($sql_check);
     if (!$value_check)
        echo sprintf("%s:LastUpdatedEntry SQL Result failure \n", $LOOPNAME); 

     $value=mysql_fetch_array($value_check);
     if (!$value) echo  sprintf("%s:LastUpdatedEntry  SQL value failure \n", $LOOPNAME); 
   
     if ((($value["$Peak_kWh"]> 0) || ($value["$Off_Peak_kWh"]> 0)) && (($value["$Peak_kW"] > 0) || ($value["$Off_Peak_kW"] > 0)))
     {    
         echo "one week time  ago ".$someTimeAgo."\n";
     }   
     else
     {    
         $someTimeAgo = date('Y-m-d H:i:s',strtotime('-6 month')); 
         echo "6 months time  ago ".$someTimeAgo."\n";
         $sql_check = sprintf("SELECT time,%s,%s,%s,%s FROM %s WHERE time LIKE '%s'", $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo );
         echo $sql_check."\n";
         $value_check=mysql_query($sql_check);
         if (!$value_check) //added 10 month ago for local database not having enough data.
         {   
            $someTimeAgo = date('Y-m-d H:i:s',strtotime('-10 month')); 
            echo "10 months time  ago ".$someTimeAgo."\n";
         }           

         $value=mysql_fetch_array($value_check);
         if (!$value) 
         {
             $someTimeAgo = date('Y-m-d H:i:s',strtotime('-10 month'));
             echo "10 months time  ago ".$someTimeAgo."\n";
         }
     }   
   return $someTimeAgo;  
}

/* Return kw when there is a gap in log time.  Use average from module or calculate based on one data point*/
function fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_time, $log_interval,$power, $pulse_meter, $pulse_demand_str)
{
    $sql_check="SELECT * FROM $devicetablename WHERE time='$demand_time'";
    //echo sprintf("%s:fixtimegap sql %s", $LOOPNAME, $sql_check); 

    if ($value_check = mysql_query($sql_check))
    {    
        if ($value = mysql_fetch_array($value_check))
        {
            if (!$pulse_meter)
            {	    
                $retvalue = $value["Average_Demand"];
                //echo sprintf("%s:fixtimegap not pulse value %f", $LOOPNAME, $retvalue); 
            }    
            else
            {	    
                $retvalue = $value["$pulse_demand_str"];
                //echo sprintf("%s:fixtimegap pulse value[%f]", $LOOPNAME,$retvalue); 
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
    echo sprintf("%s:Avg not avail kWh power=%f int %d calc kW %f\n",  $LOOPNAME, $power, $log_interval,$calc_kw);

    return($calc_kw);     //need to add check for max possible kWh      
}


function setShip($ship)
{
    global $aquisuitetable,$devicetablename,$utility, $LOOPNAME;
   
    if ($ship == "Cape_Wrath")
    {    
        $aquisuitetable = "Cape_Wrath_001EC6001FFB";
        $devicetablename = "Cape_Wrath_001EC6001FFB__device001_class2";  
        $LOOPNAME = "Cape_Wrath";  //set loopname 
        $utility = "SCE&G_Rates";
    }
    else if ($ship == "Cape_Washington")
    {    
        $aquisuitetable = "Cape_Washington_001EC6001FFA";
        $devicetablename = "Cape_Washington_001EC6001FFA__device001_class2";  
        $LOOPNAME = "Cape_Washington";  //set loopname 
        $utility = "SCE&G_Rates";
    }
    else if ($ship == "Cape_Decision")
    {    
        $aquisuitetable = "Cape_Decision_001EC6000AD8";
       $devicetablename = "Cape_Decision_001EC6000AD8__device001_class2"; 
       $LOOPNAME = "Cape_Decision";  //set loopname
        $utility = "SCE&G_Rates";
        $str1 =  sprintf("%s  %s  %s %s\n", $LOOPNAME, $aquisuitetable, $devicetablename, $utility);
        echo $str1;
    }    
    else if ($ship == "Cape_Diamond")
    {
        $aquisuitetable = "Cape_Diamond_001EC6000AB7";
       $devicetablename = "Cape_Diamond_001EC6000AB7__device001_class2";  
       $LOOPNAME = " Cape_Diamond";  //set loopname
       $utility = "SCE&G_Rates";
    }
    else if ($ship == "Cape_Domingo")
    {
         $aquisuitetable = "Cape_Domingo_001EC6000ACB";
       $devicetablename = "Cape_Domingo_001EC6000ACB__device001_class2";  
       $LOOPNAME = " Cape_Domingo";  //set loopname
       $utility = "SCE&G_Rates";
    }
    else if ($ship == "Cape_Douglas")
    {
         $aquisuitetable = "Cape_Douglas_001EC6000ABC";
        $devicetablename = "Cape_Douglas_001EC6000ABC__device001_class2";  
        $LOOPNAME = " Cape_Douglas";  //set loopname
        $utility = "SCE&G_Rates";
    }
     else if ($ship == "Cape_Ducato")
    {
         $aquisuitetable = "Cape_Ducato_001EC6000ABD";
       $devicetablename = "Cape_Ducato_001EC6000ABD__device001_class2";  
       $LOOPNAME = " Cape_Ducato";  //set loopname
       $utility = "SCE&G_Rates";
    }
    else if ($ship == "Cape_Edmont")
    {
         $aquisuitetable = "Cape_Edmont_001EC6000AD9";
        $devicetablename = "Cape_Edmont_001EC6000AD9__device001_class2";  
        $LOOPNAME = " Cape_Edmont";  //set loopname
        $utility = "SCE&G_Rates";
    }
   else if ($ship == "Gopher_State")
    {
         $aquisuitetable = "Gopher_State_001EC60008A4";
        $devicetablename = "Gopher_State_001EC60008A4__device001_class2";  
        $LOOPNAME = "Gopher_State";  //set loopname
        $utility = "Virginia_Dominion_Rates";
    }
    else if ($ship == "Cornhusker")
    {
         $aquisuitetable = "Cornhusker_001EC6000A1B";
        $devicetablename = "Cornhusker_001EC6000A1B__device003_class2";  
        $LOOPNAME = "Cornhusker";  //set loopname
        $utility = "Virginia_Dominion_Rates";
    }
   else if ($ship == "Flickertail")
    {
         $aquisuitetable = "Flickertail_Power_001EC600047E";
        $devicetablename = "Flickertail_Power_001EC600047E__device002_class2";  
        $LOOPNAME = "Flickertail";  //set loopname
        $utility = "Virginia_Dominion_Rates";
    }
    else if ($ship == "Cape_Kennedy")
    {
         $aquisuitetable = "Cape_Kennedy_001EC6001433";
        $devicetablename = "Cape_Kennedy_001EC6001433__device250_class27";  
        $LOOPNAME = "Cape_Kennedy";  //set loopname
        $utility = "Virginia_Dominion_Rates";
    }
     else if ($ship == "Cape_Knox")
    {
         $aquisuitetable = "Cape_Knox_001EC6001635";
        $devicetablename = "Cape_Knox_001EC6001635__device250_class27";  
        $LOOPNAME = "Cape_Knox";  //set loopname
        $utility = "Virginia_Dominion_Rates";
    }
    else if ($ship == "Regulus")
    {
         $aquisuitetable = "Regulus_001EC60014F2";
        $devicetablename = "Regulus_001EC60014F2__device001_class2";  
        $LOOPNAME = "Regulus";  //set loopname
        $utility = "SCE&G_Rates";
    }
   else if ($ship == "SS_Altair")
    {
         $aquisuitetable = "SS_Altair_001EC600168B";
        $devicetablename = "SS_Altair_001EC600168B__device001_class2";  
        $LOOPNAME = "SS_Altair";  //set loopname
        $utility = "SCE&G_Rates";
    }
    else if ($ship == "SS_Bellatrix")
    {
         $aquisuitetable = "SS_BELLATRIX_001EC6001595";
        $devicetablename = "SS_BELLATRIX_001EC6001595__device001_class2";  
        $LOOPNAME = "SS_Bellatrix";  //set loopname
        $utility = "SCE&G_Rates";
    }
   else if ($ship == "Denebola")
    {
         $aquisuitetable = "Denebola_001EC6001795";
        $devicetablename = "Denebola_001EC6001795__device001_class2";  
        $LOOPNAME = "Denebola";  //set loopname
        $utility = "SCE&G_Rates";
    }
    else if ($ship == "Antares")
    {
         $aquisuitetable = "Antares_001EC600179D";
        $devicetablename = "Antares_001EC600179D__device001_class2";  
        $LOOPNAME = "Antares";  //set loopname
        $utility = "SCE&G_Rates";
    }
    else
    {
        echo "Invalid Ship\n";
        exit;
    }    
}

function utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log, $someTimeAgo, $NowPlusDay, $test_only)
{
	
// DEVICE CLASS 2 Class 27 is series of inputs that could be defined to various 
// meters. Here a class 27 pulse meter reading kWh which will serve the same function as a class 2.

	$utility = utility_check($aquisuitetable); // check to see if device has an associated utility table
	
	echo sprintf("%s:utility check %s\n", $LOOPNAME, $utility);
	
	if(empty($utility)) 
	{
	     echo sprintf("%s: Utility Unavailable\n",$LOOPNAME); 
             exit;
	}
	
	$db = 'bwolff_eqoff';
	$timezone = timezone($aquisuitetable,$log,$LOOPNAME);		// check for current time zone
       	$pulse_demand_str = '';
        $pulse_meter = FALSE; 
	
	echo sprintf("%s: %s timezone %s \n", $LOOPNAME, $aquisuitetable,$timezone);

	$sql_rates = "SELECT * FROM `$utility`";
        $NowDate = date('Y-m-d H:i:s',strtotime('now')); //get recent rate schedule 
        $sql_rates = sprintf("SELECT * FROM `$utility` WHERE Rate_Date_End >= '%s' AND Rate_Date_Start <= '%s'", $NowDate, $NowDate);
                                                
	if(!$sql_rates) { echo  sprintf("%s unable to process select sql_rates %s", $LOOPNAME, $sql_rates); exit;  }
	
	$rate_q = mysql_query($sql_rates);
	if(!$rate_q) { echo  sprintf("%s unable to process select mysql request %s", $LOOPNAME, $sql_rates); exit;  }
	
	$log_interval = getLogInterval($aquisuitetable,$log,$LOOPNAME);
   
	// SCE&G UTILITY RATE 24		
	if ($utility=="SCE&G_Rates")
	{    
	    $cost=mysql_fetch_array($rate_q);
	    if(!$cost) { echo "unable to process mysql fetch cost" .  $sql_rates . "\n"; exit;  }
	
	    echo  sprintf("%s: %s\n", $LOOPNAME, $utility);
	
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
        echo sprintf("%s: rate start %s rate end %s  MayStart %s MayEnd %s\n", $LOOPNAME, $cost[20], $cost[21] , $MayOct_Start, $MayOct_End); 
	
	    //echo  sprintf("%s:sumMonth %d sumendMonth %d summer startH %d stopH %d  nonsumstart %d nonsumend %d start2 %d stop2 %d \n",
		// $LOOPNAME, $Summer_Start, $Summer_End, $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop,
		// $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
		// $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ));
	
	    date_default_timezone_set("UTC"); //go back to UTC time
	    	
	   $db = 'bwolff_eqoff';
           $EC_kWh='Energy_Consumption';
	   $Peak_kW="Peak_kW";
	   $Peak_kWh="Peak_kWh";
	   $Off_Peak_kW="Off_Peak_kW";
	   $Off_Peak_kWh="Off_Peak_kWh";

           if ($LOOPNAME == "Cape_Knox") 
           {
            $pulse_meter = TRUE; 
            $EC_kWh = 'Shore_Power';
            $rp = 'Real_Power';
            $pulse_demand_str = 'Shore_Power_Demand';
            } 
            else if  ($LOOPNAME == "Cape_Kennedy") 
            {
                $pulse_meter = TRUE; 
                $EC_kWh = 'Shore_Power_(kWh)';
                $rp = 'Real_Power';
                $pulse_demand_str = 'Shore_Power_(kWh)_Demand';
            }

	   //echo  sprintf("%s: %s\n", $LOOPNAME, $EC_kWh));
	
	    $sql_select = sprintf("SELECT time, `%s` ,`%s` ,`%s` , `%s` , `%s`  FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW,$devicetablename, $someTimeAgo, $NowPlusDay);
	
	    echo  sprintf("%s: %s\n", $LOOPNAME, $sql_select);
	
	    $RESULT_select = mysql_query($sql_select);
	    if (!$RESULT_select)
	    {
	        echo  sprintf("%s: ERROR SQL Select %s\n", $LOOPNAME, $sql_select);
		    exit;
	    }
	
	    $rows_remaining = mysql_num_rows($RESULT_select);
	    if (!$rows_remaining) echo sprintf("%s:SQL rows remaining failure \n", $LOOPNAME);
	
	    while($row=mysql_fetch_array($RESULT_select))
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
		
		 $loop_week = $time[1];
		$diff2 =  strtotime($loop_week);
	
            echo sprintf("%s: loop for %d \n", $LOOPNAME, $countd);
	    while($i<=$countd-15)
	    {	
	    $i++;

	    if(($kWh[$i]!==0 )&& ($kWh[$i] > $kWh[$i+1]))		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
	    {	
		    $Power=$kWh[$i] - $kWh[$i+1];		// Peak kWh
		    $Power_time=$time[$i];			// Peak kWh time
		    
		    	    
                    //echo sprintf("%s: loop %d +1[%d] %s +1[%s] \n", $LOOPNAME, $i,($i+1), $time[$i],$time[$i+1]);
	
		    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion of peak times
		    $timestamp = strtotime($time[$i].'UTC');	// timestamp the time in the device timezone format
		    // set up the month hour and week for the correct timezone
		    $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
		    $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
		    $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
		    date_default_timezone_set("UTC"); //set timezone back to UTC
		   
			$diff1 =  strtotime($time[$i]);
			if (abs($diff2 - $diff1 ) > 604800)
			{
			    echo sprintf("week: %s current time %s\n", $time[$i], date("H:i:s") );
		        $loop_week = $time[$i];
		     	$diff2 =  strtotime($loop_week);
		    }
		    
		                                                                                                         
		    // determine whether the kWh value has occured during Peak or Off Peak time.
		    // Set the query for the device utility column update.
		    if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
		    {
			    $timeIsPeak = TRUE;           
			    $sql_str="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power'";
			   //echo   sprintf("p1 %s m %d h %d w %d summerend %d\n", $time[$i],$month, $hour, $week, $Summer_End); 
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
			   //echo   sprintf("p2 %s m %d h %d w %d summerend %d\n", $time[$i],$month, $hour, $week, $Summer_End); 
		    }
		    else
		    {
			    $timeIsPeak = FALSE;     
			    $sql_str="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power'";
			   //echo   sprintf("u3 %s m %d h %d w %d summerend %d\n", $time[$i],$month, $hour, $week, $Summer_End); 
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
		    //echo sprintf("%s:kWh Sql %s \n", $LOOPNAME,  $sql_peak_kWh );

	            /***                                                                       
		    if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0)) 
		    {    
			$peak_query=mysql_query($sql_peak_kWh);	
			// For Aquisuite full debug mode prints out information to tell the user if the kWh utility columns have been updated.
			if(!$peak_query)
			{
			    echo sprintf("%s:Error not updated %s %f %s  \n", $LOOPNAME, $Power_time, $Power, $sql_peak_kWh );
			    echo "$devicetablename $Power_time : $Power not updated"."</br>";
			}
			else
			{
			    echo "$devicetablename $Power_time : $Power updated"."</br>";
			}
		    }  
		   ***/
	
		    $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
		    //echo sprintf("%s: calculatedinterval %d \n", $LOOPNAME, $interval);
	
		    // the Demand kW formula varies based on the data logging time interval.
		    if ($log_interval == 300) // 5 minute log
		    {
			if($i<($countd-3)) //array bounds check
			{
			    $demand_15=($kWh[$i] - $kWh[$i+3])*4;
			    $demand_15_time=$time[$i];
			    if ((strtotime($time[$i]) - strtotime($time[$i+3]) != 900 )  || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) //900 is 15 minutes
			    {
			       $demand_15 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
			       echo sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_15_time, $log_interval, $demand_15,(strtotime($time[$i]) - strtotime($time[$i+3])));
			    }
			}
		    }
		    else  if ($log_interval == 60) // 1 minute log
		    {
			if($i<($countd-15)) //array bounds check
			{
			    $demand_15=($kWh[$i] - $kWh[$i+15])*4;
			    $demand_15_time=$time[$i];
			    if ((strtotime($time[$i]) - strtotime($time[$i+15]) != 900 ) ||  ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0))  //900 is 15 minutes
			    {
			       $demand_15 = fixTimeGap($LOOPNAME, $log,  $devicetablename, $demand_15_time, $log_interval, $Power, $pulse_meter,$pulse_demand_str);
			       echo sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15);
			    }
			}   
		    }  
		    else
		    {
                        $demand_15_time=$time[$i];
			            $demand_15 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
                        echo sprintf("Log Interval error %d Fix time Gap demand %f \n", $log_interval, $demand_15);
		    }
           
		  if ($timeIsPeak && ((abs($O_kW_value[$i] - $demand_15)) > 0.01))
		  {
                      //echo sprintf("i=%d time %s old %f new %f\n",$i, $demand_15_time, $OP_kW_value[$i], $demand_15);
                      //echo sprintf("i=%d %s: peak[%f %f] off[%f %f] power:%f new[%f] \n",$i, $demand_15_time,$O_kWh_value[$i], $O_kW_value[$i], $OP_kWh_value[$i],$OP_kW_value[$i],$Power,$demand_15);
                    
		      $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_15', `$Off_Peak_kW`=0, `$Peak_kWh`='$Power', `$Off_Peak_kWh`=0 WHERE `$devicetablename`.`time`='$demand_15_time';";
		      if ($test_only) 
              {
                   $kW_peak_query=1;
                   echo sprintf("i=%d time %s old %f new %f\n",$i, $demand_15_time, $O_kW_value[$i], $demand_15);
              }
              else
              {
                   $kW_peak_query=mysql_query($sql_peak_kW);   
              }
		      if(!$kW_peak_query)
		      {
                 echo sprintf("%s:Peak Demand not updated [%s]\n", $LOOPNAME, $sql_peak_kW) ;           
		      }
              else
              {
                 //echo sprintf("%s:[%s]\n",$LOOPNAME,$sql_peak_kW); 
               }
          }
		  else  if (!$timeIsPeak && ((abs($OP_kW_value[$i] - $demand_15)) > 0.01))
		  {

		      $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_15', `$Peak_kW`=0, `$Off_Peak_kWh`='$Power', `$Peak_kWh`=0 WHERE `$devicetablename`.`time`='$demand_15_time';";
		      if ($test_only) 
              {
                  $kW_peak_query=1;
                  echo sprintf("i=%d time %s old %f new %f\n",$i, $demand_15_time, $OP_kW_value[$i], $demand_15);
              }
             else
              {
                   $kW_peak_query=mysql_query($sql_peak_kW);   
              }
		      if(!$kW_peak_query)
		      {
                 echo sprintf("%s:Non-Peak Demand not updated [%s]\n", $LOOPNAME,$sql_peak_kW) ;           
		      }
              else
              {
                  //echo sprintf("%s:[%s]\n",$LOOPNAME,$sql_peak_kW); 
              }
          }
		 }
	    }
	    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value);//clear arrays as precaution
	} //end SCE&G utility

  // VIRGINIA DOMINION RATE GS 3
    if ($utility=="Virginia_Dominion_Rates")
    {
        $cost=mysql_fetch_array($rate_q);
        if (!$cost)  { echo sprintf("%s:util fetch array sql error",$LOOPNAME); }  
    
        echo sprintf("%s: %s\n", $LOOPNAME, $utility);
    
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
        
        echo sprintf("%s: check for last update entry \n", $LOOPNAME );

        //$someTimeAgo = lastUpdatedEntry($LOOPNAME,$log,$devicetablename,$Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW);
        //$NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));           
        $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $RP_kVAR, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW, $kVAR_30, $devicetablename, $someTimeAgo, $NowPlusDay);

        echo sprintf("%s: %s\n", $LOOPNAME, $sql_update);

        $RESULT_update = mysql_query($sql_update);
        if (!$RESULT_update)
        {
             echo sprintf("%s:Utility SQL select failure \n", $LOOPNAME);
             exit;
        }

        $rows_remaining = mysql_num_rows($RESULT_update);
        if (!$rows_remaining) {  echo sprintf("%s:Utility SQL rows remaining failure \n", $LOOPNAME);}

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
        
     $loop_week = $time[1];
	 $diff2 =  strtotime($loop_week);

    echo sprintf("%s:Loop for %d\n",$LOOPNAME,$countd);
    while ($i<=$countd-15)
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
            
            	   
			$diff1 =  strtotime($time[$i]);
			if (abs($diff2 - $diff1 ) > 604800)
			{
			    echo sprintf("week: %s current time %s\n", $time[$i], date("H:i:s") );
		        $loop_week = $time[$i];
		     	$diff2 =  strtotime($loop_week);
		    }

            //echo sprintf("%s:date time_d %s %d m %d h %d w %d\n", $LOOPNAME,$time[$i - 1], $timestamp, $month, $hour, $week);                   


            if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
            {
                $timeIsPeak = TRUE;  
               // $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
            }
            else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && $hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop)
            {
                $timeIsPeak = TRUE;    
                //$sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
            }
            else
            {
                $timeIsPeak = FALSE;   
                //$sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
            }
            
            /***** don't update kWh during correction script
            if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0))  
            {
                $peak_query=mysql_query($sql_peak_kWh);	
              
                if(!$peak_query)
                {
                    echo "$devicetablename $Power_time : $Power not updated kWh"."</br>";
                    echo sprintf("%s: Not Updated kWh %s %f\n", $LOOPNAME, $Power_time, $Power);   
                }
                else
                {
                    echo "$devicetablename $Power_time : $Power updated kWh"."</br>";
                    //echo sprintf("%s: kWh %s %f\n", $LOOPNAME, $Power_time, $Power);   
                }
            }
             ******/

            $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
            //echo sprintf("%s: calculated interval %d\n", $LOOPNAME, $interval); 

            if ($log_interval == 300) // 5 minute log
            {
                if($i<($countd-6)) //array bounds check
                {
                    $kVAR_Sum = $kVAR[$i+1]+$kVAR[$i+2]+$kVAR[$i+3]+$kVAR[$i+4]+$kVAR[$i+5]+$kVAR[$i+6];
                    $kVAR_Avg = $kVAR_Sum/6;
                    $kVAR_time = $time[$i];

                    $demand_30 = ($kWh[$i] - $kWh[$i+6])*2; 
                    $demand_30_time = $time[$i];
                    if ((strtotime($time[$i]) - strtotime($time[$i+6]) != 1800 )  || ($demand_30 > MAX_DEMAND ) || ($demand_30 < 0)) //1800sec = 30 minutes
                    {
                       $demand_30 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
                       //echo sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_30_time, $log_interval, $demand_30,(strtotime($time[$i]) - strtotime($time[$i+6])));
                    }
                    //echo sprintf("%s: 30 min demand %s %f\n", $LOOPNAME, $demand_30_time, $demand_30) ;  
                }
                else
                {
                    echo sprintf("6 array out of bounds countd %d\n", $countd);
                    exit;
                }
            }
            else
            {
                $kVAR_Sum = '';    
                $demand_30_time = $time[$i];
                $demand_30 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
                //echo sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_30);
            }
    
            if ($timeIsPeak && ((abs($O_kW_value[$i] - $demand_30)) > 0.01))
            {
                //echo sprintf("i=%d time %s old %f new %f\n",$i, $demand_30_time, $O_kW_value[$i], $demand_30);
                $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_30',`$Off_Peak_kW`=0, `$Peak_kWh`='$Power', `$Off_Peak_kWh`=0 WHERE `$devicetablename`.`time`='$demand_30_time';";
                if ($test_only) 
                {
                    $kW_peak_query=1;
                }
                else
                {
                    $kW_peak_query=mysql_query($sql_peak_kW);
                }
                if(!$kW_peak_query)
                {
                    echo sprintf("%s:Peak Demand not updated [%s]\n", $LOOPNAME, $kW_peak_query) ;           
                }
                else
                {
                    //echo sprintf("%s:[%s]\n",$LOOPNAME,$sql_peak_kW); 
                }    
            }
            else  if (!$timeIsPeak && ((abs($OP_kW_value[$i] - $demand_30)) > 0.01))
            {
                //echo sprintf("i=%d time %s old %f new %f\n",$i, $demand_30_time, $OP_kW_value[$i], $demand_30);
                $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_30',`$Peak_kW`=0, `$Off_Peak_kWh`='$Power', `$Peak_kWh`=0  WHERE `$devicetablename`.`time`='$demand_30_time';";
                if ($test_only) 
                {
                    $kW_peak_query=1;
                }
                else
                {          
                    $kW_peak_query=mysql_query($sql_peak_kW);
                }
                if(!$kW_peak_query)
                {
                    echo sprintf("%s:Non-Peak Demand not updated [%s]\n", $LOOPNAME, $kW_peak_query) ;           
                }
                else
                {
                    //echo sprintf("%s:[%s]\n",$LOOPNAME,$sql_peak_kW); 
                } 
            }
        }
      }  
      unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value, $kVAR, $kVAR30_value);//clear arrays as precaution
    }//end va rates
    
    echo sprintf("%s: Utility_cost END\n", $LOOPNAME); 	

}
 
function debug_log()
{
    global $log;
    
    require '../erms/includes/KLogger.php';
    $log = new KLogger ( "fixlog.txt" , KLogger::DEBUG );   // klogger debug everything
}

/* ================================================================================================================== */
/*
    BEGIN MAIN SCRIPT HERE !
*/
include "../conn/mysql_pconnect-all.php"; // mySQL database connector.

$Peak_kW="Peak_kW";
$Peak_kWh="Peak_kWh";
$Off_Peak_kW="Off_Peak_kW";
$Off_Peak_kWh="Off_Peak_kWh";
$EC_kWh='Energy_Consumption';

date_default_timezone_set('UTC'); /** set the timezone to UTC for all date and time functions **/
  
$someTimeAgo = date('Y-m-d H:i:s',strtotime('-6 month'));  //default for back 6 months
$NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));     
$test_only = FALSE;
$all = FALSE;
$allStartSet = FALSE;

if ($argc > 1)
{    
    $ship_argname =  $argv[1];
    if ($argc > 2)
    {
       if ($argv[2] == "test")
           $test_only = TRUE;
       else  if ($argv[2] == "all")
       {
           $all = TRUE;
           $someTimeAgo = date('Y-m-d H:i:s',strtotime('2010-09-01 00:00:00'));  
		   $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));  
		   if ($argc > 3)
		      {
		           $NowPlusDay = $argv[3]; 
		           $allStartSet = TRUE;
		      }           
        }   
       else
       {
           $someTimeAgo = $argv[2]; 
           if ($argc > 3)
              $NowPlusDay = $argv[3]; 
       }   
       
    if ($argc > 4)  
    {    
       if ($argv[4] == "test")
           $test_only = TRUE;
    }
  }
      echo sprintf("fixdemand for ship %s between %s %s %s \n",$ship_argname, $someTimeAgo, $NowPlusDay, ($test_only ? "Test-Only" : "Not a test"));
}
else
{
    echo "Usage: php fixdemand.php shipname [startdate enddate[all [enddate to start all]] [test] (ex:php fixdemand.php Gopher_State [2014-01-01 2014-06-30] [test] (test: to test without changes)\n";
    exit;
}
  

setShip($ship_argname);
echo 'Ship: '.$LOOPNAME."\n";

debug_log();

    if ($all)
    {
        $sql = sprintf("SELECT MIN(time) FROM %s",$devicetablename);
        $RESULT = mysql_query($sql);
        if (!$RESULT)
        {
             echo sprintf("%s:Utility SQL select failure %s  \n", $LOOPNAME, $sql);
             exit;
        }
        $row=mysql_fetch_array($RESULT);
	    $someTimeAgo=$row[0];
	    
	    if (!$allStartSet)
	    {
	        $sql = sprintf("SELECT MAX(time) FROM %s",$devicetablename);
            $RESULT = mysql_query($sql);
            if (!$RESULT)
            {
                 echo sprintf("%s:Utility SQL select failure %s  \n", $LOOPNAME, $sql);
                 exit;
            }
            $row=mysql_fetch_array($RESULT);
	        $NowPlusDay=$row[0];
	        echo sprintf(" %s:ALL: start time %s end time %s\n",$LOOPNAME, $someTimeAgo, $NowPlusDay );
	    }
    }

	$starttime = sprintf("%s 00:00:00", date('Y-m-d',strtotime($someTimeAgo))); 
	$endtime = sprintf("%s 00:00:00", date('Y-m-d',strtotime($NowPlusDay))); 

    if ($endtime < $starttime)
    {
        echo sprintf("%s aquisuitetable %s devicetablename (ERROR end < start) %s start[%s] end[%s]\n", $LOOPNAME, $aquisuitetable, $devicetablename, $starttime, $endtime);
        exit;
    }    
    else    
        echo sprintf("%s aquisuitetable %s devicetablename %s start[%s] end[%s]\n", $LOOPNAME, $aquisuitetable, $devicetablename, $starttime, $endtime);

        
        
    if (!$all)
    {
		utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log, $starttime, $endtime, $test_only);
		exit;
	}
	
	$done = FALSE;
	$all_end_time = $endtime;
	$all_start_time = date('Y-m-d H:i:s',strtotime('-2 month', strtotime($all_end_time)));
	while (!$done)
	{
	  if ($all_start_time == $starttime)
	        $done = TRUE;
	   echo sprintf("start %s end %s\n", $all_start_time, $all_end_time);
	   utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log, $all_start_time, $all_end_time, $test_only);     
       $all_end_time = date('Y-m-d H:i:s',strtotime('+1 day', strtotime($all_start_time)));
       $all_start_time = date('Y-m-d H:i:s',strtotime('-2 month', strtotime($all_end_time)));
       if ($all_start_time < $starttime)
       {
          $all_start_time = $starttime;
       }

	}

?>

