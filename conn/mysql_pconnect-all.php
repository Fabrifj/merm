<?php

//$db_host = "bwolff-eqoff.db.sonic.net";
$db_host = "localhost";
$db_username = "bwolff_eqoff-all";
$db_pass = "457560b8";
$db_name = "bwolff_eqoff";

session_start();
$_SESSION['con'] = $con = mysql_connect ("$db_host","$db_username","$db_pass");
@mysql_pconnect ("$db_host","$db_username","$db_pass") or die ("could not connect to mysql");
@mysql_select_db("$db_name") or die ("No database");

?>
