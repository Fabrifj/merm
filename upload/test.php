<?php
require './src/db_helpers.php';
require './src/functions.php';
require './class_objects/logger.php';
require './class_objects/records.php';
require './class_objects/utility.php';



echo "Start query <br>";
$devicetablename = "Cape_Kennedy_001EC6001433__device002_class103";
$loopName = "Cape Kennedy";
$aquisuitetable = "";
$log = "Keylogger";
$timezone = "EDT";
$utilityData = [
    "Utility" => 'Entergy_NO_Rates',         // utility
    "Rate" => 'Rate1',                      // rate
    "Custom" => 100,                        // customerCharge
    "Demand_Rate_1" => 10,                  // summerPeakCostKw
    "Demand_Rate_2" => 8,                   // nonSummerPeakCostKw
    "Demand_Rate_3" => 5,                   // offPeakCostKwH
    "Energy_Rate_1" => 0.1,                 // summerPeakCostKwH
    "Energy_Rate_2" => 0.8,                 // nonSummerPeakCostKwH
    "Energy_Rate_3" => 0.5                  // offPeakCostKwH
];

$logger = new Logger($loopName);
if($_REQUEST['MODE'] == "dev"){
    $mode = "dev";
    echo "Dev mode";
}else{
    $mode = "host";
    echo "Host mode";
}

//db_connect($logger,$mode );


$ship_records = get_ships_records($logger,$timezone,$loopName,$devicetablename);
$last_record = db_fetch_last_ship_record($log, $loopName);

if(!$last_record){
    $last_records = [];
}else{
    $last_records = get_last_four_records($logger,$timezone,$loopName );
}

echo count($last_records) . "----" . count($ship_records) . "<br>";
echo "Create utility class <br>";
$utilityRate = create_utility_class($logger,$utilityData);
echo "Create calculate kw <br>";
$ship_records = calculate_kw($logger,$utilityRate,$last_records,$ship_records);

echo "Create calculate cost <br>";
$ship_records =calculate_cost($logger, $utilityRate, $ship_records);


echo "Create populate table <br>";


$erros = populate_standart_table($logger, $ship_records);

echo "End  erors: " . $erros . "<br>";

?>