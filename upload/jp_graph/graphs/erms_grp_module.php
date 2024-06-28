<?php

 /**
  * RMS Module
  *
  * RMS - Resource Management System
  *
  *.
  * @version 2.0
  * @since 2.0
  *
  *
  * @example    http://<server>/erms/upload/jp_graph/graphs/erms_module_v2.php?display=day&user=decision&module=mod1
  *
  * @param string $display  Display mode (day, month, anydate)
  * @param string $user     Ship or Facility (decision, kennedy, etc.)
  * @param string $module   ERMS sub-module (mod1, mod6 - see ERMS_Modules class below)
  */

//....................................KLogger...............................
require '../../../erms/includes/KLogger.php';
$log = new KLogger ( "log.txt" , KLogger::DEBUG );

require './src/logger.php';
$testLogger = new Logger("Test");

//.....................................End KLogger..........................

error_reporting (E_ALL ^ E_NOTICE);
$testLogger->logInfo("Start erms");

class ERMS_Modules
{
    const PowerAndCostAnalysis = 'mod1';
    const EnergyMeterTrending = 'mod3';
    const PerformanceTrending = 'mod8';
}

$Meter_Name = "";

include '../../../erms/includes/debugging.php';
include '../../../schedules/schedules.php';
include '../../../erms/includes/access_control.php';
include '../../../erms/includes/data_methods.php';
include '../../../erms/includes/energy_methods.php';
include '../../../erms/includes/gfx_methods.php';
include_once ('../../../conn/mysql_connect-ro.php');
include_once ('../../../Auth/auth.php');

//Update 2024
require './src/db_helpers.php';


$shipClass = $_REQUEST['shipClass'];

// Redirect happens within isAuthenticated and isPermitted
// but we still want to make sure we exit the main script
if(!isAuthenticated() || !isPermitted($_REQUEST['user'], $shipClass, null)) {
  exit;
}

$client_ip_address =  getRealIpAddr();
$log->logInfo('Client IP['.$client_ip_address.']');

setShipClass($shipClass);

$log->logInfo('ERMS GRP MODULE 1');

include '../../../erms/includes/init_mgr.php';
$log->logInfo('ERMS GRP MODULE 2nd');
setModLinks($username, $shipClass);
setBreadcrumbs("manager", $_SESSION['user_data']['mgrMods'][$module]["text"], $_SESSION['user_data']['shipGroup']);

?>

<!DOCTYPE HTML>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Marine Design & Operations</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

      <!-- <script type="text/javascript" src="http://code.jquery.com/jquery-1.8.2.min.js"></script> -->
        <script type="text/javascript" src="//code.jquery.com/jquery-1.11.1.min.js"></script>

    <script type="text/javascript" src="//code.jquery.com/ui/1.9.1/jquery-ui.min.js"></script>

<!--- server structure	 -->
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/930div.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/menu.css" />
    <link rel="stylesheet" type="text/css" media="all" href="/erms/css/jquery-ui-timepicker-addon.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="/erms/css/colorbox.css" />
    <link rel="stylesheet" type="text/css" media="print" href="/erms/css/printit.css" />

    <script type="text/javascript" src="/erms/jquery/jquery-ui-timepicker-addon.js"></script>
    <script type="text/javascript" src="/erms/jquery/jquery-ui-sliderAccess.js"></script>
    <script type="text/javascript" src="/erms/jquery/jquery.ui.monthpicker.js"></script>
    <script type="text/javascript" src="/erms/jquery/jquery.colorbox.js"></script>
 <!--- local test structure
    <link rel="stylesheet" type="text/css" media="screen" href="../../../erms/css/930div.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../../erms/css/menu.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../../erms/css/jquery-ui-timepicker-addon.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="../../../erms/css/colorbox.css" />
    <link rel="stylesheet" type="text/css" media="print" href="../../../erms/css/printit.css" />

    <script type="text/javascript" src="../../../erms/jquery/jquery-ui-timepicker-addon.js"></script>
    <script type="text/javascript" src="../../../erms/jquery/jquery-ui-sliderAccess.js"></script>
    <script type="text/javascript" src="../../../erms/jquery/jquery.ui.monthpicker.js"></script>
    <script type="text/javascript" src="../../../erms/jquery/jquery.colorbox.js"></script>

  --->


    <link rel="stylesheet" media="all" type="text/css" href="//code.jquery.com/ui/1.10.3/themes/flick/jquery-ui.css" />

<!-- Start Modular CSS -->
<!-- local -->
<link rel="stylesheet" type="text/css" href="/erms/css/manager_view/main.css" />
<link rel="stylesheet" type="text/css" href="/erms/css/nav/navbar.css" />
<link rel="stylesheet" type="text/css" href="/erms/css/nav/navsubbar.css" />
<link rel="stylesheet" type="text/css" href="/erms/css/nav/breadcrumbs.css" />
<!-- end local -->
<!-- vendor -->
<link rel="stylesheet" type="text/css" href="/erms/css/vendor/font-awesome-4.0.3/css/font-awesome.min.css" />
<!-- end vendor -->
<!-- End Modular CSS -->
    <script type="text/javascript">
        if(top != self) top.location.replace(location);  // Do not allow this version of RMS to load into an iframe, bust out of it - back button still works
    </script>

</head>

<body>

    <!--  Logo - Header -->
      <!--
    <div class="wrappertight">
       <div class="fhead_all"><img width="920" height="22" src="images/rms_Logo_22.png" /></div>
    </div>
      -->
    <div id="navBar"></div>
    <div id="navSubBar"></div>
    <div id="breadcrumbs"></div>
<?php
    switch ($module)
    {
    	// Energy Power and Cost Analysis
        case ERMS_Modules::PowerAndCostAnalysis:
        case ERMS_Modules::PerformanceTrending:
?>
        <div class="annualwrapper">
          <div class="chart_group_left">
            <div id="metricsTable"></div>
            <div id="mgrMainGraph" class="mgr-main-graph-container"></div>
          </div>
        <div class="annual_graph_right">
         <!-- Graph Information and Selection -->
          <div class="info_wrapper">
           <!-- Graph Period Selection -->
            <div class="iwbox1">
              <div id="graph_range_sel_header">
                            <span style="font-weight: bold;">Select Report Period</span><br />
                        </div>
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
<?php
   			 $VAL["report_year"] = str_replace(',', '', $VAL["report_year"]);
          $months = [
              "January" => 1,
              "February" => 2,
              "March" => 3,
              "April" => 4,
              "May" => 5,
              "June" => 6,
              "July" => 7,
              "August" => 8,
              "September" => 9,
              "October" => 10,
              "November" => 11,
              "December" => 12,
              "Last 30 Days" => "month",
              "Annual" => "annual"
          ];
          
          echo '
              <form id="f" action="" method="POST">
              <input name="report" type="hidden" value="report" />';
          if ($module != ERMS_Modules::PerformanceTrending) {
              echo '<label>Select Report Month</label>
              <select name="month" id="month">';
              foreach ($months as $name => $value) {
                  $selected = ($VAL["report_month"] == $name) ? "selected" : "";
                  echo "<option value=\"$value\" $selected>$name</option>";
              }
              echo '</select>';
          }
          
    					echo '<label>Select Report Year</label><br />
    					<select name="year" id="year">
                        <br />';
                         if($module == ERMS_Modules::PerformanceTrending) {
                           echo '<option value="last12" ';if ($VAL["report_year"]=="last12"){echo "selected";} echo '>Last 12 Months</option>';
                         }
                                        echo '<option value="2024" ';if ($VAL["report_year"]=="2024"){echo "selected";} echo '>2024</option>';
                                        echo '<option value="2023" ';if ($VAL["report_year"]=="2023"){echo "selected";} echo '>2023</option>';
                                        echo '<option value="2022" ';if ($VAL["report_year"]=="2022"){echo "selected";} echo '>2022</option>';
                                        echo '<option value="2021" ';if ($VAL["report_year"]=="2021"){echo "selected";} echo '>2021</option>';
                                        echo '<option value="2020" ';if ($VAL["report_year"]=="2020"){echo "selected";} echo '>2020</option>';
                                        echo '<option value="2019" ';if ($VAL["report_year"]=="2019"){echo "selected";} echo '>2019</option>';
                            	    	echo '<option value="2018" ';if ($VAL["report_year"]=="2018"){echo "selected";} echo '>2018</option>
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
            <div id="rawDataExport"></div>
          </div>
     </div>


    <!-- Graph for selected period -->
	<div class="annualwrapper">
     </div>
<?php
            break;
        case ERMS_Modules::EnergyMeterTrending:
?>
        <div class="annualwrapper">
          <div class="chart_group_left">
            <div id="metricsTable"></div>
            <div id="mgrMainGraph" class="mgr-main-graph-container"></div>
          </div>
        <div class="annual_graph_right">
         <!-- Graph Information and Selection -->
          <div class="info_wrapper">
           <!-- Graph Period Selection -->
            <div class="iwbox1">
                        <div style="color:white; padding-left: 2px; padding-top: 2px; padding-bottom: 3px;">
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
                </div>
                </div>

          <!-- Selected Data Points Summary -->
          <div class="iwbox3">
            <div id="graph_range_sel_header">
              <span style="font-weight: bold;">Graph Data Point</span><br />
            </div>

 <?php
                    $DATA = get_data($aquisuitetablename[0]);
                    $Field = $DATA['Field'];
                    $fcount = count($Field);
                    $Title = $DATA['Title'];
                    $tcount = count($Title);

                    $utility=$VAL["utility"];

                    echo'
                    <input name="datapts" type="hidden" value="points" />
                    <select name="data1" id="data1">
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
                    </div>
                    </br>
                    <div id="change">
                    </div>
                    </form>';
?>
                    </div>
               </div>
            </div>
     </div>
<?php
            break;
    }
?>





    <div class="wrapper">
        <div class="tanvmargin">&nbsp;</div>
    </div>
    <!--<div class="wrapper">
	     <div class="foot_left"></div>
     <div class="foot_left"></div>
             <div class="foot_mid"><a href="http://www.shipsenergy.com/" target="_new"><img style="margin-bottom: -10px" width="125" src="/erms/imgs/marine-design-ops.png" /></a><div style="display: inline-block; padding-bottom: 10px">&nbsp; &#169;2017 Navis Energy Management Solutions LLC</div></div>
	 </div>-->
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
  <script type="text/javascript" src="/erms/js/vendor/highcharts-custom-events.min.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/tinycolor.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/moment-2.8.1.min.js"></script>
  <script type="text/javascript" src="/erms/js/vendor/moment-timezone-with-data.min.js"></script>
<!-- end vendor scripts -->
<!-- bootstrap server data -->
<script type="text/javascript">
// The following block is carry over and should cleaned
// up and removed as soon as possible
/// ****START****
  function update()
  {
      document.getElementById('f1').submit();
      document.getElementById('f1').reset();
  }
  function updateMeter()
  {
      document.getElementById('f1').submit();
  }
  function s()
  {
      toggleVis('date_time_panel');
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
    $("#radio" ).buttonset();
  });
  var radio_value = 0;

  radio_value = $('input:radio[name=display]:checked').val();

  if(radio_value === 'anydate') {
      toggleVis('date_time_panel');
      document.getElementById('start_date_time').value = '<?php echo $VAL["date_value_start"] ?>';
      document.getElementById('stop_date_time').value = '<?php echo $VAL["date_value_end"] ?>';

  }
/// ****END****
  window.user_data = <?php echo json_encode($_SESSION['user_data']); ?>;
  window.user = "<?php echo $username; ?>";
  window.ships_data = <?php echo json_encode($ships_data); ?>;
  window.access_level = "<?php echo $access_level; ?>";
  window.module = "<?php echo $module; ?>";
  window.legacy = <?php echo json_encode($legacy? $legacy: $graph); ?>;
  window.graph = <?php echo json_encode($graph); ?>;
  window.metrics = <?php echo json_encode($metrics); ?>;
  window.shipClass = "<?php echo $shipClass; ?>";
  window.isAnnualReport = ("<?php echo $VAL["report_month"]; ?>" == "Annual");
</script>
<!-- end bootstrap server data -->
<!-- local scripts -->
  <script type="text/javascript" src="/erms/js/nav/navsubbar.js"></script>
  <script type="text/javascript" src="/erms/js/nav/navbar.js"></script>
  <script type="text/javascript" src="/erms/js/nav/breadcrumbs.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/mgr_main_graph.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/mgr_perf_trending_graph.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/mgr_energy_meter_trending_graph.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/consumption_table_group.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/energy_meter_trending_table_group.js"></script>
  <script type="text/javascript" src="/erms/js/raw-data-export.js"></script>
  <script type="text/javascript" src="/erms/js/manager_view/index.js"></script>
<!-- end local scripts -->
</body>
</html>
