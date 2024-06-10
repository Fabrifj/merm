#!/usr/bin/php
<?php
include '../erms/includes/debugging.php';

function ReportFailure($szReason,$ex)
{
	
	echo "<script language='javascript'> 
				error_text.innerHTML = 'FAILURE: $szReason' 
				</script>";
	
	if($ex==true)
	{	
		exit;
	}
}
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
function counttablefields($devicetablename)
{
	$sql="SELECT * FROM `$devicetablename`";
	$result=mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("unable to retreive $devicetablename data");
	}
	$countdevicefields = mysql_num_fields($result);
	
	return $countdevicefields;
}
function device($aquisuitetable,$MODBUSDEVICENUMBER)
{
	$sql = "SELECT * FROM $aquisuitetable WHERE `modbusdevicenumber`='$MODBUSDEVICENUMBER'";
	$result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("could not process query");
	}

	$row = mysql_fetch_array($result);
	$DEVICE["devicetablename"] = $row['devicetablename'];
	$DEVICE["deviceclass"] =$row['deviceclass'];
	$DEVICE["uploaded"] = $row['uploaded'];
	
	return $DEVICE;
}
function devicecount($aquisuitetable)
{
	$sql = "SELECT * FROM $aquisuitetable WHERE `uploaded`='1'";
	$result = mysql_query($sql);

	if(!$result)
	{
		MySqlFailure("unable to process query");
	}

	while($row = mysql_fetch_array($result))
	{
		$DEVNUM[] = $row['modbusdevicenumber'];
	}
	
	return $DEVNUM;
}

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

function timezone($ship)
{
	$tzaquisuite = "timezoneaquisuite";

	$sql = "SELECT $tzaquisuite FROM `Aquisuite_List` WHERE `aquisuitetablename`='$ship'";
	$result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("could not find $tzaquisuite from $ship ");
	}
	$row = mysql_fetch_row($result);
	$timezoneaquisuite = $row[0];

	$sql = "SELECT timezonephp FROM `timezone` WHERE $tzaquisuite='$timezoneaquisuite'";
	$result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("could not locate timezone ");
	}
	$row = mysql_fetch_row($result);
	$timezone = $row[0];

	return $timezone;

}

//$clpath = "/home/b/bwolff/public_html/equatesite.com/upload/";
$clpath = "";
//include "/home/b/bwolff/public_html/equatesite.com/conn/mysql_pconnect-all.php";
include "../conn/mysql_pconnect-all.php";
session_start();

set_time_limit (0);
	
ini_set('memory_limit',"-1");

if($_POST['aquisuite']=='all') {
	$sql = "SELECT * FROM `Aquisuite_List` WHERE `manualupload`='1'";
	$result = mysql_query($sql);
	if(!$result)
	{
		MySqlFailure("could not Select manualupload from Aquisuite_List ");
	}
	while($row=mysql_fetch_array($result))
	{
		$aquisuite[] = $row['aquisuitetablename'];
	}
}
else {
	$aquisuite[] = $_POST['aquisuite']; 
}
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title> EQUATE | Marine Energy Services Provider | Shipboard Energy Management</title>
	</head>
	<form name="upload" action="manual_data_process.php" method="POST">
	Select Update Option: <select name="aquisuite">
	<option value="all">update all</option>
	<option value='Regulus_001EC60014F2'>SS Regulus</option>
	</select>
	<input type="submit" value="Update">
	</form>
	
<?
if(isset($_POST['aquisuite'])) {
	foreach ($aquisuite as $aquisuitetable)
	{
		
		$AFilesArray = glob("$clpath"."$aquisuitetable/*.csv");
		$acount = count($AFilesArray);
		
			$DEVNUM = devicecount($aquisuitetable);
			foreach ($DEVNUM as $devicenumber)
			{	
				if ($devicenumber>99)
				{
					$fs = "mb-";
				}
				else if ($devicenumber>9)
				{
					$fs = "mb-0";
				}
				else
				{
					$fs = "mb-00";
				}
				
				$devicefilecount += count(glob("$clpath"."$aquisuitetable/".$fs.$devicenumber."*"));
			}
		
		?>			
				<h3>LOADING ERMS</h3>
				<div id="apDiv3"></div>
				<div id="apDiv4"></div>
				<div id="apDiv5"></div>
				<div id="apDiv6"></div>
				<div id="apDiv7"></div>
				<div id="apDiv8"></div>
				
		<script language="javascript"> 
		var loading_text = document.getElementById('apDiv3');
		var file_text = document.getElementById('apDiv4');
		var numfiles_text = document.getElementById('apDiv5'); 
		var totfiles_text = document.getElementById('apDiv6'); 
		var timerem_text = document.getElementById('apDiv7'); 
		var error_text = document.getElementById('apDiv8');
		//loading_text.innerHTML = 'Loading...please wait while data is updated'; 
		</script> 
		
		<?php
		
		$utility = utility_check($aquisuitetable);
		
		$timezone = timezone($aquisuitetable);
		
		if ($devicefilecount==0)
		{
			$sql = "SELECT * FROM $aquisuitetable";
			$result = mysql_query($sql);
			if(!$result)
			{
				MySqlFailure("unable to select table data");
			}
			while($row = mysql_fetch_array($result))
			{
				$class = $row['deviceclass'];
				if($class=='2')
				{
					$utilitytable[] = $row['devicetablename'];
				}
			}
			
			echo 'All files are updated'.'</br>'; 
									
		}
		else
		{
			/*declaring main variables*/
			$filesDeleted=glob("$clpath"."$aquisuitetable/mb-250*");
			$FilesArray = glob("$clpath"."$aquisuitetable/*.csv");
			$FileCount = count($FilesArray);
			$Dcount = count($filesDeleted);
			$filesUpdated = $FileCount - $Dcount;
		
			/*deleting all blank files in directory*/
			foreach ($filesDeleted AS $dfiles)
			{
				$delete_empty_files = unlink($dfiles);
		
				if (!$delete_empty_files)
				{
					echo "could not delete " . $dfiles . "</br>";
					
					exit;
				}
			}
		
			/*resetting variables after blank files are deleted*/
			$FilesArray = glob("$clpath"."$aquisuitetable/*.csv");
			
			$filesUpdated = $devicefilecount;
				
			$devicefilecount=0;
			$avecount=0;
			foreach ($FilesArray AS $files) /*starts the loop through all csv files in directory*/
			{ 
				$time_start = time();
				
				$MODBUSDEVICENUMBER = substr(basename($files),3,3);
		
				$DEVICE = device($aquisuitetable,$MODBUSDEVICENUMBER);
				
				$table = $DEVICE['devicetablename'];
				$deviceclass = $DEVICE['deviceclass'];
				$upload = $DEVICE['uploaded'];
				
				if(!empty($table) && $upload=='1')
				{
					if($devicefilecount==0)
					{
						$devicefilecount = count(glob("$clpath"."$aquisuitetable/mb-".$MODBUSDEVICENUMBER."*"));
						echo "<script language='javascript'> 
						loading_text.innerHTML = 'UPLOADING DEVICE: ".$MODBUSDEVICENUMBER."'</script>";
						
						if($deviceclass=="2")
						{
							$utilitytable[] = $table;
						}
					}
					
					echo "<script language='javascript'> 
						file_text.innerHTML = 'UPDATING FILE: ".basename($files)."'</script>";
					echo "<script language='javascript'>
						numfiles_text.innerHTML = 'DEVICE FILES REMAINING: ".$devicefilecount."'</script>";
					echo "<script language='javascript'>
						totfiles_text.innerHTML = 'TOTAL FILES REMAINING: ".$filesUpdated."'</script>";	
					
					$filesUpdated--;
					$devicefilecount--;
		
					$fd_size = filesize($files);
					$fd = fopen($files, "r");
					
					if (! $fd)
					{   
						printf("fopen failed to open logfile " . ($files));
						exit;
					}
					
					$iFileTime = filemtime($files);
					$filearc = sprintf("mb-%s_class%s_%s",$MODBUSDEVICENUMBER,$deviceclass,date ("M j y H-i-s.", $iFileTime));
					$arcdirectory = "Archive";
					
					$szTargetDirectory = sprintf("%s%s/%s",$clpath,$aquisuitetable,$arcdirectory);
					
					$szTargetFilename  = sprintf("%s/%s.csv", $szTargetDirectory, $filearc);
					
					if (! file_exists($szTargetDirectory ))            // if the directory does not exist, create it.
					{
						$nResult = mkdir($szTargetDirectory, 0700);    // create directory (unix permissions 0700 are for directory owner access only)
						if (! $nResult)                                // trap directory create errors.
						{   ReportFailure("Error creating directory " . $szTargetDirectory,true);
							exit;
						}
					}
					
					if (file_exists($szTargetFilename ))
					{   ReportFailure("target file already exits " . $szTargetFilename,false);
						$target_size = filesize($szTargetFilename);
						
						if($fd_size == $target_size)
						{	
							unlink($files);
							echo basename($szTargetFilename)." already updated"."</br>";
							continue;
						}
						continue;
					}
					
					$fOut = fopen($szTargetFilename, 'w');           // create/open target file for writing
					if (! $fOut)                                   // trap file create errors.
					{   ReportFailure("Error creating file " . $szTargetFilename,true);
						exit;
					}
						
					printf("saving data to file %s"."</br>", $szTargetFilename);  // be nice and print out the target file location.
		
					$fieldcount = counttablefields($table);
					
					while(!feof($fd))                             // loop through the source file until we reach the end of the file.
					{   
						$szBuffer = fgets($fd, 1024);              // read lines from the log file.  make sure lines don't exceed 512 bytes
						if (strlen($szBuffer) > 0)                 // verify the line is not blank.
						{
							$nResult = fputs($fOut, $szBuffer);    // write data to the log file.
							if (! $nResult)                        // trap file write errors.
							{   ReportFailure("Error writing to output file " . $szTargetFilename,true);
								exit;
							}
								 // You must check for bad chars here, such as semicolon, depending on your flavor of SQL.  
								 // All data in the log file should be numeric, and may include symbols: minus, comma, space, or 'NULL' if the data point was not available.
								 // at some point in the future, Obvius will replace the word "NULL" with a blank column in the log data to save space.
								 // not checking for characters like semicolon poses a security risk to your SQL database
								 // as someone could insert a line "3,4,5,7');drop table password;('3,4,5" into the log file. (this would be very bad)                   
								 // replace "sometable" with the name of the table you wish to insert the data into.
							if (substr($szBuffer,0,4)!="time")
							{
								 $fieldarray = explode(",", $szBuffer);             // start check for bad chars here, like semicolon. first split a single log entry in to components.
								 $nCol = 0;
								 $query = "INSERT INTO $table VALUES (";     // query prefix.  finished query string is:  insert into table values ('1','2','3')
								 
								 foreach ($fieldarray as $value)                           // loop through each array element by calling it "value", (one per data column,) 
								 {	
									 $value = str_replace('"', "", $value);     // strip double quotes 
									 $value = str_replace("'", "", $value);  // strip single quotes         
									 $value = trim($value);                   // trim whitespace, tabs, etc, on the ENDS of the string.  
									 $value = mysql_real_escape_string($value);   // MySQL has a special strip function just for this purpose, other SQL versions may vary.
									
									 switch($nCol)
									 {  case 0:  
										$query  = $query . sprintf("'%s'", $value); 	// quote data (utc date), first column has no leading comma
										$time = $value;
										break; 
										case 2: 
										case 3:  $query  = $query . sprintf(",%s", $value); break;  // don't quote hex (alarm) data, column needs leading comma seperator.
										default: 
										{   
											if($nCol==1 && $value>0)
											{
												$errorcount += 1;
												$errorcode = $value;
												$sql = "SELECT `$errorcode` FROM `errortrack` WHERE `devicetablename`='$devicetablename'";
												$result = mysql_query($sql);
												if(!$result)
												{
													MySqlFailure("could not update errortrack");
												}
												$rowcount = mysql_num_rows($result);
												
												if($rowcount==0)
												{	
													echo "rowcount: ".$rowcount;
													echo "errortrackcount: ".$errortrackcount;
													
													$sql = "INSERT INTO `errortrack` VALUES ('$devicetablename'";
													$e=1;
													while($e<$errortrackcount-1)
													{
														$sql = $sql.",''";
														$e++;
													}
													$sql = $sql.",'')";
													
													$result=mysql_query($sql);
													if(!$result)
													{
														MySqlFailure("unable to insert devicetablename into errortrack table");										
													}
												}
												
												$sql = "UPDATE `errortrack` SET `$errorcode`=`$errorcode`+1 WHERE `devicetablename`='$devicetablename'";
												
												$result=mysql_query($sql);
												
												if(!$result)
												{
													MySqlFailure("Unable to Update devicetablename in errortrack");
												}
												
												$sql = "SELECT `$errorcode` FROM `errortrack` WHERE `devicetablename`='$devicetablename'";
												
												$result=mysql_query($sql);
												if(!$result)
												{
													MySqlFailure("Unable to Select errorcode from errortrack");
												}
												$row = mysql_fetch_row($result);
												$codecount = $row[0];
												if($codecount==1)
												{
													$sql = "INSERT INTO `errorlog` VALUES ('$devicetablename','$errorcode','$time')";
													
													$result = mysql_query($sql);
													if(!$result)
													{
														MySqlFailure("Unable to Insert errorcode into errorlog");
													}
											
												}
											}
											if ($value == "") $query  = $query . ",''" ;    // don't quote the word 'NULL'
											   else $query  = $query . sprintf(",'%s'",$value); // quote data, all other columns need leading comma seperator.
										}
									 }
									 
									 $nCol++;
								 }
								
								$sql = "SELECT * FROM $table WHERE time='$time'";
								$result = mysql_query($sql);
								
								if(!$result)
								{
									MySqlFailure("could not execute query SQL: %s\n",$sql);
								}
						
								$rowsupdated = mysql_num_rows($result);
		
								if ($rowsupdated>0)
								{
									echo "record datetime: $time already exists"."</br>";
									
									continue;
								}
								
								
								if($fieldcount!=$nCol)
								{	
									$addfields = $fieldcount - $nCol;
									$i=0;
									while($i<$addfields)
									{
										$query = $query . ",''";
									
									$i++;
									}
								}
						
								$query = $query . ")";
								$result = mysql_query($query);
								if(!$result)
								{
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
					
					echo "$successcount lines inserted successfully"."</br>";
					
					if($failcount!=0)
					{
						echo "$failcount lines failed"."</br>";	
					}
					else
					{	
						$size_szTFile = filesize($szTargetFilename);
						
						if(file_exists($szTargetFilename) && $fd_size==$size_szTFile)
						{
							unlink($files);
							echo "SUCCESS"."</br>";
						}
					}
					
					$avecount++;
					$time_end = time();
					$fileProcesstime += $time_end-$time_start;
					$fileUploadtime = $fileProcesstime/$avecount;
					$devicetimeremaining = $fileUploadtime*$devicefilecount;
					$seconds = round($devicetimeremaining,2)." seconds ";
					$hours='';
					$minutes='';
					if($devicetimeremaining>60 && $devicetimeremaining<3600)
					{	
						$hours = '';
						$minutes = round($devicetimeremaining/60)." minutes ";
						$seconds = ($devicetimeremaining%60)." seconds ";
					}
					else if($devicetimeremaining>3600)
					{
						$hours = round(($devicetimeremaining/60)/60)." hours ";
						$minutes = round($devicetimeremaining/60)." minutes ";
						$seconds =($devicetimeremaining%60)." seconds ";
					}
					
					$deviceupdatetime = $hours.$minutes.$seconds;
					
					echo "<script language='javascript'>
							timerem_text.innerHTML='DEVICE UPDATE TIME REMAINING: ".$deviceupdatetime."'</script>";
						
					unset($successcount);
					unset($failcount);
				}
			}
		}
		
		
		$sql_rates = "SELECT * FROM `$utility`";
		$rate_q = mysql_query($sql_rates);
		
		if(!$rate_q)
		{
			MySqlFailure("unable to process mysql request");
		}
		
		if(!empty($utilitytable))
		{	
			$count_utables = count($utilitytable);
			
			foreach($utilitytable AS $table)
			{
			
					echo "<script language='javascript'> 
						loading_text.innerHTML = 'UPLOADING UTILITY COLUMNS FOR: ".$table."'</script>";
					echo "<script language='javascript'>
						numfiles_text.innerHTML = 'UTILITY TABLES REMAINING: ".$count_utables."'</script>";
					echo "<script language='javascript'>
						totfiles_text.innerHTML = ''</script>";
					echo "<script language='javascript'>
						timerem_text.innerHTML = ''</script>";
						
				if ($utility=="SCE&G_Rates")
				{
				$cost=mysql_fetch_array($rate_q);
		
				$Summer_Start = idate(m,strtotime($cost[10]));
				$Summer_End = idate(m,strtotime($cost[11]));
				$Peak_Time_Summer_Start = idate(H,strtotime($cost[12]));
				$Peak_Time_Summer_Stop = idate(H,strtotime($cost[13]));
				$Peak_Time_Non_Summer_Start = idate(H,strtotime($cost[14]));
				$Peak_Time_Non_Summer_Stop = idate(H,strtotime($cost[15]));
				$Peak_Time_Non_Summer_Start2 = idate(H,strtotime($cost[16]));
				$Peak_Time_Non_Summer_Stop2 = idate(H,strtotime($cost[17]));
		
				$db = 'bwolff_eqoff';
				$EC_kWh='Energy_Consumption';
				$Peak_kW="Peak_kW";
				$Peak_kWh="Peak_kWh";
				$Off_Peak_kW="Off_Peak_kW";
				$Off_Peak_kWh="Off_Peak_kWh";
		
					$sql_update = "SELECT time, $EC_kWh FROM $table ORDER BY time DESC";
					$RESULT_update = mysql_query($sql_update);
					
					$rows_remaining = mysql_num_rows($RESULT_update);
					
					unset($time,$time_d,$kWh);
					
					while($row=mysql_fetch_array($RESULT_update))
					{
					$time[] = $row['time'];
					$time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));
					$kWh[] = $row["$EC_kWh"];
					}
					
					$countd=count($time_d);
					
					$i=-1;
					while($i<=$countd-2)
					{$i++;
					
						echo "<script language='javascript'> 
						file_text.innerHTML = 'ROWS REMAINING: ".$rows_remaining."'</script>";
					
						$rows_remaining--;
						
						if($i==0){continue;}
							
						if($kWh[$i]!==0 && $kWh[$i-1]>$kWh[$i])
						{	
							
							$Power=$kWh[$i-1] - $kWh[$i];
							$Power_time=$time[$i-1];
							$timestamp = strtotime($time_d[$i-1]);
							$month = idate(m,$timestamp);
							$hour = idate(H,$timestamp);
							$week = idate(w,$timestamp);
							
							if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							else
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Off_Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							
							$sql_check="SELECT time, $Peak_kWh, $Off_Peak_kWh FROM $table WHERE time='$Power_time'";
							$value_check=mysql_query($sql_check);
							$value=mysql_fetch_array($value_check);
							$O_kWh_value=$value["$Peak_kWh"];
							$OP_kWh_value=$value["$Off_Peak_kWh"];
							
							if ($O_kWh_value==0 && $OP_kWh_value==0)
							{
							$peak_query=mysql_query($sql_peak_kWh);	
								
								if(!$peak_query)
								{
								echo "$table $Power_time : $Power not updated"."</br>";
								}
								else
								{
								echo "$table $Power_time : $Power updated"."</br>";
								}
							}
							
							$interval=strtotime($time[$i-1]) - strtotime($time[$i]);
		
							if($interval> 150 && $interval< 210)
							{	if($i>4)
								{
									if($kWh[$i]>0 && $kWh[$i-5]>$kWh[$i])
									{
										$demand_15=($kWh[$i-5] - $kWh[$i])*4;
										$demand_15_time=$time[$i-5];
										$timestamp_15 = strtotime($time_d[$i-5]);
									}
								}
							}
							else if($interval>240 && $interval< 360)
							{	if($i>2)
								{
									if($kWh[$i]>0 && $kWh[$i-3]>$kWh[$i])
									{
										$demand_15=($kWh[$i-3] - $kWh[$i])*4;
										$demand_15_time=$time[$i-3];
										$timestamp_15 = strtotime($time_d[$i-3]);
									}
								}
							}
							else if($interval>780 && $interval< 1020)
							{	
								if($i>1)
								{
									if($kWh[$i]>0 && $kWh[$i-1]>$kWh[$i])
									{
										$demand_15=($kWh[$i-1] - $kWh[$i])*4;
										$demand_15_time=$time[$i-1];
										$timestamp_15 = strtotime($time_d[$i-1]);
									}
								}
							}
							else
							{
								$demand_15 = '';
							}
							if(!empty($demand_15))
							{
								$month = idate(m,$timestamp_15);
								$hour = idate(H,$timestamp_15);
								$week = idate(w,$timestamp_15);
								
								if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
								{
								$sql_peak_kW="UPDATE `$db`.`$table` SET `$Peak_kW`='$demand_15' WHERE `$table`.`time`='$demand_15_time';";
								}
								else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && ($hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop || $hour>=$Peak_Time_Non_Summer_Start2 && $hour<$Peak_Time_Non_Summer_Stop2))
								{
								$sql_peak_kW="UPDATE `$db`.`$table` SET `$Peak_kW`='$demand_15' WHERE `$table`.`time`='$demand_15_time';";
								}
								else
								{
								$sql_peak_kW="UPDATE `$db`.`$table` SET `$Off_Peak_kW`='$demand_15' WHERE `$table`.`time`='$demand_15_time';";
								}
								
								$sql_kW_check="SELECT time, $Peak_kW, $Off_Peak_kW FROM $table WHERE time='$demand_15_time'";
								$value_kW_check=mysql_query($sql_kW_check);
								$value=mysql_fetch_array($value_kW_check);
								$O_kW_value=$value["$Peak_kW"];
								$OP_kW_value=$value["$Off_Peak_kW"];
							
								if ($O_kW_value==0 && $OP_kW_value==0)
								{
								$kW_peak_query=mysql_query($sql_peak_kW);	
									
									if(!$kW_peak_query)
									{
									echo "$table $demand_15_time : $demand_15 not updated"."</br>";
									}
									else
									{
									echo "$table $demand_15_time : $demand_15 updated"."</br>";
									}
								}
								else
								{
								echo "$table $demand_15_time ALREADY UPDATED-- Peak kW : $O_kW_value -- Off_Peak_kW : $OP_kW_value"."</br>";
								continue;
								}
							}
						}
					}
					
				}
				if ($utility=="Virginia_Dominion_Rates")
				{
		
				$cost=mysql_fetch_array($rate_q);
		
				$Summer_Start = idate(m,strtotime($cost['Summer_Start']));
				$Summer_End = idate(m,strtotime($cost['Summer_End']));
				$Peak_Time_Summer_Start = idate(H,strtotime($cost['Peak_Time_Summer_Start']));
				$Peak_Time_Summer_Stop = idate(H,strtotime($cost['Peak_Time_Summer_Stop']));
				$Peak_Time_Non_Summer_Start = idate(H,strtotime($cost['Peak_Time_Non_Summer_Start']));
				$Peak_Time_Non_Summer_Stop = idate(H,strtotime($cost['Peak_Time_Non_Summer_Stop']));
		
				$db = 'bwolff_eqoff';
				$EC_kWh='Energy_Consumption';
				$Peak_kW="Peak_kW";
				$Peak_kWh="Peak_kWh";
				$Off_Peak_kW="Off_Peak_kW";
				$Off_Peak_kWh="Off_Peak_kWh";
				$RP_kVAR="Reactive_Power";
				$kVAR_30="30_Min_Reactive_kVAR";
					
					$sql_update = "SELECT time, $EC_kWh, $RP_kVAR FROM $table ORDER BY time DESC";
					$RESULT_update = mysql_query($sql_update);
					
					$rows_remaining = mysql_num_rows($RESULT_update);
					
					unset($time,$time_d,$kWh,$kVAR);
					
					while($row=mysql_fetch_array($RESULT_update))
					{
					$time[] = $row['time'];
					$time_d[] = date('Y-m-d H:i:s',strtotime($row['time'].$timezoneUTC));
					$kWh[] = $row["$EC_kWh"];
					$kVAR[] = $row["$RP_kVAR"];
					}
					
					$count30=count($time_30);
					$countd=count($time);
					
					$i=-1;
					
					while($i<=$countd-2)
					{$i++;
					
						echo "<script language='javascript'> 
							file_text.innerHTML = 'ROWS REMAINING: ".$rows_remaining."'</script>";
					
						$rows_remaining--;
						
						if($i==0)
						{
						continue;
						}
							
						if($kWh[$i]>0 && $kWh[$i-1]>$kWh[$i])
						{
							$Power=$kWh[$i-1] - $kWh[$i];
							$Power_time=$time[$i-1];
							$timestamp = strtotime($time_d[$i-1]);
							$month = idate(m,$timestamp);
							$hour = idate(H,$timestamp);
							$week = idate(w,$timestamp);
							
							if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && $hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop)
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							else
							{
							$sql_peak_kWh="UPDATE `$db`.`$table` SET `$Off_Peak_kWh`='$Power' WHERE `$table`.`time`='$Power_time';";
							}
							
							$sql_check="SELECT time, $Peak_kWh, $Off_Peak_kWh FROM $table WHERE time='$Power_time'";
							$value_check=mysql_query($sql_check);
							$value=mysql_fetch_array($value_check);
							$O_kWh_value=$value["$Peak_kWh"];
							$OP_kWh_value=$value["$Off_Peak_kWh"];
							
							if ($O_kWh_value==0 && $OP_kWh_value==0)
							{
								$peak_query=mysql_query($sql_peak_kWh);	
								
								if(!$peak_query)
								{
								echo "$table $Power_time : $Power not updated kWh"."</br>";
								}
								else
								{
								echo "$table $Power_time : $Power updated kWh"."</br>";
								}
							}
						}
						
						$interval=strtotime($time[$i-1]) - strtotime($time[$i]);
		
						if($interval>150 && $interval< 210)
						{	if($i>9)
							{
								$kVAR_Sum = $kVAR[$i-1]+$kVAR[$i-2]+$kVAR[$i-3]+$kVAR[$i-4]+$kVAR[$i-5]+$kVAR[$i-6]+$kVAR[$i-7]+$kVAR[$i-8]+$kVAR[$i-9]+$kVAR[$i-10];
								$kVAR_Avg = $kVAR_Sum/10;
								$kVAR_time = $time[$i-10];
								
								if($kWh[$i]>0 && $kWh[$i-10]>$kWh[$i])
								{
									$demand_30 = ($kWh[$i-10] - $kWh[$i])*2;
									$demand_30_time = $time[$i-10];
									$timestamp_30 = strtotime($time_d[$i-10]);
								}
							}
						}
						else if($interval>240 && $interval< 360)
						{	if($i>5)
							{
								$kVAR_Sum = $kVAR[$i-1]+$kVAR[$i-2]+$kVAR[$i-3]+$kVAR[$i-4]+$kVAR[$i-5]+$kVAR[$i-6];
								$kVAR_Avg = $kVAR_Sum/6;
								$kVAR_time = $time[$i-6];
								
								if($kWh[$i]>0 && $kWh[$i-6]>$kWh[$i])
								{
									$demand_30 = ($kWh[$i-6] - $kWh[$i])*2;
									$demand_30_time = $time[$i-6];
									$timestamp_30 = strtotime($time_d[$i-6]);
								}
							}
						}
						else if($interval>480 && $interval< 720)
						{	if($i>2)
							{
								$kVAR_Sum = $kVAR[$i-1]+$kVAR[$i-2]+$kVAR[$i-3];
								$kVAR_Avg = $kVAR_Sum/3;
								$kVAR_time = $time[$i-3];
								
								if($kWh[$i]>0 && $kWh[$i-3]>$kWh[$i])
								{
									$demand_30 = ($kWh[$i-3] - $kWh[$i])*2;
									$demand_30_time = $time[$i-3];
									$timestamp_30 = strtotime($time_d[$i-3]);
								}
							}
						}
						else if($interval>780 && $interval< 1020)
						{	
							if($i>1)
							{
								$kVAR_Sum = $kVAR[$i-1]+$kVAR[$i-2];
								$kVAR_Avg = $kVAR_Sum/2;
								$kVAR_time = $time[$i-2];
								
								if($kWh[$i]>0 && $kWh[$i-2]>$kWh[$i])
								{
									$demand_30 = ($kWh[$i-2] - $kWh[$i])*2;
									$demand_30_time = $time[$i-2];
									$timestamp_30 = strtotime($time_d[$i-2]);
								}
							}
						}
						else if($interval>1680 && $interval< 1920)
						{
							$kVAR_Sum = $kVAR[$i-1];
							$kVAR_Avg = $kVAR_Sum;
							$kVAR_time = $time[$i-1];
							
							if($kWh[$i]>0 && $kWh[$i-1]>$kWh[$i])
							{
								$demand_30 = ($kWh[$i-1] - $kWh[$i])*2;
								$demand_30_time = $time[$i-1];
								$timestamp_30 = strtotime($time_d[$i-1]);
							}
						}
						else
						{
							$kVAR_Sum = '';
							$demand_30 = '';
						}
						if(!empty($kVAR_Sum))
						{
						
							$sql_kVAR="UPDATE `$db`.`$table` SET `$kVAR_30`='$kVAR_Avg' WHERE `$table`.`time`='$kVAR_time' OR (`time`<'$kVAR_time' AND `time`>'$time_d[$i]');";
							$sql_check="SELECT time, $kVAR_30 FROM $table WHERE time='$kVAR_time'";
							$value_check=mysql_query($sql_check);
							$value=mysql_fetch_array($value_check);
							$kVAR_value=$value["$kVAR_30"];
									
							if ($kVAR_value==0)
							{
								$sql_kVAR_demand=mysql_query($sql_kVAR);	
							
								if(!$sql_kVAR_demand)
								{
								echo "$table $kVAR_time : $kVAR_Avg 30 Min Reactive kVAR not updated"."</br>";
								}
								else
								{
								echo "$table $kVAR_time : $kVAR_Avg 30 Min Reactive kVAR updated"."</br>";
								}
							}
						}
						if(!empty($demand_30))
						{
							$month = idate(m,$timestamp_30);
							$hour = idate(H,$timestamp_30);
							$week = idate(w,$timestamp_30);
							
							if($month>=$Summer_Start && $month<$Summer_End && $week<6 && $week>0 && $hour>=$Peak_Time_Summer_Start && $hour<$Peak_Time_Summer_Stop)
							{
							$sql_peak_kW="UPDATE `$db`.`$table` SET `$Peak_kW`='$demand_30' WHERE `$table`.`time`='$demand_30_time';";
							}
							else if(($month<$Summer_Start || $month>=$Summer_End) && $week<6 && $week>0 && $hour>=$Peak_Time_Non_Summer_Start && $hour<$Peak_Time_Non_Summer_Stop)
							{
							$sql_peak_kW="UPDATE `$db`.`$table` SET `$Peak_kW`='$demand_30' WHERE `$table`.`time`='$demand_30_time';";
							}
							else
							{
							$sql_peak_kW="UPDATE `$db`.`$table` SET `$Off_Peak_kW`='$demand_30' WHERE `$table`.`time`='$demand_30_time';";
							}
							
							$sql_kW_check="SELECT time, $Peak_kW, $Off_Peak_kW FROM $table WHERE time='$demand_30_time'";
							$value_kW_check=mysql_query($sql_kW_check);
							$value=mysql_fetch_array($value_kW_check);
							$O_kW_value=$value["$Peak_kW"];
							$OP_kW_value=$value["$Off_Peak_kW"];
						
							if ($O_kW_value==0 && $OP_kW_value==0)
							{
								$kW_peak_query=mysql_query($sql_peak_kW);	
								
								if(!$kW_peak_query)
								{
								echo "$table $demand_30_time : $demand_30 not updated kW"."</br>";
								}
								else
								{
								echo "$table $demand_30_time : $demand_30 updated kW"."</br>";
								}
							}
							else
							{
							echo "$table $demand_30_time ALREADY UPDATED-- Peak kW : $O_kW_value -- Off_Peak_kW : $OP_kW_value"."</br>";
							break;
							}
						}
					}
		
				}
				
				$count_utables--;
			}
		}
		
		unset($utilitytable);
	}
}
// printf("</pre>\n");
?>
</html>