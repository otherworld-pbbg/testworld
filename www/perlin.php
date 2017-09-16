<?php
include_once "../_private/class_perlin.inc.php";

include_once "../_private/generic.inc.php";

//This currently generates a color field that varies on all channels. Earlier it varied only on the red channel and the green channel was the opposite of the red channel, while blue was static.
if (isset($_GET["seed"])) {
	$seed = setBint($_GET["seed"], 0, 10000, rand(1,2000));
}
else $seed = rand(1,2000);

if (isset($_GET["smooth"])) {
	$smooth = setBint($_GET["smooth"], 2, 99, rand(10,90));
}
else $smooth = rand(10,90);

if (isset($_GET["lift"])) {
	$tilt = $_GET["lift"];
	if ($tilt!="n"&&$tilt!="s"&&$tilt!="e"&&$tilt!="w"&&$tilt!="ne"&&$tilt!="nw"&&$tilt!="se"&&$tilt!="sw") $tilt = NULL;
}
else $tilt = NULL;

$bob = new Perlin($seed);
$bill = new Perlin($seed+1);
$peter = new Perlin($seed+2);

$gridsize = 125;

echo "smooth: " . $smooth . "<br>";
echo "seed: " . $seed. "<br>";

for($y=0; $y<$gridsize; $y+=1) {
	for($x=0; $x<$gridsize; $x+=1) {
		$num = $bob->noise($x,$y,0,$smooth);
		$num2 = $bill->noise($x,$y,0,$smooth);
		$num3 = $peter->noise($x,$y,0,$smooth);
		
		$raw = ($num/2)+.5;
		if ($tilt=="n"||$tilt=="ne"||$tilt=="nw") {
			if ($y<($gridsize/2)) {
				$raw += (($gridsize/2)-$y)/$gridsize;
			}
		}
		if ($tilt=="s"||$tilt=="se"||$tilt=="sw") {
			if ($y>($gridsize/2)) {
				$raw += ($y-($gridsize/2))/$gridsize;
			}
		}
		if ($tilt=="w"||$tilt=="nw"||$tilt=="sw") {
			if ($x<($gridsize/2)) {
				$raw += (($gridsize/2)-$x)/$gridsize;
			}
		}
		if ($tilt=="e"||$tilt=="ne"||$tilt=="se") {
			if ($x>($gridsize/2)) {
				$raw += ($x-($gridsize/2))/$gridsize;
			}
		}
		$raw = round($raw*12)/12;
		
		$raw2 = ($num2/2)+.5;
		$raw2 = round($raw2*12)/12;
		
		$raw3 = ($num3/2)+.5;
		$raw3 = round($raw3*12)/12;
		//if ($num == 0) $raw = 0;
		//else $raw = 1/abs( $num );
		
		//$raw = pow((5*$raw)-4,3)+.5;
		//$raw = 1-pow(50 * ($raw - 1), 2);
		
		//if ($raw > .9) $raw = 1;
		//else $raw = 0;
		if ($raw < 0) $raw = 0;
		if ($raw2 < 0) $raw2 = 0;
		if ($raw3 < 0) $raw3 = 0;
		
		if ($raw > 1) $raw = 1;
		
		$num = dechex( $raw*255 );
		$num2 = dechex( $raw2*255 );
		$num3 = dechex( $raw3*255 );
		
		if (strlen($num) < 2) $num = "0".$num;
		if (strlen($num2) < 2) $num2 = "0".$num2;
		if (strlen($num3) < 2) $num3 = "0".$num3;
		
		echo "<div style='background-color:#$num". $num2 . $num3 .";width:10px;display:inline-block'>a</div>";
	}
	echo "<br>";
}

/*
$bob = new Perlin(1);

$place = 0;

//for ($i=0; $i<100000; $i+=100) {
for ($i=0; $i<1000; $i++) {
	$num = round(($bob->random1D($i)/2)+.5,2);
	echo $num.'
';
	echo '';
	$place++;
}
*/
?>