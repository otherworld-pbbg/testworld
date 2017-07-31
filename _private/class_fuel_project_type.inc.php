<?php

include_once "class_character.inc.php";
include_once("constants.php");

class FuelProjectType {
	private $mysqli;
	public $uid=0;
	public $machine=0;
	public $ap=0;
	public $reserve=0;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
	}
	
	public function getInfo() {
		$sql = "SELECT `machine`, `setup_ap`, `reserve` FROM `fuel_project_types` WHERE `uid`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->machine = $row[0];
			$this->ap = $row[1];
			$this->reserve = $row[2];
		}
	}
	
	public function start($container, $charid) {
		$starter = new Character($this->mysqli, $charid);
		$starter->getBasicData();
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$starter->bodyId AND `presetFK`=211";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$apcheck = $starter->checkAP($this->ap);
			if ($apcheck == -2) {
				$firewood = $this->checkFirewood($container, $charid);
				if ($firewood==-3) return -3;//No firewood
				$something = false;
				foreach($firewood as $tinder) {
					if ($tinder["flammable"]==1) {
						$tinder_o = new Obj($this->mysqli, $tinder["uid"]);
						$result = $tinder_o->ignite($charid);
						if ($result == -2) return -4;//ignition failed
						$something = true;
					}
				}
				if ($something) {
					$starter->spendAP($this->ap);
					$curTime = new Time($this->mysqli);
					$sql = "INSERT INTO `fuel_projects` (`ptype`, `machineFK`, `startDatetime`, `startMinute`, `status`, `ap_invested`) VALUES ($this->uid, $container, ".$curTime->dateTime.", " . $curTime->minute . ", 1, $this->ap)";
					$this->mysqli->query($sql);
					$result = $this->mysqli->insert_id;
					if ($result) return 100;
					else return -5;//project creation failed
				}
			}
			else return -2;//not enough ap
		}
		else return -1;//no tool
	}
	
	public function checkFirewood($container, $charcheck) {
		$arr = array();
		$fireplace = new Obj($this->mysqli, $container);
		$contents = $fireplace->getContents();
		if (!$contents) return -3;//no burning material
		else {
			foreach ($contents as $possible) {
				$pos = new Obj($this->mysqli, $possible);
				$pos->getBasicData();
				$flammable = $pos->getAttribute(48);
				$on_fire = $pos->getAttribute(ATTR_ON_FIRE);
				$per_hour = $pos->getAttribute(50);
				if ($flammable) $arr[] = array(
					"uid" => $possible,
					"flammable" => $flammable,
					"on_fire" => $on_fire,
					"per_hour" => $per_hour,
					"deleted" => false
					);
			}
			if (!empty($arr)) return $arr;
		}
		return -3;
	}
}
?>
