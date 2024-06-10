<?php
/*
 *  FIle: testchart.php
 *  Author: Carole Snow
 * 
 * Desc: 
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/


/*----------------------------------------------------------------------------------------------------*/
/*	the timezone function searches the Aquisuite_List for the aquisuite timezone and then matches it
	in the timezone table with a PHP recognized timezone. 
*/
function getTimezone($ship)
{
    global $log, $LOOPNAME;
    
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";		// get the aquisuite timezone in the Aquisuite_List
	$result = mysql_query($sql);
	if(!$result)
	{
            	$log->logInfo(sprintf("%s:timezone mysql select failed\n", $LOOPNAME)); 
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
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {   
            $log->logInfo(sprintf("%s:timezone rows2 failed\n", $LOOPNAME)); 
        }    
	$timezone = $row[0];

	return $timezone;
}



function checkKW($num_rows,$action, $devicetablename, $highs, $lows)
 {
     global $log, $LOOPNAME, $db,$timezone;
     global  $timeUTC;
     global  $energy;
     global  $kWFix;
     global  $Summer_Start,$Summer_End; 
     global  $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop;
     global  $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop;
     global   $Peak_Time_Non_Summer_Start2, $Peak_Time_Non_Summer_Stop2;
     $epsilon = 0.0001;
    
     $i=1;
     $tinterval[] = 300;
    while($i < ($num_rows-2))
    {	
        $tinterval[]=strtotime($timeUTC[$i-1]) - strtotime($timeUTC[$i]);  // get the data logging time interval
        $i++;
    }   
         
      
    $i=0;
        while($i < ($num_rows-2))
        {	
            $i++;
            if($energy[$i]!==0 && ($energy[$i-1] > $energy[$i]))		// make sure the kWh reading is not 0 and the current value is greater that the previous value.
            {	
                    $interval=strtotime($timeUTC[$i-1]) - strtotime($timeUTC[$i]);  // get the data logging time interval
                    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                 
                    $demand_15 = '';		
                    if($interval>150 && $interval<210)
                    {	if($i>4)
                            {
                                if($energy[$i]>0 && $energy[$i-5]>$energy[$i])
                                {
                                        $demand_15=($energy[$i-5] - $energy[$i])*4;
                                        $demand_15_time=$timeUTC[$i-5];
                                        $timestamp_15 = strtotime($timeUTC[$i-5].' UTC');
                                        $orig_PeakDemand = $highs[$i-5];
                                        $orig_OffDemand = $lows[$i-5];
                                 // $log->logInfo(sprintf("i=%d t=%s d=%s %f iv%d tinv %d ene[i] %f ene[i-5] %f \n",$i,$timeUTC[$i],$demand_15_time, $demand_15, $interval,$tinterval[$i],$energy[$i],$energy[$i-5]));           
                                }
                            }
                    }
                    else if($interval>240 && $interval<360)
                    {	if($i>2)
                            {
                                if($energy[$i]>0 && $energy[$i-3]>$energy[$i])
                                {
                                        $demand_15=($energy[$i-3] - $energy[$i])*4;
                                        $demand_15_time=$timeUTC[$i-3];
                                        $timestamp_15 = strtotime($timeUTC[$i-3].' UTC');
                                        $orig_PeakDemand = $highs[$i-3];
                                        $orig_OffDemand = $lows[$i-3];
                                  // $log->logInfo(sprintf("i=%d t=%s d=%s %f iv%d tinv %d ene[i] %f ene[i-3] %f \n",$i,$timeUTC[$i],$demand_15_time, $demand_15, $interval,$tinterval[$i],$energy[$i],$energy[$i-3]));           

                                }
                            }
                    }
                    else if($interval>780 && $interval<1020)
                    {	
                            if($i>1)
                            {
                                    if($energy[$i]>0 && $energy[$i-1]>$energy[$i])
                                    {
                                            $demand_15=($energy[$i-1] - $energy[$i])*4;
                                            $demand_15_time=$timeUTC[$i-1];
                                            $timestamp_15 = strtotime($timeUTC[$i-1].' UTC');
                                            $orig_PeakDemand = $highs[$i-1];
                                            $orig_OffDemand = $lows[$i-1];  
                                   // $log->logInfo(sprintf("i=%d t=%s d=%s %f iv%d tinv %d ene[i] %f ene[i-1] %f \n",$i,$timeUTC[$i],$demand_15_time, $demand_15, $interval,$tinterval[$i],$energy[$i],$energy[$i-1]));           
                                   }
                             }
                     }
                     else
                     {
                     // $log->logInfo(sprintf("time %s interval %d  tinterval %d\n",$demand_15_time,  $interval, $tinterval[$i]));           
                     }
                     
                        //  $log->logInfo(sprintf("time %s %f iv%d ene[i] %f ene[i-3] %f \n",$demand_15_time, $demand_15, $interval,$energy[$i],$energy[$i-3]));           
                     
                     date_default_timezone_set("UTC"); //set timezone back to UTC

                    if(!empty($demand_15))
                     {

                        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                        $month = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                        $hour = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                        $week = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp_15)));
                        date_default_timezone_set("UTC"); //set timezone back to UTC
                         //$log->logInfo(sprintf("time %s %d %s m %d h %d w %d\n",$demand_15_time,$timestamp_15,date('Y-m-d H:i:s', $timestamp_15), $month, $hour, $week) );           

                         $peak_time_on = FALSE;           
                        if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
                        {
                            $peak_time_on = TRUE;
                        }
                        else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
                        {
                            $peak_time_on = TRUE;
                        }

                        if($peak_time_on)
                        {
                            if ((abs($demand_15 - $orig_PeakDemand)) > $epsilon)  
                            {   
                             $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET Peak_kW='$demand_15', Off_Peak_kW= 0 WHERE `$devicetablename`.`time`='$demand_15_time';";
                             $log->logInfo(sprintf("%s update peak to %f from %f  off %f\n", $demand_15_time, $demand_15, $orig_PeakDemand, $orig_OffDemand) ); 
                             $kWFix++;
                              if ($action == CORRECT)
                               { 
                                 //$log->logInfo(sprintf("%s\n", $sql_peak_kW) ); 
                                  $sql_query=mysql_query($sql_peak_kW);
                                  if(!$sql_query)   $log->logInfo(sprintf("%s:SQL peak KW query failure \n", $LOOPNAME) ); 
                               } 
                            }
                        }

                        else if (!$peak_time_on)
                        {
                            if ((abs($demand_15 - $orig_OffDemand)) > $epsilon)  
                            {    
                                $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET Off_Peak_kW='$demand_15', Peak_kW=0 WHERE `$devicetablename`.`time`='$demand_15_time';";
                                $log->logInfo(sprintf("%s update off to %f from %f  peak %f\n", $demand_15_time, $demand_15, $orig_OffDemand, $orig_PeakDemand) ); 
                                $kWFix++;
                                if ($action == CORRECT)
                                { 
                                  //$log->logInfo(sprintf("%s\n", $sql_peak_kW) ); 
                                   $sql_query=mysql_query($sql_peak_kW);
                                  if(!$sql_query)   $log->logInfo(sprintf("%s:SQL offpeak KW query failure \n", $LOOPNAME) ); 
                               } 
                            }    
                        }

                    }
                }
            }                
}/* end function*/


function checkit($num_rows,$action, $highs, $lows)
 {
     global $log, $LOOPNAME, $timezone;
     global $devicetablename, $db;
     global  $timeUTC, $energy; 
     global $zeros, $kwhFix;
     global  $Summer_Start,$Summer_End; 
     global  $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop;
     global  $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop;
     global   $Peak_Time_Non_Summer_Start2, $Peak_Time_Non_Summer_Stop2;
     $epsilon = 0.0001;

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
    $i=0;
    while ($i < $num_rows)
    {     
       $timestamp[] = strtotime($timeUTC[$i].' UTC');	// timestamp the time in the device timezone format
       $month[] = idate(m, strtotime(date('Y-m-d H:i:s', $timestamp[$i])));
       $hour[] = idate(H,strtotime(date('Y-m-d H:i:s', $timestamp[$i])));
       $week[] = idate(w,strtotime(date('Y-m-d H:i:s', $timestamp[$i])));
       $i++;
    }
    date_default_timezone_set("UTC"); //set timezone back to UTC
  
    $i=1;
    while ($i < $num_rows)
    {     
       
       // $log->logInfo(sprintf("time %s %s %f peakh %f offh %f month %d hour %d\n", $timeUTC[$i], date('Y-m-d H:i:s', $timestamp[$i]), $energy[$i], $highs[$i], $lows[$i], $month[$i],$hour[$i]));        
        
        $Power=$energy[$i-1] - $energy[$i];		
        $Power_time=$timeUTC[$i-1];
        if ($Power == 0)
        {
           // $log->logInfo(sprintf("%s power %f \n", $Power_time, $Power) ); 
            $i++;
            $zeros++;
            continue;
        }
   
        $peak_time_on = FALSE;
        if($month[$i-1]>=$Summer_Start && $month[$i-1]<$Summer_End && $week[$i-1]<6 && $week[$i-1]>0 && $hour[$i-1]>=$Peak_Time_Summer_Start && $hour[$i-1]<$Peak_Time_Summer_Stop)
        {
           $peak_time_on = TRUE;
        }
        else if(($month[$i-1]<$Summer_Start || $month[$i-1]>=$Summer_End) && $week[$i-1]<6 && $week[$i-1]>0 && 
                ($hour[$i-1]>=$Peak_Time_Non_Summer_Start && $hour[$i-1]<$Peak_Time_Non_Summer_Stop || $hour[$i-1]>=$Peak_Time_Non_Summer_Start2 && $hour[$i-1]<$Peak_Time_Non_Summer_Stop2))
        {
            $peak_time_on = TRUE;
        }
        
        // if peak or off peak data is correct go on  to next thing
        if ($peak_time_on)
        {
            if ((abs($highs[$i-1]- $Power)) > $epsilon)
            {   
                 $sql="UPDATE `$db`.`$devicetablename` SET Peak_kWh='$Power', Off_Peak_kWh=0 WHERE `$devicetablename`.`time`='$Power_time';";
                //$log->logInfo(sprintf("%s %s \n", $Power_time, $sql) ); 
                $log->logInfo(sprintf("%s update peak to %f from %f off %f \n", $Power_time, $Power, $highs[$i-1], $lows[$i-1]) ); 
                $kwhFix++;  
                if ($action == CORRECT)
                { 
                     $peak_query=mysql_query($sql);
                     if(!$peak_query)   $log->logInfo(sprintf("%s:SQL kWh query failure \n", $LOOPNAME) ); 
                }    
            }   
        }
        else
        {
           if ((abs($lows[$i-1]- $Power)) > $epsilon)    
            {  
                 $sql="UPDATE `$db`.`$devicetablename` SET Off_Peak_kWh='$Power', Peak_kWh=0 WHERE `$devicetablename`.`time`='$Power_time';";
                 //$log->logInfo(sprintf("%s %s \n", $Power_time, $sql) ); 
                $log->logInfo(sprintf("%s update Off to %f from %f peak %f \n", $Power_time, $Power, $lows[$i-1], $highs[$i-1]) ); 
                $kwhFix++;
                if ($action == CORRECT)
                {   
                     $peak_query=mysql_query($sql);
                     if(!$peak_query)   $log->logInfo(sprintf("%s:SQL kWh query failure \n", $LOOPNAME) ); 
                }    
            }      
        }
 
     $i++;   
    } //end while loop  
}/* end function*/   


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
    else
    {
        echo "Invalid Ship\n";
        exit;
    }    
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
require_once ('../upload/jp_graph/graphs/jpgraph/jpgraph.php');
require_once ('../upload/jp_graph/graphs/jpgraph/jpgraph_line.php');
require_once ('../upload/jp_graph/graphs/jpgraph/jpgraph_date.php');

include "../conn/mysql_pconnect-all.php"; // mySQL database connector.

    date_default_timezone_set('UTC'); /** set the timezone to UTC for all date and time functions **/

    setShip("Cape_Decision");
       
    debug_log();
     
    $log->logInfo( sprintf("%s aquisuitetable %s devicetablename %s\n", $LOOPNAME, $aquisuitetable, $devicetablename));

    $db = 'bwolff_eqoff';
    $timezone = getTimezone($aquisuitetable);		// check for current time zone

    $log->logInfo(sprintf("%s: %s timezone %s \n", $LOOPNAME, $aquisuitetable,$timezone) ); 

    $sql_rates = "SELECT * FROM `$utility`";
    if(!$sql_rates) { echo "unable to process select sql_rates" .  $sql_rates . "\n"; exit;  }
    
    $rate_q = mysql_query($sql_rates);
    if(!$rate_q) { echo "unable to process mysql request" .  $sql_rates . "\n"; exit;  }

    $cost=mysql_fetch_array($rate_q);
    if(!$cost) { echo "unable to process mysql fetch cost" .  $sql_rates . "\n"; exit;  }

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
        $startDate = date('Y-m-d H:i:s',strtotime('2014-01-01 00:00:00'));
        //$now = date('Y-m-d H:i:s',strtotime('2013-07-02 00:00:00'));
        $now = date('Y-m-d H:i:s');   
    
     $sql_update = sprintf("SELECT time, Energy_Consumption, Peak_kW, Peak_kWh, Off_Peak_kW, Off_Peak_kWh FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $devicetablename, $startDate, $now);
     $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update) ); 

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)  {echo "\nSQL Result failure \n"; exit;} 

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining)    { echo "SQL rows remaining failure \n"; exit;} 

    $log->logInfo(sprintf("%s:total rows %d\n", $LOOPNAME,  $rows_remaining) ); 

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
    while($row=mysql_fetch_array($RESULT_update))
    {
        $energy[] = $row['Energy_Consumption'];
        $peakKW[] = $row['Peak_kW'];	
        $peakKWh[] = $row['Peak_kWh'];
        $offpeakKW[] = $row['Off_Peak_kW'];	
        $offpeakKWh[] = $row['Off_Peak_kWh'];
        $timeUTC[] = $row['time'];		// UTC Time
          // $log->logInfo(sprintf("1time %s energy %f peak %f  off %f %f %f\n", $row['time'], $row['Energy_Consumption'], $row['Peak_kW'], $row['Peak_kWh'],$row['Off_Peak_kW'], $row['Off_Peak_kWh']));           
        // $log->logInfo(sprintf("timeUTC %s local m %d hour %d week %d\n",  $timeUTC[$t],  $month[$t], $hour[$t], $week[$t]));
     }
     mysql_free_result($RESULT_update);
     
    date_default_timezone_set("UTC"); //set timezone back to UTC

    $zeros = 0; $kwhFix = 0;   $kWFix = 0;

    $max_rows = $rows_remaining - 1; 
    
    $datay1 = array(20,15,23,15);
    $datay2 = array(12,9,42,8);
    $datay3 = array(5,17,32,24);

    // Setup the graph
    $graph = new Graph(300,250);
    $graph->SetScale("textlin");

    $theme_class=new UniversalTheme;

    $graph->SetTheme($theme_class);
    $graph->img->SetAntiAliasing(false);
    $graph->title->Set('Filled Y-grid');
    $graph->SetBox(false);

    $graph->img->SetAntiAliasing();

    $graph->yaxis->HideZeroLabel();
    $graph->yaxis->HideLine(false);
    $graph->yaxis->HideTicks(false,false);

    $graph->xgrid->Show();
    $graph->xgrid->SetLineStyle("solid");
    $graph->xaxis->SetTickLabels(array('A','B','C','D'));
    $graph->xgrid->SetColor('#E3E3E3');

    // Create the first line
    $p1 = new LinePlot($datay1);
    $graph->Add($p1);
    $p1->SetColor("#6495ED");
    $p1->SetLegend('Line 1');

    // Create the second line
    $p2 = new LinePlot($datay2);
    $graph->Add($p2);
    $p2->SetColor("#B22222");
    $p2->SetLegend('Line 2');

    // Create the third line
    $p3 = new LinePlot($datay3);
    $graph->Add($p3);
    $p3->SetColor("#FF1493");
    $p3->SetLegend('Line 3');

    $graph->legend->SetFrameWeight(1);

    // Output line
    $graph->Stroke();

    
    
    set_time_limit( ONE_MIN );
    //checkIt($max_rows, TEST_ONLY, $peakKWh, $offpeakKWh);  

    //checkKW($max_rows, TEST_ONLY, $devicetablename, $peakKW, $offpeakKW);

$log->logInfo(sprintf("kWh %d zeros %d  KW %d\n",$kwhFix, $zeros, $kWFix)); 
    

?>
