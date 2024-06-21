<?php
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_bar.php')



/**
 * erms_bar_graph()
 *
 * @param mixed $time
 * @param mixed $table
 * @param mixed $date_value_start
 * @param mixed $date_value_end
 * @return
 */
function erms_bar_graph($time,$table,$date_value_start,$date_value_end)
{
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
;

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
?>