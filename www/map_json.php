<?php
session_start();
include_once "../_private/test.inc.php";
include_once "../_private/class_perlin.inc.php";
include_once "../_private/generic.inc.php";
include_once("../_private/conn.inc.php");

function applyStamp($image, $address, $x, $y, $d) {
	$stamp = imagecreatefrompng($address);
	imagealphablending($stamp, false);
	imagesavealpha($stamp, true);
	imagefilter($stamp, IMG_FILTER_BRIGHTNESS, $d);
	imagecopy($image, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
	return $image;
}

if (isset($_GET["seed1"])) {
	$seed1 = setBint($_GET["seed1"], 1, 10000, rand(1,10000));
}
else $seed1 = rand(1,10000);

if (isset($_GET["seed2"])) {
	$seed2 = setBint($_GET["seed2"], 1, 10000, rand(1,10000));
}
else $seed2 = rand(1,10000);

if (isset($_GET["x"])) {
	$minx = setBint($_GET["x"], 0, 1000, 0);
}
else $minx = 0;

if (isset($_GET["y"])) {
	$miny = setBint($_GET["y"], 0, 1000, 0);
}
else $miny = 0;

$ok = true;

if ($ok) {
	$data = new Test();
	
	if (!$data) {
		$image = imagecreate(500, 120);
		$background = imagecolorallocate($image, 0, 0, 0);
		$text_color = imagecolorallocate($image, 255, 0, 0 );
		imagestring($image, 4, 30, 25, "This location hasn't been unlocked", $text_color);
		header('Content-type: image/png');
		imagepng($image);
		imagedestroy($image);
	}
	else {
		$visionRange = 7;
		$gridsize = $visionRange*2+1;
		$space = 90;
		$wholegrid = 1000;
		$data->seed1 = $seed1;
		$data->seed2 = $seed2;
		$altmap = new Perlin($data->seed1);
		$materialmap = new Perlin($data->seed2);
		
		//$minx = $pos->lx-$visionRange;
		//$miny = $pos->ly-$visionRange;
		
		$array = array();
		for($y=0; $y<$gridsize; $y++) {
			for($x=0; $x<$gridsize; $x++) {
				$num = $altmap->noise($x+$minx,$y+$miny,0,$data->smooth);
				$num2 = $materialmap->noise($x+$minx,$y+$miny,0,25);
				
				$raw = ($num/2)+.5;
				$raw2 = ($num2/2)+.5;
				//Compass directions range from -1 to 1 where 0 being no change
				//They lift or lower the corners of the map
				if ($data->north) {
					if ($y+$miny<($wholegrid/2)) {
						$raw += (($wholegrid/2)-($y+$miny))/$wholegrid*$data->north;
					}
				}
				if ($data->south) {
					if ($y+$miny>($wholegrid/2)) {
						$raw += (($y+$miny)-($wholegrid/2))/$wholegrid*$data->south;
					}
				}
				if ($data->west) {
					if ($x+$minx<($wholegrid/2)) {
						$raw += (($wholegrid/2)-($x+$minx))/$wholegrid*$data->west;
					}
				}
				if ($data->east) {
					if ($x+$minx>($wholegrid/2)) {
						$raw += (($x+$minx)-($wholegrid/2))/$wholegrid*$data->east;
					}
				}
				
				if ($raw < 0) $raw = 0;
				if ($raw > 1) $raw = 1;
				if ($raw2 < 0) $raw2 = 0;
				if ($raw2 > 1) $raw2 = 1;
				
				$raw2 = round($raw2*100);
				
				if ($raw2>90&&$data->organic>127) {
					$base = "moss";
					$rough = 3;
				}
				else if ($data->grass>$data->sand) {
					$base = "grass";
					$rough =6;
				}
				else if ($data->rock>$data->grass) {
					$base = "rock";
					$rough =4;
				}
				else if ($data->sand>$data->silt&&$data->grass>55) {
					$base = "gsand";
					$rough = 5;
				}
				else if ($data->sand>$data->silt) {
					$base = "sand";
					$rough = 2;
				}
				else {
					$base = "floor";
					$rough = 1;
				}
				
				if ($data->tree>55&&$raw2%3==0) {
					if (pointInTriangle($data->tree, $raw2, 127, 40, 255, 50, 255, 80)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_tree";
					}
					else if (pointInTriangle($data->tree, $raw2, 100, 40, 255, 30, 255, 90)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_pine";
					}
					else if (pointInTriangle($data->tree, $raw2, 55, 30, 255, 10, 255, 90)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_baobab";
					}
					else $material = "tile_" . $base . "_fern";;
				}
				else if (round(0.3529*$data->tree)==$raw2) $material = "tile_" . $base . "_baobab";
				else if ($data->bush>55&&$raw2%3==1) {
					if (pointInTriangle($data->bush, $raw2, 127, 40, 255, 50, 255, 80)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_bigbush";
					}
					else if (pointInTriangle($data->bush, $raw2, 100, 40, 255, 30, 255, 90)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_medbush";
					}
					else if (pointInTriangle($data->bush, $raw2, 55, 30, 255, 10, 255, 90)) {
						if ($data->organic>127) $base = "floor";
						$material = "tile_" . $base . "_smbush";
					}
					else $material = "tile_" . $base . "_shrub";
				}
				else if (round(0.3529*$data->bush)==$raw2) $material = "tile_" . $base . "_smbush";
				else if ($data->rock>55&&$raw2%3==2) {
					if (pointInTriangle($data->rock, $raw2, 127, 40, 255, 50, 255, 80)) $material = "tile_" . $base . "_bigrock";
					else if (pointInTriangle($data->rock, $raw2, 100, 40, 255, 30, 255, 90)) $material = "tile_" . $base . "_medrock";
					else if (pointInTriangle($data->rock, $raw2, 55, 30, 255, 10, 255, 90)) $material = "tile_" . $base . "_smrock";
					else $material = "tile" . $base;
				}
				else if (round(0.3529*$data->rock)==$raw2) $material = "tile_" . $base . "_smrock";
				else $material = "tile" . $base;
				
				if ($raw<$data->water/255) {
					$raw = $data->water/255;
					$material = "tilewater";
					$rough = 0;
				}
				
				$row[$x] = array(
					"x" => $x,
					"y" => $y,
					"raw" => $raw,
					"material" => $material,
					"rough" => $rough
					);
			}
			$array[] = $row;
		}
		
		echo json_encode($array);
	}
}
?>