<?php


// Function to get ship records
function get_ships_records($log, $timezone, $LOOPNAME, $devicetablename) {
    try {
        // Fetch the last ship record for the given loop name
        $last_record = db_fetch_last_ship_record($log, $LOOPNAME);

        $pattern = '/device\d+/';

        if (preg_match($pattern, $devicetablename, $matches)) {
            $deviname = $matches[0];
            $log->logInfo("Device Name".$deviname);
        } else {
            $log->logError("Invalid device table name: $devicetablename");
            return [];
        }

        // Fetch records from the device table based on the last record timestamp
        if (!$last_record) {
            $log->logInfo("No previous record from $LOOPNAME. Fetching all records.");
            $ships_records_tb = db_fetch_table_records($log, $devicetablename);
        } else {
            $log->logInfo("Last record from $LOOPNAME: $last_record. Fetching records after this time.");
            $ships_records_tb = db_fetch_records_after_time($log, $devicetablename, $last_record);
        }

        // Check if fetching records was successful
        if (!$ships_records_tb) {
            $log->logError("Query failed for table $devicetablename.");
            return [];
        }

        // Create record objects from the fetched records
        try {
            $ships_records_ob = RecordFactory::createRecords($timezone, $deviname, $ships_records_tb, $LOOPNAME);
        } catch (Exception $e) {
            $log->logError("Error creating records: " . $e->getMessage());
            return [];
        }

        return $ships_records_ob;

    } catch (Exception $e) {
        // Log any unexpected exceptions
        $log->logError("Unexpected error: " . $e->getMessage());
        return [];
    }
}

// Function to get the last four ship records
function get_last_four_records($log, $timezone, $LOOPNAME) {
    try {
        $deviname = 'standard';

        // Fetch the last four ship records for the given loop name
        $last_records = db_fetch_last_three_ship_records($log, $LOOPNAME);

        // Check if fetching records was successful
        if (!$last_records) {
            $log->logError("Query failed for last four records of $LOOPNAME.");
            return [];
        }

        // Create record objects from the fetched records
        try {
            $last_records_ob = RecordFactory::createRecords($timezone, $deviname, $last_records, $LOOPNAME);
        } catch (Exception $e) {
            $log->logError("Error creating records: " . $e->getMessage());
            return [];
        }

        return $last_records_ob;

    } catch (Exception $e) {
        // Log any unexpected exceptions
        $log->logError("Unexpected error: " . $e->getMessage());
        return [];
    }
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
function create_utility_class($log,$utility){
    $timezone = "EDT";/// cuidado

    try {
        // Create utility rate instance
        $utilityRate = UtilityRateFactory::createStandardUtilityRate($log,$timezone, $utility);
    } catch (Exception $e) {
        $log->logError("Error creating utility rate: " . $e->getMessage());
        return null;
    }
    return $utilityRate;
}
function areDatesInSameMonth($date1, $date2) {
    // Extract the year and month from both dates
    $year1 = $date1->format('Y');
    $month1 = $date1->format('m');
    $year2 = $date2->format('Y');
    $month2 = $date2->format('m');
    
    // Check if both dates are in the same year and month
    if ($year1 == $year2 && $month1 == $month2) {
        return true;
    } else {
        return false;
    }
}
// Function to calculate kW and kWh
function calculate_kw($log, $utilityRate, $last_ship_records, $ship_records) {
    // Set the timezone to UTC
    date_default_timezone_set("UTC");

    // Merge last ship records with current ship records
    $concatenated_records = array_merge($last_ship_records, $ship_records);
    $index = count($last_ship_records);
    $diference = count($last_ship_records);

    $newRecords = false;
    if (empty($last_ship_records)) {
        $log->logInfo("Table empty.");
        $index = 1;
        $newRecords = true;
        $diference = 1;
    }

    // Calculate kW and kWh for each record
    for (; $index < count($concatenated_records); $index++) {
        $current_record = $concatenated_records[$index];   

        $power_kwh = abs($current_record->getEnergyConsumption() - $concatenated_records[$index - 1]->getEnergyConsumption());
        // Calculate accumulation 
        if(areDatesInSameMonth($current_record->getTime(),$concatenated_records[$index - 1]->getTime() )){
            $current_record->setAccumulation($concatenated_records[$index - 1]->getAccumulation()+$power_kwh);
        }

        $previous_record = $concatenated_records[$index - $diference];
        $diff_power_kw = abs($current_record->getEnergyConsumption() - $previous_record->getEnergyConsumption());
        $diff_time = get_time_dif($current_record->getTime(), $previous_record->getTime());

        $power_kw = ($diff_power_kw / $diff_time) * 60;

        if (is_peak($current_record, $utilityRate)) {
            $current_record->setPeakKw($power_kw);
            $current_record->setPeakKwh($power_kwh);
        } else {
            $current_record->setOffPeakKw($power_kw);
            $current_record->setOffPeakKwh($power_kwh);
        }
        if($diference == 1 || $diference ==2){
            $diference ++;
        }
        
    }

    // Trim the concatenated records if not new records
    if (!$newRecords) {
        $concatenated_records = array_slice($concatenated_records, 3);
    }

    return $concatenated_records;
}

// Function to calculate cost
function calculate_cost($log, $utility, $ship_records) {
    foreach ($ship_records as $ship_record) {
        try {
            $cost_kw = 0;
            $cost_kwH = 0;
            if (is_summer($ship_record->getTime())) {
                $cost_kw = $ship_record->getPeakKw() * $utility->getCostKw("summerPeak", $ship_record->getPeakKw());
                $off_cost_kw = $ship_record->getOffPeakKw() * $utility->getCostKw("offPeak",$ship_record->getOffPeakKw());
                $cost_kwH = $ship_record->getPeakKwH() * $utility->getCostKwH("summerPeak",$ship_record->getAccumulation());
                $off_cost_kwH = $ship_record->getOffPeakKwH() * $utility->getCostKwH("offPeak",$ship_record->getAccumulation());
            
            } else {

                $cost_kw = $ship_record->getPeakKw() * $utility->getCostKw("nonSumerPeak",$ship_record->getPeakKw());
                $off_cost_kw = $ship_record->getOffPeakKw() * $utility->getCostKw("offPeak",$ship_record->getOffPeakKw());
                $cost_kwH = $ship_record->getPeakKwH() * $utility->getCostKwH("nonSumerPeak",$ship_record->getAccumulation());
                $off_cost_kwH = $ship_record->getOffPeakKwH() * $utility->getCostKwH("offPeak",$ship_record->getAccumulation());
            }
            $ship_record->setCostKw($cost_kw);
            $ship_record->setCostKwH($cost_kwH);
            $ship_record->setOffCostKw($off_cost_kw);
            $ship_record->setOffCostKwH($off_cost_kwH);
        } catch (Exception $e) {
            $log->logError("Error calculating cost for record: " . json_encode($ship_record) . ". Error: " . $e->getMessage());
        }
    }
    return $ship_records;
}

// Function to populate standard table
function populate_standart_table($log, $ship_records) {
    if(count($ship_records)==0){
        return 0;
    }
    try {
        $errors = db_insert_standar_records($log, $ship_records);
        return $errors;
    } catch (Exception $e) {
        $log->logError("Error populating standard table: " . $e->getMessage());
        return -1; // Return an error indicator
    }
}
function isTimeInRange() {
    $currentTime = date('H:i');
    
    $ranges = [
        ['12:30', '12:35'],
        ['00:30', '00:35']
    ];

    foreach ($ranges as $range) {
        $start = $range[0];
        $end = $range[1];

        if ($currentTime >= $start && $currentTime <= $end) {
            return true;
        }
    }

    return false;
}

function get_miss_information_controller($logger){
    if(isTimeInRange()){
        $ships_data =  getLoopInfo();
        foreach ($ships_data as $ship) {
            get_miss_information($logger, $ship["utility"], $ship["loopname"], $ship["tableName"],$ship["timeZone"]);
        }
    }
}


function get_miss_information($logger, $utility, $LOOPNAME, $deviceTableName,$timezone) {
    // Fetch the last record from the ship records table
    $last_record = db_fetch_last_ship_record($logger, $LOOPNAME);
    
    // Check if the last record does not exist or if more than 12 hours have passed since the last record
    if (!$last_record || (strtotime('now') - $last_record) > 12 * 3600) {
        
        try {
            // Fetch the utility rate data
            $utilityData = db_fetch_utility_rate($logger, $utility);
            
            // Fetch the ship records
            $ship_records = get_ships_records($logger, $timezone, $LOOPNAME, $deviceTableName);
            
            // Fetch the last record again after obtaining the ship records
            $last_record = db_fetch_last_ship_record($logger, $LOOPNAME);
            
            // Check if no last record exists, initialize the records array
            if (!$last_record) {
                $last_records = [];
            } else {
                // Get the last four records
                $last_records = get_last_four_records($logger, $timezone, $LOOPNAME);
            }
            
            $logger->logInfo("Creating utility class");
            // Create an instance of the utility rate class
            $utilityRate = create_utility_class($logger, $utilityData[0]);

            $logger->logInfo("Calculating kWh and kW");
            // Calculate kWh and kW
            $ship_records = calculate_kw($logger, $utilityRate, $last_records, $ship_records);

            $logger->logInfo("Calculating cost");
            // Calculate the cost
            $ship_records = calculate_cost($logger, $utilityRate, $ship_records);

            $logger->logInfo("Populating Standard table");
            // Populate the standard table
            $errors = populate_standard_table($logger, $ship_records);

            $logger->logInfo("End errors: " . $errors);
        } catch (Exception $e) {
            $logger->logError('Exception captured: ' . $e->getMessage());
        }
    }
}

function getLoopInfo() {
    // Array representing the table
    $data = [
        [
            'loopname' => 'Cape_Decision',
            'utility' => '`SCE&G_Rates`',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Decision_001EC6000AD8__device001_class2'
        ],
        [
            'loopname' => 'Cape_Diamond',
            'utility' => '`SCE&G_Rates`',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Diamond_001EC6000AB7__device248_class103'
        ],
        [
            'loopname' => 'Cape_Domingo',
            'utility' => '`SCE&G_Rates`',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Domingo_001EC6000ACB__device001_class2'
        ],
        [
            'loopname' => 'Cape_Douglas',
            'utility' => '`SCE&G_Rates`',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Douglas_001EC6000ABC__device001_class2'
        ],
        [
            'loopname' => 'Cape_Ducato_ECR',
            'utility' => '`SCE&G_Rates`',
            'timeZone' => 'US/Central',
            'tableName' => 'Cape_Ducato_ECR_001EC6000ABD__device001_class2'
        ],
        [
            'loopname' => 'Cape_Kennedy',
            'utility' => 'Entergy_NO_Rates',
            'timeZone' => 'US/Central',
            'tableName' => 'Cape_Kennedy_001EC6001433__device002_class103'
        ],
        [
            'loopname' => 'Cape_Race',
            'utility' => 'Virginia_Electric_and_Power_Co',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Race_001EC600278E__device001_class2'
        ],
        [
            'loopname' => 'Cape_Rise',
            'utility' => 'Virginia_Electric_and_Power_Co',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Rise_001EC6002792__device001_class2'
        ],
        [
            'loopname' => 'Cape_Ray',
            'utility' => 'Virginia_Electric_and_Power_Co',
            'timeZone' => 'US/Eastern',
            'tableName' => 'Cape_Ray_001EC6002828__device001_class2'
        ]
    ];

    return $data;
}


?>
