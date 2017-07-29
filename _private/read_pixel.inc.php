<?php
//$source = getGamePath() . "/graphics/resources/res_allium_fistulosum.png";
$source = getGamePath() . "/graphics/terrain/plants.png";

$im=imagecreatefrompng("$source");
$rgb=imagecolorat($im, 333, 100);
$r = ($rgb >> 16) & 0xFF;
$g = ($rgb >> 8) & 0xFF;
$b = $rgb & 0xFF;

echo "<p>red: " . $r . ", green: " . $g . ", blue " . $b . "</p>";

?>
