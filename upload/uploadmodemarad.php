<?php
/**
    Copyright © 2001-2006, Obvius Holdings, LLC. All rights reserved...

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions
    are met:

   1. Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.

   3. Neither the name of Obvius Holdings nor the names of its contributors
      may be used to endorse or promote products derived from this software
      without specific prior written permission.

    THIS SOFTWARE PROGRAM IS PROVIDED BY OBVIUS HOLDINGS AND CONTRIBUTORS
    FREE OF CHARGE AND ACCORDINGLY IS LICENSED "AS IS" WITHOUT WARRANTY OF
    ANY KIND, AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
    TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
    PURPOSE, ARE DISCLAIMED. THE ENTIRE RISK AS TO THE QUALITY AND THE
    PERFORMANCE OF THE PROGRAM IS WITH YOU.

    IN NO EVENT, UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING,
    SHALL OBVIUS HOLDINGS OR CONTRIBUTORS WHO MAY MODIFY AND/OR REDISTRIBUTE
    THE PROGRAM BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, GENERAL,
    SPECIAL, EXEMPLARY, PUNITIVE, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
    NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
    LOSS OF DATA, DATA BEING RENDERED INACCURATE OR FAILURE OF THE PROGRAM
    TO RUN WITH ANY OTHER PROGRAMS, OR LOST PROFITS; OR BUSINESS INTERRUPTION)
    HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
    LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
    OUT OF THE USE OF THIS SOFTWARE PROGRAM, EVEN IF SUCH HOLDER OR OTHER PARTY
    HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
**/
/** ---------------------------------------------------------------------------**/
/**
	$Id: uploadmode.php,v 1 2012/02/16 Frisk Exp $

	In addition to the original script (upload_sample_historical-only-do-not-use.php), this script is
	a combination of the php sample scripts provided by the Obvious Upload kit as well as additional
	modifications that were made to make it suitable for Alaris Companies production environment.

	Any person making modifications to this script should first have a look at the Obvius Upload kit and read the
	necessary documentation to get a better understanding of how the Aquisuite system communicates.

	The following is an overview of what this script currently does during an aquisuite upload session.
	To see more about the aquisuite upload process see the Upload Kit documentation.

	To get a better understanding of the database table structure that encompases this program lood at the "ERMS mySQL Database explanation".

	1. Authenticate.

		The script functions will be briefly explained below, but when the Aquisuite is in an upload
		session it will take the HTTP POST command 'SERIALNUMBER' variable and verify that it exists
		in the Aquisuite_List table in the database or otherwise terminate the session.

	2. Check MODE.

		This script handles 4 different Aquisuite MODE's. The modes are processed in the following order
		during an upload session and for each MODE the Aquisuite is re-connecting to the script.

		a) STATUS

			For this script the STATUS mode does nothing more than update the upload attempt in the Aquisuite_List
			table in the database. This is used to make sure the timezone has been acquired prior to uploading the
			data.

		b) LOGFILEUPLOAD

			The LOGFILEUPLOAD is the mode where the logfile containing the data for the device is uploaded. It does
			the following:

				- Checks the database to see if a table has already been created for the aquisuite. If a table does not exist
				then a table is created and the Aqusuite_List is populated with the aquisuite LOOPNAME and aquisuite table name.
				The aquisuite configuration file will be updated during the CONFIGFILEUPLOAD MODE.

				- Search the aquisuite table for the current device that is being uploaded and match it with device number, device table name
				and device name. If the contents do not already exist then insert the device . The device configuration
				file will be updated during the CONFIGFILEUPLOAD MODE.

				- Verify that the current upload attempt is greater than 1 so that the current timezone can be acquired from
				the configuration file during CONFIGFILEUPLOAD MODE.

				- Upload the log file data into the database and check for any errors that may have occured in the data. Any errors will be
				logged in the database and a file will be created and saved on the server with the error details.

				UTILITY COLUMN UPDATE

					The UTILITY COLUMN UPDATE is for Device Class 2 tables. This script should be modified to include additional classes and utilities
					depending on what devices need to have peak and off peak calculations done. The columns that were created in the utiltiy tables are
					updated with the utility specific data based on the peak and off peak time and any other specific requirements that may be necessary
					for calculating the energy cost assiciated with certain devices.

		c) CONFIGFILEMANIFEST

			The CONFIGFILEMANIFEST is the mode that determines what device configuration files are to be uploaded. In this script all of the device
			files are uploaded each time. The aquisuite table is checked for a list of devices and prints out each one to tell the aquisuite that
			each device configuration file will be uploaded.

		d) CONFIGFILEUPLOAD

			 The CONFIGFILEUPLOAD MODE is where the device manifested configuration files get uploaded. Since we manifested the aquisuite configuration file
			 and each device configuration file, a new configuration file will be uploaded during each upload session. The aquisuite configuration file contains
			 the current timezone, which is the only item that this script has been written to handle. The device configuration file contains data point numbers,
			 names, units of measurement and other useful information about the device. The configuration information will be used for the following purposes:

				 - Creating a new device table if one doesn't already exist.

				 - Update Device_Config table that is used for tracking all device names and points for graphing usage.

				 - Getting the aquisuite current timezone and inserting it into the Aquisuite_List for the corresponding aquisuite.

				 - Updating the Aquisuite_List table, and device table with current configuration file, changetime, and md5 checksum of config file.

		e) ALL OTHER MODES NOT YET HANDLED

			This script does not handle any other aquisuite MODES not specified above. In future firmware updates additional modes may be added.

/** -----------------------------------------------------------------------------**/
/**

    $Id: upload_sample_historical-only-do-not-use.php,v 1.28 2006/05/18 17:54:18 herzogs Exp $



    This script is provided as an example only, and should not be used in a production
    environment.

    This PHP script shows how the interface between the AcquiSuite and Database Server
    works. This file is intended as a sample, and requires modification to fit custom
    envrionments.

        Note: When this document mentions "server" it referrs to the webserver with an SQL database
        that will run this script.  The "client" or "AcquiSuite" referrs to the AcquiSuite that is
        attempting to upload log data to the server.

        This script uses PHP to authenticate the http client with a basic password scheme.
        In order to do this, PHP must be compiled into the apache webserver.  Otherwise,
        the password is not passed to the PHP script.  Please read the related
        documentation on the PHP website for more information on this topic.  Search for
        password authentication, or read www.php.net/manual/en/features.http-auth.php

        The upload process has been designed to work with as many firewalls and web proxy servers
        as possible. As such, the AcquiSuite uses basic HTTP POST commands to exchange data with
        the database server. To the firewall, or Proxy, this appears as a regular webbrowser
        accessing a website.

        PHP note: if you wish to use this script, you must enable the "register globals" option in the php
        configuration file. Otherwise, the form variables won't work as written.  The PHP website has
        further documentation on how this option works, and what alternatives are available.

        Overview:

        The AcquiSuite calls the server to upload data.  A session consists of the following:

        - The database server must listen for connections from the AcquiSuites, the specifc url may be specified on the AcquiSuite config page.
        - The AcquiSuite uses an HTTP Post command to the Database Webserver.
        - The HTTP session includes the AcquiSuite Serial Number and upload password using basic authentication.
          The serial number is the AcquiSuite MAC address.
        - The database server must check the serial number and password.
        - If the serial number and password do not match, the database server must return an "HTTP/1.0 401 Unauthorized" message
        - After sending the serial/password, the Acquisuite will send multiple form variables, detailed below.
        - the database server must check the validity of these variables before accepting the data.
        - the AcquiSuite includes the data as a FORM/POST File Attachment, in gzip format. (winzip will decompress, as will the PHP lib)
        - The database server will decompress the attached data file, and read one line at a time from it.
                - Each line of the file contains one reading for all points on the corresponding modbus device.
                - The format is: date/time, error, lowrange, highrange, point0, point1,...
                - All date/times are stored in UTC time.
                - Errors: 0=no error, 1-0x80 = linux clib errno values, 0x81-0x8B modbus tcp error codes.
                - lowrange, highrange are bitmaps with a bit set corresponding to the data points, where the point was out of range.
                  lowrange = 0x01 when point 0 is below the set low range value on the AcquiSuite, etc.
                - point0, point1, etc.  Refer to the data read from the modbus device. Points are in order as shown in the AcquiSuite configuration pages.
                - The date/time stamp should be a unique identifier, and should be used to verify and ignore duplicate data points.
                - the log filename on the AcquiSuite should not be used when reading data that has been uploaded via HTTP/POST
                - the log filename on the AcquiSuite is mb-001.log for raw text files, and mb-001.01AB87D4.log.gz when compressed.
                  The hex value in the filename is a unix timestamp when the file was compressed to allow multiple compressed log files on one AcquiSuite..
    - when the end of the file is reached, the file is closed (and deleted by php when the script ends)
    - Any alarm processing should be completed by the database server.
    - a response code is sent to the AcquiSuite to tell it if the upload was successful.
        - A response of "\nFAILURE: (reason)\n" in the html body will cause the AcquiSuite to retain the log file and try again later.
        - A response of "\nSUCCESS\n" will cause the AcquiSuite to remove the log file when the http session closes.
                - A success response should only be sent when data is imported into the database correctly.
                - If duplicate data points are detected, (and ignored) a failure message should not be sent, unless some other error ocurred.

        - This process will be repeated for each log file on the AcquiSuite system.

        IN THE FUTURE:

        - a similar upload procedure will be used to transfer the AcquiSuite configuration files
          both to and from the database server.  Further documentation on this will be made available
          when remote administration features are inlcuded in the firmware.
**/
/** -------------------------------------------------------------------------------------------------------------------**/

/***     includes      ***/
require_once  './process_config_files.php';
require_once './relay_control.php';
require_once './downloadconfig.php';

require_once './src/db_helpers.php';
require_once './src/functions.php';
require_once './class_objects/logger.php';
require_once './class_objects/records.php';
require_once './class_objects/utility.php';

/** define constants   **/
const ONE_MIN = 60;
const FIVE_MIN = 300;
const FIFTEEN_MIN = 900;
const ONE_HOUR = 3600;

const METER_DISABLED = 0;
const METER_ENABLED = 1;
const METER_PULSE_ENABLED = 2;
const METER_OUTPUT_ONLY_ENABLED = 3;

const MAX_DEMAND = 1000;


/**-----------------------------------------------------------------------------------------------------------------
 * reporting function be used to report and terminate.
 */
function ReportFailure($szReason)
{
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
  global $log;
        $value_repeat = "";

       	echo "mySQL FAILURE:"."</br>";
	$con = $_SESSION['con'];		// used for getting the mysql connector for error printing
	$sql_errno = mysql_errno($con);

	if($sql_errno>0)
	{
          $log->logInfo(sprintf("[mysql error %s]: %s", $sql_errno, mysql_error($con)));
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

	// if the serial number and password don't return a single row, ternminate the process.
	if(!$count==1)
	{
	     //log auth failure in file
            $myTargetDirectory = sprintf("%s", "AUTHERR");
            $myTargetFilename  = sprintf("%s/authserial_%s_%s.txt", $myTargetDirectory, $_REQUEST['SERIALNUMBER'],date ("M-j-y", time()));
            printf("Failed Auth file [%s]\n",$myTargetFilename);
            $fOut = fopen($myTargetFilename, 'w');           // create/open target file for writing
            $mystring = sprintf("Serial:%s Loop:%s Mode:%s IP %s\n",$_REQUEST['SERIALNUMBER'], $_REQUEST['LOOPNAME'], $_REQUEST['MODE'], $_SERVER['REMOTE_ADDR'],$_SERVER['SERVER_ADDR']);
            $nResult = fwrite($fOut, $mystring);    // write data to the log file.
              fclose($fOut);  // close the target file
	    ReportFailure('serialnumber and password are not authorized');
	}
}
/*----------------------------------------------------------------------------------------------------*/
/*	Becuase the Aquisuite system could be located on a vessel, the vessel could change timezones. The
	timezone is used for updating the utility columns in the device table. Although the logfile
	data will always be uploaded in UTC time, when determining the On & Off Peak values, the upload
	session timezone is used. This function is used to check the upload attempt in the Aquisuite_List.
	Since the timezone can only be acquired from the configuration file which is uploaded after the
	logfile, the logfile is only uploaded if the upload attempt is greater than 1.
	2014:Update- Let the time zone to a default of EST when entering the ships initial table structure.
	Then stop calling thisforevery log because it appears unnecessary.
*/
function uploadcheck($aquisuitetable)
{

	$sql = "SELECT uploadattempt FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
	$result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("unable to select uploadattempt from aquisuite_list");
	}
	$row = mysql_fetch_row($result);
	$uploadattempt = $row[0];
	if($uploadattempt<=1)
	{         ///turn this off temorarily for testing
		//exit;
	}
}

/*----------------------------------------------------------------------------------------------------*/
/*	This function is used to count the table fields for inserting the data from the log file into the device table. If there are more
	columns in the table then there are in the log file, then blank entries are created. The additional columns (For utility purposes) are
	handled in the UTILITY COLUMN UPDATE section.
*/

function counttablefields($log,$devicetablename, $LOOPNAME)
{
	$sql="SELECT * FROM `$devicetablename` LIMIT 1";
	$result=mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("unable to retreive $devicetablename data");
	}
	//$countdevicefields = mysqli_num_fields($result);
       // $log->logInfo(sprintf("%s: countfields nbr = [%s] \n", $LOOPNAME, $countdevicefields));

      	$countdevicefields = mysql_num_fields($result);

	return $countdevicefields;
}
/*----------------------------------------------------------------------------------------------------*/
/*	This function is used to check the Aquisuite_List table to see if an aquisuite table has already been created.
*/
function checkaquisuite($log, $aquisuitetable,$LOOPNAME)
{
	$SERIALNUMBER = $_REQUEST['SERIALNUMBER'];

	$check_table_exists = "SELECT * FROM `$aquisuitetable`";
	$table_check = mysql_query($check_table_exists);

	// If an aquisuite is uploading data for the first time
	// it will not have a table created for it yet. Here we
	// check to see if the aquisuite has a table created yet
	// and if it doesn't exist then we create it and Update
	// the row with the aquisuite table name and LOOPNAME.


	if(!$table_check)
	{
		// building the table query
        	$log->logInfo(sprintf("%s:checkaquisuite devicename [%s] create table [%s] \n", $LOOPNAME, $aquisuitetable, $check_table_exists));

		$Create_Table="CREATE TABLE `$aquisuitetable`
		(`SerialNumber` VARCHAR(64) BINARY NOT NULL,
		 `modbusdevicenumber` int(11) NOT NULL,
		 `devicename` VARCHAR(64) BINARY NOT NULL,
		 `devicetype` VARCHAR(64) BINARY NOT NULL,
		 `deviceclass` int(11) NOT NULL,
		 `configuration` MEDIUMBLOB NOT NULL,
		 `configurationchangetime` DATETIME NOT NULL,
		 `configurationchecksum` VARCHAR(48) NOT NULL,
		 `devicetablename` VARCHAR(60) NOT NULL,
		 `uploaded` int(1) NOT NULL,
		 `function` VARCHAR(64) NOT NULL,
                  `meter_status` TINYINT(4) NOT NULL,
   		 PRIMARY KEY (`modbusdevicenumber`)
		 )";
		 $result = mysql_query($Create_Table);
		 if(!$result)
		 {
		 	$log->logInfo(sprintf("%s:checkaquisuite devicename [%s] create table [%s] FAILED \n", $LOOPNAME, $aquisuitetable,$Create_Table));
			MySqlFailure("Unable to create table $aquisuitetable");
		 }

		 //this  sets the loopname in the main Aquisuite_List (not the aquisuitetable)
		$sql = "UPDATE `Aquisuite_List` SET loopname='$LOOPNAME', aquisuitetablename='$aquisuitetable' WHERE SerialNumber='$SERIALNUMBER'";		// updating the table
		$result = mysql_query($sql);
		if(!$result)
		{
			$log->logInfo(sprintf("%s:checkaquisuite Unable to update Acquisite_List with loopname [%s]\n", $LOOPNAME, $sql));
			MySqlFailure("Unable to update field aquisuitetablename set = $aquisuitetable");
		}
	}
}
/*----------------------------------------------------------------------------------------------------*/
/*	This function checks to see if the current device being uploaded has a row created in the aquisuite table.
*/
function checkdevice($aquisuitetable, $log, $LOOPNAME)
{
	// We define aquisuite sent variables here for mySQL insertion.

	$MODBUSDEVICENAME = $_REQUEST['MODBUSDEVICENAME'];
	$MODBUSDEVICE = $_REQUEST['MODBUSDEVICE'];
	$SERIALNUMBER = $_REQUEST['SERIALNUMBER'];
	$MODBUSDEVICECLASS = $_REQUEST['MODBUSDEVICECLASS'];
	$MODBUSDEVICETYPE = mysql_real_escape_string($_REQUEST['MODBUSDEVICETYPE']);

	// Here we check for bad characters that will take up unnessesary space in the table

	$MODBUSDEVICENAME=str_replace('-','',$MODBUSDEVICENAME);
	$MODBUSDEVICENAME=str_replace('(','',$MODBUSDEVICENAME);
	$MODBUSDEVICENAME=str_replace(')','',$MODBUSDEVICENAME);
	$MODBUSDEVICENAME=str_replace(' ','_',$MODBUSDEVICENAME);
	$MODBUSDEVICENAME=str_replace(']','',$MODBUSDEVICENAME);
	$MODBUSDEVICENAME= mysql_real_escape_string(str_replace('[','',$MODBUSDEVICENAME));

        $devicetablename = mysql_real_escape_string(sprintf("%s__device%03d_class%s",$aquisuitetable,$MODBUSDEVICE,$MODBUSDEVICECLASS));		// create device table name
	$log->logInfo(sprintf("Devicecheck %s: devicename %s \n", $LOOPNAME, $devicetablename));


	// search for the device row in the aquisuite table

	$sql_device = "SELECT * FROM $aquisuitetable WHERE devicetablename='$devicetablename' AND modbusdevicenumber='$MODBUSDEVICE' AND devicename='$MODBUSDEVICENAME'";
	$log->logInfo(sprintf("Devicecheck %s: sqlselect [%s] \n", $LOOPNAME, $sql_device));
	$request_device = mysql_query($sql_device);
	if(!$request_device)
	{
               	$log->logInfo(sprintf("Devicecheck %s: unable to select %s \n", $LOOPNAME, $sql_device));
		MySqlFailure("Unable to select data from $aquisuitetable");
	}

	$device_row_count = mysql_num_rows($request_device);

	if(!$device_row_count==1)
	{
           	$log->logInfo(sprintf("Devicecheck %s: Device row count %d\n", $LOOPNAME, $device_row_count));

		$function = ''; // set initial function value blank
		// check to see if a class has been set for the utility
		$sql = "SELECT utilitymaindevice FROM `Aquisuite_List` WHERE SerialNumber='$SERIALNUMBER'";
		$result = mysql_query($sql);
		if(!$result)
		{
                      	$log->logInfo(sprintf("Devicecheck %s: Result error in checkdevice \n", $LOOPNAME));
			MySqlFailure("unable to select utility from aquisuite_list");
		}
		$row = mysql_fetch_row($result);
		$main_utility_device = $row[0];

		if($main_utility_device==$MODBUSDEVICE) {
			$function = 'main_utility';
		}
		// insert device information into the table if no row exists. The blank entries are for the configuration file in the CONFIGFILEUPLOAD MODE.

		$configChangeTime = "1901-01-01 00:00:00";
	        $sql_device_insert = "INSERT INTO `$aquisuitetable` VALUES('$SERIALNUMBER','$MODBUSDEVICE','$MODBUSDEVICENAME','$MODBUSDEVICETYPE','$MODBUSDEVICECLASS','','$configChangeTime','','$devicetablename','1','$function','1')";
		$result_device_insert = mysql_query($sql_device_insert);
		if(!$result_device_insert)
		{
                       	$log->logInfo(sprintf("Devicecheck %s: unable to insert %s %s \n", $LOOPNAME, $aquisuitetable, $sql_device_insert));
			MySqlFailure("Unable to Insert $aquisuitetable information");
		}

		exit;
	}
}
/*----------------------------------------------------------------------------------------------------*/
/*	this function checks the Utility table the database to see if the aquisuitetablename has a utility associated with it.
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

	return $utility;
}
/*----------------------------------------------------------------------------------------------------*/
/*	the timezone function searches the Aquisuite_List for the aquisuite timezone and then matches it
	in the timezone table with a PHP recognized timezone.
*/
function timezone($ship, $log, $LOOPNAME)
{
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";		// get the aquisuite timezone in the Aquisuite_List
	$result = mysql_query($sql);

        if(!$result)
	{
            	$log->logInfo(sprintf("%s:timezone mysql select failed\n", $LOOPNAME));
		MySqlFailure("could not find $tzaquisuite from $ship ");
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {
            $log->logInfo(sprintf("%s:timezone rows failed\n", $LOOPNAME));
        }

	$timezoneaquisuite = $row[0];

	$sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";		// get the PHP recognized timezone value
	$result = mysql_query($sql);

        if(!$result)
	{
             	$log->logInfo(sprintf("%s:timezone mysql select result failed\n", $LOOPNAME));
		MySqlFailure("could not locate timezone ");
	}
	$row = mysql_fetch_row($result);
        if (!$row)
        {
            $log->logInfo(sprintf("%s:timezone rows2 failed\n", $LOOPNAME));
        }
	$timezone = $row[0];

	return $timezone;
}


/*----------------------------------------------------------------------------------------------------*/
/*	this function returns the module's log period from the acquisuite table
*/
function  getLogInterval($aquisuitetable,$log,$LOOPNAME)
{
    $log_interval = 5; // set default logperiod in minutes
    $sql = "SELECT logperiod FROM `Aquisuite_List` WHERE `aquisuitetablename`='$aquisuitetable'";	// get log period from Aquisuite_List
    $result = mysql_query($sql);
    $log->logInfo(sprintf("%s:Log interval sql %s\n", $LOOPNAME, $sql));

    if(!$result)
    {
       $log->logInfo(sprintf("%s:Log interval Select failed %s\n", $LOOPNAME, $sql));
       MySqlFailure("GETLOGINTERVAL failed");
    }
     $row = mysql_fetch_row($result);
     if (!$row)
     {
         $log->logInfo(sprintf("%s:Log interval Fetch failed\n", $LOOPNAME));
         MySqlFailure("GETLOGINTERVAL failed");
     }
    $log->logInfo(sprintf("%s:Log interval %d minutes\n", $LOOPNAME, $log_interval));
    $log_interval = $row[0] * ONE_MIN; //log interval in seconds
    $log->logInfo(sprintf("%s:Log interval %d seconds\n", $LOOPNAME, $log_interval));
    return $log_interval;
}

// This function check if a week is far enough to go back for updating kw and kwh values...otherwise go back 6 month
function lastUpdatedEntry($LOOPNAME, $log, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $lastUpdateTime)
{
    $log->logInfo(sprintf("%s:start Last Updated Entry\n", $LOOPNAME));

    $someTimeAgo = date('Y-m-d %%:00:00', strtotime('-1 week'));
    $sql_check = sprintf("SELECT time,%s,%s,%s,%s FROM %s WHERE time LIKE '%s'", $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo);
    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_check));

    $value_check = mysql_query($sql_check);
    if (!$value_check)
    {
        $someTimeAgo = date('Y-m-d H:i:s', strtotime("-6 month", strtotime($lastUpdateTime)));
        $log->logInfo(sprintf("%s:1LastUpdatedEntry %s 6 months ago %s", $LOOPNAME, $lastUpdateTime, $someTimeAgo));
    }
    else
    {
        $value = mysql_fetch_array($value_check);
        if (!$value)
        {
            $someTimeAgo = date('Y-m-d H:i:s', strtotime("-6 month", strtotime($lastUpdateTime)));
            $log->logInfo(sprintf("%s:2LastUpdatedEntry %s 6 months ago %s", $LOOPNAME, $lastUpdateTime, $someTimeAgo));
        }
        else
        {
            $log->logInfo(sprintf("%s:LastUpdatedEntry row %s", $LOOPNAME, $value["time"]));
            $someTimeAgo = $value["time"];
            if ((($value["$Peak_kWh"] > 0) || ($value["$Off_Peak_kWh"] > 0)) && (($value["$Peak_kW"] > 0) || ($value["$Off_Peak_kW"] > 0)))
            {
                $someTimeAgo = $value["time"];
                $log->logInfo(sprintf("%s:LastUpdatedEntry %s one week ago %s\n", $LOOPNAME, $lastUpdateTime, $someTimeAgo));
            }
        }
    }

    return $someTimeAgo;
}

/* Return kw when there is a gap in log time.  Use average from module or calculate based on one data point*/
function fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_time, $log_interval,$power, $pulse_meter, $pulse_demand_str)
{
    $sql_check="SELECT * FROM $devicetablename WHERE time='$demand_time'";
    $log->logInfo(sprintf("%s:fixtimegap sql %s", $LOOPNAME, $sql_check));

    if ($value_check = mysql_query($sql_check))
    {
        if ($value = mysql_fetch_array($value_check))
        {
            if (!$pulse_meter)
            {
                $retvalue = $value["Average_Demand"];
                $log->logInfo(sprintf("%s:fixtimegap not pulse value %f", $LOOPNAME, $retvalue));
            }
            else
            {
                $retvalue = $value["$pulse_demand_str"];
                $log->logInfo(sprintf("%s:fixtimegap pulse value[%f]", $LOOPNAME,$retvalue));
            }
            return round($retvalue,2);
        }
     }

    if ($log_interval > 0)
        $log_interval_minutes = ($log_interval / ONE_MIN);
    else
        $log_interval_minutes = 5; //default 5 minutes
    $calc_kw = ($power / ($log_interval_minutes)) * 60; //get average for hour;
    $calc_kw = round($calc_kw,2);
    $log->logInfo(sprintf("%s:Avg not avail kWh power=%f int %d calc kW %f\n",  $LOOPNAME, $power, $log_interval,$calc_kw));

    return($calc_kw);     //need to add check for max possible kWh
}

/*----------------------------------------------------------------------------------------------------*/
/* Get meter status
*/
function getMeterState($LOOPNAME,$aquisuitetable,$devicetablename, $log)
{
    $status = METER_ENABLED;	//default
    $sql = "SELECT * FROM $aquisuitetable WHERE devicetablename='$devicetablename'";
    $result = mysql_query($sql);
    $log->logInfo(sprintf("getMeterState: %s \n",$sql));
    if(!$result)
    {
         $log->logInfo(sprintf("%s:Unable to select meter status [%s]\n",$LOOPNAME,$sql));
         MySqlFailure("unable to select devicetable from $devicetablename");
         return $status;
     }
    $row = mysql_fetch_array($result);
    if ($row)
    {
        $status = $row['meter_status'];
    }
    else
    {
          $log->logInfo(sprintf("%s:Unable to read meter status [%s]\n",$LOOPNAME,$sql));
    }

    return $status;
}

/*
 * Function status_upload
 */
function status_upload($server_info, $request_info, $log_debug)
{
    /*************test input filter function ************/
    /***************************************************
    $server_args = array(
    'REMOTE_ADDR'   => FILTER_UNSAFE_RAW,
    'REMOTE_PORT'   => FILTER_UNSAFE_RAW,
    'SERVER_ADDR'  => FILTER_UNSAFE_RAW);

    $myinputs = filter_input_array(INPUT_POST, $server_args);
    $log_debug->logInfo("TESTING+++++++++++++++\n");
    $log_debug->logInfo($myinputs['REMOTE_ADDR']);
    $log_debug->logInfo($myinputs['REMOTE_POST']);
    $log_debug->logInfo($myinputs['REMOTE_HOST']);

     *********************/



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

    $log_debug->logInfo($sTest);

    $tz = date("Y-m-d H:i:s");
    $log_debug->logInfo(sprintf("Date %s\n",$tz));

    // The following is used to make sure that the correct timezone is aquired for the current upload.
    // It updates the current uploadattempt column in the Aquisuite_List table.

    $uploadattempt = $request_info['UPLOADATTEMPT'];
    $SerialNumber = $request_info['SERIALNUMBER'];

    $sql = "UPDATE `Aquisuite_List` SET `uploadattempt`='$uploadattempt' WHERE `SerialNumber`='$SerialNumber';";
    $result = mysql_query($sql);
    if(!$result)
    {
        MySqlFailure("unable to retrieve deviceclass".$request_info['MODBUSDEVICECLASS']);
    }

    printf("\n");   // blank line to make things look nice.

    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()

    printf("\nSUCCESS\n");   // this line is what the AcquiSuite/AcquiLite searches for in the response to
                            // tell it that the data was received and the original log file may be deleted.

    printf("</pre>\n");  // end of the script


}/** end status_upload *** */


/*
 * Function getRecentComsumption: Get most recent logged energy consumption value for device
 */
function getRecentComsumption($devicetablename, $LOOPNAME, $log)
{
    //Assume, for now, that energy consumption data is at position 4 in log data.
    // this need to be corrected for device classes that do not have data at this position.
    //Use modbusclass in future.
    $sqlLogData = "SELECT * FROM `$devicetablename` ORDER BY time DESC LIMIT 1";
    $resultLog = mysql_query($sqlLogData);
    if (mysql_num_rows($resultLog) > 0)
    {

        $lastLoggedData = mysql_fetch_row($resultLog);
        $colName = mysql_field_name($resultLog, 4);
        //check if column 4 is one of the 3 known energy consumption field names
        if (($colName == 'Energy_Consumption') || ($colName == 'Shore_Power') || ($colName == 'Shore_Power_(kWh)'))
        {

            $lastDataPoint = $lastLoggedData[0];
            $lastEnergyConsumption = $lastLoggedData[4];
            $log->logInfo(sprintf("%s: getRecentComsumption Last Logged Data %s consumption %s", $LOOPNAME, $lastDataPoint, $lastEnergyConsumption));
            return $lastEnergyConsumption;
        }
    }
    $lastEnergyConsumption = 0;
    $log->logInfo(sprintf("%s: getRecentComsumption: Default Last Logged Data %s", $LOOPNAME, $lastEnergyConsumption));
    return $lastEnergyConsumption;
}

/*
 * Function logfile_upload
 */
function logfile_upload($LOOPNAME, $aquisuitetable, $log)
{
    $linecount = 0;
    $errorcode = 0;
    $errorcount = 0;
    $successcount = 0;
    $failcount = 0;
    $modbusdevice = $_REQUEST['MODBUSDEVICE'];
    $modbusclass = $_REQUEST['MODBUSDEVICECLASS'];

 // If the Aquisuite Table has not been created yet then create it.
        $log->logInfo(sprintf("LOGFILEUPLOAD SERIALNUMBER: %s \n", $LOOPNAME));

        checkaquisuite($log,$aquisuitetable, $LOOPNAME);
        $log->logInfo(sprintf("LOGFILEUPLOAD check aquisuite: %s \n", $LOOPNAME)); //debug
        // Once the aquisuite table has been verified or created we identify
        // the current device and check to see if the device is listed in the aquisuite table

        checkdevice($aquisuitetable, $log, $LOOPNAME);
        $log->logInfo(sprintf("LOGFILEUPLOAD Check Device: %s \n", $LOOPNAME)); //debug

         // if this meter is not enabled don't log the data.  This keeps us from logging all zeros from acquisuite module base pulse meter(device 250 class 27) that is not attached to anything
         // meter_status field in database must be disabled manually for this to function.  Default value is enabled(1).
         $devicetablename = mysql_real_escape_string(sprintf("%s__device%03d_class%s",$aquisuitetable,$_REQUEST['MODBUSDEVICE'],$_REQUEST['MODBUSDEVICECLASS']));
         $meterStatus =  getMeterState($LOOPNAME,$aquisuitetable,$devicetablename, $log);
         $log->logInfo(sprintf("%s: Meter Status %d [%s]\n", $LOOPNAME, $meterStatus, $devicetablename ));
         if ($meterStatus ==  METER_DISABLED)
         {
             ob_end_flush();
             printf("\nSUCCESS\n");			// Send SUCCESS to signal the Aquisuite to delete the file if we are not logging for this meter soif doesn't fill up memory.
             $log->logInfo(sprintf("%s:Meter Disabled  End log upload function", $LOOPNAME) );
             return  $devicetablename;
         }
         else
         {
              $log->logInfo(sprintf("%s:  Meter Enabled ", $LOOPNAME) );
         }

        // calculate an MD5 checksum for the file that was uploaded.
        // On the AcquiSuite, the checksum form variable is calculated from the log file after the file has been gziped.
        // On the AcquiLite, the log file is not zipped, so the checksum is calculated on the raw log file.
        // In both cases, the checksum is that of the file that the DAS uploaded to your server.
        // the md5_file() function calculates the md5 checksum of the temp file that PHP stored the log data in when
        // it was decoded from the HTTP/POST.

    $szChecksum = "[error]";                           			  // provide some default value for this in case the following fails.
    if (file_exists($_FILES['LOGFILE']['tmp_name']))              // make sure the file exists before we start.
    {
        $log->logInfo(sprintf("LOGFILEUPLOAD %s: log file exits, checksum verify\n", $LOOPNAME)); //debug
        $szChecksum = md5_file($_FILES['LOGFILE']['tmp_name']);   // built in php function that calculates the md5 checksum of a file.
    }


        // This section prints all the expected form variables.  This serves no real purpose other
        // than to demonstrate the variables.  Enable debugging on the AcquiSuite or AcquiLite to
        // allow these to show up in the log file for your review.

    printf("Logfile upload from ip      %s \n", $_SERVER['REMOTE_ADDR'] );               // print the IP address of the DAS client.
    printf("Got SENDDATATRACE:          %s \n", $_REQUEST['SENDDATATRACE'] );             // set when the AcquiSuite requests full session debug messages
    printf("Got MODE:                   %s \n", $_REQUEST['MODE'] );                      // this shows what type of exchange we are processing.
    printf("Got SERIALNUMBER:           %s \n", $_REQUEST['SERIALNUMBER'] );              // The acquisuite serial number
    printf("Got LOOPNAME:               %s \n", $_REQUEST['LOOPNAME']  ) ;                // The name of the AcquiSuite (modbus loop name)
    printf("Got MODBUSIP:               %s \n", $_REQUEST['MODBUSIP'] );                  // Currently, always 127.0.0.1, may change in future products.
    printf("Got MODBUSPORT:             %s \n", $_REQUEST['MODBUSPORT']  ) ;              // currently, always 502, may change in future products.
    printf("Got MODBUSDEVICE:           %s \n", $_REQUEST['MODBUSDEVICE']  ) ;            // the modbus device address on the physical modbus loop. (set by dip switches)
    printf("Got MODBUSDEVICENAME:       %s \n", $_REQUEST['MODBUSDEVICENAME']  ) ;        // the user specified device name.
    printf("Got MODBUSDEVICETYPE:       %s \n", $_REQUEST['MODBUSDEVICETYPE'] ) ;         // the identity string returned by the modbus device identify command
    printf("Got MODBUSDEVICETYPENUMBER: %s \n", $_REQUEST['MODBUSDEVICETYPENUMBER']  ) ;  // the identity number returned by the modbus device identify command
    printf("Got MODBUSDEVICECLASS:      %s \n", $_REQUEST['MODBUSDEVICECLASS'] ) ;        // a unique id number for the modbus device type.
    printf("Got MD5CHECKSUM:            %s \n", $_REQUEST['MD5CHECKSUM']  );              // the MD5 checksum the AcquiSuite generated prior to upload
    printf("calculated checksum:        %s \n", $szChecksum ) ;                           // the MD5 sum we calculated on the file we received.
    printf("Got FILETIME:               %s \n", $_REQUEST['FILETIME']  ) ;                // the date and time the file was last modified. (in UTC time).
    printf("Got FILESIZE:               %s \n", $_REQUEST['FILESIZE'] ) ;                 // the original size of the log file on the AcquiSuite flash disk prior to upload
    printf("calculated filesize:        %s \n", filesize($_FILES['LOGFILE']['tmp_name'])); // the calculated file size of the file we received..
    printf("Got LOGFILE orig name:      %s \n", $_FILES['LOGFILE']['name'] );             // This is original file name on the AcquiSuite flash disk.
    printf("Got LOGFILE tmp name:       %s \n", $_FILES['LOGFILE']['tmp_name'] );         // This is the PHP temp file name where PHP stored the file contents.
    printf("Got LOGFILE size:           %s \n", $_FILES['LOGFILE']['size']  );            // What PHP claims the temp file size is
    printf("\n");

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

/* -------------------------------------------------------------------------------------------------------------------*/
        // now we should check the log file checksum to verify it is correct.
        // if not, something got corrupted.  refuse the file and the DAS will upload it again later.

   //  $log->logInfo(sprintf("LOGFILEUPLOAD %s before checksum \n", $LOOPNAME)); //debug
    if ($szChecksum != $_REQUEST['MD5CHECKSUM'])
    {
        $log->logInfo(sprintf("%s:logfile checksum error  \n", $LOOPNAME) );
        ReportFailure("The checksum of received file does not match the checksum form variable sent by the DAS.\n");
        exit;
    }
      //  $log->logInfo(sprintf("LOGFILEUPLOAD %s after checksum \n", $LOOPNAME)); //debug
/* -------------------------------------------------------------------------------------------------------------------*/

        // The MODBUSDEVICECLASS is a unique id that allows the AcquiSuite to decide what class of device
        // it is working with.  It is assumed that the number and type of points stored for a specific
        // device type are the same.  For example, the Veris 8036 power meter has several versions
        // for 100, 300, 800Amp, however the list of points are the same for all devices.  The deviceclass
        // will be the same for all flavors of Veris 8036 meters.
        // A complete list of deviceclass values are listed in the pushupload/tablestructures directoryf
        // in the readme.txt file of this zip archive.   Also provided is the table structure for
        // each listed device class.
        // For example, the deviceclass may be one of the following:
        //       MBCLASS_UNKNOWN     0
        //       MBCLASS_H8036       2           (26 data columns)
        //       MBCLASS_M4A4P2      9           (32 data columns, A8923 enhanced io module, A8811 built-in io module)
        // check here to verify the modbus device name, type, and class make sense based on previous uploads.
        // You should use this information to ensure the data is stored in a table with the correct number of columns.


        // Next open the file handle to the uploaded data file that came from the DAS.
        // the bulk of the work here is the PHP function $_FILES which provides an array of all files embedded in the
        // mime data sent to the server in the HTTP/POST.  You may read from any one file by requesting the file by name
        // in the index.  ie, a file attached as "LOGFILE" is referred to by the array element $_FILES['LOGFILE'].
        // the element is actually a second array with elements for "name", "tmp_name" and "size".  The tmp_name element
        // provides the file name that the PHP engine used to store the contents of the file.  To access the file data,
        // simply open the file with the name provided by the tmp_name element, and read the data.
        // note: gzopen() will read both compressed log files (AcquiSuite) and uncompressed text log files (AcquiLite).

   // $log->logInfo(sprintf("LOGFILEUPLOAD %s before fileopen \n", $LOOPNAME)); //debug
    $fd = gzopen($_FILES['LOGFILE']['tmp_name'], "r");
    if (!$fd)
    {
        $log->logInfo(sprintf("%s:logfile failed to open file \n", $LOOPNAME) );
        ReportFailure("gzopen failed to open logfile " . ($_FILES['LOGFILE']['tmp_name']));
    }
     // $log->logInfo(sprintf("LOGFILEUPLOAD %s after fileopen \n", $LOOPNAME)); //debug

        // create a log file on the server. Since there is already a backup logfile dumped into the Aquisuite folder
        // we will put this file in the Archive directory and name it specific to the device.
        // note that the file is created with the permissions of the webserver.
        // the file is in /xxxx/Archive/mb-yyy_classww_zzzzzzzz.log
        // where xxx is the serial number of the AcquiSuite or AcquiLite, ww is the class number,
        // and yyy is the modbus address number of the device, and zzzzzzzz is the unique datestamp when the file was received.
        // DANGER!.  it is a really bad idea to create a file with unfiltered input from a webserver post.
        // this is why we generate a file name based on some other parameters.
        // this function is only safe if you validate the SERIALNUMBER field with known valid values from your database.
        // the modbus device is relatively safe as it is reformated as a number

    //$szTargetDirectory = sprintf("%s_%s/Archive", $LOOPNAME, $_REQUEST['SERIALNUMBER']);

    //$szTargetFilename  = sprintf("%s/mb-%03d_class%s_%s.csv", $szTargetDirectory, $_REQUEST['MODBUSDEVICE'],$_REQUEST['MODBUSDEVICECLASS'],date ("M-j-y_H-i-s", time()));
   //$log->logInfo(sprintf("%s:logfile [%s] \n", $LOOPNAME, $szTargetFilename) );

    $devicetablename = mysql_real_escape_string(sprintf("%s__device%03d_class%s",$aquisuitetable,$_REQUEST['MODBUSDEVICE'],$_REQUEST['MODBUSDEVICECLASS']));
      $log->logInfo(sprintf("LOGFILEUPLOAD %s after getting devicetablename [%s]\n", $LOOPNAME,$devicetablename)); //debug

    //if (!file_exists($szTargetDirectory ))            // if the directory does not exist, create it.
   // {
    //    $nResult = mkdir($szTargetDirectory, 0700);    // create directory (unix permissions 0700 are for directory owner access only)
    //    if (!$nResult)                                // trap directory create errors.
    //        ReportFailure("Error creating directory " . $szTargetDirectory);
   // }

        // very basic test here to make sure we don't overwrite previous log data.  if a file exists, abandon
        // the upload, and let the DAS try to upload this log file later.  this should only happen if the DAS
        // tries to upload 2 log files within 1 second such that the timestamp doesn't change.  This would almost
        // never happen, so the harsh handling (refusal) shouldn't be much of a problem.

    //if (file_exists($szTargetFilename ))
    //{
    //    $log->logInfo(sprintf("%s:logfile files already exists %s \n", $LOOPNAME, $szTargetFilename) );
    //    ReportFailure("target file already exits, try again later " . $szTargetFilename);
   // }


    //$fOut = fopen($szTargetFilename, 'w');           // create/open target file for writing
   // if (!$fOut)                                       // trap file create errors.
    //{
   //   $log->logInfo(sprintf("%s:logfile error creating file %s \n", $LOOPNAME, $szTargetFilename) );
    //  ReportFailure("Error creating file " . $szTargetFilename);
    //}

    //printf("saving data to file %s\n", $szTargetFilename);  // be nice and print out the target file location.

    $fieldcount = counttablefields($log, $devicetablename, $LOOPNAME);		// counts the number of fields in the device table to
								// account for any additional columns that may have been
								// added for utility purposes. Used for building the device
								// table logfile mySQL query below.

  //  $log->logInfo(sprintf("LOGFILEUPLOAD %s before counting errortract\n", $LOOPNAME)); //debug
    $errortrackcount = counttablefields($log,"errortrack", $LOOPNAME);		// does the same as above, but for the errortrack table.
									// its used in building the mySQL query
  $log->logInfo(sprintf("LOGFILEUPLOAD %s loop through data fieldcount %d \n", $LOOPNAME, $fieldcount)); //debug
    while(!gzeof($fd))                             // loop through the source file until we reach the end of the file.
    {
        $errorcode = 0;
    	$szBuffer = gzgets($fd, 4096);              // read lines from the log file.  make sure lines don't exceed 512 bytes (1024)

    	if (strlen($szBuffer) > 0)                 // verify the line is not blank.
        {
          //$log->logInfo(sprintf("%s:[%s] \n", $LOOPNAME, $szBuffer) );

           // $nResult = fputs($fOut, $szBuffer);    // write data to the log file.
            //if (!$nResult)                        // trap file write errors.
           // {
            //    $log->logInfo(sprintf("%s:logfile error writing to file \n", $LOOPNAME) ); //debug
             //   ReportFailure("Error writing to output file " . $szTargetFilename );
             //   exit;
           // }
                 // You must check for bad chars here, such as semicolon, depending on your flavor of SQL.
                 // All data in the log file should be numeric, and may include symbols: minus, comma, space, or 'NULL' if the data point was not available.
                 // at some point in the future, Obvius will replace the word "NULL" with a blank column in the log data to save space.
                 // not checking for characters like semicolon poses a security risk to your SQL database
                 // as someone could insert a line "3,4,5,7');drop table password;('3,4,5" into the log file. (this would be very bad)
                 // replace "sometable" with the name of the table you wish to insert the data into.

			if (substr($szBuffer,0,4)!="time")		// skip the header line
			{
                            $linecount += 1;					// count the lines for error checking purposes

                            $fieldarray = explode(",", $szBuffer);             // start check for bad chars here, like semicolon. first split a single log entry in to components.
                            $nCol = 0;
                            $query = "INSERT INTO $devicetablename VALUES (";     // query prefix.  finished query string is:  insert into table values ('1','2','3')
                            foreach ($fieldarray as $value)                           // loop through each array element by calling it "value", (one per data column,)
                            {
                                 $value = str_replace('"', "", $value);     	// strip double quotes
				 $value = str_replace("'", "", $value);  		// strip single quotes
				 $value = trim($value);                   		// trim whitespace, tabs, etc, on the ENDS of the string.
				 $value = mysql_real_escape_string($value);   	// MySQL has a special strip function just for this purpose, other SQL versions may vary.

			// The Logfiles coming from the Aquisuite system have the same first 4 columns in the heading (`time_UTC`,`error`,`lowrange`,`highrange`).
			// The following switch function is breaking apart the logfile in CSV format to create a query that will be inserted into the device table.


				 switch($nCol)
				 {
                                     case 0:
                                            $query  = $query . sprintf("'%s'", $value); 	// quote data (utc date), first column has no leading comma
                                            $time = $value;
                                            break;
                                      case 2:
                                      case 3:
                                          if ($value == NULL)
                                          {
                                              $value = '0';
                                          }
                                            $query  = $query . sprintf(",%s", $value);  // don't quote hex (alarm) data, column needs leading comma seperator.
                                            break;
                                       default:
                                        {

                                        // here we trap any error values that are greater than 0. If no previous errors have occured
                                        // for a particular device then a line is inserted into the errortrack table as well as the
                                        // errorlog table with a timestamp for the initial time the error has occured. A count is updated
                                        // for each additional error of the same type.

                                            if ($nCol==1 && $value!="0")
                                            {
                                                $errorcount += 1;	// start the error count

                                                $errorcode = $value;

                                                $sql = "SELECT `$errorcode` FROM `errortrack` WHERE `devicetablename`='$devicetablename'";
                                                $result = mysql_query($sql);
                                                if(!$result)
                                                {
                                                    $log->logInfo(sprintf("%s:logfile sql error errortrack \n", $LOOPNAME) ); //debug
                                                    MySqlFailure("could not update errortrack");
                                                }

                                                $rowcount = mysql_num_rows($result);

                                                if($rowcount==0)
                                                {
                                                    $sql = "INSERT INTO `errortrack` VALUES ('$devicetablename'";	// start building the device error insert query
                                                    $e=1;
                                                    while($e<$errortrackcount-1)
                                                    {
                                                        $sql = $sql.",'0'";		        // insert zero values for each of the error codes.
                                                                                                        // the error code will be updated later.
                                                        $e++;
                                                    }
                                                    $sql = $sql.",'0')";			// end the query

                                                    $result=mysql_query($sql);
                                                    if(!$result)
                                                    {
                                                        $log->logInfo(sprintf("%s:logfile sql error query %s \n", $LOOPNAME, $sql) ); //debug
                                                        MySqlFailure("unable to insert devicetablename into errortrack table");
                                                    }
                                                 }

                                                // Once the device table row has been inserted into
                                                // the errortrack table update error code count.

                                                $sql = "UPDATE `errortrack` SET `$errorcode`=`$errorcode`+1 WHERE `devicetablename`='$devicetablename'";

                                                $result=mysql_query($sql);
                                                if(!$result)
                                                {
                                                        $log->logInfo(sprintf("%s:logfile sql error result %s \n", $LOOPNAME, $sql) ); //debug
                                                        MySqlFailure("Unable to Update devicetablename in errortrack");
                                                }

                                                    // check to see if the first instance of the error
                                                    // has been entered into the database.

                                                    $sql = "SELECT `$errorcode` FROM `errortrack` WHERE `devicetablename`='$devicetablename'";

                                                    $result=mysql_query($sql);
                                                    if(!$result)
                                                    {
                                                        $log->logInfo(sprintf("%s:logfile sql error result2 %s \n", $LOOPNAME, $sql) ); //debug
                                                         MySqlFailure("Unable to Select errorcode from errortrack");
                                                    }

                                                    $row = mysql_fetch_row($result);
                                                    $codecount = $row[0];
                                                    if($codecount==1)
                                                    {
                                                        $sql = "INSERT INTO `errorlog` VALUES ('$devicetablename','$errorcode','$time')";	// log error code and time stamp for first error instance device

                                                        $result = mysql_query($sql);
                                                        if(!$result)
                                                        {
                                                                $log->logInfo(sprintf("%s:logfile sql error codecount %s \n", $LOOPNAME, $sql) ); //debug
                                                                MySqlFailure("Unable to Insert errorcode into errorlog");
                                                        }

                                                    }
                                                }
                                                //log data with error code 139 but use energy consumption field from most recent logged data
                                                 if ($errorcode == 139 && ($nCol == 4))
                                                 {
                                                     $value = getRecentComsumption($devicetablename, $LOOPNAME, $log);
                                                 }

                                                if ($value == "")
                                                {
                                                //   {$query  = $query . ",''" ;}    // don't quote the word 'NULL'
                                                        {$query  = $query . ",'0'" ;}    // don't quote the word 'NULL'
                                                    // $log->logInfo(sprintf("%s:count %d add field [%s]\n", $LOOPNAME, $nCol, $query) ); //debug
                                                }
                                                else
                                                 {
                                                     $query  = $query . sprintf(",'%s'",$value); // quote data, all other columns need leading comma seperator.
                                                  }

                                               }/* end default case */
                                     }/*end switch*/

                                 $nCol++;	// continue to next data column in the logfile
                             }

				// An error code greater than 0 signifies that no data has
				// been logged for that time period and we don't want the
				// line to be entered into the device table.
				if($errorcode>0)
				{
                                    $log->logInfo(sprintf("%s:logfile data line error %s\n", $LOOPNAME, $errorcode) ); //debug
                                   // printf("data line error %s\n",$errorcode);
                                    //139  Device Failed to Respond (the Modbus device may be off or disconnected
                                   //If error is 139 it is a notification so still log the data
                                    if ($errorcode != 139)
                                    {
                                        $errorcode = 0;
                                        continue;
                                    }
                                    else
                                    {
                                        $errorcount--; //don't add up errors for 139 since we're logging that data
                                    }
				}

				// verify the current time values have not already been
				// entered into the database.

				$sql = "SELECT * FROM $devicetablename WHERE time='$time'";

				$result = mysql_query($sql);


				if(!$result)
				{
                                   $log->logInfo(sprintf("%s:logfile sql error result3 %s \n", $LOOPNAME, $sql) ); //debug
                                    MySqlFailure("could not execute query SQL: %s\n",$sql);
				}

				$rowsupdated = mysql_num_rows($result);


				if ($rowsupdated>0)
				{
                                    $log->logInfo(sprintf("%s:log %s already exists [%s]\n", $LOOPNAME, $time, $sql) ); //debug
                                    echo "record datetime: $time already exists"."</br>";
                                    continue;
				}

				// add empty values for any additional mySQL columns that
				// may be used for utility purposes. Utility values will
				// be updated later.

				if($fieldcount!=$nCol)
				{
                                    $addfields = $fieldcount - $nCol;
                                    $i=0;
                                    while($i<$addfields)
                                    {
                                       // $query = $query . ",''";
                                        $query = $query . ",'0'";
                                        $i++;
                                    }
				}

				$query = $query . ")";			// close sql query
				$result = mysql_query($query);

				// Count successful and failed sql inserts for full debugging purposes. In expanding
				// this script it may be wise to add a log file   for the mySQL errors and warnings during a
				// device upload session.

				if(!$result)
				{
                                $log->logInfo(sprintf("%s: SQL ERROR[%s] \n", $LOOPNAME, mysql_error()) ); //debug
                                 $log->logInfo(sprintf("%s:logfile ERROR %s \n", $LOOPNAME, $query) ); //debug
                                    echo "ERROR SQL QUERY: $query WAS NOT INSERTED"."</br>";
                                    $failcount += 1;

				}
				else
				{
                                    $successcount += 1;
				}
			}
        }
    }
	// As per the explanation at the top of this script the Aquisuite will only delete the log file if it recieves
	// a \nSUCCESS\n otherwise it holds on to the file to upload later. If the entire log file contains errors, then the
	// file is no good. Here we force a SUCCESS so the aquisuite will delete the bad logfile.

	if($errorcount==$linecount)
	{
        	$log->logInfo(sprintf("%s  LOGFILEUPLOAD ERROR count %d\n", $LOOPNAME, $errorcount)); //debug
		echo "error count: $errorcount = line count : $linecount --- Error status preventing data from being recorded ";
		echo "DATA LINE NOT UPDATED";
	}
	else
	{
		printf("%s lines inserted \n",$successcount);
		if($failcount!=0)
		{
              		$log->logInfo(sprintf("LOGFILEUPLOAD %s  fail\n", $LOOPNAME)); //debug
			ReportFailure("$failcount lines failed");  //script exits in report failure
		}
        }

        ob_end_flush();
        printf("\nSUCCESS\n");			// file data is no good send SUCCESS to signal the Aquisuite to delete the file.
	//fclose($fOut);  // close the target file
        gzclose($fd);   // close the source uploaded file,  PHP automatically removes temp files when the script exits.
        $log->logInfo(sprintf("%s: End log upload function \n", $LOOPNAME) );

        return  $devicetablename;

}/*end function*/

function utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log)
{

// DEVICE CLASS 2 Class 27 is series of inputs that could be defined to various
// meters. Here a class 27 pulse meter reading kWh which will serve the same function as a class 2.

    $log->logInfo(sprintf("%s: Start New Utility Cost\n", $LOOPNAME) );

    $meterStatus =  getMeterState($LOOPNAME,$aquisuitetable,$devicetablename, $log);
    $log->logInfo(sprintf("%s: Meter Status %d device table %s\n", $LOOPNAME, $meterStatus, $devicetablename ));
    if ($meterStatus ==  METER_DISABLED)
        return;

    if($_REQUEST['MODBUSDEVICECLASS']!=2 && $_REQUEST['MODBUSDEVICECLASS']!=27)
    {
        $log->logInfo(sprintf("%s: Utility_cost return\n", $LOOPNAME) );
        $log->logInfo(sprintf("%s: Utility_cost return modbusclass %d \n", $LOOPNAME,$_REQUEST['MODBUSDEVICECLASS']));
        return;
    }

	$utility = utility_check($aquisuitetable); // check to see if device has an associated utility table

	$log->logInfo(sprintf("%s:utility check %s\n", $LOOPNAME, $utility));

	if(empty($utility))
	{
	     $log->logInfo(sprintf("%s: Utility Unavailable\n",$LOOPNAME));
	}

	$db = 'bwolff_eqoff';
	$timezone = timezone($aquisuitetable,$log,$LOOPNAME);		// check for current time zone
	$pulse_demand_str = '';
        $pulse_meter = FALSE;

        $NowDate = date('Y-m-d H:i:s',strtotime('now')); //get recent rate schedule
        $sqlLogData = "SELECT time FROM `$devicetablename` ORDER BY time DESC LIMIT 1";
        $log->logInfo( sprintf("%s utility_cost [%s]", $LOOPNAME, $sqlLogData));
        $resultLog = mysql_query($sqlLogData);
        if (mysql_num_rows($resultLog) > 0)
        {
            $lastLoggedData = mysql_fetch_row($resultLog);
            $lastDataPoint = $lastLoggedData[0];
            $log->logInfo( sprintf("%s utility_cost: Last Logged Data %s", $LOOPNAME, $lastDataPoint));
        }
        else
        {
             $lastDataPoint = $NowDate;
             $log->logInfo( sprintf("%s utility_cost: Default Last Logged Data %s", $LOOPNAME, $lastDataPoint));
        }

	//$log->logInfo(sprintf("%s: %s timezone %s \n", $LOOPNAME, $aquisuitetable,$timezone));

	$sql_rates = "SELECT * FROM `$utility`";
        $sql_rates = sprintf("SELECT * FROM `$utility` WHERE Rate_Date_End >= '%s' AND Rate_Date_Start <= '%s'", $lastDataPoint, $lastDataPoint);

	$rate_q = mysql_query($sql_rates);
	if(!$rate_q) { $log->logInfo( sprintf("%s unable to process select mysql request %s", $LOOPNAME, $sql_rates)); return;  }

	$log_interval = getLogInterval($aquisuitetable,$log,$LOOPNAME);
	$log->logInfo(sprintf("%s: %s timezone %s log interval %d \n", $LOOPNAME, $aquisuitetable, $timezone, $log_interval));



	// SCE&G UTILITY RATE 24
	if ($utility == "SCE&G_Rates")
    {
        $cost = mysql_fetch_array($rate_q);
        if (!$cost)
        {
            echo "unable to process mysql fetch cost" . $sql_rates . "\n";
            exit;
        }

        $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $utility));

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

        $Summer_Start = idate('m', strtotime($cost[10]));
        $Summer_End = idate('m', strtotime($cost[11]));
        $Peak_Time_Summer_Start = idate('H', strtotime($cost[12]));
        $Peak_Time_Summer_Stop = idate('H', strtotime($cost[13]));
        $Peak_Time_Non_Summer_Start = idate('H', strtotime($cost[14]));
        $Peak_Time_Non_Summer_Stop = idate('H', strtotime($cost[15]));
        $Peak_Time_Non_Summer_Start2 = idate('H', strtotime($cost[16]));
        $Peak_Time_Non_Summer_Stop2 = idate('H', strtotime($cost[17]));
        $MayOct_Start = idate('H', strtotime($cost[18]));
        $MayOct_End = idate('H', strtotime($cost[19]));
        $log->logInfo(sprintf("%s: rate start %s rate end %s  MayStart %s MayEnd %s\n", $LOOPNAME, $cost[20], $cost[21], $MayOct_Start, $MayOct_End));

        //$log->logInfo( sprintf("%s:sumMonth %d sumendMonth %d summer startH %d stopH %d  nonsumstart %d nonsumend %d start2 %d stop2 %d \n",
        // $LOOPNAME, $Summer_Start, $Summer_End, $Peak_Time_Summer_Start, $Peak_Time_Summer_Stop,
        // $Peak_Time_Non_Summer_Start, $Peak_Time_Non_Summer_Stop,
        // $Peak_Time_Non_Summer_Start2 ,$Peak_Time_Non_Summer_Stop2 ));

        date_default_timezone_set("UTC"); //go back to UTC time

        $db = 'bwolff_eqoff';
        $EC_kWh = 'Energy_Consumption';
        $Peak_kW = "Peak_kW";
        $Peak_kWh = "Peak_kWh";
        $Off_Peak_kW = "Off_Peak_kW";
        $Off_Peak_kWh = "Off_Peak_kWh";

        if ($_REQUEST['MODBUSDEVICECLASS'] == 27) //pulse meters set real power
            $pulse_meter = TRUE;
        else
            $pulse_meter = FALSE;
        if ($_REQUEST['MODBUSDEVICECLASS'] == 27 && $_REQUEST['SERIALNUMBER'] == "001EC6001635") //Cape Knox
        {
            $EC_kWh = 'Shore_Power';
            $rp = 'Real_Power';
            $pulse_demand_str = 'Shore_Power_Demand';
        } else if ($_REQUEST['MODBUSDEVICECLASS'] == 27 && $_REQUEST['SERIALNUMBER'] == "001EC6001433") //Cape Kennedy
        {
            $EC_kWh = 'Shore_Power_(kWh)';
            $rp = 'Real_Power';
            $pulse_demand_str = 'Shore_Power_(kWh)_Demand';
        }
        //$log->logInfo( sprintf("%s: %s\n", $LOOPNAME, $EC_kWh));
        $NowPlusDay = date('Y-m-d H:i:s', strtotime('+1 day'));
        $someTimeAgo = lastUpdatedEntry($LOOPNAME, $log, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $lastDataPoint);
        $sql_update = sprintf("SELECT time, `%s` ,`%s` ,`%s` , `%s` , `%s`  FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo, $NowPlusDay);

        $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update));

        $RESULT_update = mysql_query($sql_update);
        if (!$RESULT_update)
        {
            $log->logInfo(sprintf("%s: ERROR SQL Select %s\n", $LOOPNAME, $sql_update));
            echo sprintf("%s:SQL Result failure \n", $LOOPNAME);
            MySqlFailure("invalid msql_query");
            return;
        }

        $rows_remaining = mysql_num_rows($RESULT_update);
        if (!$rows_remaining)
            $log->logInfo(sprintf("%s:SQL rows remaining failure \n", $LOOPNAME));

        while ($row = mysql_fetch_array($RESULT_update))
        {
            $time[] = $row['time'];  // UTC Time
            $kWh[] = $row["$EC_kWh"];  // Running Total Energy Consumption kWh
            $O_kWh_value[] = $row["$Peak_kWh"];
            $OP_kWh_value[] = $row["$Off_Peak_kWh"];
            $O_kW_value[] = $row["$Peak_kW"];
            $OP_kW_value[] = $row["$Off_Peak_kW"];
        }

        $i = -1;
        $countd = 0;
        if (isset($time))
            $countd = count($time);

        $log->logInfo(sprintf("%s: loop for %d \n", $LOOPNAME, $countd));
        while ($i <= $countd - 15) //array bounds check
        {
            $i++;

            if ($kWh[$i] !== 0 && $kWh[$i] > $kWh[$i + 1])  // make sure the kWh reading is not 0 and the current value is greater that the previous value.
            {
              // Since there may have been a new meter installed and data combined,
              // the old meter data could be adjusted to a negative value.
              // We need to adjust for that here
                $Power = abs($kWh[$i] - $kWh[$i + 1]);  // Peak kWh
                $Power_time = $time[$i];   // Peak kWh time
                //$log->logInfo(sprintf("%s: loop %d +1[%d] %s +1[%s] \n", $LOOPNAME, $i,($i+1), $time[$i],$time[$i+1]));

                date_default_timezone_set($timezone); //set timezone to ship timezone for conversion of peak times
                $timestamp = strtotime($time[$i] . 'UTC'); // timestamp the time in the device timezone format
                // set up the month hour and week for the correct timezone
                $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
                $hour = idate('H', strtotime(date('Y-m-d H:i:s', $timestamp)));
                $week = idate('w', strtotime(date('Y-m-d H:i:s', $timestamp)));
                date_default_timezone_set("UTC"); //set timezone back to UTC
                // determine whether the kWh value has occured during Peak or Off Peak time.
                // Set the query for the device utility column update.
                if ($month >= $Summer_Start && $month < $Summer_End && $week < 6 && $week > 0 && $hour >= $Peak_Time_Summer_Start && $hour < $Peak_Time_Summer_Stop)
                {
                    $timeIsPeak = TRUE;
                    $sql_str = "UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power'";
                } else if ((($month < $Summer_Start || $month >= $Summer_End) &&
                    ($week < 6 && $week > 0) &&
                    (($hour >= $Peak_Time_Non_Summer_Start && $hour < $Peak_Time_Non_Summer_Stop) ||
                    ($hour >= $Peak_Time_Non_Summer_Start2 && $hour < $Peak_Time_Non_Summer_Stop2)) &&
                    ($month != 5 && $month != 10) ) ||
                    (($month == 5 || $month == 10) && ($hour >= $MayOct_Start && $hour < $MayOct_End) ))
                {
                    $timeIsPeak = TRUE;
                    $sql_str = "UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power'";
                } else
                {
                    $timeIsPeak = FALSE;
                    $sql_str = "UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power'";
                }
                if ($pulse_meter) //pulse meters set real power
                {
                    $t_int = strtotime($time[$i]) - strtotime($time[$i + 1]);
                    $rp_value = ($Power / ($t_int / ONE_HOUR));
                    $sql_peak_kWh = sprintf("%s, `%s`=%f WHERE %s.`time`='%s';", $sql_str, $rp, $rp_value, $devicetablename, $Power_time);
                } else
                {
                    $sql_peak_kWh = sprintf("%s WHERE %s.`time`='%s';", $sql_str, $devicetablename, $Power_time);
                }
                $log->logInfo(sprintf("%s sce-g:Sql [%s] \n", $LOOPNAME, $sql_peak_kWh));


                if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0))
                {
                    $peak_query = mysql_query($sql_peak_kWh);
                    // For Aquisuite full debug mode prints out information to tell the user if the kWh utility columns have been updated.
                    if (!$peak_query)
                    {
                        $log->logInfo(sprintf("%s:Error not updated %s %f %s  \n", $LOOPNAME, $Power_time, $Power, $sql_peak_kWh));
                        echo "$devicetablename $Power_time : $Power not updated" . "</br>";
                    } else
                    {
                        echo "$devicetablename $Power_time : $Power updated" . "</br>";
                    }
                }


                $interval = strtotime($time[$i]) - strtotime($time[$i + 1]);
                //$log->logInfo(sprintf("%s: calculatedinterval %d \n", $LOOPNAME, $interval));
                // the Demand kW formula varies based on the data logging time interval.
                if ($log_interval == 300) // 5 minute log
                {
                    if ($i < ($countd - 3)) //array bounds check
                    {
                        $demand_15 = abs($kWh[$i] - $kWh[$i + 3]) * 4;
                        $demand_15_time = $time[$i];
                        if ((strtotime($time[$i]) - strtotime($time[$i + 3]) != 900 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) //900 is 15 minutes
                        {
                            $demand_15 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_15_time, $log_interval, $Power, $pulse_meter, $pulse_demand_str);
                            $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n", $demand_15_time, $log_interval, $demand_15, (strtotime($time[$i]) - strtotime($time[$i + 3]))));
                        }
                    }
                } else if ($log_interval == 60) // 1 minute log
                {
                    if ($i < ($countd - 15)) //array bounds check
                    {
                        $demand_15 = abs($kWh[$i] - $kWh[$i + 15]) * 4;
                        $demand_15_time = $time[$i];
                        if ((strtotime($time[$i]) - strtotime($time[$i + 15]) != 900 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0))  //900 is 15 minutes
                        {
                            $demand_15 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_15_time, $log_interval, $Power, $pulse_meter, $pulse_demand_str);
                            $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15));
                        }
                    }
                } else
                {
                    $demand_15_time = $time[$i];
                    $demand_15 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_15_time, $log_interval, $Power, $pulse_meter, $pulse_demand_str);
                    //echo sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15);
                }

                if (!empty($demand_15))     // only update the demand if the value has been assigned.
                {

                    if ($timeIsPeak)
                    {
                        $sql_peak_kW = "UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
                    } else
                    {
                        $sql_peak_kW = "UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";
                    }

                    if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0))
                    {
                        $kW_peak_query = mysql_query($sql_peak_kW);

                        if (!$kW_peak_query)
                        {
                            $log->logInfo(sprintf("%s: %s %s not updated SQL kw peak query fail \n", $LOOPNAME, $utility, $demand_15_time));
                        } else
                        {
                            $log->logInfo(sprintf("%s: time %s demand %f\n", $devicetablename,$demand_15_time,$demand_15));
                        }
                    } else
                    {
                         $log->logInfo(sprintf("%s:%s Already Updated Peak kW %f Off %f\n",$devicetablename, $demand_15_time, $O_kW_value[$i], $OP_kW_value[$i]));
                        break;
                    }
                } else
                {
                    $log->logInfo(sprintf("%s: %s Demand not set\n", $LOOPNAME, $utility));
                }
            }
        }
        unset($time, $kWh, $O_kWh_value, $OP_kWh_value, $O_kW_value, $O_kW_value); //clear arrays as precaution
    } //end SCE&G utility
    //
    // VIRGINIA DOMINION RATE GS 3
    if ($utility == "Virginia_Dominion_Rates")
    {
        $cost = mysql_fetch_array($rate_q);
        if (!$cost)
        {
            $log->logInfo(sprintf("%s:util fetch array sql error", $LOOPNAME));
        }

        $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $utility));

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

        $Summer_Start = idate('m', strtotime($cost['Summer_Start']));
        $Summer_End = idate('m', strtotime($cost['Summer_End']));
        $Peak_Time_Summer_Start = idate('H', strtotime($cost['Peak_Time_Summer_Start']));
        $Peak_Time_Summer_Stop = idate('H', strtotime($cost['Peak_Time_Summer_Stop']));
        $Peak_Time_Non_Summer_Start = idate('H', strtotime($cost['Peak_Time_Non_Summer_Start']));
        $Peak_Time_Non_Summer_Stop = idate('H', strtotime($cost['Peak_Time_Non_Summer_Stop']));
        date_default_timezone_set("UTC"); //go back to UTC time

        $db = 'bwolff_eqoff';
        $EC_kWh = 'Energy_Consumption';
        $Peak_kW = "Peak_kW";
        $Peak_kWh = "Peak_kWh";
        $Off_Peak_kW = "Off_Peak_kW";
        $Off_Peak_kWh = "Off_Peak_kWh";
        $RP_kVAR = "Reactive_Power";
        $kVAR_30 = "30_Min_Reactive_kVAR";

        $log->logInfo(sprintf("%s: check for last update entry \n", $LOOPNAME));

        $NowPlusDay = date('Y-m-d H:i:s', strtotime('+1 day'));
        $someTimeAgo = lastUpdatedEntry($LOOPNAME, $log, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $lastDataPoint);

        $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $RP_kVAR, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $kVAR_30, $devicetablename, $someTimeAgo, $NowPlusDay);

        $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update));

        $RESULT_update = mysql_query($sql_update);
        if (!$RESULT_update)
        {
            $log->logInfo(sprintf("%s:Utility SQL select failure \n", $LOOPNAME));
            MySqlFailure("invalid msql_query");
            return;
        }

        $rows_remaining = mysql_num_rows($RESULT_update);
        if (!$rows_remaining)
        {
            $log->logInfo(sprintf("%s:Utility SQL rows remaining failure \n", $LOOPNAME));
        }

        while ($row = mysql_fetch_array($RESULT_update))     // changed to assoc array $row=mysql_fetch_array($RESULT_update)
        {
            if (isset($row['time']))
            {
                $time[] = $row['time'];
            }

            if (isset($row["$EC_kWh"]))
            {
                $kWh[] = $row["$EC_kWh"];
            }
            if (isset($row["$RP_kVAR"]))
            {
                $kVAR[] = $row["$RP_kVAR"];
            }
            $kVAR30_value[] = $row["$kVAR_30"];
            $O_kWh_value[] = $row["$Peak_kWh"];
            $OP_kWh_value[] = $row["$Off_Peak_kWh"];
            $O_kW_value[] = $row["$Peak_kW"];
            $OP_kW_value[] = $row["$Off_Peak_kW"];
        }

        $i = -1;
        $countd = 0;
        if (isset($time))
            $countd = count($time);

        $log->logInfo(sprintf("%s:Loop for %d\n", $LOOPNAME, $countd));
        while ($i <= $countd - 15) //array bounds check
        {
            $i++;

            if ($kWh[$i] != 0 && $kWh[$i] > $kWh[$i + 1])
            {

                $Power = abs($kWh[$i] - $kWh[$i + 1]);
                $Power_time = $time[$i];

                date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
                $timestamp = strtotime($time[$i] . ' UTC');
                // set up the month hour and week for the correct timezone
                $month = idate('m', strtotime(date('Y-m-d H:i:s', $timestamp)));
                $hour = idate('H', strtotime(date('Y-m-d H:i:s', $timestamp)));
                $week = idate('w', strtotime(date('Y-m-d H:i:s', $timestamp)));
                date_default_timezone_set("UTC"); //set timezone back to UTC
                //$log->logInfo(sprintf("%s:date time_d %s %d m %d h %d w %d\n", $LOOPNAME,$time[$i - 1], $timestamp, $month, $hour, $week) );


                if ($month >= $Summer_Start && $month < $Summer_End && $week < 6 && $week > 0 && $hour >= $Peak_Time_Summer_Start && $hour < $Peak_Time_Summer_Stop)
                {
                    $timeIsPeak = TRUE;
                    $sql_peak_kWh = "UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                } else if (($month < $Summer_Start || $month >= $Summer_End) && $week < 6 && $week > 0 && $hour >= $Peak_Time_Non_Summer_Start && $hour < $Peak_Time_Non_Summer_Stop)
                {
                    $timeIsPeak = TRUE;
                    $sql_peak_kWh = "UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                } else
                {
                    $timeIsPeak = FALSE;
                    $sql_peak_kWh = "UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
                }


                if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0))
                {
                    $peak_query = mysql_query($sql_peak_kWh);

                    if (!$peak_query)
                    {
                        //echo "$devicetablename $Power_time : $Power not updated kWh"."</br>";
                        $log->logInfo(sprintf("%s: Not Updated kWh %s %f\n", $LOOPNAME, $Power_time, $Power));
                    } else
                    {
                        //echo "$devicetablename $Power_time : $Power updated kWh"."</br>";
                        //$log->logInfo(sprintf("%s: kWh %s %f\n", $LOOPNAME, $Power_time, $Power) );
                    }
                }

                $interval = strtotime($time[$i]) - strtotime($time[$i + 1]);
                //$log->logInfo(sprintf("%s: calculated interval %d\n", $LOOPNAME, $interval) );

                if ($log_interval == 300) // 5 minute log
                {
                    if ($i < ($countd - 6)) //array bounds check
                    {
                        $kVAR_Sum = $kVAR[$i + 1] + $kVAR[$i + 2] + $kVAR[$i + 3] + $kVAR[$i + 4] + $kVAR[$i + 5] + $kVAR[$i + 6];
                        $kVAR_Avg = $kVAR_Sum / 6;
                        $kVAR_time = $time[$i];

                        $demand_30 = abs($kWh[$i] - $kWh[$i + 6]) * 2;
                        $demand_30_time = $time[$i];
                        if ((strtotime($time[$i]) - strtotime($time[$i + 6]) != 1800 ) || ($demand_30 > MAX_DEMAND ) || ($demand_30 < 0)) //1800sec = 30 minutes
                        {
                            $demand_30 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_30_time, $log_interval, $Power, $pulse_meter, $pulse_demand_str);
                            $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n", $demand_30_time, $log_interval, $demand_30, (strtotime($time[$i]) - strtotime($time[$i + 6]))));
                        }
                        //$log->logInfo(sprintf("%s: 30 min demand %s %f\n", $LOOPNAME, $demand_30_time, $demand_30));
                    }
                } else
                {
                    $kVAR_Sum = '';
                    $demand_30_time = $time[$i];
                    $demand_30 = fixTimeGap($LOOPNAME, $log, $devicetablename, $demand_30_time, $log_interval, $Power, $pulse_meter, $pulse_demand_str);
                    $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_30));
                }

                if (!empty($kVAR_Sum))
                {
                    $sql_kVAR = "UPDATE `$db`.`$devicetablename` SET `$kVAR_30`='$kVAR_Avg' WHERE `$devicetablename`.`time`='$kVAR_time';";

                    if ($kVAR30_value[$i] == 0)
                    {
                        $sql_kVAR_demand = mysql_query($sql_kVAR);

                        if (!$sql_kVAR_demand)
                        {
                            $log->logInfo(sprintf("%s:utilityCost [%s]\n", $LOOPNAME, $sql_kVAR));
                            $log->logInfo(sprintf("%s:utilityCost 30 min reactive kvar not updated\n", $LOOPNAME));
                        } else
                        {
                            //$log->logInfo(sprintf("%s:utilityCost 30 min reactive kvar updated\n", $LOOPNAME));
                        }
                    }
                }

                if (!empty($demand_30))
                {
                    if ($timeIsPeak)
                    {
                        $sql_peak_kW = "UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
                    } else
                    {
                        $sql_peak_kW = "UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
                    }

                    if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0))
                    {
                        $kW_peak_query = mysql_query($sql_peak_kW);

                        if (!$kW_peak_query)
                        {
                            $log->logInfo(sprintf("%s:utilityCost [%s]\n", $LOOPNAME, $kW_peak_query));
                            $log->logInfo(sprintf("%s:DEMAND30 not updated\n", $LOOPNAME));
                        } else
                        {
                            // $log->logInfo(sprintf("%s:DEMAND30 updated kW\n", $LOOPNAME));
                        }
                    } else
                    {
                        $log->logInfo(sprintf("%s: %s Already Updated Peak kW %f Off %f\n", $LOOPNAME, $demand_30_time, $O_kW_value[$i], $OP_kW_value[$i] ));
                        break;
                    }
                }
            }
        }
        unset($time, $kWh, $O_kWh_value, $OP_kWh_value, $O_kW_value, $O_kW_value, $kVAR, $kVAR30_value); //clear arrays as precaution
    }//end va rates

    if($utility=="Virginia_Electric_and_Power_Co") {
    $cost=mysql_fetch_array($rate_q);

    if (!$cost) {
      $log->logInfo(sprintf("%s:util fetch array sql error",$LOOPNAME));
    }

    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $utility));

    date_default_timezone_set($timezone); //set timezone to ship timezone for conversion

    $Summer_Start = idate('m',strtotime($cost['Summer_Start']));
    $Summer_End = idate('m',strtotime($cost['Summer_End']));
    $Peak_Time_Summer_Start = idate('H',strtotime($cost['Peak_Time_Summer_Start']));
    $Peak_Time_Summer_Stop = idate('H',strtotime($cost['Peak_Time_Summer_Stop']));
    $Peak_Time_Non_Summer_Start_AM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Start_AM']));
    $Peak_Time_Non_Summer_Stop_AM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Stop_AM']));
    $Peak_Time_Non_Summer_Start_PM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Start_PM']));
    $Peak_Time_Non_Summer_Stop_PM = idate('H',strtotime($cost['Peak_Time_Non_Summer_Stop_PM']));
    date_default_timezone_set("UTC"); //go back to UTC time

    $db = 'bwolff_eqoff';
    $EC_kWh='Energy_Consumption';
    $Peak_kW="Peak_kW";
    $Peak_kWh="Peak_kWh";
    $Off_Peak_kW="Off_Peak_kW";
    $Off_Peak_kWh="Off_Peak_kWh";

    $log->logInfo(sprintf("%s: check for last update entry \n", $LOOPNAME ));
    $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));
    $someTimeAgo = lastUpdatedEntry($LOOPNAME, $log, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $lastDataPoint);
    $log->logInfo(sprintf("%s: someTimeAgo is: %s\n", $LOOPNAME, $someTimeAgo));
    $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo, $NowPlusDay);

    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update));

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)
    {
      $log->logInfo(sprintf("%s:Utility SQL select failure \n", $LOOPNAME));
      MySqlFailure("invalid msql_query");
      return;
    }

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining) {  $log->logInfo(sprintf("%s:Utility SQL rows remaining failure \n", $LOOPNAME));}

    while($row=mysql_fetch_array($RESULT_update))    	// changed to assoc array $row=mysql_fetch_array($RESULT_update)
    {
      if (isset($row['time']))
      {
        $time[] = $row['time'];
      }

      if (isset($row["$EC_kWh"]))
      {
        $kWh[] = $row["$EC_kWh"];
      }

      $O_kWh_value[] = $row["$Peak_kWh"];
      $OP_kWh_value[] = $row["$Off_Peak_kWh"];
      $O_kW_value[] = $row["$Peak_kW"];
      $OP_kW_value[] = $row["$Off_Peak_kW"];
    }

    $i=-1;
    $countd = 0;
    if (isset($time))
      $countd=count($time);

    $log->logInfo(sprintf("%s:Loop for %d\n",$LOOPNAME,$countd));
    while ($i<=$countd-15) //array bounds check
    {
      $i++;

      if($kWh[$i] != 0 && $kWh[$i]>$kWh[$i+1])
      {

        $Power=abs($kWh[$i] - $kWh[$i+1]);
        $Power_time=$time[$i];

        date_default_timezone_set($timezone); //set timezone to ship timezone for conversion
        $timestamp = strtotime($time[$i].' UTC');
        // set up the month hour and week for the correct timezone
        $month = idate('m',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $hour = idate('H',strtotime(date('Y-m-d H:i:s', $timestamp)));
        $week = idate('w',strtotime(date('Y-m-d H:i:s', $timestamp)));
        date_default_timezone_set("UTC"); //set timezone back to UTC

        //$log->logInfo(sprintf("%s:date time_d %s %d m %d h %d w %d\n", $loopname,$time[$i - 1], $timestamp, $month, $hour, $week) );

        $IS_SUMMER_MONTH = ($month>=$Summer_Start && $month<$Summer_End);
        $IS_PEAK_SUMMER_HOUR = ($hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop);
        $IS_NON_SUMMER_MONTH = ($month<$Summer_Start || $month>=$Summer_End);
        $IS_PEAK_NON_SUMMER_HOUR_AM = ($hour>=$Peak_Time_Non_Summer_Start_AM && $hour<$Peak_Time_Non_Summer_Stop_AM);
        $IS_PEAK_NON_SUMMER_HOUR_PM = ($hour>=$Peak_Time_Non_Summer_Start_PM && $hour<$Peak_Time_Non_Summer_Stop_PM);
        $IS_WEEKDAY = ($week<6 && $week>0);

        if($IS_SUMMER_MONTH && $IS_PEAK_SUMMER_HOUR && $IS_WEEKDAY) {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        } else if($IS_NON_SUMMER_MONTH && $IS_WEEKDAY && ($IS_PEAK_NON_SUMMER_HOUR_AM || $IS_PEAK_NON_SUMMER_HOUR_PM)) {
          $timeIsPeak = TRUE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        } else {
          $timeIsPeak = FALSE;
          $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";
        }


        if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0)) {
          $peak_query=mysql_query($sql_peak_kWh);

          if(!$peak_query) {
            $log->logInfo(sprintf("%s: Not Updated kWh %s %f\n", $LOOPNAME, $Power_time, $Power) );
          } else {
            $log->logInfo(sprintf("%s: kWh %s %f\n", $LOOPNAME, $Power_time, $Power) );
          }
        } else {
          $log->logInfo(sprintf("%s: kWh", $LOOPNAME));
        }

        $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
        //$log->logInfo(sprintf("%s: calculated interval %d\n", $loopname, $interval) );
        // 5 minute log
        if ($log_interval == 300) {
          //array bounds check
          if($i<($countd-6)) {
            $demand_30 = abs($kWh[$i] - $kWh[$i+6])*2;
            $demand_30_time = $time[$i];
            //1800sec = 30 minutes
            if ((strtotime($time[$i]) - strtotime($time[$i+6]) != 1800 ) || ($demand_30 > MAX_DEMAND ) || ($demand_30 < 0)) {
              $demand_30 = fixTimeGap($LOOPNAME, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_30_time, $log_interval, $demand_30,(strtotime($time[$i]) - strtotime($time[$i+6]))));
            }
            $log->logInfo(sprintf("%s: 30 min demand %s %f\n", $LOOPNAME, $demand_30_time, $demand_30) );
          }
        } else {
          $demand_30_time = $time[$i];
          $demand_30 = fixTimeGap($LOOPNAME, $devicetablename, $demand_30_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
          $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_30));
        }

        if(!empty($demand_30)) {
          if ($timeIsPeak) {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          } else {
            $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_30' WHERE `$devicetablename`.`time`='$demand_30_time';";
          }

          if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0)) {
            $kW_peak_query=mysql_query($sql_peak_kW);

            if(!$kW_peak_query) {
              $log->logInfo(sprintf("%s:utilityCost [%s]\n", $LOOPNAME, $kW_peak_query) );
              $log->logInfo(sprintf("%s:DEMAND30 not updated\n", $LOOPNAME) );
            } else {
              $log->logInfo(sprintf("%s: 30 min demand updated", $LOOPNAME));
            }
          } else {
              $log->logInfo(sprintf("%s: 30 min demand already updated", $LOOPNAME));
            break;
          }
        }
      }
    }
    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value, $kVAR, $kVAR30_value);//clear arrays as precaution
  }

  if($utility=="Entergy_NO_Rates") {
    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $utility));

    $db = 'bwolff_eqoff';
    $EC_kWh='Energy_Consumption';
    $Peak_kW="Peak_kW";
    $Peak_kWh="Peak_kWh";
    $Off_Peak_kW="Off_Peak_kW";
    $Off_Peak_kWh="Off_Peak_kWh";

    $log->logInfo(sprintf("%s: check for last update entry \n", $LOOPNAME ));
    $NowPlusDay = date('Y-m-d H:i:s',strtotime('+1 day'));
    $someTimeAgo = lastUpdatedEntry($LOOPNAME, $log, $devicetablename, $Peak_kWh, $Off_Peak_kWh, $Peak_kW, $Off_Peak_kW, $lastDataPoint);
    $log->logInfo(sprintf("%s: someTimeAgo is: %s\n", $LOOPNAME, $someTimeAgo));
    $sql_update = sprintf("SELECT time, %s, %s, %s, %s, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $EC_kWh, $Peak_kWh, $Off_Peak_kWh,$Peak_kW, $Off_Peak_kW, $devicetablename, $someTimeAgo, $NowPlusDay);

    $log->logInfo(sprintf("%s: %s\n", $LOOPNAME, $sql_update));

    $RESULT_update = mysql_query($sql_update);
    if (!$RESULT_update)
    {
      $log->logInfo(sprintf("%s:Utility SQL select failure \n", $LOOPNAME));
      MySqlFailure("invalid msql_query");
      return;
    }

    $rows_remaining = mysql_num_rows($RESULT_update);
    if (!$rows_remaining) {  $log->logInfo(sprintf("%s:Utility SQL rows remaining failure \n", $LOOPNAME));}

    while($row=mysql_fetch_array($RESULT_update))    	// changed to assoc array $row=mysql_fetch_array($RESULT_update)
    {
      if (isset($row['time']))
      {
        $time[] = $row['time'];
      }

      if (isset($row["$EC_kWh"]))
      {
        $kWh[] = $row["$EC_kWh"];
      }

      $O_kWh_value[] = $row["$Peak_kWh"];
      $OP_kWh_value[] = $row["$Off_Peak_kWh"];
      $O_kW_value[] = $row["$Peak_kW"];
      $OP_kW_value[] = $row["$Off_Peak_kW"];
    }

    $i=-1;
    $countd = 0;
    if (isset($time))
      $countd=count($time);

    $log->logInfo(sprintf("%s:Loop for %d\n",$LOOPNAME,$countd));
    while ($i<=$countd-15) //array bounds check
    {
      $i++;

      if($kWh[$i] != 0 && $kWh[$i]>$kWh[$i+1])
      {

        $Power=abs($kWh[$i] - $kWh[$i+1]);
        $Power_time=$time[$i];

        // Utility doesn't have an On / Off Peak Demand Schedule so we put all values in Off_Peak_*
        $sql_peak_kWh="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kWh`='$Power' WHERE `$devicetablename`.`time`='$Power_time';";

        if (($O_kWh_value[$i] == 0) && ($OP_kWh_value[$i] == 0)) {
          $peak_query=mysql_query($sql_peak_kWh);

          if(!$peak_query) {
            $log->logInfo(sprintf("%s: Not Updated kWh %s %f\n", $LOOPNAME, $Power_time, $Power) );
          } else {
            $log->logInfo(sprintf("%s: kWh %s %f\n", $LOOPNAME, $Power_time, $Power) );
          }
        } else {
          $log->logInfo(sprintf("%s: kWh", $LOOPNAME));
        }

        $interval=strtotime($time[$i]) - strtotime($time[$i+1]);
        //$log->logInfo(sprintf("%s: calculated interval %d\n", $loopname, $interval) );
        // 5 minute log
        if ($log_interval == 300) {
          //array bounds check
          if($i<($countd-3)) {
            $demand_15 = abs($kWh[$i] - $kWh[$i + 3]) * 4;
            $demand_15_time = $time[$i];
            //1800sec = 30 minutes
            if ((strtotime($time[$i]) - strtotime($time[$i+3]) != 900 ) || ($demand_15 > MAX_DEMAND ) || ($demand_15 < 0)) {
              $demand_15 = fixTimeGap($LOOPNAME, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
              $log->logInfo(sprintf("Fix time Gap %s int %d demand %f time diff %f\n",$demand_15_time, $log_interval, $demand_15,(strtotime($time[$i]) - strtotime($time[$i+3]))));
            }
            $log->logInfo(sprintf("%s: 15 min demand %s %f\n", $LOOPNAME, $demand_15_time, $demand_15) );
          }
        } else {
          $demand_15_time = $time[$i];
          $demand_15 = fixTimeGap($LOOPNAME, $devicetablename, $demand_15_time, $log_interval,$Power, $pulse_meter,$pulse_demand_str);
          $log->logInfo(sprintf("Fix time Gap int %d demand %f \n", $log_interval, $demand_15));
        }

        if(!empty($demand_15)) {
          // Utility doesn't have an On / Off Peak Demand Schedule so we put all values in Off_Peak_*
          $sql_peak_kW="UPDATE `$db`.`$devicetablename` SET `$Off_Peak_kW`='$demand_15' WHERE `$devicetablename`.`time`='$demand_15_time';";

          if (($O_kW_value[$i] == 0) && ($OP_kW_value[$i] == 0)) {
            $kW_peak_query=mysql_query($sql_peak_kW);

            if(!$kW_peak_query) {
              $log->logInfo(sprintf("%s:utilityCost [%s]\n", $LOOPNAME, $kW_peak_query) );
              $log->logInfo(sprintf("%s:DEMAND15 not updated\n", $LOOPNAME) );
            } else {
              $log->logInfo(sprintf("%s: 15 min demand updated", $LOOPNAME));
            }
          } else {
              $log->logInfo(sprintf("%s: 15 min demand already updated", $LOOPNAME));
            break;
          }
        }
      }
    }
    unset($time,$kWh,$O_kWh_value,$OP_kWh_value,$O_kW_value,$O_kW_value);//clear arrays as precaution
  }

    $log->logInfo(sprintf("%s: Utility_cost END\n", $LOOPNAME) );
}


/*
 * Function debug_log
 */
function debug_log($LOOPNAME)
{
     //---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---


     # Should log to the same directory as this file
    require_once '../erms/includes/KLogger.php';
    //$log = new KLogger ( "log.txt" , KLogger::DEBUG );   // klogger debug everything

    if($LOOPNAME == "Cape_Edmont")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if($LOOPNAME == "Cape_Kennedy")
    {
        $log = new KLogger ( "log.txt", KLogger::DEBUG );
    }
    else if($LOOPNAME == "Flickertail_Power")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if($LOOPNAME == "Cape_Diamond")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if($LOOPNAME == "Regulus")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if($LOOPNAME == "Gopher_State")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if($LOOPNAME == "SS_Altair")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if($LOOPNAME == "SS_BELLATRIX")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if ($LOOPNAME == "Cape_Knox")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if ($LOOPNAME == "Cape_Ducato")
    {
        $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if ($LOOPNAME == "Cape_Douglas")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if ($LOOPNAME == "Cape_Domingo")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
     else if ($LOOPNAME == "Cottage")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
    else if ($LOOPNAME == "Cornhusker")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
     else if ($LOOPNAME == "Cape_Wrath")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF );
    }
     else if ($LOOPNAME == "Cape_Washington")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Cape_Wright")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
   else if ($LOOPNAME == "Pollux")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Cape_Decision")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
     else if ($LOOPNAME == "Denebola")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Antares")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Benevidez")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "KOCAK")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "OBREGON")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "PLESS")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Cape_Ray")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Cape_Rise")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else if ($LOOPNAME == "Cape_Race")
    {
         $log = new KLogger ( "log.txt", KLogger::OFF);
    }
    else
    {
        $log = new KLogger ( "log.txt", KLogger::DEBUG );
    }

   return $log;

   //---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---KLOGGER---

}/** end debug_log ****/



/* ================================================================================================================== */
/*
    BEGIN MAIN SCRIPT HERE !
*/



    ob_start();     // cache header/body information, send nothing to the client yet.
                    // we actually keep cacheing until the end of the script, if you have a large amount of data,
                    // Use ob_end_flush() to flush the output cache and send the headers, however "ReportFailure()"
                    // won't work after ob_end_flush()

include "../conn/mysql_pconnect-all.php"; // mySQL database connector.

   //DEBUG!!!! session_start already called in mysql_pconnect call above
   // session_start(); // used for MySqlFailure function to get any mySQL errors or warnings.

// First check the acquisuite/acquilite credentials.  The AcuqiSuite uses basic http
// authentication, the PHP scripting language extracts the user/password and provides them
// in the variables PHP_AUTH_USER and PHP_AUTH_PW.  Different scripting languages may have
// different methods of providing username/password paris for basic http authentication.
// check these vaules here and proceed/terminate if credentials are good.

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
    	$myserialnbr =  $_REQUEST['SERIALNUMBER'];
   	$sql="SELECT * FROM `Aquisuite_List` WHERE SerialNumber='$myserialnbr'";

	// Mysql_num_row is counting table row
	$result=mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("ConfigDownload Unable to execute SQL query");
	}
	$value = mysql_fetch_array($result);
	 if (!$value)
         {
             MySqlFailure("ConfigDownload Unable to fetch SQL query");
         }
	 else
         {
             $LOOPNAME = $value["loopname"];
         }
    }


    /**** Setup KLogger log file  ******/
    $log = debug_log($LOOPNAME);

    date_default_timezone_set('UTC'); /** set the timezone to UTC for all date and time functions **/


    $log->logInfo( sprintf("%s Mode Type: %s Nbr %s\n", $LOOPNAME, $_REQUEST['MODE'], $_REQUEST['SERIALNUMBER']));

   // $log->logInfo(sprintf("Authenticated SERIALNUMBER: %s \n", $LOOPNAME));

    $aquisuitetable = sprintf("%s_%s", $LOOPNAME, $_REQUEST['SERIALNUMBER']);



    // STATUS MODE
    if ($_REQUEST['MODE'] == "STATUS")
    {
        status_upload($_SERVER, $_REQUEST, $log);

    }

    // LOGFILEUPLOAD MODE

    if ($_REQUEST['MODE'] == "LOGFILEUPLOAD")
    {
        $devicetablename = logfile_upload($LOOPNAME, $aquisuitetable, $log);  //process log data
        utility_cost($LOOPNAME, $aquisuitetable, $devicetablename, $log);  //calculate utility costs
       	printf("</pre>\n");  // end of logging script

       // Try 
       $logger = new Logger($LOOPNAME);
       
       $utility = utility_check($aquisuitetable);
       $logger->logInfo("Get utility  " . $utility);
       $timezone = "EDT";
       try {
           $utilityData = db_fetch_utility_rate($logger, $utility);
           $ship_records = get_ships_records($logger,$timezone,$LOOPNAME,$devicetablename);
           $last_record = db_fetch_last_ship_record($log, $LOOPNAME);
           
           if(!$last_record){
               $last_records = [];
           }else{
               $last_records = get_last_four_records($logger,$timezone,$LOOPNAME );
           }
           $logger->logInfo( "Creating utility class");
           $utilityRate = create_utility_class($logger,$utilityData[0]);

           $logger->logInfo( "Calculating kWh and kW");
           $ship_records = calculate_kw($logger,$utilityRate,$last_records,$ship_records);

           $logger->logInfo( "Calculating cost");
           $ship_records =calculate_cost($logger, $utilityRate, $ship_records);

           $logger->logInfo( "Populating Standard table");
           $erros = populate_standart_table($logger, $ship_records);

           $logger->logInfo( "End  erors: " . $erros );
           } catch (Exception $e) {
           $logger->logError('Excepción capturada: ' . $e->getMessage());
       }
   }
    

// CONFIGFILEMANIFEST MODE
    if ($_REQUEST['MODE'] == "CONFIGFILEMANIFEST")
    {
        check_config_file_manifest($aquisuitetable, $_REQUEST['SERIALNUMBER'],$_REQUEST['MODBUSDEVICE'], $LOOPNAME, $log);

    }

// CONFIGFILEUPLOAD MODE
    if ($_REQUEST['MODE'] == "CONFIGFILEUPLOAD")
    {
        config_file_upload($_SERVER, $_REQUEST, $_FILES, $aquisuitetable, $LOOPNAME, $log);
    }


 if ($_REQUEST['MODE'] == "CONFIGFILEDOWNLOAD")
  {
    	$myserialnbr =  $_REQUEST['SERIALNUMBER'];
   	$sql="SELECT * FROM `Aquisuite_List` WHERE SerialNumber='$myserialnbr'";
	$result=mysql_query($sql);
	if(!$result)
	{
	    MySqlFailure("ConfigDownload Unable to execute SQL query");
	}
	$value = mysql_fetch_array($result);
	 if (!$value)
         {
             MySqlFailure("ConfigDownload Unable to fetch SQL query");
         }
	 else
         {
             $configUploadNewFile = $value['upload_config'];
         }

      if ($configUploadNewFile) // set value to 1 in database to upload new config, mb-000.upload file must be in directory for this to work
      {
          $log->logInfo(sprintf("%s CONFIG File Download\n",$LOOPNAME));
          sendConfigFiletoAS($_SERVER,$_REQUEST,$_SERVER['REMOTE_ADDR'], $_REQUEST['SERIALNUMBER'], $_REQUEST['MODBUSIP'] , $_SERVER['REMOTE_PORT'], $_REQUEST['MODBUSDEVICE'],  $_REQUEST['MODBUSDEVICECLASS']);
      }
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
