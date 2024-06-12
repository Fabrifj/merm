<?php

// tests/FunctionsTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../class_objects/records.php';
require_once __DIR__ . '/../src/functions.php';

class ItsPeakTest extends TestCase {
    public function testItsPeak() {
        $this->assertTrue(its_peak('12:00', '08:00', '18:00'));
        $this->assertFalse(its_peak('07:00', '08:00', '18:00'));
        $this->assertFalse(its_peak('19:00', '08:00', '18:00'));
    }
}




class CalculateKwTest1 extends TestCase {
    public function testCalculateKw() {
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
        $last_ship_records = RecordFactory::createRecords($deviname, $last_ships_records_tb);
        $ship_records = RecordFactory::createRecords($deviname, $ships_records_tb);


        $result = calculate_kw($last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Verifica algunos valores calculados específicos
        $this->assertEquals(304, $result[3]->getOffPeakKw());
        $this->assertEquals(26, $result[3]->getOffPeakKwh());
    }
}

class CalculateKwTest2 extends TestCase {
    public function testCalculateKw() {
        $ships_records_tb =[
            ['time' => '2021-12-10 08:55:00','error' => 0,'Energy_Consumption' => 184826],
            ['time' => '2021-12-10 09:00:00','error' => 0,'Energy_Consumption' => 184852],
            ['time' => '2021-12-10 09:05:00','error' => 0,'Energy_Consumption' => 184876],
            ['time' => '2021-12-10 09:10:00','error' => 0,'Energy_Consumption' => 184902]
        
        ];
        $deviname = "test";
        $last_ship_records = [];
        $ship_records = RecordFactory::createRecords($deviname, $ships_records_tb);


        $result = calculate_kw($last_ship_records, $ship_records);

        $this->assertCount(4, $result);

        // Verifica algunos valores calculados específicos
        $this->assertEquals(312, $result[3]->getOffPeakKw());
        $this->assertEquals(26, $result[3]->getOffPeakKwh());
    }
}
?>