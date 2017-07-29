<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Otherworld</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<?php
if (isset($_SESSION["night"])) {
	if ($_SESSION["night"]=="night") echo '<link rel="stylesheet" type="text/css" href="otherworld-night.css" />';
	else echo '<link rel="stylesheet" type="text/css" href="otherworld.css" />';
}
else echo '<link rel="stylesheet" type="text/css" href="otherworld.css" />';
?>
<link rel="stylesheet" type="text/css" href="aciTree/css/aciTree.css">
<script type="text/javascript" src="aciTree/js/jquery.min.js"></script>
<script type="text/javascript" src="aciTree/js/jquery.aciPlugin.min.js"></script>
<script type="text/javascript" src="aciTree/js/jquery.aciTree.min.js"></script>
<script type="text/javascript" src="jscolor.js"></script>
<?php
echo "<link rel='icon' type='image/x-icon' href='" . $gameRoot . "/favicon.ico'/>"
?>
</head>
<body>
<div class='wrapper'>
<div class='dark'>
<?php
ptag ("a", "Otherworld", "href='index.php' class='light'");

ptag ("p", "(Logged in as " . $_SESSION['starfish'] . ".)", "class='inline_light'");
/*
echo "<ul class='right_no_marker'>\n";
//ptag ("li", "<a href='index.php?page=login' class='light'>logout</a>", "class='inline_li'");
echo "</ul>\n<br />";
*/
echo "</div></div>\n";

?>
