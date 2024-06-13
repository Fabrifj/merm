<?php

// tests/FunctionsTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../class_objects/records.php';
require_once __DIR__ . '/../class_objects/utility.php';

require_once __DIR__ . '/../src/functions.php';



/// Test of the time managment 
class TimeDifferenceTest extends TestCase {
    public function testGetTimeDif() {
        $time1 = new DateTime('2024-06-12 14:30:00');
        $time2 = new DateTime('2024-06-12 15:45:00');

        $expected = 75; // 1 hour and 15 minutes
        $this->assertEquals($expected, get_time_dif($time1, $time2));
    }
}
class SummerTest extends TestCase {
    public function testIsSummer() {
        $summerDate = new DateTime('2024-07-15');
        $winterDate = new DateTime('2024-12-15');

        $this->assertTrue(is_summer($summerDate));
        $this->assertFalse(is_summer($winterDate));
    }
}
class PeakHoursTest extends TestCase {
    public function testIsPeakHours() {
        $shipDatetime = new DateTime('2024-06-12 14:30:00');

        $timeRange = new TimeRange('12:00:00','18:00:00');

        $this->assertTrue(is_peak_hours($shipDatetime, $timeRange->getStartTime(), $timeRange->getEndTime()));

        $shipDatetime = new DateTime('2024-06-12 20:00:00');
        $this->assertFalse(is_peak_hours($shipDatetime, $timeRange->getStartTime(), $timeRange->getEndTime()));
    }
}

class PeakTest extends TestCase {
    public function testIsPeak() {
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
        $ships_records_tb =[
            ['time' => '2021-12-10 08:55:00','error' => 0,'Energy_Consumption' => 184826]
        ];
        $deviname = "test";
        $timezone = 'EDT';

        $shipRecord = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb);
        $utilityRate = UtilityRateFactory::createStandardUtilityRate($utilityData);
        $this->assertTrue(is_peak($shipRecord[0], $utilityRate));
        

        $ships_records_tb =[
            ['time' => '2021-12-10 12:55:00','error' => 0,'Energy_Consumption' => 184826]
        ];
        $shipRecord = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb);

        $this->assertTrue(is_peak($shipRecord[0], $utilityRate));
    }
}


/// Functions tests


class CalculateKwTest1 extends TestCase {
    public function testCalculateKw() {
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

        $last_ships_records_tb =[
            ['time' => '2021-12-10 08:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-12-10 08:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-12-10 08:50:00','error' => 0,'Energy_Consumption' => 184802]
        ];
        $ships_records_tb =[
            ['time' => '2021-12-10 08:55:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-12-10 09:00:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-12-10 09:05:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-12-10 09:10:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];
        $deviname = "test";
        $timezone = "EDT";
        $last_ship_records = RecordFactory::createRecords($timezone,$deviname, $last_ships_records_tb);
        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb);


        $result = calculate_kw($utilityData, $last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Is on peak time
        $this->assertEquals(304, $result[3]->getPeakKw());
        $this->assertEquals(26, $result[3]->getPeakKwh());
    }
}

class CalculateKwTest2 extends TestCase {
    public function testCalculateKw() {
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

        $ships_records_tb =[
            ['time' => '2021-12-10 00:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-12-10 00:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-12-10 00:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-12-10 00:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];
        $deviname = "test";
        $timezone = "EDT";
        $last_ship_records = [];
        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb);


        $result = calculate_kw($utilityData,$last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Is on offpeak time
        $this->assertEquals(312, $result[3]->getOffPeakKw());
        $this->assertEquals(26, $result[3]->getOffPeakKwh());
    }
}
?>