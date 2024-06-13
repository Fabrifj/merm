<?php

function get_ships_records($timezone,$LOOPNAME,$devicetablename) {

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
        $ships_records_ob = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];

    }

    return $ships_records_ob;


}


function get_last_four_records($timezone,$LOOPNAME ){
    $deviname = 'standard';
    $last_records = db_fetch_last_four_ship_records($LOOPNAME);
    if (!$last_records) {
        echo "Query failed.";
        return [];
    }

    try {
        $last_records_ob = RecordFactory::createRecords($timezone,$deviname, $last_records);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];

    }
    return $last_records_ob;

}
function get_time_dif(DateTime $time1,DateTime $time2){
    // Calculate difference 
    $interval = $time1->diff($time2);

    // Convert to minuts
    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;

    return $minutes;
}

function is_summer(DateTime $date) {
    $year = $date->format('Y');
    $summerStart = new DateTime("{$year}-06-01");
    $summerEnd = new DateTime("{$year}-10-01");

    if ($date >= $summerStart && $date <= $summerEnd) {
        return true;
    } else {
        return false;
    }
}

function is_peak_hours(DateTime $shipDatetime, DateTime $utilityMinDatetime, DateTime $utilityMaxDatetime) {
    $shipHour = (int)$shipDatetime->format('H');
    $utilityMinHour = (int)$utilityMinDatetime->format('H');
    $utilityMaxHour = (int)$utilityMaxDatetime->format('H');

    return ($shipHour >= $utilityMinHour && $shipHour <= $utilityMaxHour);
}

function is_peak(RecordsTypeStandard $shipRecord, UtilityRate $utility) {
    $dateTime = $shipRecord->getTime();
    $is_peak = false;

    if (is_summer($dateTime)) {
        $peakSummer = $utility->getPeakTimeSummer();
        foreach ($peakSummer as $value) {
            if (is_peak_hours($dateTime, $value->getStartTime(), $value->getEndTime())) {
                $is_peak = true;
                break; 
            }
        }
    } else {
        $peakNonSummer = $utility->getPeakTimeNonSummer();
        foreach ($peakNonSummer as $value) {
            if (is_peak_hours($dateTime, $value->getStartTime(), $value->getEndTime())) {
                $is_peak = true;
                break; 
            }
        }
    }

    return $is_peak;
}

function calculate_kw($utility,$last_ship_records, $ship_records){
    // set up time 
    date_default_timezone_set("UTC");    
    //
    try {
        $utilityRate = UtilityRateFactory::createStandardUtilityRate($utility);

    }catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }

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
        if(is_peak( $concatenated_records[$index], $utilityRate)){
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
function calculate_cost($utility, $ship_records){
    foreach ($shipRecords as $ship_record) {
        if(is_summer($shipRecord->getTime())){
            $ship_record->setCostKw($shipRecord->getPeakKw() * $utility->getSummerPeakCostKw() + $shipRecord->setOffPeakKwh() * $utility->getOffPeakCostKw());
        }else{
            $ship_record->setCostKw($shipRecord->getPeakKw() * $utility->getNonSummerPeakCostKw() + $shipRecord->setOffPeakKwh() * $utility->getOffPeakCostKw());
        }
    }
    return $ship_records;
}

function populate_standart_table(){

}






?>