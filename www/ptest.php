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

if (isset($_GET["x"])) {
	$xoff = setBint($_GET["x"], 0, 100000000, 0);
}
else $xoff = 0;

if (isset($_GET["y"])) {
	$yoff = setBint($_GET["y"], 0, 10000, 0);
}
else $yoff = 0;

$bob = new Perlin($seed);
$bill = new Perlin($seed+1);

$space = 70;
$gridsize = 20;

//echo "smooth: " . $smooth . "<br>";
//echo "seed: " . $seed. "<br>";
$materials = array(
	"roc",
	"san",
	"gras",
	"fer",
	"sub",
	"mos"
	);

$counter = 0;
$ranges = array();

for ($i = 0; $i<sizeof($materials); $i++) {
	$counter += rand(5,25);
	if ($i==sizeof($materials)-1) $ranges[] = 101;
	else $ranges[] = $counter;
}

$material = $materials[rand(0,sizeof($materials)-1)];

$array = array();
for($y=0; $y<$gridsize; $y+=1) {
	for($x=0; $x<$gridsize; $x+=1) {
		$num = $bob->noise($x+$xoff,$y+$yoff,0,$smooth);
		$num2 = $bill->noise($x+$xoff,$y+$yoff,0,20);
		
		$raw = ($num/2)+.5;
		$raw2 = ($num2/2)+.5;

		if ($raw < 0) $raw = 0;
		if ($raw > 1) $raw = 1;
		if ($raw2 < 0) $raw2 = 0;
		if ($raw2 > 1) $raw2 = 1;
		
		$raw2 = round($raw2*100);
		
		$r2 = 0;
		foreach ($ranges as $key => $r) {
			if ($r>$raw2) {
				$r2 = $key;
				break;
			}
		}
		
		$material = $materials[$r2];
		
		$array[] = array(
			"x" => $x,
			"y" => $y,
			"raw" => $raw,
			"material" => $material
			);
	}
}

$bottom = imagecreatetruecolor($gridsize*$space,$gridsize*$space-120);
//$color = imagecolorallocate($bottom, 50,50,30);
//imagefill ($bottom, 0, 0, $color);

$variation = 100;

foreach ($array as $info) {
	$d = round($info["raw"]*$variation-($variation*0.6));
	
	$bottom = applyStamp($bottom, 'graphics/' . $info["material"] . '1.png', $info["x"]*$space+($info["y"]*2)-60, round(50+$info["y"]*$space-($info["raw"]*$variation*2.5)-($info["x"]*2)), $d);
	$prevy = $info["y"];
}

header('Content-type: image/png');
imagepng($bottom);
imagedestroy($bottom);
?>