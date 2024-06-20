<?php

class UtilityRate {
    private $utility;
    private $rate;
    private $customerCharge;
    private $summerPeakCostKw;
    private $nonSummerPeakCostKw;
    private $offPeakCostKw;
    private $summerPeakCostKwH;
    private $nonSummerPeakCostKwH;
    private $offPeakCostKwH;
    private $peakTimeSummer;
    private $peakTimeNonSummer;

    public function __construct($utility, $rate, $customerCharge, 
                                $summerPeakCostKw,$nonSummerPeakCostKw, $offPeakCostKw,
                                $summerPeakCostKwH,$nonSummerPeakCostKwH, $offPeakCostKwH, 
                                $peakTimeSummer, $peakTimeNonSummer) {
        $this->utility = $utility;
        $this->rate = $rate;
        $this->customerCharge = $customerCharge;
        $this->summerPeakCostKw = $summerPeakCostKw;
        $this->nonSummerPeakCostKw = $nonSummerPeakCostKw;
        $this->offPeakCostKw = $offPeakCostKw;
        $this->summerPeakCostKwH = $summerPeakCostKwH;
        $this->nonSummerPeakCostKwH = $nonSummerPeakCostKwH;
        $this->offPeakCostKwH = $offPeakCostKwH;
        // Peak
        $this->peakTimeSummer = $peakTimeSummer;
        $this->peakTimeNonSummer = $peakTimeNonSummer;

    }


    public function getUtility() {
        return $this->utility;
    }
    public function getSummerPeakCostKw() {
        return $this->summerPeakCostKw;
    }
    public function getNonSummerPeakCostKw() {
        return $this->nonSummerPeakCostKw;
    }
    public function getOffPeakCostKw() {
        return $this->offPeakCostKw;
    }
    public function getSummerPeakCostKwH() {
        return $this->summerPeakCostKwH;
    }
    public function getNonSummerPeakCostKwH() {
        return $this->nonSummerPeakCostKwH;
    }
    public function getOffPeakCostKwH() {
        return $this->offPeakCostKwH;
    }
    public function getPeakTimeSummer() {
        return $this->peakTimeSummer;
    }

    public function getPeakTimeNonSummer() {
        return $this->peakTimeNonSummer;
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
    public static function createStandardUtilityRate($timeZone, $data) {
        $timeZone = new DateTimeZone($timeZone);
        $utility = $data["Utility"];
        switch ($utility) {
            case 'SCE&G':
                $peakTimeSummer = [new TimeRange($timeZone,$data["Peak_Time_Summer_Start"], $data["Peak_Time_Summer_Stop"])];
                $peakTimeNonSummer = [new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start"], $data["Peak_Time_Non_Summer_Stop"]), new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start2"], $data["Peak_Time_Non_Summer_Stop2"])];
                return new UtilityRate($data["Utility"], $data["Rate"], $data["Customer_Charge"], 
                                $data["Summer_Peak_Demand_kW"], $data["Non_Summer_Peak_Demand_kW"], $data["Off_Peak_Demand_kW"],
                                $data["Summer_Peak_kWh"], $data["Non_Summer_Peak_kWh"], $data["Off_Peak_kWh"],
                                 $peakTimeSummer, $peakTimeNonSummer);
            case 'Nav_Fed_Rate': // Miss Cost_kw
                $peakTimeSummer = [];
                $peakTimeNonSummer = [];
                return new UtilityRate($data["Utility"],$data["Rate"],0,
                                $data["Miss"],$data["Miss"],$data["Miss"],
                                $data["Cost_kWh"],$data["Cost_kWh"],$data["Cost_kWh"],
                                $peakTimeSummer,$peakTimeNonSummer);
            case 'Entergy_NO_Rates': // Fix What is Whats
                $peakTimeSummer = [];
                $peakTimeNonSummer = [];
                return new UtilityRate($data["Utility"],$data["Rate"],0,
                                $data["Demand_Rate_1"],$data["Demand_Rate_2"],$data["Demand_Rate_3"],
                                $data["Energy_Rate_1"],$data["Energy_Rate_2"],$data["Energy_Rate_3"],
                                $peakTimeSummer,$peakTimeNonSummer);
            case 'Virginia_Dominion_Rates':
                $peakTimeSummer = [new TimeRange($timeZone,$data["Peak_Time_Summer_Start"], $data["Peak_Time_Summer_Stop"])];
                $peakTimeNonSummer = [new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start"], $data["Peak_Time_Non_Summer_Stop"])];
                return new UtilityRate($data["Utility"],$data["Rate"],$data["Customer_Charge"],
                                $data["Peak_kW"],$data["Peak_kW"],$data["Off_Peak_kW"],
                                $data["Peak_kWh"],$data["Peak_kWh"],$data["Off_Peak_kWh"],
                                $peakTimeSummer,$peakTimeNonSummer);
            case 'Virginia_Electric_and_power_Co': ///  KWh Are not 
                $peakTimeSummer = [new TimeRange($timeZone,$data["Peak_Time_Summer_Start"], $data["Peak_Time_Summer_Stop"])];
                $peakTimeNonSummer = [new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start_AM"], $data["Peak_Time_Non_Summer_Stop_AM"]),
                                new TimeRange($timeZone,$data["Peak_Time_Non_Summer_Start_PM"], $data["Peak_Time_Non_Summer_Stop_PM"])];
                return new UtilityRate($data["Utility"],$data["Rate"],$data["Customer_Charge"],
                                $data["Peak_kW_Demand_1"],$data["Peak_kW_Demand_1"],$data["Peak_kW_Demand_1"],
                                $data["Energy_Rate_1"],$data["Energy_Rate_2"],$data["Energy_Rate_3"],
                                $peakTimeSummer,$peakTimeNonSummer);
            
            default:
                throw new Exception("Invalid record type: $utility");
        }
        
    }
}

?>
