<?php
include_once("../_private/class_build_menu2.inc.php");

//$mysqli = new mysqli("localhost", "countd6_other", "bluegoo52", "countd6_ogreworld");
$mysqli = new mysqli("localhost", "root", "", "mygame");

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

// what branch was requested?
$viewId = isset($_GET['sel_item']) ? $_GET['sel_item'] : null;
$userId = isset($_GET['userid']) ? $_GET['userid'] : null;
$charId = isset($_GET['charid']) ? $_GET['charid'] : null;

$BuildMenu = new BuildMenu2($mysqli, $userId, $charId);
$BuildMenu->printObjectManufacturing($viewId);

?>
