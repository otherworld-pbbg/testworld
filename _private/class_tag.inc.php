<?php
include_once("class_character.inc.php");
include_once("class_time.inc.php");
include_once("generic.inc.php");

class Tag
{
	private $mysqli;
	var $keyword = "";
	var $num = 0;
	var $params = array();
	var $keywords = array(
				"DCNAME",//ID
				"GROUPNAME",//ID
				"RESOURCE",//ID, PRESET (optional)
				"PRESET",//ID
				"ANIMAL",//ID, COUNT (optional)
				"DIR",//VAL (number 1 to 8 clockwise from north)
				"COUNT",//VAL (numeric)
				"PRONOUN"//VAL (any word but generally his, hers, their, him, her, them)
				);

	public function __construct($mysqli, $keyword, $num, $params) {
		//keywords must be from the list
		//params is an array formatted "PARAM" => value
		//all param keys must be uppercase and contain no numbers or special characters
		//values can contain numbers, letters and underscores
		$this->mysqli = $mysqli;
		$this->keyword = $keyword;
		$this->num = $num;
		$this->params = $params;
	}
	
	public function toString() {
		$str = "<" . $this->keyword . "_" . $this->num;
		foreach ($this->params as $key => $param) {
			$str .= " #" . $key . "#=". $param;
		}
		$str .= ">";
		return $str;
	}
}
?>
