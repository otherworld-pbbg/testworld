<?php
include_once "../_private/class_perlin.inc.php";
include_once "../_private/generic.inc.php";

function applyStamp($image, $address, $x, $y, $d) {
	$stamp = imagecreatefrompng($address);
	imagealphablending($stamp, false);
	imagesavealpha($stamp, true);
	imagefilter($stamp, IMG_FILTER_BRIGHTNESS, $d);
	imagecopy($image, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
	return $image;
}

if (isset($_GET["seed"])) {
	$seed = setBint($_GET["seed"], 0, 10000, rand(1,2000));
}
else $seed = rand(1,2000);

if (isset($_GET["smooth"])) {
	$smooth = setBint($_GET["smooth"], 2, 50, rand(10,50));
}
else $smooth = rand(10,90);

if (isset($_GET["lift"])) {
	$tilt = $_GET["lift"];
	if ($tilt!="n"&&$tilt!="s"&&$tilt!="e"&&$tilt!="w"&&$tilt!="ne"&&$tilt!="nw"&&$tilt!="se"&&$tilt!="sw") $tilt = NULL;
}
else $tilt = NULL;

$bob = new Perlin($seed);
$bill = new Perlin($seed+1);

$space = 70;
$gridsize = 30;

//echo "smooth: " . $smooth . "<br>";
//echo "seed: " . $seed. "<br>";
$materials = array(
	"roc",
	"san",
	"gras",
	"fer",
	"sub",
	"moss"
	);

$material = $materials[rand(0,sizeof($materials)-1)];

$array = array();
for($y=0; $y<$gridsize; $y+=1) {
	for($x=0; $x<$gridsize; $x+=1) {
		$num = $bob->noise($x,$y,0,$smooth);
		$num2 = $bill->noise($x,$y,0,20);
		
		$raw = ($num/2)+.5;
		$raw2 = ($num2/2)+.5;
		if ($tilt=="n"||$tilt=="ne"||$tilt=="nw") {
			if ($y<($gridsize/2)) {
				$raw += (($gridsize/2)-$y)/$gridsize;
			}
		}
		if ($tilt=="s"||$tilt=="se"||$tilt=="sw") {
			if ($y>($gridsize/2)) {
				$raw += ($y-($gridsize/2))/$gridsize;
			}
		}
		if ($tilt=="w"||$tilt=="nw"||$tilt=="sw") {
			if ($x<($gridsize/2)) {
				$raw += (($gridsize/2)-$x)/$gridsize;
			}
		}
		if ($tilt=="e"||$tilt=="ne"||$tilt=="se") {
			if ($x>($gridsize/2)) {
				$raw += ($x-($gridsize/2))/$gridsize;
			}
		}
		
		if ($raw < 0) $raw = 0;
		if ($raw > 1) $raw = 1;
		if ($raw2 < 0) $raw2 = 0;
		if ($raw2 > 1) $raw2 = 1;
		
		$raw2 = round($raw2*5);
		
		$material = $materials[$raw2];
		
		$array[] = array(
			"x" => $x,
			"y" => $y,
			"raw" => $raw,
			"material" => $material
			);
	}
}

$bottom = imagecreatetruecolor($gridsize*$space+100,$gridsize*$space);
//$color = imagecolorallocate($bottom, 50,50,30);
//imagefill ($bottom, 0, 0, $color);

$variation = 100;

foreach ($array as $info) {
	$d = round($info["raw"]*$variation-($variation*0.6));
	
	$bottom = applyStamp($bottom, 'graphics/' . $info["material"] . '1.png', $info["x"]*$space+50, round(150+$info["y"]*$space-($info["raw"]*$variation*2.5)), $d);
	$prevy = $info["y"];
}

header('Content-type: image/png');
imagepng($bottom);
imagedestroy($bottom);
?>