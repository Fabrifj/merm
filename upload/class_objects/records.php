<?php
class Record {
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    // This method can be overridden by subclasses to return specific data
    public function getSpecificData() {
        return null;
    }

    // Common methods for all records can be added here
    public function printData() {
        foreach ($this->data as $element) {
            echo $element . "<br>";
        }
    }
    public function printDate() {
        echo " Este es el tiempo:".$this->data["time"] . "<br>";
    }
}

class RecordType001 extends Record {
    public function getSpecificData() {
        // Assuming the specific data is in position 0
        return $this->data[0];
    }
}

class RecordType250 extends Record {
    public function getSpecificData() {
        // Assuming the specific data is in position 1
        return $this->data[0];
    }
}

class RecordType002 extends Record {
    public function getSpecificData() {
        // Assuming the specific data is in position 2
        return $this->data[0];
    }
}


class RecordsTypeStandard {
    private $timeZone;
    private $dateTime;
    private $error;
    private $energyConsumption;
    private $realPower;
    private $reactivePower;
    private $apparentPower;
    private $powerFactor;
    private $current;
    private $realPowerPhaseA;
    private $realPowerPhaseB;
    private $realPowerPhaseC;
    private $powerFactorPhaseA;
    private $powerFactorPhaseB;
    private $powerFactorPhaseC;
    private $voltagePhaseAB;
    private $voltagePhaseBC;
    private $voltagePhaseAC;
    private $voltagePhaseAN;
    private $voltagePhaseBN;
    private $voltagePhaseCN;
    private $currentPhaseA;
    private $currentPhaseB;
    private $currentPhaseC;
    private $averageDemand;
    private $maximumDemand;
    private $peakKw;
    private $peakKwh;
    private $offPeakKw;
    private $offPeakKwh;
    private $costKw;
    private $shipName;

    public function __construct(
        $timeZone, $time, $error, $energyConsumption, $shipName ,$realPower = null, $reactivePower = null, $apparentPower = null,
        $powerFactor = null, $current = null, $realPowerPhaseA = null, $realPowerPhaseB = null, $realPowerPhaseC = null,
        $powerFactorPhaseA = null, $powerFactorPhaseB = null, $powerFactorPhaseC = null,
        $voltagePhaseAB = null, $voltagePhaseBC = null, $voltagePhaseAC = null, $voltagePhaseAN = null,
        $voltagePhaseBN = null, $voltagePhaseCN = null, $currentPhaseA = null, $currentPhaseB = null,
        $currentPhaseC = null, $averageDemand = null, $maximumDemand = null) 
        {    
        $this->timeZone = new DateTimeZone($timeZone);
        $this->dateTime = new DateTime($time, $this->timeZone);
        $this->error = $error;
        $this->energyConsumption = $energyConsumption;
        $this->realPower = $realPower !== null ? $realPower : 0; 
        $this->reactivePower = $reactivePower!== null ? $reactivePower : 0;
        $this->apparentPower = $apparentPower!== null ? $apparentPower : 0;
        $this->powerFactor = $powerFactor!== null ? $powerFactor : 0;
        $this->current = $current!== null ? $current : 0;
        $this->realPowerPhaseA = $realPowerPhaseA!== null ? $realPowerPhaseA : 0;
        $this->realPowerPhaseB = $realPowerPhaseB!== null ? $realPowerPhaseB : 0;
        $this->realPowerPhaseC = $realPowerPhaseC!== null ? $realPowerPhaseC : 0;
        $this->powerFactorPhaseA = $powerFactorPhaseA!== null ? $powerFactorPhaseA : 0;
        $this->powerFactorPhaseB = $powerFactorPhaseB!== null ? $powerFactorPhaseB : 0;
        $this->powerFactorPhaseC = $powerFactorPhaseC!== null ? $powerFactorPhaseC : 0;
        $this->voltagePhaseAB = $voltagePhaseAB!== null ? $voltagePhaseAB : 0;
        $this->voltagePhaseBC = $voltagePhaseBC!== null ? $voltagePhaseBC : 0;
        $this->voltagePhaseAC = $voltagePhaseAC!== null ? $voltagePhaseAC : 0;
        $this->voltagePhaseAN = $voltagePhaseAN!== null ? $voltagePhaseAN : 0;
        $this->voltagePhaseBN = $voltagePhaseBN!== null ? $voltagePhaseBN : 0;
        $this->voltagePhaseCN = $voltagePhaseCN!== null ? $voltagePhaseCN : 0;
        $this->currentPhaseA = $currentPhaseA!== null ? $currentPhaseA : 0;
        $this->currentPhaseB = $currentPhaseB!== null ? $currentPhaseB : 0;
        $this->currentPhaseC = $currentPhaseC!== null ? $currentPhaseC : 0;
        $this->averageDemand = $averageDemand!== null ? $averageDemand : 0;
        $this->maximumDemand = $maximumDemand!== null ? $maximumDemand : 0;
        $this->shipName = $shipName;

        // Start values in 0
        $this->peakKw = 0;
        $this->peakKwh = 0;
        $this->offPeakKw = 0;
        $this->offPeakKwh = 0;
        $this->costKw = 0;
    }

    public function getData(){
        $value = sprintf(
            "('%s', '%s', %d, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, %f, '%f', '%s')",
            mysql_real_escape_string((string)$this->dateTime->format("Y-m-d H:i:s")), mysql_real_escape_string((string)$this->timeZone->getName()),
            (int)$this->error, (float)$this->energyConsumption, (float)$this->realPower, (float)$this->reactivePower,
            (float)$this->apparentPower, (float)$this->powerFactor, (float)$this->current, (float)$this->realPowerPhaseA,
            (float)$this->realPowerPhaseB, (float)$this->realPowerPhaseC,(float)$this->powerFactorPhaseA,
            (float)$this->powerFactorPhaseB,(float)$this->powerFactorPhaseC,(float)$this->voltagePhaseAB,
            (float)$this->voltagePhaseBC,(float)$this->voltagePhaseAC,(float)$this->voltagePhaseAN,
            (float)$this->voltagePhaseBN,(float)$this->voltagePhaseCN, (float)$this->currentPhaseA,
            (float)$this->currentPhaseB,(float)$this->currentPhaseC, (float)$this->averageDemand,
            (float)$this->maximumDemand, (float)$this->peakKw, (float)$this->peakKwh, (float)$this->offPeakKw,     
            (float)$this->offPeakKwh, (float)$this->costKw, mysql_real_escape_string((string)$this->shipName)
        );
        return $value;
    }
    public function getShipName() {
        return $this-> shipName;
    }
    public function getTime() {
        return $this->dateTime;
    }
    public function setTime($dateTime) {
        $this->dateTime = $dateTime;
    }
    public function getEnergyConsumption() {
        return $this->energyConsumption;
    }
    public function setEnergyConsumption($energyConsumption) {
        $this->energyConsumption = $energyConsumption;
    }
    /// Set peak kh
    public function setOffPeakKw($offPeakKw) {
        $this->offPeakKw = $offPeakKw;
    }
    public function getOffPeakKw() {
        return $this->offPeakKw;
    }
    public function setOffPeakKwh($offPeakKwh) {
        $this->offPeakKwh = $offPeakKwh;
    }
    public function getOffPeakKwh() {
        return $this->offPeakKwh;
    }
    public function setPeakKw($peakKw) {
        $this->peakKw = $peakKw;
    }
    public function getPeakKw() {
        return $this->peakKw ;
    }
    public function setPeakKwh($peakKwh) {
        $this->peakKwh = $peakKwh;
    }
    public function getPeakKwh() {
        return $this->peakKwh;
    }
    public function setCostKw($costKw) {
        $this->costKw = $costKw;
    }
    public function getCostKw() {
        return $this->costKw;
    }
    public function getKwValues() {
        return [$this->offPeakKw,$this->offPeakKwh,$this->peakKw,$this->peakKwh ];
    }

}
// Record Factory !! 
class RecordFactory {
    public static function createRecord($timezone,$type, $data, $loopName) {
        switch ($type) {
            case 'device001':
                return new RecordsTypeStandard($timezone,$data['time'],$data['error'],$data['Energy_Consumption'],$loopName,
                    $data['Real_Power'],$data['Reactive_Power'],$data['Apparent_Power'],
                    $data['Power_Factor'],$data['Current'],$data['Real_Power_phase_A'],
                    $data['Real_Power_phase_B'],$data['Real_Power_phase_C'],$data['Power_Factor_phase_A'],
                    $data['Power_Factor_phase_B'],$data['Power_Factor_phase_C'],$data['Voltage_phase_A_B'],
                    $data['Voltage_phase_B_C'],$data['Voltage_phase_C_A'],$data['Voltage_phase_A_N'],
                    $data['Voltage_phase_B_N'],$data['Voltage_phase_C_N'],$data['Current_phase_A'],
                    $data['Current_phase_B'],$data['Current_phase_C'],$data['Average_Demand'],
                    $data['Maximum_Demand']
                );
            case 'device002':
                return new RecordsTypeStandard($timezone,$data['time'],$data['error'],$data['Accumulated_Real_Energy:_Net_Import__Export'],$loopName,
                    $data['Total_Net_Instantaneous_Real_P_Power'],$data['Total_Net_Instantaneous_Reactive_Q_Power'],$data['Total_Net_Instantaneous_Apparent_S_Power_vector_sum'],
                    $data['Total_Power_Factor_Total_KW_/_Total_KVA'],$data['Current_Average_of_Active_Phases'],$data['Real_Power_Phase_A'],
                    $data['Real_Power_Phase_B'],$data['Real_Power_Phase_C'],$data['Power_Factor_Phase_A'],
                    $data['Power_Factor_Phase_B'],$data['Power_Factor_Phase_C'],$data['Voltage_Phase_AB'],
                    $data['Voltage_Phase_BC'],$data['Voltage_Phase_AC'],$data['Voltage_Phase_AN'],
                    $data['Voltage_Phase_BN'],$data['Voltage_Phase_CN'],$data['Current_Phase_A'],
                    $data['Current_Phase_B'],$data['Current_Phase_C'],$data['Total_Real_Power_Present_Demand'],
                    $data['Total_Real_Power_Max_Demand_Export']
                );
            case 'device250':
                // To complete
                    return new RecordType250($timezone,$data);
            case 'standard':
                return new RecordsTypeStandard($timezone,$data['time'],$data['error'],$data['energy_consumption'],$data['loopName'],
                    $data['real_power'],$data['reactive_power'],$data['apparent_power'],
                    $data['power_factor'],$data['current'],$data['real_power_phase_a'],
                    $data['real_power_phase_b'],$data['real_power_phase_c'],$data['power_factor_phase_a'],
                    $data['power_factor_phase_b'],$data['power_factor_phase_c'],$data['voltage_phase_a_Bb'],
                    $data['voltage_phase_b_c'],$data['voltage_phase_c_a'],$data['voltage_phase_a_n'],
                    $data['voltage_phase_b_n'],$data['voltage_phase_c_n'],$data['current_phase_a'],
                    $data['current_phase_a'],$data['current_phase_c'],$data['average_demand'],
                    $data['maximum_Demand']
                );
            case 'test':
                return new RecordsTypeStandard($timezone,$data['time'],$data['error'],$data['Energy_Consumption'],$loopName);
            default:
                throw new Exception("Invalid record type: $type");
        }
    }

    public static function createRecords($timezone,$type, $dataList, $loopName) {
        $records = [];
        foreach ($dataList as $data) {
            $records[] = self::createRecord($timezone,$type, $data,$loopName);
        }
        return $records;
    }
}


?>

