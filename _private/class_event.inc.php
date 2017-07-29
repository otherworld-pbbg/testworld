<?php
include_once("class_character.inc.php");
include_once("class_time.inc.php");
include_once("class_tag.inc.php");
include_once("generic.inc.php");

class Event
{
	private $mysqli;
	var $uid = 0;
	var $charid = 0;
	var $eventtype = 0;
	var $custom_contents = "";
	var $tags = "";
	var $timestamp = 1010100;
	var $minute = 0;

	public function __construct($mysqli, $charid, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		$this->charid = $charid;//observer id
		
		if ($this->uid>0) $this->getData();
	}
	
	public function fillData($uid, $eventtype, $tags, $custom_contents, $timestamp, $minute) {
		//This is for when stuff has been mass loaded and it wouldn't make sense to get each event one by one. This requires that the event is initialized without an id to prevent it from loading data
		$this->uid = $uid;
		$this->eventtype = $eventtype;
		$this->tags = $tags;
		$this->custom_contents = $custom_contents;
		$this->timestamp = $timestamp;
		$this->minute = $minute;
	}
	
	public function getData() {
		$sql = "SELECT `etype`, `tags`, `custom`, `timestamp`, `minute` FROM `events` WHERE `uid`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->eventtype = $row[0];
			$this->tags = $row[1];
			$this->custom_contents = $row[2];
			$this->timestamp = $row[3];
			$this->minute = $row[4];
		}
	}
	
	public function create($eventtype, $tags, $custom_contents="", $timestamp = 0, $minute = 0) {
		$this->eventtype = $eventtype;
		//tags are objects of type Tag
		foreach ($tags as $t) {
			$this->tags .= $t->toString();
		}
		$this->custom_contents = $custom_contents;
		if ($timestamp>0) {
			$this->timestamp = $timestamp;
			$this->minute = $minute;
		}
		else {
			$ti = new Time($this->mysqli);
			$this->timestamp = $ti->dateTime;
			$this->minute = $ti->minute;
		}		
		$check = $this->enterDB();
		return $check;
	}
	
	private function enterDB() {
		if ($this->uid>0) {
			$sql = "UPDATE `events` SET `etype`=$this->eventtype, `tags`='$this->tags', `custom`='$this->custom_contents' WHERE `uid`=$this->uid LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==1) {
				return 100;
			}
			else return -1;
		}
		$sql = "INSERT INTO `events` (`etype`, `tags`, `custom`, `timestamp`, `minute`) VALUES ($this->eventtype, '$this->tags', '$this->custom_contents', $this->timestamp, $this->minute)";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->uid = $result;
			return 200;
		}
		else return -2;
	}
	
	public function getTemplate() {
		$eventtypes = array(
			0 => "(error)",
			100 => "<DCNAME_1> traveled <DIR_1>.",
			101 => "<DCNAME_1> traveled <DIR_1> with <DCNAME_2>.",
			102 => "<DCNAME_1> traveled <DIR_1> with <PRONOUN_1> party.",
			103 => "<DCNAME_1> lead <DCNAME_2>'s party <DIR_1>.",
			110 => "<DCNAME_1> traveled <COUNT_1> legs <DIR_1>.",
			111 => "<DCNAME_1> traveled <COUNT_1> legs <DIR_1> with <DCNAME_2>.",
			112 => "<DCNAME_1> traveled <COUNT_1> legs <DIR_1> with <PRONOUN_1> party.",
			113 => "<DCNAME_1> lead <DCNAME_2>'s party <COUNT_1> legs <DIR_1>.",
			200 => "<DCNAME_1> dropped some <RESOURCE_1>.",
			201 => "<DCNAME_1> dropped <PRESET_1>.",
			202 => "<DCNAME_1> gave some <RESOURCE_1> to <DCNAME_2>.",
			203 => "<DCNAME_1> gave <PRESET_1> to <DCNAME_2>.",
			300 => "<DCNAME_1> killed <ANIMAL_1>.",
			301 => "<DCNAME_1> was killed by <ANIMAL_1>.",
			302 => "<DCNAME_1> killed <DCNAME_2> in the heat of combat.",
			303 => "<DCNAME_1> killed <DCNAME_2> after <PRONOUN_2> had surrendered.",
			304 => "<DCNAME_1> killed <DCNAME_2> while <PRONOUN_2> was incapacitated.",
			305 => "<DCNAME_1> attacked <DCNAME_2>.",
			306 => "<DCNAME_1> attacked <DCNAME_2> with <PRONOUN_1> group.",
			307 => "<DCNAME_1> ordered <PRONOUN_1> group to attack <DCNAME_2>'s group.",
			308 => "<DCNAME_1> ordered <DCNAME_2>'s group to attack <DCNAME_3>'s group.",
			309 => "<DCNAME_1> ordered <PRONOUN_1> group to attack <GROUPNAME_1>.",
			310 => "<DCNAME_1> ordered <DCNAME_2>'s group to attack <GROUPNAME_1>.",
			311 => "<DCNAME_1> ordered <GROUPNAME_1> to attack <GROUPNAME_2>.",
			312 => "As a result, <COUNT_1> of the defenders and <COUNT_2> of the offenders were injured.",
			313 => "As a result, <COUNT_1> of the defenders and <COUNT_2> of the offenders died.",
			314 => "As a result, <COUNT_1> of the defenders and <COUNT_2> of the offenders were injured and <COUNT_3> and <COUNT_4> died, respectively.",
			315 => "<GROUPNAME_1> killed <ANIMAL_1> during a hunt.",
			316 => "<DCNAME_1> killed <ANIMAL_1> during a hunt with <GROUPNAME_1>.",
			);
		
		return $eventtypes[$this->eventtype];
	}
	
	public function show() {
		$print_str = $this->getTemplate();
		//in contents, there are tags like <ANIMAL_0>, <DCNAME_1>, <RESOURCE_2>, <PRESET_3>
		//they correspond to tags like <DCNAME 1 #ID#=1>, <ANIMAL 0 #ID#=2>, <RESOURCE_2 #ID#=1 #PRESET#=20>
		//The first delimiter can be a space or an underscore but the second one is always a space
		//tags in contents always have an underscore because it uses direct replace instead of regex
		preg_match_all('/<(?P<keyword>\w+)(\s|_)+(?P<tagnum>[0-9]+) (?P<params>(#([A-Z]+)#=([\w]+)(\s*))+)>/', $this->tags, $matches, PREG_SET_ORDER);
		if ($matches) {
			$keywords = array(
				"DCNAME",
				"GROUPNAME",
				"RESOURCE",
				"PRESET",
				"ANIMAL",
				"DIR",
				"COUNT",
				"PRONOUN"
				);
			foreach ($matches as $tag) {
				
				$rep = "<" . $tag["keyword"] . "_" .  $tag["tagnum"] . ">";//The string that's being replaced, for example <DCNAME_1>
				
				if (in_array($tag["keyword"], $keywords)) {
					
					preg_match_all('/#(?P<var>[A-Z]+)#=(?P<val>[\w]+)/', $tag["params"], $vars, PREG_SET_ORDER);
					switch ($tag["keyword"]) {
					case "DCNAME":
						if ($val = searchSingle($vars, "var", "ID")) {
							
							$observer = new Character($this->mysqli, $this->charid);
							$print_str = str_replace ($rep, $observer->getNameLink($val["val"]), $print_str);
						}
						else $print_str = str_replace ($rep, "(unknown character)", $print_str);
						break;
					case "GROUPNAME":
						if ($val = searchSingle($vars, "var", "ID")) {
							$observer = new Character($this->mysqli, $this->charid);
							$print_str = str_replace ($rep, $group->loadName(), $print_str);					
						}
						else $print_str = str_replace ($rep, "(unknown group)", $print_str);
						break;
					case "RESOURCE":
						if ($val = searchSingle($vars, "var", "ID")) {
							if (!$val2 = searchSingle($vars, "var", "PRESET")) $val2["val"] = "20";
							$resource = new Resource($this->mysqli, $val["val"], $val2);
							$print_str = str_replace ($rep, $resource->name, $print_str);					
						}
						else $print_str = str_replace ($rep, "(unknown resource)", $print_str);
						break;
					case "PRESET":
						if ($val = searchSingle($vars, "var", "ID")) {
							$preset = new Preset($this->mysqli, $val["val"]);
							$print_str = str_replace ($rep, $preset->name, $print_str);					
						}
						else $print_str = str_replace ($rep, "(unknown resource)", $print_str);
						break;
					case "ANIMAL":
						if ($val = searchSingle($vars, "var", "ID")) {
							$at = new AnimalType($this->mysqli, $val["val"]);
							if ($val2 = searchSingle($vars, "var", "COUNT")) $print_str = str_replace ($rep, $val2["val"] . " " .$at->getPlural(), $print_str);
							else $print_str = str_replace ($rep, $at->getName(), $print_str);					
						}
						else $print_str = str_replace ($rep, "(unknown animal)", $print_str);
						break;
					case "DIR":
						if ($val = searchSingle($vars, "var", "VAL")) {
							switch ($val["val"]) {
							case 1: $r = "north";
								break;
							case 2: $r = "north-east";
								break;
							case 3: $r = "east";
								break;
							case 4: $r = "south-east";
								break;
							case 5: $r = "south";
								break;
							case 6: $r = "south-west";
								break;
							case 7: $r = "west";
								break;
							case 8: $r = "north-west";
								break;
							default: $r = "(unknown direction)";
							}
							$print_str = str_replace ($rep, $r, $print_str);					
						}
						else $print_str = str_replace ($rep, "(unknown direction)", $print_str);
						break;
					case "COUNT":
						if ($val = searchSingle($vars, "var", "VAL")) {
							$print_str = str_replace ($rep, $val["val"], $print_str);	
						}
						else $print_str = str_replace ($rep, "(unknown number)", $print_str);
						break;
					case "PRONOUN":
						if ($val = searchSingle($vars, "var", "VAL")) {
							$print_str = str_replace ($rep, $val["val"], $print_str);	
						}
						else $print_str = str_replace ($rep, "(unknown gender)", $print_str);
						break;
					default:
						$print_str = str_replace ($rep, "(error)", $print_str);
					}
				}
				else {
					$print_str = str_replace ($rep, "(misspelled keyword)", $print_str);
				}
			}
		}
		para("[" . $this->getDateTime() . "] " . $print_str);
	}
	
	public function getDateTime() {
		$ts = new Time($this->mysqli, $this->timestamp, $this->minute);
		return $ts->getDateTime();
	}
	
	public function addWitness($charids) {
		$list = "";
		foreach ($charids as $c) {
			if ($list!="") $list.=", ";
			$list .= "($c, $this->uid)";
		}
		$sql = "INSERT INTO `e_witness` (`charid`, `event`) VALUES " . $list;
		if ($this->uid>0) $this->mysqli->query($sql);
	}
}
?>
