<?php 
require_once ("jpgraph/jpgraph.php"); 
require_once ("jpgraph/jpgraph_pie.php"); 
require_once ("jpgraph/jpgraph_pie3d.php"); 

$connect = mysql_connect("localhost","root","equate101");
// Set the active mySQL database 
$db_selected = mysql_select_db("test_database", $connect); 
if (!$db_selected) { 
die ('Can\'t use db : ' . mysql_error()); 
} 

// Select all the rows in the markers table 
$sql = "SELECT id From smms_flickertail";
$RESULT = mysql_query("$sql");
$records = mysql_num_rows( $RESULT);
// echo $records; // number of unique urls

for($i=0;$i<$records;$i++) // for every unique url read the next lines
{
$rows = mysql_fetch_row($RESULT); 
$Time = $rows[0];
 
$sql_2 = "SELECT Time FROM smms_flickertail WHERE Time = '$Time'";
$RESULT_2 = mysql_query("$sql_2");
$records_2 = mysql_num_rows( $RESULT_2); // number of unique urls
$row_2 = mysql_fetch_row($RESULT_2);
// $Energycon = $row_2[0];
// echo $RESULT_2;

// echo $Time . "</br>";
// echo $MinVolts;
// echo $row_2;
}
// echo $records;
// echo $records;
// echo $RESULT_2;
// echo $Time;
// echo $MinVolts
// echo $row_2
// $sqlTotal = "SELECT count(*) AS Number FROM servicesrendered"; 
$sqlTotal = "SELECT Energy_Consumption AS Number FROM smms_flickertail WHERE id = '$records'";
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

$total = mysql_query($sqlTotal);
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

while($resultkVA = mysql_fetch_array($apparent)) 
{ 
$apparentValue = $resultkVA['Number'];
echo $apparentValue . "</br>"; 
} 

// while($resultOil = mysql_fetch_array($oil)) 
// { 
// $oilValue = $resultOil['Number']; 
// } 

// while($resultTransmission = mysql_fetch_array($transmission)) 
// { 
// $transmissionValue = $resultTransmission['Number']; 

// } 

// while($resultSteering = mysql_fetch_array($steering)) 
// { 
// $steeringValue = $resultSteering['Number']; 
// } 
// while($resultbrake = mysql_fetch_array($brake)) 
// { 
// $brakeValue = $resultbrake['Number']; 
// } 
// while($resultengine = mysql_fetch_array($engine)) 
// { 
// $engineValue = $resultengine['Number']; 
// } 

// while($resultair = mysql_fetch_array($air)) 
// { 
// $airValue = $resultair['Number']; 
// } 
// while($resultclutch = mysql_fetch_array($clutch)) 
// { 
// $clutchValue = $resultclutch['Number']; 
// } 
// while($resultcooling = mysql_fetch_array($cooling)) 
// { 
// $coolingValue = $resultcooling['Number']; 
// } 
// while($resultdriveshaft = mysql_fetch_array($driveshaft)) 
// { 
// $driveshaftValue = $resultdriveshaft['Number']; 
// } 
// while($resultexhaust = mysql_fetch_array($exhaust)) 
// { 
// $exhaustValue = $resultexhaust['Number']; 
// }
// while($resultsuspension = mysql_fetch_array($suspension)) 
// { 
// $suspensionValue = $resultsuspension['Number']; 
// }
// while($resultOther = mysql_fetch_array($other)) 
// { 
// $otherValue = $resultOther['Number']; 
// } 
$time= date('m/d/y'.' @ '.'g:i a');

echo $time;

// $data = array($transmissionValue, $oilValue, $steeringValue, $brakeValue, $engineValue, $airValue, $clutchValue, $coolingValue, $driveshaftValue, $exhaustValue,$suspensionValue,$otherValue); 
// $data = 
// //print_r($data); 

$data = array($realValue, $reactiveValue, $apparentValue);
// print_r ($data);
$graph = new PieGraph(510, 350,"auto"); 
$graph->SetShadow(); 
$graph->title->Set("Matrix of Services Rendered--Last Updated $time"); 
$graph->title->SetFont(FF_FONT1,FS_BOLD); 
$graph->SetColor('silver@0.1' ); 

$p1 = new PiePlot3D($data); 
$p1->SetSize(.3); 
$p1->SetCenter(0.50); 
$p1->SetStartAngle(90); 
$p1->SetAngle(60); 
$p1->SetTheme('earth');

$p1->value->SetFont(FF_FONT1,FS_BOLD); 
$p1->SetLabelType(PIE_VALUE_PER); 

// // $legends = array('Transmission','Oil Changes','Steering','Brakes Syst','Engine','Air Intake','Clutch','Cooling System','Driveshaft & Axle','Exhaust','Other'); 
// // //print_r($legends);
$legends = array('Real Power','Reactive Power','Apparent Power');
print_r ($legends); 

$p1->SetLegends($legends); 

$a = array_search(max($data),$data); //Find the position of maixum value. 
$p1->ExplodeSlice($a);//(array(25,75,50,20)); 

$graph->Add($p1); 
$graph->Stroke();

?>