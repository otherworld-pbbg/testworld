<?php
//Perlin's snake 2
include_once "../_private/class_perlin.inc.php";
include_once("../_private/generic.inc.php");

function psnake_destined($seed, $step, $angle, $x, $y, $dx, $dy) {
	$dist = 5;
	
	$bob = new Perlin($seed);
	
	
	
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
	
	$d = sqrt(pow($dy-$y,2)+pow($dx-$x,2));
	if ($d<$dist) $dist = $d;
	
	if ($d<20) $achange *= 0.5;
	else if ($d<50) $achange *= 0.75;
	else if ($d<100) $achange *= 0.90;
	
	$na = $angle + $achange;
	
	$d = sqrt(pow($dy-$y,2)+pow($dx-$x,2));
	if ($d<$dist) $dist = $d;
	
	$dangle = rad2deg(atan2($dy-$y,$dx-$x));
	
	$diff = $dangle - $na;
	while ($diff<-180) $diff += 360;
	while ($diff>180) $diff -= 360;
	
	if ($dist>0) $na += $diff*0.5/$dist;
	if ($d<20) $na = $dangle;
	
	if ($na>360) $na-=360;
	if ($na<0) $na+=360;
	
	$xshift = $dist*cos(deg2rad($na));
	$yshift = $dist*sin(deg2rad($na));
	
	//echo $d . " _ " . $dangle . "<br>";
	
	return array(
		"x" => round($xshift+$x),
		"y" => round($yshift+$y),
		"a" => $na,
		"d" => $d
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

if (isset($_GET["dx"])) {
	$dx = setBint($_GET["dx"], 0, $width, 0);
}
else $dx = rand(0,$width);

if (isset($_GET["dy"])) {
	$dy = setBint($_GET["dy"], 0, $height, 0);
}
else $dy = rand(0,$height);

$max = 2000;
$image = imagecreate($width, $height);
$background = imagecolorallocate($image, 0, 0, 0);
$line_color = imagecolorallocate($image, rand(90,255), rand(90,255), rand(90,255));
$dot_color = imagecolorallocate($image, 200, 50, 50);

$ready = false;
$counter = 0;

while (!$ready) {
	$info = psnake_destined($seed, $counter, $preva, $prevx, $prevy, $dx, $dy);
	imageline($image, $prevx, $prevy, $info["x"], $info["y"], $line_color);
	$counter++;
	if ($info["d"]<5||$counter==$max) $ready = true;
	else {
		$prevx = $info["x"];
		$prevy = $info["y"];
		$preva = $info["a"];
	}
}

imagefilledellipse ($image, $dx, $dy, 5, 5, $dot_color);

header("Content-type: image/png");
imagepng($image);
imagecolordeallocate($image, $background);
imagedestroy($image);

?>