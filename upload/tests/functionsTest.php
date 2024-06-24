<?php

// tests/FunctionsTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../class_objects/records.php';
require_once __DIR__ . '/../class_objects/utility.php';
require_once __DIR__ . '/../class_objects/logger.php';

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
        $timeZone =  "EDT";
        $timeZone = new DateTimeZone($timeZone);

        $shipDatetime = new DateTime('2024-06-12 14:30:00');

        $timeRange = new TimeRange($timeZone,'12:00:00','18:00:00');

        $this->assertTrue(is_peak_hours($shipDatetime, $timeRange->getStartTime(), $timeRange->getEndTime()));

        $shipDatetime = new DateTime('2024-06-12 20:00:00');
        $this->assertFalse(is_peak_hours($shipDatetime, $timeRange->getStartTime(), $timeRange->getEndTime()));
    }
}

class PeakTest extends TestCase {
    public function testIsPeak() {
        $utilityData = [
            "Utility" => 'SCE&G', "Rate" => 24,"Customer_Charge" => 987.5, 
            "Summer_Peak_Demand_kW" => 18.22,
            "Non_Summer_Peak_Demand_kW" => 12.76,
            "Off_Peak_Demand_kW" => 5.57,
            "Summer_Peak_kWh" => 0.08598,
            "Non_Summer_Peak_kWh" => 0.06235,
            "Off_Peak_kWh" => 0.04764,
            "Peak_Time_Summer_Start" => '13:00:00',
            "Peak_Time_Summer_Stop" => '21:00:00',
            "Peak_Time_Non_Summer_Start" => '06:00:00',
            "Peak_Time_Non_Summer_Stop" => '12:00:00',
            "Peak_Time_Non_Summer_Start2" => '17:00:00',
            "Peak_Time_Non_Summer_Stop2" => '21:00:00',
        ];
        $ships_records_tb =[
            ['time' => '2021-12-10 08:55:00','error' => 0,'Energy_Consumption' => 184826]
        ];
        $deviname = "test";
        $timezone = 'EDT';
        $loopName = "Cape Kennedy";
        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $utilityRate = create_utility_class($logger,$utilityData);
        $shipRecord = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $this->assertTrue(is_peak($shipRecord[0], $utilityRate));
        

        $ships_records_tb =[
            ['time' => '2021-12-10 12:55:00','error' => 0,'Energy_Consumption' => 184826]
        ];
        $shipRecord = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);

        $this->assertTrue(is_peak($shipRecord[0], $utilityRate));
    }
}


/// Functions tests


class CalculateKwTest1 extends TestCase {
    public function testCalculateKw() {
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
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $utilityRate = create_utility_class($logger,$utilityData);
        $last_ship_records = RecordFactory::createRecords($timezone,$deviname, $last_ships_records_tb,$loopName);
        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);

        $result = calculate_kw($logger,$utilityRate, $last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Is on peak time
        $this->assertEquals(304, $result[3]->getOffPeakKw());
        $this->assertEquals(26, $result[3]->getOffPeakKwh());
    }
}

class CalculateKwTest2 extends TestCase {
    public function testCalculateKw() {
        $utilityData = [
            "Utility" => 'SCE&G', "Rate" => 24,"Customer_Charge" => 987.5, 
            "Summer_Peak_Demand_kW" => 18.22,
            "Non_Summer_Peak_Demand_kW" => 12.76, // 
            "Off_Peak_Demand_kW" => 5.57, 
            "Summer_Peak_kWh" => 0.08598,
            "Non_Summer_Peak_kWh" => 0.06235, ///
            "Off_Peak_kWh" => 0.04764,
            "Peak_Time_Summer_Start" => '13:00:00',
            "Peak_Time_Summer_Stop" => '21:00:00',
            "Peak_Time_Non_Summer_Start" => '06:00:00',
            "Peak_Time_Non_Summer_Stop" => '12:00:00',
            "Peak_Time_Non_Summer_Start2" => '17:00:00',
            "Peak_Time_Non_Summer_Stop2" => '21:00:00',
        ];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 14:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 14:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 14:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];
        $deviname = "test";
        $timezone = "EDT";
        $last_ship_records = [];
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);
        $utilityRate = create_utility_class($logger,$utilityData);
        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);

        $result = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Is on offpeak time
        $this->assertEquals(304, $result[3]->getPeakKw());
        $this->assertEquals(26, $result[3]->getPeakKwh());
    }
}
class CalculateCost1 extends TestCase {
    public function testCalculateCost() {
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
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);
        $last_ship_records = RecordFactory::createRecords($timezone,$deviname, $last_ships_records_tb,$loopName);
        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);

        $result = calculate_cost($logger,$utilityRate,$ship_records);

        $this->assertCount(4, $result);

        // Is on offpeak time
        $this->assertEquals(1520, $result[3]->getOffCostKw());
        $this->assertEquals(13, $result[3]->getOffCostKwH());
    }
}
class CalculateCost2 extends TestCase {
    public function testCalculateCost() {
        $utilityData = [
            "Utility" => 'SCE&G', "Rate" => 24,"Customer_Charge" => 987.5, 
            "Summer_Peak_Demand_kW" => 18.22,  ///
            "Non_Summer_Peak_Demand_kW" => 12.76,
            "Off_Peak_Demand_kW" => 5.57,
            "Summer_Peak_kWh" => 0.08598,   ///
            "Non_Summer_Peak_kWh" => 0.06235,
            "Off_Peak_kWh" => 0.04764,
            "Peak_Time_Summer_Start" => '13:00:00',
            "Peak_Time_Summer_Stop" => '21:00:00',
            "Peak_Time_Non_Summer_Start" => '06:00:00',
            "Peak_Time_Non_Summer_Stop" => '12:00:00',
            "Peak_Time_Non_Summer_Start2" => '17:00:00',
            "Peak_Time_Non_Summer_Stop2" => '21:00:00',
        ];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 14:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 14:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 14:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $last_ship_records = [];
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);

        $result = calculate_cost($logger,$utilityRate,$ship_records);

        $this->assertCount(4, $result);

        // Is on offpeak time
        $this->assertEquals(5684.64, $result[1]->getCostKw());
        $this->assertEquals(2.23548, $result[1]->getCostKwH());
    }
}

?>