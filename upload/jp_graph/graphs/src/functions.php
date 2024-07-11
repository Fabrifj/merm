<?php
function makeDate($dateValue)
{
    $my_date = date('Y-m-d H:i', strtotime($dateValue));
 	return $my_date;
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


?>