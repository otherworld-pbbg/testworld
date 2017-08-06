<?php
class CombatSettings {
	
	private $mysqli;
	public $uid;
	public $owner;
	public $subpage;
	public $name = "Default";
	public $target=0;
	public $hitSelection=1;
	public $hitExclusions = array(
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
		0
		);
	public $healExcludeAlways = array();
	public $healExcludeNever = array();
	public $blockSelection=1;
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
}
?>
