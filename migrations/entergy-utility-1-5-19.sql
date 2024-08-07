USE bwolff_eqoff;

INSERT INTO Cape_Knox_001EC6001635__device001_class2 (`time`,
  `error`,
  `lowrange`,
  `highrange`,
  `Energy_Consumption`,
  `Real_Power`,
  `Reactive_Power`,
  `Apparent_Power`,
  `Power_Factor`,
  `Voltage,_Line_to_Line`,
  `Voltage,_Line_to_Neutral`,
  `Current`,
  `Real_Power_phase_A`,
  `Real_Power_phase_B`,
  `Real_Power_phase_C`,
  `Power_Factor_phase_A`,
  `Power_Factor_phase_B`,
  `Power_Factor_phase_C`,
  `Voltage_phase_A-B`,
  `Voltage_phase_B-C`,
  `Voltage_phase_C-A`,
  `Voltage_phase_A-N`,
  `Voltage_phase_B-N`,
  `Voltage_phase_C-N`,
  `Current_phase_A`,
  `Current_phase_B`,
  `Current_phase_C`,
  `Average_Demand`,
  `Minimum_Demand`,
  `Maximum_Demand`,
  `Peak_kW`,
  `Peak_kWh`,
  `Off_Peak_kW`,
  `Off_Peak_kWh`)
SELECT time, error, lowrange, highrange, Shore_Power, Real_Power, 0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
`Peak_kW`,
`Peak_kWh`,
`Off_Peak_kW`,
`Off_Peak_kWh`
FROM Cape_Knox_001EC6001635__device250_class27 ORDER BY time ASC;
INSERT INTO Cape_Knox_001EC6001635__device001_class2 (`time`,
  `error`,
  `lowrange`,
  `highrange`,
  `Energy_Consumption`,
  `Real_Power`,
  `Reactive_Power`,
  `Apparent_Power`,
  `Power_Factor`,
  `Voltage,_Line_to_Line`,
  `Voltage,_Line_to_Neutral`,
  `Current`,
  `Real_Power_phase_A`,
  `Real_Power_phase_B`,
  `Real_Power_phase_C`,
  `Power_Factor_phase_A`,
  `Power_Factor_phase_B`,
  `Power_Factor_phase_C`,
  `Voltage_phase_A-B`,
  `Voltage_phase_B-C`,
  `Voltage_phase_C-A`,
  `Voltage_phase_A-N`,
  `Voltage_phase_B-N`,
  `Voltage_phase_C-N`,
  `Current_phase_A`,
  `Current_phase_B`,
  `Current_phase_C`,
  `Average_Demand`,
  `Minimum_Demand`,
  `Maximum_Demand`,
  `Peak_kW`,
  `Peak_kWh`,
  `Off_Peak_kW`,
  `Off_Peak_kWh`)
SELECT time, error, lowrange, highrange, Shore_Power, Real_Power, 0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
`Peak_kW`,
`Peak_kWh`,
`Off_Peak_kW`,
`Off_Peak_kWh`
FROM Cape_Knox_001EC6001635__device250_class27 ORDER BY time ASC;

INSERT IGNORE INTO Cape_Kennedy_001EC6001433__device001_class2 (`time`,
  `error`,
  `lowrange`,
  `highrange`,
  `Energy_Consumption`,
  `Real_Power`,
  `Reactive_Power`,
  `Apparent_Power`,
  `Power_Factor`,
  `Voltage,_Line_to_Line`,
  `Voltage,_Line_to_Neutral`,
  `Current`,
  `Real_Power_phase_A`,
  `Real_Power_phase_B`,
  `Real_Power_phase_C`,
  `Power_Factor_phase_A`,
  `Power_Factor_phase_B`,
  `Power_Factor_phase_C`,
  `Voltage_phase_A-B`,
  `Voltage_phase_B-C`,
  `Voltage_phase_C-A`,
  `Voltage_phase_A-N`,
  `Voltage_phase_B-N`,
  `Voltage_phase_C-N`,
  `Current_phase_A`,
  `Current_phase_B`,
  `Current_phase_C`,
  `Average_Demand`,
  `Minimum_Demand`,
  `Maximum_Demand`,
  `Peak_kW`,
  `Peak_kWh`,
  `Off_Peak_kW`,
  `Off_Peak_kWh`)
SELECT `time`, `error`, `lowrange`, `highrange`, `Shore_Power_(kWh)`, `Real_Power`, 0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
`Peak_kW`,
`Peak_kWh`,
`Off_Peak_kW`,
`Off_Peak_kWh`
FROM Cape_Kennedy_001EC6001433__device250_class27 ORDER BY time ASC;

INSERT INTO Cape_Kennedy_001EC6001433__device001_class2 (`time`,
  `error`,
  `lowrange`,
  `highrange`,
  `Energy_Consumption`,
  `Real_Power`,
  `Reactive_Power`,
  `Apparent_Power`,
  `Power_Factor`,
  `Voltage,_Line_to_Line`,
  `Voltage,_Line_to_Neutral`,
  `Current`,
  `Real_Power_phase_A`,
  `Real_Power_phase_B`,
  `Real_Power_phase_C`,
  `Power_Factor_phase_A`,
  `Power_Factor_phase_B`,
  `Power_Factor_phase_C`,
  `Voltage_phase_A-B`,
  `Voltage_phase_B-C`,
  `Voltage_phase_C-A`,
  `Voltage_phase_A-N`,
  `Voltage_phase_B-N`,
  `Voltage_phase_C-N`,
  `Current_phase_A`,
  `Current_phase_B`,
  `Current_phase_C`,
  `Average_Demand`,
  `Minimum_Demand`,
  `Maximum_Demand`,
  `Peak_kW`,
  `Peak_kWh`,
  `Off_Peak_kW`,
  `Off_Peak_kWh`)
SELECT '2018-12-11 17:30:01', `error`, `lowrange`, `highrange`, `Shore_Power_(kWh)`, `Real_Power`, 0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
0,
`Peak_kW`,
`Peak_kWh`,
`Off_Peak_kW`,
`Off_Peak_kWh`
FROM Cape_Kennedy_001EC6001433__device250_class27
WHERE time='2018-12-11 17:30:00'
ORDER BY time ASC;

DELETE FROM Cape_Kennedy_001EC6001433__device001_class2 WHERE time='2018-12-11 17:30:59';

CREATE TABLE `bwolff_eqoff`.`Entergy_NO_Rates` (
  `Utility` VARCHAR(45) NOT NULL,
  `Rate` VARCHAR(45) NULL,
  `Energy_Rate_1` FLOAT NULL,
  `Energy_Rate_2` FLOAT NULL,
  `Energy_Rate_3` FLOAT NULL,
  `Energy_Rate_4` FLOAT NULL,
  `Energy_Rate_5` FLOAT NULL,
  `Demand_Rate_1` FLOAT NULL,
  `Demand_Rate_2` FLOAT NULL,
  `Demand_Rate_3` FLOAT NULL,
  `Demand_Rate_4` FLOAT NULL,
  `Rider_Fuel_kWh` FLOAT NULL,
  `Rider_Capacity_kWh` FLOAT NULL,
  `Rider_EAC_kWh` FLOAT NULL,
  `Street_Use_Franchise_Fee` FLOAT NULL,
  `Storm_Securitization_Fee` FLOAT NULL,
  `Formula_Rate_Plan_Percentage` FLOAT NULL,
  `MISO_Recovery_Percentage` FLOAT NULL,
  PRIMARY KEY (`Utility`));

ALTER TABLE `bwolff_eqoff`.`Entergy_NO_Rates`
CHANGE COLUMN `Rate` `Rate` VARCHAR(45) NOT NULL ,
ADD COLUMN `Rate_Date_Start` DATETIME NOT NULL AFTER `MISO_Recovery_Percentage`,
ADD COLUMN `Rate_Date_End` DATETIME NOT NULL AFTER `Rate_Date_Start`;

INSERT INTO `bwolff_eqoff`.`Entergy_NO_Rates` (`Utility`, `Rate`, `Energy_Rate_1`, `Energy_Rate_2`, `Energy_Rate_3`, `Energy_Rate_4`, `Energy_Rate_5`, `Demand_Rate_1`, `Demand_Rate_2`, `Demand_Rate_3`, `Demand_Rate_4`, `Rider_Fuel_kWh`, `Rider_Capacity_kWh`, `Rider_EAC_kWh`, `Street_Use_Franchise_Fee`, `Storm_Securitization_Fee`, `Formula_Rate_Plan_Percentage`, `MISO_Recovery_Percentage`, `Rate_Date_Start`, `Rate_Date_End`) VALUES ('Entergy_NO_Rates', 'le-hlf-8', '0.05077', '0.02741', '0.02649', '0.02625', '0.02173', '508.06', '8.57', '8.04', '7.68', '0.036380375', '0.01047975', '0.000001', '1731.92625', '514.12625', '-0.105278', '0.046862','2017-12-01 00:00:00','2050-12-01 00:00:00');

UPDATE `bwolff_eqoff`.`Aquisuite_List` SET `utility`='Entergy_NO_Rates', `utilityrate`='le-hlf-8' WHERE `SerialNumber`='001EC6001433';
UPDATE `bwolff_eqoff`.`Aquisuite_List` SET `utility`='Entergy_NO_Rates', `utilityrate`='le-hlf-8' WHERE `SerialNumber`='001EC6001635';

