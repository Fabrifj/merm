<?php

 /**
  * RMS Module
  *
  * RMS - Resource Management System
  *
  * *
  * *
  * *
  *
  * @author
  * @version 2.0
  * @since 2.0
  *
  *
  *
  * @example    http://<server>/erms/upload/jp_graph/graphs/erms_module_v2.php?display=day&user=decision&module=mod1
  *
  * @param string $display  Display mode (day, month, anydate)
  * @param string $user     Ship or Facility (decision, kennedy, etc.)
  * @param string $module   ERMS sub-module (mod1, mod6 - see ERMS_Modules class below)
  */

session_start();


//....................................KLogger...............................
require '../../../erms/includes/KLogger.php';
$log = new KLogger ( "log.txt" , KLogger::DEBUG );

require './src/logger.php';
$testLogger = new Logger("Test montly");
//.....................................End KLogger..........................

error_reporting (E_ALL ^ E_NOTICE);

class ERMS_Modules
{
    const Overview = 'mod0';
    const PowerAndCostAnalysis = 'mod1';
    const EnergyMeterData = 'mod3';
    const WaterMeterData = 'mod5';
    const MonthlyReports = 'mod6';
}

$curmodule = ERMS_Modules::EnergyMeterData;
//$curmodule = ERMS_Modules::WaterMeterData; //testing water

$Title = "";

include '../../../erms/includes/debugging.php';
include '../../../schedules/schedules.php';
include '../../../erms/includes/access_control.php';
// include '../../../erms/includes/data_methods.php';
include '../../../erms/includes/energy_methods.php';
include '../../../erms/includes/gfx_methods.php';
include_once ('../../../conn/mysql_connect-ro.php');
include_once ('../../../Auth/auth.php');

//Update 2024
require './src/db_helpers.php';
require './src/functions.php';
// Redirect happens within isAuthenticated and isPermitted
// but we still want to make sure we exit the main script
if(!isAuthenticated() || !isPermitted($_REQUEST['user'], $_REQUEST['shipClass'], $_REQUEST['ship'])) {
  exit;
}

//TODO figure out what this is used for
$client_ip_address =  getRealIpAddr();
$log->logInfo('Client IP['.$client_ip_address.']');

$log->logInfo('ERMS MODULE 1 erms_module1');
include '../../../erms/includes/init_2024.php';
$log->logInfo('ERMS MODULE 2nd');
 debugPrint('(erms_module1) START ');

setModLinks($username, $_REQUEST['shipClass'], $shipDeviceClass[0]);
// TODO need to clean this up somehow
$module_name = $_SESSION['user_data']['shipMods'][$module]["text"];
if ($module == "mod6") {
  if($annual_report) {
    $module_name = 'Annual Report for '.$_REQUEST["year"];
  } else {
      $module_name = 'Monthly Report for '.$_REQUEST["year"].",".$_REQUEST["month"];
  }
}
setBreadcrumbs("ship", $module_name, $indicator);

//Update 2024!! 
$performance = fetch_last_30_days($testLogger, $loopname);


?>

<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Marine Design & Operations</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

<!--- server structure	-->
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/930div.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/menu.css" />
    <link rel="stylesheet" type="text/css" media="all" href="/erms/css/jquery-ui-timepicker-addon.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/colorbox.css" />
    <link rel="stylesheet" type="text/css" media="print" href="/erms/css/printit.css" />
 <!--->

    <!--<link rel="stylesheet" media="all" type="text/css" href="//code.jquery.com/ui/1.9.1/themes/lightness/jquery-ui.css" />-->
    <!--<link rel="stylesheet" media="all" type="text/css" href="http://code.jquery.com/ui/1.9.1/themes/flick/jquery-ui.css" /> -->
     <link rel="stylesheet" media="all" type="text/css" href="//code.jquery.com/ui/1.10.3/themes/flick/jquery-ui.css" />
    <!--<link rel="stylesheet" media="all" type="text/css" href="http://code.jquery.com/ui/1.9.1/themes/south-street/jquery-ui.css" />-->
    <!--<link rel="stylesheet" media="all" type="text/css" href="/erms/css/south-street/jquery-ui.css" />-->
    <!--<link rel="stylesheet" media="all" type="text/css" href="/erms/css/ui-lightness/jquery-ui-1.8.18.custom.css" />-->
<!-- css vendor start -->
<link rel="stylesheet" type="text/css" href="/erms/css/vendor/font-awesome-4.0.3/css/font-awesome.min.css" />
<!-- css vendor end -->
<!-- css modules start -->
<link rel="stylesheet" type="text/css" href="/erms/css/nav/navbar.css" />
<link rel="stylesheet" type="text/css" href="/erms/css/nav/breadcrumbs.css" />
<link rel="stylesheet" type="text/css" href="/erms/css/ship_view/main.css" />
<!-- css modules end -->

     <script type="text/javascript" src="//code.jquery.com/jquery-1.8.2.min.js"></script>
     <script type="text/javascript" src="//code.jquery.com/ui/1.9.1/jquery-ui.min.js"></script>

<!--- server structure -->
	<script type="text/javascript" src="/erms/jquery/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="/erms/jquery/jquery-ui-sliderAccess.js"></script>
	<script type="text/javascript" src="/erms/jquery/jquery.ui.monthpicker.js"></script>
	<script type="text/javascript" src="/erms/jquery/jquery.colorbox.js"></script>

    <script type="text/javascript">
        // Do not allow this version of ERMS to load into an iframe, bust out of it - back button still works
        if(top != self) top.location.replace(location);
    </script>
      <script type="text/javascript">
        function update()
        {
            document.getElementById('f1').submit();
            document.getElementById('f1').reset();
        }
        function update2() {
            document.getElementById('f2').submit();
        }
    </script>
    <script type="text/javascript">
        function updateMeter()
        {
            document.getElementById('f1').submit();
         }
    </script>
    <script type="text/javascript">
        function s()
        {
            toggleVis('date_time_panel');
        }

        function goToNewPage(dropdownlist)
        {
            var url = dropdownlist.options(dropdownlist.selectedIndex).value;
            if (url != "")
            {
                top.location = url;
            }
        }
        function toggleVis(elemId)
        {
        	var ele = document.getElementById(elemId);
        	if(ele.style.display == "block")
            {
        		ele.style.display = "none";
          	}
        	else
            {
        		ele.style.display = "block";
        	}
        }
        $(function() {
            $( document ).tooltip({
                position: {
                    my: "center bottom-20",
                    at: "center top"
                }
            });
        });
    </script>
</head>
<?php

	//$Title = 'Cape Decision';
    //$module = 'mod1';
?>
<body>
  <div id="navBar"></div>
  <div id="breadcrumbs"></div>

<?php
    switch ($module)
    {
        case ERMS_Modules::Overview:
    	// Energy Power and Cost Analysis
        case ERMS_Modules::PowerAndCostAnalysis:
?>
    <!-- Last 30 Days Summary -->
    <div class="wrapper">
        <div class="def_all">
            <div id="last30_summary_header">
                <span style="font-weight: bold;">Summary: Last 30 Days</span><br />
            </div>
    		<table class="tblLast30">
    		<tr>
    			<td>Average Cost Per Lay Day <b>$<?php echo $performance["avg_cost"] ?></b></td>
    			<td>Average kWh Per Lay Day <b><?php echo $performance["avg_kwH"] ?></b></td>
    			<td>Cost Per kWh <b>$<?php echo $performance["avg_kw"] ?></b></td>
    		</tr>
            </table>
        </div>
    </div>
    <div id="metricsTable"></div>
    <!-- Graph for selected period -->
	<div class="wrapper">
          <div id="mainGraph" class="main-graph-container"></div>
        <div class="graph_right">
         <!-- Graph Information and Selection -->
          <div class="info_wrapper">
           <!-- Graph Period Selection -->
            <?php if ($module == ERMS_Modules::Overview) { ?>
              <div class="iwbox1">
                        <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Report Period</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
<?php
   			 $VAL["report_year"] = str_replace(',','',$VAL["report_year"]);
   			 echo '
    					<form id="f" action="" method="POST">
    					<input name="report" type="hidden" value="report" />
    					<label>Select Report Month</label>
    					<select name="month" id="month">
    					<option value="01" ';if ($VAL["report_month"]=="January"){echo "selected";} echo '>January</option>
    					<option value="02" ';if ($VAL["report_month"]=="February"){echo "selected";} echo '>February</option>
    					<option value="03" ';if ($VAL["report_month"]=="March"){echo "selected";} echo '>March</option>
    					<option value="04" ';if ($VAL["report_month"]=="April"){echo "selected";} echo '>April</option>
    					<option value="05" ';if ($VAL["report_month"]=="May"){echo "selected";} echo '>May</option>
    					<option value="06" ';if ($VAL["report_month"]=="June"){echo "selected";} echo '>June</option>
    					<option value="07" ';if ($VAL["report_month"]=="July"){echo "selected";} echo '>July</option>
    					<option value="08" ';if ($VAL["report_month"]=="August"){echo "selected";} echo '>August</option>
    					<option value="09" ';if ($VAL["report_month"]=="September"){echo "selected";} echo '>September</option>
    					<option value="10" ';if ($VAL["report_month"]=="October"){echo "selected";} echo '>October</option>
    					<option value="11" ';if ($VAL["report_month"]=="November"){echo "selected";} echo '>November</option>
    					<option value="12" ';if ($VAL["report_month"]=="December"){echo "selected";} echo '>December</option>
    					<option value="month" ';if ($VAL["report_month"]=="Last 30 Days"){echo "selected";} echo '>Last 30 Days</option>
    				    <option value="annual" ';if ($VAL["report_month"]=="Annual"){echo "selected";} echo '>Annual</option>
    					</select>
    					<label>Select Report Year</label><br />
    					<select name="year" id="year">
                        <br />                            	    	
                                        <option value="2024" ';if ($VAL["report_year"]=="2024"){echo "selected";} echo '>2024</option>
                            	    	<option value="2023" ';if ($VAL["report_year"]=="2023"){echo "selected";} echo '>2023</option>
                            	    	<option value="2022" ';if ($VAL["report_year"]=="2022"){echo "selected";} echo '>2022</option>
                            	    	<option value="2021" ';if ($VAL["report_year"]=="2021"){echo "selected";} echo '>2021</option>
                            	    	<option value="2020" ';if ($VAL["report_year"]=="2020"){echo "selected";} echo '>2020</option>
                            	    	<option value="2019" ';if ($VAL["report_year"]=="2019"){echo "selected";} echo '>2019</option>
                            	    	<option value="2018" ';if ($VAL["report_year"]=="2018"){echo "selected";} echo '>2018</option>
                            	    	<option value="2017" ';if ($VAL["report_year"]=="2017"){echo "selected";} echo '>2017</option>
                            	    	<option value="2016" ';if ($VAL["report_year"]=="2016"){echo "selected";} echo '>2016</option>
                            	    	<option value="2015" ';if ($VAL["report_year"]=="2015"){echo "selected";} echo '>2015</option>
    				    	<option value="2014" ';if ($VAL["report_year"]=="2014"){echo "selected";} echo '>2014</option>
                                        <option value="2013" ';if ($VAL["report_year"]=="2013"){echo "selected";} echo '>2013</option>
    					<option value="2012" ';if ($VAL["report_year"]=="2012"){echo "selected";} echo '>2012</option>
    					<option value="2011" ';if ($VAL["report_year"]=="2011"){echo "selected";} echo '>2011</option>
    					<option value="2010" ';if ($VAL["report_year"]=="2010"){echo "selected";} echo '>2010</option>
    					</select><br />
    					<input type="submit" value="Show Report" />
    					</form>
                        ';
?>
                    </div>
               </div>
            <?php } else { ?>
            <div class="iwbox1">
                    <form id="f1" action="" method="POST">
                        <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Graph Range</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
                            <div id="radio">
                                <input type="radio" id="radio1" name="display" value="day" onclick="update()" <?php if($VAL["display"]=="day"){echo "checked";} else {echo '';} ?> /><label for="radio1">Last 24 Hours</label><br />
                                <input type="radio" id="radio2" name="display" value="week" onclick="update()" <?php if($VAL["display"]=="week"){echo "checked";} else {echo '';} ?> /><label for="radio2">Last 7 Days</label><br />
                                <input type="radio" id="radio3" name="display" value="month" onclick="update()" <?php if($VAL["display"]=="month"){echo "checked";} else {echo '';} ?> /><label for="radio3">Last 30 Days</label><br />
                                <input type="radio" id="radio4" name="display" value="anydate" onclick="s()" <?php if($VAL["display"]=="anydate"){echo "checked";} else {echo '';} ?> /><label for="radio4">Date &amp; Time Selection</label><br />
                            </div>
                            <br />
                            <div id="date_time_panel" style="display: none">
                                <label for="start_date_time">Start Date/Time</label><br />
                                <input type="text" name="start_date_time" id="start_date_time" value="" />
                                <br />
                                <label for="stop_date_time">End Date/Time</label><br />
                                <input type="text" name="stop_date_time" id="stop_date_time" value="" />
                                <input name="todo" type="hidden" value="submit" />
                                <br />
                                <button id="btnShowGraph">Show Graph</button>
                                <!--<input type="button" value="Show Graph" onclick="update()" />-->
                            </div>
                            </ul>
                        </div>
            	        <br />
            	       <div id="change"></div>
                    </form>

                </div>

                <!-- Selected Detailed Summary -->
                <div class="iwbox2">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Detailed Summary</span><br />
                    </div>
                    <div>
                        <table class="tblDetailedSummary">
                		<tr>
                			<td style="color:black;">Average kW</td>
                			<td><b><?php echo $VAL["Demand_avg"] ?> kW</b></td>
                		</tr>
                		<tr class="odd">
                			<td style="color:black;">Max Peak Demand</td>
                			<td><b><?php echo $VAL["Peak_Demand"] ?> kW</b></td>
                		</tr>
                		<tr>
                			<td style="color:black;">Time of Occurrence</td>
                			<td><b><?php echo date('Y-m-d H:i',strtotime($VAL["Peak_Demand_Time"])) ?></b></td>
                		</tr>
                		<tr class="odd">
                			<td style="color:black;">Total kWh</td>
                			<td><b><?php echo $VAL["kWh_Total"] ?> kWh</b></td>
                		</tr>
                		<tr>
                			<td style="color:black; border-bottom: 0px;">kWh Per Lay Day</td>
                			<td style="border-bottom: 0px;"><b><?php echo $VAL["kWh_day"] ?> kWh/day</b></td>
                		</tr>
                        </table>
                    </div>
               </div>
                <?php } ?>
            </div>
          </div>
     </div>

    <div id="dialog-message" title="Select a date range">
        <p>
            <span class="ui-icon ui-icon-circle-check" style="float: left; margin: 0 7px 50px 0;"></span>
            Please select a valid date range.
        </p>
    </div>


    <script type="text/javascript">
        $(function() {
            // Y-m-d H:i:s
            $('#start_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
                controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
            $('#stop_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
            	controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
        });

        //alert ('Hello!');
        //alert ($('input:radio[name=display]:checked').val());
        var radio_value = 0;

        radio_value = $('input:radio[name=display]:checked').val();

        //alert (radio_value);
        if(radio_value === 'anydate')
        {

            //alert (radio_value);
            toggleVis('date_time_panel');
            document.getElementById('start_date_time').value = '<?php echo $VAL["date_value_start"] ?>';
            document.getElementById('stop_date_time').value = '<?php echo $VAL["date_value_end"] ?>';

        }

        $('#dialog-message').dialog
            (
                {
                    resizable: false,
                    modal: true,
                    autoOpen: false,
                    buttons:
                    {
                        Ok: function()
                        {
                            $( this ).dialog( 'close' );
                        }
                    }
                }
            );
    </script>
    <script type="text/javascript">
        $(function()
        {
            $("input[type=submit], button")
            .button()
            .click(function( event )
            {
                var target = event.target

                console.log(event);
                if(target.value !== 'Show Report') {
                  event.preventDefault();
                }
            });

            $("#radio" ).buttonset();
        });
	</script>
<?php
            break;
        case ERMS_Modules::EnergyMeterData:
?>
    <!-- Last 30 Days Summary -->
    <div class="wrapper">
        <div class="def_meter_all">
            <div id="last30_summary_header">
                <span style="font-weight: bold;">Summary: Last 30 Days</span><br />
            </div>
    		<table class="tblLast30">
    		<tr>
    			<td>Average Cost Per Lay Day <b>$<?php echo $COST_30["Grand_Total_Lay_Day"] ?></b></td>
    			<td>Average kWh Per Lay Day <b><?php echo $VAL_30["kWh_day"] ?></b></td>
    			<td>Cost Per kWh <b>$<?php echo $COST_30["Grand_Total_kWh"] ?></b></td>
    		</tr>
            </table>
        </div>
    </div>
     <!-- Graph for selected period -->
   	 <div class="wrapper">
       <div class="chart_meter_left">
         <div class="consumption_box_group">
              <div id="graph_range_sel_header"><span style="font-weight: bold;">Consumption and Usage Summary</span><br /></div>
 <?php
   	 		echo
		'<table class="tblLast30">
				 <tr valign="bottom">
					<td align="center" style="background:none;">On Peak Energy:</td>
					<td align="center" style="background:none;"><font color="black">'.$VAL["Peak_kWh_Total"].' kWh</font></td>
					<td align="center" style="background:none;">Average Current:</td>
					<td align="center" style="background:none;"><font color="black">'.$VAL["1_Current_Avg"].' Amps</font></td>
				</tr>
				<tr class="odd" valign="bottom">
					<td align="center" style="background:none;">Off Peak Energy: </td>
					<td align="center" style="background:none;">'.$VAL["Off_Peak_kWh_Total"].' kWh</td>
					<td align="center" style="background:none;">Maximum Current: </td>
					<td align="center" style="background:none;">'.$VAL["1_Current_Demand"].' Amps</td>
				</tr>
				<tr valign="bottom">
					<td align="center" style="background:none;">On Peak Demand: </td>
					<td align="center" style="background:none;">'.$VAL["Peak_Demand"]." kW".'</td>
					<td align="center" style="background:none;">Time of Occurance: </td>
					<td align="center" style="background:none;">'.$VAL["Current_Demand_Time"].'</td>
				</tr>
				<tr class="odd" valign="bottom">
					<td align="center" style="background:none;">Time of Occurance: </td>
					<td align="center" style="background:none;">'.$VAL["Peak_Demand_Time"].'</td>
					<td align="center" style="background:none;">Average Voltage: </td>
					<td align="center" style="background:none;">'.$VAL["1_Voltage_Avg"]." Volts".'</td>
				</tr>
				<tr valign="bottom">
					<td align="center" style="background:none;">Off Peak Demand: </td>
					<td align="center" style="background:none;">'.$VAL["Off_Peak_Demand"]." kW".'</td>
					<td align="center" style="background:none;">Maximum Voltage: </td>
					<td align="center" style="background:none;">'.$VAL["1_Voltage_Max"]." Volts".'</td>
				</tr>
				<tr class="odd" valign="bottom">
					<td align="center" style="background:none;">Time of Occurance: </td>
					<td align="center" style="background:none;">'.$VAL["Off_Peak_Demand_Time"].'</td>
					<td align="center" style="background:none;">Minimum Voltage: </td>
					<td align="center" style="background:none;">'.$VAL["1_Voltage_Min"]." Volts".'</td>
				</tr>
				<tr valign="bottom">
					<td align="center" style="background:none;">Average Power Factor: </td>
					<td align="center" style="background:none;">'.$VAL["2_PF_Avg"]." &#37".'</td>
					<td align="center" style="background:none;">Average Reactive Power: </td>
					<td align="center" style="background:none;">'.$VAL["1_kVAR_Avg"]." kVAR".'</td>
				</tr>
				<tr class="odd" valign="bottom">
					<td align="center" style="background:none;">Maximum Power Factor: </td>
					<td align="center" style="background:none;">'.$VAL["2_PF_Max"]." &#37".'</td>
					<td align="center" style="background:none;">Maximum Reactive Power: </td>
					<td align="center" style="background:none;">'.$VAL["1_kVAR_Max"]." kVAR".'</td>
				</tr>
				<tr valign="bottom">
					<td align="center" style="background:none;">Minimum Power Factor: </td>
					<td align="center" style="background:none;">'.$VAL["2_PF_Min"]." &#37".'</td>
					<td align="center" style="background:none;">Time of Occurance: </td>
					<td align="center" style="background:none;">'.$VAL["kVAR_Max_Time"].'</td>
				</tr>
			</table>';

?>
   	 </div>
   	 </div>
         </div>
   	 	<div class="wrapper">
                  <div id="mainGraph" class="main-graph-container"></div>
       <div class="graph_meter_right">
         <!-- Graph Information and Selection -->
          <div class="info_wrapper">
           <!-- Graph Period Selection -->
            <div class="iwbox1">
                    <form id="f1" action="" method="POST">
                        <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Graph Range</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
                            <div id="radio">
                                <input type="radio" id="radio1" name="display" value="day" onclick="updateMeter()" <?php if($VAL["display"]=="day"){echo "checked";} else {echo '';} ?> /><label for="radio1">Last 24 Hours</label><br />
                                <input type="radio" id="radio2" name="display" value="week" onclick="updateMeter()" <?php if($VAL["display"]=="week"){echo "checked";} else {echo '';} ?> /><label for="radio2">Last 7 Days</label><br />
                                <input type="radio" id="radio3" name="display" value="month" onclick="updateMeter()" <?php if($VAL["display"]=="month"){echo "checked";} else {echo '';} ?> /><label for="radio3">Last 30 Days</label><br />
                                <input type="radio" id="radio4" name="display" value="anydate" onclick="s()" <?php if($VAL["display"]=="anydate"){echo "checked";} else {echo '';} ?> /><label for="radio4">Date &amp; Time Selection</label><br />
                            </div>
                            <br />
                            <div id="date_time_panel" style="display: none">
                                <label for="start_date_time">Start Date/Time</label><br />
                                <input type="text" name="start_date_time" id="start_date_time" value="" />
                                <br />
                                <label for="stop_date_time">End Date/Time</label><br />
                                <input type="text" name="stop_date_time" id="stop_date_time" value="" />
                                <input name="todo" type="hidden" value="submit" />
                                <br />
                                <button id="btnShowGraph">Show Graph</button>
                                <!--<input type="button" value="Show Graph" onclick="update()" />-->
                            </div>
                            </ul>
                        </div>
                      <br />
            	     <div id="change"></div>
                </form>
            </div>
          </div>

          <!-- Selected Data Points Summary -->
          <div class="iwbox1">
            <div id="graph_range_sel_header">
              <span style="font-weight: bold;">Graph Data Points</span><br />
            </div>
            <form id="f2" action="" method="POST">
 <?php
                    $DATA = get_data($aquisuitetablename[0]);
                    $Field = $DATA['Field'];
                    $fcount = count($Field);
                    $Title = $DATA['Title'];
                    $tcount = count($Title);

                    $utility=$VAL["utility"];

                    echo'
                    <input name="datapts" type="hidden" value="points" />
                    <select onchange="update2()" name="data1" id="data1">
                     <option value="'.$graph['units'][0]['field'].'" selected>'.$graph['units'][0]['name'].'</option>';
                            $i=0;

                            while($i<$fcount)
                            {
                                    if($Field[$i]!=$graph['units'][0]['field'])
                                    {
                                    echo '<option value="'.$Field[$i].'">'.$Title[$i].'</option>';
                                    }
                                    $i++;
                            }
                            switch($utility)
                            {
                                    case "Virginia_Dominion_Rates":
                                    if($graph['data1']!="30_Min_Reactive_kVAR")
                                    {
                                    echo '<option value="30_Min_Reactive_kVAR">Reactive Power Demand</option>';
                                    }
                                    break;
                            }

                    echo '</select>';
                    echo'
                    <select onchange="update2()" name="data2" id="data2">
                     <option value="'.$graph['units'][1]['field'].'" selected>'.$graph['units'][1]['name'].'</option>';
                            $i=0;
                            while($i<$fcount)
                            {
                                    if($Field[$i]!=$graph['units'][1]['field'])
                                    {
                                     echo '<option value="'.$Field[$i].'">'.$Title[$i].'</option>';
                                    }
                                    $i++;
                            }
                            switch($utility)
                            {
                                    case "Virginia_Dominion_Rates":
                                    if($graph['data2']!="30_Min_Reactive_kVAR")
                                    {
                                    echo '<option value="30_Min_Reactive_kVAR">Reactive Power Demand</option>';
                                    }
                                    break;
                            }
                    echo '</select>';
?>
</form>
</div>
</br>

    <div id="rawDataExport"></div>
    <script type="text/javascript">
        $(function() {
            // Y-m-d H:i:s
            $('#start_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
                controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
            $('#stop_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
            	controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
        });

        //alert ('Hello!');
        //alert ($('input:radio[name=display]:checked').val());
        var radio_value = 0;

        radio_value = $('input:radio[name=display]:checked').val();

        //alert (radio_value);
        if(radio_value === 'anydate')
        {

            //alert (radio_value);
            toggleVis('date_time_panel');
            document.getElementById('start_date_time').value = '<?php echo $VAL["date_value_start"] ?>';
            document.getElementById('stop_date_time').value = '<?php echo $VAL["date_value_end"] ?>';

        }

        $('#dialog-message').dialog
            (
                {
                    resizable: false,
                    modal: true,
                    autoOpen: false,
                    buttons:
                    {
                        Ok: function()
                        {
                            $( this ).dialog( 'close' );
                        }
                    }
                }
            );
    </script>
    <script type="text/javascript">
        $(function()
        {
            $("input[type=submit], button#btnShowGraph")
            .button()
            .click(function( event )
            {
                event.preventDefault();
            });

            $("#radio" ).buttonset();
        });
	</script>
    </div>
       </div>


<?php
            break;

        //Water Meter Data
        case ERMS_Modules::WaterMeterData:
  ?><!-- Last 30 Days Summary -->

    <div class="wrapper">
        <div class="def_all">
            <div id="last30_summary_header">
                <span style="font-weight: bold;">Summary: Last 30 Days</span><br />
            </div>
    		<table class="tblLast30">
    		<tr>
    			<td>Average Cost Per Lay Day <b>$<?php echo $COST_30["Grand_Total_Lay_Day"] ?></b></td>
    			<td>Average Gallons Per Lay Day <b><?php echo $VAL_30["kWh_day"] ?></b></td>
    			<td>Cost Per Gallon <b>$<?php echo $COST_30["Grand_Total_kWh"] ?></b></td>
    		</tr>
            </table>
        </div>
    </div>
    <!-- Graph for selected period -->
	<div class="wrapper">
             <div id="mainGraph"></div>
	     <div class="graph_right">
            <!-- Graph Information and Selection -->
        	<div class="info_wrapper">
                <!-- Graph Period Selection -->
                <div class="iwbox1">
                    <!--<form id="f1" action="showheaders.php" method="POST">-->
                    <form id="f1" action="" method="POST">
                        <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Graph Range</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
                            <div id="radio">
                                <input type="radio" id="radio1" name="display" value="day" onclick="update()" <?php if($VAL["display"]=="day"){echo "checked";} else {echo '';} ?> /><label for="radio1">Last 24 Hours</label><br />
                                <input type="radio" id="radio2" name="display" value="week" onclick="update()" <?php if($VAL["display"]=="week"){echo "checked";} else {echo '';} ?> /><label for="radio2">Last 7 Days</label><br />
                                <input type="radio" id="radio3" name="display" value="month" onclick="update()" <?php if($VAL["display"]=="month"){echo "checked";} else {echo '';} ?> /><label for="radio3">Last 30 Days</label><br />
                                <input type="radio" id="radio4" name="display" value="anydate" onclick="s()" <?php if($VAL["display"]=="anydate"){echo "checked";} else {echo '';} ?> /><label for="radio4">Date &amp; Time Selection</label><br />
                            </div>
<!--
                            <br />
                            <ul style="list-style-type: none; margin: 0; padding: 0;">
                            <li><input type="radio" id="display" name="display" value="day" onclick="update()" <?php if($VAL["display"]=="day"){echo "checked";} else {echo '';} ?> />Last 24 Hours</li>
                            <li><input type="radio" id="display" name="display" value="week" onclick="update()" <?php if($VAL["display"]=="week"){echo "checked";} else {echo '';} ?> />Last 7 Days</li>
                            <li><input type="radio" id="display" name="display" value="month" onclick="update()" <?php if($VAL["display"]=="month"){echo "checked";} else {echo '';} ?> />Last 30 Days</li>
                            <li><input type="radio" id="display" name="display" value="anydate" onclick="s()" <?php if($VAL["display"]=="anydate"){echo "checked";} else {echo '';} ?> />Date &amp; Time Selection</li>
-->
                            <br />
                            <div id="date_time_panel" style="display: none">
                                <label for="start_date_time">Start Date/Time</label><br />
                                <input type="text" name="start_date_time" id="start_date_time" value="" />
                                <br />
                                <label for="stop_date_time">End Date/Time</label><br />
                                <input type="text" name="stop_date_time" id="stop_date_time" value="" />
                                <input name="todo" type="hidden" value="submit" />
                                <br />
                                <button id="btnShowGraph">Show Graph</button>
                                <!--<input type="button" value="Show Graph" onclick="update()" />-->
                            </div>
                            </ul>
                        </div>
            			<br />
            			<div id="change"></div>
                    </form>

                </div>
                <!-- Selected Detailed Summary -->
                <div class="iwbox2">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Detailed Summary</span><br />
                    </div>
                    <div>
                        <table class="tblDetailedSummary">
                		<tr>
                			<td style="color:black;">Average Gallons</th>
                			<td><b><?php echo $VAL["Demand_avg"] ?> kW</b></th>
                		</tr>
                		<tr class="odd">
                			<td style="color:black;">Max Peak Demand</th>
                			<td><b><?php echo $VAL["Peak_Demand"] ?> kW</b></th>
                		</tr>
                		<tr>
                			<td style="color:black;">Time of Occurance</th>
                			<td><b><?php echo date('Y-m-d H:i',strtotime($VAL["Peak_Demand_Time"])) ?></b></th>
                		</tr>
                		<tr class="odd">
                			<td style="color:black;">Total Gallons</th>
                			<td><b><?php echo $VAL["kWh_Total"] ?> kWh</b></th>
                		</tr>
                		<tr>
                			<td style="color:black; border-bottom: 0px;">Gallons Per Lay Day</th>
                			<td style="border-bottom: 0px;"><b><?php echo $VAL["kWh_day"] ?> kWh/day</b></th>
                		</tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="dialog-message" title="Select a date range">
        <p>
            <span class="ui-icon ui-icon-circle-check" style="float: left; margin: 0 7px 50px 0;"></span>
            Please select a valid date range.
        </p>
    </div>
    <script type="text/javascript">
        $(function() {
            // Y-m-d H:i:s
            $('#start_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
                controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
            $('#stop_date_time').datetimepicker({
            	maxDate: new Date(),
                dateFormat: 'yy-mm-dd',
            	controlType: 'select',
            	timeFormat: 'HH:mm:ss'
            });
        });

        //alert ('Hello!');
        //alert ($('input:radio[name=display]:checked').val());
        var radio_value = 0;

        radio_value = $('input:radio[name=display]:checked').val();

        //alert (radio_value);
        if(radio_value === 'anydate')
        {

            //alert (radio_value);
            toggleVis('date_time_panel');
            document.getElementById('start_date_time').value = '<?php echo $VAL["date_value_start"] ?>';
            document.getElementById('stop_date_time').value = '<?php echo $VAL["date_value_end"] ?>';

        }

        $('#dialog-message').dialog
            (
                {
                    resizable: false,
                    modal: true,
                    autoOpen: false,
                    buttons:
                    {
                        Ok: function()
                        {
                            $( this ).dialog( 'close' );
                        }
                    }
                }
            );
    </script>
    <script type="text/javascript">
        $(function()
        {
            $("input[type=submit], button")
            .button()

            $("input[type=submit], button#btnShowGraph")
            .click(function( event ) {
                event.preventDefault();
            });

            $("#radio" ).buttonset();
        });
	</script>

 <?php
            break;

        case ERMS_Modules::MonthlyReports:
           if (!$annual_report)
   {

?>
    <!-- Performance Metrics -->
    <div class="wrapper">
        <div class="def_all">
            <div id="last30_summary_header">
                <span style="font-weight: bold;">Performance Metrics</span><br />
            </div>
    		<table class="tblLast30">
    		<tr>
    			<td>kWh per day <b><?php echo $performance["avg_kwH"] ?></b></td>
    			<td>Cost per day <b>$<?php echo $performance["avg_cost"] ?></b></td>
    			<td>Cost per kWh <b>$<?php echo $COST["Grand_Total_kWh"] ?></b></td>
    		</tr>
            </table>
        </div>
    </div>
    <!-- Chart for selected period -->
	<div class="wrapper">
        <div class="chart_left">
<?php

	echo '
        <div class="consumption_box">
            <div id="graph_range_sel_header">
                <span style="font-weight: bold;">Consumption and Usage Detailed Summary</span><br/>
            </div>
            <div>
    		<table id="TblConsumeDetailSummary" class="tblDetailedSummaryRpt">
		<tr>
			<td>Meter End of the Month Reading</font></td>
			<td  style="background:none;"><font color="black">'.$shipData["EndOfMonthReading"].'</font></td>
		</tr>
    		<tr>
    			<td>Total kWh Consumed</font></td>
    			<td style="background:none;"><font color="black">'.$shipData["TotalkWhConsumed"].' kWh</font></td>
    		</tr>
     		<tr>
    			<td>Maximum On Peak Demand</font></td>
    			<td style="background:none;"><font color="black">'.$shipData["MaxOnPeakDemand"].' kW</font></td>
    		</tr>';

 		if ($utility=="SCE&G_Rates")
		{
		  echo
		  '<tr>
			<td>On Peak Billed 15 Minute Demand</font></td>
			<td style="background:none;"><font color="black">'.$shipData["OnPeakBilledDemand"].' kW</font></td>
		  </tr>';
                 }

    		echo '<tr>
    			<td>Time of Occurrence</font></td>
    			<td style="background:none;"><font color="black">'.makeDate($shipData["TimeOfMaxOnPeakDemand"]).'</font></td>
    		</tr>
    		<tr>
    			<td>Maximum Off Peak Demand</font></td>
    			<td style="background:none;"><font color="black">'.$shipData["MaxOffPeakDemand"].' kW</font></td>
    		</tr>';
              if ($utility=="SCE&G_Rates")
	      {
		echo
		  '<tr>
		     	<td>Off Peak Billed 15 Minute Demand</font></td>
		        <td style="background:none;"><font color="black">'.$shipData["OffPeakBilledDemand"].' kW</font></td>
		    </tr>';
		}

                echo
    		'<tr>
    			<td>Time of Occurrence</font></td>
    			<td style="background:none;"><font color="black">'.makeDate($shipData["TimeOfMaxOffPeakDemand"]).'</font></td>
    		</tr>';

             if ($utility=="Virginia_Dominion_Rates")
                {
		  echo
		   '<tr>
		 	<td>Maximum 30 Minute Reactive Demand</font></td>
			<td style="background:none;"><font color="black">'.$shipData["OnPeakBilledDemand"].' kVAR</font></td>
		   </tr>';
		}

                echo
    		'<tr>
    			<td>Lay Days</font></td>
    			<td style="background:none;"><font color="black">'.number_format($shipData["LayDays"],2).' Days</font></td>
    		</tr>
    		<tr>
    			<td>On Peak kWh</font></td>
    			<td style="background:none;"><font color="black">'.$shipData["OnPeakkWh"].' kWh</font></td>
    		</tr>
    		<tr>
    			<td>Off Peak kWh</font></td>
    			<td style="background:none;"><font color="black">'.$shipData["OffPeakkWh"].' kWh</font></td>
    		</tr>';

			echo
		'<tr>
			<td>Average Power</font></td>
			<td style="background:none;"><font color="black">'.$shipData["AvgPower"].' kW</font></td>
		</tr>';

			if ($utility=="SCE&G_Rates")
			{
			echo
                    '<tr>
			<td>Billed Power Factor</font></td>
			<td style="background:none;"><font color="black">'.$shipData["BilledPowerFactor"].'</font></td>
                    </tr>';
			}
			echo
		'<tr>
			<td>Average Power Factor</font></td>
			<td style="background:none;"><font color="black">'.$shipData["AvgPowerFactor"].'</font></td>
		</tr>
		<tr>
			<td>Lowest Power Factor</font></td>
			<td style="background:none;"><font color="black">'.$shipData["LowestPowerFactor"].'</font></td>
		</tr>
		<tr>
			<td>Highest Power Factor</font></td>
			<td style="background:none;"><font color="black">'.$shipData["HighestPowerFactor"].'</font></td>
		</tr>
                <tr>
    		      <td>Total CO<sub>2</sub></font></td>
    		      <td style="background:none;"><font color="black">'.$shipData["TotalCO2"].' MT</font></td>
    		</tr>
            </table>
        </div>
	';

?>
            </div>
        </div>



        <div class="chart_right">

            <!-- Graph Information and Selection -->
        	<div class="info_wrapper">
                <!-- Graph Period Selection -->
                <div class="iwbox1">
                        <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Report Period</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">

<?php

    $VAL["report_year"] = str_replace(',','',$VAL["report_year"]);
    echo '
    					<form id="f" action="" method="POST">
    					<input name="report" type="hidden" value="report" />
    					<label>Select Report Month</label>
    					<select name="month" id="month">
    					<option value="-01-01 00:00:00" ';if ($VAL["report_month"]=="January"){echo "selected";} echo '>January</option>
    					<option value="-02-01 00:00:00" ';if ($VAL["report_month"]=="February"){echo "selected";} echo '>February</option>
    					<option value="-03-01 00:00:00" ';if ($VAL["report_month"]=="March"){echo "selected";} echo '>March</option>
    					<option value="-04-01 00:00:00" ';if ($VAL["report_month"]=="April"){echo "selected";} echo '>April</option>
    					<option value="-05-01 00:00:00" ';if ($VAL["report_month"]=="May"){echo "selected";} echo '>May</option>
    					<option value="-06-01 00:00:00" ';if ($VAL["report_month"]=="June"){echo "selected";} echo '>June</option>
    					<option value="-07-01 00:00:00" ';if ($VAL["report_month"]=="July"){echo "selected";} echo '>July</option>
    					<option value="-08-01 00:00:00" ';if ($VAL["report_month"]=="August"){echo "selected";} echo '>August</option>
    					<option value="-09-01 00:00:00" ';if ($VAL["report_month"]=="September"){echo "selected";} echo '>September</option>
    					<option value="-10-01 00:00:00" ';if ($VAL["report_month"]=="October"){echo "selected";} echo '>October</option>
    					<option value="-11-01 00:00:00" ';if ($VAL["report_month"]=="November"){echo "selected";} echo '>November</option>
    					<option value="-12-01 00:00:00" ';if ($VAL["report_month"]=="December"){echo "selected";} echo '>December</option>
    					<option value="month" ';if ($VAL["report_month"]=="Last 30 Days"){echo "selected";} echo '>Last 30 Days</option>
    					</select>
    					<label>Select Report Year</label><br />
    					<select name="year" id="year">
                        <br />
                        <option value="2024" ';if ($VAL["report_year"]=="2024"){echo "selected";} echo '>2024</option>
                        <option value="2023" ';if ($VAL["report_year"]=="2023"){echo "selected";} echo '>2023</option>
                        <option value="2022" ';if ($VAL["report_year"]=="2022"){echo "selected";} echo '>2022</option>
                        <option value="2021" ';if ($VAL["report_year"]=="2021"){echo "selected";} echo '>2021</option>
                        <option value="2020" ';if ($VAL["report_year"]=="2020"){echo "selected";} echo '>2020</option>
                        <option value="2019" ';if ($VAL["report_year"]=="2019"){echo "selected";} echo '>2019</option>
                        <option value="2018" ';if ($VAL["report_year"]=="2018"){echo "selected";} echo '>2018</option>
                        <option value="2017" ';if ($VAL["report_year"]=="2017"){echo "selected";} echo '>2017</option>
    				    <option value="2016" ';if ($VAL["report_year"]=="2016"){echo "selected";} echo '>2016</option>
                        <option value="2015" ';if ($VAL["report_year"]=="2015"){echo "selected";} echo '>2015</option>
    				    <option value="2014" ';if ($VAL["report_year"]=="2014"){echo "selected";} echo '>2014</option>
                        <option value="2013" ';if ($VAL["report_year"]=="2013"){echo "selected";} echo '>2013</option>
    					<option value="2012" ';if ($VAL["report_year"]=="2012"){echo "selected";} echo '>2012</option>
    					<option value="2011" ';if ($VAL["report_year"]=="2011"){echo "selected";} echo '>2011</option>
    					<option value="2010" ';if ($VAL["report_year"]=="2010"){echo "selected";} echo '>2010</option>
    					</select><br />
    					<input type="submit" value="Show Report" />
    					</form>
                        ';
?>
                    </div>
                </div>

                <!-- Selected Detailed Summary -->
                <div class="iwbox2">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Energy Cost Overview</span><br />
                    </div>
                    <div>
                        <table id="TblEnergyCostOverview" class="tblDetailedSummary">
                		<tr>
                			<td style="color:black;">Total Energy Charges</td>
                			<td><b>$<?php echo $COST["Total_kWh_Cost"] ?></b></td>
                		</tr>
                		<tr class="odd">
                			<td style="color:black;">Total Demand Charges</td>
                			<td><b>$<?php echo $COST["Total_kW_Cost"] ?></b></td>
                		</tr>
                		<tr>
                			<td style="color:black;">Total Taxes &amp; Fees</td>
                			<td><b>$<?php echo $COST["Taxes_Add_Fees"] ?></b></td>
                		</tr>
                		<tr class="odd">
                			<td style="color:black; border-bottom: 0px;">Total Cost</td>
                			<td><b>$<?php echo $COST["Grand_Total_Cost"] ?></b></td>
                		</tr>
                        </table>
                    </div>
                </div>
                <!-- Pie Chart -->
                <div class="iwbox2">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Selected Chart</span><br />
                    </div>
                    <div>
                        <a title="Click image to view larger" id="monthly_report_image" href="<?php echo $graph['graph'] ?>"><img src="<?php echo $graph['graph'] ?>" width="<?php echo ($graph['width']/3.5) ?>" height="<?php echo ($graph['height']/3.5) ?>" border="0"></a>
                    </div>
                  </div>
                 <div class="printRptBtn"><button onClick="downloadCSV();">Export</button> </div>
               </div>
          </div>
    </div>

<!-- Energy Cost summary box -->

 	<div class="wrapper">
        <div class="chart_left">
<?php
		echo
		'
       <div class="consumption_box">
            <div id="graph_range_sel_header">
                <span style="font-weight: bold;">Energy Cost Detailed Summary</span><br />
            </div>

    		<table id="TblEnergyCostDetail" class="tblDetailedSummaryRpt">

				<tr>
					<td>On Peak Energy Charges</font></td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["OnPeakEnergyCharges"].'</font></td>
				</tr>
				<tr class="odd">
					<td>Off Peak Energy Charges</font></td>
         				<td  style="background:none;"><font color="black">'.'$'.$shipData["OffPeakEnergyCharges"].'</font></td>
				</tr>
				<tr>
					<td>Other Energy Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["OtherEnergyCharges"].'</font></td>
				</tr>
				<tr class="odd">
					<td>Total Energy Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["TotalEnergyCharges"].'</font></td>
				</tr>
				<tr>
					<td>On Peak Demand Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["OnPeakDemandCharges"].'</font></td>
				</tr>
				<tr class="odd">
					<td>Off Peak Demand Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["OffPeakDemandCharges"].'</font></td>
				</tr>
				<tr>
					<td>Other Demand Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["OtherDemandCharges"].'</font></td>
				</tr>
				<tr class="odd">
					<td>Total Demand Charges</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["TotalDemandCharges"].'</font></td>
				</tr>
				<tr>
					<td>Total Estimated Bill</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["TotalEstimatedBill"].'</font></td>
				</tr>
				<tr class="odd">
					<td>Full Burden Pure Demand Rate($/kW)</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["FullBurdenPureDemandRate"].'/kW</font></td>
				</tr>
				<tr>
					<td>Full Burden Pure Energy Rate($/kWh)</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["FullBurdenPureEnergyRate"].'/kWh</font></td>
				</tr>
				<tr class="odd">
					<td>Full Burden Rate of Shorepower($/kWh)</td>
					<td  style="background:none;"><font color="black">'.'$'.$shipData["FullBurdenShorepowerRate"].'/kWh</font></td>
				</tr>
<!-- end detailed report info -->
</table>
</div>
';
?>
      </div>
    </div>

<!-- Utility Rate box -->

 <div class="wrapper">
        <div class="chart_left">
<?php
	if ($utility=="SCE&G_Rates")
	{
           	echo '
                   <div class="consumption_box">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">South Carolina Electric & Gas Company Rate 24</span><br />
                     </div>

                    <table class="tblDetailedSummaryRpt">
                    <tr>
                            <td>Basic Customer</td>
                            <td  style="background:none;"><font color="black">$'.$COST["U_Customer_Charge"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Summer On-Peak Billing Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Summer_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Non-Summer On-Peak Billing Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Non_Summer_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Off-Peak Billing Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Off_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Summer On-Peak Energy. June-September</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Summer_Peak_kWh"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Non-Summer On-Peak Energy. October-May</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Non_Summer_Peak_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Off-Peak Energy</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Off_Peak_kWh_rate"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Franchise Fee</th>
                            <td style="background:none;"><font color="black">'.$COST["U_Franchise_Fee"]*100.0.'%</font></td>
                    </tr>
        <!-- end energy rate schedule report info -->
        </table>
      </div>
    ';
   }
    if($utility == "Virginia_Dominion_Rates") {
           	echo '
                   <div class="consumption_box">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Virginia Dominion Rate GS 3</span><br />
                     </div>

                    <table class="tblDetailedSummaryRpt">
                    <tr>
                            <td>Basic Customer</td>
                            <td  style="background:none;"><font color="black">$'.$COST["U_Customer_Charge"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>On-Peak Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Peak_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Off-Peak Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Off_Peak_kW"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Peak Energy</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Peak_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Off-Peak Energy</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Off_Peak_kWh"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Reactive Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Demand_rkVA"].'</font></td>
                    </tr>
                    <tr>
                            <td>Distribution Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Distribution_Demand"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>ESS Adjustment</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_ESS_Adjustment_Charge"].'</font></td>
                    </tr>
                    <tr>
                            <td>Fuel Charge (per kWh)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Fuel_Charge"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Sales Charge (per kWh)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Sales_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Rider R Peak Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_R_Peak_kW"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Rider S Peak Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_S_Peak_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Rider T Peak Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_T_Peak_kW"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Rider R Peak Demand Credit</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_R_Credit"].'</font></td>
                    </tr>
                    <tr>
                            <td>Rider S Peak Demand Credit</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_S_Credit"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Rider T Peak Demand Credit</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_T_Credit"].'</font></td>
                    </tr>
                    <tr>
                            <td>Tax Rate Tier 1 (2,500 kWh and below)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Tax_Rate_1"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Tax Rate Tier 2 (between 2,500 and 50,000 kWh)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Tax_Rate_2"].'</font></td>
                    </tr>
                    <tr>
                            <td>Tax Rate Tier 3 (greater than 50,000 kWh)</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Tax_Rate_3"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Utility Tax</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Utility_tax"].'</font></td>
                    </tr>
        <!-- end energy rate schedule report info -->
        </table>
      </div>
    ';

    }
	if ($utility=="Virginia_Electric_and_Power_Co")
	{
           	echo '
                   <div class="consumption_box">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Virginia Electric and Power Company Schedule 10</span><br />
                     </div>

                    <table style="border-collapse: collapse" class="tblDetailedSummaryRpt">
                    <tr>
                            <td colspan="3">Basic Customer</td>
                            <td colspan="3" style="background:none;"><font color="black">$'.$COST["U_Customer_Charge"].'</font></td>
                    </tr>
                    <tr>
                            <td colspan="3">Peak Demand Charge (500 kW minimum)</td>
                            <td colspan="3"  style="background:none;"><font color="black">$'.$COST["U_Peak_kW_Demand_1_Cost"].'</font></td>
                    </tr>
                    <tr style="border-top: 1px solid black; border-bottom: 1px solid black">
                      <td style="width:33%" colspan="2">Month</td>
                      <td style="width:33%" colspan="2">On Peak Energy</td>
                      <td style="width:33%" colspan="2">Off Peak Energy</td>
                    </tr>';
                for($i = 1; $i < count($COST["U_Monthly_Rates"]) + 1; $i++) {
                  $month_name = date('F', mktime(0, 0, 0, $i, 10));
                  $peak_rate = number_format($COST["U_Monthly_Rates"][$i]["Peak_kWh"], 3);
                  $off_peak_rate = number_format($COST["U_Monthly_Rates"][$i]["Off_Peak_kWh"], 3);
                  $row_class = $i % 2 == 0 ? "" : "odd";

                  echo '<tr class="'.$row_class.'">
                        <td style="width:33%" colspan="2" style="background:none; color:black;">'.$month_name.'</td>
                        <td style="width:33%" colspan="2" style="background:none; color:black;">$'.$peak_rate.'</td>
                        <td style="width:33%" colspan="2" style="background:none; color:black;">$'.$off_peak_rate.'</td>
                      </tr>';
                }
        echo '</table>
      </div>';
   }

   if($utility=="Entergy_NO_Rates") {
           	echo '
                   <div class="consumption_box">
                    <div id="graph_range_sel_header">
                        <span style="font-weight: bold;">Entergy New Orleans Rate LE-HLF</span><br />
                     </div>

                    <table class="tblDetailedSummaryRpt">
                    <tr>
                            <td>Demand 0 to 50 kW</td>
                            <td  style="background:none;"><font color="black">$'.$COST["U_Demand_Rate_1"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Demand 50 to 100 kW</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Demand_Rate_2"].'</font></td>
                    </tr>
                    <tr>
                            <td>Demand 100 to 200 kW</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Demand_Rate_3"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Demand Additional kW</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Demand_Rate_4"].'</font></td>
                    </tr>
                    <tr>
                            <td>Energy first 5,000 kWh</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Energy_Rate_1"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Energy 5,000 to 10,000 kWh</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Energy_Rate_2"].'</font></td>
                    </tr>
                    <tr>
                            <td>Energy 10,000 to 15,000 kWh</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Energy_Rate_3"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Energy Additional 400 kWh/kW</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Energy_Rate_4"].'</font></td>
                    </tr>
                    <tr>
                            <td>Energy All Additional kWh</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Energy_Rate_5"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Rider Fuel Cost per kWh</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_Fuel_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Rider Capacity Aquisition</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_Capacity_kWh"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Rider EAC</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Rider_EAC_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Street use Franchise Fee</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Street_Use_Franchise_Fee"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Storm Securitization Fee</td>
                            <td style="background:none;"><font color="black">$'.$COST["U_Storm_Securitization_Fee"].'</font></td>
                    </tr>
                    <tr>
                            <td>Formula Rate Plan %</td>
                            <td style="background:none;"><font color="black">'.$COST["U_Formula_Rate_Plan_Percentage"].'%</font></td>
                    </tr>
                    <tr class="odd">
                            <td>MISO Recovery %</td>
                            <td style="background:none;"><font color="black">'.$COST["U_MISO_Recovery_Percentage"].'%</font></td>
                    </tr>
        <!-- end energy rate schedule report info -->
        </table>
      </div>
    ';

   }

?>
       </div>
    </div>

<?php
    }//end single month report
    else
    { //annual report
?>


      <!-- Chart for Monthly Performance Metrics -->
	<div class="annualwrapper">
        <div class="annualchart_left">
 <?php
        	echo
		'
        <div class="annual_consumption_box">
            <div id="graph_range_sel_header"> <span style="font-weight: bold;">Annual Performance Metrics Summary</span><br />  </div>
            <div>
    		<table id="annualTblConsumeDetailSummary" class="annualtblDetailedSummaryRpt">';

    	    echo '<tr id="monthHeader" class="odd" span style="text-align: center;"><td> </td>';
    	    for ($m=0;$m<$max_month;$m++)
    	    {
    	     	$m_str = date('F', mktime(0, 0, 0, $m+1, 10));
    		    echo '<td>'.$m_str.'</font></td>';
    		}
    		echo '<td>'.Average.'</font></td>';




    		echo '<tr><td>kWh per day(kWh)</font></td>';
    	    for ($m=0;$m<$max_month;$m++)
    	        echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["kWh_day"].'</font></td>';
            echo '<td style="background:none;"><font color="black">'.$monthly_average["kWh_day"].'</font></td>';


    	   echo'</tr><tr class="odd"><td>Cost per day</font></td>';

    	   for ($m=0;$m<$max_month;$m++)
           {
    	      echo '<td style="background:none;"><font color="black">'.$COST_YEAR[$m]["Grand_Total_Lay_Day"].'</font></td>';
           }
           echo '<td style="background:none;"><font color="black">'.$monthly_average["Grand_Total_Lay_Day"].'</font></td>';

    	   echo'</tr><tr><td>Cost per kWh </font></td>';

    	   for ($m=0;$m<$max_month;$m++)
           {
    	      echo '<td style="background:none;"><font color="black">'.$COST_YEAR[$m]["Grand_Total_kWh"].'</font></td>';
           }
    	   echo '<td style="background:none;"><font color="black">'.$monthly_average["Grand_Total_kWh"].'</font></td>';

    		echo '</tr></table>';
			echo '</div>';
?>
     </div>
   </div>


       <div class="chart_right">

            <!-- Graph Information and Selection -->
        	<div class="info_wrapper">
                <!-- Graph Period Selection -->
                <div class="iwbox1">
                        <div id="graph_range_sel_header"><span style="font-weight: bold;">Select Report Period</span><br /></div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
<?php

    $VAL["report_year"] = str_replace(',','',$VAL["report_year"]);
    echo '
    					<form id="f" action="" method="POST">
    					<input name="report" type="hidden" value="report" />
    					<label>Select Report Month</label>
    					<select name="month" id="month">
    					<option value="-01-01 00:00:00" ';if ($VAL["report_month"]=="January"){echo "selected";} echo '>January</option>
    					<option value="-02-01 00:00:00" ';if ($VAL["report_month"]=="February"){echo "selected";} echo '>February</option>
    					<option value="-03-01 00:00:00" ';if ($VAL["report_month"]=="March"){echo "selected";} echo '>March</option>
    					<option value="-04-01 00:00:00" ';if ($VAL["report_month"]=="April"){echo "selected";} echo '>April</option>
    					<option value="-05-01 00:00:00" ';if ($VAL["report_month"]=="May"){echo "selected";} echo '>May</option>
    					<option value="-06-01 00:00:00" ';if ($VAL["report_month"]=="June"){echo "selected";} echo '>June</option>
    					<option value="-07-01 00:00:00" ';if ($VAL["report_month"]=="July"){echo "selected";} echo '>July</option>
    					<option value="-08-01 00:00:00" ';if ($VAL["report_month"]=="August"){echo "selected";} echo '>August</option>
    					<option value="-09-01 00:00:00" ';if ($VAL["report_month"]=="September"){echo "selected";} echo '>September</option>
    					<option value="-10-01 00:00:00" ';if ($VAL["report_month"]=="October"){echo "selected";} echo '>October</option>
    					<option value="-11-01 00:00:00" ';if ($VAL["report_month"]=="November"){echo "selected";} echo '>November</option>
    					<option value="-12-01 00:00:00" ';if ($VAL["report_month"]=="December"){echo "selected";} echo '>December</option>
    					<option value="month" ';if ($VAL["report_month"]=="Last 30 Days"){echo "selected";} echo '>Last 30 Days</option>
    				    <option value="annual" ';if ($VAL["report_month"]=="Annual"){echo "selected";} echo '>Annual</option>
    					</select>
    					<label>Select Report Year</label><br />
    					<select name="year" id="year">
                        <br />
                       	<option value="2017" ';if ($VAL["report_year"]=="2017"){echo "selected";} echo '>2017</option>
    				  	<option value="2016" ';if ($VAL["report_year"]=="2016"){echo "selected";} echo '>2016</option>
                    	<option value="2015" ';if ($VAL["report_year"]=="2015"){echo "selected";} echo '>2015</option>
    				  	<option value="2014" ';if ($VAL["report_year"]=="2014"){echo "selected";} echo '>2014</option>
                        <option value="2013" ';if ($VAL["report_year"]=="2013"){echo "selected";} echo '>2013</option>
    					<option value="2012" ';if ($VAL["report_year"]=="2012"){echo "selected";} echo '>2012</option>
    					<option value="2011" ';if ($VAL["report_year"]=="2011"){echo "selected";} echo '>2011</option>
    					<option value="2010" ';if ($VAL["report_year"]=="2010"){echo "selected";} echo '>2010</option>
    					</select><br />
    					<input type="submit" value="Show Report" />
    					</form>
                        ';
?>
                    </div>
                  </div>
                </div>
              </div>
            </div>





       <!-- Chart for selected period -->
	<div class="annualwrapper">
        <div class="annualchart_left">
 <?php
        	echo
		'
        <div class="annual_consumption_box">
            <div id="graph_range_sel_header"> <span style="font-weight: bold;">Annual Consumption and Usage Detailed Summary</span><br />  </div>
            <div>
    		<table id="annualTblConsumeUsageSummary" class="annualtblDetailedSummaryRpt">';

    	    echo '<tr id=annualMonthUsageSum class="odd" span style="text-align: center;"><td> </td>';
    	    for ($m=0;$m<$max_month;$m++)
    	    {
    	     	$m_str = date('F', mktime(0, 0, 0, $m+1, 10));
    		    echo '<td>'.$m_str.'</font></td>';
    		}
    	    echo '<td>'.Average.'</font></td>';
    	    echo '<td>'.Total.'</font></td>';
    	    echo '</tr>';


    		echo '<tr><td>Total kWh Consumed(kWh)</font></td>';
    	    for ($m=0;$m<$max_month;$m++)
    	    	echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["kWh_Total"].'</font></td>';
     	    echo '<td style="background:none;"><font color="black">'.$monthly_average["kWh_Total"].'</font></td>';
     	    echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["kWh_Total"].'</font></td>';




    		echo'</tr><tr><td>Maximum On Peak Demand(kW)</font></td>';

    	   for ($m=0;$m<$max_month;$m++)
    			echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Peak_Demand"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'.$monthly_average["Peak_Demand"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

            if ($utility=="SCE&G_Rates")
            {
	        echo 	'<tr class="odd"><td>On Peak Billed 15 Minute Demand(kW)</font></td>';

	        for ($m=0;$m<$max_month;$m++)
		    echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Peak_Billed_Demand"].'</font></td>';
	        echo '<td style="background:none;"><font color="black">'.$monthly_average["Peak_Billed_Demand"].'</font></td>';
      	        echo '<td style="background:none;"><font color="black">'." ".'</font></td>';
            }

    		echo '</tr>	<tr class="odd"><td>Time of Occurrence</font></td>';

    		for ($m=0;$m<$max_month;$m++)
    		{
    		    $strDate = makeDate($VAL_YEAR[$m]["Peak_Demand_Time"]);
    		    if ($strDate <  "1970-01-01 00:00:00")
    				echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';
    			else
    				echo '<td style="background:none;"><font color="black">'.makeDate($VAL_YEAR[$m]["Peak_Demand_Time"]).'</font></td>';
    		}
    		echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';
    		echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';


    		echo '</tr><tr><td>Maximum Off Peak Demand(kW)</font></td>';

    		for ($m=0;$m<$max_month;$m++)
    			echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Off_Peak_Demand"].'</font></td>';
     	    echo '<td style="background:none;"><font color="black">'.$monthly_average["Off_Peak_Demand"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

            if ($utility=="SCE&G_Rates")
            {
               echo '</tr><tr><td>Off Peak Billed 15 Minute Demand(kW)</font></td>';
	       for ($m=0;$m<$max_month;$m++)
	           echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Off_Peak_Billed_Demand"].'</font></td>';
	       echo '<td style="background:none;"><font color="black">'.$monthly_average["Off_Peak_Billed_Demand"].'</font></td>';
       	       echo '<td style="background:none;"><font color="black">'." ".'</font></td>';
            }


			 echo	'</tr>';

    		echo '</tr><tr class="odd"><td>Time of Occurrence</font></td>';

    		for ($m=0;$m<$max_month;$m++)
    		{
    		     $strDate = makeDate($VAL_YEAR[$m]["Off_Peak_Demand_Time"]);
    		    if ($strDate <  "1970-01-01 00:00:00")
    				echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';
    			else
    		        echo '<td style="background:none;"><font color="black">'.makeDate($VAL_YEAR[$m]["Off_Peak_Demand_Time"]).'</font></td>';
    		 }
    		echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';
    		echo '<td style="background:none;"><font color="black">'.'  '.'</font></td>';


	       if ($utility=="Virginia_Dominion_Rates")
                {
          	    echo '<tr><td>Maximum 30 Minute Reactive Demand(kVAR)</font></td>';
                    for ($m=0;$m<$max_month;$m++)
   		        echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["kVAR_Demand"].'</font></td>';

   	            echo '</tr>';
    		}


    		echo '</tr><tr><td>Lay Days</font></td>';

    		for ($m=0;$m<$max_month;$m++)
    			echo '<td style="background:none;"><font color="black">'.number_format($VAL_YEAR[$m]["Lay_Days"],2).'</font></td>';
    		echo '<td style="background:none;"><font color="black">'.$monthly_average["Lay_Days"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Lay_Days"].'</font></td>';


     		echo '</tr><tr class="odd"><td>On Peak kWh</font></td>';

    		for ($m=0;$m<$max_month;$m++)
        		echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Peak_kWh_Total"].'</font></td>';
          	echo '<td style="background:none;"><font color="black">'.$monthly_average["Peak_kWh_Total"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'." ".'</font></td>';


    	   	echo '</tr><tr><td>Off Peak kWh</font></td>';

    		for ($m=0;$m<$max_month;$m++)
    			echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Off_Peak_kWh_Total"].'</font></td>';
    		echo '<td style="background:none;"><font color="black">'.$monthly_average["Off_Peak_kWh_Total"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

    		echo '</tr>';


			echo '<tr class="odd"><td>Average Power(kW)</font></td>';

			for ($m=0;$m<$max_month;$m++)
		        echo'<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["Demand_avg"].'</font></td>';
		    echo '<td style="background:none;"><font color="black">'.$monthly_average["Demand_avg"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';



			 echo '</tr>';

			if ($utility=="SCE&G_Rates")
			{
			echo '<tr><td>Billed Power Factor</font></td>';

			for ($m=0;$m<$max_month;$m++)
			    echo '<td style="background:none;"><font color="black">'.number_format(($VAL_YEAR[$m]["2_PF_Demand"]/100), 2, '.', '').'</font></td>';
		    echo '<td style="background:none;"><font color="black">'.number_format(($monthly_average["2_PF_Demand"]/100), 2, '.', '').'</font></td>';
       	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

			echo '</tr>';
			}
			echo '<tr class="odd"><td>Average Power Factor</font></td>';

			for ($m=0;$m<$max_month;$m++)
			    echo '<td style="background:none;"><font color="black">'.number_format(($VAL_YEAR[$m]["2_PF_Avg"]/100), 2, '.', '').'</font></td>';
			echo '<td style="background:none;"><font color="black">'.number_format(($monthly_average["2_PF_Avg"]/100), 2, '.', '').'</font></td>';
       	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

			echo '</tr><tr><td>Lowest Power Factor</font></td>';

			for ($m=0;$m<$max_month;$m++)
			    echo '<td style="background:none;"><font color="black">'.number_format(($VAL_YEAR[$m]["2_PF_Min"]/100), 2, '.', '').'</font></td>';
			echo '<td style="background:none;"><font color="black">'.number_format(($monthly_average["2_PF_Min"]/100), 2, '.', '').'</font></td>';
       	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

			echo '</tr><tr class="odd"><td>Highest Power Factor</font></td>';

		    for ($m=0;$m<$max_month;$m++)
			    echo '<td style="background:none;"><font color="black">'.number_format(($VAL_YEAR[$m]["2_PF_Max"]/100), 2, '.', '').'</font></td>';
			echo '<td style="background:none;"><font color="black">'.number_format(($monthly_average["2_PF_Max"]/100), 2, '.', '').'</font></td>';
       	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

		    		echo'</tr><tr class="odd"><td>Total CO<sub>2</sub>(MT)</sub></font></td>';

    	   for ($m=0;$m<$max_month;$m++)
           echo '<td style="background:none;"><font color="black">'.$VAL_YEAR[$m]["2_Total_CO2"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'.$monthly_average["2_Total_CO2"].'</font></td>';
    	    echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["2_Total_CO2"].'</font></td>';

			echo '</tr></table>';
			echo '</div>';
?>
     </div>
      </div>
     <div class="annualprintRptBtn"><button onClick="write_to_excel('annualTblConsumeDetailSummary');">Export</button> </div>
   </div>



              <!-- Energy Cost summary box -->

 	<div class="annualwrapper">
        <div class="annualchart_left">
<?php
		echo '<div class="annual_consumption_box">
            <div id="graph_range_sel_header"><span style="font-weight: bold;">Annual Energy Cost Detailed Summary</span><br /></div>

    		<table id="annualTblEnergyCostDetail" class="annualtblDetailedSummaryRpt">';

    		    echo '<tr class="odd" span style="text-align: center;"><td> </td>';
    	    for ($m=0;$m<$max_month;$m++)
    	    {
    	     	$m_str = date('F', mktime(0, 0, 0, $m+1, 10));
    		    echo '<td>'.$m_str.'</font></td>';
    		}
    	    echo '<td>'.Average.'</font></td>';
    	    echo '<td>'.Total.'</font></td>';
    	    echo '</tr>';

				echo '<td>On Peak Energy Charges</font></td>';

			   for ($m=0;$m<$max_month;$m++)
			   		echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Peak_kWh_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Peak_kWh_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Peak_kWh_Cost"].'</font></td>';

				echo '</tr>	<tr class="odd"><td>Off Peak Energy Charges</font></td>';
			    for ($m=0;$m<$max_month;$m++)
         		    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Off_Peak_kWh_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Off_Peak_kWh_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Off_Peak_kWh_Cost"].'</font></td>';

				echo '</tr><tr><td>Other Energy Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Other_Energy_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Other_Energy_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Other_Energy_Cost"].'</font></td>';

				echo '</tr><tr class="odd"><td>Total Energy Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Total_kWh_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Total_kWh_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Total_kWh_Cost"].'</font></td>';

				echo '</tr><tr><td>On Peak Demand Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Peak_kW_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Peak_kW_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Peak_kW_Cost"].'</font></td>';

				echo '</tr><tr class="odd"><td>Off Peak Demand Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Off_Peak_kW_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Off_Peak_kW_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Off_Peak_kW_Cost"].'</font></td>';

				echo '</tr><tr><td>Other Demand Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Other_Demand_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Other_Demand_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Other_Demand_Cost"].'</font></td>';

				echo '</tr><tr class="odd"><td>Total Demand Charges</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Total_kW_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Total_kW_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Total_kW_Cost"].'</font></td>';


				echo '</tr><tr><td>Total Estimated Bill</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Grand_Total_Cost"].'</font></td>';
			   echo '<td style="background:none;"><font color="black">'.$monthly_average["Grand_Total_Cost"].'</font></td>';
    	       echo '<td style="background:none;"><font color="black">'.$monthly_running_totals["Grand_Total_Cost"].'</font></td>';

				echo '</tr><tr class="odd"><td>Full Burden Pure Demand Rate($/kW)</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Demand_Total_kW"].'</font></td>';
			    echo '<td style="background:none;"><font color="black">'.$monthly_average["Demand_Total_kW"].'</font></td>';
		 	    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';

				echo '</tr><tr><td>Full Burden Pure Energy Rate($/kWh)</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Energy_Total_kWh"].'</font></td>';
			     echo '<td style="background:none;"><font color="black">'.$monthly_average["Energy_Total_kWh"].'</font></td>';
			     echo '<td style="background:none;"><font color="black">'." ".'</font></td>';


				echo '</tr><tr class="odd"><td>Full Burden Rate of Shorepower($/kWh)</td>';
			    for ($m=0;$m<$max_month;$m++)
				    echo '<td  style="background:none;"><font color="black">'.$COST_YEAR[$m]["Grand_Total_kWh"].'</font></td>';
			    echo '<td style="background:none;"><font color="black">'.$monthly_average["Grand_Total_kWh"].'</font></td>';
			    echo '<td style="background:none;"><font color="black">'." ".'</font></td>';


				echo '</tr></table></div>';
?>
      </div>
    </div>


<!-- Utility Rate box -->

 <div class="annualwrapper">
        <div class="annualchart_left">
<?php
	if ($utility=="SCE&G_Rates")
	{
           	echo '
                   <div class="annual_consumption_box">
                    <div id="graph_range_sel_header"> <span style="font-weight: bold;">South Carolina Electric & Gas Company Rate 24</span><br /></div>

                    <table class="annualRatetblDetailedSummaryRpt">
                    <tr>
                            <td>Basic Customer</td>
                            <td  style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Customer_Charge"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Summer On-Peak Billing Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Summer_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Non-Summer On-Peak Billing Demand (per kW)</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Non_Summer_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Off-Peak Billing Demand</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Off_Peak_Demand_kW"].'</font></td>
                    </tr>
                    <tr>
                            <td>Summer On-Peak Energy June-September</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Summer_Peak_kWh"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Non-Summer On-Peak Energy October-May</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Non_Summer_Peak_kWh"].'</font></td>
                    </tr>
                    <tr>
                            <td>Off-Peak Energy</td>
                            <td style="background:none;"><font color="black">$'.$COST_YEAR[$max_month-1]["U_Off_Peak_kWh_rate"].'</font></td>
                    </tr>
                    <tr class="odd">
                            <td>Franchise Fee</th>
                            <td style="background:none;"><font color="black">'.$COST_YEAR[$max_month-1]["U_Franchise_Fee"]*100.0.'%</font></td>
                    </tr>
        <!-- end energy rate schedule report info -->
        </table>
      </div>
    ';
   }
?>
       </div>
    </div>



<?php
             }
                         break;
         }
?>



    <script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#chart_month").monthpicker({
				showOn:     "both",
                dateFormat: 'yy-mm-01',
				buttonImage: "images/calendar.png",
				buttonImageOnly: true
			});
			jQuery("#date1").datepicker({
				showOn:     "both",
				buttonImage: "images/calendar.png",
				buttonImageOnly: true
			});
			jQuery("#monthly_report_image").colorbox();
		});
	</script>

    <script type="text/javascript">
        //$('#dialog-message').dialog('open');

        // parse a date in yyyy-mm-dd format
        function parseDate(d) {
             var date1 = new Date(d.substr(0, 4), d.substr(5, 2) - 1, d.substr(8, 2), d.substr(11, 2), d.substr(14, 2), d.substr(17, 2));
             //alert('date1: ' + date1);
        return date1;
        }

      function dateToYMD(date) {
             var d = date.getDate();
             var m = date.getMonth() + 1;
             var y = date.getFullYear();
             var t1 = date.getHours();
             var t2 = date.getMinutes();
             var t3 = date.getSeconds();
             var testDate = '' + y + '-' + (m<=9 ? '0' + m : m) + '-' + (d <= 9 ? '0' + d : d) + ' ' + (t1 <= 9 ? '0' + t1 : t1)
                            + ':' + (t2 <= 9 ? '0' + t2 : t2) + ':' + (t3 <= 9 ? '0' + t3 : t3);
            return testDate;
          };

         function timeToUTC() {
                var dt1=document.getElementById('start_date_time').value;
                var myStartDate = parseDate(dt1);
                var offset = myStartDate.getTimezoneOffset() / 60;
                hours = myStartDate.getHours();
                myStartDate.setHours(hours + offset);
                dateStr = dateToYMD(myStartDate);
               $("#start_date_time").val(dateStr);
               //alert('Format date ' + dateStr  );
               return;
            };

    	//alert("test1");
        $('#btnShowGraph').click(function()
        {
            if( !$('#start_date_time').val() || !$('#stop_date_time').val())
            {
                $('#dialog-message').dialog('open');
                return false;
            }
            else
            {
        	//alert("test2");
                // timeToUTC();
                //  alert('Start Date ' + $('#start_date_time').val() + ' Stop Date ' + $('#stop_date_time').val());
                checkDates();
                return true;
            }
    	});
    </script>

    <div class="wrapper">
        <div class="tanvmargin">&nbsp;</div>
    </div>
    <!--<div class="wrapper">
	     <div class="foot_left"></div>
             <div class="foot_mid"><a href="http://www.shipsenergy.com/" target="_new"><img style="margin-bottom: -10px" width="125" src="/erms/imgs/marine-design-ops.png" /></a><div style="display: inline-block; padding-bottom: 10px">&nbsp; &#169;2017 Navis Energy Management Solutions LLC</div></div>
	 </div>-->

	 <!-- header for exported spreadsheet -->
   	 <table id="meter_name_Export" style="display:none">
   	 <tr><td> </td><td><?php echo $Title ?></td> </tr></table>

   <script type="text/javascript">

    function download(strData, strFileName, strMimeType) {
        var D = document,
            a = D.createElement("a");
        strMimeType = strMimeType || "application/octet-stream";

        if (navigator.msSaveBlob) { // IE10+
            return navigator.msSaveBlob(new Blob([strData], { type: strMimeType }), strFileName);
        }

        if ('download' in a) { //html5 A[download]
            a.href = "data:" + strMimeType + "," + encodeURIComponent(strData);
            a.setAttribute("download", strFileName);
            a.innerHTML = "downloading...";
            D.body.appendChild(a);
            setTimeout(function() {
                a.click();
                D.body.removeChild(a);
                if (window.URL) {
                    setTimeout(function() { window.URL.revokeObjectURL(a.href); }, 250);
                }
            }, 66);
            return true;
        }

            // do iframe dataURL download (old ch+FF):
        var f = D.createElement("iframe");
        D.body.appendChild(f);
        f.src = "data:" + strMimeType + "," + encodeURIComponent(strData);

        setTimeout(function() {
            D.body.removeChild(f);
        }, 333);
        return true;
    }
/* end download() */
    function arrayToCSV(array) {
        if (!array || typeof array !== 'object' || Object.keys(array).length === 0) {
            console.error("Array invalido");
            console.log(array);
            return '';
        }

        var csvContent = '';
        var keys = Object.keys(array);
        csvContent += keys.join(',') + '\r\n';

        var row = keys.map(function(key) {
            var cellValue = array[key] ? array[key].toString().replace(/"/g, '""') : '';
            return '"' + cellValue + '"';
        }).join(',');
        csvContent += row + '\r\n';

        return csvContent;
    }

    function downloadCSV() {
        var dataShips = <?php echo json_encode($shipData); ?>;
        var csvContent = arrayToCSV(dataShips);
        var encodedUri = encodeURI("data:text/csv;charset=utf-8," + csvContent);
        var link = document.createElement("a");
        var shipName = "<?php echo $Title; ?>"; 
        var noSpacesName = shipName.replace(" ", "");
        var dt = new Date();
        var month = dt.getMonth() + 1;
        var year = dt.getFullYear();
        var postfix = month + "-" + year;
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", noSpacesName + postfix + ".csv");
        document.body.appendChild(link); // Necesario para Firefox
        link.click();
        document.body.removeChild(link);
    }
  function write_to_excel(tableid)
  {
     	 //alert( "Browser start " + navigator.appCodeName );

          if (tableid == 'TblEnergyCostOverview')
          {
    	      //alert( "Browser Regular " + navigator.appCodeName );
   	          if (navigator.appCodeName == 'Mozilla')
   	          {
   	              //alert( "Browser2 " + navigator.appCodeName );
  	              window.open('data:application/vnd.ms-excel, ' + '<table>'+encodeURIComponent($('#TblConsumeDetailSummary').html()) +encodeURIComponent($('#TblEnergyCostDetail').html()) +encodeURIComponent($('#TblEnergyCostOverview').html()) +  '</table>'  );
  	          }
  	          else
     	      {
     	          //alert( "Browser3 " + navigator.appCodeName );
     	          if (!tableid.nodeType)
              	      tableid = document.getElementById(tableid);
      	          var shipName =  "<?php echo $Title; ?>";
      	          var noSpacesName = shipName.replace(" ", "");
      	          var dt = new Date();
       	          var day = dt.getDate();
        	      var month = dt.getMonth() + 1;
        	      var year = dt.getFullYear();
        	      var postfix = month + "-" + day + "-" + year;
        	      var filename =  noSpacesName + postfix + '.xls';
        	      var myTable = meter_name_Export.outerHTML+TblConsumeDetailSummary.outerHTML+TblEnergyCostDetail.outerHTML+TblEnergyCostOverview.outerHTML;
        	      download(myTable , filename, "application/vnd.ms-excel");
        	      //download(meter_name_Export.outerHTML+TblConsumeDetailSummary.outerHTML+TblEnergyCostDetail.outerHTML+TblEnergyCostOverview.outerHTML  , filename, "application/vnd.ms-excel");
         	  }
          }
          else
          {
                  //alert( "Browser Annual Report " + navigator.appCodeName );
                  var tbltop_row = document.getElementById("monthHeader");  //add total header in top row
                  var totalcell = tbltop_row.insertCell(-1);
   				  totalcell.innerHTML = "Total";

                   //clone tables so originals do not change when deleting month header
                  var tblsource = document.getElementById("annualTblConsumeUsageSummary");
                  var tblcopy = tblsource.cloneNode(true);
                  tblcopy.setAttribute('id', "tableB");
                  tblcopy.deleteRow(0);

                  var tblsource2 = document.getElementById("annualTblEnergyCostDetail");
                  var tblcopy2 = tblsource2.cloneNode(true);
                  tblcopy2.setAttribute('id', "tableC");
                  tblcopy2.deleteRow(0);


                if (navigator.appCodeName == '0Mozilla')
   	            {
   	      	        //alert( "Browser4 " + navigator.appCodeName );
  	                window.open('data:application/vnd.ms-excel, ' + '<table>'+encodeURIComponent($('#annualTblConsumeDetailSummary').html())  + encodeURIComponent(tblcopy.innerHTML) +encodeURIComponent(tblcopy2.innerHTML) +  '</table>'  );
  	         	}
  	        	else
     	     	{
     	             //alert( "Browser5 " + navigator.appCodeName );
    	        	 if (!tableid.nodeType)
              	         tableid = document.getElementById(tableid);
      	      	     var shipName =  "<?php echo $Title; ?>";
      	        	 var noSpacesName = shipName.replace(" ", "");
      	             var dt = new Date();
       	             var day = dt.getDate();
        	         var month = dt.getMonth() + 1;
        	         var year = dt.getFullYear();
        	         var postfix = month + "-" + day + "-" + year;
        	         var filename =  noSpacesName + postfix + '.xls';
        	         var myTable = annualTblConsumeDetailSummary.outerHTML+tblcopy.outerHTML+tblcopy2.outerHTML;
        	         download(myTable , filename, "application/vnd.ms-excel");
        	         //download(meter_name_Export.outerHTML+TblConsumeDetailSummary.outerHTML+TblEnergyCostDetail.outerHTML+TblEnergyCostOverview.outerHTML  , filename, "application/vnd.ms-excel");
         	      }
            }
        }
</script>



<?php
    function getRealIpAddr()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
?>
<!-- vendor scripts -->
  <script type="text/javascript" src="/erms/js/vendor/underscore-min.js"></script>
  <script type="text/javascript" src="https://unpkg.com/mithril/mithril.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/Highcharts-5.0.14/code/highcharts.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/Highcharts-5.0.14/code/modules/exporting.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/tinycolor.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/moment-2.8.1.min.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/moment-timezone-with-data.min.js"></script>
<!-- end vendor scripts -->
<!-- bootstrap server data -->
<script type="text/javascript">
  window.user_data = <?php echo json_encode($_SESSION['user_data']); ?>;
  window.user = "<?php echo $username; ?>";
  window.shipClass = "<?php echo $shipClass; ?>";
  window.module = "<?php echo $_REQUEST['module']; ?>";
  window.ships_data = <?php echo json_encode($ships_data); ?>;
  // For the ship view we only look at 1 ship at a time
  window.aquisuite = Object.keys(ships_data)[0];
  window.ship = ships_data[aquisuite];
  window.access_level = "<?php echo $access_level; ?>";
  window.module = "<?php echo $module; ?>";
  window.legacy = <?php echo json_encode($legacy? $legacy: $graph); ?>;
  window.graph = <?php echo json_encode($graph); ?>;
  window.metrics = <?php echo json_encode($metrics); ?>;
</script>
<!-- end bootstrap server data -->
<!-- local scripts -->
  <script type="text/javascript" src="/erms/js/nav/navbar.js"></script>
  <script type="text/javascript" src="/erms/js/nav/breadcrumbs.js"></script>
  <script type="text/javascript" src="/erms/js/ship_view/power_cost_graph.js"></script>
  <script type="text/javascript" src="/erms/js/ship_view/energy_meter_graph.js"></script>
  <script type="text/javascript" src="/erms/js/ship_view/main_graph.js"></script>
  <script type="text/javascript" src="/erms/js/ship_view/consumption_table.js"></script>
  <script type="text/javascript" src="/erms/js/raw-data-export.js"></script>
  <script type="text/javascript" src="/erms/js/ship_view/index.js"></script>
<!-- end local scripts -->
</body>
</html>
