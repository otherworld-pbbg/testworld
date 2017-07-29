<?php

class LocalMap {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function readSoilMap($uid, $centerX, $centerY, $radius) {
		$minX = $centerX-$radius;
		$minY = $centerY-$radius;
		$maxX = $centerX+$radius;
		$maxY = $centerY+$radius;
		$sql = "SELECT `point_uid`, `local_x`, `local_y`, `sand`, `silt`, `clay` FROM `local_tiles_temp` WHERE `local_mapFK`=$uid AND `local_x`>=$minX AND `local_x`<=$maxX AND `local_y`>=$minY AND `local_y`<=$maxY ORDER BY `local_x`, `local_y`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {

			while($row = $result->fetch_array())
			{
				echo "clay: " . $row['clay'] . ", sand: " . $row['sand'] . ", silt: " . $row['silt'];
				echo "<br />";
			}
			return $result;
		}
		else {
			echo "Something went wrong.";
			return -1;
		}
	}
	
	public function generateRandomSquare($uid, $x, $y) {
		
		$elevation_flux = 4;
		$moisture_flux = 20;
		$clay_flux = 5;
		$silt_flux = 5;
		$sand_flux = 5;
		$organic_flux = 5;
		$rocky_flux = 25;
		$herbs_flux = 25;
		$shrubs_flux = 100;
		$trees_flux = 100;
		
		$neighborx = $x-1;
		$neighbory = $y-1;
		
		$row = $this->getSquareStats($uid, $neighborx, $y);
		$row2 = $this->getSquareStats($uid, $x, $neighbory);
		
		if ($row&&$row2) {
			$neighbor_array = array(
			"elevation" => round(($row['elevation']+$row2['elevation'])/2),
			"moisture" => round(($row['moisture']+$row2['moisture'])/2),
			"clay" => round(($row['clay']+$row2['clay'])/2),
			"sand" => round(($row['sand']+$row2['sand'])/2),
			"silt" => round(($row['silt']+$row2['silt'])/2),
			"organic" => round(($row['organic']+$row2['organic'])/2),
			"rocky" => round(($row['rocky']+$row2['rocky'])/2),
			"herbs" => round(($row['herbs']+$row2['herbs'])/2),
			"shrubs" => round(($row['shrubs']+$row2['shrubs'])/2),
			"trees" => round(($row['trees']+$row2['trees'])/2),
			);
		}
		else if ($row) {
			$neighbor_array = array(
			"elevation" => $row['elevation'],
			"moisture" => $row['moisture'],
			"clay" => $row['clay'],
			"sand" => $row['sand'],
			"silt" => $row['silt'],
			"organic" => $row['organic'],
			"rocky" => $row['rocky'],
			"herbs" => $row['herbs'],
			"shrubs" => $row['shrubs'],
			"trees" => $row['trees'],
			);
		}
		else if ($row2) {
			$neighbor_array = array(
			"elevation" => $row2['elevation'],
			"moisture" => $row2['moisture'],
			"clay" => $row2['clay'],
			"sand" => $row2['sand'],
			"silt" => $row2['silt'],
			"organic" => $row2['organic'],
			"rocky" => $row2['rocky'],
			"herbs" => $row2['herbs'],
			"shrubs" => $row2['shrubs'],
			"trees" => $row2['trees'],
			);
		}
		else return -1;
		
		$new_array = array(
			"elevation" => $this->getRandInt($neighbor_array['elevation'], $elevation_flux, 0, 9999),
			"moisture" => $this->getRandInt($neighbor_array['moisture'], $moisture_flux, 0, 50),
			"clay" => $this->getRandInt($neighbor_array['clay'], $clay_flux, 0, 255),
			"sand" => $this->getRandInt($neighbor_array['sand'], $sand_flux, 0, 255),
			"silt" => $this->getRandInt($neighbor_array['silt'], $silt_flux, 0, 255),
			"organic" => $this->getRandInt($neighbor_array['organic'], $organic_flux, 0, 255),
			"rocky" => $this->getRandInt($neighbor_array['rocky'], $rocky_flux, 0, 255),
			"herbs" => $this->getRandInt($neighbor_array['herbs'], $herbs_flux, 0, 255),
			"shrubs" => $this->getRandInt($neighbor_array['shrubs'], $shrubs_flux, 0, 255),
			"trees" => $this->getRandInt($neighbor_array['trees'], $trees_flux, 0, 255),
			);
		
		$sql = "INSERT INTO `local_tiles_temp` (`local_mapFK`, `local_x`, `local_y`, `moisture`, `elevation`, `sand`, `silt`, `clay`, `organic`, `rocky`, `herbs`, `shrubs`, `trees`) VALUES (" . $uid . ", " . $x . ", " . $y. ", " . $new_array['moisture']. ", " . $new_array['elevation']. ", " . $new_array['sand']. ", " . $new_array['silt']. ", " . $new_array['clay']. ", " . $new_array['organic']. ", " . $new_array['rocky']. ", " . $new_array['herbs']. ", " . $new_array['shrubs']. ", " . $new_array['trees'] . ")";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1)
		return 1;//success
		else return -1;//something went wrong
	}
	
	public function getSquareStats($uid, $x, $y) {
		$sql = "SELECT `point_uid`, `local_x`, `local_y`, `moisture`, `elevation`, `sand`, `silt`, `clay`, `organic`, `rocky`, `herbs`, `shrubs`, `trees` FROM `local_tiles_temp` WHERE `local_mapFK`=$uid AND `local_x`=$x AND `local_y`=$y";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {

			$row = $result->fetch_array();
			return $row;
		}
		else {
			return -1;
		}	
	}
	
	public function getRandInt($base, $flux, $min, $max) {
		$newmin = max( round($base-($flux/2)), $min );
		$newmax = min( round($base+($flux/2)), $max);//uses the fluxuation unless it's out of range

		return rand($newmin, $newmax);
	}
	
	public function getNextXY($mapUid)
	{
		$sql = "SELECT max(`local_y`) AS 'maxy' FROM `local_tiles_temp` WHERE `local_mapFK`=$mapUid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = $result->fetch_array();
			$sql2 = "SELECT max(`local_x`) AS 'maxx' FROM `local_tiles_temp` WHERE `local_mapFK`=$mapUid AND `local_y`=" . $row['maxy'];
			$result2 = $this->mysqli->query($sql2);
			if (mysqli_num_rows($result2)) {
				$row2 = $result2->fetch_array();
				if ($row["maxy"]==99 && $row2["maxx"] ==99) return 0;
				else if ($row2["maxx"]==99 && $row["maxy"] <99) return $new_array = array("x" => 0, "y" => $row["maxy"]+1);//move to next row
				else return $new_array = array("x" => $row2["maxx"]+1, "y" => $row["maxy"]);
			}
			return -1;
		}
		return -1;
	}
	
	public function finishRow($mapUid) {
		$arr = $this->getNextXY($mapUid);
		if (!$arr) return $arr;
		for ($i=$arr["x"]; $i<100; $i++) {
			$success=$this->generateRandomSquare($mapUid, $i, $arr["y"]);
			if ($success) echo "Generated (" . $i . "," . $arr["y"] . ")<br>";
			else {
				echo "Fail";
				return -1;
			}
		}
		return 1;
	}
}
?>
