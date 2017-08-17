<?php
include_once("class_character.inc.php");
include_once("class_animal_type.inc.php");
include_once("class_resource_string.inc.php");
include_once("constants.php");
include_once("generic.inc.php");

class Obj
{
	
	private $mysqli;//db connection
	var $uid = 0;//object id if assigned
	var $preset = 0;
	var $type = 0;
	var $parent = 0;
	var $x = NULL;
	var $y = NULL;
	var $localx = 0;
	var $localy = 0;
	var $secondary = 0;
	var $pieces = 0;
	var $weight = 0;
	var $name = "";
	var $datetime = 1010100;
	var $minute = 0;

	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($this->uid>0) $this->getBasicData();
	}
	
	//-------- General purpose -----------------------------------------------
	
	function create($preset, $type, $parent, $comment, $x, $y, $lx, $ly, $secondary, $pieces, $weight, $datetime, $minute) {
		if (is_null($x)) $x = "NULL";//The function should be given null as a string but in case someone still gives it null, it gets entered as a string
		if (is_null($y)) $y = "NULL";
		$sql = "INSERT INTO `objects`(`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '$preset', '$type', '$parent', CURRENT_TIMESTAMP, '$comment', $x, $y, '$lx', '$ly', '$secondary', '$pieces', '$weight', '$datetime', '$minute')";
		
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		$this->uid = $result;
		$this->preset = $preset;
		$this->type = $type;
		$this->parent = $parent;
		$this->x = $x;
		$this->y = $x;
		$this->localx = $lx;
		$this->localy = $ly;
		$this->secondary = $secondary;
		$this->pieces = $pieces;
		$this->weight = $weight;
		$this->datetime = $datetime;
		$this->minute = $minute;
		return $result;
	}
	
	function createImmaterial($preset, $type, $parent, $x, $y, $lx, $ly, $secondary, $pieces=1, $weight=0, $datetime=1010100, $minute=0) {
		$this->preset = $preset;
		$this->type = $type;
		$this->parent = $parent;
		$this->x = $x;
		$this->y = $x;
		$this->localx = $lx;
		$this->localy = $ly;
		$this->secondary = $secondary;
		$this->pieces = $pieces;
		$this->weight = $weight;
		$this->datetime = $datetime;
		$this->minute = $minute;
	}
	
	function getBasicData() {
		$sql = "SELECT `presetFK`, `general_type`, `parent`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute` FROM `objects` WHERE `uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->preset = $row[0];
			$this->type = $row[1];
			$this->parent = $row[2];
			$this->x = $row[3];
			$this->y = $row[4];
			$this->localx = $row[5];
			$this->localy = $row[6];
			$this->secondary = $row[7];
			$this->pieces = $row[8];
			$this->weight = $row[9];
			$this->datetime = $row[10];
			$this->minute = $row[11];
			return true;
		}
		else return false;
	}
	
	function changeType($pre, $sec, $gen) {
		$sql = "UPDATE `objects` SET `presetFK`=$pre, `general_type`=$gen, `secondaryFK`=$sec WHERE `uid`=$this->uid";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) return false;
		
		$this->preset = $pre;
		$this->secondary = $sec;
		return true;
	}
	
	function getName($incl=true) {
		if ($this->name!=""&&$this->name!="unknown object") return $this->name;
		if ($this->type == 5) $sql = "SELECT `name`, `plural` FROM `res_subtypes` WHERE `uid`=$this->secondary";
		else if ($this->type == 4||$this->type == 9) $sql = "SELECT `animal_name` FROM `animals` WHERE `uid`=$this->secondary";
		else $sql = "SELECT `name` FROM `o_presets` WHERE `uid`=$this->preset";
		$str = "unknown object";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$str = $row[0];
			if ($this->type == 5) $plural = $row[1];
			else $plural = "not a resource";
		}
		
		if ($this->type == 5 && $this->preset!=20) {
			$sql2 = "SELECT `name` FROM `o_presets` WHERE `uid`=$this->preset";
			$res = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				
				$str = str_replace(array("#MATERIAL#", "#VEGETABLES#", "#PLANTS#", '#FRUITS#'), array($str, $plural, $plural, $plural), $row[0]);
				
				$str = str_replace(array("berry berries", "fruit fruit", "bean beans", "bead beads", "pea peas"), array("berries", "fruit", "beans", "beads", "peas"), $str);//This takes care of double definitions that moving fruit into #MATERIAL# fruit and berries into #MATERIAL# berries
			}
		}
		
		if ($this->type == 5&&$incl&&$this->pieces==1) {
			/*
			$liquids = array(1,2,143,401,420,432);
			if (in_array($this->secondary, $liquids)&&$this->weight<10) $str = "drops of " . $str;
			else if (in_array($this->secondary, $liquids)&&$this->weight<20) $str = "splash of " . $str;
			else if (in_array($this->secondary, $liquids)&&$this->weight>2000) $str = "pool of " . $str;
			else if (in_array($this->secondary, $liquids)) $str = "puddle of " . $str;
			else */
			$str = "pile of " . $str;
		}
		
		if ($this->type == 8||($this->type == 1 && $this->secondary>0)) {
			$sql2 = "SELECT `name` FROM `res_subtypes` WHERE `uid`=$this->secondary";
			$res = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$str .= " (" . $row[0] . ")";
			}
		}
		if ($this->type == 11) {
			$sql2 = "SELECT `animal_name` FROM `animals` WHERE `uid`=$this->secondary";
			$res = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$str .= " (" . $row[0] . ")";
			}
		}
		
		if ($this->type!=2&&$this->type!=4&&$this->type!=7&&$incl) {
			$temperature = $this->getAttribute(ATTR_TEMPERATURE);
			
			$exit = $this->getExitCoordinates();
			$time = new Time($this->mysqli);
			$weather = $time->getWeather($exit["x"], $exit["y"], true);
			
			if ($temperature) {
				if (between($weather["temp"], $temperature-3, $temperature+3)) $str = $str;
				else if ($temperature>=1200) $str = "white-hot " . $str . " ";
				else if ($temperature>=1000) $str = "yellow-hot " . $str . " ";
				else if ($temperature>=900) $str = "orange-hot " . $str . " ";
				else if ($temperature>=600) $str = "red-hot " . $str . " ";
				else if ($temperature>=320) $str = "burning hot " . $str . " ";
				else if ($temperature>=170) $str = "oven hot " . $str . " ";
				else if ($temperature>=100) $str = "boiling hot " . $str . " ";
				else if ($temperature>=70) $str = "steaming hot " . $str . " ";
				else if ($temperature>=50) $str = "semi-hot " . $str . " ";
				else if ($temperature>=30) $str = "very warm " . $str . " ";
				else if ($temperature>=18) $str = "warm " . $str . " ";
				else if ($temperature>=15) $str = "slightly cool " . $str . " ";
				else if ($temperature>=8) $str = "cool " . $str . " ";
				else if ($temperature>=2) $str = "cold " . $str . " ";
				else if ($temperature>=0) $str = "very cold " . $str . " ";
				else if ($temperature>=15) $str = "frozen " . $str . " ";
				else $str = "frozen solid " . $str . " ";
			}
		}
		
		if ($incl) {
			$heat_react = $this->getAttribute(ATTR_HEAT_REACT);
			if ($heat_react) {
				$heat_treated = $this->getAttribute(ATTR_HEAT_TREATED);
				if ($heat_treated) {
					if ($heat_react==1) {
						if ($heat_treated>80) $str = "very dry " . $str . " ";
						else if ($heat_treated>50) $str = "dry " . $str . " ";
						else if ($heat_treated>25) $str = "semi-dry " . $str . " ";
					}
					else if ($heat_react==2) {
						if ($heat_treated>80) $str = "mushy " . $str . " ";
						else if ($heat_treated>50) $str = "softened " . $str . " ";
						else if ($heat_treated>25) $str = "semi-softened " . $str . " ";
					}
					else if ($heat_react==3) {
						if ($heat_treated>80) $str = "syrupy " . $str . " ";
						else if ($heat_treated>50) $str = "thickened " . $str . " ";
						else if ($heat_treated>25) $str = "slightly thickened " . $str . " ";
					}
					else if ($heat_react==4) {
						if ($heat_treated>80) $str = "shriveled " . $str . " ";
						else if ($heat_treated>50) $str = "withered " . $str . " ";
						else if ($heat_treated>25) $str = "slightly withered " . $str . " ";
					}
					else if ($heat_react==6) {
						if ($heat_treated==100) $str = "stoneware " . $str . " ";
						else if ($heat_treated>=90) $str = "earthenware " . $str . " ";
						else if ($heat_treated>=80) $str = "bisque fired " . $str . " ";
						else if ($heat_treated>=30) $str = "brittle half-baked " . $str . " ";
						else if ($heat_treated>=10) $str = "dry unfired " . $str . " ";
					}
				}
			}
		}
		
		if ($this->type == 9||$this->type == 10) $str = "dead " . $str;
		
		$str = $str . " (id ". $this->uid . ")";
		$this->name = $str;
		return $str;
	}
	
	function getAttribute($attr) {
		
		if ($this->uid>0) {
			$sql = "SELECT `value` FROM `o_attrs` WHERE `objectFK`=$this->uid AND `attributeFK`=$attr ORDER BY `o_attrs`.`uid` DESC LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return false;
			}
			if ($res->num_rows==0) $found = false;
			else {
				$row = $res->fetch_object();
				$value = $row->value;
				$found = true;
			}
		}
		else $found = false;
		if ($found) return $value;
		
		$res = $this->mysqli->query("SELECT `value` FROM `pr_attrs` join `objects` ON `o_presetFK`=`presetFK` WHERE `objects`.`uid`=$this->uid AND `attributeFK`=$attr ORDER BY `pr_attrs`.`uid` DESC LIMIT 1");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		if ($res->num_rows==0) $found = false;
		else {
			$row = $res->fetch_object();
			$value = $row->value;
			$found = true;
		}
		if ($found) return $value;
		if ($this->type == 5) {
			$res = $this->mysqli->query("SELECT `value` FROM `res_attrs` WHERE `resFK`=$this->secondary AND `attrFK`=$attr ORDER BY `res_attrs`.`uid` DESC LIMIT 1");
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return false;
			}
			if ($res->num_rows==0) return false;
			else {
				$row = $res->fetch_object();
				$value = $row->value;
				$found = true;
			}
		}
		if ($found) return $value;
		if ($this->type == 4||$this->type == 9) {
			$sql = "SELECT `value` FROM `animal_attributes` WHERE `animal_type`=$this->secondary AND `attributeFK`=$attr ORDER BY `animal_attributes`.`uid` DESC LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return false;
			}
			if ($res->num_rows==0) return false;//value doesn't exist
			else {
				$row = $res->fetch_object();
				$value = $row->value;
				return $value;
			}
		}
		return false;
	}
	
	function purgeAttribute($attr) {
		if ($this->uid>0) {
			$sql = "SELECT `value` FROM `o_attrs` WHERE `objectFK`=$this->uid AND `attributeFK`=$attr ORDER BY `o_attrs`.`uid` DESC LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (!$res) {
				para("Query failed: " . $this->mysqli->error);
				return -3;
			}
			if ($res->num_rows==0) return -1;//There is no entry in the first place
			
			$r=queryDelete($this->mysqli, "o_attrs", "`objectFK`=$this->uid AND `attributeFK`=$attr", "`objectFK`", 1);
			if ($r==0) return -2;
			return 100;//success
		}
		return -4;//Id is 0
	}
	
	function setAttribute($attr, $newVal) {
		if ($this->uid>0) {
			$sql = "UPDATE `o_attrs` SET `value`=" . $newVal . " WHERE `objectFK`=$this->uid AND `attributeFK`=$attr LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				$old = $this->getAttribute($attr);
				if ($old == $newVal) return -3;//It's already the same
		
				$sql = "INSERT INTO `o_attrs` (`objectFK`, `attributeFK`, `value`) VALUES ($this->uid, $attr, $newVal)";
				$this->mysqli->query($sql);
				$result = $this->mysqli->insert_id;
			}
			else return 100;
			if (!$result) {
				para("Query failed: " . $this->mysqli->error);
				return -1;
			}
			else return 100;
		}
		return -2;
	}
	
	function weightStr() {
		if ($this->weight<12) $str = "featherweight";
		else if ($this->weight<150) $str = "very light";
		else if ($this->weight<2000) $str = "light";
		else if ($this->weight<7000) $str = "medium-weight";
		else if ($this->weight<12600) $str = "two stone";
		else if ($this->weight<18900) $str = "three stone";
		else if ($this->weight<32000) $str = "child-weight";
		else if ($this->weight<60000) $str = "light adult-weight";
		else if ($this->weight<80000) $str = "man-weight";
		else if ($this->weight<120000) $str = "very heavy";
		else if ($this->weight<300000) $str = "massive";
		else $str = "gigantic";
		
		return $str;
	}
	
	function getHandle($incl=true) {
		if ($this->pieces>1) $str = $this->pieces . " x " . $this->getName($incl);
		else if ($this->type == 5) $str = "a " . $this->weightStr() . " " . $this->getName($incl);
		else {
			$arr = array("a", "e", "i", "o", "u");
			$str = $this->getName($incl);
			$first = substr($str, 0, 1);
			if (in_array($first, $arr)) $str = "an " . $str;
			else $str = "a " . $str;
		}
		return $str;
	}
	
	function approximateWeight() {
		if ($this->weight<12) $str = "about the weight of a pebble (1 to 10 grams)";
		else if ($this->weight<60) $str = "something like a small plum (about 40 grams)";
		else if ($this->weight<150) $str = "something like a carrot (about 100 grams)";
		else if ($this->weight<350) $str = "something like a large orange (about 200-300 grams)";
		else if ($this->weight<650) $str = "something like a head of lettuce (about half a kilo)";
		else if ($this->weight<1500) $str = "something like a cabbage (about a kilo)";
		else if ($this->weight<4000) $str = "about the same as a newborn (a few kilos)";
		else if ($this->weight<7000) $str = "about the same as an older baby (a few kilos)";
		else if ($this->weight<12600) $str = "about the same as a toddler (8 to 12 kilos)";
		else if ($this->weight<18900) $str = "about the same as a small child (13 to 18 kilos)";
		else if ($this->weight<32000) $str = "about the same as an older child (20-30 kilos)";
		else if ($this->weight<55000) $str = "about the same as  a small woman (35-50 kilos)";
		else if ($this->weight<73000) $str = "about the same as a small man (50-70 kilos)";
		else if ($this->weight<91000) $str = "about the same as a grown man (75-90 kilos)";
		else if ($this->weight<123000) $str = "about the same as a big man (90-120 kilos)";
		else if ($this->weight<210000) $str = "about the same as a fat person (130-200 kilos)";
		else $str = "far more than a single person can lift (200+ kilos)";
		
		return $str;
	}
	
	function checkMethod($method, $pieces, $weight) {
		$isCountable = $this->getAttribute(ATTR_COUNTABLE);
		if ($method == "pieces") {
			if ($isCountable) {
				if ($this->pieces>$pieces) return array(
				"method" => "part",
				"pieces" => $pieces,
				"weight" => $this->weight,
				"countable" => 1
				);
				return array(
				"method" => "whole",
				"pieces" => $this->pieces,
				"weight" => $this->weight,
				"countable" => 1
				);
			}
			return array(
			"method" => "whole",
			"pieces" => $this->pieces,
			"weight" => $this->weight,
			"countable" => 0
			);
		}
		if ($method == "weight") {
			if ($isCountable) return false;//error
			
			if ($this->weight>$weight) return array(
			"method" => "part",
			"pieces" => $this->pieces,
			"weight" => $weight,
			"countable" => 0
			);
			
			return array(
			"method" => "whole",
			"pieces" => $this->pieces,
			"weight" => $this->weight,
			"countable" => 0
			);
		}
		if ($method == "whole") {
			return array(
			"method" => "whole",
			"pieces" => $this->pieces,
			"weight" => $this->weight,
			"countable" => $isCountable
			);
		}
		return false;
	}
	
	function getSubPieces($subWeight, $charcheck) {
		$method = "part";
		if ($this->uid>0) $this->getBasicData();
		if ($subWeight>=$this->weight) {
			$subWeight = $this->weight;
		}
		$isCountable = $this->getAttribute(ATTR_COUNTABLE);
		if ($isCountable) {
			$smallUnitMass = $this->getAttribute(ATTR_LARGE_MASS);//checking if this has a set unit mass like most solid things without variance
			$minLgMass = $this->getAttribute(42);
			$maxLgMass = $this->getAttribute(43);
			$minSmallMass = $this->getAttribute(40);
			$maxSmallMass = $this->getAttribute(41);
			//all countables should have at least one of these
			if ($smallUnitMass) {
				$pieces = round($subWeight/$smallUnitMass);
				if ($pieces<1) $pieces = 1;
				if ($this->pieces<=$pieces) $method = "whole";
				else {
					$subWeight = $pieces*$smallUnitMass;
				}
			}
			else if ($minLgMass&$maxLgMass) {
				$minPieces = ceil($subWeight/($maxLgMass*1000));//this is always at least one
				$maxPieces = min(floor($subWeight/($minLgMass*1000)), $this->$pieces);
				if ($maxPieces>0) {
					$pieces = rand($minPieces, $maxPieces);
					$remainingPieces = $this->pieces-$pieces;
					$remainingWeight = $this->weight-$subWeight;
					if ($remainingPieces) {
						if ($remainingWeight/$remainingPieces>$maxLgMass*1000) $pieces=$this->pieces-ceil($remainingWeight/($maxLgMass*1000));
						if ($remainingWeight/$remainingPieces<$minLgMass*1000) $pieces=rand($this->pieces-(ceil($this->weight-$subWeight)/($maxLglMass*1000)),$this->pieces-floor(($this->weight-$subWeight)/($minLgMass*1000)));
					}
				}
				else if ($minPieces==1) {
					$subWeight=$minLgMass*1000;
					$pieces = 1;
				}
			}
			else if ($minSmallMass&$maxSmallMass) {
				$minPieces = ceil($subWeight/$maxSmallMass);
				$maxPieces = min(floor($subWeight/$minSmallMass), $this->pieces);
				if ($maxPieces>0) {
					$pieces = rand($minPieces, $maxPieces);
					$remainingPieces = $this->pieces-$pieces;
					$remainingWeight = $this->weight-$subWeight;
					if ($remainingPieces) {
						if ($remainingWeight/$remainingPieces>$maxSmallMass) $pieces=$this->pieces-ceil($remainingWeight/$maxSmallMass);
						if ($remainingWeight/$remainingPieces<$minSmallMass) $pieces=rand($this->pieces-(ceil($this->weight-$subWeight)/$maxSmallMass),$this->pieces-floor(($this->weight-$subWeight)/$minSmallMass));
					}
				}
				else if ($minPieces==1) {
					$subWeight=$minSmallMass;
					$pieces = 1;
				}
			}
			else {
				$pieces = 1;
				if ($this->pieces<=1) {
					$method = "whole";
					$subWeight = $this->weight;
				}
			}
		}
		else {
			//not countable
			$pieces = 1;
			if ($this->type!=5) {
				$subWeight = $this->weight;//non-breakable
				$method = "whole";
			}
		}
		if ($pieces>=$this->pieces&&$this->type!=5) {
			$pieces = $this->pieces;
			$subWeight = $this->weight;
			$method = "whole";
		}
		if ($subWeight>=$this->weight) {
			$method = "whole";
			$subWeight = $this->weight;
			$pieces = $this->pieces;
		}
		
		return array(
			"method" => $method,
			"weight" => $subWeight,
			"pieces" => $pieces,
			"countable" => $isCountable
			);
	}
	
	function getSubWeight($pieces, $charcheck) {
		$method="part";
		if ($this->uid>0) $this->getBasicData();
		if ($pieces>$this->pieces) $pieces = $this->pieces;//trying to drop more than they have
		$isCountable = $this->getAttribute(ATTR_COUNTABLE);
		if ($isCountable) {
			$smallUnitMass = $this->getAttribute(ATTR_SMALL_MASS);
			$minLgMass = $this->getAttribute(42);
			$maxLgMass = $this->getAttribute(43);
			$minSmallMass = $this->getAttribute(40);
			$maxSmallMass = $this->getAttribute(41);
			
			if ($smallUnitMass) {
				$weight = $pieces*$smallUnitMass;
			}
			else if ($minLgMass&$maxLgMass) {
				$minWeight = $pieces*($minLgMass*1000);
				$maxWeight = $pieces*($maxLgMass*1000);
				$maxRemainingWeight = ($this->pieces-$pieces)*($maxLgMass*1000);
				$minRemainingWeight = ($this->pieces-$pieces)*($minLgMass*1000);
				$value1 = rand($minRemainingWeight, $maxRemainingWeight);
				$value2 = rand($minRemainingWeight, $maxRemainingWeight);
				
				$max2=$this->weight-$minRemainingWeight;
				$min2=$this->weight-$maxRemainingWeight;
				if ($value2>$value1) {
					if ($max2<$maxWeight) $maxWeight=$this->weight-$value2;
					if ($min2>$minWeight) $minWeight=$this->weight-$value1;
				}
				else {
					if ($max2<$maxWeight) $maxWeight=$this->weight-$value1;
					if ($min2>$minWeight) $minWeight=$this->weight-$value2;
				}
				if ($minWeight<$this->weight&&$maxWeight<$this->weight) {
					$weight = rand($minWeight, $maxWeight);
				}
				else if ($minWeight<$this->weight) $weight = rand($minWeight, $this->weight);
				else $weight = $this->weight;
			}
			else if ($minSmallMass&$maxSmallMass) {
				$minWeight = $pieces*$minSmallMass;
				$maxWeight = $pieces*$maxSmallMass;
				$maxRemainingWeight = ($this->pieces-$pieces)*$maxSmallMass;
				$minRemainingWeight = ($this->pieces-$pieces)*$minSmallMass;
				$value1 = rand($minRemainingWeight, $maxRemainingWeight);
				$value2 = rand($minRemainingWeight, $maxRemainingWeight);
				
				$max2=$this->weight-$minRemainingWeight;
				$min2=$this->weight-$maxRemainingWeight;
				if ($value2>$value1) {
					if ($max2<$maxWeight) $maxWeight=$this->weight-$value2;
					if ($min2>$minWeight) $minWeight=$this->weight-$value1;
				}
				else {
					if ($max2<$maxWeight) $maxWeight=$this->weight-$value1;
					if ($min2>$minWeight) $minWeight=$this->weight-$value2;
				}
				if ($minWeight<$this->weight&&$maxWeight<$this->weight) {
					$weight = rand($minWeight, $maxWeight);
				}
				else if ($minWeight<$this->weight) $weight = rand($minWeight, $this->weight);
				else $weight = $this->weight;
			}
			if ($pieces>$this->pieces) $method = "whole";
		}
		else if ($pieces==1) {
			$method = "whole";//the item doesn't stack and 1 is selected, so everything is okay
			$weight = $this->weight;
		}
		else return false;//If the pieces is bigger than 1 but the object is not countable, there is a bug involved
		
		if ($this->weight<=$weight) {
			$method = "whole";
			$pieces = $this->pieces;
		}
				
		return array(
			"method" => $method,
			"weight" => $weight,
			"pieces" => $pieces,
			"countable" => $isCountable
			);
	}
	
	function getContents($general = 0) {
		//Limit statement is general_type if multiple separate by commas
		if ($this->uid>0) {
			$curTime = new Time($this->mysqli);
			if ($general) $limiter = "AND `general_type` IN (" . $general . ")";
			else $limiter = "";
			$arr = array();
			$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->uid $limiter AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				while ($row = mysqli_fetch_row($result)) {
					$arr[] = $row[0];
				}
				return $arr;
			}
			else return false;
		}
		else return false;
	}
	
	function deleteFromDb() {
		$r=queryDelete($this->mysqli, "objects", "`uid`=$this->uid", "`uid`", 1);
		if ($r==1) {
			queryDelete($this->mysqli, "o_attrs", "`objectFK`=$this->uid", "`uid`");
			return true;
		}
		else return false;
	}
	
	function makeImmaterial() {
		$sql = "UPDATE `objects` SET `parent`=0, `global_x`=NULL, `global_y`= NULL, `local_x`=0, `local_y`=0 WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function changeSize($weightChange, $piecesChange, $charcheck=0) {
		$isCountable = $this->getAttribute(ATTR_COUNTABLE);
		if (!$isCountable) $piecesChange=0;
		
		$sql = "UPDATE `objects` SET `weight`=`weight`+$weightChange, `pieces`=`pieces`+$piecesChange WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			$this->weight += $weightChange;
			$this->pieces += $piecesChange;
			return true;
		}
		else return false;
	}
	
	function getDanger() {
		if ($this->type==4) {
			$atype = new AnimalType($this->mysqli, $this->secondary);
			return $atype->getDanger();
		}
		return -2;//Not an animal
		//In the future, this can also be used to calculate the danger rating of groups (maybe)
	}
	
	function validatePool($pool) {
		$sql = "SELECT `ap_modifier`, `quality` FROM `pool_tools` WHERE `toolFK`=$this->preset AND `poolFK`=$pool LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)==1) return mysqli_fetch_assoc($result);
		else return false;
	}
	
	function getUses() {
		$arr = array();
		$sql3 = "SELECT `projectFK`, `project_types`.`presetFK`, `secondary` FROM `needed_t_pools` join `project_types` ON `needed_t_pools`.`projectFK`=`project_types`.`uid` WHERE `hidden`=0 AND `poolFK` IN (SELECT `poolFK` FROM `pool_tools` WHERE `toolFK` = $this->preset) GROUP BY `projectFK`";
		$result = $this->mysqli->query($sql3);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"preset" => $row[1],
					"secondary" => $row[2],
					"type" => "manu"
					);
			}
		}
		$sql2 = "SELECT `project_type`, `presetFK`, `secondary` FROM `needed_tools` join `project_types` on `project_type`=`project_types`.`uid` WHERE `toolFK`=$this->preset ORDER BY `category`";
		$result = $this->mysqli->query($sql2);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"preset" => $row[1],
					"secondary" => $row[2],
					"type" => "manu"
					);
			}
		}
		
		$fire_effect = $this->getAttribute(ATTR_IGNITION);
		if ($fire_effect) $arr[] = array(
			"uid" => $this->uid,
			"type" => "fire",
			"value" => $fire_effect
			);
		
		return $arr;
	}
	
	function getCharid() {
		//To-do: Should we take into account siamese twins and possessed people?
		$sql = "SELECT `uid` FROM `chars` WHERE `objectFK`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return -1;//Not found or not a character
	}
	
	//-------fuel project ------------------------------------------------------------
	
	function getStatus($charcheck) {
		$fire = $this->getAttribute(ATTR_ON_FIRE);//on fire
		if (!$fire) return "";
		else return " (<span class='fire'>on fire</span>)";
	}
	
	function ignite() {
		return $this->setAttribute(ATTR_ON_FIRE, 1);
	}
	
	//-Combat/health---------------------------------------------------------------------------------------------------------
	
	function calculateBlood() {
		$curBlood = $this->getAttribute(ATTR_BLOOD);
		if (!$curBlood) {
			$newVal = round($this->weight/10);
			$this->setAttribute(ATTR_BLOOD, $newVal);
			return $newVal;
		}
		return $curBlood;
	}
	
	function bleed($grams, $observer=0) {
		$curBlood = $this->calculateBlood();
		$new = max($curBlood-$grams, 0);
		$this->setAttribute(ATTR_BLOOD, $new);
		return $new;
	}
	
	function sumWounds($charid) {
		$observer = new Character($this->mysqli, $charid);
		$curTime = new Time($this->mysqli);
		$sum = 0;
		$ext = 0;
		$sql = "SELECT `uid`, `bleed_level`, `bleed_type`, `bandage` FROM `wounds` WHERE `objectFK`=$this->uid AND `stitches`=0 ORDER BY `uid`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$bleed = round(pow($row[1], 2.8)*pow($this->weight, 0.15)/2);
				if ($row[3]>0) $bleed = round($bleed/($row[3]+1));
				
				if ($row[2]==1) $ext += $bleed;
				if ($row[2]==3) $ext += round($bleed/3);
				$sum += $bleed;
			}
			$curBlood = $this->calculateBlood();
			if ($sum>$curBlood/2) $sum = round($curBlood/2);
			if ($ext>$curBlood/2) $ext = round($curBlood/2);
			
			if ($ext>0) {
				$blood_obj = new Obj($this->mysqli, 0);
				$blood_obj->create(20, 5, $this->parent, "Blood spilled from wounds", $this->x, $this->y, $this->localx, $this->localy, 2, 1, $ext, $curTime->dateTime, $curTime->minute);
			}
			return $sum;
		}
		else return 0;
	}
	
	function attackCharacter($charid, $down) {
		//down is boolean
		if ($this->type==4) {
			$atype = new AnimalType($this->mysqli, $this->secondary);
			$victim = new Character($this->mysqli, $charid);
			$victim->getBasicData();
			
			$at_array = $atype->getAttackTypes();
			if (!is_array($at_array)) return -2;//not capable of attacking
			$strategy = $atype->getStrategy($down);
			if (!$strategy) return -3;
			$curIndex = rand(0, sizeof($at_array)-1);
			$result = $victim->receiveAttack($this->uid, $at_array[$curIndex], $strategy);
			if (!$result) return -4;
			return 100;
		}
		return -1;//Incapable of attacking
	}
	
	function leaveCombat($observer_id) {
		$curTime = new Time($this->mysqli);
		$sql = "UPDATE `combat_participants` SET `leave_dt`=".$curTime->dateTime.", `leave_m`=".$curTime->minute." WHERE `leave_dt`=0 AND `objectFK`=$this->uid";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows>0) return true;
		else return false;
	}
	
	function moveInside($inside) {
		$sql = "UPDATE `objects` SET `parent`=$inside, `global_x`=NULL AND `global_y`=NULL AND `local_x`=0 AND `local_y`=0 WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function perish($observer_id=0) {
		$curTime = new Time($this->mysqli);
		if ($this->type==2) $sql = "UPDATE `objects` SET `general_type`=10, `datetime`=". $curTime->dateTime .", `minute`=". $curTime->minute ." WHERE `uid`=$this->uid LIMIT 1";
		else if ($this->type==4) $sql = "UPDATE `objects` SET `general_type`=9, `datetime`=". $curTime->dateTime .", `minute`=". $curTime->minute ." WHERE `uid`=$this->uid LIMIT 1";
		else return -1;
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			if ($this->type==2) {
				$charid = $this->getCharid();
				if ($charid>0) {
					$dier = new Character($this->mysqli, $charid);
					$dier->changeStatus(2);
				}
			}
			return 100;
		}
		else return -2;
	}
	
	function getBloodPercentage() {
		$max = round($this->weight/10);
		$curBlood = $this->calculateBlood();
		
		return round($curBlood/$max*100);
	}
	
	function dressCarcass($charid, $parts, $tool) {
		//To do: Make parts go into group stock if the carcass is in group stock
		$actor = new Character($this->mysqli, $charid);
		$actor->getBasicData();
		$possible = $actor->getPoolToolsInventory(1);
		if (!$possible) return -1;//Doesn't have any tools of this kind
		if (!in_array($tool, $possible)) return -1;//Trying to use invalid tool
		
		$multiplier = round(pow($this->weight+400, 0.3));
		$blood = $this->getAttribute(ATTR_BLOOD);
		$skin = $this->getAttribute(ATTR_SKIN_TYPE);
		$braincheck = $this->getAttribute(ATTR_HAS_BRAINS);
		$brain = $this->getAttribute(ATTR_BRAIN_SIZE);
		$intestine = $this->getAttribute(ATTR_HAS_INTESTINE);
		$offal = $this->getAttribute(ATTR_HAS_OFFAL);
		$sinew = $this->getAttribute(ATTR_HAS_SINEW);
		$head = $this->getAttribute(ATTR_HAS_HEAD);
		$horns = $this->getAttribute(ATTR_HAS_HORNS);
		$scapula = $this->getAttribute(ATTR_HAS_SCAPULA);
		$feet = $this->getAttribute(ATTR_HAS_FEET);
		
		$original_wt = $this->getAttribute(ATTR_ORIGINAL_WEIGHT);
		if (!$original_wt) {
			$original_wt = $this->weight;
			$this->setAttribute(ATTR_ORIGINAL_WEIGHT, $original_wt);
		}
		
		$ap = 10;
		
		if ($parts[3]==1||$parts[3]==2) $ap += $multiplier;
		if ($parts[5]==1||$parts[5]==2) $ap += round($multiplier/2);
		if ($parts[9]==1||$parts[9]==2) $ap += round($multiplier/8*$feet);
		
		if ($actor->getAP()<$ap) return -2;//not enough AP
		
		$curTime = new Time($this->mysqli);
		$weightloss = 0;
		
		for ($i=0;$i<3;$i++) {
			if ($parts[$i]<0||$parts[$i]>2) return -3;//Value out of range
		}
		for ($i=3;$i<10;$i++) {
			if ($parts[$i]<0||$parts[$i]>3) return -3;//Value out of range
		}
		
		if ($blood) {
			if ($blood>round($original_wt*0.04)) {
				$bloodwt = round($blood-round($original_wt*0.04));
			
				$newo = new Obj($this->mysqli);
				if ($parts[0]==1) $newo->create(20, 5, $actor->parent, "Discarded blood", $actor->x, $actor->y, $actor->localx, $actor->localy, 2, 1, $bloodwt, $curTime->dateTime, $curTime->minute);
				if ($parts[0]==2) $newo->create(20, 5, $actor->bodyId, "Collected blood", "NULL", "NULL", 0, 0, 2, 1, $bloodwt, $curTime->dateTime, $curTime->minute);
				$this->setAttribute(ATTR_BLOOD, 0);
				$weightloss += $bloodwt;
			}
		}
		if ($intestine) {
			$intestinewt = round($original_wt*0.05);
			$newo1 = new Obj($this->mysqli);
			if ($parts[1]==1) $newo1->create(20, 5, $actor->parent, "Discarded intestine", $actor->x, $actor->y, $actor->localx, $actor->localy, 444, 1, $intestinewt, $curTime->dateTime, $curTime->minute);
			if ($parts[1]==2) $newo1->create(20, 5, $actor->bodyId, "Saved intestine", "NULL", "NULL", 0, 0, 444, 1, $intestinewt, $curTime->dateTime, $curTime->minute);
			$this->setAttribute(ATTR_HAS_INTESTINE, 0);//removes intestine from body
			$weightloss += $intestinewt;
		}
		if ($offal) {
			$offalwt = round($original_wt*0.07);
			$newo2 = new Obj($this->mysqli);
			if ($parts[2]==1) $newo2->create(20, 5, $actor->parent, "Discarded offal", $actor->x, $actor->y, $actor->localx, $actor->localy, 445, 1, $offalwt, $curTime->dateTime, $curTime->minute);
			if ($parts[2]==2) $newo2->create(20, 5, $actor->bodyId, "Saved offal", "NULL", "NULL", 0, 0, 445, 1, $offalwt, $curTime->dateTime, $curTime->minute);
			$this->setAttribute(ATTR_HAS_OFFAL, 0);//removes offal from body
			$weightloss += $offalwt;
		}
		if ($skin) {
			$skinwt = round($original_wt*0.16);
		
			$newo3 = new Obj($this->mysqli);
			if ($parts[3]==1) $newo3->create($skin, 11, $actor->parent, "Discarded skin", $actor->x, $actor->y, $actor->localx, $actor->localy, $this->secondary, 1, $skinwt, $curTime->dateTime, $curTime->minute);
			if ($parts[3]==2) $newo3->create($skin, 11, $actor->bodyId, "Saved skin", "NULL", "NULL", 0, 0, $this->secondary, 1, $skinwt, $curTime->dateTime, $curTime->minute);
			$this->setAttribute(ATTR_SKIN_TYPE, 0);
			$weightloss += $skinwt;
		}
		if ($sinew) {
			$sinewwt = max(round($original_wt*0.000125),40);
			$newo4 = new Obj($this->mysqli);
			if ($parts[4]==1) $newo4->create(20, 5, $actor->parent, "Discarded sinew", $actor->x, $actor->y, $actor->localx, $actor->localy, 269, 1, $sinewwt, $curTime->dateTime, $curTime->minute);
			if ($parts[4]==2) $newo4->create(20, 5, $actor->bodyId, "Saved sinew", "NULL", "NULL", 0, 0, 269, 1, $sinewwt, $curTime->dateTime, $curTime->minute);
			if ($parts[4]<3) $this->setAttribute(ATTR_HAS_SINEW, 0);//removes sinew from body
			if ($parts[4]<3) $weightloss += $sinewwt;
		}
		if ($head) {
			$headwt = round($original_wt*0.05);
			if ($parts[6]==3&&$brain&&$parts[5]<3) $headwt += round($original_wt*0.02);//adds the weight of the brain if it's left in
			if ($parts[7]==3&&$horns&&$parts[5]<3) $headwt += round($original_wt*0.01);//adds the weight of the antlers/tusks/horns if it's left in
		
			$newo5 = new Obj($this->mysqli);
			if ($parts[5]==1) $newo5->create(5, 11, $actor->parent, "Discarded head", $actor->x, $actor->y, $actor->localx, $actor->localy, $this->secondary, 1, $headwt, $curTime->dateTime, $curTime->minute);
			if ($parts[5]==2) $newo5->create(5, 11, $actor->bodyId, "Saved head", "NULL", "NULL", 0, 0, $this->secondary, 1, $headwt, $curTime->dateTime, $curTime->minute);
			if ($parts[5]<3) $this->setAttribute(ATTR_HAS_HEAD, 0);//removes head from body
			if (($parts[6]==3&&$parts[5]<3)&&$brain) {
				$newo5->setAttribute(ATTR_BRAIN_SIZE, $brain);//adds brain type to head
				$newo5->setAttribute(ATTR_HAS_BRAINS, 1);//adds brain to head
				$this->setAttribute(ATTR_HAS_BRAINS, 0);//removes brain from body
			}
			if (($parts[7]==3&&$parts[5]<3)&&$horns) {
				$newo5->setAttribute(ATTR_HAS_HORNS, $horns);//adds antlers to head
				$this->setAttribute(ATTR_HAS_HORNS, 0);//removes antlers from body
			}
			$weightloss += $headwt;
		}
		if ($braincheck) {
			$brainwt = round($original_wt*0.02);
		
			$newo6 = new Obj($this->mysqli);
			if ($parts[6]==1) $newo6->create($brain, 11, $actor->parent, "Discarded brain", $actor->x, $actor->y, $actor->localx, $actor->localy, $this->secondary, 1, $brainwt, $curTime->dateTime, $curTime->minute);
			if ($parts[6]==2) $newo6->create($brain, 11, $actor->bodyId, "Saved brain", "NULL", "NULL", 0, 0, $this->secondary, 1, $brainwt, $curTime->dateTime, $curTime->minute);
			if ($parts[6]<3||($parts[6]==3&&$parts[5]<3)) $this->setAttribute(ATTR_HAS_BRAINS, 0);//removes brain from body
			if ($parts[6]<3) $weightloss += $brainwt;
		}
		if ($horns) {
			$hornswt = round($original_wt*0.01);
			if ($horns==1) $horntype=439;//antler
			if ($horns==2) $horntype=268;//horn
			if ($horns==3) $horntype=443;//ivory
			$newo7 = new Obj($this->mysqli);
			if ($parts[7]==1) $newo7->create(20, 5, $actor->parent, "Discarded horns", $actor->x, $actor->y, $actor->localx, $actor->localy, $horntype, 1, $hornswt, $curTime->dateTime, $curTime->minute);
			if ($parts[7]==2) $newo7->create(20, 5, $actor->bodyId, "Saved horns", "NULL", "NULL", 0, 0, $horntype, 1, $hornswt, $curTime->dateTime, $curTime->minute);
			if ($parts[7]<3||($parts[7]==3&&$parts[5]<3)) $this->setAttribute(ATTR_HAS_HORNS, 0);//removes horns from body
			if ($parts[7]<3) $weightloss += $hornswt;
		}
		if ($scapula) {
			if ($scapula==1) $stype=455;
			if ($scapula==2) $stype=456;
			$scapulawt = round($original_wt*0.015);
			$newo8 = new Obj($this->mysqli);
			if ($parts[8]==1) {
				$newo8->create($stype, 1, $actor->parent, "Discarded scapula", $actor->x, $actor->y, $actor->localx, $actor->localy, 0, 1, $scapulawt, $curTime->dateTime, $curTime->minute);
				$newo8->create($stype, 1, $actor->parent, "Discarded scapula", $actor->x, $actor->y, $actor->localx, $actor->localy, 0, 1, $scapulawt, $curTime->dateTime, $curTime->minute);
			}
			if ($parts[8]==2) {
				$newo8->create($stype, 1, $actor->bodyId, "Saved scapula", "NULL", "NULL", 0, 0, 0, 1, $scapulawt, $curTime->dateTime, $curTime->minute);
				$newo8->create($stype, 1, $actor->bodyId, "Saved scapula", "NULL", "NULL", 0, 0, 0, 1, $scapulawt, $curTime->dateTime, $curTime->minute);
			}
			if ($parts[8]<3) $this->setAttribute(ATTR_HAS_SCAPULA, 0);//removes scapula
		}
		if ($feet) {
			$feetwt = round($original_wt*0.07);
			$newo9 = new Obj($this->mysqli);
			if ($parts[9]==1) {
				for ($i=0;$i<$feet;$i++) {
					$newo9->create(7, 11, $actor->parent, "Cut of leg (discarded)", $actor->x, $actor->y, $actor->localx, $actor->localy, $this->secondary, 1, $feetwt, $curTime->dateTime, $curTime->minute);
				}
			}
			if ($parts[9]==2) {
				for ($i=0;$i<$feet;$i++) {
					$newo9->create(7, 11, $actor->bodyId, "Cut off leg (saved)", "NULL", "NULL", 0, 0, $this->secondary, 1, $feetwt, $curTime->dateTime, $curTime->minute);
				}
			}
			if ($parts[9]<3) $this->setAttribute(ATTR_HAS_FEET, 0);//removes feet
			if ($parts[9]<3) $weightloss += $feetwt*$feet;
		}
		
		$new_weight = max($original_wt-$weightloss,50);
		$sql = "UPDATE `objects` SET `weight`=$new_weight WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		
		$actor->spendAP($ap);
		$actor->updateCharLocTime($actor->x, $actor->y, $actor->localx, $actor->localy, $actor->building, 1, $ap);
		return 100;
	}
	
	function generateColor($category) {
		//category: 0 - tropical fruit, 1 - berry, 2 - root vegetable, 3 - flower, 4 - bean, 5 - citrus fruit, 6 - apple, 7 - drupes
		if ($category>7) return "dull-colored";
		
		switch ($category) {
		case 0:
			//tropical fruit
			$colors = array(
				"brown",
				"orange",
				"yellowish orange",
				"pale yellow",
				"green",
				"yellowish green",
				"red",
				"yellow-red",
				"warm yellow",
				"yellow",
				"red and green",
				"dark red",
				"dark purple",
				"pink",
				"magenta",
				"white",
				"grayish beige",
				"brown-striped",
				"pale green"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			
			break;
		case 1:
			//berry
			$colors = array(
				"blue",
				"black",
				"bluish black",
				"purplish black",
				"bluish pink",
				"pink",
				"red",
				"dark red",
				"white",
				"orange",
				"green"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 2:
			//root vegetable
			$colors = array(
				"white",
				"orange",
				"brown",
				"golden brown",
				"light brown",
				"yellow",
				"light yellow",
				"pink",
				"red",
				"purple",
				"light green"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 3:
			//flower
			$colors = array(
				"white",
				"light yellow",
				"bright yellow",
				"orange",
				"pink",
				"yellowish green",
				"white-pink",
				"white-yellow",
				"white, purple-streaked",
				"red",
				"magenta",
				"purple",
				"blue"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 4:
			//bean
			$colors = array(
				"brown",
				"golden brown",
				"reddish brown",
				"red",
				"beige",
				"white",
				"green",
				"brown spotted, beige",
				"black",
				"dark brown",
				"orange",
				"dark-spotted, white",
				"yellow",
				"light brown",
				"pink",
				"light, pink-striped",
				"brown and white"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 5:
			//citrus fruit
			$colors = array(
				"bright green",
				"yellowish green",
				"cool yellow",
				"warm yellow",
				"orange",
				"reddish orange"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 6:
			//apples
			$colors = array(
				"green",
				"yellowish green",
				"yellow",
				"pale yellow",
				"yellow-red",
				"yellow-speckled red",
				"red",
				"dark red"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		case 7:
			//drupes
			$colors = array(
				"orange",
				"yellow",
				"blushed yellow",
				"pink",
				"light purple",
				"medium purple",
				"reddish brown",
				"almost black",
				"dark green",
				"drab green"
				);
			$string = $colors[rand(0, sizeof($colors)-1)];
			break;
		}
		return $string;
	}
	
	function generateShape($category, $size=-1) {
		//category: 0 - tropical fruit, 1 - berry, 2 - root vegetable, 3 - flower, 4 - bean, 5 - citrus fruit, 6 - apple, 7 - drupes
		
		$size_a = array(
			"tiny",
			"small",
			"mid-sized",
			"large",
			"huge"
			);
		
		if ($size == -1) {
			if ($category==0) $size_s = $size_a[rand(0,sizeof($size_a)-1)];
			else $size_s = $size_a[rand(0,sizeof($size_a)-2)];
		}
		else $size_s = $size_a[$size];//predetermined
		
		$shape0 = array(
			"round",
			"pear-shaped",
			"egg-shaped",
			"oval",
			"odd-shaped",
			"elongated"
			);
		$shape1 = array(
			"round",
			"oval",
			"elongated",
			"drupelet-shaped",
			"strawberry-like",
			"blueberry-like",
			"mulberry-like",
			"currant-like",
			"gooseberry-like"
			);
		$shape2 = array(
			"many-forked",
			"two-forked",
			"carrot-shaped",
			"parsnip-shaped",
			"turnip-shaped",
			"celeriac-shaped",
			"round",
			"curved",
			"elongated",
			"pointy-ended",
			"odd-shaped"
			);
		$shape3 = array(
			"funnel-form",
			"trumpet-shaped",
			"tubular",
			"bowl-shaped",
			"saucer-shaped",
			"stellate",
			"cruciform",
			"urn-shaped",
			"bell-shaped"
			);
		$shape4 = array(
			"circular",
			"kidney-shaped",
			"ovate",
			"elliptic",
			"narrow kidney-shaped",
			"narrow ovate",
			"narrow elliptic",
			"broad kidney-shaped",
			"broad ovate",
			"broad elliptic"
			);
		$shape5 = array(
			"round",
			"pear-shaped",
			"egg-shaped",
			"oval",
			"lemon-shaped",
			"hand-shaped"
			);
		
		$shape6 = array(
			"round",
			"bumpy",
			"conic",
			"lopsided",
			"oblate"
			);
		
		$shape7 = array(
			"peach-shaped",
			"cherry-shaped",
			"plum-shaped",
			"olive-shaped"
			);
		
		$texture = array(
			"leathery",
			"waxy",
			"smooth",
			"fuzzy",
			"glowing",
			"spiny",
			"spiky",
			"pitted",
			"dented",
			"scaly",
			"rough",
			"hairy",
			"grooved",
			"smooth",
			"bumpy"
			);
		
		$peel = array(
			"very thin",
			"thin",
			"thick",
			"very thick",
			"unevenly thick",
			"hard"
			);
		
		switch ($category) {
		case 0:
			return array(
				"size" => $size_s,
				"shape" => $shape0[rand(0, sizeof($shape0)-1)],
				"texture" => $texture[rand(0, sizeof($texture)-1)],
				"skin" => $peel[rand(0, sizeof($peel)-1)]
				);
		case 1:
			return array(
				"size" => $size_s,
				"shape" => $shape1[rand(0, sizeof($shape1)-1)],
				"texture" => $texture[rand(0, 2)],
				"skin" => $peel[rand(0, 2)]
				);
		case 2:
			return array(
				"size" => $size_s,
				"shape" => $shape2[rand(0, sizeof($shape2)-1)],
				"texture" => $texture[rand(sizeof($texture)-5, sizeof($texture)-2)],
				"skin" => $peel[rand(1, 4)]
				);
		case 3:
			return array(
				"size" => $size_s,
				"shape" => $shape3[rand(0, sizeof($shape3)-1)],
				"texture" => $texture[rand(1, 3)],
				"skin" => false
				);
		case 4:
			return array(
				"size" => $size_s,
				"shape" => $shape4[rand(0, sizeof($shape4)-1)],
				"texture" => $texture[rand(sizeof($texture)-3, sizeof($texture)-1)],
				"skin" => $peel[rand(0, 2)]
				);
		case 5:
			return array(
				"size" => $size_s,
				"shape" => $shape5[rand(0, sizeof($shape5)-1)],
				"texture" => "pitted",
				"skin" => $peel[rand(0, 3)]
				);
		case 6:
			return array(
				"size" => $size_s,
				"shape" => $shape6[rand(0, sizeof($shape6)-1)],
				"texture" => "waxy",
				"skin" => $peel[rand(0, 2)]
				);
		case 7:
			return array(
				"size" => $size_s,
				"shape" => $shape7[rand(0, sizeof($shape7)-1)],
				"texture" => $texture[rand(0, 3)],
				"skin" => $peel[rand(0, 2)]
				);
		}
	}
	
	function generateFlavor($category=0) {
		//category: 0 - tropical fruit, 1 - berry, 2 - root vegetable, 5 - citrus fruit, 6 - apple, 7 - drupes
		$sweet = false;
		$sour = false;
		$bland = false;
		$bitter = false;
		$salty = false;
		$spicy = false;
		
		$sour1 = array(
			"acidic",
			"sour",
			"tart",
			"astringent",
			"tangy"
			);
		$sweet1 = array(
			"sweet",
			"jammy",
			"saccharine"
			);
		$mod1 = array(
			"strong",
			"mild",
			"aromatic",
			"watery",
			"chalky"
			);
		
		$roots = array(
			"sulphuric",
			"bitter",
			"sweet",
			"bland",
			"flavourful",
			"earthy",
			"woody",
			"soapy",
			"nutty",
			"peppery",
			"starchy"
			);
		
		$citrus = array(
			"orange",
			"lemon",
			"grapefruit",
			"lime",
			"tangerine",
			"mandarin orange",
			"pomelo",
			"cucumber"
			);
		
		$pome = array(
			"sweet",
			"mildly sweet",
			"tart",
			"sour",
			"astringent",
			"sweet and sour",
			"bitter",
			"bland"
			);
		
		$drupe = array(
			"plum",
			"nectarine",
			"apricot",
			"peach",
			"mango",
			"cherry",
			"avocado",
			"olive"
			);
		
		$sweet = array(
		"pineapple",
		"banana",
		"pear",
		"vanilla",
		"apple",
		"bubblegum",
		"honeydew",
		"peach",
		"mango",
		"papaya",
		"strawberry",
		"grape",
		"candy",
		"custard",
		"roses",
		"plum",
		"apricot",
		"caramel",
		"raspberry",
		"blueberry",
		"raisins",
		"jam",
		"cherry",
		"passionfruit",
		"lychee",
		"fig",
		"coconut",
		"dates",
		"honey",
		"cola");
		
		$neutral = array(
		"cucumber",
		"potato",
		"bread",
		"zucchini",
		"chalk",
		"cauliflower",
		"tofu",
		"almonds",
		"squash",
		"crayons");
		
		$sour = array(
		"lime",
		"tangerine",
		"grapefruit",
		"tomato",
		"kiwi",
		"cranberries",
		"orange");
		
		$weird = array(
		"onion",
		"fish",
		"cheese",
		"sweaty socks",
		"shrimp",
		"tea",
		"mushrooms",
		"vomit",
		"snot",
		"cloves",
		"garlic",
		"allspice",
		"coffee",
		"crab meat");
		
		$modifiers = array(
		"nutty",
		"soapy",
		"salty",
		"lemony",
		"tart",
		"tangy",
		"sweet",
		"sour",
		"juicy",
		"mild",
		"melony",
		"musky",
		"buttery",
		"creamy",
		"hearty",
		"mellow",
		"zesty",
		"chocolaty",
		"mellow",
		"spicy",
		"earthy",
		"watery");
		
		if ($category==0) {
			
			$rand = rand(0,11);
			
			switch ($rand) {
			case 0:
				// 2 sweet
				$rand2 = range(0, sizeof($sweet)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $sweet[$rand2[0]] . " and " . $sweet[$rand2[1]] . ".";
				$sweet = true;
				break;
			case 1:
				// 3 sweet
				$rand2 = range(0, sizeof($sweet)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $sweet[$rand2[0]] . ", " . $sweet[$rand2[1]] . " and " . $sweet[$rand2[2]] . ".";
				$sweet = true;
				break;
			case 2:
				//adjective sweet
				$mod_ad = $modifiers[rand(0,sizeof($modifiers)-1)];
				if ($mod_ad == "salty") $salty = true;
				else if ($mod_ad == "tart"||$mod_ad == "tangy") $sour = true;
				else if ($mod_ad == "spicy") $spicy = true;
				$string = "It tastes like " . $mod_ad . " " . $sweet[rand(0,sizeof($sweet)-1)] . ".";
				$sweet = true;
				break;
			case 3:
				//sweet and modified sweet
				$rand2 = range(0, sizeof($sweet)-1);
				shuffle($rand2);
				$mod_ad = $modifiers[rand(0,sizeof($modifiers)-1)];
				if ($mod_ad == "salty") $salty = true;
				else if ($mod_ad == "tart"||$mod_ad == "tangy") $sour = true;
				else if ($mod_ad == "spicy") $spicy = true;
				$string = "It tastes like a blend of " . $sweet[$rand2[0]] . " and " . $mod_ad . " " . $sweet[$rand2[1]] . ".";
				$sweet = true;
				break;
			case 4:
				// two neutrals
				$rand2 = range(0, sizeof($neutral)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $neutral[$rand2[0]] . " and " . $neutral[$rand2[1]] . ".";
				$bland = true;
				break;
			case 5:
				// 2 sours
				$rand2 = range(0, sizeof($sour)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $sour[$rand2[0]] . " and " . $sour[$rand2[1]] . ".";
				$sour = true;
				break;
			case 6:
				//sweet and sour
				$string = "It tastes like a mix of " . $sour[rand(0,sizeof($sour)-1)] . " and " . $sweet[rand(0,sizeof($sweet)-1)] . ".";
				$sour = true;
				$sweet = true;
				break;
			case 7:
				//sweet and weird
				$string = "It tastes like a strange mix of " . $sweet[rand(0,sizeof($sweet)-1)] . " and " . $weird[rand(0,sizeof($weird)-1)] . ".";
				$sweet = true;
				break;
			case 8:
				//neutral and weird
				$string = "It tastes like a strange mix of " . $neutral[rand(0,sizeof($neutral)-1)] . " and " . $weird[rand(0,sizeof($weird)-1)] . ".";
				$bland = true;
				break;
			case 9:
				//modified neutral
				$mod_ad = $modifiers[rand(0,sizeof($modifiers)-1)];
				if ($mod_ad == "salty") $salty = true;
				else if ($mod_ad == "tart"||$mod_ad == "tangy") $sour = true;
				else if ($mod_ad == "spicy") $spicy = true;
				else if ($mod_ad == "sweet") $sweet = true;
				else $bland = true;
				$string = "It tastes like " . $mod_ad . " " . $neutral[rand(0,sizeof($neutral)-1)] . ".";
				break;
			case 10:
				// 2 sweet with a hint of third
				$rand2 = range(0, sizeof($sweet)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $sweet[$rand2[0]] . " and " . $sweet[$rand2[1]] . ", with a hint of " . $sweet[$rand2[2]] . ".";
				$sweet = true;
				break;
			case 11:
				// 2 sweet with a hint of sour
				$rand2 = range(0, sizeof($sweet)-1);
				shuffle($rand2);
				$string = "It tastes like a blend of " . $sweet[$rand2[0]] . " and " . $sweet[$rand2[1]] . ", with a hint of " . $sour[rand(0,sizeof($sour)-1)] . ".";
				$sweet = true;
				$sour = true;
				break;
			}
		}
		else if ($category==1) {
			//berry
			$rand = rand(0,4);
			switch ($rand) {
			case 0:
				$string = "It tastes very " . $sour1[rand(0, sizeof($sour1)-1)] . ".";
				$sour = true;
				break;
			case 1:
				$string = "It tastes mildy " . $sour1[rand(0, sizeof($sour1)-1)] . ".";
				$sour = true;
				break;
			case 2:
				$string = "It tastes " . $sweet1[rand(0, sizeof($sweet1)-1)] . ".";
				$sweet = true;
				break;
			case 3:
				$string = "It tastes " . $sour1[rand(0, sizeof($sour1)-1)] . " but " . $sweet1[rand(0, sizeof($sweet1)-1)] . ".";
				$sour = true;
				$sweet = true;
				break;
			case 4:
				$string = "It tastes " . $sweet1[rand(0, sizeof($sweet1)-1)] . " and " . $mod1[rand(0, sizeof($mod1)-1)] . ".";
				$sweet = true;
				break;
			}
		}
		else if ($category==2) {
			$rand = rand(0,4);
			switch ($rand) {
			case 0:
				$string = "It tastes very " . $roots[rand(0, sizeof($roots)-1)] . ".";
				break;
			case 1:
				$string = "It tastes mildy " . $roots[rand(0, sizeof($roots)-1)] . ".";
				break;
			case 2:
				$rand2 = range(0, sizeof($roots)-1);
				shuffle($rand2);
				$string = "It tastes mainly " . $roots[$rand2[0]] . " but also " . $roots[$rand2[1]] . ".";
				break;
			case 3:
				$rand2 = range(0, sizeof($roots)-1);
				shuffle($rand2);
				$string = "It tastes " . $roots[$rand2[0]] . " and slightly " . $roots[$rand2[1]] . ".";
				break;
			case 4:
				$rand2 = range(0, sizeof($roots)-1);
				shuffle($rand2);
				$string = "It tastes both " . $roots[$rand2[0]] . " and " . $roots[$rand2[1]] . ".";
				break;
			}
		}
		else if ($category==5) {
			$cit_s = $citrus[rand(0, sizeof($citrus)-1)];
			$rand = rand(0,4);
			switch ($rand) {
			case 0:
				$string = "It tastes a lot like " . $cit_s . ".";
				if ($cit_s == "cucumber") $bland = true;
				else $sour = true;
				break;
			case 1:
				$string = "It tastes mildly like " . $cit_s . ".";
				if ($cit_s == "cucumber") $bland = true;
				else $sour = true;
				break;
			case 2:
				$rand2 = range(0, sizeof($citrus)-1);
				shuffle($rand2);
				$string = "It tastes like " . $citrus[$rand2[0]] . " with a hint of " . $citrus[$rand2[1]] . ".";
				break;
			case 3:
				$string = "It tastes like sweet " . $cit_s . ".";
				$sweet = true;
				if ($cit_s == "cucumber") $bland = true;
				else $sour = true;
				break;
			case 4:
				$rand2 = range(0, sizeof($citrus)-1);
				shuffle($rand2);
				$string = "It tastes like a mixture of " . $citrus[$rand2[0]] . " and " . $citrus[$rand2[1]] . ".";
				$sour = true;
				break;
			}
		}
		else if ($category==6) {
			$pome_s = $pome[rand(0, sizeof($pome)-1)];
			if ($pome_s == "sweet and sour") {
				$sour = true;
				$sweet = true;
			}
			else if ($pome_s == "sweet"||$pome_s == "mildy sweet") $sweet = true;
			else if ($pome_s == "bland") $bland = true;
			else if ($pome_s == "bitter") $bitter = true;
			else $sour = true;
			$string = "It tastes " . $pome_s . ".";
		}
		else if ($category==7) {
			$drupe_s = $drupe[rand(0, sizeof($drupe)-1)];
			if ($drupe_s == "avocado") $bland = true;
			else if ($drupe_s == "olive") $bitter = true;
			else $sweet = true;
			$rand = rand(0,7);
			switch ($rand) {
			case 0:
				$string = "It tastes a lot like " . $drupe_s . ".";
				break;
			case 1:
				$string = "It tastes faintly like " . $drupe_s . ".";
				break;
			case 2:
				$rand2 = range(0, sizeof($drupe)-1);
				shuffle($rand2);
				$string = "It tastes like " . $drupe[$rand2[0]] . " with a hint of " . $drupe[$rand2[1]] . ".";
				break;
			case 3:
				$string = "It tastes like sweet " . $drupe_s . ".";
				$sweet = true;
				break;
			case 4:
				$rand2 = range(0, sizeof($drupe)-1);
				shuffle($rand2);
				$string = "It tastes like a mixture of " . $drupe[$rand2[0]] . " and " . $drupe[$rand2[1]] . ".";
				break;
			case 5:
				$string = "It tastes like tangy " . $drupe_s . ".";
				$sour = true;
				break;
			case 6:
				$string = "It tastes like tart " . $drupe_s . ".";
				$sour = true;
				break;
			case 7:
				$string = "It tastes like slightly salty " . $drupe_s . ".";
				$salty = true;
				break;
			}
		}
		
		return array (
			"string" => $string,
			"sweet" => $sweet,
			"sour" => $sour,
			"bitter" => $bitter,
			"bland" => $bland,
			"spicy" => $spicy
			);
	}
	
	public function getExitCoordinates() {
		if (is_numeric($this->x)&&is_numeric($this->y)) return array(
			"x" => $this->x,
			"y" => $this->y,
			"lx" => $this->localx,
			"ly" => $this->localy
			);
		if ($this->parent==0) return array(
			"x" => -1,
			"y" => 0,
			"lx" => 0,
			"ly" => 0
			);
		$parent = new Obj ($this->mysqli, $this->parent);
		return $parent->getExitCoordinates();
	}
	
	function changeGroupRule($rule, $value) {
		if ($rule=="join") $attr = 97;
		else if ($rule=="command") $attr = 96;
		else return -1;//invalid rule
		$this->setAttribute($attr, $value);
		return 1;
	}
	
	function getGroupRule($rule) {
		if ($rule=="join") $attr = 97;
		else if ($rule=="command") $attr = 96;
		else return -1;//invalid rule
		$value = $this->getAttribute($attr);
		if (!$value) return 0;
		return $value;
	}
	
	function getPassengers($livingOnly = true) {
		if ($livingOnly) $passengers = $this->getContents("2");
		else $passengers = $this->getContents("2, 10");
		if (!$passengers) return -1;
		else return $passengers;//note: this gets body ids, not charids
	}
	
	function getPeopleWithStatus($rule, $status) {
		$retArr = array();
		//rule: 1 - right to command, 2 - right to join
		$sql = "SELECT `charFK` FROM `char_rules` WHERE `objFK`=$this->uid AND `value`=$status AND `rule`=$rule";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		else return -1;
	}
	
	function getPassengersInc() {
		//This includes the owner
		$bodyid = $this->parent;
		if ($bodyid == 0) return -1;//This isn't in an inventory so this isn't valid
		$op = new Obj($this->mysqli, $bodyid);
		$ownerid = $op->getCharid();
		if ($ownerid==-1) return -2;//The parent isn't a character
		$retArr[] = $ownerid;
		$arr = $this->getContents("2");//people
		if (!$arr) return $retArr;//Just the owner
		foreach ($arr as $bo) {
			$o = new Obj($this->mysqli, $bo);
			$retArr[] = $o->getCharid();
		}
		return $retArr;
	}
	
	function getAPgroup() {
		$arr = $this->getPassengersInc();
		if ($arr<0) return $arr;//pass forward error message
		$retArr = array();
		foreach ($arr as $passenger) {
			$p = new Character($this->mysqli, $passenger);
			$ap = $p->getAP();
			$retArr[] = array("charid" => $passenger, "ap" => $ap);
		}
		return $retArr;
	}
	
	function compareAPgroup($ap) {
		$arr = $this->getAPgroup();
		if ($arr<0) return $arr;//pass forward error message
		$counter = 0;
		foreach ($arr as $p) {
			if ($p["ap"]<$ap) {
				$counter++;
			}
		}
		return $counter;
	}
	
}
?>
