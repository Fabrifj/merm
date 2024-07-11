<?php
 //....................................KLogger...............................
//include Logger.php";
$log = new KLogger ( "log.txt" , KLogger::DEBUG );
$log->logInfo('energy methods hello');
//.....................................End KLogger..........................

// The Vir_Dom_Rate function is used for the
// Virginia Dominion Utility Rate Structure.
// The Utility is what determines the rate
// Structure to use.
/**
 * mod_values()
 *
 * @param mixed $Time_Field
 * @param mixed $ship
 * @param mixed $value_days
 * @return
 */
function mod_values($Time_Field, $ship, $value_days, $ship_count,$report_month,$report_year)
{
       global $log;
       global $key;
       global $aquisuitetablename;
       global $device_class;
       global $annual_report;

	$VAL["display"] = $_REQUEST['display'];
	$VAL["todo"]=$_REQUEST['todo'];
	$VAL["report"]=$_REQUEST['report'];
        $VAL["Avail_Data"] = TRUE;
   // $report_month = $_REQUEST["month"];

        debugPrint("(mod_values) --START");
	debugPrint('(mod_values) display: ('.$VAL["display"].') todo: ('.$VAL["todo"].') report: ('.$VAL["report"].') month: ('.$report_month. ')'.' report year:'.$report_year);
        debugPrint('(mod_values) time: ('.$Time_Field.') ship: ('.$ship.') value_days: ('.$value_days.')');
             //if (!isset($_REQUEST['start_date_time']))
       	     //{
       	      //  $_REQUEST['start_date_time']  = date('Y-m-d H:i:s', strtotime("-1 month"));
       	      //   debugPrint("(mod_values):request start date ".$_REQUEST['start_date_time']."  request timezone start ".$_REQUEST['start_date_time'].$timezone);
	         //	}


	$timezone=timezone($aquisuitetablename[$key]);
	$month_date_set = FALSE;


	if ((isset($VAL["todo"]) and $VAL["display"]=="anydate") ||
           (isset($VAL["report"]) && ($report_month !=="month" && $report_month !=="annual")))
	{
            date_default_timezone_set("UTC");

            //echo 'bGraphing from: '.$_REQUEST['start_date_time'].' to: '.$_REQUEST['stop_date_time'].'<br />';
            //posted variabes
            /*
            $month1=$_REQUEST['month1'];
            $dt1=$_REQUEST['dt1'];
            $year1=$_REQUEST['year1'];
            $t1=$_REQUEST['t1'];
            $VAL["date_value_start"]=date('Y-m-d H:i:s',strtotime("$year1-$month1-$dt1 $t1:00".$timezone));
            $month2=$_REQUEST['month2'];
            $dt2=$_REQUEST['dt2'];
            $t2=$_REQUEST['t2'];
            $year2=$_REQUEST['year2'];
            $Time_Mtr_End=date('Y-m-d H:i:s',strtotime("$year2-$month2-$dt2 $t2:00".$timezone));
            */

            //echo 'date(): '.date('Y-m-d H:i:s', strtotime($_REQUEST['start_date_time'])).'<br />';

            $my_t   = getdate(strtotime($_REQUEST['start_date_time'].$timezone));
            $month1 = $my_t[month];
            $dt1    = $my_t[mday];
            $year1  = $my_t[year];
            $t1     = $my_t[hours].":".$my_t[minutes];

            $VAL["date_value_start"] = date('Y-m-d H:i:s', strtotime($_REQUEST['start_date_time'])); //date('Y-m-d H:i:s', strtotime("$year1-$month1-$dt1 $t1:00".$timezone));
            $VAL["save_startdate"] = $VAL["date_value_start"];

             date_default_timezone_set($timezone);
             $VAL["date_value_start"]  = gmdate('Y-m-d H:i:s', strtotime($_REQUEST['start_date_time'])); //set to GMT/UTC time
             date_default_timezone_set("UTC");
            /*
            print "<pre>\n";
            print_r($_REQUEST);
            print "</pre>\n";
            print "<pre>\n";
            print_r($my_t);
            print "</pre>\n";
            */
            debugPrint("(date_value_start1): ".$VAL["date_value_start"]);

            $my_t   = getdate(strtotime($_REQUEST['stop_date_time'].$timezone));
            $month2 = $my_t[month];
            $dt2    = $my_t[mday];
            $year2  = $my_t[year];
            $t2     = $my_t[hours].":".$my_t[minutes];

            $VAL["date_value_end"] = date('Y-m-d H:i:s', strtotime($_REQUEST['stop_date_time'])); //date('Y-m-d H:i:s', strtotime("$year2-$month2-$dt2 $t2:00".$timezone));
            $VAL["save_enddate"] = $VAL["date_value_end"];

            date_default_timezone_set($timezone);
            $VAL["date_value_end"]  = gmdate('Y-m-d H:i:s', strtotime($_REQUEST['stop_date_time'])); //set to GMT/UTC time

            $Time_Mtr_End = gmdate('Y-m-d H:i:s', strtotime($_REQUEST['stop_date_time'])); //date('Y-m-d H:i:s', strtotime("$year2-$month2-$dt2 $t2:00".$timezone));

            $log->logInfo(sprintf("mod_values: start %s  stop %s meter %s",$VAL["date_value_start"], $VAL["date_value_end"], $Time_Mtr_End));

            debugPrint('Calcing from: '.$VAL["date_value_start"].' to: '.$VAL["date_value_end"].' (time meter end) '.$Time_Mtr_End);

            if(isset($VAL["report"]))
            {
                $month = $report_month;
                $year = $report_year;
                debugPrint('(energy):Month '.$month.' Year '.$year);

                $VAL["save_startdate"] = date('Y-m-d H:i:s',strtotime($year.$month.$timezone));
                $VAL["save_enddate"] = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($VAL["save_startdate"])) . " +1 month"));

                date_default_timezone_set("UTC");

                $VAL["date_value_start"] = date('Y-m-d H:i:s',strtotime($year.$month.$timezone));
                $VAL["date_value_end"] = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($VAL["date_value_start"])) . " +1 month"));
                $VAL["report_month"] = date("F",strtotime($VAL["date_value_start"]));
                $Time_Mtr_End = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s", strtotime($VAL["date_value_start"])) . " +1 month"));

                $VAL["report_year"] = date("Y",strtotime($VAL["date_value_start"]));
                $month_date_set = TRUE;
                 $log->logInfo(sprintf("mod_values: report start %s  m %s y %s mtrend %s ",$VAL["date_value_start"], $VAL["report_month"], $VAL["report_year"], $Time_Mtr_End));
            }
            date_default_timezone_set("UTC");


            debugPrint('Calcing from: '.$VAL["date_value_start"].' to: '.$VAL["date_value_end"].' (time meter end) '.$Time_Mtr_End);

            debugPrint("Report Month: (".$VAL["report_month"].")");
            debugPrint("Report Year: (".$VAL["report_year"].")");

            debugPrint("(date_value_start2A): ".$VAL["date_value_start"]);

            $errorAction = $ship_count;
            if ($annual_report)
                $errorAction = 2; //don't show error on annual report
  
  		debugPrint('(energy_methods)check date range1');
            $current_ship_time = date_range_alert($Time_Field,$VAL["date_value_start"],$Time_Mtr_End,$ship,'DESC',  $errorAction);
            debugPrint('(energy_methods)cur_ship_time: ['.$current_ship_time.'] Time Meter End ['.$Time_Mtr_End.']');
            if (!$current_ship_time)
            {
                debugPrint("(mod_values) No Data for: ".$ship);
                $VAL = zero_values($VAL);
                return $VAL;  //return error if no data
            }

            if ($ship_count > 1)
            {
                if ((!$current_ship_time) || ($current_ship_time < $Time_Mtr_End))
                {
                    if (!$current_ship_time)
                    {
                        debugPrint("(mod_values) No Data: ".$ship);
                        $VAL = zero_values($VAL);
                        return $VAL;  //return error if no data for a single ship on multiple ship report
                     }

                  //  $VAL["kWh_Meter_Start"] = value_find($Time_Field,$Energy_Field,$ship,$VAL["date_value_start"]);
                  //  $VAL["kWh_Meter_End"] = value_find($Time_Field,$Energy_Field,$ship,$VAL["Time_Meter_End"]);
                  //  $VAL["kWh_Time_Meter_End"] = date("D M d Y G:i",strtotime($VAL["Time_Meter_End"]));


                    if ($current_ship_time && ($current_ship_time <  $Time_Mtr_End))
                    {
                        $VAL["Time_Meter_End"] = $current_ship_time;
                        $VAL["date_value_end"] = $current_ship_time;
                        debugPrint("(mod_values) No Data current time < meter end time: ".$ship." current_ship_time ".$current_ship_time);
                    }

                }
            }

		debugPrint('(energy_methods)check date range2');
                $VAL["Time_Meter_End"] = date_range_alert($Time_Field,$VAL["date_value_start"],$Time_Mtr_End,$ship,'DESC', $errorAction);
	debugPrint('(energy_methods)check date range3');
                $VAL["date_value_start"] = date_range_alert($Time_Field,$VAL["date_value_start"],$Time_Mtr_End,$ship,'ASC',$errorAction);
                $VAL["date_value_end"] = date_op($Time_Field,$VAL["Time_Meter_End"],5,'MINUTE',$ship,1);

            $log->logInfo(sprintf("mod_values:range start %s  stop %s meter %s",$VAL["date_value_start"], $VAL["date_value_end"], $VAL["Time_Meter_End"]));
	}



	if (((isset($VAL["display"]) && $VAL["display"] !== "anydate" && $VAL["report"]!=="report"))
		|| (isset($VAL["report"]) && ($report_month =="month" || $report_month =="annual")) || ((isset($value_days) && $value_days=="month")) && !$month_date_set)
	{

            $sql="SELECT $Time_Field, DATE_SUB($Time_Field,INTERVAL 5 MINUTE) AS minfive, DATE_SUB($Time_Field,INTERVAL 1 DAY) AS yesterday, DATE_SUB($Time_Field,INTERVAL 7 DAY) AS lastweek, DATE_SUB($Time_Field, INTERVAL 30 DAY) AS lastmonth FROM $ship ORDER BY $Time_Field DESC LIMIT 4";
            $query=mysql_query($sql);
            $row=mysql_fetch_array($query);

            debugPrint('(test) '.$sql);
            //echo 'mod_values: ' . $sql . "</br>";

            debugPrint(sprintF("mod_values): value_days: %s display: %s", $value_days, $VAL["display"]));
            if($VAL["display"] =="day" && $value_days!=="month")
            {
                $VAL["date_value_start"] = $row['yesterday'];
            }
            else if($VAL["display"] =="week" && $value_days!=="month")
            {
                $VAL["date_value_start"] = $row['lastweek'];
            }
            else if($VAL["display"]=="month" || $value_days=="month" || $report_month =="month")
            {
            	debugPrint('(energy_methods): request month ['.$report_month.'] value days ['.$value_days.']');
                debugPrint('(energy_methods): report month ['.$VAL["report_month"].']');
                $VAL["date_value_start"] = $row['lastmonth'];
                $VAL["report_month"] = "Last 30 Days";
                $VAL["report_year"] = date("Y",strtotime($row['lastmonth']));
            }

            $VAL["date_value_end"] = date("Y-m-d H:i:s",strtotime($row['minfive']));
            $VAL["Time_Meter_End"] = date("Y-m-d H:i:s",strtotime($row["$Time_Field"]));

           debugPrint('Report Month: ('.$VAL["report_month"].')  Report Year: ('.$VAL["report_year"].')  date value start: ('.$VAL["date_value_start"].')  Date Value End ['.$VAL["date_value_end"].'] Date Meter End ['.$VAL["Time_Meter_End"]);
	}


	#### DATABASE FIELD NAMES ####

        debugPrint('XXCalcing from: '.$VAL["date_value_start"].' to: '.$VAL["date_value_end"].' (time meter end) '.$Time_Mtr_End);

        if ($ship_count > 1)
        {
            if ($VAL["report_month"] == "Last 30 Days")
            {
                $difDates = abs(strtotime($VAL["date_value_end"]) - strtotime("now"));
                debugPrint('Date Diff: '.$difDates);
                if ($difDates > 86400)  //if end data is more than one day(86400 seconds) old reset range to recent month
                {
                     debugPrint('(energy_methods): return after date diff  ');
                     $VAL["date_value_end"] = date("Y-m-d H:i:s",strtotime("now"));
          	     $VAL["Time_Meter_End"] = date("Y-m-d H:i:s",strtotime($row["$Time_Field"]));
          	     $VAL["date_value_start"] = date("Y-m-d H:i:s",strtotime("-1 month"));
                     $VAL["report_year"] = date("Y-m-d H:i:s",strtotime("now"));
          	     debugPrint('(energy_methods: date value start: ('.$VAL["date_value_start"].')  Date Value End ['.$VAL["date_value_end"].'] Date Meter End ['.$VAL["Time_Meter_End"]);
                     $VAL = zero_values($VAL);
                     return $VAL;
          	 }
	    }
        }

            $Voltage_Field = "Voltage_Line_to_Line";
            $Current_Field = "Current";
            $Energy_Field = "Energy_Consumption";

        debugPrint('mod_values device class: ('.$device_class.')');

        // This is where we handle special meters!
        if($device_class=='27')
        {
             $Energy_Field = "Shore_Power";
             if($ship=='Cape_Kennedy_001EC6001433__device250_class27')
             {
                $Energy_Field = "`Shore_Power_(kWh)`";
             }
         }

	$Peak_kW_Field="Peak_kW";
	$Peak_kWh_Field="Peak_kWh";
	$Off_Peak_kW_Field="Off_Peak_kW";
	$Off_Peak_kWh_Field="Off_Peak_kWh";
	$Reactive_Power_Field = "Reactive_Power";
	$kVAR_Demand_Field = "30_Min_Reactive_kVAR";
	$Power_Factor_Field="Power_Factor";
	$Energy_Usage_Field = "Power_kWh";
	$Demand_Field = "15_Min_Demand_kW";
	$Air_Temp_Field = "Air_Temperature_Degrees_F";

	if($device_class=='17')
        {
	    $Energy_Field = "`Pulse2Consumption`";
            if($ship=='BSU_Portsmouth_0050C230EED9X__device082_class17')
            {
	        $Energy_Field = "`Pulse2Consumption`";
            }

        	$Energy_Usage_Field = "Pulse2Consumption";
	}

	#### VALUE CALCULATIONS ####

	// the following uses the values function
	// (explained above) to determine the needed
	// values for each module and cost associated
	// with those values.

        debugPrint('Hello');

	$VAL["kWh_Meter_Start"] = value_find($Time_Field,$Energy_Field,$ship,$VAL["date_value_start"]);
	$VAL["kWh_Meter_End"] = value_find($Time_Field,$Energy_Field,$ship,$VAL["Time_Meter_End"]);
	$VAL["kWh_Time_Meter_End"] = date("D M d Y G:i",strtotime($VAL["Time_Meter_End"]));

	$value_pf_max = values($Time_Field,'cdate','MAX',$Power_Factor_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["2_PF_Max"] = $value_pf_max["$Power_Factor_Field"]*100;

	$value_pf_min = values($Time_Field,'cdate','MIN',$Power_Factor_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["2_PF_Min"] = $value_pf_min["$Power_Factor_Field"]*100;

	$value_pf_avg = values($Time_Field,'','AVG',$Power_Factor_Field,'pfavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["2_PF_Avg"] = $value_pf_avg['pfavg']*100;

	$value_volts_max = values($Time_Field,'cdate','MAX',$Voltage_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["1_Voltage_Max"] = $value_volts_max["$Voltage_Field"];

	$value_volts_min = values($Time_Field,'cdate','MIN',$Voltage_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["1_Voltage_Min"] = $value_volts_min["$Voltage_Field"];

	$value_volts_avg = values($Time_Field,'','AVG',$Voltage_Field,'voltavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["1_Voltage_Avg"] = $value_volts_avg['voltavg'];

	$value_kVAR = values($Time_Field,'cdate','MAX',$Reactive_Power_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["kVAR_Max_Time"]=$value_kVAR['cdate'];
	$VAL["1_kVAR_Max"]= $value_kVAR["$Reactive_Power_Field"];

	$value_kVAR_avg = values($Time_Field,'','AVG',$Reactive_Power_Field,'kVARavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["1_kVAR_Avg"] = $value_kVAR_avg['kVARavg'];

	$value_current = values($Time_Field,'cdate','MAX',$Current_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["Current_Demand_Time"]=$value_current['cdate'];
	$VAL["1_Current_Demand"]= $value_current["$Current_Field"];

	$value_current_avg = values($Time_Field,'','AVG',$Reactive_Power_Field,'ampavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["1_Current_Avg"] = $value_current_avg['ampavg'];

	$value_kW = values($Time_Field,'','SUM',"$Off_Peak_kW_Field+$Peak_kW_Field",'kWsum',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$value_kW_count =  values($Time_Field,'','COUNT',"$Off_Peak_kW_Field+$Peak_kW_Field",'kWcount',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["kW_sum"]=$value_kW['kWsum'];
	$VAL["kW_count"]=$value_kW_count['kWcount'];

	$value = values($Time_Field,'cdate','MAX',$Peak_kW_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);

	$VAL["Peak_Demand_Time"]=$value['cdate'];
	$VAL["PD_mtime"]=idate('m', strtotime($VAL["Peak_Demand_Time"]));
	$VAL["Peak_Demand"] = $value["$Peak_kW_Field"];

       $log->logInfo(sprintf("energy:pk time %s  m %d demand %f",$VAL["Peak_Demand_Time"], $VAL["PD_mtime"],$VAL["Peak_Demand"]));

       //echo 'mod_values[$Peak_kW_Field]' . $Peak_kW_Field . '</br>';
        //echo 'mod_values[Peak_Demand_Time0]' . $VAL["Peak_Demand_Time"] . '</br>';

	$value2 = values($Time_Field,'cdate','MAX',$Off_Peak_kW_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);

	$VAL["Off_Peak_Demand_Time"]=$value2['cdate'];
	$VAL["OPD_mtime"] = idate('m', strtotime($VAL["Off_Peak_Demand_Time"]));
	$VAL["Off_Peak_Demand"] = $value2["$Off_Peak_kW_Field"];

	$value3 = values($Time_Field,'','AVG',"$Off_Peak_kW_Field+$Peak_kW_Field",'kWavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["Demand_avg"] = $value3['kWavg'];

	$value4 = values($Time_Field,'','SUM',$Peak_kWh_Field,'Peaksum',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["Peak_kWh_Total"] = $value4['Peaksum'];

	$value5 = values($Time_Field,'','SUM',$Off_Peak_kWh_Field,'OffPeaksum',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
	$VAL["Off_Peak_kWh_Total"] = $value5['OffPeaksum'];
	$VAL["kWh_Total"] = $VAL["Peak_kWh_Total"]+$VAL["Off_Peak_kWh_Total"];

	//C02
	$VAL["2_Total_CO2"] =($VAL["kWh_Total"]*601.292474+$VAL["kWh_Total"]*0.899382565*310+$VAL["kWh_Total"]*0.01839836*21)/pow(10,6);

	$utility=utility_check($aquisuitetablename[$key]);
        $VAL["utility"] = $utility;


    switch($utility)
    {
		case "Virginia_Dominion_Rates":
                    $value6 = values($Time_Field,'cdate','MAX',$kVAR_Demand_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                    $VAL["kVAR_Demand_Time"]=$value6['cdate'];
                    $VAL["kVAR_Demand"]= $value6["$kVAR_Demand_Field"];

                 break;
                case "Virginia_Electric_and_Power_Co":
                  $real_power_limit = '';
                  $hasRealPower = checkRealPower($ship);

                  if($hasRealPower) {
                    $real_power_limit = " AND (`Real_Power`>10) ";
                  }

                  $sql = "SELECT DATE_FORMAT(CONVERT_TZ(time,'UTC', '$timezone'), '%c') AS TZ,
                    SUM(Peak_kWh) AS Peak_kWh_Monthly_Sum,
                    SUM(Off_Peak_kWh) AS Off_Peak_kWh_Monthly_Sum
                    FROM $ship
                    WHERE time BETWEEN '".$VAL['date_value_start']."' AND '".$VAL['date_value_end']."'
                    $real_power_limit
                    GROUP BY DATE_FORMAT(CONVERT_TZ(time,'UTC', '$timezone'), '%c')
                    ORDER BY CONVERT(TZ, UNSIGNED)";
                  debugPrint("(values)(Virginia_Electric_and_Power_Co): $sql");
                  $res = mysql_query($sql);

                  while($row = mysql_fetch_array($res)) {
                    $VAL['kWh_Monthly_Totals'][$row['TZ']] = array(
                      'Peak_kWh' => $row['Peak_kWh_Monthly_Sum'],
                      'Off_Peak_kWh' => $row['Off_Peak_kWh_Monthly_Sum']
                    );
                  }
                 break;

		case "SCE&G_Rates":
	          $input_time = date('Y-m-d H:i:s',strtotime($VAL["Peak_Demand_Time"]));
                  $bill_demand = billvalues($Time_Field, $ship, $input_time);
                  $bill_demand_values = calculate_bill_values($bill_demand, $VAL["Peak_Demand"], $VAL["Off_Peak_Demand"], $Power_Factor_Field);
                  $VAL = array_merge($VAL, $bill_demand_values);

                  $tmp_val = ($VAL["kWh_Total"] /1000.00);
                  $tmp_val = ($tmp_val * 1079.57);
                  $VAL["2_Total_CO2"] = ($tmp_val / 2204.62);

		break;
                case "Entergy_NO_Rates":
                  $VAL["Peak_Demand"] = $VAL["Peak_Demand"] > $VAL["Off_Peak_Demand"] ? $VAL["Peak_Demand"] : $VAL["Off_Peak_Demand"];

                  $VAL["Peak_Demand_Time"] = $VAL["Peak_Demand"] > $VAL["Off_Peak_Demand"] ? $VAL["Peak_Demand_Time"] : $VAL["Off_Peak_Demand_Time"];
                  $VAL["Peak_kWh_Total"] = $VAL["Peak_kWh_Total"] + $VAL["Off_Peak_kWh_Total"];
                  $VAL["Off_Peak_Demand_Time"] = NULL;
                  $VAL["Off_Peak_Demand"] = 0;
                  $VAL["Off_Peak_kWh_Total"] = 0;

                break;

		case "Nav_Fed_Rates":
                 $temp_value_max = values($Time_Field,'cdate','MAX',$Air_Temp_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                 $VAL["Air_Max_Temp_Time"] = $temp_value_max['cdate'];
                 $VAL["Air_Max_Temp"] = $temp_value_max["$Air_Temp_Field"];

                 $temp_value_min = values($Time_Field,'cdate','MIN',$Air_Temp_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                 $VAL["Air_Min_Temp_Time"] = $temp_value_min['cdate'];
                 $VAL["Air_Min_Temp"] = $temp_value_min["$Air_Temp_Field"];

                 $VAL["temp_value_avg"] = values($Time_Field,'','AVG',$Air_Temp_Field,'atavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                 $VAL["Air_Avg_Temp"] = $temp_value_avg['atavg'];

                 $value = values($Time_Field,'cdate','MAX',$Demand_Field,'',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                 $VAL["Peak_Demand_Time"]=$value['cdate'];
                 $VAL["PD_mtime"]=idate(m,strtotime($Peak_Demand_Time));
                 $VAL["Peak_Demand"]=$value["$Demand_Field"];

                 $VAL["Off_Peak_Demand_Time"] = $VAL["Peak_Demand_Time"];
                 $VAL["Off_Peak_Demand"] = $VAL["Peak_Demand"];

                 $value3 = values($Time_Field,'','AVG',$Demand_Field,'kWavg',$ship,$VAL["date_value_start"],$VAL["date_value_end"]);
                 $VAL["Demand_avg"] = $value3['kWavg'];

                 $VAL["Peak_kWh_Total"] = $VAL["kWh_Meter_End"]-$VAL["kWh_Meter_Start"];
                 $VAL["Off_Peak_kWh_Total"] = $VAL["Peak_kWh_Total"];
                 $VAL["kWh_Total"] = $VAL["Peak_kWh_Total"];


                 $VAL["2_Total_CO2"] =($VAL["kWh_Total"]*601.292474+$VAL["kWh_Total"]*0.899382565*310+$VAL["kWh_Total"]*0.01839836*21)/pow(10,6);
            break;
    }

	// Total number of days that a ship was
	// connected to power. The formula looks
	// only counts time where the Voltage >
	// 10 Volts.

	$VAL["Lay_Days"] = day_count($Time_Field,$ship,$VAL["date_value_start"],$VAL["date_value_end"],$Voltage_Field,10);
        debugPrint('(init mod_values) Lay Days '.$VAL["Lay_Days"]." start ".$VAL["date_value_start"].' end '.$VAL["date_value_end"].' kwh_total '.$VAL["kWh_Total"]);

	// In case the number of lay days is
	// calculated to be 0. This prevents
	// a PHP error warning from occuring.
	if(!empty($VAL["Lay_Days"]))
	{
    	   $VAL["kWh_day"] = $VAL["kWh_Total"]/$VAL["Lay_Days"];
    	   $VAL["2_CO2_day"] = $VAL["2_Total_CO2"]/$VAL["Lay_Days"];
	}


	return $VAL;
}
/**
 * mod_cost()
 *
 * @param mixed $Time_Field
 * @param mixed $date_value_start
 * @param mixed $date_value_end
 * @param mixed $ship
 * @param mixed $Off_Peak_kWh_Total
 * @param mixed $Peak_kWh_Total
 * @param mixed $Peak_Demand
 * @param mixed $Peak_Billed_Demand
 * @param mixed $Off_Peak_Billed_Demand
 * @param mixed $Demand_rkVA
 * @param mixed $kVAR_Demand
 * @param mixed $Off_Peak_Demand
 * @param mixed $Peak_Demand_Time
 * @param mixed $OPD_mtime
 * @param mixed $Lay_Days
 * @return
 */
function mod_cost($Time_Field, $ship, $val)
{
        $date_value_start = $val["date_value_start"];
        $date_value_end = $val["date_value_end"];
        $Peak_Demand = $val["Peak_Demand"];
        $Lay_Days = $val["Lay_Days"];

	// This section uses the utility variable
	// that will be determined by a vessels
	// table name prior to this function. the
	// utility is used to determine the correct
	// rate structure to be used.
        global $aquisuitetablename;
        global $log;
        global $key;

        debugPrint('(mod_cost): Date Value Start '.$date_value_start.' Date Value End '.$date_value_end.' Lay Days '.$Lay_Days);


        // @TODO Decide how to handle a missing utility or rate schedule
        $utility = utility_check($aquisuitetablename[$key]);
        $cost = utility_schedule_rates($utility, $date_value_start, $date_value_end);

        $COST = schedule_cost($ship, $utility, $val, $cost);

	#### COST METRIC CALCULATIONS ####

	// In case the number being divided is
	// 0. This prevents a PHP error
	// warning from occuring.
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

        $kWh_Total = $COST["kWh_Total"];

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

	return $COST;
}

function zero_values($my_val)
{
    $my_val["kW_sum"]=0;
    $my_val["kW_count"]=0;
    $my_val["Peak_Demand_Time"]=0;
    $my_val["Peak_Demand"] = 0;
    $my_val["Off_Peak_Demand_Time"]= 0;
    $my_val["Off_Peak_Demand"] =  0;
    $my_val["Demand_avg"] =  0;
    $my_val["Peak_kWh_Total"] =  0;
    $my_val["Off_Peak_kWh_Total"] = 0;
    $my_val["kWh_day"] = 0;
    $my_val["kWh_Total"] =  0;
    $my_val["Lay_Days"] = 0;
    $my_val["Avail_Data"] = FALSE;

    debugPrint("(mod_values) Zero Data peak time ".$my_val["Peak_Demand_Time"].' off peak time '.$my_val["Off_Peak_Demand_Time"]);

    return $my_val;
}

?>
