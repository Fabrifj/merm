 <ul id="erms_menu">
        <li title="View a detailed Water Usage" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod5') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&todo=<?php echo $_REQUEST['todo'] ?>&start_date_time=<?php echo $VAL["date_value_start"] ?>&stop_date_time=<?php echo $VAL["date_value_end"]?> &module=mod5">Potable Water Usage</a></li>
        <li title="View Water Cost Analysis"<?php if (stripos($_SERVER['REQUEST_URI'],'=mod3') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&module=mod3">Water Cost Analysis</a></li>
        <li title="View a Monthly Report for any month" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod6') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=month&user=<?php echo $_REQUEST['user'] ?>&module=mod6">Monthly Reports</a></li>
    </ul>


<!---
<div id="menu-position">
    <ul id="erms_menu">
        <li title="View a detailed Water Usage" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod5') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&module=mod5">Potable Water Usage</a></li>
        <li title="View Water Cost Analysis"<?php if (stripos($_SERVER['REQUEST_URI'],'=mod3') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&module=mod3">Water Cost Analysis</a></li>
        <li title="View a Monthly Report for any month" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod6') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=month&user=<?php echo $_REQUEST['user'] ?>&module=mod6">Monthly Reports</a></li>
    </ul>
</div>
--->
