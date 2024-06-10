<?php
/*
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
*/
/* -------------------------------------------------------------------------------------------------------------------*/


/*
 * Function check_config_file_manifest     
 */
function check_config_file_manifest($aquisuite_table,$loop_name,$log_debug)   
{
    printf("\n");

    $log_debug->logInfo(sprintf("CONFIGFILEMANIFEST SERIALNUMBER: %s \n", $loop_name));

    // This function sends a list of all current configuration files on the server. 
    // The format is as follows:
    // 
    //            CONFIGFILE,loggerconfig.ini,md5checksum,timestamp
    //            CONFIGFILE,modbus/mb-001.ini,md5checksum,timestamp
    //            CONFIGFILE,modbus/mb-007.ini,md5checksum,timestamp
    //                          
    // the timestamp is in UTC time, SQL format, ie "YYYY-MM-DD HH:MM:SS" (use blank if not present/first-time)
    // The md5checksum is the md5 checksum of the config file. (use blank if not present/first-time)
    //     
    // Checksums and timestamps are stored in a database, file data can be stored in the same table as a blob record.
    // When a config file is received from the AcquiSuite, the server should verify the checksum, save the config file
    // and also save the timestamp and checksum for the file for future reference.  
    //      
    // The AcquiSuite will process this response, and only exchange files listed in the manifest response.
    //    
    // If the config file checksum sent in the response does not match the AcquiSuite config file checksum, the        
    // AcquiSuite will process the exchange.  If "remote configuration" is enabled, the AcquiSuite will check the 
    // timestamp to find if the server version of the config file is newer than the AcquiSuite version .if so the 
    // AcquiSuite will request to download the configuration file from the server. 
    //      
    // If the checksum values do not match and the AcquiSuite file timestamp is newer, or if the server timestamp is blank, 
    // or if the "remote configuration" option is not enabled, the AcquiSuite will send the configuration file to 
    // the server.
    //
    // Note, in the example below, the md5checksum is shown as "X" for simplicity, replace this with the actual checksum

    printf("CONFIGFILE,loggerconfig.ini,X,0000-00-00 00:00:00\n");  // print this line to always upload the Aquisuite Configuration file for most current information.
    //
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
}

//End check_config_file_manifest



/*
 * Function config_file_upload     
 */
function config_file_upload($server_info, $request_info, $file_info, $aquisuite_table, $loop_name, $log_debug)
{

    $log_debug->logInfo(sprintf("CONFIGFILEUPLOAD SERIALNUMBER: %s \n", $loop_name));
    // calculate an MD5 checksum for the file that was uploaded.
    // On the AcquiSuite, the checksum form variable is calculated from the log file after the file has been gziped.
    // On the AcquiLite, the log file is not zipped, so the checksum is calculated on the raw log file.
    // In both cases, the checksum is that of the file that the DAS uploaded to your server.
    // the md5_file() function calculates the md5 checksum of the temp file that PHP stored the log data in when
    // it was decoded from the HTTP/POST.


    $szChecksum = "[error]";                           // provide some default value for this in case the following fails.
    if (file_exists($file_info['CONFIGFILE']['tmp_name']))              // make sure the file exists before we start.
    {
        $szChecksum = md5_file($file_info['CONFIGFILE']['tmp_name']);   // built in php function that calculates the md5 checksum of a file.
    }

    // This section prints all the expected form variables.  This serves no real purpose other
    // than to demonstrate the variables.  Enable debugging on the AcquiSuite or AcquiLite to
    // allow these to show up in the log file for your review.

    printf("Config file upload from ip  %s \n", $server_info['REMOTE_ADDR']);               // print the IP address of the DAS client.  
    printf("Got SENDDATATRACE:          %s \n", $request_info['SENDDATATRACE']);             // set when the AcquiSuite requests full session debug messages
    printf("Got MODE:                   %s \n", $request_info['MODE']);                      // this shows what type of exchange we are processing.
    printf("Got SERIALNUMBER:           %s \n", $request_info['SERIALNUMBER']);              // The acquisuite serial number
    printf("Got PASSWORD:               %s \n", $request_info['PASSWORD']);                  // The acquisuite password
    printf("Got LOOPNAME:               %s \n", $request_info['LOOPNAME']);                // The name of the AcquiSuite (modbus loop name)
    printf("Got MODBUSIP:               %s \n", $request_info['MODBUSIP']);                  // Currently, always 127.0.0.1, may change in future products.
    printf("Got MODBUSPORT:             %s \n", $request_info['MODBUSPORT']);              // currently, always 502, may change in future products.    
    printf("Got MODBUSDEVICECLASS:      %s \n", $request_info['MODBUSDEVICECLASS']);        // a unique id number for the modbus device type.
    printf("Got MD5CHECKSUM:            %s \n", $request_info['MD5CHECKSUM']);              // the MD5 checksum the AcquiSuite generated prior to upload
    printf("calculated checksum:        %s \n", $szChecksum);                           // the MD5 sum we calculated on the file we received.
    printf("Got FILETIME:               %s \n", $request_info['FILETIME']);                // the date and time the file was last modified. (in UTC time).
    printf("Got FILESIZE:               %s \n", $request_info['FILESIZE']);                 // the original size of the log file on the AcquiSuite flash disk prior to upload

    printf("calculated filesize:        %s \n", filesize($file_info['CONFIGFILE']['tmp_name']));   // the calculated file size of the file we received..
    printf("Got CONFIGFILE orig name:   %s \n", $file_info['CONFIGFILE']['name']);             // This is original file name on the AcquiSuite flash disk.
    printf("Got CONFIGFILE tmp name:    %s \n", $file_info['CONFIGFILE']['tmp_name']);         // This is the PHP temp file name where PHP stored the file contents.
    printf("Got CONFIGFILE size:        %s \n", $file_info['CONFIGFILE']['size']);            // What PHP claims the temp file size is
    printf("\n");


    // now we should check the log file checksum to verify it is correct.
    // if not, something got corrupted.  refuse the file and the DAS will upload it again later.

    if ($szChecksum != $request_info['MD5CHECKSUM'])
    {
        ReportFailure("The checksum of received file does not match the checksum form variable sent by the DAS.\n");
        $log_debug->logError(sprintf("The checksum of received file does not match the checksum form variable sent by the DAS : %s\n", $loop_name));
        exit;
    }

    // The MODBUSDEVICECLASS is a unique id that allows the AcquiSuite to decide what class of device
    // it is working with.  It is assumed that the number and type of points stored for a specific
    // device type are the same.  For example, the Veris 8036 power meter has several versions
    // for 100, 300, 800Amp, however the list of points are the same for all devices.  The deviceclass
    // will be the same for all flavors of Veris 8036 meters.
    // A complete list of deviceclass values are listed in the pushupload/tablestructures directory
    // in the readme.txt file of this zip archive.   Also provided is the table structure for
    // each listed device class.
    // For example, the deviceclass may be one of the following:
    //       MBCLASS_UNKNOWN     0
    //       MBCLASS_H8036       2           (26 data columns)
    //       MBCLASS_M4A4P2      9           (32 data columns, A8923 enhanced io module, A8811 built-in io module)
    // check here to verify the modbus device name, type, and class make sense based on previous uploads.
    // You should use this information to ensure the data is stored in a table with the correct number of columns.
    // Next open the file handle to the uploaded ini file that came from the DAS.
    // the bulk of the work here is the PHP function $_FILES which provides an array of all files embedded in the
    // mime data sent to the server in the HTTP/POST.  You may read from any one file by requesting the file by name
    // in the index.  ie, a file attached as "CONFIGFILE" is referred to by the array element $_FILES['CONFIGFILE'].
    // the element is actually a second array with elements for "name", "tmp_name" and "size".  The tmp_name element
    // provides the file name that the PHP engine used to store the contents of the file.  To access the file data,
    // simply open the file with the name provided by the tmp_name element, and read the data.

    $fd = fopen($file_info['CONFIGFILE']['tmp_name'], "r");
    if (!$fd)
    {
        ReportFailure("open failed to open configfile " . ($file_info['CONFIGFILE']['tmp_name']));
        $log_debug->logError(sprintf("open failed to open configfile " . ($file_info['CONFIGFILE']['tmp_name']) . ": %s\n", $loop_name));
        exit;
    }

    // create a log file on the server.  for ease of permissions sake, we create this in the /tmp directory.
    // note that the file is created with the permissions of the webserver.
    // the file is in /tmp/xxxx/mb-yyy.ini
    // where xxx is the serial number of the AcquiSuite or AcquiLite
    // and yyy is the modbus address number of the device.
    // DANGER!.  it is a really bad idea to create a file with unfiltered input from a webserver post.
    // this is why we generate a file name based on some other parameters.
    // this function is only safe if you validate the SERIALNUMBER field with known valid values from your database.
    // the modbus device is relatively safe as it is reformated as a number


    $szTargetDirectory = sprintf("%s_%s", $loop_name, $request_info['SERIALNUMBER']);   // FIX THIS (clean up serial number, or validate it)

    $szTargetFilename = sprintf("%s/mb-%03d.ini", $szTargetDirectory, $request_info['MODBUSDEVICE']);


    if (!file_exists($szTargetDirectory))            // if the directory does not exist, create it.
    {
        $nResult = mkdir($szTargetDirectory, 0700);    // create directory (unix permissions 0700 are for directory owner access only)
        if (!$nResult)                                // trap directory create errors.
        {
            ReportFailure("Error creating directory " . $szTargetDirectory);
            $log_debug->logError(sprintf("Error creating directory " . $szTargetDirectory . ": %s\n", $loop_name));
            exit;
        }
    }


    $fOut = fopen($szTargetFilename, w);           // create/open target file for writing
    if (!$fOut)                                   // trap file create errors.
    {
        ReportFailure("Error creating file " . $szTargetFilename);
        $log_debug->logError(sprintf("Error creating file " . $szTargetFilename . ": %s\n", $loop_name));
        exit;
    }

    printf("Saving data to file %s\n", $szTargetFilename);  // be nice and print out the target file location.
    $log_debug->logInfo(sprintf("ConfigfileUpload: Saving data to file " . $szTargetFilename . ": %s\n", $loop_name));


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
        $sql = "SELECT * FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable' AND timezoneaquisuite='$timezone'";
        $result = mysql_query($sql);
        $row_count = mysql_num_rows($result);
        if (!$row_count == 1)
        {
            $sql = "UPDATE `Aquisuite_List` SET timezoneaquisuite='$timezone' WHERE aquisuitetablename='$aquisuitetable'";
            $result = mysql_query($sql);
            if (!$result)
            {
                MySqlFailure("unable to update Aquisuite_List with timezone $timezone");
            }
        }

        $sql = "UPDATE `Aquisuite_List` SET configuration='$szTargetFilename', configurationchangetime='" . date('Y-m-d H:i:s', time()) . "', configurationchecksum='" . md5($szTargetFilename) . "' WHERE SerialNumber='$request_info[SERIALNUMBER]'";
        $result = mysql_query($sql);
        if (!$result)
        {
            MySqlFailure("unable to select data from Aquisuite_List");
        }
    } else
    {
        updatedevicetable($aquisuitetable, $Point_Names, $Point_Name_Default, $Point_Units, $szTargetFilename);  // creates device table is not already created and updates all device information.
    }
    fclose($fOut);  // close the target file
    fclose($fd);   // close the source uploaded file
    // PHP automatically removes temp files when the script exits.


    ob_end_flush();   // send any cached stuff, and stop caching. This may only be done after the last point where you might call ReportFailure()

    printf("\nSUCCESS\n");   // this line is what the AcquiSuite/AcquiLite searches for in the response to
    // tell it that the data was received and the original log file may be deleted.

    printf("</pre>\n");  // end of the script
}

?>
