<?php

class EnergyMetrics
{
    private static $metrics = [
        'Energy_Consumption' => ['name' => 'Energy Consumption', 'units' => 'kWh'],
        'Real_Power' => ['name' => 'Real Power', 'units' => 'kW'],
        'Reactive_Power' => ['name' => 'Reactive Power', 'units' => 'kVAR'],
        'Apparent_Power' => ['name' => 'Apparent Power', 'units' => 'kVA'],
        'Power_Factor' => ['name' => 'Power Factor', 'units' => ''],
        'Voltage_Line_to_Line' => ['name' => 'Voltage, Line to Line', 'units' => 'Volts'],
        'Voltage_Line_to_Neutral' => ['name' => 'Voltage, Line to Neutral', 'units' => 'Volts'],
        'Current' => ['name' => 'Current', 'units' => 'Amps'],
        'Real_Power_phase_A' => ['name' => 'Real Power phase A', 'units' => 'kW'],
        'Real_Power_phase_B' => ['name' => 'Real Power phase B', 'units' => 'kW'],
        'Real_Power_phase_C' => ['name' => 'Real Power phase C', 'units' => 'kW'],
        'Power_Factor_phase_A' => ['name' => 'Power Factor phase A', 'units' => ''],
        'Power_Factor_phase_B' => ['name' => 'Power Factor phase B', 'units' => ''],
        'Power_Factor_phase_C' => ['name' => 'Power Factor phase C', 'units' => ''],
        'Voltage_phase_A_B' => ['name' => 'Voltage phase A-B', 'units' => 'Volts'],
        'Voltage_phase_B_C' => ['name' => 'Voltage phase B-C', 'units' => 'Volts'],
        'Voltage_phase_C_A' => ['name' => 'Voltage phase C-A', 'units' => 'Volts'],
        'Voltage_phase_A_N' => ['name' => 'Voltage phase A-N', 'units' => 'Volts'],
        'Voltage_phase_B_N' => ['name' => 'Voltage phase B-N', 'units' => 'Volts'],
        'Voltage_phase_C_N' => ['name' => 'Voltage phase C-N', 'units' => 'Volts'],
        'Current_phase_A' => ['name' => 'Current phase A', 'units' => 'Amps'],
        'Current_phase_B' => ['name' => 'Current phase B', 'units' => 'Amps'],
        'Current_phase_C' => ['name' => 'Current phase C', 'units' => 'Amps'],
        'Average_Demand' => ['name' => 'Average Demand', 'units' => 'kW'],
        'Minimum_Demand' => ['name' => 'Minimum Demand', 'units' => 'kW'],
        'Maximum_Demand' => ['name' => 'Maximum Demand', 'units' => 'kW'],
        'Peak_kW' => ['name' => 'Peak kW', 'units' => 'kW'],
        'Peak_kWh' => ['name' => 'Peak kWh', 'units' => 'kWh'],
        'Off_Peak_kW' => ['name' => 'Off Peak kW', 'units' => 'kW'],
        'Off_Peak_kWh' => ['name' => 'Off Peak kWh', 'units' => 'kWh']
    ];

    public static function get_names()
    {
        $names = [];
        foreach (self::$metrics as $key => $value) {
            $names[] = $value['name'];
        }
        return $names;
    }

    public static function get_units($name)
    {
        foreach (self::$metrics as $key => $value) {
            if ($value['name'] == $name) {
                return [
                    'field' => $key,
                    'name' => $value['name'],
                    'units' => $value['units']
                ];
            }
        }
        return null;
    }
}

// Ejemplo de uso:
$names = EnergyMetrics::get_names();
print_r($names);

$units = EnergyMetrics::get_units('Real Power');
print_r($units);

?>
