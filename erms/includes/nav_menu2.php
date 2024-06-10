<div id="menu-position">
    <ul id="erms_menu">
    <li title="Energy Cost Overview"><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=month&user=<?php echo $_REQUEST['user'] ?>&module=mod0&ship=<?php echo $_REQUEST['ship']; ?>&shipClass=<?php echo $_REQUEST['shipClass']; ?>">Energy Cost Overview</a>
        </li>
        <li title="View a detailed Power &amp; Cost Analysis" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod1') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&todo=<?php echo $_REQUEST['todo'] ?>&start_date_time=<?php echo $VAL["date_value_start"] ?>&stop_date_time=<?php echo $VAL["date_value_end"]?>&module=mod1&ship=<?php echo $_REQUEST['ship']; ?>&shipClass=<?php echo $_REQUEST['shipClass']; ?>">Energy: Power &amp; Cost Analysis</a></li>
        <?php if ($shipDeviceClass[0] != 27) { ?>
        <li title="View Energy Meter Data"<?php if (stripos($_SERVER['REQUEST_URI'],'=mod3') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=<?php echo $VAL["display"] ?>&user=<?php echo $_REQUEST['user'] ?>&todo=<?php echo $_REQUEST['todo'] ?>&start_date_time=<?php echo $VAL["date_value_start"] ?>&stop_date_time=<?php echo $VAL["date_value_end"]?>&module=mod3&ship=<?php echo $_REQUEST['ship']; ?>&shipClass=<?php echo $_REQUEST['shipClass']; ?>">Energy Meter Data</a></li>
        <?php } ?>
        <li title="View a Monthly Report for any month" <?php if (stripos($_SERVER['REQUEST_URI'],'=mod6') !== false) {echo ' class="selected"';} ?>><a href="<?php echo $_SERVER["SCRIPT_NAME"] ?>?display=month&user=<?php echo $_REQUEST['user'] ?>&module=mod6&ship=<?php echo $_REQUEST['ship']; ?>&shipClass=<?php echo $_REQUEST['shipClass']; ?>">Monthly Reports</a></li>
    </ul>
</div>


