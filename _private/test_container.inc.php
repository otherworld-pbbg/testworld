<?php
include_once("class_container.inc.php");
include_once("class_character.inc.php");
include_once("local_map.inc.php");
include_once("class_global_map.inc.php");
include_once("class_time.inc.php");
include_once("class_obj.inc.php");
include_once("class_resource.inc.php");
include_once("generic.inc.php");

include_once "header.inc.php";

function getSuccess($chance) {
	$percent = round($chance*100);
	$rand = rand(0, 100);
	
	if ($rand < $percent) return true;
	return false;
}

function getScore() {
	$chance = 0.8;
	$counter = 0;
	$points = 0;
	while ($points<50) {
		$counter++;
		if (getSuccess($chance)) $points++;
	}
	return $counter;
}

function doLoop($count) {
	for ($i = 0; $i<$count; $i++) {
		$hours = getScore();
		echo $hours . "<br>";
		echo workdays($hours) . " days / ". years($hours) . " years <br>";
	}
}

function workdays($hours) {
	return round($hours/4);
}

function years($hours) {
	return round ($hours/4/288);
}

$obj = new Obj($mysqli, 0);

//category: 0 - tropical fruit, 1 - berry, 2 - root vegetable, 3 - flower, 4 - bean, 5 - citrus fruit, 6 - apple, 7 - drupes
$shape = $obj->generateShape(0);
$str = $obj->generateFlavor(0);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(0) . " fruit with a " . $shape["skin"] . ", " . $shape["texture"] . " skin. " . $str["string"]);
$shape = $obj->generateShape(1);
$str = $obj->generateFlavor(1);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(1) . " berry with a " . $shape["skin"] . ", " . $shape["texture"] . " skin. " . $str["string"]);
$shape = $obj->generateShape(5);
$str = $obj->generateFlavor(5);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(5) . " citrus fruit with a " . $shape["skin"] . ", " . $shape["texture"] . " zest. " . $str["string"]);
$shape = $obj->generateShape(6);
$str = $obj->generateFlavor(6);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(6) . " pome with a " . $shape["skin"] . ", " . $shape["texture"] . " skin. " . $str["string"]);
$shape = $obj->generateShape(7);
$str = $obj->generateFlavor(7);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(7) . " drupe with a " . $shape["skin"] . ", " . $shape["texture"] . " skin. " . $str["string"]);
$shape = $obj->generateShape(2);
$str = $obj->generateFlavor(2);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(2) . " root vegetable with a " . $shape["skin"] . ", " . $shape["texture"] . " peel. " . $str["string"]);
$shape = $obj->generateShape(3);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(3) . " flower with a " . $shape["texture"] . " texture.");
$shape = $obj->generateShape(4);
para("a " . $shape["size"] . ", " . $shape["shape"] . ", " . $obj->generateColor(4) . " bean with a " . $shape["skin"] . ", " . $shape["texture"] . " skin.");

?>