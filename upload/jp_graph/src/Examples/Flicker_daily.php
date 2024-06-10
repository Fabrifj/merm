<?php


// require_once ("jpgraph/jpgraph.php"); 
// require_once ("jpgraph/jpgraph_pie.php"); 
// require_once ("jpgraph/jpgraph_pie3d.php"); 

$connect = mysql_connect("bwolff-eqoff.db.sonic.net","bwolff_eqoff-rw","61ee28b7");

// Set the active mySQL database 
$db_selected = mysql_select_db("bwolff_eqoff", $connect); 
if (!$db_selected) { 
die ('Can\'t use db : ' . mysql_error()); 
} 

// Select all the rows in the markers table 
$sql = "SELECT id, Energy_Consumption, Time From smms_flickertail WHERE Time LIKE '%2011-03-18%'";
$RESULT = mysql_query("$sql");
$records = mysql_num_rows( $RESULT);
// echo $records; // number of unique urls

// for($i=0;$i<10;$i++) // for every unique url read the next lines
	
// {
// $rows = mysql_fetch_row($RESULT); 
// $id = $rows[0];
// $EC = $rows[1];
// $Time = $rows[2];
// echo $Time . "</br>";
// echo $id . "</br>";
// echo $EC . "</br>";

// }
	
	
$sql_2 = "SELECT * FROM smms_flickertail WHERE id LIKE '%2011-03-05%'";
$RESULT_2 = mysql_query("$sql_2");
$records_2 = mysql_num_rows( $RESULT_2); // number of unique urls

$sql_3 = "SELECT * FROM smms_flickertail WHERE Time LIKE '%2011-03-05%'";
$RESULT_3 = mysql_query("$sql_3");


		
		// echo $data;
// $row0 = (mysql_fetch_array($RESULT_2));
// $row1 = (mysql_fetch_array($RESULT_3));
// $row2 = (mysql_fetch_array($RESULT_4));



// for ($i=0;$i<$records;$i++)
// {
// $row_2 = mysql_fetch_row($RESULT_2);
// $id_1 = $row_2[0];
// $row_3 = mysql_fetch_row($RESULT_3);
// $id_2 = $row_3[0];
// $row_4 = mysql_fetch_row($RESULT_4);
// $id_3 = $row_4[0];

// echo $id_1 . $id_2 . $id_3 . "</br>";
// // echo $date . "</br>";
// }
// i = 0

// while ($row = mysql_fetch_assoc($RESULT_3))

// foreach($row as $value) {

// $want[$i] = $value;

// }

// echo $want[$i];

// $Energycon = $row_2[0];
// echo $RESULT_2;

// echo $records_2 . "</br>";
// echo $MinVolts;
// echo $row_2;



// echo $records;
// echo $records;
// echo $RESULT_2;
// echo $Time;
// echo $MinVolts
// echo $row_2
// $sqlTotal = "SELECT count(*) AS Number FROM servicesrendered";
// $sqlTime = "SELECT * FROM smms_flickertail WHERE Time LIKE '%2011-03-04%'";
$sqlTotal = "SELECT Energy_Consumption AS Number FROM smms_flickertail WHERE id = '$records'";
$sqlkWhprev = "SELECT Energy_Consumption AS Number FROM smms_flickertail WHERE id = $records - 1";
$sqlkW = "SELECT Real_Power_kW AS Number FROM smms_flickertail WHERE id = '$records'";
$sqlkVAR = "SELECT Reactive_Power_kVAR AS Number FROM smms_flickertail WHERE id = '$records'";
$sqlkVA = "SELECT Apparent_Power_kVA AS Number FROM smms_flickertail WHERE id = '$records'";
// $sqloil = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%oil%'"; 
// $sqltransmission = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%transmission%'"; 
// $sqlother = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%other%'"; 
// $sqlsteering = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%steering%'"; 
// $sqlbrake = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%brake%'"; 
// $sqlengine = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%engine%'"; 
// $sqlai = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%air%'"; //air intake
// $sqlclutch = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%clutch%'"; 
// $sqlcs = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%cooling%'"; //cooling system
// $sqldsa = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%driveshaft%'"; //driveshaft&Axle
// $sqlexhaust = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%exhaust%'"; 
// $sqlsuspension = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%suspension%'"; 

// $daytime = mysql_query($sqlTime); 
$total = mysql_query($sqlTotal);
$daily = mysql_query($sqlkWhprev);
$real = mysql_query($sqlkW);
$reactive = mysql_query($sqlkVAR);
$apparent = mysql_query($sqlkVA); 
// // $oil = mysql_query($sqloil); 
// // $transmission = mysql_query($sqltransmission); 
// // $other = mysql_query($sqlother); 
// // $steering = mysql_query($sqlsteering); 
// // $brake = mysql_query($sqlbrake); 
// // $engine = mysql_query($sqlengine); 
// // $air = mysql_query($sqlai); 
// // $clutch = mysql_query($sqlclutch); 
// // $cooling = mysql_query($sqlcs); 
// // $driveshaft = mysql_query($sqldsa); 
// // $exhaust = mysql_query($sqlexhaust); 
// // $suspension = mysql_query($sqlsuspension);

// $recordstime = mysql_num_rows($daytime) 
// for($i=0;$i<$recordstime;$i++)
// {
// $rowst = mysql_fetch_row($daytime);
// echo $rowst
// }
// while($resulttime = mysql_fetch_array($daytime)) 
// { 
// $timeValue = $resulttime;
// echo $timeValue;
// }

// for($i=0;$i<$records;$i++)
// {
// while($resultdaily = mysql_fetch_array($daily)) 
// { 
// $dailyValue = $resultdaily['Number'];

// }

// }
// echo $dailyValue . "</br>";

while($resultTotal = mysql_fetch_array($total)) 
{ 
$totalValue = $resultTotal['Number'];
// echo $totalValue . "</br>"; 
}

while($resultkW = mysql_fetch_array($real)) 
{ 
$realValue = $resultkW['Number'];
// echo $realValue . "</br>"; 
}


while($resultkVAR = mysql_fetch_array($reactive)) 
{ 
$reactiveValue = $resultkVAR['Number'];
// echo $reactiveValue . "</br>"; 
}

// content="text/plain; charset=utf-8"
// require_once ('jpgraph/jpgraph.php');
// require_once ('jpgraph/jpgraph_line.php');
// require_once ('jpgraph/jpgraph_date.php');
 
// // Create a data set in range (50,70) and X-positions
// DEFINE('NDATAPOINTS',360);
// DEFINE('SAMPLERATE',240); 
// $start = time();
// $end = $start+NDATAPOINTS*SAMPLERATE;
// $data = array();
// $xdata = array();
// for( $i=0; $i < NDATAPOINTS; $i++ ) {
    // $data[$i] = rand(50,70);
	// // $data[$i] = $id_3
    // $xdata[$i] = $start + $i * SAMPLERATE;
	// // $xdata[$i] = $id_2
// }
// // Create the new graph
// $graph = new Graph(540,300);
 
// // Slightly larger than normal margins at the bottom to have room for
// // the x-axis labels
// $graph->SetMargin(40,40,30,130);
 
// // Fix the Y-scale to go between [0,100] and use date for the x-axis
// $graph->SetScale('intlin',0,100000);
// $graph->title->Set("Example on Date scale");
 
// // Set the angle for the labels to 90 degrees
// $graph->xaxis->SetLabelAngle(90);
 
// // $line = new LinePlot($data,$xdata);
// $line = new LinePlot($row2,$row0);
// $line->SetLegend('Year 2005');
// $line->SetFillColor('lightblue@0.5');
// $graph->Add($line);
// $graph->Stroke();


require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require_once ('jpgraph/jpgraph_date.php');

include_once ('mysql_connect.php');

$sql_4 = "SELECT id, Real_Power_kW, Time From smms_flickertail WHERE Time LIKE '%2011-03-11%'";
$RESULT_4 = mysql_query($sql_4);
while ($row=mysql_fetch_array($RESULT_4)) {

	$xdata[] = $row['Time'];
	$ydata[] = $row['id'];
	$ydata2[] = $row['Real_Power_kW'];
	}
// Create the graph. 
$graph = new Graph(900,600,"auto",30);
$graph->SetMargin(40,40,30,175);	
$graph->SetScale("textlin");
$graph->yaxis->scale->SetGrace(20);
$graph->SetMarginColor('gray');
$graph->SetFrame(true,'lightblue',.05);
// $graph->SetBackgroundGradient('green',BGRAD_PLOT);


// Create a line pot
// $lplot = new LinePlot($ydata);
// $lplot->SetWeight(2);
// $lplot->SetColor('red');
$sp1 = new LinePlot($ydata2);





//Add plot
$graph->Add($sp1);
// $graph->Add($lplot);
$sp1->SetWeight(1);
$sp1->SetColor("#151B54");
$sp1->SetFillColor('lightblue@0.5');


$caption=new Text("Time Interval",400,575);
$caption->SetFont(FF_ARIAL,FS_BOLD,10);
$graph->AddText($caption);
// titles
$graph->title->Set("Current Power in kW ");
$graph->title->SetColor("black"); 
$graph->title->SetFont(FF_ARIAL,FS_BOLD,14);

//x-axis
$graph->xaxis->title->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->xaxis->SetFont(FF_ARIAL,FS_BOLD,9);
$graph->xaxis->SetTitlemargin(80);
// $graph->xaxis->Settitle("Day");
$graph->xaxis->SetLabelMargin(0);
$graph->xaxis->SetTickLabels($xdata);
$graph->xaxis->SetLabelAngle(75);
$graph->xaxis->SetTextLabelInterval(12);
$graph->xaxis->SetPos("min"); 
$graph->xaxis->HideTicks(false,false); 
$graph->xaxis->SetColor("black"); 
$graph->xgrid->Show(true);

//y-axis
$graph->yaxis->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->yaxis->SetPos("min"); 
$graph->yaxis->SetTitlemargin(20);
$graph->yaxis->title->SetAngle(0); 
// $graph->yaxis->Settitle("kW");
$graph->yaxis->SetColor("black"); 
$graph->yaxis->SetLabelFormat('%1.1f');
$graph->yaxis->HideTicks(true,true);

// Display the graph
$graph->Stroke();
// if ($myrow=mysql_fetch_array($RESULT_4)) {
	// do {
		// $datay[] = $array["Energy_Consumption"];
		// }while ($myrow=mysql_fetch_array($RESULT_4));
		// }
// $i=0;
// while ($array=mysql_fetch_array($result)) {
		// $ydata[$i]=$array[0];
		
// $i++;
// };
		
		
// Some (random) data
// $ydata   = $data;
// $ydata2  = array(800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000, 800000);

// Size of the overall graph
// $width=900;
// $height=500;

// // Create the graph and set a scale.
// // These two calls are always required
// $graph = new Graph($width,$height);
// $graph->SetScale('intlin',800000,1000000);
// $graph->SetShadow();

// // Setup margin and titles
// $graph->SetMargin(40,20,20,100);
// $graph->title->Set('Calls per operator (June,July)');
// $graph->subtitle->Set('(March 12, 2008)');
// $graph->xaxis->title->Set('Operator');
// $graph->yaxis->title->Set('# of calls');


// $graph->yaxis->title->SetFont( FF_FONT1 , FS_BOLD );
// $graph->xaxis->title->SetFont( FF_FONT1 , FS_BOLD );

// // Create the first data series
// $lineplot=new LinePlot($ydata);
// $lineplot->SetWeight( 2 );   // Two pixel wide

// // Add the plot to the graph
// $graph->Add($lineplot);

// // Create the second data series
// $lineplot2=new LinePlot($ydata2);
// $lineplot2->SetWeight( 2 );   // Two pixel wide

// // Add the second plot to the graph
// $graph->Add($lineplot2);

// //Display the graph
// $graph->Stroke();

// while($resultkVA = mysql_fetch_array($apparent)) 
// { 
// $apparentValue = $resultkVA['Number'];
// echo $apparentValue; 
// } 
// require_once ('jpgraph/jpgraph.php');
// require_once ('jpgraph/jpgraph_line.php');

// // Some (random) data
// $ydata   = array(4, 3, 8, 12, 5, 1, $date, 13, 5, 7);
// $ydata2  = array(1, 19, 15, 7, 22, 14, 5, 9, 21, 13 );

// // Size of the overall graph
// $width=350;
// $height=250;

// // Create the graph and set a scale.
// // These two calls are always required
// $graph = new Graph($width,$height);
// $graph->SetScale('intlin');
// $graph->SetShadow();

// // Setup margin and titles
// $graph->SetMargin(40,20,20,40);
// $graph->title->Set('Calls per operator (June,July)');
// $graph->subtitle->Set('(March 12, 2008)');
// $graph->xaxis->title->Set('Operator');
// $graph->yaxis->title->Set('# of calls');

// $graph->yaxis->title->SetFont( FF_FONT1 , FS_BOLD );
// $graph->xaxis->title->SetFont( FF_FONT1 , FS_BOLD );

// // Create the first data series
// $lineplot=new LinePlot($ydata);
// $lineplot->SetWeight( 2 );   // Two pixel wide

// // Add the plot to the graph
// $graph->Add($lineplot);

// // Create the second data series
// $lineplot2=new LinePlot($ydata2);
// $lineplot2->SetWeight( 2 );   // Two pixel wide

// // Add the second plot to the graph
// $graph->Add($lineplot2);

// // Display the graph
// $graph->Stroke();
?>
