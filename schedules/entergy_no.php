<?php
function entergy_le_hlf($val, $cost) {
  global $log;

  // This utility doesn't have an on / off peak demand
  // so for Energy both Peak and Off_Peak are combined and
  // for Demand whichever value is greater is used.
  $kWh_Total = $val["Off_Peak_kWh_Total"] + $val["Peak_kWh_Total"];
  $Demand = $val["Peak_Demand"] > $val["Off_Peak_Demand"] ? $val["Peak_Demand"] : $val["Off_Peak_Demand"];

  $COST["kWh_Total"] = $kWh_Total;

  $Utility = $cost["Utility"];
  $Rate = $cost["Rate"];
  $COST["U_Energy_Rate_1"] = $cost["Energy_Rate_1"];
  $COST["U_Energy_Rate_2"] = $cost["Energy_Rate_2"];
  $COST["U_Energy_Rate_3"] = $cost["Energy_Rate_3"];
  $COST["U_Energy_Rate_4"] = $cost["Energy_Rate_4"];
  $COST["U_Energy_Rate_5"] = $cost["Energy_Rate_5"];

  $COST["U_Demand_Rate_1"] = $cost["Demand_Rate_1"];
  $COST["U_Demand_Rate_2"] = $cost["Demand_Rate_2"];
  $COST["U_Demand_Rate_3"] = $cost["Demand_Rate_3"];
  $COST["U_Demand_Rate_4"] = $cost["Demand_Rate_4"];

  $COST["U_Rider_Fuel_kWh"] = $cost["Rider_Fuel_kWh"];
  $COST["U_Rider_Capacity_kWh"] = $cost["Rider_Capacity_kWh"];
  $COST["U_Rider_EAC_kWh"] = $cost["Rider_EAC_kWh"];

  $COST["U_Street_Use_Franchise_Fee"] = $cost["Street_Use_Franchise_Fee"];
  $COST["U_Storm_Securitization_Fee"] = $cost["Storm_Securitization_Fee"];

  $COST["U_Formula_Rate_Plan_Percentage"] = $cost["Formula_Rate_Plan_Percentage"];
  $COST["U_MISO_Recovery_Percentage"] = $cost["MISO_Recovery_Percentage"];

  // Demand Tiered Cost. At this time 2 ships are sharing a meter
  // so each of the tier calculated values are divided in half.

  // 0 - 50 kW
  if($Demand > 0) {
    // multiple ship correction factor
    $COST["Demand_Cost_Tier_1"] = $cost["Demand_Rate_1"]/2;
  }

  // 50 - 100 kW
  if($Demand > 50) {
    $demand_tier_2 = 100;
    if($Demand < 100) {
      $demand_tier_2 = $Demand;
    }
    // multiple ship correction factor
    $demand_tier_2 = $demand_tier_2/2;
    $COST["Demand_Cost_Tier_2"] = $demand_tier_2 * $cost["Demand_Rate_2"];
  }

  // 100 - 200 kW
  if($Demand > 100) {
    $demand_tier_3 = 200;
    if($Demand < 200) {
      $demand_tier_3 = $Demand;
    }
    // multiple ship correction factor
    $demand_tier_3 = $demand_tier_3/2;
    $COST["Demand_Cost_Tier_3"] = $demand_tier_3 * $cost["Demand_Rate_3"];
  }

  // 200 kW +
  if($Demand > 200) {
    $demand_tier_4 = $Demand - ($demand_tier_1 + $demand_tier_2 + $demand_tier_3);
    $COST["Demand_Cost_Tier_4"] = $demand_tier_4 * $cost["Demand_Rate_4"];
  }

  $COST["Total_kW_Cost"] = $COST["Demand_Cost_Tier_1"] + $COST["Demand_Cost_Tier_2"] + $COST["Demand_Cost_Tier_3"] + $COST["Demand_Cost_Tier_4"];

  // Energy Tiered cost.
  // 0 - 5000 kWh
  if($kWh_Total > 0) {
    $kwh_tier_1 = 5000;
    if($kwh_tier_1 < 5000) {
      $kwh_tier_1 = $kWh_Total;
    }
    // multiple ship correction factor
    $kwh_tier_1 = $kwh_tier_1/2;
    $COST["kWh_Cost_Tier_1"] = $kwh_tier_1 * $cost["Energy_Rate_1"];
  }

  // 5000 - 10000 kWh
  if($kWh_Total > 5000) {
    $kwh_tier_2 = 10000;
    if($kWh_Total < 10000) {
      $kwh_tier_2 = $kWh_Total;
    }
    // multiple ship correction factor
    $kwh_tier_2 = $kwh_tier_2/2;
    $COST["Energy_Cost_Tier_2"] = $kwh_tier_2 * $cost["Energy_Rate_2"];
  }

  // 10000 - 15000 kWh
  if($kWh_Total > 10000) {
    $kwh_tier_3 = 15000;
    if($kWh_Total < 15000) {
      $kwh_tier_3 = $kWh_Total;
    }
    // multiple ship correction factor
    $kwh_tier_3 = $kwh_tier_3/2;
    $COST["Energy_Cost_Tier_3"] = $kwh_tier_3 * $cost["Energy_Rate_3"];
  }

  // 10000 - 15000 kWh
  if($Demand > 0) {
    $kwh_tier_4 = 400 * $Demand;
    $COST["Energy_Cost_Tier_4"] = $kwh_tier_4 * $cost["Energy_Rate_4"];
  }

  // Additional kWh
  if($kWh_Total > ($kwh_tier_1+$kwh_tier2+$kwh_tier3+$kwh_tier4)) {
    $kwh_tier_5 = $kWh_Total - ($kwh_tier_1+$kwh_tier2+$kwh_tier3+$kwh_tier4);
    $COST["Energy_Cost_Tier_5"] = $kwh_tier_5 * $cost["Energy_Rate_5"];
  }

  $COST["Energy_Cost"] = $COST["Energy_Cost_Tier_1"] + $COST["Energy_Cost_Tier_2"] + $COST["Energy_Cost_Tier_3"] + $COST["Energy_Cost_Tier_4"] + $COST["Energy_Cost_Tier_5"];

  // Riders
  $COST["Rider_Fuel_kWh_Cost"] = $kWh_Total*$cost["Rider_Fuel_kWh"];
  $COST["Rider_Capacity_kWh_Cost"] = $kWh_Total*$cost["Rider_Capacity_kWh"];
  $COST["Rider_EAC_kWh_Cost"] = $kWh_Total*$cost["Rider_EAC_kWh"];

  $COST["Rider_Total_Cost"] = $COST["Rider_Fuel_kWh_Cost"] + $COST["Rider_Capacity_kWh_Cost"] + $COST["Rider_EAC_kWh_Cost"];

  $COST["Peak_kWh_Cost"] = $COST["Energy_Cost"];
  $COST["Off_Peak_kWh_Cost"] = 0;
  $COST["Other_Energy_Cost"] = $COST["Rider_Total_Cost"];
  $COST["Total_kWh_Cost"] = $COST["Energy_Cost"] + $COST["Rider_Total_Cost"];

  $COST["Peak_kW_Cost"] = $COST["Total_kW_Cost"];
  $COST["Off_Peak_kW_Cost"] = 0;
  $COST["Other_Demand_Cost"] = 0;

  $COST["Total_Demand_and_Energy"] = $COST["Total_kWh_Cost"] + $COST["Total_kW_Cost"];

  $COST["Taxes_Add_Fees"] = ($cost["Street_Use_Franchise_Fee"] + $cost["Storm_Securitization_Fee"])/2;

  $COST["Formula_Rate_Plan_Cost"] = $COST["Total_Demand_and_Energy"]*$cost["Formula_Rate_Plan_Percentage"];
  $COST["MISO_Recovery_Cost"] = $COST["Total_Demand_and_Energy"]*$cost["MISO_Recovery_Percentage"];

  $COST["Total_Other_Cost"] = $COST["Formula_Rate_Plan_Cost"] + $COST["MISO_Recovery_Cost"];

  $COST["Grand_Total_Cost"] = $COST["Total_Demand_and_Energy"] + $COST["Taxes_Add_Fees"] + $COST["Total_Other_Cost"];

  return $COST;
}
?>
