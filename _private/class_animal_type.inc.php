<?php

//include_once("class_time.inc.php");
//include_once("local_map.inc.php");
//include_once("class_resource.inc.php");
include_once("class_obj.inc.php");
include_once("constants.php");

class AnimalType {
	private $mysqli;
	public $uid=0;
	public $name = "(unknown animal)";
	public $plural = "";
	public $attack_types = -1;
	public $danger = -1;
	
	public function __construct($mysqli, $uid) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($this->uid>0) $this->getBasicData();
	}
	
	public function getBasicData() {
		$sql = "SELECT `animal_name`, `attack_types`, `danger`, `plural` FROM `animals` WHERE `uid`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->name = $row[0];
			$this->attack_types = $row[1];
			$this->danger = $row[2];
			if ($row[3]!="") $this->plural = $row[3];
			else $this->plural = $row[0] . "s";
		}
	}
	
	public function createRepresentative($x, $y, $lx, $ly, $datetime, $minute) {
		$weight = $this->getAttribute(ATTR_LARGE_MASS);
		if (!$weight) $weight = $this->getAttribute(ATTR_SMALL_MASS);
		else $weight = $weight*1000;
		if (!$weight) $weight=1000;
		$weight = rand(round($weight*0.8), round($weight*1.2));
		$class = $this->getAttribute(ATTR_ANIMAL_CLASS);
		if (!$class) $class = 0;
		$classes = array(
		0 => 8,
		1 => 9,
		2 => 11,
		3 => 33,
		4 => 43,
		5 => 50,
		11 => 51,
		12 => 52,
		13 => 59,
		14 => 60,
		15 => 61,
		21 => 62,
		22 => 63,
		23 => 64,
		24 => 65,
		25 => 69,
		31 => 469,
		32 => 470,
		33 => 471,
		34 => 472,
		35 => 473,
		41 => 474,
		42 => 475,
		43 => 476,
		44 => 477,
		45 => 478,
		51 => 479,
		52 => 480,
		53 => 481,
		54 => 482,
		55 => 483,
		62 => 484,
		63 => 485,
		64 => 486,
		65 => 487
		);//these correspond to presets
		
		$ob = new Obj($this->mysqli, 0);
		$result = $ob->create($classes[$class], 4, 0, "Discovered wild animal", $x, $y, $lx, $ly, $this->uid, 1, $weight, $datetime, $minute);
		if ($result) {
			$new_a = new Obj($this->mysqli, $result);
			$new_a->setAttribute(ATTR_BLOOD, round($weight/10));//blood, basically hit points
			return $result;
		}
		else return -1;
	}
	
	function getAttribute($attr) {
		//51
		//1 - tiny mammal, 2 - small mammal, 3 - medium mammal, 4 - large mammal, 5 - huge mammal
		//11 - tiny bird, 12 - small bird, 13 - medium bird, 14 - large bird, 15 - huge bird
		//21 - tiny marsupial, 22 small marsupial, 23 - medium marsupial, 24 - large marsupial, 25 - huge marsupial
		//31 - tiny reptile, 32 - small reptile, 33 - medium reptile, 34 - large reptile, 35 - huge reptile
		//41 - tiny amphibian, 42 - small amphibian, 43 - medium amphibian, 44 - large amphibian, 45 - huge amphibian
		//51-55 fish
		//6x hooved animals
		$sql = "SELECT `value` FROM `animal_attributes` WHERE `animal_type`=$this->uid AND `attributeFK`=$attr LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		if ($res->num_rows==0) return false;//value doesn't exist
		else {
			$row = $res->fetch_object();
			$value = $row->value;
		}
		return $value;
	}
	
	function getName() {
		return $this->name;
	}
	
	function getPlural() {
		return $this->plural;
	}
	
	function getAttackTypes() {
		return explode(",", $this->attack_types);
	}
	
	function getDanger() {
		return $this->danger;
	}
	
	function getStrategy($enemy_down) {
		if ($enemy_down) $ast = $this->getAttribute(63);
		else $ast = $this->getAttribute(62);
		return $ast;
	}
}
