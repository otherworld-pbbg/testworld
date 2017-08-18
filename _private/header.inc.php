<!DOCTYPE html>
<html lang="en">
<head>
<title>Otherworld</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
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
<nav class="navbar navbar-inverse">
<div class='container-fluid'>
<div class="navbar-header">
<?php
ptag ("a", "Otherworld", "href='index.php' class='navbar-brand'");
echo "</div>";
echo '<ul class="nav navbar-nav navbar-right">';
ptag ("li", "<a href='index.php?page=register'><span class='glyphicon glyphicon-user'></span> Register</a>");
ptag ("li", "<a href='index.php?page=login'><span class='glyphicon glyphicon-log-in'></span> Login</a>");
echo "</ul>\n</div></nav>\n";
?>
<div class='container-fluid'>
