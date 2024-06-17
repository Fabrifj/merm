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
    'SCE&G',        // utiliy
    'Rate1',        // rate
    100,            // customerCharge
    10,             // summerPeakCostKw
    8,              // nonSummerPeakCostKw
    5,              // offPeakCostKw
    '', '', '', '', '', '',  // otros datos irrelevantes
    '14:00:00',        // peakTimeSummer startTime
    '18:00:00',        // peakTimeSummer endTime
    '08:00:00',        // peakTimeNonSummer startTime 1
    '12:00:00',        // peakTimeNonSummer endTime 1
    '13:00:00',        // peakTimeNonSummer startTime 2
    '17:00:00'         // peakTimeNonSummer endTime 2
];

$logger = new Logger($loopName);
if($_REQUEST['MODE'] == "dev"){
    $mode = "dev";
    echo "Dev mode";
}else{
    $mode = "host";
    echo "Host mode";
}

db_connect($logger,"host");


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
db_close()

?>