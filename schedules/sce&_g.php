<?php
  function sce_and_g_24($ship, $val, $cost) {
    global $log;
    global $key;
    global $aquisuitetablename;
    global $device_class;

    $date_value_start = $val["date_value_start"];
    $date_value_end = $val["date_value_end"];
    $Off_Peak_kWh_Total = $val["Off_Peak_kWh_Total"];
    $Peak_kWh_Total = $val["Peak_kWh_Total"];
    $Peak_Billed_Demand = $val["Peak_Billed_Demand"];
    $Off_Peak_Billed_Demand = $val["Off_Peak_Billed_Demand"];
    $OPD_mtime = $val["OPD_mtime"];
    $Lay_Days = $val["Lay_Days"];
    $Peak_kWh_Field="Peak_kWh";

    $Utility = $cost[0];
    $Rate = $cost[1];
    $COST["U_Customer_Charge"] = $cost[2];
    $COST["U_Summer_Peak_Demand_kW"]= $cost[3];
    $COST["U_Non_Summer_Peak_Demand_kW"] = $cost[4];
    $COST["U_Off_Peak_Demand_kW"] = $cost[5];
    $COST["U_Summer_Peak_kWh"] = $cost[6];
    $COST["U_Non_Summer_Peak_kWh"] = $cost[7];
    $COST["U_Off_Peak_kWh_rate"] = $cost[8];
    $COST["U_Franchise_Fee"] = $cost[9];
    $COST["U_Summer_Start"] = idate(m,strtotime($cost[10]));
    $COST["U_Summer_End"] = idate(m,strtotime($cost[11]));
    $summer_start_date = $cost[10];
    $summer_end_date = $cost[11];

    if($OPD_mtime>=$COST["U_Summer_Start"] && $OPD_mtime<$COST["U_Summer_End"]) {
        $COST["Peak_kW_Cost"] = $Peak_Billed_Demand*$COST["U_Summer_Peak_Demand_kW"];
    } else {
        $last_summer_high_demand_point = get_high_point($ship,$summer_start_date,$summer_end_date);
        $percent_last_summer = $last_summer_high_demand_point * .80; //get 80% of last summers high point;
        $billed_demand_value = max($Peak_Billed_Demand, $percent_last_summer); //billed demand value is greater of max demand for current time period or 80 percent of last summer's high demand
        $COST["Peak_kW_Cost"] = $billed_demand_value*$COST["U_Non_Summer_Peak_Demand_kW"]; //non-summer
        debugPrint('(energy_method:mod_cost) last summer high'.$last_summer_high_demand_point.' 80 percent= '.$percent_last_summer.' current demand '.$Peak_Billed_Demand.' using demand value'.$billed_demand_value);

    }

    $COST["Off_Peak_kW_Cost"] = $Off_Peak_Billed_Demand*$COST["U_Off_Peak_Demand_kW"];
    $COST["Total_kW_Cost"] = $COST["Peak_kW_Cost"]+$COST["Off_Peak_kW_Cost"];
    $COST["Other_Demand_Cost"] = 0;

    $COST["Peak_kWh_Cost"] = kWh_cost
                            (
                                "time",
                                $date_value_start,
                                $date_value_end,
                                $Peak_kWh_Field,
                                $ship,
                                $COST["U_Summer_Start"],
                                $COST["U_Summer_End"],
                                $COST["U_Summer_Peak_kWh"],
                                $COST["U_Non_Summer_Peak_kWh"],
                                $Peak_kWh_Total
                            );
    $COST["Off_Peak_kWh_Cost"] = $Off_Peak_kWh_Total*$COST["U_Off_Peak_kWh_rate"];
    $COST["Other_Energy_Cost"] = 0;

    debugPrint('mod_cost: Lay Days '.$Lay_Days);

    if(!empty($Lay_Days))
    {
        $COST["Total_kWh_Cost"] = ($COST["Peak_kWh_Cost"]+$COST["Off_Peak_kWh_Cost"]);
        $COST["kWh_Total"] = ($Peak_kWh_Total+$Off_Peak_kWh_Total);
    }

    $COST["Total_Peak_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Peak_kW_Cost"];

    $COST["Total_Off_Peak_Cost"] = $COST["Off_Peak_kWh_Cost"]+$COST["Off_Peak_kW_Cost"];

    $COST["Total_Cost"] = $COST["Total_kW_Cost"]+$COST["Total_kWh_Cost"];

    if ($Lay_Days > 0)
    {
        $COST["Sub_Total_Cost"] = $COST["Total_Cost"]+$COST["U_Customer_Charge"];

        //Taxes & other fees
        $COST["Franchise_Fee_Cost"] = $COST["U_Franchise_Fee"]*$COST["Sub_Total_Cost"];
        $COST["Taxes_Add_Fees"] = $COST["Franchise_Fee_Cost"]+$COST["U_Customer_Charge"];
        $COST["Taxes_and_Other"] = $COST["Taxes_Add_Fees"];
    }

    //Grand Total
    $COST["Grand_Total_Cost"] = $COST["Total_Cost"]+$COST["Taxes_Add_Fees"];
    debugPrint('mod_cost: Grand total cost '.$COST["Grand_Total_Cost"].' Total Cost '.$COST["Total_Cost"].' taxes and other '. $COST["Taxes_and_Other"]." franchise ". $COST["Franchise_Fee_Cost"]);

    return $COST;
  }
?>
