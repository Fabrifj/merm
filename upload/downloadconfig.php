<?php
/*
 *  FIle: downloadconfig.php
 *  Author: Carole Snow
 * 
 * Desc: This file contails functions to process relay output on Acquisuite Modules
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/



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

    $szTargetDirectory = sprintf("%s_%s", $LOOPNAME, $SERIALNUMBER); 
    $szTargetFilename = sprintf("%s/mb-%03d.upload", $szTargetDirectory, $MODBUSDEVICE);
    
    if (file_exists($szTargetFilename))              // make sure the file exists before we start.
    {
        $log->LogInfo(sprintf("%s: file %s", $LOOPNAME, $szTargetFilename));
  
    }
    else
    {    
      $log->LogInfo(sprintf("%s: File %s does not exist ", $LOOPNAME, $szTargetFilename));
    }  
    $fd = fopen($szTargetFilename, 'r');           // create/open target file for writing
    if (!$fd)                                       // trap file create errors.
    {    
      $log->logInfo(sprintf("%s:logfile error reading file %s \n", $LOOPNAME, $szTargetFilename) ); 
      ReportFailure("Error reading file " . $szTargetFilename);
    }  
    
    while (!feof($fd))                              // loop through the config file until we reach the end of the file.
        {
            $szBuffer = fgets($fd, 512);               // read lines from the config file.  make sure lines don't exceed 512 bytes
             printf( $szBuffer);         // write data to the log file.
       }

    ob_end_flush(); // send cached stuff, and stop caching. we've already kicked out the last header line.

     $log->logInfo(sprintf("ConfigfileUpload: Saving data to file " . $szTargetFilename . ": %s\n", $LOOPNAME));
    
}    
   
?>
