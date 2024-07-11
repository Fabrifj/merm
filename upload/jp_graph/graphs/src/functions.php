<?php
function makeDate($dateValue)
{
    $my_date = date('Y-m-d H:i', strtotime($dateValue));
 	return $my_date;
}
?>