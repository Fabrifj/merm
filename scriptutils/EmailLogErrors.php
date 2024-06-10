#!/usr/bin/php
<?php
/*
 *  File: EmailLogStatus.php
 *  Author: Carole Snow
 * 
 * EmailLogErrors reads the errortrack table in the database and sends the recorded
 * errors in an email.  The recorded errors are then deleted from the database.
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
const FROM_EMAIL = "mgaffney@mdoinc.net";
//$toEmail = "mgaffney@mdoinc.net";
$toEmail = "shipsenergy@gmail.com";

    
function MySqlFailure($Reason)
{	
	$con = $_SESSION['con'];
	$sql_errno = mysql_errno($con); 
	
	if($sql_errno>0)
	{	
		echo "mySQL FAILURE: $Reason"."</br>";
		echo  "mySQL FAILURE: $Reason"."</br>".$sql_errno. ": " . mysql_error($con) . "</br>";
		exit;
	}
	else
	{
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
								$value+= $value." ";
							}
							$value_repeat = $value;
						}
						echo "mySQL WARNING: $value"."</br>";
					}
				}
			}
		}
	}
}
 
function sendEmailGmail($to, $subject, $message)
{
  
 //    $toEmailCC = "cesnow85@gmail.com";

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
 //  $mail->AddCC($toEmailCC);   //Add test email CC
    //$mail->AddCC('navisenergy@gmail.com');   //Send back to gmail account
    $mail->addReplyTo('shipsenergy@gmail.com', 'Mike Gaffney');

  
    //Content
    $mail->isHTML(false);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body = $message;
 
    if (!$mail->send())
    {
        printf("Message could not be sent.");
        printf("Mailer Error: %s", $mail->ErrorInfo);
    }
    else
    {
        //printf("Message has been sent");
    }
  
}


define('ROOT_PATH', dirname(__DIR__).'/');
define('CONN_PATH', dirname(__DIR__).'/conn/');
define('DEBUG_PATH', dirname(__DIR__).'/erms/includes/');
 
//include "../conn/mysql_pconnect-all.php"; // mySQL database connector. //old way
include(CONN_PATH.'mysql_pconnect-all.php'); // mySQL database connector.  //pasaha liquidweb

 // require '../erms/includes/KLogger.php';
 require(DEBUG_PATH.'KLogger.php');
 $log = new KLogger ( "fixlog.txt" , KLogger::DEBUG );   // klogger debug everything

$curTime = time();
$timeupdate = date("Mjy-H-i-s", $curTime);
$formatTimeDate = date("M-j-Y H:i:s", $curTime);

$filename = sprintf("errorlog_%s",$timeupdate);
$filedirectory = sprintf("%s/%s",getcwd(),"errorlog");
$filename = sprintf("%s/%s.txt",$filedirectory,$filename);
printf("file %s\n", $filename);
$foh = fopen($filename,'w');

 $sql = "SELECT * FROM `errorlog`";
 $result = mysql_query($sql);
 if(!$result)
 {
 	MySqlFailure("unable to select data from errorlog");
 }
 $count = mysql_num_rows($result);
 if ($count <= 0)
    exit;


 $fcontent="Logged errors on each device connected to SMSS since the last report.\r\n";
 $fcontent = "== ERROR LOG FOR $formatTimeDate ==\r\n\r\n"; 
 
 while ($row = mysql_fetch_array($result))
 {	
 	$devicetablename = $row['devicetablename'];

 	$errorcode = $row['errorcode'];
 	$time = $row['time'];
 	$sql1 = "SELECT $errorcode FROM `errortrack` WHERE devicetablename='$devicetablename'";
 	$result1 = mysql_query($sql1);
 	if(!$result)
 	{
 		MySqlFailure("unable to select errorcode for device table name");
 	}
 	
 	$sql2 = "SELECT `errorresponse` FROM `errorcodes` WHERE errorcode='$errorcode'";
 	$result2=mysql_query($sql2);
 	if(!$result)
 	{
 		MySqlFailure("unable to select errorresponse for errorcode");
 	}
 	$row2 = mysql_fetch_row($result2);
 	$errorresponse = $row2[0];
        
        $fcontent .= "$devicetablename : "."$errorcode"." $errorresponse."." First reported at $time since last email.\r\n\r\n"; 

 }
 $output = fputs($foh,$fcontent);
 //$log->logInfo(sprintf("%s \n", $fcontent )); 

 
$sql = "TRUNCATE TABLE `errortrack`";
$result = mysql_query($sql);
if(!$result)
{
        $log->logInfo(sprintf("Unable to empty errortrack \n")); 
	MySqlFailure("unable to empty errorcodes");
}

$sql = "TRUNCATE TABLE `errorlog`";
$result = mysql_query($sql);
if(!$result)
{
        $log->logInfo(sprintf("Unable to empty errorlog \n")); 
	MySqlFailure("unable to empty errorcodes");
}

$subject="SSMS Error Log ".$formatTimeDate;

//sendEmailGmail($toEmail, $subject, $fcontent);
$sendThis = wordwrap($fcontent, 70, "\r\n");
mail('shipsenergy@gmail.com',$subject,$sendThis);

?>