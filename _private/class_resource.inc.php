<?
include_once("generic.inc.php");

class Resource {
	private $mysqli;
	public $uid;
	public $name = "";
	public $category = false;
	public $natural = 0;
	public $maxDeposit = 0;
	public $maxPoints = 0;
	public $gathered = 0;
	public $catName = false;
	public $preset = 20;
	public $plural = "";

	public function __construct($mysqli, $uid=0, $preset=20) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		$this->preset = $preset;
		
		if ($uid>0) $this->loadData();
	}
	
	function loadData() {
		
		$sql = "SELECT `name`, `category`, `natural`, `maxDeposit`, `maxPoints`, `gathered`, `plural` FROM `res_subtypes` WHERE `uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->name = $row[0];
			$this->category = $row[1];
			$this->natural = $row[2];
			$this->maxDeposit = $row[3];
			$this->maxPoints = $row[4];
			$this->gathered = $row[5];
			$this->plural = $row[6];
			
			if ($this->preset!=20) {
				$sql2 = "SELECT `name` FROM `o_presets` WHERE `uid`=$this->preset LIMIT 1";
				$res2 = $this->mysqli->query($sql2);
				if (mysqli_num_rows($res2)) {
					$row = mysqli_fetch_row($res2);
					$str = $row[0];
					
					$str = str_replace(array("#MATERIAL#", "#PLURAL#"), array($this->name, $this->plural), $str);
					$this->name = $str;
				}
			}
			
			return true;
		}
		else return false;
	}
	
	function listResources($natural=false) {
		if ($natural) $limiter = "`natural`>0";
		else $limiter = "1";
		
		$retArr = array();
		
		$sql = "SELECT `uid`, `name`, `category` FROM `res_subtypes` WHERE $limiter ORDER BY `category`, `name`";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($arr = mysqli_fetch_assoc($res)) {
				$retArr[] = $arr;
			}
			return $retArr;
		}
		else return false;
	}
	
	function getCategory() {
		if ($this->catName) return $this->catName;
		else {
			if ($this->category==false) $this->loadData();
			$sql = "SELECT `name` FROM `res_categories` WHERE `uid`=$this->category LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$this->catName = $row[0];
				return $this->catName;
			}
			else return "undefined";
		}
	}
	
	function isCountable() {
		$sql = "SELECT `resFK` FROM `res_attrs` WHERE `attrFK`=44 AND `value`=1 AND `resFK`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			return true;
		}
		else return false;
	}
	
	function getAttr($attr) {
		$sql = "SELECT `value` FROM `res_attrs` WHERE `attrFK`=$attr AND `resFK`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			return $row[0];
		}
		else return false;
	}
	
	function getWeightBounds() {
		$min = 0;
		$max = 0;
		$category = 0;//1 - small weight, 2 - heavy weight
		
		$sql = "SELECT `attrFK`, `value` FROM `res_attrs` WHERE `attrFK` IN (40, 41, 42, 43) AND `resFK`=$this->uid LIMIT 4";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				if ($row[0]==40) {
					$category = 1;
					$min = $row[1];
				}
				else if ($row[0]==41) {
					$category = 1;
					$max = $row[1];
				}
				else if ($row[0]==42) {
					$category = 2;
					$min = $row[1];
				}
				else if ($row[0]==43) {
					$category = 2;
					$max = $row[1];
				}
			}
		}
		if ($category==0) return false;
		else return array(
			"category" => $category,
			"min" => $min,
			"max" => $max);
	}
	
	function getPieces($weight) {
		$countable = $this->getAttr(44);
		if (!$countable) return 1;
		$min_sm_mass = $this->getAttr(40);
		$max_sm_mass = $this->getAttr(41);
		$min_lg_mass = $this->getAttr(42);
		$max_lg_mass = $this->getAttr(43);
		$unit_mass = $this->getAttr(6);
		
		if ($min_sm_mass&&$max_sm_mass) {
			$min_pieces = max(1,round($weight/$max_sm_mass));
			$max_pieces = max(1,round($weight/$min_sm_mass));
			return rand($min_pieces, $max_pieces);
		}
		if ($min_lg_mass&&$max_lg_mass) {
			$min_pieces = max(1,round($weight/$max_lg_mass/1000));
			$max_pieces = max(1,round($weight/$min_lg_mass/1000));
			return rand($min_pieces, $max_pieces);
		}
		if ($unit_mass) {
			return max(1,round($weight/$unit_mass));
		}
		return 1;
	}
	
	function addStrings($uids, $strings, $append=false) {
		$uid_arr = explode(",", $uids);
		
		foreach ($uid_arr as $curid) {
			if ($append) $sql = "UPDATE `res_strings` SET `str`=CONCAT(`str`, ',$strings') WHERE `resFK`=$curid";
			else $sql = "UPDATE `res_strings` SET `str`='$strings' WHERE `resFK`=$curid";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				$sql = "INSERT INTO `res_strings` (`resFK`, `str`) VALUES ($curid, '$strings')";
				$this->mysqli->query($sql);
			}
		}
		return true;
	}
	
	public function getStrings() {
		$sql = "SELECT `str` FROM `res_strings` WHERE `resFK`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return "";
	}
	
	public function generatePerk($y) {
		$perk = rand(26, 35);//These numbers correspond to plant strengths
		$ranges = array(
			array(1,10),
			array(10,20),
			array(11,21),
			array(12,22),
			array(13,22),
			array(14,23),
			array(20,24),
			array(21,25),
			array(22,26),
			array(23,27),
			array(24,28),
			array(25,29),
			array(26,30),
			array(27,32),
			array(30,35),
			array(30,45),
			array(30,50),
			array(30,60),
			array(35,74),
			array(75,100)
			);//average values are more likely
		$range = rand(0,19);
		$source = $ranges[$range];
		$rand2 = rand($source[0], $source[1]);
		if ($y<-3000||$y>3000&&$perk==29) $perk=27;
		else if ($y>-2000&&$y<2000&&$perk==27) $perk=29;
		
		return array(
			"perk" => $perk,
			"value" => $rand2
			);
	}
	
	public function getAncestors($child, $resArr, $rounds=0) {
		
		if ($rounds>5) return false;
		$sql = "SELECT `parent1`, `parent2` FROM `res_subtypes` WHERE `uid`=$child LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$resArr[] = $row[0];
			$resArr[] = $row[1];
			if ($row[0]>0) {
				$ancestors1 = $this->getAncestors($row[0], $resArr, $rounds++);
				$resArr = array_merge($resArr, $ancestors1);
			}
			if ($row[1]>0) {
				$ancestors2 = $this->getAncestors($row[1], $resArr, $rounds++);
				$resArr = array_merge($resArr, $ancestors2);
			}
			
			return array_unique($resArr);
		}
		else return array_unique($resArr);
	}
	
	public function getPossibleHybrids($other) {
		$retArr = array();
		$sql = "SELECT `uid` FROM `res_subtypes` WHERE (`parent1`=$this->uid AND `parent2`=$other) OR (`parent2`=$this->uid AND `parent1`=$other);";//This checks if a direct hybrid already exists
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
		}
		if (!empty($retArr)) return $retArr;
		
		$anc1 = $this->getAncestors($this->uid, array());
		$anc2 = $this->getAncestors($other, array());
		
		$anc1[] = $this->uid;
		$anc2[] = $other;
		
		$str1 = arrayToComma($anc1);
		$str2 = arrayToComma($anc2);
		
		$sql2 = "SELECT `uid` FROM `res_subtypes` WHERE ((`parent1` IN ($str1) AND `parent2` IN ($str2)) OR (`parent2` IN ($str1) AND `parent1` IN ($str2))) AND `parent1`>0 AND `parent2`>0;";//checks if any of their ancestors can create hybrids
		$res = $this->mysqli->query($sql2);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
		}
		return $retArr;
	}
	
	function getMultiplier($y) {
		
		$presets = array (
			24 => 3,
			190 => 1,
			206 => 5,
			364 => 4,
			365 => 3,
			366 => 3,
			438 => 1,
			513 => 3,
			524 => 2,
			525 => 2,
			526 => 3,
			528 => 2
			);
		
		if (!array_key_exists($this->preset, $presets)) return 1;
		
		$curTime = new Time($this->mysqli);
		$sel = $presets[$this->preset];
		
		switch ($sel) {
		case 1:
			$result = $curTime->getMultiplierSpring($y);
			break;
		case 2:
			$result = $curTime->getMultiplierSummer($y);
			break;
		case 3:
			$result = $curTime->getMultiplierHarvest($y);
			break;
		case 4:
			$result = $curTime->getMultiplierLateFall($y);
			break;
		case 5:
			$result = $curTime->getMultiplierWarm($y);
			break;
		default:
			$result = 1;
		}
		return $result;
	}
}
?>
