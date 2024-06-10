<?php

function printArray($array)
{
    foreach ($array as $key => $value)
    {
        echo "$key => $value</br>";
        if(is_array($value))
        {
            //If $value is an array, print it as well!
            printArray($value);
        }
    }
}
function access_check()
{
	if(isset($_REQUEST['user']))
	{
		$username = $_REQUEST['user'];
		$user_table = "Equate_User";
		$Access_Level_Field = "Access_Level";
		$Username_Field = "Username";

        //$sql = "SELECT * FROM `equate_user` WHERE `Username` LIKE '%$username%'";
		$sql = "SELECT * FROM Equate_User WHERE Username='$username'";
		debugPrint($sql);

        $RESULT = mysql_query($sql);

		if(!$RESULT)
		{
            MySqlFailure("access check failed for (".$username.")");
		}

		$row = mysql_fetch_array($RESULT);
		$access_level =$row['Access_Level'];
		$Title = $row["Title"];
        $Meter_Name = $row["Title"];
		$Company = $row["Company"];
		$aquisuitetablename = $row["aquisuitetablename"];
                $default_ship_class = $row["Default_Ship_Class"];

        debugPrint('(rows )['.mysql_num_rows($RESULT).'] (access level) '.$access_level.' (owner ) '.$Company."<br />");

		$access_table = "Equate_User_Access";
		$Data_Table_Field = "Data_Table";
		$Owner_Field = "Owner";

		$sql_2 = "SELECT * FROM Equate_User_Access WHERE ($access_level='$aquisuitetablename') AND (Owner='$Company')";

                if($default_ship_class) {
                  $sql_2 .= " AND Ship_Class='$default_ship_class'";
                }

		debugPrint($sql_2);

		$RESULT_2 = mysql_query($sql_2);

		if(!$RESULT_2)
		{
			MySqlFailure("Could not find aquisuitetablename ($aquisuitetablename) or company ($Company)");
		}

		while($row_2 = mysql_fetch_array($RESULT_2))
		{
			$ships[] = $row_2['aquisuitetablename'];
		}

        debugPrint('(rows )['.mysql_num_rows($RESULT_2).']'."<br />");

        //print_r ($ships);
		return $ships;
	}
}


/**
 * user_page()
 *
 * @return
 */
function user_page()
{
	if(isset($_REQUEST['user']))
	{
		$username = $_REQUEST['user'];
		$user_table = "Equate_User";
		$Access_Level_Field = "Access_Level";
		$Username_Field = "Username";

		$sql = "SELECT * FROM $user_table WHERE $Username_Field='$username'";
		$RESULT = mysql_query($sql);

		if(!$RESULT)
		{
		  MySqlFailure("access check faled".$username);
		}

		$row = mysql_fetch_array($RESULT);
		$access_level =$row["$Access_Level_Field"];
		$Title = $row["Title"];
		$Company = $row["Company"];
		$access_number = substr($access_level,-1,1)+1;

		$access_table = "Equate_User_Access";
		$Data_Table_Field = "Data_Table";
		$Owner_Field = "Owner";

		$sql_2 = "SELECT * FROM $access_table WHERE ($access_level='$Title') AND ($Owner_Field='$Company')";
		debugPrint($sql_2);

        $RESULT_2 = mysql_query($sql_2);

		while($row_2=mysql_fetch_array($RESULT_2))
		{
			$ships[] = $row_2['Level_'."$access_number"];
		}

		$ships['Title'] = $Title;

		return $ships;

	}
}

/**
 * data_table_check()
 *
 * @return
 */
function data_table_check()
{
	if(isset($_REQUEST['user']))
	{
		$username = $_REQUEST['user'];
		$user_table = "Equate_User";
		$Access_Level_Field = "Access_Level";
		$Username_Field = "Username";

		$sql = "SELECT * FROM $user_table WHERE $Username_Field='$username'";
		$RESULT = mysql_query($sql);

		if(!$RESULT)
		{
		  MySqlFailure("access check faled".$username);
		}
		$row = mysql_fetch_array($RESULT);
		$Data_Table = $row["Data_Table"];
	}
	return $Data_Table;
}

/**
 * utility_check()
 *
 * @param mixed $ship
 * @return
 */
function utility_check($ship)
{
	// query for finding the correct
	// utility for a particular ship

	$sql = "SELECT utility FROM Aquisuite_List WHERE aquisuitetablename='$ship'";

	$RESULT = mysql_query($sql);

	if(!$RESULT) {
		MySqlFailure("utility check failed");
	}

	$row_utility=mysql_fetch_array($RESULT);
	$utility=$row_utility[0];
        //debugPrint('utility_check: '.$sql.' utility '.$utility);

	return $utility;
}

/**
 * class_check()
 *
 * @param mixed $ship
 * @return
 */
function class_check($ship, $access_level)
{
	// delcaring variables for Access
	// Table specific to the vessel

	$class_table = "Equate_User_Access";
	$Class_Field = "Ship_Class";
	$data_tbl = "aquisuitetablename";
	$Owner_Field = "Owner";

	// query that returns all the
	// ships in a specific class

	$sql = "SELECT $Class_Field, $Owner_Field, $access_level FROM $class_table WHERE $data_tbl='$ship'";
	$RESULT = mysql_query($sql);

	if(!$RESULT)
	{
	MySqlFailure("utility check failed".$class_table);
	}

	$row_class=mysql_fetch_row($RESULT);
	$class=$row_class[0];
	$owner=$row_class[1];
        $group= ($row_class[2] ? $row_class[2] : $ship);

        return ships_in_class($owner, $class, $access_level, $group);
}

/**
 * ships_in_class()
 */
function ships_in_class($owner, $class, $access_level, $group) {
  global $log;
	$class_table = "Equate_User_Access";
	$Class_Field = "Ship_Class";
	$data_tbl = "aquisuitetablename";
	$Owner_Field = "Owner";

	$sql_ships = "SELECT $data_tbl FROM $class_table WHERE ($Class_Field='$class') AND ($Owner_Field='$owner') AND ($access_level='$group')";
        $log->logInfo("(ships_in_class): $sql_ships");
	$ships_qer = mysql_query($sql_ships);

	if(!$ships_qer) {
	  MySqlFailure("utility check failed ".$Class_Field);
	}

	while ($row_class=mysql_fetch_array($ships_qer)) {
	  $ships_in_class[] = $row_class["$data_tbl"];
	}

	return $ships_in_class;
}

/**
 * aq_ main_device()
 */

function main_device_info ($aquisuite) {
  global $log;

  $sql = sprintf("SELECT aq.modbusdevicenumber,  aq.deviceclass, aq.devicetablename, aq_list.timezoneaquisuite, aq_list.utility, aq_list.utilitymaindevice, aq_list.utilityrate, aq_list.logperiod, tz.timezonephp
   FROM $aquisuite AS aq
   LEFT JOIN Aquisuite_List AS aq_list ON aq_list.utilitymaindevice = aq.modbusdevicenumber
   LEFT JOIN timezone AS tz on aq_list.timezoneaquisuite = tz.timezoneaquisuite
   WHERE aq_list.aquisuitetablename = '%s' LIMIT 1", $aquisuite);
  $log->logInfo("(main_device_info): $sql");
  $res = mysql_query($sql);

  if(!$res) {
    MySqlFailure("Failed to retrive device data");
  }

  return mysql_fetch_assoc($res);
}

/**
 * device_class_check()
 *
 * @param mixed $ship
 * @return
 */
function device_class_check($ship)
{
	global $key;
	global $aquisuitetablename;

	$device_class_check = "SELECT deviceclass FROM $aquisuitetablename[$key] WHERE devicetablename='$ship'";
	$device_class_checkQ = mysql_query($device_class_check);
	$row = mysql_fetch_array($device_class_checkQ);
	return $row['deviceclass'];
}

?>
