<?php

include_once "../conn/mysql_connect-all.php";

$file_array = glob("deviceclass*");
foreach ($file_array AS $file_array)
{
	$table = substr($file_array,0,13);
	$fh = fopen($file_array, 'r');
	while(!feof($fh))
	{ 
	  $char = fread($fh, 1);
	  if($char=="|")
	  {
		$line[] = fgets($fh);
	  }
	}
	foreach($line AS $key => $var)
	{
		$line[$key] = explode('|',$var);
	}

	foreach($line AS $key => $value)
	{	
		$Field = trim($line[$key][0]);
		$Type = $line[$key][1]; 
		$NULL = trim($line[$key][2]);
		$Key = trim($line[$key][3]);
		$Default = $line[$key][4];
		$Extra = $line[$key][5];
		
		if($key == "Field")
		{	
			$Field = trim($line[$key][0]);
			$Type = trim($line[$key][1]); 
			$NULL = trim($line[$key][2]);
			$Key = trim($line[$key][3]);
			$Default = trim($line[$key][4]);
			$Extra = trim($line[$key][5]);
			echo $table."</br>";
			echo $Field." | ".$Type." | ".$NULL." | ".$Key." | ".$Default." | ".$Extra."</br>";	
			$sql_table = "CREATE TABLE `$table` (`$Field` VARCHAR(64) NOT NULL,`$Type` VARCHAR(64) NOT NULL, `$NULL` VARCHAR(64) NOT NULL, `$Key` VARCHAR(64) NOT NULL, `$Default` VARCHAR(64) NOT NULL, `$Extra` VARCHAR(64) NOT NULL)";
			$RESULT_table = mysql_query($sql_table);
			if(!$RESULT_table)
			{
				echo "unable to create $table table";
			}
			continue;
		}
		echo $table."</br>";
		echo $Field." ".$Type." ".$NULL." ".$Key." ".$Default." ".$Extra."</br>";	
		$sql = "INSERT INTO `$table` VALUES('$Field','$Type','$NULL','$Key','$Default','$Extra')";
		$RESULT = mysql_query($sql);
		if(!$RESULT)
		{
			echo "could not insert values into $table"."</br>";
		}
	}
	unset($line);
}

?>