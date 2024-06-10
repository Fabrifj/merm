<?php
/*
 *  FIle: configfix.php
 *  Author: Carole Snow
 * 
 * Desc: This file contails functions to process relay output on Acquisuite Modules
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/
/*
 *  configfix is used to correct configuration files on a remote acquisuite module.
 * This script is contains hard coded variables that need to be changed foe each ship.
 * Flags need to be turned off/on for each section you wish to execute depending on the problem with the data.
 */
/***     includes      ***/

/*** globals ***/
global $log, $LOOPNAME;

/** define constants   **/
const ONE_MIN = 60;
const FIVE_MIN = 300;
const FIFTEEN_MIN = 900;
const ONE_HOUR = 3600;

/**-----------------------------------------------------------------------------------------------------------------
 * reporting function be used to report and terminate.
 */
function ReportFailure($szReason)
{   
    global $log, $LOOPNAME;
    
    Header("WWW-Authenticate: Basic realm=\"UploadRealm\"");    // realm name is actually ignored by the AcquiSuite.
    Header("HTTP/1.0 406 Not Acceptable");                      // generate a 400 series http server error response.

    $szNotes = sprintf("Rejected logfile upload"); 			 // print out some information about what failed, for the benifit of the log file.
    printf("FAILURE: $szReason\n");                			 // report failure to client.
    printf("NOTES:   $szNotes\n");

    ob_end_flush(); // send cached stuff, and stop caching. we've already kicked out the last header line.
    
    exit;
}


/*----------------------------------------------------------------------------------------------------*/
/* mySQL reporting function. terminates if error is fatal and prints out mySQL warnings for failure.
*/
function MySqlFailure($Reason)
{	
    global $log, $LOOPNAME;
    
       	echo "mySQL FAILURE:"."</br>";
	$con = $_SESSION['con'];		// used for getting the mysql connector for error printing
	$sql_errno = mysql_errno($con); 
	
	if($sql_errno>0)
	{	
		echo "mySQL FAILURE: $Reason"."</br>";
		echo  "mySQL FAILURE: $Reason"."</br>".$sql_errno. ": " . mysql_error($con) . "</br>";
             	ob_end_flush();   // send any cached stuff and stop caching. 
                sleep(1); 
		exit;
	}
	else
	{
		
		// the following counts the number of warnings in the query and prints them out.
		
		$warningCountResult = mysql_query("SELECT @@warning_count");		
		if ($warningCountResult) 
		{
			$warningCount = mysql_fetch_row($warningCountResult );
			if ($warningCount[0] > 0) 
			{
				$warningDetailResult = mysql_query("SHOW WARNINGS");
				if ($warningDetailResult ) 
				{		
					while ($warning = mysql_fetch_array($warningDetailResult)) 
					{	
						foreach ($warning AS $key => $value)
						{	
							if($value!==$value_repeat)
							{
								$value = $value." ";	// build all of the warnings into one.
							}
							$value_repeat = $value;		// make sure the warning isn't repeated.
						}
						echo "mySQL WARNING: $value"."</br>";		// print out the warnings
					}
				}
			}
		}
	}
}
/*----------------------------------------------------------------------------------------------------*/
/* function used to check that serial number and password exist in the Aquisuite_List.
*/
function Authenticate()
{
   global $log, $LOOPNAME;	
	// username and password sent from Aquisuite. 
	// If password doesn't exist then verify by
	// the Serial Number. This should eventually
	// be changed once each Aquisuite is updated
	// with a password.
	
	$myusername=$_REQUEST['SERIALNUMBER']; 
	$mypassword='';
	if(!empty($_REQUEST['PASSWORD']))
	{
		$mypassword=$_REQUEST['PASSWORD'];
	}
	
	// To protect MySQL injection (more detail about MySQL injection)
	$myusername = stripslashes($myusername);
	$mypassword = stripslashes($mypassword);
	$myusername = mysql_real_escape_string($myusername);
	$mypassword = mysql_real_escape_string($mypassword);

	$sql="SELECT * FROM `Aquisuite_List` WHERE SerialNumber='$myusername' and password='$mypassword'";

	// Mysql_num_row is counting table row
	$result=mysql_query($sql);
	
	if(!$result)
	{
		MySqlFailure("Unable to execute SQL query");
	}
	$count=mysql_num_rows($result);
	
	// if the serial number and password don't return a single row,
	// ternminate the process.
	if(!$count==1)
	{
		ReportFailure('serialnumber and password are not authorized');
	}
}

function utility_check($ship)
{
        global $log, $LOOPNAME;
	// query for finding the correct utility for a particular ship

	$sql = "SELECT utility FROM Aquisuite_List WHERE aquisuitetablename='$ship'";
	$RESULT = mysql_query($sql);

	if(!$RESULT) { MySqlFailure("utility check failed");	}
	
	$row_utility=mysql_fetch_array($RESULT);
	$utility=$row_utility[0];

	return $utility;
}

/*
 * Function status_upload     
 */
function status_upload($server_info, $request_info)
{
    global $log, $LOOPNAME;
    
    //$log->logInfo(sprintf("%s: STATUS\n",$LOOPNAME)); 
    
    printf("Status information from ip  %s \n", $server_info['REMOTE_ADDR'] );                // print the IP address of the DAS client.
    printf("Remote Port 				%s \n", $server_info['REMOTE_PORT'] );
    printf("Remote Host					%s \n", $server_info['REMOTE_HOST'] );
    printf("Server information from ip  %s \n", $server_info['SERVER_ADDR'] );                // print the IP address of the DAS client.
    printf("Server Port 				%s \n", $server_info['SERVER_PORT'] );
    printf("Server Host					%s \n", $server_info['SERVER_HOST'] );
    printf("Got SENDDATATRACE:          %s \n", $request_info['SENDDATATRACE'] );             // set when the A.S. or A.L. requests full session debug messages
    printf("Got MODE:                   %s \n", $request_info['MODE'] );                      // this shows what type of exchange we are processing.
    printf("Got SERIALNUMBER:           %s \n", $request_info['SERIALNUMBER'] );              // The acquisuite serial number
    printf("Got PASSWORD:               %s \n", $request_info['PASSWORD'] );                  // The acquisuite password
    printf("Got LOOPNAME:               %s \n", $request_info['LOOPNAME'] );                // The name of the AcquiSuite (modbus loop name)
    printf("Got UPTIME:                 %s \n", $request_info['UPTIME'] );                  // The number of seconds the DAS has been running

    printf("Got PERCENTBLOCKSINUSE:     %s \n", $request_info['PERCENTBLOCKSINUSE'] );      // the amount of memory in use for storing log file data
    printf("Got PERCENTINODESINUSE:     %s \n", $request_info['PERCENTINODESINUSE'] );      // the amount of memory in use for storing log file data
    printf("Got UPLOADATTEMPT:          %s \n", $request_info['UPLOADATTEMPT'] );           // first attempt, second attempt, etc.

    printf("Got ACQUISUITEVERSION:      %s \n", $request_info['ACQUISUITEVERSION'] );       // A.S. only, firmware version for as.cramfs.
    printf("Got USRVERSION:             %s \n", $request_info['USRVERSION'] );              // A.S. only, firmware version for usr.cramfs
    printf("Got ROOTVERSION:            %s \n", $request_info['ROOTVERSION'] );             // A.S. only, firmware version for root
    printf("Got KERNELVERSION:          %s \n", $request_info['KERNELVERSION'] );           // A.S. shows linux kernel version.  A.L. shows ucII version
    printf("Got FIRMWAREVERSION:        %s \n", $request_info['FIRMWAREVERSION'] );         // A.L. only, shows firmware version

    printf("Got BOOTCOUNT:              %s \n", $request_info['BOOTCOUNT'] );               // A.L. only, shows number of system startups.
    printf("Got BATTERYGOOD:            %s \n", $request_info['BATTERYGOOD'] );             // A.L. only, shows onboard battery status (YES/NO).

    printf("Got GSMSIGNAL:              %s \n", $request_info['GSMSIGNAL'] );               // GSM enabled AcquiSuite only, shows signal strenght/quality prior to gsm outbound call.  return value is "RSSI,BER"
    
    
    $sTest = "";
    $sTest .= sprintf("Status information from ip  %s \n", $server_info['REMOTE_ADDR'] );                // print the IP address of the DAS client.
    $sTest .= sprintf("Remote Port 		   %s \n", $server_info['REMOTE_PORT'] );
    $sTest .= sprintf("Remote Host		   %s \n", $server_info['REMOTE_HOST'] );
    $sTest .= sprintf("Server information from ip  %s \n", $server_info['SERVER_ADDR'] );                // print the IP address of the DAS client.
    $sTest .= sprintf("Server Port 		   %s \n", $server_info['SERVER_PORT'] );
    $sTest .= sprintf("Server Host		   %s \n", $server_info['SERVER_HOST'] );
    $requestTimeStamp =  date("Y-m-d H:i:s",$server_info['REQUEST_TIME']);
    $sTest .= sprintf("Request Time		   %s %s\n", $server_info['REQUEST_TIME'], $requestTimeStamp );
    $sTest .= sprintf("Got SENDDATATRACE:          %s \n", $request_info['SENDDATATRACE'] );             // set when the A.S. or A.L. requests full session debug messages
    $sTest .= sprintf("Got MODE:                   %s \n", $request_info['MODE'] );                      // this shows what type of exchange we are processing.
    $sTest .= sprintf("Got SERIALNUMBER:           %s \n", $request_info['SERIALNUMBER'] );              // The acquisuite serial number
    $sTest .= sprintf("Got PASSWORD:               %s \n", $request_info['PASSWORD'] );                  // The acquisuite password
    $sTest .= sprintf("Got LOOPNAME:               %s \n", $request_info['LOOPNAME'] );                // The name of the AcquiSuite (modbus loop name)
    $sTest .= sprintf("Got UPTIME:                 %s \n", $request_info['UPTIME'] );                  // The number of seconds the DAS has been running
    $sTest .= sprintf("Got PERCENTBLOCKSINUSE:     %s \n", $request_info['PERCENTBLOCKSINUSE'] );      // the amount of memory in use for storing log file data
    $sTest .= sprintf("Got PERCENTINODESINUSE:     %s \n", $request_info['PERCENTINODESINUSE'] );      // the amount of memory in use for storing log file data
    $sTest .= sprintf("Got UPLOADATTEMPT:          %s \n", $request_info['UPLOADATTEMPT'] );           // first attempt, second attempt, etc.
    $sTest .= sprintf("Got ACQUISUITEVERSION:      %s \n", $request_info['ACQUISUITEVERSION'] );       // A.S. only, firmware version for as.cramfs.
    $sTest .= sprintf("Got USRVERSION:             %s \n", $request_info['USRVERSION'] );              // A.S. only, firmware version for usr.cramfs
    $sTest .= sprintf("Got ROOTVERSION:            %s \n", $request_info['ROOTVERSION'] );             // A.S. only, firmware version for root
    $sTest .= sprintf("Got KERNELVERSION:          %s \n", $request_info['KERNELVERSION'] );           // A.S. shows linux kernel version.  A.L. shows ucII version
    $sTest .= sprintf("Got FIRMWAREVERSION:        %s \n", $request_info['FIRMWAREVERSION'] );         // A.L. only, shows firmware version
    $sTest .= sprintf("Got BOOTCOUNT:              %s \n", $request_info['BOOTCOUNT'] );               // A.L. only, shows number of system startups.
    $sTest .= sprintf("Got BATTERYGOOD:            %s \n", $request_info['BATTERYGOOD'] );             // A.L. only, shows onboard battery status (YES/NO).
    $sTest .= sprintf("Got GSMSIGNAL:              %s \n", $request_info['GSMSIGNAL'] );               // GSM enabled AcquiSuite only, shows signal strenght/quality prior to gsm outbound call.  return value is "RSSI,BER"
    $sTest .= sprintf("\n");

    $log->logInfo($sTest);
    
    $tz = date("Y-m-d H:i:s");
    $log->logInfo(sprintf("Date %s\n",$tz)); 

    $SerialNumber = $request_info['SERIALNUMBER'];
     printf("\n");   // blank line to make things look nice.

    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()

    printf("\nSUCCESS\n");   // this line is what the AcquiSuite/AcquiLite searches for in the response to
                            // tell it that the data was received and the original log file may be deleted.
    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()

    printf("</pre>\n");  // end of the script
}/** end status_upload *** */


/*
 * Function logfile_upload     
 */
function logfile_upload($aquisuitetable)
{
    global $log, $LOOPNAME;
    
    $sTest = "";
    $sTest .= sprintf("Logfile upload from ip      %s \n", $_SERVER['REMOTE_ADDR'] );               // print the IP address of the DAS client.
    $sTest .= sprintf("Got SENDDATATRACE:          %s \n", $_REQUEST['SENDDATATRACE'] );             // set when the AcquiSuite requests full session debug messages
    $sTest .= sprintf("Got MODE:                   %s \n", $_REQUEST['MODE'] );                      // this shows what type of exchange we are processing.
    $sTest .= sprintf("Got SERIALNUMBER:           %s \n", $_REQUEST['SERIALNUMBER'] );              // The acquisuite serial number
    $sTest .= sprintf("Got LOOPNAME:               %s \n", $_REQUEST['LOOPNAME']  ) ;                // The name of the AcquiSuite (modbus loop name)
    $sTest .= sprintf("Got MODBUSIP:               %s \n", $_REQUEST['MODBUSIP'] );                  // Currently, always 127.0.0.1, may change in future products.
    $sTest .= sprintf("Got MODBUSPORT:             %s \n", $_REQUEST['MODBUSPORT']  ) ;              // currently, always 502, may change in future products.
    $sTest .= sprintf("Got MODBUSDEVICE:           %s \n", $_REQUEST['MODBUSDEVICE']  ) ;            // the modbus device address on the physical modbus loop. (set by dip switches)
    $sTest .= sprintf("Got MODBUSDEVICENAME:       %s \n", $_REQUEST['MODBUSDEVICENAME']  ) ;        // the user specified device name.
    $sTest .= sprintf("Got MODBUSDEVICETYPE:       %s \n", $_REQUEST['MODBUSDEVICETYPE'] ) ;         // the identity string returned by the modbus device identify command
    $sTest .= sprintf("Got MODBUSDEVICETYPENUMBER: %s \n", $_REQUEST['MODBUSDEVICETYPENUMBER']  ) ;  // the identity number returned by the modbus device identify command
    $sTest .= sprintf("Got MODBUSDEVICECLASS:      %s \n", $_REQUEST['MODBUSDEVICECLASS'] ) ;        // a unique id number for the modbus device type.
    $sTest .= sprintf("Got MD5CHECKSUM:            %s \n", $_REQUEST['MD5CHECKSUM']  );              // the MD5 checksum the AcquiSuite generated prior to upload
    $sTest .= sprintf("calculated checksum:        %s \n", $szChecksum ) ;                           // the MD5 sum we calculated on the file we received.
    $sTest .= sprintf("Got FILETIME:               %s \n", $_REQUEST['FILETIME']  ) ;                // the date and time the file was last modified. (in UTC time).
    $sTest .= sprintf("Got FILESIZE:               %s \n", $_REQUEST['FILESIZE'] ) ;                 // the original size of the log file on the AcquiSuite flash disk prior to upload
    $sTest .= sprintf("calculated filesize:        %s \n", filesize($_FILES['LOGFILE']['tmp_name'])); // the calculated file size of the file we received..
    $sTest .= sprintf("Got LOGFILE orig name:      %s \n", $_FILES['LOGFILE']['name'] );             // This is original file name on the AcquiSuite flash disk.
    $sTest .= sprintf("Got LOGFILE tmp name:       %s \n", $_FILES['LOGFILE']['tmp_name'] );         // This is the PHP temp file name where PHP stored the file contents.
    $sTest .= sprintf("Got LOGFILE size:           %s \n", $_FILES['LOGFILE']['size']  );            // What PHP claims the temp file size is
    $sTest .= sprintf("\n");

    $log->logInfo($sTest);
    $devicetablename = mysql_real_escape_string(sprintf("%s__device%03d_class%s",$aquisuitetable,$_REQUEST['MODBUSDEVICE'],$_REQUEST['MODBUSDEVICECLASS']));
	
    // printf("\nSUCCESS\n");			// this line is what the AcquiSuite/AcquiLite searches for in the response to
    $log->logInfo(sprintf("%s:LOGFILEUPLOAD\n", $LOOPNAME)); 
    return  $devicetablename;

}/*end function*/				   


/*
 * Function check_config_file_manifest     
 */
function check_config_file_manifest($aquisuite_table)   
{
    global $log, $LOOPNAME;
    
    $log->logInfo(sprintf("%s:CONFIGFILEMANIFEST\n", $LOOPNAME));
        
    $sql = "SELECT * FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuite_table'";
    $result = mysql_query($sql);

    $configChangeTime = "0000-00-00 00:00:00"; //default values used to receive a new config file from ASModule
    $configChecksum = "X";    //default

    $asTable = mysql_fetch_array($result);    
    if ($asTable)
    {    
         $configChangeTime= $asTable['configurationchangetime'];
         $configChecksum=$asTable['configurationchecksum'];
    }
    $log->LogInfo(sprintf("%s configtime %s configchecksum %s \n", $LOOPNAME, $configChangeTime, $configChecksum )); 


    $config_file_name = sprintf("CONFIGFILE,loggerconfig.ini,%s,%s\n", $configChecksum, $configChangeTime);

    //set time to zeros to get acquisuite to send config file upload to server
    //$config_file_name = sprintf("CONFIGFILE,loggerconfig.ini,X,0000-00-00 00:00:00\n"); 

    //set time to current timestamp to get acquisuite to request donwload of config file from server
    $config_file_name = sprintf("CONFIGFILE,loggerconfig.ini,X,%s\n", date('Y-m-d H:i:s', gmmktime()));

    printf($config_file_name); //send to asModule
    $log->LogInfo(sprintf("%s ConfigFileRequest %s\n", $LOOPNAME, $config_file_name));

    // Search the Aquisuite table to find a list of all the current devices
    $sql = "SELECT * FROM $aquisuite_table";
    $result = mysql_query($sql);
    if (!$result)
    {
        MySqlFailure("could not find data for $aquisuite_table");
    }
    $device_count = mysql_num_rows($result);

    if ($device_count > 0)
    {
        while ($row = mysql_fetch_array($result))
        {
            // print out a line for each device so a current configuration file will be  uploaded into the database.
            printf("CONFIGFILE,modbus/mb-%03d.ini,X,0000-00-00 00:00:00\n", $row['modbusdevicenumber']); 

        }
    }

    printf("\n");   // blank line to make things look nice.

    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()
}//End check_config_file_manifest


/*----------------------------------------------------------------------------------------------------*/
/*	the following function is used in the CONFIGFILEUPLOAD MODE to build the necessary table columns based on device class,
data point names, numbers, and units. It is also used to update a Device_Config table that keeps a record of each table field, point name,
point number, and point units.
*/
function updatedevicetable($aquisuitetable,$Point_Names,$Point_Name_Default,$Point_Units,$szTargetFilename)
{
      global $log, $LOOPNAME;	

    $MODBUSDEVICE = $_REQUEST['MODBUSDEVICE'];
	
	$MODBUSDEVICECLASS = $_REQUEST['MODBUSDEVICECLASS'];
	
	$devicetablename = mysql_real_escape_string(sprintf("%s__device%03d_class%s",$aquisuitetable,$MODBUSDEVICE,$MODBUSDEVICECLASS));
	
	// check to see if table exists
	
	$check_table_exists = "SELECT * FROM `$devicetablename`";
	$table_check = mysql_query($check_table_exists);
	
	// check Device_Config table for any record of device information in case the table was added prior to the automatic upload process.
	
	$check_device_config = "SELECT Field FROM `Device_Config` WHERE aquisuitetablename='$aquisuitetable' AND devicetablename='$devicetablename'";
	$device_rows = mysql_num_rows(mysql_query($check_device_config));
	
	if(!$table_check || $device_rows==0)
	{	
			
		// here we check the database for a device class table structure that
		// will have the nessesary information to create the device table.
			
		$deviceclasstable = mysql_real_escape_string(sprintf("deviceclass%02d",$MODBUSDEVICECLASS));
		
		$sql="SELECT * FROM $deviceclasstable";		// get the static device class table structure contents.
		$result=mysql_query($sql);
		
		$query = "CREATE TABLE `$devicetablename` (";		// start to build the table query.
		
		if(!$result)		// if a device class does not exist in the database then we use the
							// configuration file data point names or the default point names
							// created for each data input in CONFIGFILEUPLOAD MODE.
		{	

			$inputcount=count($Point_Name_Default);		// get the total number of data field names from the configuration file.
			$rows=0; 	
			
			// every device has a standard first four columns that are saved for time, error, lowrange (alarm) and highrange(alarm).
			// add those columns to the query.
			
			$query = $query."`time` DATETIME NOT NULL,
			`error` int(11) NULL,
			`lowrange` int(11) UNSIGNED NULL,
			`highrange` int(11) UNSIGNED NULL,";
			
			// start the loop through each data field name
			
			while($rows<$inputcount)
			{	
				
				// here we check to make sure the data point names are not blank
				// if they are we create a standard field name based on the point 
				// number key that the point name array was assigned to. 
				
				if($Point_Names[$rows]!="-" && !empty($Point_Names[$rows]))	
				{	
					$point_name=str_replace('-','',$Point_Names[$rows]);
					$point_name=str_replace('(','',$point_name);
					$point_name=str_replace(')','',$point_name);
					$point_name=str_replace(' ','_',$point_name);
					$point_name=str_replace(']','',$point_name);
					$point_name=str_replace(',','',$point_name);
					$point_name= mysql_real_escape_string(str_replace('[','',$point_name));
					
					$query = $query." `".$point_name."`"." FLOAT NULL,";		// continue to build the table query.
					
					// this portion is for updating the Device_Config table
					
					$Field[] = $point_name;	
					$name[] = str_replace("_"," ",$point_name);		// name the 
					$pointnumber[] = $rows;
				}
				else
				{
					$query = $query." `".$Point_Name_Default[$rows]."`"." FLOAT NULL,";
					
					
					$Field[] = $Point_Name_Default[$rows];		
					$name[] = "";		// we don't want to name the points that are emptied
					$pointnumber[] = $rows;
				}

				$rows++;
			}
		}
		
		// if the device class table structure is in the database
		// then we proceed to retrieving the contents and continuing with creating the 
		// table query.
		
		else
		{	
			$rows=-4;		// set the value to -4 to account for the 4 default fields.
			
			while($deviceclass = mysql_fetch_array($result))
			{	
				
				// identifying each column in the device class table
				
				$deviceclass['Field'];
				$deviceclass['Type'];
				$deviceclass['Null'];
				$deviceclass['Key'];
				$deviceclass['Default'];
				$deviceclass['Extra'];
				
				// The point names in the configuration file are what the default field name should be named.
				// if the point name is empty, then use the default device class table field name.
				
				if($Point_Names[$rows]!="-" && !empty($Point_Names[$rows]))
				{	
					$query = $query." `".$Point_Names[$rows]."`"." ".strtoupper($deviceclass['Type']);
					
					$Field[] = $Point_Names[$rows];
					$name[] = str_replace("_"," ",$Point_Names[$rows]);
					$pointnumber[] = $rows;
				}
				else
				{
					$query = $query." `".str_replace(" ","_",trim($deviceclass['Field']))."`"." ".strtoupper($deviceclass['Type']);
					
					if($deviceclass['Field']!='time' && $deviceclass['Field']!='error' && $deviceclass['Field']!='lowrange' && $deviceclass['Field']!='highrange')
					{
						$Field[] = str_replace(" ","_",trim($deviceclass['Field']));
						$name[] = str_replace("_"," ",$Point_Names[$rows]);
						$pointnumber[] = $rows;
					}
				}
				if($deviceclass['NULL']=='YES')
				{
					$query = $query." NULL,";
				}
				else
				{
					$query = $query." NOT NULL,";
				}
				
				$rows++;
			}
		}
			// check the Aquisuite_List to see if the aquisuite table has a utility and add the 
			// utility columns associated with the deviceclass from the utilitycolumns table.
			
		$utility = utility_check($aquisuitetable);
		if(!empty($utility)) {
			$sql = "SELECT * FROM `utilitycolumns` WHERE deviceclass='$MODBUSDEVICECLASS' AND Utility='$utility'";
			$result = mysql_query($sql);
				if(!$result)
				{
					MySqlFailure("Unable to locate $deviceclasstable $aquisuitetable");
				}
				else
				{	
					$count_values = 0;
					while($row = mysql_fetch_array($result))
					{	
						$row['Field'];
						$row['Type'];
						$row['Null'];
						$row['Key'];
						$row['Default'];
						$row['Extra'];
						$row['units'];
						
						$Field[] = $row['Field'];	
						$name[] = str_replace("_"," ",$row['Field']);
						$Point_Units[] = $row['units'];
						
						$query = $query." `".$row['Field']."`"." ".strtoupper($row['Type']);
						
						if($row['NULL']=='YES')
						{
							$query = $query." NULL,";
						}
						else
						{
							$query = $query." NOT NULL,";
						}
						$count_values++;
					}
					
					// specify the point names,field names, and point units for the added utility columns.
					
					if($count_values>0)
					{
						$Field[] = "(Peak_kWh+Off_Peak_kWh)";		// used for retrieval for graph
						$name[] = "Energy Consumption";				
						$Point_Units[] = "kWh";		
						$pointnumber[] = "";		// the point number doesn't actually exist in the configuration file so we leave it blank
						$Field[] = "(Peak_kW+Off_Peak_kW)";
						$name[] = "kW Demand";
						$Point_Units[] = "kW";
						$pointnumber[] = "";
					}
				}
		}
		$query = $query." PRIMARY KEY (`time`))";		// end the query, all device table primary keys will be the time field.
		$result = mysql_query($query);
		if(!$result)
		{
			MySqlFailure("uable to create table $devicetablename");
		}
		
		//	update the Device_Config table with each array of values if neccessary. 
		
		
				{

		
		$i=0;
		while($i< count($Field))
		{
			$insert = mysql_query("INSERT INTO `Device_Config` VALUES (DEFAULT,'$aquisuitetable','$devicetablename','$MODBUSDEVICECLASS','$Field[$i]','$pointnumber[$i]','$name[$i]','$Point_Units[$i]',0)");
     			if(!$insert)
			{
				MySqlFailure("unable to insert `Device_Config` Values: $insert");
			}
			
			$i++;
		}
	}
	
	// update each device configuration file each time an upload session occurs. The last entry is left blank for the point value 
	// that can be updated using the XML upload process that was not implemented into this program. See the XML obvius upload kit
	// for more information on how to implement that process.
	
	$fileinsert = addslashes(file_get_contents($szTargetFilename));
	$sql = "UPDATE `$aquisuitetable` SET configuration='$fileinsert', configurationchangetime='".date('Y-m-d H:i:s',time())."', configurationchecksum='".md5($szTargetFilename)."' WHERE devicetablename='$devicetablename'";

        $result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("could not insert $filename into $devicetable"); 
	}
}//end function



/*
 * Function config_file_upload     
 */
function config_file_upload($server_info, $request_info, $file_info, $aquisuite_table)        
{
    global $log, $LOOPNAME;

    $log->logInfo(sprintf("CONFIGFILEUPLOAD SERIALNUMBER: %s \n", $LOOPNAME));
    $fileTime = $request_info['FILETIME']; 


    $szChecksum = "[error]";                           // provide some default value for this in case the following fails.
    if (file_exists($file_info['CONFIGFILE']['tmp_name']))              // make sure the file exists before we start.
    {
        $szChecksum = md5_file($file_info['CONFIGFILE']['tmp_name']);   // built in php function that calculates the md5 checksum of a file.
    }

    $acquisuiteIPAddress = $server_info['REMOTE_ADDR'];  //save ip from acquisuite
    if ($request_info['MODBUSDEVICECLASS'] == "0")
    {
        $log->LogInfo(sprintf("%s FileTime %s chksum %s IP Addr %s", $LOOPNAME, $fileTime, $request_info['MD5CHECKSUM'],$acquisuiteIPAddress));
    }
    
    
    // now we should check the log file checksum to verify it is correct.
    // if not, something got corrupted.  refuse the file and the DAS will upload it again later.

    if ($szChecksum != $request_info['MD5CHECKSUM'])
    {
         $log->logError(sprintf("The checksum of received file does not match the checksum form variable sent by the DAS : %s\n", $LOOPNAME));
        ReportFailure("The checksum of received file does not match the checksum form variable sent by the DAS.\n");
        exit;
    }

    $fd = fopen($file_info['CONFIGFILE']['tmp_name'], "r");
    if (!$fd)
    {
       $log->logError(sprintf("open failed to open configfile " . ($file_info['CONFIGFILE']['tmp_name']) . ": %s\n", $LOOPNAME));
        ReportFailure("open failed to open configfile " . ($file_info['CONFIGFILE']['tmp_name']));
        exit;
    }

    $szTargetDirectory = sprintf("%s_%s", $LOOPNAME, $request_info['SERIALNUMBER']);   // FIX THIS (clean up serial number, or validate it)

    $szTargetFilename = sprintf("%s/mb-%03d.ini", $szTargetDirectory, $request_info['MODBUSDEVICE']);

    if (!file_exists($szTargetDirectory))            // if the directory does not exist, create it.
    {
        $nResult = mkdir($szTargetDirectory, 0700);    // create directory (unix permissions 0700 are for directory owner access only)
        if (!$nResult)                                // trap directory create errors.
        {
                  $log->logError(sprintf("%s: error creating directory %s", $LOOPNAME, $szTargetDirectory));
            ReportFailure("Error creating directory " . $szTargetDirectory);
            exit;
        }
    }

    $fOut = fopen($szTargetFilename, w);           // create/open target file for writing
    if (!$fOut)                                   // trap file create errors.
    {
        ReportFailure("Error creating file " . $szTargetFilename);
        exit;
    }

    printf("Saving data to file %s\n", $szTargetFilename);  // be nice and print out the target file location.
     $log->logInfo(sprintf("ConfigfileUpload: Saving data to file " . $szTargetFilename . ": %s\n", $LOOPNAME));


    while (!feof($fd))                              // loop through the source file until we reach the end of the file.
    {
        $szBuffer = fgets($fd, 512);               // read lines from the log file.  make sure lines don't exceed 512 bytes
        $nResult = fputs($fOut, $szBuffer);         // write data to the log file.
        if (!feof($fd))
        {
            if (!$nResult)                         // trap file write errors.
            {
                ReportFailure("Error writing to output file " . $szTargetFilename);
                exit;
            }
        }

        $configpoint = explode('=', $szBuffer);  // separate the configuration points and values into an array.

        $name_check = substr($configpoint[0], 7, 4); // identifying configuration point names.

        $unit_check = substr($configpoint[0], 7, 5);  // identifying point units.

        $pointnum = round(substr(trim($configpoint[0]), 5, 2));  // the configuration file doesn't always list the points in the correct order
        // we identify the point number here so that the tables can be created in the
        // correct order for data log file inserts.

        if ($configpoint[0] == "TIMEZONE")
        {
            $timezone = str_replace("\"", "", $configpoint[1]);  // extracting the current time zone.
        }

        if ($name_check == "NAME")
        {
            $Point_Name_Default[$pointnum] = "input" . $pointnum;

            if (trim($configpoint[1]) == $point_names_repeat && trim($configpoint[1]) != "-")  // for mySQL purposes make sure the name isn't repeated
            // and that the point isn't an empty value.
            {
                $Point_Names[$pointnum] = str_replace(' ', '_', trim($configpoint[1]) . "_" . $pointnum);  // replace white spaces and add the point number value to a repeated point name. 
                // These names will be column names in a mySQL table
            } else
            {
                $Point_Names[$pointnum] = str_replace(' ', '_', trim($configpoint[1]));
            }

            $point_names_repeat = trim($configpoint[1]);  // control for repeated point names
        }

        if ($unit_check == "UNITS")
        {
            $Point_Units[$pointnum] = trim($configpoint[1]);  // get the point unit of measurment
        }
    }

    // MODBUSDEVICECLASS 0 is the aquisuite configuration file itself. Here we want to update the Aquisuite_List
    // table to update the current time zone if it has changed since the last update.
    //  we also want to update the current configuration file and timestamp where the SERIAL NUMBER in the database
    //  matches the current uploading aquisuite.

    if ($request_info['MODBUSDEVICECLASS'] == "0")
    {
        $sql = "SELECT * FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuite_table' AND timezoneaquisuite='$timezone'";
        $result = mysql_query($sql);
        $row_count = mysql_num_rows($result);
        if (!$row_count == 1)
        {
            $sql = "UPDATE `Aquisuite_List` SET timezoneaquisuite='$timezone' WHERE aquisuitetablename='$aquisuite_table'";
            $result = mysql_query($sql);
            if (!$result)
            {
                $log->LogInfo(sprintf("%s unable to update Aquisuite_List with timezone %s\n", $LOOPNAME, $timezone));
                MySqlFailure("unable to update Aquisuite_List with timezone $timezone");
            }
        }
        
 
        // save config file time received from ASmodule
        $sql = "UPDATE `Aquisuite_List` SET configuration='$szTargetFilename', configurationchangetime='" . $fileTime . "', configurationchecksum='" . $szChecksum . "', asIPAddress='" . $acquisuiteIPAddress . "' WHERE SerialNumber='$request_info[SERIALNUMBER]'";
      
        $log->LogInfo(sprintf("%s: ConfigUpload %s\n", $LOOPNAME, $sql));
               
        $result = mysql_query($sql);
        if (!$result)
        {
           $log->LogInfo(sprintf("%s unable to select data from Aquisuite_List\n", $LOOPNAME));
            MySqlFailure("unable to select data from Aquisuite_List");
        }
    } 
    else
    {
        $log->LogInfo(sprintf("%s call updatedevicetable\n", $LOOPNAME));
        updatedevicetable($aquisuite_table, $Point_Names, $Point_Name_Default, $Point_Units, $szTargetFilename);  // creates device table is not already created and updates all device information.
    }
    fclose($fOut);  // close the target file
    fclose($fd);   // close the source uploaded file
    // PHP automatically removes temp files when the script exits.


    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()

    printf("\nSUCCESS\n");   // this line is what the AcquiSuite/AcquiLite searches for in the response to
    // tell it that the data was received and the original log file may be deleted.

    printf("</pre>\n");  // end of the script
}

/* -----------------------------------------------------------------------------------------------*/
/*  This function sends a specific configuration file to the acquisuite. 
    The script should finish the header, and then send the config file as the body to the http response.
    No other content should be included in the response body.
        
    The variables $SERIALNUMBER, $MODBUSIP, $MODBUSPORT and $MODBUSDEVICE are used to identify the config file.       
    THe variable $MODBUSDEVICECLASS is used to verify the device the configuration file is for is the same as
    the hardware detected on the AcquiSuite.          

    Special case: when MODBUSDEVICE and MODBUSDEVICECLASS are both 0, return the loggerconfig.ini file.
      
    The AcquiSuite will confirm the md5 checksum from the manifest with the checksum of the downloaded file for verification.
*/

/*
 * Function sendConfigFiletoAS     
 */
function sendConfigFiletoAS ($server_info, $request_info, $REMOTE_ADDR, $SERIALNUMBER, $MODBUSIP, $MODBUSPORT, $MODBUSDEVICE, $MODBUSDEVICECLASS)        
{
    global $log, $LOOPNAME;
    
    if ($request_info['MODBUSDEVICECLASS'] != "0")
        return;
    
    $log->logInfo( sprintf("%s: serial num %s remote addr %s modip %s modport %s moddev %s modclass %s \n",
            $LOOPNAME, $SERIALNUMBER,$REMOTE_ADDR ,$MODBUSIP, $MODBUSPORT, $MODBUSDEVICE, $MODBUSDEVICECLASS)); 

    $szChecksum = "[error]";                           // provide some default value for this in case the following fails.
    $szTargetDirectory = sprintf("%s_%s", $LOOPNAME, $SERIALNUMBER); 
    $szTargetFilename = sprintf("%s/mb-%03d.upload", $szTargetDirectory, $MODBUSDEVICE);
    
    if (file_exists($szTargetFilename))              // make sure the file exists before we start.
    {
        $szChecksum = md5_file($szTargetFilename);   // built in php function that calculates the md5 checksum of a file.
        $log->LogInfo(sprintf("%s: file %s at %s checksum %s", $LOOPNAME, $szTargetFilename, $szChecksum));
  
    }
    else
    {    
      $log->LogInfo(sprintf("%s: File %s does not exist ", $LOOPNAME, $szTargetFilename));
    }  
    $fIn = fopen($szTargetFilename, 'r');           // create/open target file for writing
    if (!$fIn)                                       // trap file create errors.
    {    
      $log->logInfo(sprintf("%s:logfile error reading file %s \n", $LOOPNAME, $szTargetFilename) ); 
      ReportFailure("Error reading file " . $szTargetFilename);
    }  
    
    Header("WWW-Authenticate: Basic realm=\"UploadRealm\"");    // realm name is actually ignored by the AcquiSuite.
    Header("HTTP/1.0 200 Success");                  
    while (!feof($fd))                              // loop through the config file until we reach the end of the file.
        {
            $szBuffer = fgets($fd, 512);               // read lines from the config file.  make sure lines don't exceed 512 bytes
             printf( $szBuffer);         // write data to the log file.
            if (!feof($fd))
            {
                if (!$nResult)                         // trap file write errors.
                {
                    $log->logInfo(sprintf("%s:Error writing to output file %s \n", $LOOPNAME, $szTargetFilename) ); 
                    ReportFailure("Error writing to output file " . $szTargetFilename);
                    exit;
                }
            }
    }

    printf("SUCCESS\n");    

    ob_end_flush(); // send cached stuff, and stop caching. we've already kicked out the last header line.

    printf("Sending file %s\n", $szTargetFilename);  
     $log->logInfo(sprintf("ConfigfileUpload: Saving data to file " . $szTargetFilename . ": %s\n", $LOOPNAME));

    
    //printf("\nSUCCESS\n");   // Send success for now till code is finished
}    


/*
 * Function debug_log   
 */
function debug_log()
{
     //---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---		
    
     # Should log to the same directory as this file
    require '../erms/includes/KLogger.php';
    $log = new KLogger ( "clog.txt" , KLogger::DEBUG );   // klogger debug everything
    
   return $log;
   
   //---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---
   
}/** end debug_log ****/



/* ================================================================================================================== */
/*
    BEGIN MAIN SCRIPT HERE !
*/

    ob_start();     

include "../conn/mysql_pconnect-all.php"; // mySQL database connector.

   Authenticate();
   
    // Defining Aquisuite Table Name and verifying that it exists.
    $LOOPNAME = $_REQUEST['LOOPNAME'];

    $LOOPNAME = str_replace(" ", "_", $LOOPNAME);  // Replace any spaces in the LOOPNAME. 
    // The LOOPNAME will become a part ofa mySQL table in the database.

    if(empty($LOOPNAME))
    {
        $LOOPNAME = $_REQUEST['SERIALNUMBER'];  // If a LOOPNAME has not been set use the SERIAL NUMBER as a default
    }
 
    // check for config download mode request
    $downloadMode = $_GET['MODE'];
    if ($downloadMode == 'CONFIGFILEDOWNLOAD')
    {
            $LOOPNAME = "Cape_Washington"; 
           $LOOPNAME = "Cottage"; 

    }
    
    //debug cape washington
     if ( $_REQUEST['SERIALNUMBER'] == "001EC6001FFA")
     {
        $LOOPNAME = $_REQUEST['SERIALNUMBER']; 
        $LOOPNAME = "Cape_Washington"; 
     }   
    
    /**** Setup KLogger log file  ******/
    $log = debug_log($LOOPNAME);
   
    date_default_timezone_set('UTC'); /** set the timezone to UTC for all date and time functions **/
    
    $aquisuitetable = sprintf("%s_%s", $LOOPNAME, $_REQUEST['SERIALNUMBER']);

   $log->logInfo( sprintf("%s Mode %s serial %s\n", $aquisuitetable, $_REQUEST['MODE'], $_REQUEST['SERIALNUMBER']));
 

    // STATUS MODE		
    if ($_REQUEST['MODE'] == "STATUS")
    {
        status_upload($_SERVER, $_REQUEST);  
        
    }

    // LOGFILEUPLOAD MODE

    if ($_REQUEST['MODE'] == "LOGFILEUPLOAD")
    {
        $devicetablename = logfile_upload($LOOPNAME, $aquisuitetable);  //process log data
        utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log);  //calculate utility costs
       	printf("</pre>\n");  // end of the script
	ob_end_flush();   // send any cached stuff and stop caching. 	
    }

// CONFIGFILEMANIFEST MODE
    if ($_REQUEST['MODE'] == "CONFIGFILEMANIFEST")
    {	 
        check_config_file_manifest($aquisuitetable); 
  
    }

// CONFIGFILEUPLOAD MODE
    if ($_REQUEST['MODE'] == "CONFIGFILEUPLOAD")
    {
        config_file_upload($_SERVER, $_REQUEST, $_FILES, $aquisuitetable); 
    }
   
     
 if ($_REQUEST['MODE'] == "CONFIGFILEDOWNLOAD")
  {   
      $log->logInfo(sprintf("%s CONFIG File Download\n",$LOOPNAME));
      sendConfigFiletoAS ($_SERVER,$_REQUEST,$_SERVER['REMOTE_ADDR'], $_REQUEST['SERIALNUMBER'], $_REQUEST['MODBUSIP'] , $_SERVER['REMOTE_PORT'], $_REQUEST['MODBUSDEVICE'],  $_REQUEST['MODBUSDEVICECLASS']);
      } // end CONFIGFILEDOWNLOAD processing
    
    if($_REQUEST['MODE']!="STATUS" && 
       $_REQUEST['MODE']!="CONFIGFILEUPLOAD" && 
       $_REQUEST['MODE']!="CONFIGFILEDOWNLOAD" && 
       $_REQUEST['MODE'] !="LOGFILEUPLOAD" &&
       $_REQUEST['MODE'] !="CONFIGFILEMANIFEST")	// trap all other possible MODES not handled in this script that may have been added in furture versions. system here.
    {   
        $log->logInfo( sprintf("%s Mode Type: %s not supported\n", $LOOPNAME, $_REQUEST['MODE']));
	ReportFailure("Mode type " . $_REQUEST['MODE'] . " not supported by this sample script\n");  
    }
    
    
   
?>
