<?php

function navy_fed($cost) {
  global $log;

  $Peak_kWh_Total = $val["Peak_kWh_Total"];
  $Lay_Days = $val["Lay_Days"];

  $Utility=$cost['Utility'];
  $Rate=$cost['Rate'];
  $COST["kWh_cost"] = $cost['Cost_kWh'];

  if(!empty($Lay_Days))
  {
      $COST["Total_kWh_Cost"] = (($Peak_kWh_Total*$COST["kWh_cost"])/$Lay_Days)*30;
      $COST["kWh_Total"] = ($Peak_kWh_Total/$Lay_Days)*30;
  }
  $COST["Grand_Total_Cost"] = $COST["Total_kWh_Cost"];

  return $COST;
}

?>
