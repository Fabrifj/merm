<?php

class UtilityRate {
    private $utility;
    private $rate;
    private $customerCharge;
    private $summerPeakCostKw;
    private $nonSummerPeakCostKw;
    private $offPeakCostKw;
    private $peakTimeSummer;
    private $peakTimeNonSummer;

    public function __construct($utility, $rate, $customerCharge, $summerPeakCostKw, 
                                $nonSummerPeakCostKw, $offPeakCostKw, 
                                $peakTimeSummer, $peakTimeNonSummer) {
        $this->utility = $utility;
        $this->rate = $rate;
        $this->customerCharge = $customerCharge;
        $this->summerPeakCostKw = $summerPeakCostKw;
        $this->nonSummerPeakCostKw = $nonSummerPeakCostKw;
        $this->offPeakCostKw = $offPeakCostKw;
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
                $peakTimeSummer = [new TimeRange($timeZone,$data[12], $data[13])];
                $peakTimeNonSummer = [new TimeRange($timeZone,$data[14], $data[15]), new TimeRange($timeZone,$data[16], $data[17])];
                return new UtilityRate($data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $peakTimeSummer, $peakTimeNonSummer);
            case 'Nav_Fed_Rate':
                $peakTimeSummer = [];
                $peakTimeNonSummer = [];
                return new UtilityRate($data[0],$data[1],0,$data[2],$data[2],$data[2],$peakTimeSummer,$peakTimeNonSummer);
            case 'Entergy_NO_Rates':
                $peakTimeSummer = [];
                $peakTimeNonSummer = [];
                return new UtilityRate($data["Utility"],$data["Rate"],0,$data["Energy_Rate_1"],$data["Energy_Rate_2"],$data["Energy_Rate_3"],$peakTimeSummer,$peakTimeNonSummer);
            default:
                throw new Exception("Invalid record type: $utility");
        }
        
    }
}

?>
