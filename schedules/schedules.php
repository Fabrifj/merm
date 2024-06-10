<?php
  require_once('virginia_dominion.php');
  require_once('sce&_g.php');
  require_once('ve&_pc.php');
  require_once('navy_fed.php');
  require_once('entergy_no.php');

  function schedule_cost ($ship, $utility, $val, $cost) {
    // TODO remove these globals at some point
    global $log;
    global $key;
    global $aquisuitetablename;
    global $device_class;

    switch($utility) {
    case "Virginia_Dominion_Rates":
      return virginia_dominion_gs3($val, $cost);
      break;
    case "SCE&G_Rates":
      return sce_and_g_24($ship, $val, $cost);
      break;
    case "Virginia_Electric_and_Power_Co":
      return ve_and_pc($val, $cost);
      break;
    case "Nav_Fed_Rates":
      return navy_fed($cost);
      break;
    case "Entergy_NO_Rates":
      return entergy_le_hlf($val, $cost);
      break;
    }
  }
?>
