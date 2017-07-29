<?php
$im = imagecreatefrompng("graphics/RGB-map.png");

header('Content-Type: image/png');

imagepng($im);
imagedestroy($im);
?>
