<?php

function generatePoints($count, $min_radius, $max_radius, $cx, $cy) {
	$radius = bRand($min_radius, $max_radius, ($max_radius-$min_radius)/2);
	
	$points[] = array($cx +  $radius * cos(0), $cy +  $radius *  sin(0));          

for ($i = 1; $i < $count; $i += 1) {
	$radius = bRand($min_radius, $max_radius, $radius);
    $points[] = array($cx +  $radius * cos($i * 2 * PI() / $count), $cy +  $radius * sin($i * 2 * PI() / $count));
}
	return $points;
}

function bRand($min, $max, $prevValue) {
	$array = array();
	$range = $max - $min;
	$nrange = $range;
	while ($nrange>1) {
		$nrange = $range*2/3;
		$array[] = array($nrange, $range);
		$range = $nrange;
	}
	$rand1 = rand(0, sizeof($array)-1);
	$stray = rand($array[$rand1][0], $array[$rand1][1]);
	if (rand(0,1)) $stray = -$stray;
	$prevValue += $stray;
	return max($min,min($max, $prevValue));
}

function getBiasedStray($max) {
	if ($max<1) return 0;
	$array = array();
	$min = $max;
	while ($min>=1) {
		$min = $max * 2/3;
		$array[] = array(round($min), round($max));
		$max = $min;
	}
	
	$rand1 = rand(0,sizeof($array)-1);
	$stray = rand($array[$rand1][0], $array[$rand1][1]);
	if (rand(0,1)) $stray = -$stray;
	return $stray;
}

function getMidNormal($x1, $y1, $x2, $y2) {
	$newx = ($x1 + $x2) / 2;
	$newy = ($y1 + $y2) / 2;
	
	if ($x2-$x1 == 0) return array ("x" => $newx, "y" => $newy, "k" => 0);
	
	$k = ($y2 - $y1) / ($x2 - $x1);
	
	return array ("x" => $newx, "y" => $newy, "k" => $k);
}

function countNewPoint($maxstray, $infoarray) {
	$stray = getBiasedStray($maxstray);
	
	$alpha = atan($infoarray["k"]+rand(-5,5));
	
	$newx = sin($alpha)*$stray+$infoarray["x"];
	$newy = cos($alpha)*$stray+$infoarray["y"];
	
	return array($newx, $newy);
}

function farEnough($x1, $y1, $x2, $y2) {
	$dist = sqrt(pow($x2-$x1,2)+pow($y2-$y1,2));
	return $dist>6;
}

function complicate($points, $maxstray, $level) {
	$newArr[] = $points[0];
	
	for ($i = 1; $i< sizeof($points); $i++) {
		$infoarray = getMidNormal($points[$i-1][0], $points[$i-1][1], $points[$i][0], $points[$i][1]);
		$retArr = countNewPoint($maxstray/$level, $infoarray);
		
		$ok = true;
		for ($j = $i; $j > 1; $j--) {
			$ok = farEnough($points[$j-1][0], $points[$j-1][1], $retArr[0], $retArr[1]);
			if (!$ok) break;
		}
		if ($ok) $newArr[] = $retArr;
		$newArr[] = $points[$i];
	}
	
	$infoarray = getMidNormal($points[sizeof($points)-1][0], $points[sizeof($points)-1][1], $points[0][0], $points[0][1]);
	$retArr = countNewPoint($maxstray/$level, $infoarray);
	$newArr[] = $retArr;
	
	return $newArr;
}

$infoarray = getMidNormal(0, 0, 10, 10);
$retArr = countNewPoint(2, $infoarray);

$cx = 400;
$cy = 300;
$count = 20;
$min_radius = 100;
$max_radius = 300;

$points = generatePoints($count, $min_radius, $max_radius, $cx, $cy);

$maxstray = ($max_radius-$min_radius)/6;

for ($i = 1; $i < 10; $i++) {
	$newArr = complicate($points, $maxstray, $i);
	$points = $newArr;
}

?>

<canvas id="canvas" width="800" height="600"></canvas>

<script language="javascript">

var jsonObject = 
<?php
echo json_encode($newArr);
?>;

var canvas=document.getElementById("canvas");
var ctx = canvas.getContext('2d');

ctx.beginPath();
ctx.moveTo(jsonObject[0][0], jsonObject[0][1]);

for (var i = 1; i < Object.keys(jsonObject).length; i++) {
	ctx.lineTo(jsonObject[i][0], jsonObject[i][1]);
}
ctx.lineTo(jsonObject[0][0], jsonObject[0][1]);
ctx.strokeStyle = "#000000";
ctx.lineWidth = 1;
ctx.stroke();

</script>
