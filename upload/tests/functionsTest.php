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


        // Is on offpeak time
        $this->assertEquals(304, $result[3]->getPeakKw());
        $this->assertEquals(26, $result[3]->getPeakKwh());
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

        // Is on offpeak time
        $this->assertEquals(304, $result[3]->getPeakKw());
        $this->assertEquals(26, $result[3]->getPeakKwh());
        // Is on offpeak time
        $this->assertEquals(5684.64, $result[1]->getCostKw());
        $this->assertEquals(2.23548, $result[1]->getCostKwH());
    }
}


class FullProcessSCE extends TestCase {
    public function testFullProcessSCE() {
        $utilityData = [
            "Utility" => "SCE&G",
            "Rate" => 24,
            "Customer_Charge" => 987.5,
            "Summer_Peak_Demand_kW" => 18.22,
            "Non_Summer_Peak_Demand_kW" => 12.76,
            "Off_Peak_Demand_kW" => 5.57,
            "Summer_Peak_kWh" => 0.08598,
            "Non_Summer_Peak_kWh" => 0.06235,
            "Off_Peak_kWh" => 0.04764,
            "Franchise_Fee" => 0.04,
            "Summer_Start" => "2014-06-01 00:00:00",
            "Summer_End" => "2014-10-01 00:00:00",
            "Peak_Time_Summer_Start" => "13:00:00",
            "Peak_Time_Summer_Stop" => "21:00:00",
            "Peak_Time_Non_Summer_Start" => "06:00:00",
            "Peak_Time_Non_Summer_Stop" => "12:00:00",
            "Peak_Time_Non_Summer_Start2" => "17:00:00",
            "Peak_Time_Non_Summer_Stop2" => "21:00:00",
            "MayOct_Start" => "13:00:00",
            "MayOct_End" => "21:00:00",
            "Rate_Date_Start" => "2014-05-01 00:00:00",
            "Rate_Date_End" => "2014-10-31 23:55:00"
        ];
        
        
        $last_ship_records = [];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-07-10 14:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-07-10 14:50:00','error' => 0,'Energy_Consumption' => 184802],
            ['time' => '2021-07-10 15:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 15:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 15:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 15:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);
        // Is on offpeak time
        $this->assertEquals(304, $ship_records[6]->getPeakKw());
        $this->assertEquals(26, $ship_records[6]->getPeakKwh());

        $result = calculate_cost($logger,$utilityRate,$ship_records);


        // 0.08598 , 18.22
        // Is on offpeak time
        $this->assertEquals(5538.88, $result[6]->getCostKw(),'', 0.01);
        $this->assertEquals(2.23548, $result[6]->getCostKwH(),'', 0.01);
    }
}
class FullProcessNAV extends TestCase {
    public function testFullProcessNAV() {
        $utilityData = [
            "Utility" => "Nav Fed",
            "Rate" => 1,
            "Cost_kWh" => 0.099
        ];
        
        
        $last_ship_records = [];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-07-10 14:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-07-10 14:50:00','error' => 0,'Energy_Consumption' => 184802],
            ['time' => '2021-07-10 15:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 15:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 15:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 15:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);
        // Is on offpeak time
        $this->assertEquals(26, $ship_records[6]->getOffPeakKwh());
        $this->assertEquals(304, $ship_records[6]->getOffPeakKw());

        $result = calculate_cost($logger,$utilityRate,$ship_records);

        // 0.099 , 0
        // Is on offpeak time
        $this->assertEquals(0, $result[6]->getOffCostKw(),'', 0.01);
        $this->assertEquals(2.574, $result[6]->getOffCostKwH(),'', 0.01);
    }
}

class FullProcessENR extends TestCase {
    public function testFullProcessENR() {
        $utilityData = [
            "Utility" => "Entergy_NO_Rates",
            "Rate" => "le-hlf-8",
            "Energy_Rate_1" => 0.05077,
            "Energy_Rate_2" => 0.02741,
            "Energy_Rate_3" => 0.02649,
            "Energy_Rate_4" => 0.02625,
            "Energy_Rate_5" => 0.02173,
            "Demand_Rate_1" => 508.06,
            "Demand_Rate_2" => 8.57,
            "Demand_Rate_3" => 8.04,
            "Demand_Rate_4" => 7.68,
            "Rider_Fuel_kWh" => 0.0363804,
            "Rider_Capacity_kWh" => 0.0104798,
            "Rider_EAC_kWh" => 0.000001,
            "Street_Use_Franchise_Fee" => 1731.93,
            "Storm_Securitization_Fee" => 514.126,
            "Formula_Rate_Plan_Percentage" => -0.105278,
            "MISO_Recovery_Percentage" => 0.046862,
            "Rate_Date_Start" => "2017-12-01 00:00:00",
            "Rate_Date_End" => "2050-12-01 00:00:00"
        ];
        
        
        $last_ship_records = [];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-07-10 14:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-07-10 14:50:00','error' => 0,'Energy_Consumption' => 184802],
            ['time' => '2021-07-10 15:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 15:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 15:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 15:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);
        // Is on offpeak time
        $this->assertEquals(304, $ship_records[6]->getOffPeakKw());
        $this->assertEquals(26, $ship_records[6]->getOffPeakKwh());

        $result = calculate_cost($logger,$utilityRate,$ship_records);

        echo "Calculate ENR: Price: ".$utilityRate->getCostKw("TEST",$result[6]->getOffCostKw())." Cost Kw: ".$result[6]->getOffCostKw()."-".$result[6]->getCostKw();

        // 0.05077 , 11.322
        // Is on offpeak time
        $this->assertEquals(2334.71, $result[6]->getOffCostKw(),'', 0.01);
        $this->assertEquals(1.32002, $result[6]->getOffCostKwH(),'', 0.01);
    }
}
class FullProcessVDR extends TestCase {
    public function testFullProcessVDR() {
        $utilityData = [
            "Utility" => "Virginia_Dominion_Rates",
            "Rate" => "GS_3",
            "Customer_Charge" => 119.8,
            "Demand_rkVA" => 0.15,
            "Distribution_Demand" => 2.12,
            "ESS_Adjustment_Charge" => -0.64,
            "Peak_kWh" => 0.00404,
            "Off_Peak_kWh" => 0.00272,
            "Peak_kW" => 11.322,
            "Off_Peak_kW" => 0.656,
            "Fuel_Charge" => 0.02927,
            "Rider_R_Peak_kW" => 3.78,
            "Rider_S_Peak_kW" => 0.907,
            "Rider_T_Peak_kW" => 1.359,
            "Rider_R_Credit" => -0.378,
            "Rider_S_Credit" => -0.416,
            "Rider_T_Credit" => -0.527,
            "Sales_kWh" => 0.0004,
            "Tax_Rate_1" => 0.00155,
            "Tax_Rate_2" => 0.00099,
            "Tax_Rate_3" => 0.00075,
            "Utility_tax" => 80,
            "Summer_Start" => "2011-06-01 00:00:00",
            "Summer_End" => "2011-10-01 00:00:00",
            "Peak_Time_Summer_Start" => "10:00:00",
            "Peak_Time_Summer_Stop" => "22:00:00",
            "Peak_Time_Non_Summer_Start" => "07:00:00",
            "Peak_Time_Non_Summer_Stop" => "22:00:00",
            "Rate_Date_Start" => "2007-01-01 00:00:00",
            "Rate_Date_End" => "2014-01-24 23:55:00"
        ];
        
        $last_ship_records = [];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-07-10 14:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-07-10 14:50:00','error' => 0,'Energy_Consumption' => 184802],
            ['time' => '2021-07-10 15:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 15:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 15:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 15:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);
        // Is on offpeak time
        $this->assertEquals(304, $ship_records[6]->getPeakKw());
        $this->assertEquals(26, $ship_records[6]->getPeakKwh());

        $result = calculate_cost($logger,$utilityRate,$ship_records);

        // 0.00404 , 11.322
        // Is on offpeak time
        $this->assertEquals(3441.88, $result[6]->getCostKw(),'', 0.01);
        $this->assertEquals(0.10504, $result[6]->getCostKwH(),'', 0.01);
    }
}
class FullProcessVEP extends TestCase {
    public function testFullProcessVEP() {
        $utilityData = [
            "id" => 1,
            "Utility" => "Virginia_Electric_and_Power_Co",
            "Rate" => "LS_10",
            "Summer_Start" => "2015-05-01 00:00:00",
            "Summer_End" => "2015-09-30 00:00:00",
            "Peak_Time_Summer_Start" => "11:00:00.000",
            "Peak_Time_Summer_Stop" => "21:00:00.000",
            "Peak_Time_Non_Summer_Start_AM" => "06:00:00.000",
            "Peak_Time_Non_Summer_Stop_AM" => "12:00:00.000",
            "Peak_Time_Non_Summer_Start_PM" => "17:00:00.000",
            "Peak_Time_Non_Summer_Stop_PM" => "21:00:00.000",
            "Rate_Date_Start" => "2015-04-29 00:00:00",
            "Rate_Date_End" => "2025-04-29 00:00:00",
            "Customer_Charge" => 131,
            "Peak_kW_Demand_1" => 2.897
        ];
        
        
        $last_ship_records = [];

        $ships_records_tb =[
            ['time' => '2021-07-10 14:40:00','error' => 0,'Energy_Consumption' => 184759],
            ['time' => '2021-07-10 14:45:00','error' => 0,'Energy_Consumption' => 184780],
            ['time' => '2021-07-10 14:50:00','error' => 0,'Energy_Consumption' => 184802],
            ['time' => '2021-07-10 15:00:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-07-10 15:05:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-07-10 15:10:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-07-10 15:15:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];

        $deviname = "test";
        $timezone = "EDT";
        $loopName = "Cape Kennedy";

        $logger = new Logger($loopName);
        $logger->logInfo($loopName);

        $ship_records = RecordFactory::createRecords($timezone,$deviname, $ships_records_tb,$loopName);
        $utilityRate = create_utility_class($logger,$utilityData);

        $ship_records = calculate_kw($logger,$utilityRate,$last_ship_records, $ship_records);
        // Is on offpeak time
        $this->assertEquals(304, $ship_records[6]->getPeakKw());
        $this->assertEquals(26, $ship_records[6]->getPeakKwh());


        echo $utilityRate->getCostKw("summerPeak");
        $result = calculate_cost($logger,$utilityRate,$ship_records);
        // 0.12 , 2.897
        // Is on offpeak time
        $this->assertEquals(880.68, $result[6]->getCostKw(),'', 0.01);
        $this->assertEquals(4.094, $result[6]->getCostKwH(),'', 0.01);
    }
}
?>