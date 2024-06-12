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
    private $time;
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
    private $voltagePhaseCA;
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

    public function __construct(
        $time, $error, $energyConsumption, $realPower = null, $reactivePower = null, $apparentPower = null,
        $powerFactor = null, $current = null, $realPowerPhaseA = null, $realPowerPhaseB = null, $realPowerPhaseC = null,
        $powerFactorPhaseA = null, $powerFactorPhaseB = null, $powerFactorPhaseC = null,
        $voltagePhaseAB = null, $voltagePhaseBC = null, $voltagePhaseCA = null, $voltagePhaseAN = null,
        $voltagePhaseBN = null, $voltagePhaseCN = null, $currentPhaseA = null, $currentPhaseB = null,
        $currentPhaseC = null, $averageDemand = null, $maximumDemand = null) 
        {
        $this->time = $time;
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
        $this->voltagePhaseCA = $voltagePhaseCA!== null ? $voltagePhaseCA : 0;
        $this->voltagePhaseAN = $voltagePhaseAN!== null ? $voltagePhaseAN : 0;
        $this->voltagePhaseBN = $voltagePhaseBN!== null ? $voltagePhaseBN : 0;
        $this->voltagePhaseCN = $voltagePhaseCN!== null ? $voltagePhaseCN : 0;
        $this->currentPhaseA = $currentPhaseA!== null ? $currentPhaseA : 0;
        $this->currentPhaseB = $currentPhaseB!== null ? $currentPhaseB : 0;
        $this->currentPhaseC = $currentPhaseC!== null ? $currentPhaseC : 0;
        $this->averageDemand = $averageDemand!== null ? $averageDemand : 0;
        $this->maximumDemand = $maximumDemand!== null ? $maximumDemand : 0;

        // Start values in 0
        $this->peakKw = 0;
        $this->peakKwh = 0;
        $this->offPeakKw = 0;
        $this->offPeakKwh = 0;
        $this->costKw = 0;
    }
    public function getTime() {
        return $this->time;
    }
    public function setTime($time) {
        $this->time = $time;
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
    public function setPeakKwh($peakKwh) {
        $this->peakKwh = $peakKwh;
    }
    public function setCostKw($costKw) {
        $this->costKw = $costKw;
    }
    public function getKwValues() {
        return [$this->offPeakKw,$this->offPeakKwh,$this->peakKw,$this->peakKwh ];
    }

}
// Record Factory !! 
class RecordFactory {
    public static function createRecord($type, $data) {
        switch ($type) {
            case 'device001':
                return new RecordsTypeStandard($data['time'],$data['error'],$data['Energy_Consumption'],
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
                return new RecordsTypeStandard($data['time'],$data['error'],$data['Accumulated_Real_Energy_Net_Import__Export'],
                    $data['Total_Net_Instantaneous_Real_P_Power'],$data['Total_Net_Instantaneous_Reactive_Q_Power'],$data['Total_Net_Instantaneous_Apparent_S_Power_vector_sum'],
                    $data['Total_Power_Factor_Total_KW_/_Total_KVA'],$data['Current_Average_of_Active_Phases'],$data['Real_Power_Phase_A'],
                    $data['Real_Power_phase_B'],$data['Real_Power_phase_C'],$data['Power_Factor_phase_A'],
                    $data['Power_Factor_phase_B'],$data['Power_Factor_phase_C'],$data['Voltage_phase_A_B'],
                    $data['Voltage_phase_B_C'],$data['Voltage_phase_C_A'],$data['Voltage_phase_A_N'],
                    $data['Voltage_phase_B_N'],$data['Voltage_phase_C_N'],$data['Current_phase_A'],
                    $data['Current_phase_B'],$data['Current_phase_C'],$data['Total_Real_Power_Present_Demand'],
                    $data['Total_Real_Power_Max_Demand_Export']
                );
            case 'device250':
                // To complete
                    return new RecordType250($data);
            case 'standard':
                return new RecordsTypeStandard($data['time'],$data['error'],$data['Energy_Consumption'],
                    $data['Real_Power'],$data['Reactive_Power'],$data['Apparent_Power'],
                    $data['Power_Factor'],$data['Current'],$data['Real_Power_phase_A'],
                    $data['Real_Power_phase_B'],$data['Real_Power_phase_C'],$data['Power_Factor_phase_A'],
                    $data['Power_Factor_phase_B'],$data['Power_Factor_phase_C'],$data['Voltage_phase_A_B'],
                    $data['Voltage_phase_B_C'],$data['Voltage_phase_C_A'],$data['Voltage_phase_A_N'],
                    $data['Voltage_phase_B_N'],$data['Voltage_phase_C_N'],$data['Current_phase_A'],
                    $data['Current_phase_B'],$data['Current_phase_C'],$data['Average_Demand'],
                    $data['Maximum_Demand']
                );
            case 'test':
                return new RecordsTypeStandard($data['time'],$data['error'],$data['Energy_Consumption']);
            default:
                throw new Exception("Invalid record type: $type");
        }
    }

    public static function createRecords($type, $dataList) {
        $records = [];
        foreach ($dataList as $data) {
            $records[] = self::createRecord($type, $data);
        }
        return $records;
    }
}


?>

