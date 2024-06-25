<?php

class UtilityRate {
    protected $utility;
    protected $rate;
    protected $customerCharge;
    protected $costKw;
    protected $costKwH;
    protected $peakTimeSummer;
    protected $peakTimeNonSummer;

    public function __construct($utility, $rate, $customerCharge, $costKw, $costKwH,
                                $peakTimeSummer, $peakTimeNonSummer) {
        $this->utility = $utility;
        $this->rate = $rate;
        $this->customerCharge = $customerCharge;
        $this->costKw = $costKw;
        $this->costKwH = $costKwH;
        $this->peakTimeSummer = $peakTimeSummer;
        $this->peakTimeNonSummer = $peakTimeNonSummer;

    }


    public function getUtility() {
        return $this->utility;
    }
    public function getCostKw($index, $accumulated) {
        return $this->costKw[$index];
    }
    public function getCostKwH($index, $accumulated) {
        return $this->costKwH[$index];
    }

    public function getPeakTimeSummer() {
        return $this->peakTimeSummer;
    }

    public function getPeakTimeNonSummer() {
        return $this->peakTimeNonSummer;
    }

}
// Class for type 1 utility rate
class ENRUtilityRate extends UtilityRate {
    protected $kwHDemandRanges = [5000, 10000, 15000]; // Define your demand ranges here
    protected $kwDemandRanges = [50, 100, 200]; // Define your demand ranges here

    public function getCostKw($index, $accumulated) {
        // echo "--".$accumulated;
        foreach ($this->kwDemandRanges as $i => $range) {
            if ($accumulated <= $range) {
                return $this->costKw["Demand_Rate_" . ($i + 1)];
            }
        }
        return $this->costKw["Demand_Rate_" . (count($this->kwDemandRanges) + 1)];
    }

    public function getCostKwH($index, $accumulated) {
        foreach ($this->kwHDemandRanges as $i => $range) {
            if ($accumulated <= $range) {
                return $this->costKwH["Energy_Rate_" . ($i + 1)];
            }
        }
        return $this->costKw["Demand_Rate_" . (count($this->kwHDemandRanges) + 1)];
    }
}

// Class for type 2 utility rate
class VEPUtilityRate extends UtilityRate {
    private function getCurrentMonth() {
        return date('n') - 1; // 0-based month (0 for January, 1 for February, ..., 11 for December)
    }

    public function getCostKw($index, $accumulated = null) { 
        echo $this->utility." Test month: ".$month. " index :". $index." Cost : ". $this->costKw[$index] ." \n";
        return $this->costKw[$index]; 
    }

    public function getCostKwH($index, $accumulated = null) {
        $month = $this->getCurrentMonth();
        return $this->costKwH[$month][$index];
    }
}


class TimeRange {
    private $startTime;
    private $endTime;

    public function __construct($timeZone,$startTime, $endTime) {
        $this->startTime = DateTime::createFromFormat('H:i:s', $startTime,$timeZone);
        $this->endTime = DateTime::createFromFormat('H:i:s', $endTime,$timeZone);
        if ($this->startTime === false || $this->endTime === false) {
            throw new Exception("Invalid time format: startTime = $startTime, endTime = $endTime");
        }
    }

    public function getStartTime() {
        return $this->startTime;
    }

    public function getEndTime() {
        return $this->endTime;
    }
}



class UtilityRateFactory {
    private static function formatTime($time) {
        // Trim the time to remove milliseconds if they exist
        return substr($time, 0, 8);
    }

    public static function createStandardUtilityRate($logger,$timeZone, $data) {
        $timeZone = new DateTimeZone($timeZone);
        $utility = $data["Utility"];
        try {
            switch ($utility) {
                case 'SCE&G':
                    $peakTimeSummer = [new TimeRange($timeZone,$data["Peak_Time_Summer_Start"], $data["Peak_Time_Summer_Stop"])];
                    $peakTimeNonSummer = [new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start"], $data["Peak_Time_Non_Summer_Stop"]), new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start2"], $data["Peak_Time_Non_Summer_Stop2"])];
                    $costKw = [
                        "summerPeak"=>$data["Summer_Peak_Demand_kW"],
                        "nonSumerPeak" =>$data["Non_Summer_Peak_Demand_kW"],
                        "offPeak" =>$data["Off_Peak_Demand_kW"] 
                    ];
                    $costKwH = [
                        "summerPeak"=>$data["Summer_Peak_kWh"],
                        "nonSumerPeak" =>$data["Non_Summer_Peak_kWh"],
                        "offPeak" =>$data["Off_Peak_kWh"] 
                    ];
                    
                    return new UtilityRate($data["Utility"], $data["Rate"], $data["Customer_Charge"], 
                                    $costKw,$costKwH, $peakTimeSummer, $peakTimeNonSummer);
                case 'Nav Fed': // Miss Cost_kw
                    $peakTimeSummer = [];
                    $peakTimeNonSummer = [];
                    $costKw = [
                        "summerPeak"=>0,
                        "nonSumerPeak" =>0,
                        "offPeak" =>0 
                    ];
                    $costKwH = [
                        "summerPeak"=>$data["Cost_kWh"],
                        "nonSumerPeak" =>$data["Cost_kWh"],
                        "offPeak" =>$data["Cost_kWh"] 
                    ];
                    return new UtilityRate($data["Utility"],$data["Rate"],0,
                                    $costKw,$costKwH, $peakTimeSummer,$peakTimeNonSummer);
                case 'Entergy_NO_Rates': // Fix What is Whats
                    $peakTimeSummer = [];
                    $peakTimeNonSummer = [];
                    $costKw = [
                        "Demand_Rate_1"=>$data["Demand_Rate_1"],
                        "Demand_Rate_2" =>$data["Demand_Rate_2"],
                        "Demand_Rate_3" =>$data["Demand_Rate_3"],
                        "Demand_Rate_4" =>$data["Demand_Rate_4"]
                    ];
                    $costKwH = [
                        "Energy_Rate_1"=>$data["Energy_Rate_1"],
                        "Energy_Rate_2"=>$data["Energy_Rate_2"],
                        "Energy_Rate_3"=>$data["Energy_Rate_3"],
                        "Energy_Rate_4"=>$data["Energy_Rate_4"],
                        "Energy_Rate_5"=>$data["Energy_Rate_5"],

                    ];
                    return new ENRUtilityRate($data["Utility"],$data["Rate"],0,
                                    $costKw,$costKwH, $peakTimeSummer,$peakTimeNonSummer);

                case 'Virginia_Dominion_Rates':
                    $peakTimeSummer = [new TimeRange($timeZone,$data["Peak_Time_Summer_Start"], $data["Peak_Time_Summer_Stop"])];
                    $peakTimeNonSummer = [new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start"], $data["Peak_Time_Non_Summer_Stop"])];
                    $costKw = [
                        "summerPeak"=>$data["Peak_kW"],
                        "nonSumerPeak" =>$data["Off_Peak_kW"],
                        "offPeak" =>$data["Off_Peak_kWh"] 
                    ];
                    $costKwH = [
                        "summerPeak"=>$data["Peak_kWh"],
                        "nonSumerPeak" =>$data["Peak_kWh"],
                        "offPeak" =>$data["Off_Peak_kWh"] 
                    ];
                    return new UtilityRate($data["Utility"],$data["Rate"],$data["Customer_Charge"],
                                    $costKw,$costKwH, $peakTimeSummer,$peakTimeNonSummer);

                case 'Virginia_Electric_and_Power_Co': ///  Manual fix KWh
                    $peakTimeSummer = [new TimeRange($timeZone,self::formatTime($data["Peak_Time_Summer_Start"]), self::formatTime($data["Peak_Time_Summer_Stop"]))];
                    $peakTimeNonSummer = [new TimeRange($timeZone,self::formatTime($data["Peak_Time_Non_Summer_Start_AM"]), self::formatTime($data["Peak_Time_Non_Summer_Stop_AM"])),
                                    new TimeRange($timeZone,self::formatTime($data["Peak_Time_Non_Summer_Start_PM"]), self::formatTime($data["Peak_Time_Non_Summer_Stop_PM"]))];
                    $costKw = [
                        "summerPeak"=>$data["Peak_kW_Demand_1"],
                        "nonSumerPeak" =>$data["Peak_kW_Demand_1"],
                        "offPeak" =>$data["Peak_kW_Demand_1 "] 
                    ];
                    $costKwH = [
                        [ "summerPeak"=> 0.1071,"nonSumerPeak" => 0.1071 ,"offPeak" => 0.0895 ],
                        [ "summerPeak"=> 0.0967,"nonSumerPeak" => 0.0967 ,"offPeak" => 0.0874 ],
                        [ "summerPeak"=> 0.0933,"nonSumerPeak" => 0.0933 ,"offPeak" => 0.0877 ],
                        [ "summerPeak"=> 0.0894,"nonSumerPeak" => 0.0894 ,"offPeak" => 0.0855 ],
                        [ "summerPeak"=> 0.0946,"nonSumerPeak" => 0.0946 ,"offPeak" => 0.0857 ],
                        [ "summerPeak"=> 0.12,"nonSumerPeak" =>  0.12,"offPeak" =>  0.0885],
                        [ "summerPeak"=> 0.1575,"nonSumerPeak" => 0.1575 ,"offPeak" => 0.0914 ],
                        [ "summerPeak"=> 0.134,"nonSumerPeak" => 0.134 ,"offPeak" =>  0.0899],
                        [ "summerPeak"=> 0.1172,"nonSumerPeak" => 0.1172 ,"offPeak" =>  0.088],
                        [ "summerPeak"=> 0.0919,"nonSumerPeak" => 0.0919 ,"offPeak" =>  0.0869],
                        [ "summerPeak"=> 0.096,"nonSumerPeak" =>  0.096,"offPeak" =>  0.0881],
                        [ "summerPeak"=> 0.1159,"nonSumerPeak" => 0.1159 ,"offPeak" =>  0.901]
                    ];
                    return new VEPUtilityRate($data["Utility"],$data["Rate"],$data["Customer_Charge"],
                                    $costKw,$costKwH, $peakTimeSummer,$peakTimeNonSummer);
                default:
                    throw new Exception("Invalid record type: $utility");
            }
        } catch (Exception $e) {
            $logger->logError("Invalid record type: $utility");
            echo 'Error: ' . $e->getMessage();
        }
        
    }
}

?>
