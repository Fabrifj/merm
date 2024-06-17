<?php


// Function to get ship records
function get_ships_records($log, $timezone, $LOOPNAME, $devicetablename) {
    try {
        // Fetch the last ship record for the given loop name
        $last_record = db_fetch_last_ship_record($log, $LOOPNAME);

        // Parse device table name to extract device name
        $parts = explode('_', $devicetablename);

        if (count($parts) >= 5) {
            $deviname = $parts[4];
        } else {
            $log->logInfo("Invalid device table name: $devicetablename");
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
            $log->logInfo("Query failed for table $devicetablename.");
            return [];
        }

        // Create record objects from the fetched records
        try {
            $ships_records_ob = RecordFactory::createRecords($timezone, $deviname, $ships_records_tb, $LOOPNAME);
        } catch (Exception $e) {
            $log->logInfo("Error creating records: " . $e->getMessage());
            return [];
        }

        return $ships_records_ob;

    } catch (Exception $e) {
        // Log any unexpected exceptions
        $log->logInfo("Unexpected error: " . $e->getMessage());
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
            $log->logInfo("Query failed for last four records of $LOOPNAME.");
            return [];
        }

        // Create record objects from the fetched records
        try {
            $last_records_ob = RecordFactory::createRecords($timezone, $deviname, $last_records, $LOOPNAME);
        } catch (Exception $e) {
            $log->logInfo("Error creating records: " . $e->getMessage());
            return [];
        }

        return $last_records_ob;

    } catch (Exception $e) {
        // Log any unexpected exceptions
        $log->logInfo("Unexpected error: " . $e->getMessage());
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
    $timezone = "EDT";

    try {
        // Create utility rate instance
        $utilityRate = UtilityRateFactory::createStandardUtilityRate($timezone, $utility);
    } catch (Exception $e) {
        $log->logInfo("Error creating utility rate: " . $e->getMessage());
        return null;
    }
    return $utilityRate;
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
    }

    // Calculate kW and kWh for each record
    for (; $index < count($concatenated_records); $index++) {
        $current_record = $concatenated_records[$index];   

        $power_kwh = abs($current_record->getEnergyConsumption() - $concatenated_records[$index - 1]->getEnergyConsumption());
        
        $previous_record = $newRecords ? $concatenated_records[$index - 1] : $concatenated_records[$index - $diference];
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
            if (is_summer($ship_record->getTime())) {
                $cost_kw = $ship_record->getPeakKw() * $utility->getSummerPeakCostKw() + $ship_record->getOffPeakKw() * $utility->getOffPeakCostKw();
            } else {
                $cost_kw = $ship_record->getPeakKw() * $utility->getNonSummerPeakCostKw() + $ship_record->getOffPeakKw() * $utility->getOffPeakCostKw();
            }
            $ship_record->setCostKw($cost_kw);
        } catch (Exception $e) {
            $log->logInfo("Error calculating cost for record: " . json_encode($ship_record) . ". Error: " . $e->getMessage());
        }
    }
    return $ship_records;
}

// Function to populate standard table
function populate_standart_table($log, $ship_records) {
    try {
        $errors = db_insert_standar_records($log, $ship_records);
        return $errors;
    } catch (Exception $e) {
        $log->logInfo("Error populating standard table: " . $e->getMessage());
        return -1; // Return an error indicator
    }
}
?>
