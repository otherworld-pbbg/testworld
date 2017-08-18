<?php
//registration
$privateRoot = "../_private";

include_once "root.inc.php";
include_once $privateRoot . "/conn.inc.php";
include_once $privateRoot . "/generic.inc.php";

$teststring = mysql_real_escape_string($_POST['username']);

$check = checkFreeUsername($mysqli, $teststring);
echo $check;
?>
