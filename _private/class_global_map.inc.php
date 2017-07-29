<?php
include_once("class_map_pixel.inc.php");
include_once("generic.inc.php");

class GlobalMap
{
	private $mysqli;
	public $x;
	public $y;

	public function __construct($mysqli, $x, $y) {
		
		$this->mysqli = $mysqli;
		$this->x = $x;
		$this->y = $y;
	}
	
	function searchByFieldSingle($categories, $field, $value)
	{
		foreach($categories as $key => $category)
		{
			if ($category[$field] == $value) {
				return $categories[$key];
			}
		}
		return -1;
	}
	
	function getMajorPixel() {
		$pixX = floor($this->x/20);
		$pixY = floor(($this->y+5000)/20);
		return array("x" => $pixX, "y" => $pixY);
	}
	
	function getLocalMap ($x, $y) {
		$localX = floor($x/4);
		$localY = floor($y+5000)/4;
		return array("x" => $localX, "y" => $localY);
	}
	
	public function toPercent($num) {
		$percent = round($num/255*100);
		return $percent;
	}
	
	public function getNeighbors() {
		$north = array($this->x, $this->y-4);
		$east = array($this->x+4, $this->y);
		$south = array($this->x, $this->y+4);
		$west = array($this->x-4, $this->y);
		
		if ($north[1]<-5000) $north[1] = 4998;
		if ($east[0]>19998) $east[0] = 0;
		if ($south[1]>4998) $south[1] = -5000;
		if ($west[0]<0) $west[0] = 19998;
		
		return array(
			"n" => $north,
			"e" => $east,
			"s" => $south,
			"w" => $west
			);
	}
	
	function getAllTravelMultipliers($sailing) {
		$n_multi = $this->getTravelMultiplier ("n", $sailing);
		$e_multi = $this->getTravelMultiplier ("e", $sailing);
		$s_multi = $this->getTravelMultiplier ("s", $sailing);
		$w_multi = $this->getTravelMultiplier ("w", $sailing);
		$ne_multi = $this->getTravelMultiplier ("ne", $sailing);
		$se_multi = $this->getTravelMultiplier ("se", $sailing);
		$sw_multi = $this->getTravelMultiplier ("sw", $sailing);
		$nw_multi = $this->getTravelMultiplier ("nw", $sailing);
		
		return array(
			"n" => $n_multi,
			"e" => $e_multi,
			"s" => $s_multi,
			"w" => $w_multi,
			"ne" => $ne_multi,
			"se" => $se_multi,
			"sw" => $sw_multi,
			"nw" => $nw_multi
			);
	}
	
	function getTravelMultiplier ($direction, $sailing) {
		//direction = n, e, s, w, ne, se, sw, nw
		//sailing = true if in a boat
		(float) $multiplier = 1;
		$water = false;
		
		$targetX = $this->x;
		$targetY = $this->y;
		//s = y++, n= y-- e= x++, w = x--
		if ($direction == "n" || $direction == "ne" || $direction == "nw") {
			$targetY-=4;
			if ($targetY<-5000) $targetY=4996;
		}
		else if ($direction == "s" || $direction == "se" || $direction == "sw") {
			$targetY+=4;
			if ($targetY>4996) $targetY=-5000;
		}
		
		if ($direction == "e" || $direction == "ne" || $direction == "se") {
			$targetX+=4;
			if ($targetX>19996) $targetX=0;
		}
		else if ($direction == "w" || $direction == "nw" || $direction == "sw") {
			$targetX-=4;
			if ($targetX<0) $targetX=19996;
		}
		
		if ($direction=="nw"||$direction=="sw"||$direction=="ne"||$direction=="se") $multiplier = 1.41;
		
		$targetLocation = new GlobalMap($this->mysqli, $targetX, $targetY);
		
		//check if road exists
		
		//check water level. Not if road exists
		
		$targetWater = $this->toPercent($targetLocation->getLevel("water"));
		
		if ($targetWater>90) {
			$water = true;
			if (!$sailing) return -1;//not possible to enter water without a boat
		}
		else if ($sailing) return -1;//can't sail if water <=90
		else if ($targetWater>50) {
			//echo "You need duckboards.<br />";
			$multiplier += $targetWater/50;
		}
		else if ($targetWater>25) {
			$multiplier += $targetWater/150;
		}
		
		if (!$water)
		{
			//check difference in altitude. Still matters even if there's a road
			$currentAlt = $this->getLevel("altitude");
			$targetAlt = $targetLocation->getLevel("altitude");
			
			$altDiff = $targetAlt-$currentAlt;
			
			if ($altDiff>30||$altDiff<-30) $multiplier=-3;//too steep
			else if ($altDiff>=20||$altDiff<=-20) $multiplier=-2;//need a rope
			else if ($altDiff>2) $multiplier +=($altDiff/50);
			else if ($altDiff<-11) $multiplier -= ($altDiff/50);//since these are negative numbers, using - to cancel it out. Walking down a steep hill is still challenging
			else if ($altDiff<-2) $multiplier += ($altDiff/100);//these actually make the multiplier smaller since you go faster downhill
			
			//check plant obstacles. Will be ignored if road exists
			
			$targetGrass = $this->toPercent($targetLocation->getLevel("grass"));
			$targetBrush = $this->toPercent($targetLocation->getLevel("brush"));
			
			if ($targetGrass>20) {
				$multiplier += $targetGrass/200;
			}
			if ($targetBrush>30) {
				$multiplier += $targetBrush/200;
			}
		}
		
		return $multiplier;
	}
	
	function getLevel($name) {
		$arr = array(
			"water" => array("/graphics/terrain/water.png", "b"),
			"organic" => array("/graphics/terrain/organic.png", "g"),
			"rocky" => array("/graphics/terrain/rocky.png", "r"),
			"altitude" => array("/graphics/terrain/altitude.png", "r"),
			"grass" => array("/graphics/terrain/plants.png", "r"),
			"brush" => array("/graphics/terrain/plants.png", "g"),
			"trees" => array("/graphics/terrain/plants.png", "b"),
			"sand" => array("/graphics/terrain/soil.png", "r"),
			"silt" => array("/graphics/terrain/soil.png", "g"),
			"clay" => array("/graphics/terrain/soil.png", "b")
			);
		$c = $this->getMajorPixel();
		$target = new MapPixel($this->mysqli);
		$rgb = $target->readColor(getGamePath() . $arr[$name][0], $c["x"], $c["y"]);
		$level = $target->getSingleColor($rgb, $arr[$name][1]);
		return $level;
	}
	
	function getROWlevel() {
		$red = $this->getLevel("rocky");
		$green = $this->getLevel("organic");
		$blue = $this->getLevel("water");
		
		if ($red>229) $red_level = 9;
		else if ($red>203) $red_level = 8;
		else if ($red>179) $red_level = 7;
		else if ($red>153) $red_level = 6;
		else if ($red>127) $red_level = 5;
		else if ($red>101) $red_level = 4;
		else if ($red>60) $red_level = 3;
		else if ($red>30) $red_level = 2;
		else if ($red>10) $red_level = 1;
		else $red_level = 0;
		
		if ($green>229) $green_level = 9;
		else if ($green>203) $green_level = 8;
		else if ($green>179) $green_level = 7;
		else if ($green>153) $green_level = 6;
		else if ($green>127) $green_level = 5;
		else if ($green>101) $green_level = 4;
		else if ($green>60) $green_level = 3;
		else if ($green>30) $green_level = 2;
		else if ($green>10) $green_level = 1;
		else $green_level = 0;
		
		if ($blue>229) $blue_level = 8;
		else if ($blue>203) $blue_level = 7;
		else if ($blue>179) $blue_level = 6;
		else if ($blue>153) $blue_level = 5;
		else if ($blue>110) $blue_level = 4;
		else if ($blue>70) $blue_level = 3;
		else if ($blue>30) $blue_level = 2;
		else if ($blue>10) $blue_level = 1;
		else $blue_level = 0;
		
		return array(
			"red" => $red_level,
			"green" => $green_level,
			"blue" => $blue_level
			);
	}
	
	function getVegeLevel() {
		$red = $this->getLevel("grass");
		$green = $this->getLevel("brush");
		$blue = $this->getLevel("trees");
		
		if ($blue>229) $blue_level = 9;
		else if ($blue>203) $blue_level = 8;
		else if ($blue>179) $blue_level = 7;
		else if ($blue>153) $blue_level = 6;
		else if ($blue>127) $blue_level = 5;
		else if ($blue>101) $blue_level = 4;
		else if ($blue>60) $blue_level = 3;
		else if ($blue>30) $blue_level = 2;
		else if ($blue>10) $blue_level = 1;
		else $blue_level = 0;
		
		if ($green>229) $green_level = 9;
		else if ($green>203) $green_level = 8;
		else if ($green>179) $green_level = 7;
		else if ($green>153) $green_level = 6;
		else if ($green>127) $green_level = 5;
		else if ($green>101) $green_level = 4;
		else if ($green>60) $green_level = 3;
		else if ($green>30) $green_level = 2;
		else if ($green>10) $green_level = 1;
		else $green_level = 0;
		
		if ($red>229) $red_level = 9;
		else if ($red>203) $red_level = 8;
		else if ($red>179) $red_level = 7;
		else if ($red>153) $red_level = 6;
		else if ($red>127) $red_level = 5;
		else if ($red>101) $red_level = 4;
		else if ($red>60) $red_level = 3;
		else if ($red>30) $red_level = 2;
		else if ($red>10) $red_level = 1;
		else $red_level = 0;
		
		return array(
			"blue" => $blue_level,
			"green" => $green_level,
			"red" => $red_level
			);
	}
	
	function printROW() {
		$str = "";
		
		$arr = $this->getROWlevel();
		
		if ($arr["red"]==9) $str .= "The ground is basically bedrock, occasionally smooth, occasionally jagged and cracked. ";
		else if ($arr["red"]==8) $str .= "The area resembles a giant's playfield, with large boulders scattered around. ";
		else if ($arr["red"]==7) $str .= "Several large rocks can be found on this area, as well as tons of smaller ones. ";
		else if ($arr["red"]==6) $str .= "You can see medium-sized rocks scattered all around. ";
		else if ($arr["red"]==5) $str .= "The ground is half covered in rocks. ";
		else if ($arr["red"]==4) $str .= "The terrain is somewhat rocky. ";
		else if ($arr["red"]==3) $str .= "The terrain is quite rocky but they tend to be small. ";
		else if ($arr["red"]==2) $str .= "Rocks are rare and far between in these parts. ";
		else if ($arr["red"]==1) $str .= "The terrain is uncannily devoid of loose rocks. ";
		
		if ($arr["green"]==9) $str .= "The ground is covered in a thick layer of organic matter, dead leaves and such. ";
		else if ($arr["green"]==8) $str .= "The ground is covered in a medium layer of organic matter, dead leaves and such. ";
		else if ($arr["green"]==7) $str .= "The ground is covered in a thin layer of organic matter, dead leaves and such. ";
		else if ($arr["green"]==6) $str .= "There is a good amount of humus in the ground. ";
		else if ($arr["green"]==5) $str .= "There is an average amount of humus in the ground. ";
		else if ($arr["green"]==4) $str .= "There is a thin layer of humus on the ground. ";
		else if ($arr["green"]==3) $str .= "The earth is low on nutrients. ";
		else if ($arr["green"]==2) $str .= "The earth is very low on nutrients. ";
		else if ($arr["green"]==1) $str .= "The earth is seriously devoid of nutrients. ";
		
		if ($arr["blue"]==8) $str .= "You are surrounded by water.";
		else if ($arr["blue"]==7) $str .= "The surroundings are full of stagnant pools of water, making it hard to find a way around. Duckboards would be in order.";
		else if ($arr["blue"]==6) $str .= "Pools of water are common here, making you work hard to find a way around them. The land is likely to be swampy or muddy.";
		else if ($arr["blue"]==5) $str .= "Pools of water are scattered here and there.";
		else if ($arr["blue"]==4) $str .= "The land is swampy or muddy.";
		else if ($arr["blue"]==3) $str .= "The earth is moist.";
		else if ($arr["blue"]==2) $str .= "The earth is somewhat humid.";
		else if ($arr["blue"]==1) $str .= "The earth is very dry.";
		else $str .= "The earth is cracked and parched.";
		
		if ($str == "") $str = "You're in a very very dry environment with a lack of rocks or plant debris.";
		
		return $str;
	}
	
	function printVegetation() {
		$str = "";
		
		$arr = $this->getVegeLevel();
		
		if ($arr["blue"]==9) $str .= "The forest is dense enough to be called a jungle regardless of tree type. ";
		else if ($arr["blue"]==8) $str .= "The forest is very dense here. ";
		else if ($arr["blue"]==7) $str .= "The forest is rather dense here. ";
		else if ($arr["blue"]==6) $str .= "The forest is somewhat dense here. ";
		else if ($arr["blue"]==5) $str .= "There is a regular old forest here. ";
		else if ($arr["blue"]==4) $str .= "There is a sparse forest here. ";
		else if ($arr["blue"]==3) $str .= "There are some trees every now and then. ";
		else if ($arr["blue"]==2) $str .= "There are barely any trees. ";
		else if ($arr["blue"]==1) $str .= "It's very uncommon to see a tree around these parts. ";
		
		if ($arr["green"]==9) $str .= "The ground is entirely covered in bushes, making it a struggle to push through them. ";
		else if ($arr["green"]==8) $str .= "The ground is covered in a dense layer of bushes, with barely any paths between them. ";
		else if ($arr["green"]==7) $str .= "The bushes are rather dense here. ";
		else if ($arr["green"]==6) $str .= "Bushes are rather common around these parts. ";
		else if ($arr["green"]==5) $str .= "You can see some bushes scattered about. ";
		else if ($arr["green"]==4) $str .= "There are some bushes here and there. ";
		else if ($arr["green"]==3) $str .= "You occasionally see a bush every now and then. ";
		else if ($arr["green"]==2) $str .= "Bushes are scarce around here. ";
		else if ($arr["green"]==1) $str .= "Very rarely you might see some sort of a bush. ";
		
		if ($arr["red"]==9) $str .= "The ground is covered in a dense layer of low plants, making it unavoidable to step on them.";
		else if ($arr["red"]==8) $str .= "The ground is almost covered by low plants, making it hard to find a place to set your foot down.";
		else if ($arr["red"]==7) $str .= "Low plants and grasses are common around these parts.";
		else if ($arr["red"]==6) $str .= "Low plants and grasses are rather common around these parts.";
		else if ($arr["red"]==5) $str .= "Low plants and grasses cover about half of the ground.";
		else if ($arr["red"]==4) $str .= "Ground vegetation is somewhat common but sparce.";
		else if ($arr["red"]==3) $str .= "Ground vegetation is sparce and spaced wide apart.";
		else if ($arr["red"]==2) $str .= "You can see a tuft of grass or a low plant every now and then.";
		else if ($arr["red"]==1) $str .= "It's very rare to see even a tuft of grass or some sort of a shrub.";
		
		if ($str == "") $str = "The environment is almost if not completely devoid of vegetation, making it a desolate sight.";
		
		return $str;
	}
	
	function getPercentThree($a, $b, $c) {
		$sum = $a+$b+$c;
		$pa = round(($a/$sum)*100);
		$pb = round(($b/$sum)*100);
		$pc = round(($c/$sum)*100);
		return array($pa, $pb, $pc);
	}
	
	function getRgbThree($a, $b, $c) {
		$sum = $a+$b+$c;
		$pa = round(($a/$sum)*255);
		$pb = round(($b/$sum)*255);
		$pc = round(($c/$sum)*255);
		return array($pa, $pb, $pc);
	}
	
	function getTerrains($dbonly = false) {
		$arr = array();
		
		$filenames = array(
			array (23, "water"),
			array (14, "chaparal"),
			array (7, "deciduous"),
			array (10, "desert"),
			array (3, "fertile"),
			array (4, "grassland"),
			array (17, "mountains"),
			array (8, "rainforest"),
			array (9, "savanna"),
			array (13, "swamp"),
			array (5, "taiga"),
			array (11, "tundra")
			);
		
		$dbter = $this->getTerrainsDB();//comma separated list of numbers
		if ($dbter) {
			$arr2 = explode(",", $dbter);
			
			for ($n=0; $n<count($filenames);$n++)
			{//starting from 1 rules out the water that has no terrain file
				if (in_array($filenames[$n][0], $arr2)) $arr[] = $filenames[$n];
			}
			return $arr;
		}
		if (!$dbter&&$dbonly) return array(0, "undefined");
		
		$water = $this->getLevel("water");
		
		if ($water>230) $arr[] = array (23, "water");
		else {
			$target = new MapPixel($this->mysqli);
			$pixArr = $this->getMajorPixel($this->x, $this->y);
			for ($n=1; $n<count($filenames);$n++)
			{//starting from 1 rules out the water that has no terrain file
				$url = getGamePath() . "/graphics/terrain/ter-". $filenames[$n][1] . ".png";
				$rgb = $target->readColor($url, $pixArr["x"], $pixArr["y"]);
				$val = $target->getSingleColor($rgb, "g");
				if ($val>0) $arr[] = $filenames[$n];//the actual value doesn't matter as long as it's bigger than 0
			}
		}
		$this->recordTerrain($arr);
		return $arr;
	}
	
	function getTerrainsDB() {
		$sql = "SELECT `terrains` FROM `stored_terrains` WHERE `x`=$this->x AND `y`=$this->y LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			return $row[0];
		}
		return false;
	}
	
	function recordTerrain($arr) {
		$existing = $this->getTerrainsDB();
		if ($existing) return 1;//Already in the database
		
		$string = "";
		foreach ($arr as $entry) {
			if ($string!="") $string .= ",";
			$string .= $entry[0];
		}
		$sql = "INSERT INTO `stored_terrains` (`x`, `y`, `terrains`) VALUES ($this->x, $this->y, '$string')";
		$this->mysqli->query($sql);
		if ($this->mysqli->insert_id) return 2;//insert success
		return -1;//insert failed
	}
	
	function listTerrains() {
		$arr = $this->getTerrains();

		if (count($arr)==0) echo "undefined";
		else
		{
			for ($i=0; $i<count($arr); $i++) {
				if ($i>0) echo " / ";
				echo $arr[$i][1];
				if ($arr[$i][1]=="deciduous") echo " forest";
				if ($arr[$i][1]=="fertile") echo " land";
			}
		}
	}
	
	function getResources() {
		$possible = array();
		$pix = $this->getMajorPixel ($this->x, $this->y);
		//$sql = "SELECT `uid`, `name`, `url`, `color` FROM `natural` WHERE 1";
		$sql = "SELECT `uid`, `name`, `url`, `color` FROM `natural` WHERE `minx`<". $pix["x"] . " AND `miny`<" . $pix["y"] . " AND `maxx`>" . $pix["x"] . " AND `maxy`>" . $pix["y"];
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$possible[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"url" => $row[2],
					"letter" => $row[3]
					);
			}
			
		}
		else return 0;
		$resources = array();
		$target = new MapPixel($this->mysqli);
		
		for ($i=0; $i<count($possible); $i++) {
			if (!file_exists($possible[$i]["url"])) echo "Location data for resource " . $possible[$i]["uid"] . " doesn't exist. Please inform game administrator.";
			else {
				$rgb = $target->readColor($possible[$i]["url"], $pix["x"], $pix["y"]);
				$curQuantity = $target->getSingleColor($rgb, $possible[$i]["letter"]);
				if ($curQuantity>0) {
					$resources[] = array(
					"uid" => $possible[$i]["uid"],
					"name" => $possible[$i]["name"],
					"value" => $curQuantity
					);
				}
			}
		}
		return $resources;
	}
	
	function getResByCategory($category) {
		$resources = array();
		$natural = $this->getResources();
		if (!$natural) return -1;
		$sql = "SELECT `res_subtypes`.`uid`, `res_subtypes`.`name`, `res_subtypes`.`natural`, `gathered`, `maxPoints` FROM `res_subtypes` WHERE `category`=$category";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$result2 = $this->searchByFieldSingle($natural, "uid", $row[2]);
				if (is_array($result2)) {
					$resources[] = array(
						"res_uid" => $row[0],
						"name" => $row[1],
						"nat_uid" => $row[2],
						"gathered" => $row[3],
						"frequency" => $row[4]
						);
				}
			}
			return $resources;
		}
		return -1;
	}
	
	function printResources($resArr, $threshold) {
		$anyFound = 0;
		echo "<ul class='normal'>";
		for ($i=0;$i<count($resArr);$i++) {
			if ($resArr[$i]["value"]>$threshold) {
				ptag("li", $resArr[$i]["name"]);
				$anyFound = 1;
			}
		}
		echo "</ul>";
		if ($anyFound==0) para("You can't see any obvious resources but you have the feeling there is something hidden in here.");
		else if ($threshold>1) para("There might be something else hidden here that you can't see.");
	}
	
	function getCurrent() {
		$current = array();
		$minx = $this->x-100;
		$miny = $this->y-100;
		$maxx = $this->x+100;
		$maxy = $this->y+100;
		
		$sql = "SELECT `x`, `y`, `dir`, `speed` FROM `currents` WHERE `x`>$minx AND `y`>$miny AND `x`<$maxx AND `y`<$maxy ORDER BY `x`, `y` LIMIT 4";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$xdif = $row["x"]-$this->x;
				$ydif = $row["y"]-$this->y;
				$dif = sqrt(pow($xdif,2)+pow($ydif,2));
				$current[] = array(
					$dif, $row["dir"], $row["speed"], $xdif, $ydif
					);
			}
			for ($i=0;$i<count($current);$i++) {
				if ($i==0) {
					$foundDif = $current[$i][0];
					$closestDir = $current[$i][1];
					$closestSpeed = $current[$i][2];
					$index = $i;
				}
				else if ($foundDif>$current[$i][0]) {
					$foundDif = $current[$i][0];
					$closestDir = $current[$i][1];
					$closestSpeed = $current[$i][2];
					$index = $i;
				}
			}
			
			$retArr = array(
				"dir" => $closestDir,
				"speed" => $closestSpeed
				);
			
			if (count($current)>1) {
				$pointbehind = -1;
				for ($i=0;$i<count($current);$i++) {
					if ($i!=$index) {
						if ($current[$i][1]=="n"&&$current[$i][4]<0) $pointbehind=$i;
						if ($current[$i][1]=="w"&&$current[$i][3]<0) $pointbehind=$i;
						if ($current[$i][1]=="s"&&$current[$i][4]>0) $pointbehind=$i;
						if ($current[$i][1]=="e"&&$current[$i][3]>0) $pointbehind=$i;
						if ($current[$i][1]=="ne"&&($current[$i][4]<0||$current[$i][3]>0)) $pointbehind=$i;
						if ($current[$i][1]=="nw"&&($current[$i][3]<0||$current[$i][4]<0)) $pointbehind=$i;
						if ($current[$i][1]=="sw"&&($current[$i][4]>0||$current[$i][3]<0)) $pointbehind=$i;
						if ($current[$i][1]=="se"&&($current[$i][3]>0||$current[$i][4]>0)) $pointbehind=$i;
					}
				}
				if ($pointbehind>-1) {
					$speed1 = $current[$pointbehind][2];
					$speed2 = $current[$index][2];
					$sum = $current[$pointbehind][0]+$current[$index][0];
					
					$dif=$current[$pointbehind][0]-$current[$index][0];
					$newSpeed = $speed1*(($sum-$dif)/$sum) + $speed2*($dif/$sum);
					
					$retArr["speed"] = $newSpeed;
				}
			}
			return $retArr;
		}
		else return 0;
	}
	
	function currentVerbal() {
		$retStr = "";
		$dir = "";
		$res = $this->getCurrent();
		if ($res==0) $retStr = "The water doesn't flow in any particular direction.";
		else {
			if ($res["dir"]=="n") $dir = "north";
			if ($res["dir"]=="e") $dir = "east";
			if ($res["dir"]=="s") $dir = "south";
			if ($res["dir"]=="w") $dir = "west";
			if ($res["dir"]=="ne") $dir = "northeast";
			if ($res["dir"]=="se") $dir = "southeast";
			if ($res["dir"]=="sw") $dir = "southwest";
			if ($res["dir"]=="nw") $dir = "northwest";
			
			if ($res["speed"]>25) $retStr = "The rapids rush " . $dir . " with powerful strength.";
			else if ($res["speed"]>15) $retStr = "The current charges " . $dir . " with high speed.";
			else if ($res["speed"]>10) $retStr = "The current rushes " . $dir . " at running speed.";
			else if ($res["speed"]>5) $retStr = "The current rushes " . $dir . " with a hurried pace.";
			else if ($res["speed"]>3) $retStr = "The current flows " . $dir . " at walking speed.";
			else if ($res["speed"]>2) $retStr = "The current flows " . $dir . " at casual strolling speed.";
			else if ($res["speed"]>1) $retStr = "The current lurches " . $dir . " at leisure pace.";
			else $retStr = "The water is barely moving, flowing lazily " . $dir . " at barely detectable speed.";
		}
		return $retStr;
	}
	
	function loadResources($natural, $threshold) {
		if ($natural == 0) return false;
		$sql = "SELECT `local_res`.`uid`, `hidden`, `name`, `plural`, `category` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `x`=$this->x AND `y`=$this->y AND `local_res`.`amount`>0 ORDER BY `hidden`, `name`";
		$localRes = array();
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$localRes[] = array(
					"uid" => $row[0],
					"hidden" => $row[1],
					"name" => $row[2],
					"plural" => $row[3],
					"category" => $row[4]
					);
			}
		}
		if ($localRes) return $localRes;
		//else continues to create a resource pool
		$visible = "";
		$hidden = "";
		for ($i=0;$i<count($natural);$i++) {
			if ($natural[$i]["value"]>$threshold) {
				if ($visible == "") $visible = $natural[$i]["uid"];
				else $visible .= ", " . $natural[$i]["uid"];
			}
			else {
				if ($hidden == "") $hidden = $natural[$i]["uid"];
				else $hidden .= ", " . $natural[$i]["uid"];
			}
		}
		$sql1 = 0;
		$sql2 = 0;
		
		$insertStr = "";
		
		if ($visible!="") {
			$sql1 = "SELECT `uid`, `maxDeposit`, `maxPoints` FROM `res_subtypes` WHERE `natural` in ($visible) ORDER BY `natural`, `category`, `name`";
			$res1 = $this->mysqli->query($sql1);
			if (mysqli_num_rows($res1)) {
				while ($row = mysqli_fetch_row($res1)) {
					$num = rand(1, $row[2]);
					$minD = round($row[1]/25);
					if ($insertStr!="") $insertStr .= ", ";
					$insertStr .= "(NULL, ". $row[0] . ", " . $this->x . ", " . $this->y . ", " . $num . ", " . $minD . ", " . $row[1] . ", 0)";
				}
			}
			
		}
		
		if ($hidden!="") {
			$sql2 = "SELECT `uid`, `maxDeposit`, `maxPoints` FROM `res_subtypes` WHERE `natural` in ($hidden) ORDER BY `natural`, `category`, `name`";
			$res2 = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res2)) {
				while ($row = mysqli_fetch_row($res2)) {
					$num = rand(1, $row[2]);
					$minD = round($row[1]/25);
					if ($insertStr!="") $insertStr .= ", ";
					$insertStr .= "(NULL, ". $row[0] . ", " . $this->x . ", " . $this->y . ", " . $num . ", " . $minD . ", " . $row[1] . ", 1)";
				}
			}
			
		}
		
		
		$sql4 = "INSERT INTO `local_res` (`uid`, `resFK`, `x`, `y`, `amount`, `minSize`, `maxSize`, `hidden`) VALUES " . $insertStr;
		
		$this->mysqli->query($sql4);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$sql = "SELECT `local_res`.`uid`, `hidden`, `name` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `x`=$this->x AND `y`=$this->y AND `local_res`.`amount`>0 ORDER BY `hidden`, `name`";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$localRes[] = array(
						"uid" => $row[0],
						"hidden" => $row[1],
						"name" => $row[2]
						);
				}
			}
			if ($localRes) return $localRes;
			else return false;
		}
		else return false;
	}
	
	function getAreas() {
		$areas = array();
		$pix = $this->getMajorPixel ($this->x, $this->y);
		$sql = "SELECT `uid`, `filename`, `color` FROM `areas` WHERE 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$url = getGamePath() . "/graphics/areas/" . $row[1];
				if (!file_exists($url)) echo "File for area " . $row[0] . " doesn't exist. Please inform game administrator.";
				else {
					$target = new MapPixel($this->mysqli);
					$rgb = $target->readColor($url, $pix["x"], $pix["y"]);
					$curQuantity = $target->getSingleColor($rgb, $row[2]);
					if ($curQuantity>0) {
						$areas[] = $row[0];
					}
				}
			}
		}
		return $areas;
	}
	
	function getPossibleAnimals() {
		$str = "";
		$areas = $this->getAreas();
		foreach ($areas as $area) {
			if (!$str=="") $str .= ", ";
			$str .= $area;
		}
		if ($str=="") return -1;
		$str2 = "";
		$terrains = $this->getTerrains();
		foreach ($terrains as $area) {
			if (!$str2=="") $str2 .= ", ";
			$str2 .= $area[0];
		}
		$str3 = "";
		$sql1 = "SELECT `animalFK` FROM `area_animals` WHERE `areaFK` IN ($str)";
		$res = $this->mysqli->query($sql1);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				if (!$str3=="") $str3 .= ", ";
				$str3 .= $row[0];
			}
		}
		else return -1;
		$retArr = array();
		$sql2 = "SELECT `animalFK`, `animal_name`, `plural` FROM `areatype_animals` JOIN `animals` ON `animalFK`=`animals`.`uid` WHERE `areatypeFK` IN ($str2) AND `animalFK` IN ($str3) GROUP BY `animals`.`uid`";
		$res = $this->mysqli->query($sql2);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"plural" => $row[2]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function generateRandomPlant() {
		$targetWater = $this->toPercent($this->getLevel("water"));
		$terrains = $this->getTerrains();
		if ($targetWater>90) {
			return "algae";
		}
		
		$rand1 = rand(0,10);
		
		if ($rand1==0) return "moss";
		if ($rand1==1) return "club moss";
		if ($rand1==2) return "fern";
		if ($rand1<6) {
			//gymnosperm
			if (abetween($this->y, -1500, 1500)) return "cycad";
			
			$rand2 = rand(0,99);
			if ($rand2==0&&between($this->y, -2000, 2000)) return "gingko";//very rare
			if (between($this->y, -3000, 3000)) return "cypress";
			if (between($this->y, -3500, 3500)&&$rand2>70) return "fir";
			if ($this->y>3000&&$rand2<20) return "yew";//Hemisphere restriction
			return "pine";//most common outside the tropics
		}
		
		//angiosperm
		$rand3 = rand(0,80);
		if ($targetWater>50&&$rand3<20) return "water lily";
		if ($rand3==0) return "anise";
		if ($rand3<40) {
			//grass and lily families
			if ($rand3<15&&searchSingle($terrains, 1, "desert")) return "agave";
			if ($rand3<8&&$this->y>-1000&&$this->y<1000) return "orchid";
			if ($rand3==1) return "asparagus";
			if ($rand3==2) return "aloe";
			if ($rand3==3) return "onion";
			if ($rand3==4) return "amaryllis";
			if ($rand3<8) return "daylily";
			if ($rand3<12) return "lily";
			if ($rand3<15) return "iris";
			
			if ($rand3<30&&searchSingle($terrains, 1, "desert")) return "aroid";
			if ($rand3==39&&between($this->y, -1000, 1000)) return "banana";
			if ($rand3==38&&between($this->y, -1000, 1000)) return "pineapple";
			if ($rand3==37&&between($this->y, -3000, 3000)) return "yam";
			if ($rand3==36) return "ginger";
			if ($rand3==38&&between($this->y, -2000, 2000)&&$rand3==35) return "aroid";
			if ($rand3<30&&between($this->y, -1000, 1000)) return "palm";
			
			return "grass";//default
		}
		//rand 40+
		if (searchSingle($terrains, 1, "desert")) {
			$rand4 = rand(0,3);
			if ($rand4==0) return "wild grapes";
			return "cactus";
		}
		if ($rand3==40) {
			if (between($this->y, -2500, 2500)) return "laurel";
			$rand4 = rand(0,50);
			if ($rand4==0) return "pawpaw";
			if ($rand4<20) return "pepper";
			return "magnolia";
		}
		if ($rand3==41) return "poppy";
		if ($rand3==42) return "buttercup";
		if ($rand3==43) return "sycamore";
		if ($rand3==44) {
			$rand4 = rand(0,20);
			if ($rand4==0) return "beet";
			if ($rand4<11) return "carnation";
			return "mistletoe";
		}
		if ($rand3==45) {
			if (between($this->y, -3500, 3500)) return "blueberry";
			else return "tea";
		}
		if ($rand3==48) {
			if (between($this->y, -2000, 2000)&&rand(0,2)==0) return "coffee";
			return "milkweed";
		}
		if ($rand3==47) {
			if (between($this->y, -3000, 3000)) return "currant";
			if (between($this->y, -2000, 2000)) return "grapes";
			return "peony";
		}
		if ($rand3==48) {
			if (between($this->y, -3000, 3000)) return "holly";
			return "sunflower";
		}
		if ($rand3==49) {
			$rand4 = rand(0,20);
			if ($rand4==0) return "wild carrot";
			if ($rand4==1) return "ginseng";
			return "bellflowers";
		}
		if ($rand3==50) return "gum tree";
		if ($rand3==51) {
			if (between($this->y, -1000, 1000)) return "capsicum";
			if (between($this->y, -2000, 2000)) return "tomatoes";
			if (between($this->y, -2500, 2500)) return "olives";
			return "mints/verbena";
		}
		if ($rand3==51) return "Euphorbia";
		if ($rand3==52) return "violets";
		if ($rand3==53) return "willow";
		if ($rand3==54) return "apple trees";
		if ($rand3==55) {
			if (between($this->y, -2000, 2000)) return "citrus tree";
			return "maple";
		}
		if ($rand3==56) return "sumac";
		if ($rand3==57) {
			if (between($this->y, -2000, 2000)) return "cacao";
			return "malva";
		}
		if ($rand3==58) {
			if (between($this->y, -1500, 1500)) return "papaya";
			return "mustard";
		}
		if ($rand3==59) return "elm tree";
		if ($rand3==60) return "roses";
		if ($rand3==61) return "balsams";
		if (between($this->y, -3000, 3000)) return "birch";//cutoff cold regions
		
		if ($rand3==62) return "beans";
		if ($rand3==63) return "hops";
		if ($rand3==64) {
			if (between($this->y, -1500, 1500)) return "figs";
			return "mulberry";
		}
		
		if ($rand3==65) return "walnut";
		if ($rand3==66) {
			if (between($this->y, -2000, 2000)) return "cucumber";
			return "begonia";
		}
		return "oak";//default
	}
}
?>
