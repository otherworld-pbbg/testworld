<?php
require_once $privateRoot . "/class_pointlocation.inc.php";
//This probably isn't used anywhere anymore since it's a very old file back from when the world was a sphere
class Location
{
	
	private $radius = 3183.0988618379067153776752674503;//km, 10k/pi
	private $degreesKm = 0.018;//degrees per 1 km of surface
	private $mysqli;//db connection
	var $parentLoc=0;
	var $global_x = false;
	var $global_y = false;
	var $local_x = 0;
	var $local_y = 0;

	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	//setting functions
	public function setParent($parentLoc) {
		$this->parentLoc = $parentLoc;
	}
	
	public function setGlobalCoords($global_x, $global_y) {
		$this->global_x = $global_x;
		$this->global_y = $global_y;
	}
	
	public function setLocalCoords($local_x, $local_y) {
		$this->local_x = $local_x;
		$this->local_y = $local_y;
	}
	
	//get functions
	
	public function getParent() {
		return $this->parentLoc;
	}
	
	public function getGlobalCoords() {
		return "$this->global_x $this->global_y";
	}
	
	public function getLocalCoords() {
		return "$this->local_x $this->local_y";
	}
	
	//world functions
	
	//function squaresPerLatitude($squaresFromEquator)
//basically gives you the width of the ring and thus tells you how many 1km squares there are on that latitude

//function countSquares
//not really used, but can be used to calculate the amount of possible local maps in the whole game

//function getLocalMapCorner($longitude, $latitude)
//you give it the global coordinates and it gives you the upper left corner of the local map where the point resides

//function distanceOnLatitude($latitude, $distance)
//gives you how many x points are per given distance in km on that latitude

//function getPolygon($shape_id)
//gets the corner coordinates of a given polygon (in global)

//getObjectsWithinRange($radius, $general_type)
//first gets the current local map and other local maps within range, gets all the objects on those, then limits it down to ones that are actually within the given radius

//function convertToLocal($p_gx, $p_gy, $p_lx, $p_ly)
//used by the previous function, gets the coordinates in relation to the observer and also the angle and distance

//function getRandomLocation($gx_dev, $gy_dev, $lx_dev, $ly_dev, $limits=0)
//Gets a random location within a given area. It can return false if the random point doesn't match the given criteria

//function checkIfAreatype($areatype, $nottype, $point
//checks if it's a given type and/or not another given type in a given point

//function localToGlobal()
//does what it says it does

//function getAreatypes($x, $y)
//gets all areatypes in a given point


//function getBounds($shape_id)
//gets min and max x and y for a given shape. These are stored in the database
	
	function squaresPerLatitude($squaresFromEquator) {
	//$squaresFromEquator = surface km from equator, max 4999, negative if northern
	if ($squaresFromEquator>4999||$squaresFromEquator<-4999) return false;
	$angle =  $squaresFromEquator/(2*pi()*$this->radius) * 360;
	$circumference = floor( cos(deg2rad($angle))*$this->radius*2*pi() );
	return $circumference;
	}
	
	function countSquares()
	{
		$squareCount = 0;
		for ($i=-4999; $i<5000; $i++) {
			$new = $this->squaresPerLatitude($i);
			$squareCount += $new;
			//echo $new . "<br>";
		}
		return $squareCount;
	}
	
	function getLocalMapCorner($longitude, $latitude) {
		//$longitude ranges from 1 to 20000
		while ($longitude>20000) $longitude-=20000;
		while ($longitude<1) $longitude+=20000;
		//$latitude ranges from -4999 to 4999
		while ($latitude>4999) $latitude-=9998;
		while ($latitude<-4999) $latitude+=9998;
		
		$squaresOnLatitude = $this->squaresPerLatitude($latitude);
		$xPerSquare = 20000/$squaresOnLatitude;
		$squareNumber = floor($longitude/$xPerSquare);
		$coords = array(
			"x" => $squareNumber*$xPerSquare,
			"y" => floor($latitude)
			);
		return $coords;
	}
	
	function distanceOnLatitude($latitude, $distance) {
		//distance in kilometers
		$squaresOnLatitude = $this->squaresPerLatitude($latitude);
		if ($squaresOnLatitude) {
			$xpoints = $distance * (20000/$squaresOnLatitude);//amount of x points per kilometer
			return $xpoints;
		}
		else return false;
	}
	
	function getPolygon($shape_id)
	{
		$res = $this->mysqli->query("SELECT `x`, `y` FROM `shape_coords` WHERE `shapeFK`=$shape_id ORDER BY `uid`");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		else {
			$polygon = array();
			while ($row = $res->fetch_object())
			{
				$polygon[] = "$row->x $row->y";
			}
			$res->close();
			return $polygon;
		}
	}
	
	function getObjectsWithinRange($radius, $general_type) {
		//radius in metres
		$km = ceil($radius/1000);
		if ($km == 0) $km = 1;
		$xkm = $this->distanceOnLatitude($this->global_y, $km);
		
		$minx = $this->global_x - $xkm;
		$maxx = $this->global_x + $xkm;
		$miny = $this->global_y - $km;
		$maxy = $this->global_y + $km;

		
		$conditions = "`global_x`>=$minx AND `global_x`<=$maxx AND `global_y`>=$miny AND `global_y`<=$maxy";
		echo $conditions . "<br>";
		$res = $this->mysqli->query("SELECT `uid`, `presetFK`, `global_x`, `global_y`, `local_x`, `local_y` FROM `objects` WHERE `general_type`=$general_type AND `parent`=0 AND $conditions ORDER BY RAND() LIMIT 1000");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		$potential = array();
		while ($row = $res->fetch_object())
		{
			$potential[] = array(
				"uid" => $row->uid,
				"preset" => $row->presetFK,
				"gx" => $row->global_x,
				"gy" => $row->global_y,
				"lx" => $row->local_x,
				"ly" => $row->local_y
				);
		}
		$res->close();
		$withinRange = array();
		for ($i=0; $i<sizeof($potential); $i++) {
			//para("global x: " . $potential[$i]["gx"] . ", global y: " . $potential[$i]["gy"] . " local x: " . $potential[$i]["lx"] . ", local y: " . $potential[$i]["ly"] . "<br>" );
			$difference = $this->convertToLocal($potential[$i]["gx"], $potential[$i]["gy"], $potential[$i]["lx"], $potential[$i]["ly"]);
			//para($difference["xdiff"] . ", " . $difference["ydiff"] . " angle " . $difference["angle"] . " distance " . $difference["dist"] . "<br>");
			if ($difference["dist"]<($radius/1000)) {
				$withinRange[] = array(
					"uid" =>  $potential[$i]["uid"],
					"preset" =>  $potential[$i]["preset"],
					"angle" => $difference["angle"],
					"dist" => $difference["dist"]
					);
			}
		}
		return $withinRange;
	}
	
	function convertToLocal($p_gx, $p_gy, $p_lx, $p_ly) {
		$xdiff = $p_gx - $this->global_x;
		$ydiff = $p_gy - $this->global_y;
		
		$xdiff = $xdiff / $this->distanceOnLatitude($p_gy, 1);//actual kilometers
		$xdiff = $xdiff - ($this->local_x/1000) + ($p_lx/1000);
		$ydiff = $ydiff - ($this->local_y/1000) + ($p_ly/1000);
		if ($xdiff==0) {
			if ($ydiff>0) $angle=180;
			else $angle=0;
		}
		else if ($ydiff==0) {
			if ($xdiff>0) $angle=90;
			else $angle=270;
		}
		else {
			$angle = rad2deg( atan($ydiff/$xdiff));
			if ($xdiff>0) $angle+=90;
			if ($xdiff<0) $angle+=270;
		}
		$dist = sqrt( pow($xdiff, 2) + pow($ydiff,2) );
		
		return array("xdiff" => $xdiff, "ydiff" => $ydiff, "angle" => $angle, "dist" => $dist);
	}
	
	function getRandomLocation($gx_dev, $gy_dev, $lx_dev, $ly_dev, $limits=0) {
		//limits = 0 or not given - anything goes, 1 - mainland, 2 - coastal waters, 3 - coastal water or land, 4 - water
		$boundsG = array();
		$boundsG["x"] = array(
			"min" => floor(max($this->global_x - $gx_dev, 1)),
			"max" => ceil(min($this->global_x + $gx_dev, 20000))
			);
		$boundsG["y"] = array(
			"min" => floor(max($this->global_y - $gy_dev, -4999)),
			"max" => ceil(min($this->global_y + $gy_dev, 4999))
			);
		
		$boundsL = array();
		$boundsL["x"] = array(
			"min" => max($this->local_x - $lx_dev, 0),
			"max" => min($this->local_x + $lx_dev, 999)
			);
		$boundsL["y"] = array(
			"min" => max($this->local_y - $ly_dev, 0),
			"max" => min($this->local_y + $ly_dev, 999)
			);
		
		$randPoint = array(
			"gx" => rand($boundsG["x"]["min"],$boundsG["x"]["max"]),
			"gy" => rand($boundsG["y"]["min"],$boundsG["y"]["max"]),
			"lx" => rand($boundsL["x"]["min"],$boundsL["x"]["max"]),
			"ly" => rand($boundsL["y"]["min"],$boundsL["y"]["max"])
		);
		if ($gx_dev==0) $randPoint["gx"] = $this->global_x;//These are because if the original number had decimals, the random number would be an integer and thus not the same
		if ($gy_dev==0) $randPoint["gy"] = $this->global_y;
		if ($randPoint["lx"]+$randPoint["ly"]>0) {
			$corner = $this->getLocalMapCorner($randPoint["gx"],$randPoint["gy"]);//if local coords aren't 0,0, global coords should be at the corner of the local map
			$randPoint["gx"] = $corner["x"];
			$randPoint["gy"] = $corner["y"];
		}
		
		$rp = $randPoint["gx"] . " " . $randPoint["gy"];
		
		if ($limits == 1) {
			if ( $this->checkIfAreatype(1, 0, $rp) ) return $randPoint;
			else return false;
		}
		else if ($limits == 2) {
			if ($this->checkIfAreatype(2, 1, $rp)) return $randPoint;
			else return false;
		}
		else if ($limits == 3) {
			if ($this->checkIfAreatype(2, 0, $rp)) return $randPoint;
			else return false;
		}
		else if ($limits == 4) {
			if ($this->checkIfAreatype(0, 2, $rp)) return $randPoint;
			else return false;
		}
		else return $randPoint;	
	}
	
	function checkIfAreatype($areatype, $nottype, $point) {
		$ok = false;
		if ($areatype) {
			$res = $this->mysqli->query("SELECT `uid` FROM `shapes` WHERE `shape_type`=$areatype");
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return false;
			}
			$pointLoc = new pointLocation();
			while ($row = $res->fetch_object()) {
				$polygon = $this->getPolygon($row->uid);
				$status = $pointLoc->pointInPolygon($point, $polygon);
				if ($status!="outside") {
					$ok = true;
					break;
				}
			}
			$res->close();
			
		}
		else $ok = true;
		if ($nottype&&$ok) {
			$res = $this->mysqli->query("SELECT `uid` FROM `shapes` WHERE `shape_type`=$areatype");
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return false;
			}
			$pointLoc2 = new pointLocation();
			while ($row = $res->fetch_object()) {
				$polygon = $this->getPolygon($row->uid);
				$status = $pointLoc2->pointInPolygon($point, $polygon);
				if ($status!="outside") {
					$ok = false;
					break;
				}
			}
			$res->close();
		}
		return $ok;
	}
	
	function localToGlobal() {
		$coords = array(
			"x" => $this->global_x + $this->local_x * $this->distanceOnLatitude($this->global_y, 0.01),
			"y" => $this->global_y + $this->local_y * 0.01
			);
		return $coords;
	}
	
	function getAreatypes($x, $y) {
		if ($y<-4000) return 12;//arctic
		if ($y>4500) return 12;//antarctic
		
		$res = $this->mysqli->query("SELECT `uid`, `shape_type` FROM `shapes` WHERE `minx`<=$x AND `maxx`=>$x AND `miny`<=$y AND `maxy`=>$y");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		$found = false;
		$matches = array();
		$point = "$x $y";
		$pointLoc = new pointLocation();
		while ($row = $res->fetch_object()) {
			$polygon = $this->getPolygon($row->uid);
			$status = $pointLoc->pointInPolygon($point, $polygon);
			if ($status!="outside") {
				$found = true;
				$matches[] = array(
					"uid" => $row->uid,
					"type" => $row->shape_type
					);
			}
		}
		$res->close();
		if (!$found) return 0;//sea
	}
	
	function getBounds($shape_id) {
		$res = $this->mysqli->query("SELECT min(`x`) as minx, max(`x`) as maxx, min(`y`) as miny, max(`y`) as maxy FROM `shape_coords` WHERE `shapeFK`=$shape_id LIMIT 1");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		else {
			$row = $res->fetch_object();
			$bounds = array(
				"minx" => $row->minx,
				"maxx" => $row->maxx,
				"miny" => $row->miny,
				"maxy" => $row->maxy
				);
			$res->close();
			return $bounds;
		}
	}
}

?>
