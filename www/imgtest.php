<?php

function applyStamp($image, $stamp, $x, $y) {
	imagecopy($image, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
	return $image;
}

function snake($x, $y, $angle) {
	$dist = 20;
	
	$xshift = $dist*cos(deg2rad($angle));
	$yshift = $dist*sin(deg2rad($angle));
	
	$magnitude = rand(0,5);
	$array = array(
		array(0,5),
		array(6,15),
		array(16,30),
		array(31,50),
		array(51,75),
		array(76,100)
		);
	$achange = rand($array[$magnitude][0],$array[$magnitude][1]);
	if (rand(0,1)==1) $achange = -$achange;
	
	$na = $angle + $achange;
	
	if ($na>360) $na-=360;
	if ($na<0) $na+=360;
	
	return array(
		"x" => round($xshift+$x),
		"y" => round($yshift+$y),
		"a" => $na
		);
}

$ter1 = imagecreatefrompng('graphics/sand1.png');
$ter2 = imagecreatefrompng('graphics/grass1.png');
$ter3 = imagecreatefrompng('graphics/water1.png');

$ter22 = imagecreatefrompng('graphics/grass2.png');
$ter23 = imagecreatefrompng('graphics/grass3.png');

$ter12 = imagecreatefrompng('graphics/sand2.png');
$ter13 = imagecreatefrompng('graphics/sand3.png');

$ters = array($ter1, $ter2, $ter3);
$sands = array($ter1, $ter12, $ter13);
$grasses = array($ter2, $ter22, $ter23);

$stamp1 = imagecreatefrompng('graphics/tree1.png');
$stamp2 = imagecreatefrompng('graphics/bush1.png');
$stamp3 = imagecreatefrompng('graphics/bush2.png');
$stamp4 = imagecreatefrompng('graphics/bush3.png');
$stamp5 = imagecreatefrompng('graphics/rock1.png');
$stamp6 = imagecreatefrompng('graphics/rock2.png');
$stamp7 = imagecreatefrompng('graphics/rock3.png');
$stamp8 = imagecreatefrompng('graphics/rock4.png');
$stamp9 = imagecreatefrompng('graphics/rock5.png');
$stamp10 = imagecreatefrompng('graphics/rock6.png');
$stamp11 = imagecreatefrompng('graphics/rockmoss1.png');
$stamp12 = imagecreatefrompng('graphics/rockmoss2.png');
$stamp13 = imagecreatefrompng('graphics/rockmoss3.png');
$stamp14 = imagecreatefrompng('graphics/rockmoss4.png');
$stamp15 = imagecreatefrompng('graphics/rockmoss5.png');
$stamp16 = imagecreatefrompng('graphics/rockmoss6.png');
$stamp17 = imagecreatefrompng('graphics/rockwater1.png');
$stamp18 = imagecreatefrompng('graphics/rockwater2.png');
$stamp19 = imagecreatefrompng('graphics/rockwater3.png');
$stamp20 = imagecreatefrompng('graphics/rockwater4.png');
$stamp21 = imagecreatefrompng('graphics/rockwater5.png');
$stamp22 = imagecreatefrompng('graphics/rockwater6.png');
$stamp23 = imagecreatefrompng('graphics/rockgrass1.png');
$stamp24 = imagecreatefrompng('graphics/rockgrass2.png');
$stamp25 = imagecreatefrompng('graphics/rockgrass3.png');
$stamp26 = imagecreatefrompng('graphics/rockgrass4.png');
$stamp27 = imagecreatefrompng('graphics/rockgrass5.png');
$stamp28 = imagecreatefrompng('graphics/rockgrass6.png');
$stamps = array($stamp1, $stamp2, $stamp3, $stamp4, $stamp5, $stamp6, $stamp7, $stamp8, $stamp9, $stamp10, $stamp11, $stamp12, $stamp13, $stamp14, $stamp15, $stamp16, $stamp17, $stamp18, $stamp19, $stamp20, $stamp21, $stamp22, $stamp23, $stamp24, $stamp25, $stamp26, $stamp27, $stamp28);


$im = imagecreatetruecolor(1000,700);

$density = rand(100,900);

for ($row = -20; $row<=680; $row+=70) {
		for ($i = -20; $i<1000; $i+=70) {
			$im = applyStamp($im, $ters[1], $i, $row);
		}
}

$grasscount = rand(1,10);

for ($j = 0; $j<$grasscount; $j++) {
	$prevx = rand(0,1000);
	$prevy = rand(0,700);
	$preva = rand(0,364);
	$snakelength = rand(10,200);
	
	for ($i = 0; $i<$snakelength; $i++) {
		$cur = $sands[rand(0,2)];
		imagesavealpha($cur, true);
		$transparency = imagecolorallocatealpha($cur, 0, 0, 0, 127);
		$new = snake($prevx, $prevy, $preva);
		$cur = imagerotate($cur, -$new["a"], $transparency);
		$im = applyStamp($im, $cur, $new["x"], $new["y"]);
		$prevx = $new["x"];
		$prevy = $new["y"];
		$preva = $new["a"];
	}
}

for ($row = 0; $row<600; $row+=20) {
		for ($i = rand(0,$density); $i<900; $i+=rand(20,$density)) {
			$im = applyStamp($im, $stamps[rand(0,27)], $i, $row);
		}
}

// Output and free memory
header('Content-type: image/png');
imagepng($im);
imagedestroy($im);
?>