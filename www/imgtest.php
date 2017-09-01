<?php

include_once("../_private/generic.inc.php");

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

function selectStamp($vege, $row) {
	$possibilities = array();
	
	switch ($vege["green"]) {
	case 1: $bush3 = 30;
		$bush2 = 20;
		$bush1 = 1;
		break;
	case 2: $bush3 = 40;
		$bush2 = 30;
		$bush1 = 2;
		break;
	case 3: $bush3 = 50;
		$bush2 = 40;
		$bush1 = 3;
		break;
	case 4: $bush3 = 60;
		$bush2 = 50;
		$bush1 = 5;
		break;
	case 5: $bush3 = 70;
		$bush2 = 60;
		$bush1 = 10;
		break;
	case 6: $bush3 = 80;
		$bush2 = 70;
		$bush1 = 20;
		break;
	case 7: $bush3 = 70;
		$bush2 = 80;
		$bush1 = 30;
		break;
	case 8: $bush3 = 20;
		$bush2 = 100;
		$bush1 = 50;
		break;
	case 9: $bush3 = 10;
		$bush2 = 100;
		$bush1 = 70;
		break;
	default: $bush3 = 0;
	$bush2 = 0;
	$bush1 = 0;
	}
	
	switch ($vege["blue"]) {
	case 1:
		$tree1 = 1;
		break;
	case 2: $tree1 = 10;
		break;
	case 3: $tree1 = 40;
		break;
	case 4: $tree1 = 50;
		break;
	case 5: $tree1 = 60;
		break;
	case 6: $tree1 = 70;
		break;
	case 7: $tree1 = 80;
		break;
	case 8: 
		$tree1 = 90;
		break;
	case 9: $tree1 = 100;
		break;
	default: $tree1 = 0;
	}
	
	switch ($vege["red"]) {
	case 1: 
		$rock6 = 1;
		$rock5 = 0;
		$rock4 = 0;
		$rock3 = 0;
		$rock2 = 1;
		$rock1 = 10;
		break;
	case 2: $rock6 = 2;
		$rock5 = 0;
		$rock4 = 0;
		$rock3 = 0;
		$rock2 = 1;
		$rock1 = 20;
		break;
	case 3: $rock6 = 5;
		$rock5 = 0;
		$rock4 = 0;
		$rock3 = 1;
		$rock2 = 10;
		$rock1 = 30;
		break;
	case 4: $rock6 = 10;
		$rock5 = 0;
		$rock4 = 0;
		$rock3 = 5;
		$rock2 = 20;
		$rock1 = 40;
		break;
	case 5:
		$rock6 = 20;
		$rock5 = 0;
		$rock4 = 10;
		$rock3 = 20;
		$rock2 = 30;
		$rock1 = 40;
		break;
	case 6: $rock6 = 40;
		$rock5 = 1;
		$rock4 = 20;
		$rock3 = 40;
		$rock2 = 40;
		$rock1 = 30;
		break;
	case 7: $rock6 = 30;
		$rock5 = 10;
		$rock4 = 70;
		$rock3 = 60;
		$rock2 = 30;
		$rock1 = 20;
		break;
	case 8: $rock6 = 40;
		$rock5 = 30;
		$rock4 = 80;
		$rock3 = 50;
		$rock2 = 20;
		$rock1 = 20;
		break;
	case 9: $rock6 = 50;
		$rock5 = 50;
		$rock4 = 90;
		$rock3 = 40;
		$rock2 = 30;
		$rock1 = 20;
		break;
	default: $rock6 = 0;
		$rock5 = 0;
		$rock4 = 0;
		$rock3 = 0;
		$rock2 = 0;
		$rock1 = 10;
	}
	
	for ($i = 0;$i<$tree1;$i++) $array[] = 0;
	for ($i = 0;$i<$bush1;$i++) $array[] = 1;
	for ($i = 0;$i<$bush2;$i++) $array[] = 2;
	for ($i = 0;$i<$bush3;$i++) $array[] = 3;
	for ($i = 0;$i<$rock1;$i++) $array[] = 4;
	for ($i = 0;$i<$rock2;$i++) $array[] = 5;
	for ($i = 0;$i<$rock3;$i++) $array[] = 6;
	for ($i = 0;$i<$rock4;$i++) $array[] = 7;
	for ($i = 0;$i<$rock5;$i++) $array[] = 8;
	for ($i = 0;$i<$rock6;$i++) $array[] = 9;
	
	if (empty($array)) $array[] = 16;
	
	return $array[rand(0, sizeof($array)-1)];
}

$vegeLevel = array(
	"red" => rand(0,9),
	"green" => rand(0,9),
	"blue" => rand(0,9)
	);

$rowLevel = array(
	"red" => rand(0,9),
	"green" => rand(0,9),
	"blue" => rand(0,9)
	);

$ter1 = imagecreatefrompng('graphics/sand1.png');
$ter2 = imagecreatefrompng('graphics/grass1.png');
$ter3 = imagecreatefrompng('graphics/water1.png');

$ter22 = imagecreatefrompng('graphics/grass2.png');
$ter23 = imagecreatefrompng('graphics/grass3.png');

$ter12 = imagecreatefrompng('graphics/sand2.png');
$ter13 = imagecreatefrompng('graphics/sand3.png');

$ter4 = imagecreatefrompng('graphics/moss1.png');
$ter42 = imagecreatefrompng('graphics/moss2.png');
$ter43 = imagecreatefrompng('graphics/moss3.png');

$ters = array($ter1, $ter2, $ter3);
$sands = array($ter1, $ter12, $ter13);
$grasses = array($ter2, $ter22, $ter23);
$mosses = array($ter4, $ter42, $ter43);

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

$spacing = rand(100,900);


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
	
	$type = rand(0,2);
	if ($type==2) {
		$cur2 = $mosses;
		$rotate = false;
	}
	else {
		$cur2 = $sands;
		$rotate = false;
	}
	
	for ($i = 0; $i<$snakelength; $i++) {
		$cur = $cur2[rand(0,2)];
		imagesavealpha($cur, true);
		$transparency = imagecolorallocatealpha($cur, 0, 0, 0, 127);
		$new = snake($prevx, $prevy, $preva);
		if ($rotate) $cur = imagerotate($cur, -$new["a"], $transparency);
		$im = applyStamp($im, $cur, $new["x"], $new["y"]);
		$prevx = $new["x"];
		$prevy = $new["y"];
		$preva = $new["a"];
	}
}

for ($row = rand(0,$spacing/4); $row<600; $row+=rand(20,$spacing/4)) {
		for ($i = rand(0,$spacing); $i<900; $i+=rand(20,$spacing)) {
			$im = applyStamp($im, $stamps[selectStamp($vegeLevel, $rowLevel)], $i, $row);
		}
}

// Output and free memory
header('Content-type: image/png');
imagepng($im);
imagedestroy($im);
?>