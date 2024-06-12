<?php

function get_ships_records($LOOPNAME,$devicetablename) {

    $last_record=db_fetch_last_ship_record($LOOPNAME);


    $parts = explode('_', $devicetablename);

    if (count($parts) >= 3) {
        $deviname = $parts[4];

        echo "device name: " . $deviname . "<br>" ;
    } else {
        echo "Invalid device table name.";
        return;
    }

    if(!$last_record){
        echo "No hay registro previo" . "<br>";
        $ships_records_tb = db_fetch_table_records($devicetablename);
    }else{
        echo "Ultimo registro" . $last_record . "<br>";
        $ships_records_tb = db_fetch_records_after_time($devicetablename,$last_record);

    }


    if (!$ships_records_tb) {
        echo "Query failed.";
        return [];
    }

    try {
        $ships_records_ob = RecordFactory::createRecords($deviname, $ships_records_tb);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];

    }

    return $ships_records_ob;


}


function get_time_dif($time1, $time2){
    $datetime1 = new DateTime($time1);
    $datetime2 = new DateTime($time2);

    // Calculate difference 
    $interval = $datetime2->diff($datetime1);

    // Convert to minuts
    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    return $minutes;
}


function get_last_four_records($LOOPNAME ){
    $deviname = 'standard';
    $last_records = db_fetch_last_four_ship_records($LOOPNAME);
    if (!$last_records) {
        echo "Query failed.";
        return [];
    }

    try {
        $last_records_ob = RecordFactory::createRecords($deviname, $last_records);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];

    }
    return $last_records_ob;

}
function its_peak($time, $min_time, $max_time){
    $time = new DateTime($time);
    $min_time = new DateTime($min_time);
    $max_time = new DateTime($max_time);

    if($time < $max_time && $time > $min_time){
        return true;
    }
    else {
        return false;
    }
}

function calculate_kw($last_ship_records, $ship_records){
    // set up time 
    date_default_timezone_set("UTC");    
    //

    $concatenated_records = array_merge($last_ship_records, $ship_records);
    $index = count($last_ship_records);
    $diference = count($last_ship_records);

    $newRecords = false;
    if (!$last_ship_records) {
        echo "Table empty.";
        $index = 1;
        $newRecords = true; 
    }
    // Calculate for each record kw and kwh 
    for ( ;$index <count($concatenated_records) ; $index++) { 
        $power_kwh = abs($concatenated_records[$index]->getEnergyConsumption() - $concatenated_records[$index - 1]->getEnergyConsumption());
         
        // calculate with different range
        if($newRecords){
            $diff_power_kw = abs($concatenated_records[$index]->getEnergyConsumption() - $concatenated_records[$index - 1]->getEnergyConsumption()) ;
            $diff_time =  get_time_dif($concatenated_records[$index]->getTime(), $concatenated_records[$index - 1]->getTime());   
        }else{
            $diff_power_kw = abs($concatenated_records[$index]->getEnergyConsumption() - $concatenated_records[$index - $diference]->getEnergyConsumption());
            $diff_time =  get_time_dif($concatenated_records[$index]->getTime(), $concatenated_records[$index - $diference]->getTime());  
        }
        $power_kw = ($diff_power_kw / $diff_time) * 60;
        // its peak -- we should improve this.  
        if(false){
            $concatenated_records[$index]->setPeakKw($power_kw);
            $concatenated_records[$index]->setPeakKwh($power_kwh);
        }else{
            $concatenated_records[$index]->setOffPeakKw($power_kw);
            $concatenated_records[$index]->setOffPeakKwh($power_kwh);
        }
        
    }
    if(!$newRecords){
        $concatenated_records = array_slice($concatenated_records, 3);
    }
    return $concatenated_records;


}

function populate_standart_table(){

}






?>