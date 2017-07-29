<?php
class MapPixel
{
	private $mysqli;

	public function __construct($mysqli) {
		
		$this->mysqli = $mysqli;
	}
	
	public function readColor($map, $x, $y) {
		$im=imagecreatefrompng($map);
		if (!$im) return false;
		else {
			$rgb=imagecolorat($im, $x, $y);
			return $rgb;
		}
	}
	
	public function readColorArray($map, $x, $y) {
		$im=imagecreatefrompng($map);
		if (!$im) return false;
		else {
			$rgb=imagecolorat($im, $x, $y);
			$returned = array();
			$returned["r"] = ($rgb >> 16) & 0xFF;
			$returned["g"] = ($rgb >> 8) & 0xFF;
			$returned["b"] = $rgb & 0xFF;
			return $returned;
		}
	}
	
	public function getSingleColor($rgb, $color) {
		if ($color=="r") $returned = ($rgb >> 16) & 0xFF;
		else if ($color=="g") $returned = ($rgb >> 8) & 0xFF;
		else if ($color=="b") $returned = $rgb & 0xFF;
		else {
			echo "Unrecognized parameter!";
			return -1;
		}
		return $returned;
	}
	
	public function toPercent($num) {
		$percent = round($num/255*100);
		return $percent;
	}
	
//$source = getGamePath() . "/graphics/resources/res_allium_fistulosum.png";
//$source = getGamePath() . "/graphics/terrain/plants.png";


}
?>
