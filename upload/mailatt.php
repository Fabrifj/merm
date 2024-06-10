#!/usr/bin/php
<?php
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
function mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
 $file = $path.$filename;
 $file_size = filesize($file);
 $handle = fopen($file, "r");
 $content = fread($handle, $file_size);
 fclose($handle);
 $content = chunk_split(base64_encode($content));
 $uid = md5(uniqid(time()));
 $name = basename($file);
 $header = "From: ".$from_name." <".$from_mail.">\r\n";
 $header .= "Reply-To: ".$replyto."\r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
 $header .= "This is a multi-part message in MIME format.\r\n";
 $header .= "--".$uid."\r\n";
 $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
 $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
 $header .= $message."\r\n\r\n";
 $header .= "--".$uid."\r\n";
 $header .= "Content-Type:text/plain; name=\"".$filename."\"\r\n"; // use different content types here
 $header .= "Content-Transfer-Encoding: base64\r\n";
 $header .= "Content-Disposition: attachment; filename=\"".$name."\"\r\n\r\n";
 $header .= $content."\r\n\r\n";
 $header .= "--".$uid."--";
 return @mail($mailto, $subject, "", $header);
 }
 

include "/home/b/bwolff/public_html/equatesite.com/conn/mysql_pconnect-all.php";
require_once '../erms/includes/KLogger.php';
 $log = new KLogger ( "fixlog.txt" , KLogger::DEBUG );   // klogger debug everything

$timeupdate = date("M j y H-i-s.", time());

$filename = sprintf("errorlog_%s",$timeupdate);
$filedirectory = "/home/b/bwolff/public_html/equatesite.com/upload/errorlog";
$filename = sprintf("%s/%s.txt",$filedirectory,$filename);
$foh = fopen($filename,'w');

 $sql = "SELECT * FROM `errorlog`";
 $result = mysql_query($sql);
 if(!$result)
 {
 	MySqlFailure("unable to select data from errorlog");
 }
 
 $fcontent = "======== ERROR LOG FOR $timeupdate ========\r\n\r\n"; 
 
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
 	$row1 = mysql_fetch_row($result1);
 	$errorcount = round($row1[0]/12,2)." hours";
 	
 	$sql2 = "SELECT `errorresponse` FROM `errorcodes` WHERE errorcode='$errorcode'";
 	$result2=mysql_query($sql2);
 	if(!$result)
 	{
 		MySqlFailure("unable to select errorresponse for errorcode");
 	}
 	$row2 = mysql_fetch_row($result2);
 	$errorresponse = $row2[0];
 	
 	$fcontent .= "$devicetablename : "."$errorcode"." $errorresponse."."Device has stopped uploading data since $time.\r\ntotal downtime: $errorcount\r\n\r\n"; 
 

 }
 $output = fputs($foh,$fcontent);
 $log->logInfo(sprintf("%s \n", $fcontent )); 

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

$path="";
$mailto="james.cates@alariscompanies.com";
$from_mail="Do not Reply";
$from_name="ERMS Administration Department";
$replyto="";
$subject="Error Log Update ".$timeupdate;
$message="Dear Aquisuite User,\r\nAttached is a list of all errors on each device attahced to each Aquisuite on ERMS.";



//mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message);
?>