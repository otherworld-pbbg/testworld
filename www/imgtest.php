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
	
	for ($i = 0;$i<$tree1;$i++) $array[] = "tree1";
	for ($i = 0;$i<$bush1;$i++) $array[] = "bush1";
	for ($i = 0;$i<$bush2;$i++) $array[] = "bush2";
	for ($i = 0;$i<$bush3;$i++) $array[] = "bush3";
	for ($i = 0;$i<$rock1;$i++) $array[] = "rock1";
	for ($i = 0;$i<$rock2;$i++) $array[] = "rock2";
	for ($i = 0;$i<$rock3;$i++) $array[] = "rock3";
	for ($i = 0;$i<$rock4;$i++) $array[] = "rock4";
	for ($i = 0;$i<$rock5;$i++) $array[] = "rock5";
	for ($i = 0;$i<$rock6;$i++) $array[] = "rock6";
	
	if (empty($array)) $array[] = "rockmoss1";
	
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

$stamp_images = array(
	array("name" => "tree1", "x1" => 20, "y1" => 80, "x2" => 70, "y2" => 100 ),
	array("name" => "bush1", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "bush2", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "bush3", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rock1", "x1" => 0, "y1" => 80, "x2" => 25, "y2" => 100 ),
	array("name" => "rock2", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rock3", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rock4", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rock5", "x1" => 0, "y1" => 80, "x2" => 90, "y2" => 100 ),
	array("name" => "rock6", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockmoss1", "x1" => 0, "y1" => 80, "x2" => 25, "y2" => 100 ),
	array("name" => "rockmoss2", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockmoss3", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockmoss4", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockmoss5", "x1" => 0, "y1" => 80, "x2" => 90, "y2" => 100 ),
	array("name" => "rockmoss6", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockwater1", "x1" => 0, "y1" => 80, "x2" => 25, "y2" => 100 ),
	array("name" => "rockwater2", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockwater3", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockwater4", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockwater5", "x1" => 0, "y1" => 80, "x2" => 90, "y2" => 100 ),
	array("name" => "rockwater6", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockgrass1", "x1" => 0, "y1" => 80, "x2" => 25, "y2" => 100 ),
	array("name" => "rockgrass2", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 ),
	array("name" => "rockgrass3", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockgrass4", "x1" => 0, "y1" => 80, "x2" => 45, "y2" => 100 ),
	array("name" => "rockgrass5", "x1" => 0, "y1" => 80, "x2" => 90, "y2" => 100 ),
	array("name" => "rockgrass6", "x1" => 0, "y1" => 80, "x2" => 40, "y2" => 100 )
	);

for ($i = 0; $i< sizeof($stamp_images)-1; $i++) {
	$stamps[$stamp_images[$i]["name"]] = imagecreatefrompng("graphics/". $stamp_images[$i]["name"] . ".png");
}


$bottom = imagecreatetruecolor(1000,700);
$top = imagecreatetruecolor(1000,700);
$transparency = imagecolorallocatealpha($top, 0, 0, 0, 127);
imagefill($top, 0, 0, $transparency);

$spacing = rand(100,900);


for ($row = -20; $row<=680; $row+=70) {
		for ($i = -20; $i<1000; $i+=70) {
			$bottom = applyStamp($bottom, $ters[1], $i, $row);
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
		$new = snake($prevx, $prevy, $preva);
		if ($rotate) {
			$transparency = imagecolorallocatealpha($cur, 0, 0, 0, 127);
			$cur = imagerotate($cur, -$new["a"], $transparency);
		}
		$bottom = applyStamp($bottom, $cur, $new["x"], $new["y"]);
		$prevx = $new["x"];
		$prevy = $new["y"];
		$preva = $new["a"];
	}
}

$newmin=20;
for ($row = rand(0,$spacing/4); $row<600; $row+=rand(20,max($newmin,$spacing/4))) {
		for ($i = rand(0,$spacing); $i<900; $i+=rand($newmin,$spacing)) {
			$stampname = selectStamp($vegeLevel, $rowLevel);
			$entry = searchSingle($stamp_images, "name", $stampname);
			$top = applyStamp($top, $stamps[$stampname], $i, $row);
			$newmin = $entry["x2"];
		}
}

applyStamp($bottom, $top, 0, 0);

// Output and free memory
header('Content-type: image/png');
imagepng($bottom);
imagedestroy($bottom);
?>