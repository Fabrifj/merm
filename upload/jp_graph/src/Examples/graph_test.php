<?php 
include_once ("jpgraph/jpgraph.php"); 
include_once ("jpgraph/jpgraph_pie.php"); 
include_once ("jpgraph/jpgraph_pie3d.php"); 

$connect = mysql_connect("localhost","","");
// Set the active mySQL database 
$db_selected = mysql_select_db("shop", $connect); 
if (!$db_selected) { 
die ('Can\'t use db : ' . mysql_error()); 
} 

// Select all the rows in the markers table 
$sql = "SELECT DISTINCT servicearea FROM servicesrendered group by servicearea";
$RESULT = mysql_db_query("shop","$sql",$connect);
$records = mysql_num_rows( $RESULT);
// echo $records; // number of unique urls

for($i=0;$i<$records;$i++) // for every unique url read the next lines
{
$rows = mysql_fetch_row($RESULT); 
$servicearea = $rows[0];

$sql_2 = "SELECT servicearea FROM servicesrendered WHERE servicearea = '$servicearea'";
$RESULT_2 = mysql_db_query("shop","$sql_2",$connect);
$records_2 = mysql_num_rows( $RESULT_2); // number of unique urls
//$row_2 = mysql_fetch_row($RESULT_2);
//$servicearea_used[] = $row_2[0];
//echo $records_2;
}

$sqlTotal = "SELECT count(*) AS Number FROM servicesrendered"; 
$sqloil = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%oil%'"; 
$sqltransmission = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%transmission%'"; 
$sqlother = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%other%'"; 
$sqlsteering = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%steering%'"; 
$sqlbrake = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%brake%'"; 
$sqlengine = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%engine%'"; 
$sqlai = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%air%'"; //air intake
$sqlclutch = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%clutch%'"; 
$sqlcs = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%cooling%'"; //cooling system
$sqldsa = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%driveshaft%'"; //driveshaft&Axle
$sqlexhaust = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%exhaust%'"; 
$sqlsuspension = "SELECT count(*) AS Number FROM servicesrendered a where servicearea LIKE '%suspension%'"; 

$total = mysql_query($sqlTotal); 
$oil = mysql_query($sqloil); 
$transmission = mysql_query($sqltransmission); 
$other = mysql_query($sqlother); 
$steering = mysql_query($sqlsteering); 
$brake = mysql_query($sqlbrake); 
$engine = mysql_query($sqlengine); 
$air = mysql_query($sqlai); 
$clutch = mysql_query($sqlclutch); 
$cooling = mysql_query($sqlcs); 
$driveshaft = mysql_query($sqldsa); 
$exhaust = mysql_query($sqlexhaust); 
$suspension = mysql_query($sqlsuspension); 


while($resultTotal = mysql_fetch_array($total)) 
{ 
$totalValue = $resultTotal['Number']; 
} 

while($resultOil = mysql_fetch_array($oil)) 
{ 
$oilValue = $resultOil['Number']; 
} 

while($resultTransmission = mysql_fetch_array($transmission)) 
{ 
$transmissionValue = $resultTransmission['Number']; 

} 

while($resultSteering = mysql_fetch_array($steering)) 
{ 
$steeringValue = $resultSteering['Number']; 
} 
while($resultbrake = mysql_fetch_array($brake)) 
{ 
$brakeValue = $resultbrake['Number']; 
} 
while($resultengine = mysql_fetch_array($engine)) 
{ 
$engineValue = $resultengine['Number']; 
} 

while($resultair = mysql_fetch_array($air)) 
{ 
$airValue = $resultair['Number']; 
} 
while($resultclutch = mysql_fetch_array($clutch)) 
{ 
$clutchValue = $resultclutch['Number']; 
} 
while($resultcooling = mysql_fetch_array($cooling)) 
{ 
$coolingValue = $resultcooling['Number']; 
} 
while($resultdriveshaft = mysql_fetch_array($driveshaft)) 
{ 
$driveshaftValue = $resultdriveshaft['Number']; 
} 
while($resultexhaust = mysql_fetch_array($exhaust)) 
{ 
$exhaustValue = $resultexhaust['Number']; 
}
while($resultsuspension = mysql_fetch_array($suspension)) 
{ 
$suspensionValue = $resultsuspension['Number']; 
}
while($resultOther = mysql_fetch_array($other)) 
{ 
$otherValue = $resultOther['Number']; 
} 
$time= date('m/d/y'.' @ '.'g:i a');

$data = array($transmissionValue, $oilValue, $steeringValue, $brakeValue, $engineValue, $airValue, $clutchValue, $coolingValue, $driveshaftValue, $exhaustValue,$suspensionValue,$otherValue); 
//print_r($data); 

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

$legends = array('Transmission','Oil Changes','Steering','Brakes Syst','Engine','Air Intake','Clutch','Cooling System','Driveshaft & Axle','Exhaust','Other'); 
//print_r($legends); 

$p1->SetLegends($legends); 

$a = array_search(max($data),$data); //Find the position of maixum value. 
$p1->ExplodeSlice($a);//(array(25,75,50,20)); 

$graph->Add($p1); 
$graph->Stroke();

?>