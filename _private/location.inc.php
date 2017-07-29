<?php
include_once "class_location.inc.php";
require_once "class_pointlocation.inc.php";
require_once "class_obj.inc.php";

$world = new Location($mysqli);
$testObject = new Obj($mysqli);

//para( $world->squaresPerLatitude(-200));

//$arr = $world->coordsToSquares(10000, 3000);

//para ( $arr["x"] . ", " . $arr["y"]);

//para ($world->countSquares());


/*
$pointLocation = new pointLocation();
$points = array("2000 0",
"6000 -4000",
"11600 0",
"16000 -2000",
"18000 -1400",
"17000 3000");
$polygon = $world->getPolygon(5);
// The last point's coordinates must be the same as the first one's, to "close the loop"
foreach($points as $key => $point) {
    echo "$key ($point) is " . $pointLocation->pointInPolygon($point, $polygon) . "<br>";
}

$arr = $world->getLocalMapCorner(8000, -701);
para($arr["x"] . ", " . $arr["y"]);

$world->setGlobalCoords(8000, -700);
$world->setLocalCoords(0, 0);

para("Squares on -700 " . $world->squaresPerLatitude(-700));
$arr2=$world->getObjectsWithinRange(3, 1);

for ($i=0; $i<sizeof($arr2); $i++) {
	para("id: " . $arr2[$i]["uid"] ." preset: ". $arr2[$i]["preset"] ." angle: ". $arr2[$i]["angle"] ." distance: ". $arr2[$i]["dist"]);
}

$world->setGlobalCoords(8001, -700);
$arr3=$world->getObjectsWithinRange(1003, 1);

for ($i=0; $i<sizeof($arr3); $i++) {
	para("id: " . $arr3[$i]["uid"] ." preset: ". $arr3[$i]["preset"] ." angle: ". $arr3[$i]["angle"] ." distance: ". $arr3[$i]["dist"]);
}

$world->setGlobalCoords(9999.5, 0);
$world->setLocalCoords(0, 0);
$newpoint = $world->getRandomLocation(0, 99, 500, 500, 4);
if ($newpoint) para( $newpoint["gx"] .", ". $newpoint["gy"] ." : ". $newpoint["lx"] .", ". $newpoint["ly"] );
else para("Point out of range.");
*/

?>
