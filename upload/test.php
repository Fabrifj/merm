<?php
require './src/db_helpers.php';
require './src/functions.php';
require './class_objects/records.php';

db_connect("dev");


echo "Start query <br>";
$devicetablename = "Cape_Kennedy_001EC6001433__device002_class103";
$LOOPNAME = "Cape Kennedy";
$aquisuitetable = "";
$log = "Keylogger";
$timezone = "EDT";
$utility = "SCE&G";

$ship_records = get_ships_records($timezone,$LOOPNAME,$devicetablename);
$last_records = get_last_four_records($timezone,$LOOPNAME );

echo count($last_records) . "----" . count($ship_records) . "<br>";

$records = calculate_kw($utility,$last_records,$ship_records);
foreach ($records as $record) {
    $record_array = $record->getKwValues();
    echo "offPeakKw:" . $record_array[0] . " offPeakKwh:" . $record_array[1] . " PeakKw:" . $record_array[2] . " PeakKwh:" . $record_array[3] . "<br>";
}

db_close()

?>