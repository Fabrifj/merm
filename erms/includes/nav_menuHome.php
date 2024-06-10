<div id="menu-position">
    <ul id="erms_menu">
<?php
$shipClass = $_REQUEST['shipClass'];
$permittedShipClasses = $_SESSION['user_data']['permittedShipClasses'];
foreach($permittedShipClasses as $class => $group) {
?>
  <li title="<?php echo $group['name'] ?>"<?php if ($class == $shipClass) {echo ' class="selected"'; } ?>><a href="<?php echo $group['homepage']; ?>"><?php echo $group['name']; ?></a></li>
<?php
}
?>
    </ul>
</div>
