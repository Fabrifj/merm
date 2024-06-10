<?php

$db_host = "localhost";
//$db_username = "bwolff_eqoff-ro";
// temp change until we get a new user. Another quick note.
$db_username = "bwolff_eqoff-all";
$db_pass = "457560b8";
$db_name = "bwolff_eqoff";

//$db_host = "localhost";
//$db_username = "bwolff_eqoff-ro";
//$db_pass = "test";
//$db_name = "bwolff_eqoff";

session_start();
//$_SESSION['con'] = mysql_connect ("$db_host","$db_username","$db_pass");
$_SESSION['con'] = mysqli_connect("$db_host","$db_username","$db_pass");

@mysql_connect("$db_host","$db_username","$db_pass") or die ("ro could not connect to mysql");
@mysql_select_db("$db_name") or die ("RO No database" . mysql_error());

?>
