<?php

$db_host = "bwolff-eqoff.db.sonic.net";
$db_username = "bwolff_eqoff-rw";
$db_pass = "0a4e744c";
$db_name = "bwolff_eqoff";

@mysql_pconnect ("$db_host","$db_username","$db_pass") or die ("could not connect to mysql");
@mysql_select_db("$db_name") or die ("No database");

?>