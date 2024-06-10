<?php
session_start();
include_once "ref/header.php";
date_default_timezone_set('UTC');
?>
<div class="table">
	<div style="text-align: center">
        <img src="/erms/imgs/marine-design-ops.png" width="239" style="padding: 5px;"/>
        <h2>Maritime Energy and Resource Monitoring</h2>
<table width="300" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC" style="text-align: left">
<tr>
<form name="form1" method="post" action="Auth/checklogin.php">
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#eee">
<tr>
<td colspan="3"><strong> Login </strong></td>
</tr>
<?php if(isset($_SESSION['error_message'])) { ?>
<tr>
<td colspan="3" style="color:red;"><?php echo $_SESSION['error_message'] ?></td>
</tr>
<?php } ?>
<tr>
<td width="78">Username</td>
<td width="6">:</td>
<td width="294"><input name="myusername" type="text" id="myusername"></td>
</tr>
<tr>
<td>Password</td>
<td>:</td>
<td><input name="mypassword" type=password id="mypassword"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td><input type="submit" name="Submit" value="Login"></td>
</tr>
</table>
</td>
</form>
</tr>
</table>
</div>
<!--<div style="
    position: absolute;
    bottom: 0;
    right: 0;
    left: 0;
    /*text-align: center;*/
    width: 100%;
    padding: 10px;
    border-top: 1px solid #eee;">
  <div><img src="/erms/imgs/mdo-logo.jpeg" width="125" style="margin-bottom: -5px"/><span style="font-size: 12px; color: #888"></span></div>
</div>-->
<?php
if(isset($_SESSION['error_message'])) {
  unset($_SESSION['error_message']);
}
?>
