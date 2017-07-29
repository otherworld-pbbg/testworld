<?php
include_once "header2.inc.php";
ptag("h2", "Object creator");


$selOptions = ptag("option", "Select type", "value='0' selected='selected'", "silent");

$res = $mysqli->query("SELECT `uid`, `name` FROM `o_presets` WHERE 1 ORDER BY `name`");

if (!$res) {
	para("Query failed.");
	exit;
}

echo "<form name=\"o_type\" action=\"index.php?page=objectCreator\" method=\"post\">";
while ($row = $res->fetch_object())
{
	$selOptions .= ptag("option", "$row->name", "value='$row->uid'", "silent");
}
echo "<p>";
ptag("select", "$selOptions", "name='obtype'");
echo "</p>";
ptag("h3", "Global x", "class='inline'");
echo "<p class='inline'>";
ptag("input", "", "type='text' name='globalx'");
echo "(0 - 19999)";
echo "</p>";
echo "<br>";
ptag("h3", "Global y", "class='inline'");
echo "<p class='inline'>";
ptag("input", "", "type='text' name='globaly'");
echo "(-5000 - 4999)";
echo "</p>";
echo "<br>";
ptag("h3", "Local x", "class='inline'");
echo "<p class='inline'>";
ptag("input", "", "type='text' name='localx'");
echo "(0-999)";
echo "</p>";
echo "<br>";
ptag("h3", "Local y", "class='inline'");
echo "<p class='inline'>";
ptag("input", "", "type='text' name='localy'");
echo "(0-999)";
echo "</p>";
echo "<br>";
ptag("input", "", "type='submit' value='Create'");

echo "</form>";

?>