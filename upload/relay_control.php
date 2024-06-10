<?php
/*
 *  FIle: Relay_Control.php
 *  Author: Carole Snow
 * 
 * Desc: This file contails functions to process relay output on Acquisuite Modules
 * Copyright © 2014, Carole Snow. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
*/

/*----------------------------------------------------------------------------------------------------*/
/* Get outputRelay boolean variable for ship and return value
 *     true if ship uses output relays
*/
function usesRelays($aquisuitetable, $log)
{
    //$sql_device = "SELECT relaysAvail FROM $aquisuitetable WHERE devicetablename='$devicetablename'";
    $sql = "SELECT relaysAvail FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $result = mysql_query($sql);
    if(!$result)
    {
       MySqlFailure("Unable to select relaysAvail from $aquisuitetable");
       return FALSE; 
    }
    
    $device_row_count = mysql_num_rows($result);
    if($device_row_count!=1)
    {	
      return FALSE; 
    }

   $row = mysql_fetch_row($result);
   if (!$row)        
      return FALSE;
   $log->logInfo(sprintf("Ship uses Relays is %d\n", $row[0]));

    return ($row[0]);
}


/*----------------------------------------------------------------------------------------------------*/
/* Get current relay state boolean variable for ship and return value
 *     true if ship output relay on modbusdevice 250 is set to on
*/
function getRelayState($aquisuitetable, $log)
{
    $sql = "SELECT devicetablename FROM $aquisuitetable WHERE function != 'main_utility'"; //device 250 is not main utility
    $result = mysql_query($sql);
    $log->logInfo(sprintf("getRelayState: %s \n",$sql)); 
    if(!$result)
    {
         $log->logInfo(sprintf("Unable to select devicetablename from %s\n",$aquisuitetable));       
         MySqlFailure("unable to select devicetable from $devicetablename");
         return false;
     } 
    $row = mysql_fetch_row($result);
    $devicetablename = $row[0];
    
    $sql = sprintf("SELECT Output_01 FROM $devicetablename ORDER BY time DESC LIMIT 1");

    $result = mysql_query($sql);
    if(!$result)
    {
      $log->logInfo(sprintf("Unable to select Output_01 from %s\n",$devicetablename));       
      MySqlFailure("unable to select Output_01 from $devicetablename");
      return false;
    }
 
    $row = mysql_fetch_row($result);
    $log->logInfo(sprintf("getRelayState: %s value %d\n",$sql, $row[0])); 
     
    return ($row[0]);
}


/*----------------------------------------------------------------------------------------------------*/
/* Set time relay was turned on for ship 
*/
function setRelayOnTime($aquisuitetable, $log)
{
    $sql = "UPDATE `Aquisuite_List` SET timeRelayOn='" . gmdate('Y-m-d H:i:s') . "' WHERE aquisuitetablename='$aquisuitetable'";
    $log->LogInfo(sprintf("setRelayOn  %s\n", $sql));
    $result = mysql_query($sql);
    if (!$result)
    {
        MySqlFailure("unable to update relayTimeOn from Aquisuite_List");
    }    
   $log->LogInfo(sprintf("TURNONOFF  %s ON at %s\n", $aquisuitetable, gmdate('Y-m-d H:i:s')));
}


/*----------------------------------------------------------------------------------------------------*/
/* Set time relay was turned off for ship 
*/
function setRelayOffTime($aquisuitetable, $log)
{
    $sql = "UPDATE `Aquisuite_List` SET timeRelayOff='" . gmdate('Y-m-d H:i:s') . "' WHERE aquisuitetablename='$aquisuitetable'";
    $log->LogInfo(sprintf("setRelayOff  %\n", $sql));
    $result = mysql_query($sql);
    if (!$result)
    {
        MySqlFailure("unable to update relayTimeOff from Aquisuite_List");
    }  
   $log->LogInfo(sprintf("TURNONOFF  %s OFF at %s\n", $aquisuitetable, gmdate('Y-m-d H:i:s')));
}

/*----------------------------------------------------------------------------------------------------*/
/* Get time relay was turned on for ship and return value
*/
function getRelayOnTime($aquisuitetable, $log)
{

    $sql_device = "SELECT timeRelayOn FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $log->logInfo(sprintf("getRelayTimeOn %s\n",$sql_device));
    $result = mysql_query($sql_device);
    if(!$result)
    {
       $log->logInfo(sprintf("Error in getRelayTimeOn\n"));
        MySqlFailure("Unable to select timeRelayOn from $aquisuitetable");
    }

    $device_row_count = mysql_num_rows($result);

    if($device_row_count==1)
    {	
        $row = mysql_fetch_row($result);
        $log->logInfo(sprintf("GetRelayTimeOn %s\n", $row[0]));
        return ($row[0]);
    }
    else
    {
        return false;
    }
}


/*----------------------------------------------------------------------------------------------------*/
/* Get time relay was turned off for ship and return value
*/
function getRelayOffTime($aquisuitetable, $log)
{
    $sql_device = "SELECT timeRelayOff FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $log->logInfo(sprintf("getRelayTimeOff %s\n",$sql_device));  
    $result = mysql_query($sql_device);
    if(!$result)
    {
       $log->logInfo(sprintf("Error in gerRelayTimeOff\n"));
        MySqlFailure("Unable to select timeRelayOff from $aquisuitetable");
    }

    $device_row_count = mysql_num_rows($result);

    if($device_row_count==1)
    {	
        $row = mysql_fetch_row($result);
        $log->logInfo(sprintf("GetRelayTimeOff %s\n", $row[0]));
        return ($row[0]);
    }
    else
    {
        return false;
    }
}

/*----------------------------------------------------------------------------------------------------*/
/*  Get utility function of this device on Acquisuite
 *        return true if this device is main utility
*/
function getUtilityFunction($aquisuitetable, $devicetablename, $log)
{
    $sql = "SELECT function FROM $aquisuitetable WHERE devicetablename = '$devicetablename'"; 
    $result = mysql_query($sql);
    $log->logInfo(sprintf("getUtilityFunction %s \n",$sql)); 
    if(!$result)
    {
         $log->logInfo(sprintf("Unable to select function from %s\n",$aquisuitetable));       
         MySqlFailure("unable to select function from $aquisuitetable");
         return false;
     } 
    $row = mysql_fetch_row($result);
    $devFunction = $row[0];
    $log->logInfo(sprintf("Main Util %s \n",$devFunction)); 
    if ($devFunction != 'main_utility')
    {
        return false;
    }    
    return true;    
}


/*----------------------------------------------------------------------------------------------------*/
/*	Get the IP Address of the Acquisuite
*/
function myIP($aquisuitetable)
{
    $sql = "SELECT asIPAddress FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $result = mysql_query($sql);
    if(!$result)
    {
        MySqlFailure("unable to select IP address from aquisuite_list");
    }
     $row = mysql_fetch_row($result);
     return ($row[0]);
          
}


/*----------------------------------------------------------------------------------------------------*/
/*	Get the user login information of the Acquisuite
*/
function myUserInfo($aquisuitetable)
{
    $sql = "SELECT asUserName FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $result = mysql_query($sql);
    if(!$result)
    {
        MySqlFailure("unable to select user from aquisuite_list");
    }
     $user_row = mysql_fetch_row($result);
      
    $sql = "SELECT asPW FROM `Aquisuite_List` WHERE aquisuitetablename='$aquisuitetable'";
    $result = mysql_query($sql);
    if(!$result)
    {
        MySqlFailure("unable to select pass from aquisuite_list");
    }
     $pass_row = mysql_fetch_row($result);
    
     return array(
        'user' => $user_row[0],
        'pass' => $pass_row[0] );
          
}

/*-------------------------------------------------------------------------------------------------------------------*/
/* HTTP POST Request
*/
function post_request($host, $path, $user, $pass, $data, $log)       
{
    // Convert the data array into URL Parameters like a=b&foo=bar etc.
    $data = http_build_query($data);
 
    $log->logInfo(sprintf("URL host %s path %s user %s password %s\n",$host, $path, $user, $pass ));

    // open a socket connection on port 80 - timeout: 30 sec
    $fp = fsockopen($host, 80, $errno, $errstr, 30);
 
    if ($fp)
    {
         // send the request headers:
        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");
        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp,"Authorization: Basic ". base64_encode($user.':'.$pass)."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);
      
        $result = ''; 
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }
    }
   else 
    { 
        $log->logInfo(sprintf("Post Request Error \n" ));
        return array(
            'status' => 'err', 
            'error' => "$errstr ($errno)"
        );
    }
 
    // close the socket connection:
    fclose($fp);
 
    // split the result header from the content
    $result = explode("\r\n\r\n", $result, 2);
 
    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';
 
    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content
    );
}

/* -------------------------------------------------------------------------------------------------------------------*/
/* set relay state on acquisuite and return array with relay information
*/
function setRelay($addr, $point, $state, $ipAddr, $signInInfo, $log)
{   
    //Setup request to get relay data
    $post_data = array(
        'OPTION' => 'SETRELAY',
        'ADDRESS' => $addr,
        'POINT' => $point,
        'STATE' => $state
        );
    
    // send request to acquisuite
    //$result = post_request(sprintf("http://%s:%s@%s/setup/relaywidget.cgi", $signInInfo['user'], $signInInfo['pass'],$ipAddr), $post_data, $log);
    $result = post_request($ipAddr, "/setup/relaywidget.cgi",$signInInfo['user'], $signInInfo['pass'], $post_data, $log);
     
    if ($result['status'] == 'ok')
    {
         $relay_content = $result['content'];
         $p = xml_parser_create();
         xml_parse_into_struct($p, $relay_content, $vals, $index);
         xml_parser_free($p);
                  
         $relay_addr = isset($vals[1]['value']) ? $vals[1]['value'] : '';
         $relay_point = isset($vals[3]['value']) ? $vals[3]['value'] : '';
         $relay_state = isset($vals[5]['value']) ? $vals[5]['value'] : '';
          
         //$log->logInfo(sprintf("addr %s point %s state %s\n", $relay_addr, $relay_point, $relay_state));   
         // return as structured array:
        $ret_array =  array(
             'status' => true,
             'address' => $relay_addr,
             'point' => $relay_point,
             'state' => $relay_state ); 
     }
    else
    {
        //$log->logInfo(sprintf("getrelay Error %s\n", $result['error']));
         $ret_array =  array(
             'status' => false,
             'address' => 0,
             'point' => 0,
             'state' => 0 ); 
    }
   
     return $ret_array;
  
}


/* -------------------------------------------------------------------------------------------------------------------*/
/* get relay state from acquisuite and return array with relay information
*/
function getRelay($addr, $point, $ipAddr, $signInInfo, $log)
{   
    //Setup request to get relay data
    $post_data = array(
        'OPTION' => 'GETRELAY',
        'ADDRESS' => $addr,
        'POINT' => $point,
        'CACHE' => 'NO'
        );
    
    // send request to acquisuite
    //$result = post_request(sprintf("http://%s:%s@%s/setup/relaywidget.cgi", $signInInfo['user'], $signInInfo['pass'],$ipAddr), $post_data, $log);
    $result = post_request($ipAddr, "/setup/relaywidget.cgi",$signInInfo['user'], $signInInfo['pass'], $post_data, $log);
     
    if ($result['status'] == 'ok')
    {
         $relay_content = $result['content'];
         $p = xml_parser_create();
         xml_parse_into_struct($p, $relay_content, $vals, $index);
         xml_parser_free($p);
                  
         $relay_addr = isset($vals[1]['value']) ? $vals[1]['value'] : '';
         $relay_point = isset($vals[3]['value']) ? $vals[3]['value'] : '';
         $relay_state = isset($vals[5]['value']) ? $vals[5]['value'] : '';
          
         //$log->logInfo(sprintf("addr %s point %s state %s\n", $relay_addr, $relay_point, $relay_state));   
         // return as structured array:
        $ret_array =  array(
             'status' => true,
             'address' => $relay_addr,
             'point' => $relay_point,
             'state' => $relay_state ); 
     }
    else
    {
        //$log->logInfo(sprintf("getrelay Error %s\n", $result['error']));
         $ret_array =  array(
             'status' => false,
             'address' => 0,
             'point' => 0,
             'state' => 0 ); 
    }
   
     return $ret_array;
  
}


/*----------------------------------------------------------------------------------------------------*/
/* checkPowerLimits function reads demand on ship for last 15 minutes 
 *                   returns true if any value over threshold during that time
*/
 function checkPowerLimits($threshold, $devicetablename, $log)
 {
    //get data for last 15 minutes 
     $minsAgo = gmdate('Y-m-d H:i:s',strtotime('-15 minutes')); 
     $rp = 'Real_Power';
     $sql = sprintf("SELECT time, %s FROM %s WHERE time BETWEEN '%s' AND '%s' ORDER BY time DESC", $rp, $devicetablename, $minsAgo,gmdate('Y-m-d H:i:s') );

    $log->logInfo(sprintf("checkPowerLimits %d  %s\n",$threshold, $sql));
    $result = mysql_query($sql);
    if(!$result)
    {
       $log->logInfo(sprintf("Error3 in checkPowerLimits\n"));
       MySqlFailure("Unable to select Real_Power from $devicetablename");
       return false;
    }

    $device_row_count = mysql_num_rows($result);
    $log->logInfo(sprintf("%d rows in checkPowerLimits\n", $device_row_count)); 

    if($device_row_count==0)
    {	
       return false; 
    }
 
    $i=0;
    $powerOverLimit = false;
    while ($row = mysql_fetch_array($result)) 
    {
        $real_power[] = $row["$rp"];
        $log->logInfo(sprintf("checkPowerLimits  Power %f\n", $real_power[$i]));  
        if ($real_power[$i] > $threshold)
        {
            $log->logInfo(sprintf("Power %f over limit of %d\n", $real_power[$i], $threshold));
            $powerOverLimit = true;
            break;
        }
        $i++;
    } 
    return $powerOverLimit;
 }


/*----------------------------------------------------------------------------------------------------*/
/* turnRelayOn function turns on the output relay 
 *                   returns true if success
*/
function turnRelayOn($aquisuitetable, $log)
 {
      //send relay on command
    $mySignIn = myUserInfo($aquisuitetable);
    $myIP = myIP($aquisuitetable);

    // DEBUG
    /***
    $relay = array(
        'ADDRESS' => "2",
        'POINT' => "40",
        'status' => true,
        'state' => 'ON'
        );
     ***/
    
    $relay = setRelay("2", "40", "ON", $myIP, $mySignIn, $log);
    $log->logInfo(sprintf("%s IP %s State %s\n",$aquisuitetable, $myIP, $relay['state'])); 
        // save time relay turned on
    if ($relay['status'])
    {    
     setRelayOnTime($aquisuitetable, $log);
     return true;
    }
    return false;
 }

/*----------------------------------------------------------------------------------------------------*/
/* turnRelayOff function turns off the output relay 
 *                   returns true if success
*/
function turnRelayOff($aquisuitetable, $log)
 {
      //send relay on command
    $mySignIn = myUserInfo($aquisuitetable);
    $myIP = myIP($aquisuitetable);

    // DEBUG
    /****
    $relay = array(
        'ADDRESS' => "2",
        'POINT' => "40",
        'status' => true,
        'state' => 'OFF'
        );
    ****/
    
    $relay = setRelay("2", "40", "OFF", $myIP, $mySignIn, $log);
    $log->logInfo(sprintf("IP %s State %s\n",$myIP, $relay['state'])); 
        // save time relay turned on
    if ($relay['status'])
    {    
        setRelayOffTime($aquisuitetable, $log);
        return true;
    }
    return false;
 }

 
/*----------------------------------------------------------------------------------------------------*/
/* outputRelays function processes turning on or off output relays
*/
function outputRelays($LOOPNAME, $aquisuitetable, $devicetablename, $log)
{
 
    $mainUtility = getUtilityFunction($aquisuitetable, $devicetablename, $log);
    $log->logInfo(sprintf("%s:Main Util %d \n",$LOOPNAME, $mainUtility));   
    if (!$mainUtility)
    {
       $log->logInfo(sprintf("%s: not main util\n",$LOOPNAME));
        return false;
    }    
        
    //$relayOn = getRelayState($aquisuitetable, $log); //DEBUG!!!!!!
    $cottageaquisuitetable = "Cottage_001EC60500A4";
    $relayOn = getRelayState($cottageaquisuitetable, $log);
      
    if (!$relayOn) /*relay off*/
    {
       $log->logInfo(sprintf("%s: %s relay off\n",$LOOPNAME, $cottageaquisuitetable));
       
      // $relayTimeOff = getRelayOffTime($aquisuitetable, $log); //DEBUG!!!!
       $relayTimeOff = getRelayOffTime($cottageaquisuitetable, $log); //DEBUG!!
       
       $dateFromDatabase = (strtotime($relayTimeOff));
       $recentUpdate =  gmmktime() - FIVE_MIN; //get utc time 5 minutes ago 
       if ($dateFromDatabase > $recentUpdate)
       {
          $log->logInfo(sprintf("%s:%s relay off at %d 300 more than now=%d \n",$LOOPNAME, $cottageaquisuitetable,$dateFromDatabase,$recentUpdate));
          $log->logInfo(sprintf("%s:%s  %d=%s now=%d\n",$LOOPNAME, $cottageaquisuitetable,$dateFromDatabase,$relayTimeOff,$recentUpdate));
           return;  //relay turned on within 5 minutes go do something else
       }
         
        // $log->logInfo(sprintf("%s: time relay turned off %s On %s\n",$LOOPNAME, $relayTimeOff, $relayTimeOn));

         $upperThreshold = 130;
         $turnItOn = checkPowerLimits($upperThreshold, $devicetablename, $log);
         if ($turnItOn)
         {
            // if (turnRelayOn($aquisuitetable, $log)) //DEBUG!!!!
             if (turnRelayOn($cottageaquisuitetable, $log))         
             {
                 $log->logInfo(sprintf("%s:RelayCtrl Sucess Turned on because demand high \n",$LOOPNAME));
             }
         }
    }    
    else  /*relay on */
    {
        $log->logInfo(sprintf("%s: relay on\n",$LOOPNAME));
        // $relayTimeOn = getRelayOnTime($aquisuitetable, $log); //DEBUG!!!
         $relayTimeOn = getRelayOnTime($cottageaquisuitetable, $log); //DEBUG!!!

        $dateFromDatabase = strtotime($relayTimeOn);  
        $recentUpdate =  gmmktime() - FIVE_MIN; //get utc time 5 minutes ago 
        if ($dateFromDatabase > $recentUpdate)
        {
           return;  //relay turned off within 5 minutes go do something else
        }
        
        $dateOneHourAgo =  gmmktime() - ONE_HOUR; //one hour ago UTC
       
       $log->logInfo(sprintf("%s time relay turned on %s  minus 1 hour %s\n",$LOOPNAME, $dateFromDatabase, $dateOneHourAgo));  
       $log->logInfo(sprintf("%s:relay on %s one hr ago utc %s \n",$LOOPNAME, $relayTimeOn, gmdate('Y-m-d H:i:s', $dateOneHourAgo)));

        if ($dateFromDatabase < $dateOneHourAgo) 
        {
            // relay has been on over one hour so turn it off
            $log->logInfo(sprintf("%s:relay ON turned on %s over one hour ago %s \n",$LOOPNAME, $relayTimeOn, gmdate('Y-m-d H:i:s', $dateOneHourAgo)));
            //if (turnRelayOff($aquisuitetable, $log)) //DEBUG
            if (turnRelayOff($cottageaquisuitetable, $log))
            {
                $log->logInfo(sprintf("%s:RelayCtrl %s relay Sucess Turned OFF because on over one hour \n",$LOOPNAME, $cottageaquisuitetable));
            }
         }
        else 
        {
            $lowerThreshold = $upperThreshold - 20;
            $log->logInfo(sprintf("%s:relay ON need to check if below %d threshold \n",$LOOPNAME, $lowerThreshold));
            $overLimits = checkPowerLimits($lowerThreshold, $devicetablename, $log);
            if (!$overLimits)
            {
                $log->logInfo(sprintf("%s:relay ON, RP under limits for 15 minutes  not overlimits\n",$LOOPNAME));
               // if (turnRelayOff($aquisuitetable, $log))  //DEBUG
              if (turnRelayOff($cottageaquisuitetable, $log))        
                {
                   $log->logInfo(sprintf("%s:RelayCtrl %s relay Sucess Turned OFF because RP under limit for 15 minutes \n",$LOOPNAME, $cottageaquisuitetable));
                }
            }    
        }
    }


            //$mySignIn = myUserInfo($aquisuitetable);
             //$myIP = myIP($aquisuitetable);

            // $relay = getRelay("2", "40", $myIP, $mySignIn, $log);
            // $log->logInfo(sprintf("IP %s State %s\n",$myIP, $relay['state']));

            // $relay = setRelay("2", "40", "ON", $myIP, $mySignIn, $log);
            // $log->logInfo(sprintf("IP %s State %s\n",$myIP, $relay['state'])); 

            // $relay = getRelay("2", "40", $myIP, $mySignIn, $log);
            // $log->logInfo(sprintf("IP %s State %s\n",$myIP, $relay['state']));
    
}