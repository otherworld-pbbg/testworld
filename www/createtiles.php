<?php

function applyStamp($image, $stamp, $x, $y) {
	imagealphablending($image, true);
	imagesavealpha($image, true);
	imagecopy($image, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
	return $image;
}

function createTile($base, $top, $filename) {
	$im = imagecreatetruecolor(100,300);
	$transparency = imagecolorallocatealpha($im, 0, 0, 0, 127);
	imagefill($im, 0, 0, $transparency);
	
	$im = applyStamp($im, $base, 0, 0);
	$im = applyStamp($im, $top, 0, 0);
	
	imagepng($im, "$filename", NULL, NULL);
	imagedestroy($im);
}

$bases = array(
	"sand",
	"gsand",
	"grass",
	"moss",
	"rock",
	"floor"
	);
$tops = array(
	"baobab",
	"pine",
	"spruce",
	"tree",
	"smbush",
	"medbush",
	"bigbush",
	"smrock",
	"medrock",
	"bigrock",
	"shrub",
	"fern"
	);

foreach ($bases as $b) {
	foreach ($tops as $t) {
		$base = imagecreatefrompng("graphics/tile" . $b . ".png");
		$top = imagecreatefrompng("graphics/top" . $t . ".png");
		$filename = "tile_" . $b . "_" . $t . ".png";
		
		createTile($base, $top, $filename);
	}
}
?>