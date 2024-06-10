<?php
  function ve_and_pc($val, $cost) {
    global $log;

    $Off_Peak_kWh_Total = $val["Off_Peak_kWh_Total"];
    $Peak_kWh_Total = $val["Peak_kWh_Total"];
    $Peak_Demand = $val["Peak_Demand"];
    $Lay_Days = $val["Lay_Days"];

    $kWh_Monthly_Totals = $val["kWh_Monthly_Totals"];
    $COST["U_Customer_Charge"] = $cost["Customer_Charge"];
    $COST["U_Peak_kW_Demand_1_Cost"] = $cost["Peak_kW_Demand_1"];

    $sql = "SELECT Virginia_Electric_and_Power_Co_Rates.*
              FROM Virginia_Electric_and_Power_Co
              LEFT JOIN Virginia_Electric_and_Power_Co_Rates ON Virginia_Electric_and_Power_Co_Rates.Virginia_Electric_and_Power_Co_id = Virginia_Electric_and_Power_Co.id";
    debugPrint("(mod_cost)(Virginia_Electric_and_Power_Co): $sql");
    $res = mysql_query($sql);

    while($row = mysql_fetch_array($res)) {
      $month = $row["Month"];
      $COST["U_Monthly_Rates"][$month] = array(
        "Peak_kWh" => $row["Peak_kWh"],
        "Off_Peak_kWh" => $row["Off_Peak_kWh"]
      );
      $COST["Monthly_Peak_kWh_Cost"][$month] = $kWh_Monthly_Totals[$month]["Peak_kWh"] * $row["Peak_kWh"];
      $COST["Monthly_Off_Peak_kWh_Cost"][$month] = $kWh_Monthly_Totals[$month]["Off_Peak_kWh"] * $row["Off_Peak_kWh"];
    }

    // kW Peak and Off-Peak Cost
    if($Peak_Demand > 500) {
      $log->logInfo("(mod_cost)(Virginia_Electric_and_Power_Co): Peak Demand is larger than the defaulted 500 kW");
    }
    $COST["Peak_kW_Demand_Cost"] = 500*$COST["U_Peak_kW_Demand_1_Cost"]; //*$Peak_Month_Days;
    $COST["Peak_kW_Cost"] = $COST["Peak_kW_Demand_Cost"];

    $COST["Off_Peak_kW_Cost"] = 0;

    $COST["Other_Demand_Cost"] = 0;

    $COST["Total_kW_Cost"] = $COST["Peak_kW_Cost"] + $COST["Off_Peak_kW_Cost"] + $COST["Other_Demand_Cost"];

    // kWh Peak and Off-Peak Cost
    if(!empty($Lay_Days))
    {
        $COST["Peak_kWh_Cost"] = (array_sum($COST["Monthly_Peak_kWh_Cost"])/$Lay_Days)*30;
        $COST["Off_Peak_kWh_Cost"] = (array_sum($COST["Monthly_Off_Peak_kWh_Cost"])/$Lay_Days)*30;;
        $COST["kWh_Total"] = (($Peak_kWh_Total+$Off_Peak_kWh_Total)/$Lay_Days)*30;
    }

    $COST["Other_Energy_Cost"] = 0;
    $COST["Total_kWh_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Off_Peak_kWh_Cost"]+$COST["Other_Energy_Cost"];

    // Sub Totals before taxes and other fees.
    $COST["Total_Peak_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Peak_kW_Cost"];
    $COST["Total_Off_Peak_Cost"] = $COST["Off_Peak_kWh_Cost"];

    $COST["Total_Cost"] = $COST["Total_Peak_Cost"]+$COST["Total_Off_Peak_Cost"];

    //Taxes and Other Fees
    $COST["Basic_Customer_Cost"] = $COST["U_Customer_Charge"];
    $COST["Taxes_Add_Fees"] = $COST["Basic_Customer_Cost"];
    $COST["Taxes_and_Other"] = $COST["Taxes_Add_Fees"];

    //Grand Total After Taxes and Other Fees
    $COST["Grand_Total_Cost"] = $COST["Total_Cost"]+$COST["Taxes_Add_Fees"];

    return $COST;
  }
?>
