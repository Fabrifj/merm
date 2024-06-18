<?php
date_default_timezone_set('UTC');

require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');

// echo "test"; // Remove or comment out this line

$datay = array(20,15,23,15);
$graph = new Graph(400,300);
$graph->SetScale("textlin");
$lineplot = new LinePlot($datay);
$graph->Add($lineplot);
$graph->Stroke();
?>
