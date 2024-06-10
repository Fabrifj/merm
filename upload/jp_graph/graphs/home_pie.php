<?php
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_pie.php');
require_once ('jpgraph/jpgraph_pie3d.php');

$report_graph = "tmp_rpt/".$ship."report".$date_value_start.$date_value_end.".png";
if(file_exists($report_graph)){unlink($report_graph);}

$Ferry = 300000;
$Tug_Barge = 1100000;
$Govn_Cargo_Vessel = 1098000;
$Terminal = 11386;

// Some data
$data = array($Ferry,$Tug_Barge,$Govn_Cargo_Vessel,$Terminal);

$Ferry = number_format($Ferry);
$Tug_Barge = number_format($Tug_Barge);
$Govn_Cargo_Vessel = number_format($Govn_Cargo_Vessel);
$Terminal = number_format($Terminal);

// Create the Pie Graph. 
$graph = new PieGraph(835,325);
// $graph->SetMargin(75,50,30,150);
// $graph->SetMarginColor('black');

$theme_class= new VividTheme;
$graph->SetTheme($theme_class);

// Set A title for the plot
// $graph->title->Set("Period Cost Breakdown");

// Create
$p1 = new PiePlot3D($data);
$graph->Add($p1);

$legends = array('Total Energy Charges (%d)','Total Demand Charges (%d)','Taxes and Other Fees (%d)');

// $p1->SetLegends($legends);
$p1->ShowBorder();
$p1->SetColor('black');
$p1->SetSliceColors(array('#6CBAEA','#2A4089','#7BE000','red'));
$p1->value->SetFont(FF_LUCSANSUN,FS_NORMAL,12);
$p1->ExplodeAll();
$p1->SetLabelType(PIE_VALUE_ABS);
$p1->SetLabelMargin(10);
$p1->SetLabels(array("Ferry: $$Ferry","Articulated Tug and Barge: $$Tug_Barge","US Government Cargo Vessel: $$Govn_Cargo_Vessel","Terminal: $$Terminal",),1);
$p1->value->SetColor('black');
$graph->Stroke($report_graph);
?>	<table align='center'>	
		<tr>
			<th align="center" style='background:none; border-bottom:none; border-top:none;'><img src="<?php echo $report_graph; ?>" width="835" height="325" border="0"></th>
		</tr>
	</table>