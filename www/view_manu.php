<?php
include_once("../_private/class_build_menu2.inc.php");

include_once("../_private/conn.inc.php");

// what branch was requested?
$viewId = isset($_GET['sel_item']) ? $_GET['sel_item'] : null;
$userId = isset($_GET['userid']) ? $_GET['userid'] : null;
$charId = isset($_GET['charid']) ? $_GET['charid'] : null;

$BuildMenu = new BuildMenu2($mysqli, $userId, $charId);
$BuildMenu->printObjectManufacturing($viewId);

?>
