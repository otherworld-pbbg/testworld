<?php

class ResourceString {
	
	private $mysqli;
	public $str;
	
	public function __construct($mysqli, $str) {
		$this->mysqli = $mysqli;
		$this->str = $str;
	}
	
	public function getMatchingPreset($searchterm) {
		$presets = array (
			3 => "sprigs",
			20 => "singular",
			24 => "seeds",
			189 => "wood_resinous",
			190 => "sap",
			192 => "wood",
			203 => "root",
			204 => "corm",
			206 => "leaves",
			364 => "berries",
			365 => "fruit",
			366 => "nuts",
			438 => "flowers",
			513 => "pits",
			519 => "bark",
			520 => "inner",
			521 => "twig",
			522 => "rootlets",
			523 => "cones",
			524 => "shoots",
			525 => "greens",
			526 => "pods",
			528 => "plural"
			);
		
		if (!in_array($searchterm, $presets)) return -1;
		
		return array_search($searchterm, $presets);
	}
	
	public function getMatchingPresets() {
		$retArr = array();
		$arr = $this->stringToArr();
		foreach ($arr as $p) {
			$result = $this->getMatchingPreset($p);
			if ($result>-1) $retArr[] = $result;
		}
		return $retArr;
	}

	public function getMatchingResTypes() {
		$retArr = array();
		$arr = $this->stringToArr();
		$like = "";
		foreach ($arr as $string) {
			if ($like != "") $like .= " AND ";
			$like .= "`str` LIKE '%$string%'";
		}
		
		$sql = "SELECT `resFK`, `str` FROM `res_strings` WHERE $like";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$okay = true;
				$strings = explode(",", $row[1]);
				foreach ($arr as $string) {
					if (!in_array($string, $strings)) $okay = false;
				}
				if ($okay) $retArr[] = $row[0];
			}
			if (empty($retArr)) return false;
			else return $retArr;
		}
		else return false;
	}
	
	public function stringToArr() {
		$arr = explode(",", $this->str);
		return $arr;
	}
	
	public function getEnvironment() {
		$arr = $this->stringToArr();
		
		$water = array(
			"aquatic",
			"water"
			);
		
		$bush = array(
			"bush"
			);
		
		$wood = array(
			"palm_wood",
			"wood"
			);
		
		$rock = array(
			"stone_can_cabochon",
			"stone_can_facet",
			"stone_hard",
			"stone_lithic",
			"stone_polishable",
			"stone_soft",
			"stone_thermoresistant"
			);
		
		$ret_arr = array(
			"water" => false,
			"bush" => false,
			"wood" => false,
			"rock" => false,
			"clay" => false
			);
		foreach ($arr as $string) {
			if (in_array($string, $water)) $ret_arr["water"] = true;
			if (in_array($string, $bush)) $ret_arr["bush"] = true;
			if (in_array($string, $wood)) $ret_arr["wood"] = true;
			if (in_array($string, $rock)) $ret_arr["rock"] = true;
			if ($string == "clay") $ret_arr["clay"] = true;
		}
		return $ret_arr;
	}
	
	public function beautifyStrings($returnAsString=true) {
		$arr = $this->stringToArr();
		
		$pos_strs = array(
			"aquatic" => "can only grow in water",
			"bakable" => "is bakable",
			"bark" => "has useful bark",
			"berries" => "has berries",
			"boilable" => "can be boiled",
			"brick" => "is a type of brick",
			"carvable" => "is carvable",
			"choppable" => "can be chopped",
			"clay" => "is clay",
			"coarse" => "is coarse",
			"combustible" => "is combustible",
			"corm" => "has corms",
			"crushable" => "is crushable",
			"cuttable" => "is cuttable",
			"deflect_rain" => "deflects rain",
			"dicable" => "can be diced",
			"drillable" => "is drillable",
			"dryable_brittle" => "gets brittle when dried",
			"dryable_crunchy" => "gets crunchy when dried",
			"dryable_soft" => "remains soft when dried",
			"dryable_tough" => "gets tough when dried",
			"durable" => "is durable",
			"edible" => "is edible",
			"fabric" => "is a type of fabric",
			"fermentable" => "is fermentable",
			"fibre" => "has fibers",
			"fine" => "is of fine quality",
			"flowers" => "has flowers",
			"fragrant" => "is fragrant",
			"fruit" => "has fruit",
			"generic" => "is generic",
			"grain_coarse" => "has coarse grain",
			"grain_fine" => "has fine grain",
			"gratable" => "is gratable",
			"grindable" => "can be ground",
			"hallucinogenic" => "is hallucinogenic",
			"hard_organic" => "is a hard animal product",
			"heat_resistant" => "is heat resistant",
			"hollow" => "is hollow",
			"impact_resistant" => "is impact resistant",
			"inner" => "has useful inner bark",
			"insulates" => "insulates",
			"leaves" => "has leaves",
			"liquid" => "is a liquid",
			"malleable" => "is malleable",
			"mashable" => "is mashable",
			"metal_extra_hard" => "is comparable to steel",
			"metal_hard" => "is comparable to iron",
			"metal_medium" => "is comparable to bronze",
			"metal_soft" => "is a soft metal",
			"mortar" => "can be used as mortar",
			"narrow" => "is narrow",
			"neutral" => "is neutral",
			"nonflammable" => "is not flammable",
			"nuts" => "has nuts",
			"oily" => "is oily",
			"padding" => "can be used as padding",
			"palm_wood" => "is a type of palm wood",
			"peelable_hammer" => "shell can be broken by crushing",
			"peelable_knife" => "can be peeled with a knife",
			"peelable_manual" => "can be peeled manually",
			"pits" => "has pits (stones)",
			"planks" => "is planks",
			"plant" => "is a type of plant",
			"pliable" => "is pliable",
			"poisonous" => "is poisonous",
			"powder" => "is a powder",
			"processed" => "is a processed form",
			"residue_dry" => "leaves dry residue",
			"residue_sticky" => "leaves sticky residue",
			"residue_wet" => "leaves wet residue",
			"root" => "has useful roots",
			"rust-proof" => "is rust-proof",
			"sap" => "has sap",
			"seeds" => "contains seeds",
			"slicable" => "can be sliced",
			"smokable" => "can be cured with smoke",
			"soakable" => "can be soaked",
			"soft" => "is soft",
			"softens_when_boiled" => "softens when boiled",
			"sprigs" => "has sprigs",
			"steamable" => "can be steamed",
			"sticky" => "is sticky",
			"stone_can_cabochon" => "can become a cabochon",
			"stone_can_facet" => "can be faceted",
			"stone_hard" => "is a hard stone",
			"stone_lithic" => "can be processed with lithic reduction",
			"stone_polishable" => "is a stone that can be polished",
			"stone_soft" => "is a soft stone",
			"stone_thermoresistant" => "is heat tolerant",
			"string" => "is a type of string",
			"tanning" => "can be used as a tanning agent",
			"tastes_bitter" => "tastes bitter",
			"tastes_bland" => "tastes bland",
			"tastes_good" => "tastes good",
			"tastes_salty" => "tastes salty",
			"tastes_sour" => "tastes sour",
			"tastes_spicy" => "tastes spicy",
			"tastes_sweet" => "tastes sweet",
			"tool_bone" => "can be processed with bone tools",
			"tool_bronze" => "can be processed with bronze tools",
			"tool_copper" => "can be processed with soft metal tools",
			"tool_iron" => "can be processed with iron tools",
			"tool_steel" => "can be processed with steel tools",
			"tool_stone" => "can be processed with stone tools",
			"treated" => "is cured",
			"twig" => "has thin branches",
			"twinable" => "can be twined",
			"unprocessed" => "is unprocessed",
			"water" => "is water",
			"waterproof" => "is waterproof",
			"weavable" => "can be woven",
			"whetstone" => "can be used as whetstone",
			"wide" => "is wide",
			"wood" => "is a type of wood",
			"wood_barrel" => "is a wood suitable for making barrels",
			"wood_bow" => "is suitable for making bows",
			"wood_grain_straight" => "has straight grain",
			"wood_resinous" => "is resinous",
			"yarn" => "is a type of yarn"
			);
		
		if ($returnAsString) {
			$ret_str = "";
			for ($i=0; $i<sizeof($arr); $i++) {
				if ($ret_str != "") {
					if ($i == sizeof($arr)-1) $ret_str .= " and ";
					else $ret_str .= ", ";
				}
				if (array_key_exists($arr[$i], $pos_strs)) $ret_str .= $pos_strs[$arr[$i]];
				else $ret_str .= $arr[$i];
			}
			return $ret_str;
		}
		else {
			$ret_arr = array();
			foreach ($arr as $string) {
				if (array_key_exists($string, $pos_strs)) $ret_arr[] = $pos_strs[$string];
				else $ret_arr[] = $string;
			}
			return $ret_arr;
		}
	}
}
