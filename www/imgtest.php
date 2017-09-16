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

function selectStamp($vege, $row, $utype) {
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
	
	if ($utype == "moss") {
		for ($i = 0;$i<$rock1;$i++) $array[] = "rockmoss1";
		for ($i = 0;$i<$rock2;$i++) $array[] = "rockmoss2";
		for ($i = 0;$i<$rock3;$i++) $array[] = "rockmoss3";
		for ($i = 0;$i<$rock4;$i++) $array[] = "rockmoss4";
		for ($i = 0;$i<$rock5;$i++) $array[] = "rockmoss5";
		for ($i = 0;$i<$rock6;$i++) $array[] = "rockmoss6";
	}
	else if ($utype == "grass") {
		for ($i = 0;$i<$rock1;$i++) $array[] = "rockgrass1";
		for ($i = 0;$i<$rock2;$i++) $array[] = "rockgrass2";
		for ($i = 0;$i<$rock3;$i++) $array[] = "rockgrass3";
		for ($i = 0;$i<$rock4;$i++) $array[] = "rockgrass4";
		for ($i = 0;$i<$rock5;$i++) $array[] = "rockgrass5";
		for ($i = 0;$i<$rock6;$i++) $array[] = "rockgrass6";
	}
	else if ($utype == "water") {
		for ($i = 0;$i<$rock1;$i++) $array[] = "rockwater1";
		for ($i = 0;$i<$rock2;$i++) $array[] = "rockwater2";
		for ($i = 0;$i<$rock3;$i++) $array[] = "rockwater3";
		for ($i = 0;$i<$rock4;$i++) $array[] = "rockwater4";
		for ($i = 0;$i<$rock5;$i++) $array[] = "rockwater5";
		for ($i = 0;$i<$rock6;$i++) $array[] = "rockwater6";
	}
	else {
		for ($i = 0;$i<$rock1;$i++) $array[] = "rock1";
		for ($i = 0;$i<$rock2;$i++) $array[] = "rock2";
		for ($i = 0;$i<$rock3;$i++) $array[] = "rock3";
		for ($i = 0;$i<$rock4;$i++) $array[] = "rock4";
		for ($i = 0;$i<$rock5;$i++) $array[] = "rock5";
		for ($i = 0;$i<$rock6;$i++) $array[] = "rock6";
	}
	
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

$ter5 = imagecreatefrompng('graphics/under1.png');
$ter52 = imagecreatefrompng('graphics/under2.png');
$ter53 = imagecreatefrompng('graphics/under3.png');

$ter6 = imagecreatefrompng('graphics/bedrock1.png');
$ter62 = imagecreatefrompng('graphics/bedrock2.png');
$ter63 = imagecreatefrompng('graphics/bedrock3.png');

$ter7 = imagecreatefrompng('graphics/fern1.png');
$ter72 = imagecreatefrompng('graphics/fern2.png');
$ter73 = imagecreatefrompng('graphics/fern3.png');

$ter8 = imagecreatefrompng('graphics/leaves1.png');
$ter82 = imagecreatefrompng('graphics/leaves2.png');
$ter83 = imagecreatefrompng('graphics/leaves3.png');

$ters = array($ter1, $ter2, $ter3, $ter6);
$sands = array($ter1, $ter12, $ter13);
$grasses = array($ter2, $ter22, $ter23);
$mosses = array($ter4, $ter42, $ter43);
$unders = array($ter5, $ter52, $ter53);
$bedrocks = array($ter6, $ter62, $ter63);
$ferns = array($ter7, $ter72, $ter73);
$leaves = array($ter8, $ter82, $ter83);

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

for ($i = 0; $i< sizeof($stamp_images); $i++) {
	$stamps[$stamp_images[$i]["name"]] = imagecreatefrompng("graphics/". $stamp_images[$i]["name"] . ".png");
}

$width = isset($_GET['w']) ? $_GET['w'] : 1000;
$height = isset($_GET['h']) ? $_GET['h'] : 700;

$width = setBint($width, 300, 3000, 1000);
$height = setBint($height, 300, 2100, 700);

$bottom = imagecreatetruecolor($width,$height);
$top = imagecreatetruecolor($width,$height);
$transparency = imagecolorallocatealpha($top, 0, 0, 0, 127);
imagefill($top, 0, 0, $transparency);

$bg = rand(0,3);
for ($row = -20; $row<=$height-20; $row+=70) {
		for ($i = -20; $i<$width; $i+=70) {
			$bottom = applyStamp($bottom, $ters[$bg], $i, $row);
		}
}

$chance = rand(10,60);
$topstamps = array();
$snakescount = rand(max(5,round($width/3000)),max(10,round($width/30)));

for ($j = 0; $j<$snakescount; $j++) {
	$prevx = rand(0,$width);
	$prevy = rand(0,$height);
	$preva = rand(0,364);
	$snakelength = rand(10,200);
	
	$type = rand(0,6);
	if ($type==1) {
		$cur2 = $unders;
		$rotate = false;
		$utype = "undergrowth";
	}
	else if ($type==2) {
		$cur2 = $mosses;
		$rotate = false;
		$utype = "moss";
	}
	else if ($type==3) {
		$cur2 = $grasses;
		$rotate = false;
		$utype = "grass";
	}
	else if ($type==4) {
		$cur2 = $bedrocks;
		$rotate = false;
		$utype = "rock";
	}
	else if ($type==5) {
		$cur2 = $ferns;
		$rotate = false;
		$utype = "fern";
	}
	else if ($type==6) {
		$cur2 = $leaves;
		$rotate = false;
		$utype = "leaves";
	}
	else {
		$cur2 = $sands;
		$rotate = false;
		$utype = "sand";
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
		
		if (rand(0,100)<$chance) {
			$stampname = selectStamp($vegeLevel, $rowLevel, $utype);
			$a = round($new["x"]/30);
			$b = round($new["y"]/30);
			$name = "x" . $a . "_" . $b;
			$topstamps[$name] = array("name" => $stampname, "x" => $new["x"], "y" => $new["y"]-50);		
		}
	}
}

if (!empty($topstamps)) {
	aasort($topstamps, "y");
	foreach ($topstamps as $ts) {
		$stampname = $ts["name"];
		//echo $ts["x"] .",". $ts["y"] . "<br>";
		$top = applyStamp($top, $stamps[$stampname], $ts["x"], $ts["y"]);
	}
}
applyStamp($bottom, $top, 0, 0);

// Output and free memory
header('Content-type: image/png');
imagepng($bottom);
imagedestroy($bottom);
?>