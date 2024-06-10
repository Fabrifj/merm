<?php
// // content="text/plain; charset=utf-8"
// require_once ('jpgraph/jpgraph.php');
// require_once ('jpgraph/jpgraph_line.php');

// // Some data
// $ydata = array($BattVolt);
 
// // Create the graph. These two calls are always required
// $graph = new Graph(350,250);
// $graph->SetScale('textlin');
 
// // Create the linear plot
// $lineplot=new LinePlot($ydata);
// $lineplot->SetColor('blue');
 
// // Add the plot to the graph
// $graph->Add($lineplot);
 
// // Display the graph
// $graph->Stroke();

include_once "mysql_connecttst.php";

$res = mysql_query('select * from batt_tst');

$field_time = mysql_field_name($res, 0) . "\n";
$field_BattVolt = mysql_field_name($res, 1);
$field_Ave = mysql_field_name($res, 2);
$field_Min = mysql_field_name($res, 3);
$field_Max = mysql_field_name($res, 4);

$sql = mysql_query("SELECT * FROM batt_tst");

while($row = mysql_fetch_array($sql)) {

$Time = $row["Time"];
print "$field_time</br>";
echo "$Time</br>";
$BattVolt = $row["Battery Voltage"];
echo "$field_BattVolt";
echo "$BattVolt</br>";
$Ave = $row["Ave"];
echo "$field_Ave";
echo "$Ave,</br>";
$Min = $row["Min"];
echo "$field_Min";
echo "$Min,</br>";
$Max = $row["Max"];
echo "$field_Max";
echo "$Max,</br>";
 
};

?>

