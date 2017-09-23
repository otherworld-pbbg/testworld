<?php
//Perlin's snake
include_once "../_private/class_perlin.inc.php";
include_once("../_private/generic.inc.php");

function psnake($seed, $step, $angle, $x, $y) {
	$dist = 5;
	
	$bob = new Perlin($seed);
	
	$xshift = $dist*cos(deg2rad($angle));
	$yshift = $dist*sin(deg2rad($angle));
	
	$p = $bob->random1D($step)*6;
	
	for ($i = -6; $i<6.1; $i ++) {
		if (round($p) == $i) {
			switch ($i) {
			case -6:
				$achange = ($p+5)*35-58;//p between -5.5 and -6
				break;
			case -5:
				$achange = ($p+4)*25-40;//p between -4.5 and -5.4
				break;
			case -4:
				$achange = ($p+3)*25-18;//p between -3.5 and -4.4
				break;
			case -3:
				$achange = ($p+2)*15-9;//p between -2.5 and -3.4
				break;
			case -2:
				$achange = ($p+1)*12;//p between -1.5 and -2.4
				break;
			case -1:
				$achange = $p*5;//p between -0.5 and -1.4
				break;
			case 0:
				$achange = $p*2.5;//p between -0.4 tp 0.4
				break;
			case 6:
				$achange = ($p-5)*35+58;//p between 5.5 and 6
				break;
			case 5:
				$achange = ($p-4)*25+40;//p between 4.5 and 5.4
				break;
			case 4:
				$achange = ($p-3)*25+18;//p between 3.5 and 4.4
				break;
			case 3:
				$achange = ($p-2)*15+9;//p between 2.5 and 3.4
				break;
			case 2:
				$achange = ($p-1)*12;//p between 1.5 and 2.4
				break;
			case 1:
				$achange = $p*5;//p between 0.5 and 1.4
				break;
			}
			break;
		}
	}
	//echo $step . " - " . $p . "<br>";
	$na = $angle + $achange;
	
	if ($na>360) $na-=360;
	if ($na<0) $na+=360;
	
	return array(
		"x" => round($xshift+$x),
		"y" => round($yshift+$y),
		"a" => $na
		);
}

$width = 800;
$height = 600;

if (isset($_GET["seed"])) {
	$seed = setBint($_GET["seed"], 0, 10000, rand(1,2000));
}
else $seed = rand(1,2000);

if (isset($_GET["x"])) {
	$prevx = setBint($_GET["x"], 0, $width, 0);
}
else $prevx = rand(0,$width);

if (isset($_GET["y"])) {
	$prevy = setBint($_GET["y"], 0, $height, 0);
}
else $prevy = rand(0,$height);

if (isset($_GET["a"])) {
	$preva = setBint($_GET["a"], 0, 360, 0);
}
else $preva = rand(0,365);

$image = imagecreate($width, $height);
$background = imagecolorallocate($image, 0, 0, 0);
$line_color = imagecolorallocate($image, rand(90,255), rand(90,255), rand(90,255));

for ($i = 0; $i<100; $i++) {
	$info = psnake($seed, $i, $preva, $prevx, $prevy);
	
	
	imageline($image, $prevx, $prevy, $info["x"], $info["y"], $line_color);
	
	$prevx = $info["x"];
	$prevy = $info["y"];
	$preva = $info["a"];
}

header("Content-type: image/png");
imagepng($image);
imagecolordeallocate($image, $background);
imagedestroy($image);

?>