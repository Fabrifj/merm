<?php

/*
 *  File: EmailLogStatus.php
 *  Author: Carole Snow
 * 
 * EmailLogStatus reads the most recent 24 hours of log data for all ships with meter status set to on.
 * The logs are checked for any intervals greater than Max Gap Allowed between logs and sends an email
 * report to mgaffney @ mdoinc.net. An email is also send daily reporting all ships offline more than one day.
 *  This script is designed to run from CRON once a day. 
 * 
 * Copyright © 2017, Navis Energy Management Solutions. All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are not permitted without the author's permission.
 * 
 */

//Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
const ONE_MIN = 60;
const TEN_MIN = 600;
const ONE_HOUR = 3600;
const FIFTEEN_MIN = 900;
const MAX_GAP_ALLOWED = TEN_MIN;
const FROM_EMAIL = "mgaffney@mdoinc.net";  //email address being sent from
//
$toEmail = "mgaffney@mdoinc.net";


$log;

/* ---------------------------------------------------------------------------------------------------- */
/* mySQL reporting function. terminates if error is fatal and prints out mySQL warnings for failure.
 */

function MySqlFailure($Reason)
{
    echo "mySQL FAILURE: $Reason" . " ";
    $sql_errno = mysql_errno();

    if ($sql_errno > 0)
    {
        echo "mySQL FAILURE: $Reason" . " " . $sql_errno . ": " . mysql_error();
    }
    sleep(1);
    exit;
}

function debug_log()
{
    global $log;
   // require '../erms/includes/KLogger.php';
    require(DEBUG_PATH.'KLogger.php');
    $log = new KLogger("fixlog.txt", KLogger::DEBUG);   // klogger debug everything
}

function getAllShips()
{
    $sqlQ = "SELECT * FROM `Aquisuite_List`";
    $result = mysql_query($sqlQ);
    if (!$result)
    {
        MySqlFailure("Unable to execute SQL query");
    }
    $count = mysql_num_rows($result);
    if ($count <= 0)
        return NULL;

    $p = 0;
    $shipArray = array();
    while ($row = mysql_fetch_assoc($result))
    {
        $shipArray[] = array("SerialNumber" => $row['SerialNumber'], "loopName" => $row['loopname'], "tableName" => $row['aquisuitetablename']);
        //printf("%s %s %s\n", $shipArray[$p]['SerialNumber'], $shipArray[$p]['loopName'], $shipArray[$p]['tableName']);
        $p++;
    }
    return($shipArray);
}//end function 

function getMainTable($tableName, $LOOPNAME)
{
    $sqlQ = sprintf("SELECT * FROM `%s`", $tableName);
    $result = mysql_query($sqlQ);
    if (!$result)
    {
        MySqlFailure("getMainTable:Unable to execute SQL query");
    }
    while ($row = mysql_fetch_assoc($result))
    {
        if ($row['function'] == "main_utility")
        {
            if ($row['meter_status'] == 1) //meter status set on
            {
                return $row['devicetablename'];
            }
        }
    }
    return NULL; //no main utility declared in ship table
}

function getOffLineShip($tableName, $LOOPNAME)
{
    $mainTable = getMainTable($tableName, $LOOPNAME);
    if ($mainTable == NULL)
        return NULL;

    $defaultDate = date('Y-m-d 00:00:00', strtotime("1999-01-01")); //send back default beginning of time
    $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
    $endDate = date('Y-m-d H:i:s');

    $sqlQ = sprintf("SELECT time FROM %s WHERE time BETWEEN '%s' AND '%s'", $mainTable, $startDate, $endDate);
    $result = mysql_query($sqlQ);
    if ($result)
    {
        $count = mysql_num_rows($result);
        if (!$count)
        {
            $sqlQ = sprintf("SELECT time FROM %s ORDER BY time DESC LIMIT 1", $mainTable);
            $result = mysql_query($sqlQ);
            $row = mysql_fetch_assoc($result);
            if ($row)
            {
                //printf("TIME %s\n",$row['time']);
                return $row['time'];
            }
        }
        else
        {
            return NULL;
        }
    }
    return $defaultDate;
}//end function

function checkTimes($tableName, $startDate, $endDate, $LOOPNAME)
{
    $mainTable = getMainTable($tableName, $LOOPNAME);
    if ($mainTable == NULL)
        return NULL;

    //for testing offline set the data back a year
    //$startDate = date('Y-m-d 00:00:00', strtotime("2016-08-01"));
    //$endDate = date('Y-m-d H:i:s', strtotime("2016-08-30 00:00:00"));
    $sqlQ = sprintf("SELECT time, Real_Power FROM %s WHERE time BETWEEN '%s' AND '%s'", $mainTable, $startDate, $endDate);
    $result = mysql_query($sqlQ);
    if (!$result)
    {
        return NULL;
    }
    $count = mysql_num_rows($result);
    if (!$count)
        return NULL;

    $row1 = mysql_fetch_assoc($result);
    if (!$row1)
    {
        return NULL;
    }

    $badTimes = array();
    while ($row2 = mysql_fetch_assoc($result))
    {
        $firstTime = $row1['time'];
        $nextTime = $row2['time'];
        $timestamp1 = strtotime($firstTime);
        $timestamp2 = strtotime($nextTime);
        $diff = abs($timestamp2 - $timestamp1);
        if ($diff >= MAX_GAP_ALLOWED)
        {
            $badTimes[] = array("time" => $row1['time'], "loopName" => $LOOPNAME, "diffSecs" => $diff, "Real_Power" => $row1['Real_Power']);
            //printf("%s: %s  %d minutes\n", $LOOPNAME, $firstTime, ($diff/ONE_MIN));
        }
        $row1 = $row2;
    }
    return $badTimes;
}//end function 

//sort function used to order ship offline array by most recent time then alphabetically by ship name
function sort_by_time($a, $b)
{
    if ($a['offTime'] == $b['offTime'])
    {
        if ($a['ship'] == $b['ship'])
        {
            return 0;
        }
        return ($a['ship'] < $b['ship']) ? -1 : 1;
    }
    return ($a['offTime'] > $b['offTime']) ? -1 : 1;
}//end function

function generateStatus($offLineShips)
{
    uasort($offLineShips, 'sort_by_time');
    $body = sprintf("Ship last log date(utc) :\r\n");
    foreach ($offLineShips as $ship)
    {
        $body .= sprintf("%s  %s\r\n", $ship['ship'], $ship['offTime']);
        $body = wordwrap($body, 70, "\r\n");
    }
    //printf($body);
    return $body;
}//end function

function generateList($shipTimes, $startDate, $endDate, $LOOPNAME)
{
    $body = sprintf("%s log gaps %s to %s(utc):\r\n", $LOOPNAME, $startDate, $endDate);
    foreach ($shipTimes as $times)
    {
        if (isset($times['diffSecs']) && ($times['diffSecs'] > 0))
        {
            $diffMinutes = $times['diffSecs'] / ONE_MIN;
        }
        $body .= sprintf("%s %d minutes\r\n", $times['time'], $diffMinutes);
    }
    $body .= "\n";
    $body = wordwrap($body, 70, "\r\n");
    //printf($body);
    return $body;
}//end function

function sendEmailGmail($to, $subject, $message, $LOOPNAME)
{
    $toEmailCC = "cesnow85@gmail.com";

   $mail = new PHPMailer(TRUE);
    //Server settings
    $mail->SMTPDebug = 2;                                 // Enable verbose debug output
    $mail->ContentType = 'text/plain';
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com';                       // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication

    $mail->Username = "shipsenergy@gmail.com";            // SMTP username
    $mail->Password = "Pasha23502Energy99";                       // SMTP password
    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                                    // TCP port to connect to
    // OAUTH Requires PHP Version 5.6 so skip for now
    //Recipients
    $mail->setFrom('shipsenergy@gmail.com', 'Ships Energy');
    $mail->addAddress($to);  // Add a recipient
    $mail->AddCC($toEmailCC);   //Add test email CC
    //$mail->AddCC('navisenergy@gmail.com');   //Send back to gmail account
    $mail->addReplyTo('shipsenergy@gmail.com', 'Mike Gaffney');

    //Content
    $mail->isHTML(false);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body = $message;
    // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if (!$mail->send())
    {
        printf("Message could not be sent.");
        printf("Mailer Error: %s", $mail->ErrorInfo);
    }
    else
    {
        printf("Message has been sent");
    }
}

/* end function */



/* ================================================================================================================== */
/*
/*    MAIN SCRIPT
*/


define('ROOT_PATH', dirname(__DIR__).'/');
define('CONN_PATH', dirname(__DIR__).'/conn/');
define('DEBUG_PATH', dirname(__DIR__).'/erms/includes/');
   

//include "../conn/mysql_pconnect-all.php"; // mySQL database connector. //old way
include(CONN_PATH.'mysql_pconnect-all.php'); // mySQL database connector.  //pasaha liquidweb


date_default_timezone_set('UTC');/** set the timezone to UTC * */
//Currently the arguments don't do anything. Someday pass in option for data and ship
if ($argc > 1)
{
    if ($argv[1] == "test")
    {
        $test_only = TRUE;
        //printf("EmailLogStatus %s\n", ($test_only ? "Test-Only" : "Not a test"));
    }
    else if ($argv[1] == "?")
    {
        printf("Usage: php EmailLogStatus.php  [test] (ex:php EmailLogStatus.php [test] (test: to test without changes)\n");
        exit;
    }
}

debug_log();

//printf("dir [%s] root path [%s] conn path [%s] debug [%s]\n  ", __DIR__, ROOT_PATH, CONN_PATH.'mysql_pconnect-all.php', DEBUG_PATH.'KLogger.php');
 
$shipArray = getAllShips();  //get a list of available ship table names
//get data for last 24 hours
$startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
$endDate = date('Y-m-d 00:00:00', strtotime('now'));

//printf("start %s end %s \n", $startDate, $endDate);
foreach ($shipArray as $ship)
{
    $shipTimes = checkTimes($ship['tableName'], $startDate, $endDate, $ship['loopName']);
    $offTime = getOffLineShip($ship['tableName'], $ship['loopName']);
    if ($offTime != NULL)
    {
        //printf("SHIP %s OFFLINE since %s\n", $ship['tableName'], $offTime);
        $offLineShips[] = array("ship" => $ship['loopName'], "offTime" => $offTime);
    }
    if ($shipTimes != NULL)
    {
        // The Subject
        $subject = sprintf("%s Log Gap", $ship['loopName']);
        $message = generateList($shipTimes, $startDate, $endDate, $ship['loopName']);
        sendEmailGmail($toEmail, $subject, $message, $ship['loopName']);
    }
}
//Send Status Email
$subject = sprintf("ShipsEnergy Ships Offline"); // The Subject
if (isset($offLineShips))
{
    $message = generateStatus($offLineShips);
   // sendEmailGmail($toEmail, $subject, $message, $ship['loopName']);
    
    $sendThis = wordwrap($message, 70, "\r\n");
   // printf("%s \n", $sendThis);
    mail('shipsenergy@gmail.com','Ships Offline',$sendThis);

}
?>

