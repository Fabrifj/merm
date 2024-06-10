<?php
const AUTH_TABLE = "Equate_User";
const ACCESS_TABLE = "Equate_User_Access";
const USERNAME = 'Username';
const ACCESS = 'Access_Level';
const COMPANY = 'Company';
const OWNER = 'Owner';
const AQUISUITE = 'aquisuitetablename';
const SHIP_GROUP = 'Ship_Group';
const DEFAULT_SHIP_CLASS = 'Default_Ship_Class';

function origin() {
  $http = (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS'])? 'https://': 'http://';

  return $http.$_SERVER['HTTP_HOST'];
}

function goHome() {
  $origin = origin();
  header("location: ".$origin);
}

function activateSession($member, $homepage) {
  if(isset($_SESSION['error_message'])) {
    unset($_SESSION['error_message']);
  }

  $_SESSION['user_data']['username'] = $member[USERNAME];
  $_SESSION['user_data']['access'] = $member[ACCESS];
  $_SESSION['user_data']['aquisuite'] = $member[AQUISUITE];
  $_SESSION['user_data']['company'] = $member[COMPANY];
  $_SESSION['user_data']['homepage'] = $homepage;

  setLevelAccess($member, $homepage);
  setModLinks($member[USERNAME], $member[DEFAULT_SHIP_CLASS]);
}

function authenticate() {
  if(empty($_POST['myusername']) || empty($_POST['mypassword'])) {
    $_SESSION['error_message'] = 'Username and password cannot be blank';

    goHome();
    return;
  }

  $myusername=$_POST['myusername'];
  $mypassword=$_POST['mypassword'];

  // To protect MySQL injection (more detail about MySQL injection)
  $myusername = stripslashes($myusername);
  $mypassword = stripslashes($mypassword);
  $myusername = mysql_real_escape_string($myusername);
  $mypassword = mysql_real_escape_string($mypassword);

  $sql="SELECT * FROM " . AUTH_TABLE . " WHERE Username='$myusername' and Password='$mypassword'";
  $result=mysql_query($sql);

  if(!$result) {
    $_SESSION['error_message'] = "An unexpexted error occurred. Please try again later";
    goHome();
    return;
  }

  $member=mysql_fetch_array($result);
  $access=$member[ACCESS];
  $defaultShipClass=$member[DEFAULT_SHIP_CLASS];
  $defaultMod="mod1";
  $defaultDisplay="day";
  $count=mysql_num_rows($result);

  if($count != 1) {
    $headerTxt = sprintf("location: %s", origin());
    $_SESSION['error_message'] = "Invalid username or password";
  } else if($access=='Level_7') {
    $homepage = level7ModHomepage($myusername, $defaultShipClass, $defaultMod, $defaultDisplay);
    $homepage = sprintf("%s/upload/jp_graph/graphs/erms_module1.php?display=day&user=%s&module=mod1", origin(), $myusername);
    $headerTxt = sprintf("location: %s", $homepage);
    activateSession($member, $homepage);
  } else if($access=='Level_3'){
    $homepage = level3ModHomepage($myusername, $defaultShipClass, $defaultMod);
    $headerTxt = sprintf("location: %s", $homepage);
    activateSession($member, $homepage);
  } else {
    $headerTxt = sprintf("location: %s", origin());
    $_SESSION['error_message'] = "Management level unavailable";
  }
  header($headerTxt);
}

function isAuthenticated($no_redirect = false) {
  if(!isset($_SESSION['user_data'])) {
    $_SESSION['error_message'] = 'Your session has timed out. Please log in';
    if(!$no_redirect) {
      goHome();
    }
    return false;
  }

  return true;
}

function isPermitted($user, $class, $ship = null, $no_redirect = false) {
  $permitted = true;

  if($_SESSION['user_data']['username'] != $user) {
    $permitted = false;
  }
  if(isset($ship) && !hasShipPermission($ship)) {
    $permitted = false;
  }

  if(!hasAccessLevelPermission($class)) {
    $permitted = false;
  }

  if(!$permitted) {
    if(!$no_redirect) {
      header('location: '.$_SESSION['user_data']['homepage']);
    }
  }

  return $permitted;
}

function hasAccessLevelPermission($class) {

  $accessLevel = $_SESSION['user_data']['access'];
  if($accessLevel == "Level_3") {
    return hasLevel3Permission($class);
  }

  if($accessLevel == "Level_7") {
    return hasLevel7Permission();
  }

  return true;
}

function hasShipPermission($ship) {
  $permittedShips = $_SESSION['user_data']['permittedShips'];
  if($ship == "") {
    return true;
  }
  return in_array($ship, $permittedShips);
}

function hasLevel3Permission($class) {
  $permittedShipClasses = $_SESSION['user_data']['permittedShipClasses'];
  return array_key_exists($class, $permittedShipClasses);
}

function level3ModHomepage($myusername, $shipClass, $mod) {
  return sprintf("%s/upload/jp_graph/graphs/erms_grp_module.php?display=day&user=%s&module=%s&shipClass=%s", origin(), $myusername, $mod, $shipClass);
}

function level7ModHomepage($myusername, $shipClass, $mod, $display) {
//if(isset($_REQUEST["display"])) {
//  $display = $_REQUEST["display"];
//}
$ship = "";
if(isset($_REQUEST["ship"])) {
  $ship = $_REQUEST["ship"];
}
$modLink = sprintf("%s/upload/jp_graph/graphs/erms_module1.php?display=%s&user=%s&module=%s&shipClass=%s&ship=%s", origin(), $display, $myusername, $mod, $shipClass, $ship);

//if(isset($_REQUEST["todo"])) {
//  $modLink = sprintf($modLink."&todo=%s", $_REQUEST["todo"]);
//}
//if(isset($_REQUEST["start_date_time"])) {
//  $modLink = sprintf($modLink."&start_date_time=%s", $_REQUEST["start_date_time"]);
//}
//if(isset($_REQUEST["stop_date_time"])) {
//  $modLink = sprintf($modLink."&stop_date_time=%s", $_REQUEST["stop_date_time"]);
//}
return $modLink;
}

function hasLevel7Permission() {
  return true;
}

function setShipClass($shipClass) {
    $groups = $_SESSION['user_data']['permittedShipClasses'];
    $defaultShipClass = $_SESSION['user_data']['defaultShipClass'];
    $nextShipClass = $groups[$shipClass] ? $shipClass : $defaultShipClass;
    $_SESSION['user_data']['permittedShips'] = $groups[$nextShipClass]['ships'];
    $_SESSION['user_data']['currentShipClass'] = $nextShipClass;
}

function setModLinks($username, $shipClass, $shipDeviceClass = "") {
  $accessLevel = $_SESSION['user_data']['access'];

  if($accessLevel == "Level_3") {
    setLevel3ModLinks($username, $shipClass);
    setLevel7ModLinks($username, $shipClass, $shipDeviceClass);
  }

  if($accessLevel == "Level_7") {
    setLevel7ModLinks($username, $shipClass, $shipDeviceClass);
  }
}

function setLevel3ModLinks($username, $shipClass) {
    $_SESSION['user_data']['mgrMods'] = array(
      "mod1" => array(
        "indicator" => $_SESSION['user_data']['shipGroup'],
        "text" => "Energy, Demand and Cost Analysis",
        "link" => level3ModHomepage($username, $shipClass, "mod1")
      ),
      "mod3" => array(
        "indicator" => $_SESSION['user_data']['shipGroup'],
        "text" => "Energy Meter Trending",
        "link" => level3ModHomepage($username, $shipClass, "mod3")
      ),
      "mod8" => array(
        "indicator" => $_SESSION['user_data']['shipGroup'],
        "text" => "Performance Trending",
        "link" => sprintf("%s&year=last12&report=report", level3ModHomepage($username, $shipClass, "mod8"))
      )
    );
    foreach($_SESSION['user_data']['permittedShipClasses'] as $cls => $group) {
      // TODO fix this. A TERRIBLE hack to get this rats nest to link properly
      $year = "";
      $report = "";
      if(isset($_REQUEST['year'])) {
        $year = "&year=".$_REQUEST['year'];
      }
      if(isset($_REQUEST['report'])) {
        $report = "&report=".$_REQUEST['report'];
      }
      $_SESSION['user_data']['permittedShipClasses'][$cls]["homepage"] = sprintf('%s%s%s', level3ModHomepage($username, $cls, $_REQUEST['module']), $year, $report);
    }
}

function setLevel7ModLinks($username, $shipClass, $shipDeviceClass) {
  $_SESSION['user_data']['shipMods'] = array(
    "mod0" => array(
      "text" => "Energy, Demand and Cost Analysis",
      "link" =>  level7ModHomepage($username, $shipClass, "mod0", "month")
    ),
    "mod1" => array(
      "text" => "Power and Demand Analysis",
      "link" => level7ModHomepage($username, $shipClass, "mod1", "day")
    )
  );
  if($shipDeviceClass != 27) {
    $_SESSION['user_data']['shipMods']["mod3"] = array(
      "text" => "Energy Meter Data",
      "link" => level7ModHomepage($username, $shipClass, "mod3", "day")
    );
  }
  $_SESSION['user_data']['shipMods']["mod6"] = array(
    "text" => "Monthly Reports",
    "link" => level7ModHomepage($username, $shipClass, "mod6", "month")
  );
}

function setBreadcrumbs($view, $module, $indicator) {
  $_SESSION['user_data']['breadcrumbs'] = array();
  $accessLevel = $_SESSION['user_data']['access'];
  $modHome = "mod1";

  if ($accessLevel == "Level_3") {
    $mgrLinks = $_SESSION['user_data']['mgrMods'][$modHome];
    if($view == "ship") {
      $_SESSION['user_data']['breadcrumbs'] = array(
        array(
          "link" => $mgrLinks["link"],
          "text" => $mgrLinks["indicator"]
        )
      );
    }
  }
  array_push($_SESSION['user_data']['breadcrumbs'],
    array(
      "module" => $module,
      "indicator" => $indicator
    )
  );
}

function setLevelAccess($member) {
  $accessLevel = $member[ACCESS];
  $aquisuiteTableName = $member[AQUISUITE];
  $company = $member[COMPANY];
  $sqlAccess = "SELECT * FROM Equate_User_Access WHERE $accessLevel='$aquisuiteTableName' AND Owner='$company'";

  $resultAccess = mysql_query($sqlAccess);

  if(!$resultAccess) {
    $_SESSION['error_message'] = "An unexpexted error occurred. Please try again later";
    goHome();
    return;
  }

  if($accessLevel == "Level_3") {
    while($rowAccess = mysql_fetch_array($resultAccess)) {
      $shipClass = $rowAccess['Ship_Class'];
      $groups[$shipClass]['ships'][] = $rowAccess[AQUISUITE];
      $groups[$shipClass]['name'] = $rowAccess['Ship_Class_Name'];
      $groups[$shipClass]['homepage'] = level3ModHomepage($member[USERNAME], $shipClass, "mod1");
    }

    $_SESSION['user_data']['shipGroup'] = $member[SHIP_GROUP];
    $_SESSION['user_data']['defaultShipClass'] =  $member[DEFAULT_SHIP_CLASS];
    $_SESSION['user_data']['currentShipClass'] = $member[DEFAULT_SHIP_CLASS];
    $_SESSION['user_data']['permittedShipClasses'] = $groups;
    $_SESSION['user_data']['permittedShips'] = $groups[$member[DEFAULT_SHIP_CLASS]]['ships'];
  }

  if($accessLevel == "Level_7") {
    while($rowAccess = mysql_fetch_array($resultAccess)) {
      $ships[] = $rowAccess[AQUISUITE];
    }
    $_SESSION['user_data']['permittedShips'] = $ships;
  }
}

function logout() {
  // Unset all of the session variables.
  $_SESSION = array();

  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
          $params["path"], $params["domain"],
          $params["secure"], $params["httponly"]
      );
  }

  // Finally, destroy the session.
  session_destroy();
}

?>
