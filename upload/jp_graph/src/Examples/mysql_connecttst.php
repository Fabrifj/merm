<?php

$db_host = "localhost";
$db_username = "root";
$db_pass = "equate101";
$db_name = "test_database";

@mysql_connect ("$db_host","$db_username","$db_pass") or die ("could not connect to mysql");
@mysql_select_db("$db_name") or die ("No database");

?>