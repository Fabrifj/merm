<?php
function virginia_dominion_gs3($val, $cost) {
        global $log;

        $Off_Peak_kWh_Total = $val["Off_Peak_kWh_Total"];
        $Peak_kWh_Total = $val["Peak_kWh_Total"];
        $Peak_Demand = $val["Peak_Demand"];
        $kVAR_Demand = $val["kVAR_Demand"];
        $Off_Peak_Demand = $val["Off_Peak_Demand"];
        $Peak_Demand_Time = $val["Peak_Demand_Time"];
        $Lay_Days = $val["Lay_Days"];

        $Utility=$cost['Utility'];
        $Rate=$cost['Rate'];
        $COST["U_Customer_Charge"]=$cost['Customer_Charge'];
        $COST["U_Demand_rkVA"]=$cost['Demand_rkVA'];
        $COST["U_Distribution_Demand"]=$cost['Distribution_Demand'];
        $COST["U_ESS_Adjustment_Charge"]=$cost['ESS_Adjustment_Charge'];
        $COST["U_Peak_kWh"]=$cost['Peak_kWh'];
        $COST["U_Off_Peak_kWh"]=$cost['Off_Peak_kWh'];
        $COST["U_Peak_kW"]=$cost['Peak_kW'];
        $COST["U_Off_Peak_kW"]=$cost['Off_Peak_kW'];
        $COST["U_Fuel_Charge"]=$cost['Fuel_Charge'];
        $COST["U_Rider_R_Peak_kW"]=$cost['Rider_R_Peak_kW'];
        $COST["U_Rider_S_Peak_kW"]=$cost['Rider_S_Peak_kW'];
        $COST["U_Rider_T_Peak_kW"]=$cost['Rider_T_Peak_kW'];
        $COST["U_Rider_R_Credit"]=$cost['Rider_R_Credit'];
        $COST["U_Rider_S_Credit"]=$cost['Rider_S_Credit'];
        $COST["U_Rider_T_Credit"]=$cost['Rider_T_Credit'];
        $COST["U_Sales_kWh"]=$cost['Sales_kWh'];
        $COST["U_Tax_Rate_1"]=$cost['Tax_Rate_1'];
        $COST["U_Tax_Rate_2"]=$cost['Tax_Rate_2'];
        $COST["U_Tax_Rate_3"]=$cost['Tax_Rate_3'];
        $COST["U_Utility_tax"]=$cost['Utility_tax'];

        #### COST CALCULATIONS ####

        // The following cost calculations are based on
        // The Utility rate structure and the values
        // calculated above.

        $Peak_Month_Days = idate(t, strtotime($Peak_Demand_Time))/30;

        // kW Peak and Off-Peak Cost
        $COST["Peak_kW_Demand_Cost"] = max(545/3, $Peak_Demand, 100)*$COST["U_Peak_kW"]*$Peak_Month_Days;
        $COST["ESS_Adj_Cost"] = $Peak_Demand*$COST["U_ESS_Adjustment_Charge"]*$Peak_Month_Days;
        $COST["Other_ESS_Riders"] = $Peak_Demand*($COST["U_Rider_R_Peak_kW"]+$COST["U_Rider_S_Peak_kW"]+$COST["U_Rider_T_Peak_kW"])+$Peak_Demand*($COST["U_Rider_R_Credit"]+$COST["U_Rider_S_Credit"])*$Peak_Month_Days;
        $COST["Peak_kW_Cost"] = $COST["Peak_kW_Demand_Cost"]+$COST["ESS_Adj_Cost"]+$COST["Other_ESS_Riders"];

        $COST["Off_Peak_kW_Cost"] = 0;

        if($Off_Peak_Demand-($Peak_Demand*.9)>0)
        {
            $COST["Off_Peak_kW_Cost"] = $COST["U_Off_Peak_kW"]*($Off_Peak_Demand-($Peak_Demand*.9));
        }

        $COST["kVAR_Demand_Cost"] = $COST["U_Demand_rkVA"]*$kVAR_Demand*$Peak_Month_Days;
        $COST["Distribution_Demand_Cost"] = $COST["U_Distribution_Demand"]*$Peak_Demand*$Peak_Month_Days;
        $COST["Other_Demand_Cost"] = $COST["kVAR_Demand_Cost"]+$COST["Distribution_Demand_Cost"];

        $COST["Total_kW_Cost"] = $COST["Peak_kW_Cost"]+$COST["Off_Peak_kW_Cost"]+$COST["Other_Demand_Cost"];

        // kWh Peak and Off-Peak Cost
        if(!empty($Lay_Days))
        {
            $COST["Peak_kWh_Cost"] = $Peak_kWh_Total*$COST["U_Peak_kWh"];
            $COST["Off_Peak_kWh_Cost"] = $Off_Peak_kWh_Total*$COST["U_Off_Peak_kWh"];
            $kWh_Total = $Peak_kWh_Total+$Off_Peak_kWh_Total;
        }
        $COST["Fuel_Cost"] = $COST["U_Fuel_Charge"]*$kWh_Total;
        $COST["Sales_Cost"] = $COST["U_Sales_kWh"]*$kWh_Total;

        if($kWh_Total>50000)
        {
            $COST["Consumption_Tax"] = ($kWh_Total-50000)*$COST["U_Tax_Rate_3"]+(50000-2500)*$COST["U_Tax_Rate_2"]+2500*$COST["U_Tax_Rate_1"];
        }
        else if($kWh_Total>2500 && $kWh_Total< 50000)
        {
            $COST["Consumption_Tax"] = ($kWh_Total-2500)*$COST["U_Tax_Rate_2"]+2500*$COST["U_Tax_Rate_1"];
        }
        else
        {
            $COST["Consumption_Tax"] = $kWh_Total*$COST["U_Tax_Rate_1"];
        }
        $COST["Other_Energy_Cost"] = $COST["Fuel_Cost"]+$COST["Sales_Cost"]+$COST["Consumption_Tax"];

        $COST["Total_kWh_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Off_Peak_kWh_Cost"]+$COST["Other_Energy_Cost"];

        // Sub Totals before taxes and other fees.
        $COST["Total_Peak_Cost"] = $COST["Peak_kWh_Cost"]+$COST["Peak_kW_Cost"];
        $COST["Total_Off_Peak_Cost"] = $COST["Off_Peak_kWh_Cost"]+$COST["Off_Peak_kW_Cost"];
        $COST["Total_Other_Cost"] = $COST["Other_Demand_Cost"]+$COST["Other_Energy_Cost"];

        $COST["Total_Cost"] = $COST["Total_Peak_Cost"]+$COST["Total_Off_Peak_Cost"]+$COST["Total_Other_Cost"];

        //Taxes and Other Fees
        $COST["Basic_Customer_Cost"] = $COST["U_Customer_Charge"]*$Peak_Month_Days;
        $COST["Taxes_Add_Fees"] = $COST["Basic_Customer_Cost"]+$COST["U_Utility_tax"];
        $COST["Taxes_and_Other"] = $COST["Taxes_Add_Fees"]+$COST["Total_Other_Cost"];

        //Grand Total After Taxes and Other Fees
        $COST["Grand_Total_Cost"] = $COST["Total_Cost"]+$COST["Taxes_Add_Fees"];

        return $COST;
}

?>
