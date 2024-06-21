<?php
date_default_timezone_set('UTC');

require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');
require './src/db_helpers.php';
require './src/logger.php';


$logger = new Logger($loopName);
if($_REQUEST['MODE'] == "dev"){
    $logger->logInfo("dev");
    $mode = "dev";
}else{
    $mode = "host";
    $logger->logInfo("host");
}
$loopName = "Cape_Kennedy";
db_connect($logger,$mode );

// echo "test"; // Remove or comment out this line
$shipData = fetch_last_24_hours($logger, $loopName);
if ($shipData) {
    $logger->logDebug("Data received");
} else {
    $logger->logDebug("No data received");
}
$datay = $shipData['total_kwH'];
$datax = $shipData['time'];

$graph = new Graph(800,600);
$graph->SetScale("textlin");
//
// Ajustes del gráfico
$graph->title->Set("Example: " . $loopName);
$graph->xaxis->title->Set("Date");
$graph->yaxis->title->Set("Total_kw");
//
$graph->xaxis->SetTickLabels($datax);
$lineplot = new LinePlot($datay);
$graph->Add($lineplot);
$graph->Stroke();

db_close()
?>
