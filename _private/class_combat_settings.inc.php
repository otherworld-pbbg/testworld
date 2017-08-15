<?php
include_once("constants.php");

class CombatSettings {
	
	private $mysqli;
	public $uid;
	public $owner;
	public $subpage;
	public $name = "Default";
	public $target=COMBAT_TARGET_RANDOM;//for example random, fastest, slowest
	public $hitSelection=1;
	public $hitExclusions = array(
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		1,
		1
		);
	public $hitPriorities = array();
	public $hitExcludeAlways = array();
	public $hitExcludeNever = array();
	public $bodyparts = array(
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0
		);
	public $healExclusions = array(
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0,
		0
		);
	public $healPriorities = array();
	public $healExcludeAlways = array();
	public $healExcludeNever = array();
	public $blockSelection=1;
	public $blockSelection2=2;
	public $rules;
	
	public function __construct($mysqli, $owner, $subpage, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		$this->owner = $owner;
		$this->subpage = $subpage;
		
		if ($this->uid>0) $this->fetchFromDb();
	}
	
	public function fetchFromDb() {
		
	}
	
	public function getRules() {
		for ($i=1; $i<3; $i++) {
			for ($i=1; $i<4; $i++) $this->getRule($i, $j);
		}
	}
	
	public function getRule($rule, $method) {
		$sql = "SELECT `uid`, `objFK`, `priority` FROM `combat_rules` WHERE `settings_page`=" . $this->uid . " AND `rule`=$rule AND `method`=$method ORDER BY `priority` DESC, `uid` ASC";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_assoc($res)) {
				$retArr[] = $row;
			}
			if ($rule==1) {
				if ($method==1) $this->hitPriorities = $retArr;
				if ($method==2) $this->hitExcludeNever = $retArr;
				if ($method==3) $this->hitExcludeAlways = $retArr;
			}
			if ($rule==2) {
				if ($method==1) $this->healPriorities = $retArr;
				if ($method==2) $this->healExcludeNever = $retArr;
				if ($method==3) $this->healExcludeAlways = $retArr;
			}
			
			return $retArr;
		}
		return array();
	}
}
?>
