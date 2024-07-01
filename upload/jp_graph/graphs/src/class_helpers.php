<?php

class EnergyMetrics
{
    private static $metrics = [
        'energy_consumption' => ['name' => 'Energy Consumption', 'units' => 'kWh'],
        'real_power' => ['name' => 'Real Power', 'units' => 'kW'],
        'reactive_power' => ['name' => 'Reactive Power', 'units' => 'kVAR'],
        'apparent_power' => ['name' => 'Apparent Power', 'units' => 'kVA'],
        'power_factor' => ['name' => 'Power Factor', 'units' => ''],
        'voltage_line_to_line' => ['name' => 'Voltage, Line to Line', 'units' => 'Volts'],
        'voltage_line_to_neutral' => ['name' => 'Voltage, Line to Neutral', 'units' => 'Volts'],
        'current' => ['name' => 'Current', 'units' => 'Amps'],
        'real_power_phase_a' => ['name' => 'Real Power phase A', 'units' => 'kW'],
        'real_power_phase_b' => ['name' => 'Real Power phase B', 'units' => 'kW'],
        'real_power_phase_c' => ['name' => 'Real Power phase C', 'units' => 'kW'],
        'power_factor_phase_a' => ['name' => 'Power Factor phase A', 'units' => ''],
        'power_factor_phase_b' => ['name' => 'Power Factor phase B', 'units' => ''],
        'power_factor_phase_c' => ['name' => 'Power Factor phase C', 'units' => ''],
        'voltage_phase_a_b' => ['name' => 'Voltage phase A-B', 'units' => 'Volts'],
        'voltage_phase_b_c' => ['name' => 'Voltage phase B-C', 'units' => 'Volts'],
        'voltage_phase_c_a' => ['name' => 'Voltage phase C-A', 'units' => 'Volts'],
        'voltage_phase_a_n' => ['name' => 'Voltage phase A-N', 'units' => 'Volts'],
        'voltage_phase_b_n' => ['name' => 'Voltage phase B-N', 'units' => 'Volts'],
        'voltage_phase_c_n' => ['name' => 'Voltage phase C-N', 'units' => 'Volts'],
        'current_phase_a' => ['name' => 'Current phase A', 'units' => 'Amps'],
        'current_phase_b' => ['name' => 'Current phase B', 'units' => 'Amps'],
        'current_phase_c' => ['name' => 'Current phase C', 'units' => 'Amps'],
        'average_demand' => ['name' => 'Average Demand', 'units' => 'kW'],
        'minimum_demand' => ['name' => 'Minimum Demand', 'units' => 'kW'],
        'maximum_demand' => ['name' => 'Maximum Demand', 'units' => 'kW'],
        'peak_kw' => ['name' => 'Peak kW', 'units' => 'kW'],
        'peak_kwh' => ['name' => 'Peak kWh', 'units' => 'kWh'],
        'off_peak_kw' => ['name' => 'Off Peak kW', 'units' => 'kW'],
        'off_peak_kwh' => ['name' => 'Off Peak kWh', 'units' => 'kWh']
    ];
    

    public static function get_names()
    {
        $names = [];
        foreach (self::$metrics as $key => $value) {
            $names[] = $value['name'];
        }
        return $names;
    }

    public static function get_details($key)
    {
        if (isset(self::$metrics[$key])) {
            $value = self::$metrics[$key];
            return [
                'field' => $key,
                'name' => $value['name'],
                'units' => $value['units']
            ];
        }
        return null;
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


?>
