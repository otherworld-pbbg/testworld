<?php
include_once("class_map_pixel.inc.php");
include_once("local_map.inc.php");
include_once("class_obj.inc.php");
include_once("class_character.inc.php");
include_once("generic.inc.php");
include_once("class_project.inc.php");

class FieldArea
{
	private $mysqli;
	public $area_id = false;
	public $gx;
	public $gy;
	public $coords_list;//array of included coordinates

	public function __construct($mysqli, $x, $y, $hex=false, $area_id = false) {
		
		$this->mysqli = $mysqli;
		$this->gx = $x;
		$this->gy = $y;
		$this->area_id = $area_id;
		
		if (!$hex&&$area_id) $hex = $this->getHexDB();
		
		$checked = checkHex($hex);
		if (!$checked) $checked = "#ff0000";
		$this->hex = $checked;
		
		if ($this->area_id) $this->getIncludedSquares();
	}
	
	public function getUid() {
		if ($this->area_id) return $this->area_id;
		
		$sql = "INSERT INTO `field_areas` (`gx`, `gy`, `hex`) VALUES ('$this->gx', '$this->gy', '$this->hex')";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->area_id = $result;
			return $result;
		}
		else return false;
	}
	
	public function getHexDB() {
		$sql = "SELECT `hex` FROM `field_areas` WHERE `uid`=$this->area_id LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->hex = $row[0];
			return $row[0];//hex found
		}
		else return false;
	}
	
	public function checkCoords() {
		$sql = "SELECT `gx`, `gy` FROM `field_areas` WHERE `uid`=$this->area_id LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->gx = $row[0];
			$this->gy = $row[1];
			return true;//coords found
		}
		else return false;
	}
	
	public function updateHex($hex) {
		$checked = checkHex($hex);
		if (!$checked) $checked = "#ff0000";
		
		$sql = "UPDATE `field_areas` SET `hex`='$checked' WHERE `uid`=$this->area_id LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			$this->hex = $checked;
			return true;
		}
		return false;
	}
	
	public function getIncludedSquares() {
		$retArr = array();
		$undefined = false;
		$sql = "SELECT `uid`, `lx`, `ly`, `soil` FROM `field_contents` WHERE `fieldFK`=$this->area_id";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
				if ($row["soil"]==0) $undefined = true;
			}
			$this->coords_list = $retArr;
			if ($undefined) $this->updateSoil();
			return $retArr;
		}
		else return false;
	}
	
	function addSquares($coords2add) {
		if (!$this->area_id) $new = $this->getUid();
		else $new = $this->area_id;
		if (!$new) return array(
			"num" => -1
			);
		
		$numAdded = 0;
		$addStr = "";
		//local_coords is an array of x,y pairs
		$squareStatus = $this->partOfOtherField($coords2add);//Check if some of these are already bound to other fields
		
		if (sizeof($squareStatus["notPart"])==0) {
			return array(
			"num" => $numAdded,
			"handled" => array(),
			"already" => array(),
			"other" => $squareStatus["other"]
			);
		}//all squares were already part of other fields
		
		$status2 = $this->partOfThis($squareStatus["notPart"]);//Out of the ones that are not, check if any area already parts of this
		
		if (sizeof($status2["notPart"])>0) {
			foreach ($status2["notPart"] as $square) {
				$px = floor($square["lx"]/10);
				$py = floor($square["ly"]/10);
				$soil = $this->getSoil($px, $py);
				if ($addStr != "") $addStr .= ", ";
				$addStr .= "('$this->area_id', '". $square["lx"] . "', '". $square["ly"] . "', '". $soil . "')";
				$numAdded++;
			}
			$sql = "INSERT INTO `field_contents` (`fieldFK`, `lx`, `ly`, `soil`) VALUES $addStr";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) $numAdded =  -1;
			else $this->getIncludedSquares();//update list contents to get the uids
		}
		
		return array(
			"num" => $numAdded,
			"handled" => $status2["notPart"],
			"already" => $status2["isPart"],
			"other" => $squareStatus["other"]
			);//Some of the results can be empty arrays
	}
	
	function moveFromOtherField($coords2move) {
		//This can take squares from multiple fields
		$addStr = "";
		$count = 0;
		$status = $this->partOfOtherField($coords2move);
		if (sizeof($status["other"])>0) {
			foreach ($status["other"] as $square) {
				if ($addStr != "") $addStr .= " OR ";
				$addStr .= "`uid`='". $square["uid"] . "'";
				$count++;
			}
			$sql = "UPDATE `field_contents` SET `fieldFK`=$this->area_id WHERE $addStr LIMIT $count";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				$count = 0;
				$success = false;
			}
			else $success = true;
			$this->getIncludedSquares();//update list
		}
		else $success = false;
		
		return array(
			"num" => $count,
			"excluded" => $status["notPart"],
			"moved" => $status["other"],
			"success" => $success
			);//Some of the results can be empty arrays
	}
	
	function deleteSquares($coords2remove) {
		//This isn't recommended because it destroys the contents
		$addStr = "";
		$count = 0;
		$status = $this->partOfThis($coords2remove);
		if (sizeof($status["isPart"])>0) {
			foreach ($status["isPart"] as $square) {
				if ($addStr != "") $addStr .= " OR ";
				$addStr .= "`uid`='". $square["uid"] . "'";
				$count++;
			}
			$r=queryDelete($this->mysqli, "field_contents", $addStr, "`uid`", $count);
			if ($r==0) {
				$count = 0;
				$success = false;
			}
			else $success = true;
			$this->getIncludedSquares();//update what is left
		}
		else $success = false;
		
		return array(
			"num" => $count,
			"excluded" => $status["notPart"],
			"deleted" => $status["isPart"],
			"success" => $success
			);//Some of the results can be empty arrays
	}
	
	function partOfThis($coords2check) {
		if (!$this->coords_list) {
			$success = $this->getIncludedSquares();
			if (!$success) return array(
				"notPart" => $coords2check,
				"isPart" => array()
			);//coords_list is false, so this field has no squares yet, thus none of the checked squares are part of it
		}
		$partCoords = array();
		$notCoords = array();
		
		foreach ($coords2check as $coords) {
			$matchFound = false;
			foreach ($this->coords_list as $included) {
				if ($included["lx"]==$coords["lx"]&&$included["ly"]==$coords["ly"]) {
					$matchFound = true;
					$partCoords[] = array(
						"uid" => $included["uid"],
						"lx" => $included["lx"],
						"ly" => $included["ly"]
						);
					break;
				}
				
			}
			if (!$matchFound) $notCoords[] = $coords;
		}
		
		return array(
			"isPart" => $partCoords,
			"notPart" => $notCoords
		);
		
	}
	
	function partOfOtherField($coords2check) {
		//must remember that coords2check doesn't necessarily include uids
		$addStr = "";
		$notCoords = array();
		$otherCoords = array();
		
		foreach ($coords2check as $square) {
			if ($addStr != "") $addStr .= " OR ";
			$addStr .= "(`lx`='". $square["lx"] . "' AND `ly`='". $square["ly"] . "')";
		}
		$sql = "SELECT `uid`, `fieldFK`, `lx`, `ly` FROM `field_contents` WHERE `fieldFK`<>$this->area_id AND ($addStr)";
		
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$otherCoords[] = $row;
			}
		}
		
		foreach ($coords2check as $coords) {
			$found = false;
			foreach ($otherCoords as $other) {
				if ($other["lx"]==$coords["lx"]&&$other["ly"]==$coords["ly"]) $found = true;
			}
			if (!$found) $notCoords[] = $coords;
		}
		
		return array(
			"notPart" => $notCoords,
			"other" => $otherCoords
		);//notPart can either be not part of anything or part of this
	}
	
	function updateSoil() {
		foreach($this->coords_list as $square) {
			$px = floor($square["lx"]/10);
			$py = floor($square["ly"]/10);
			
			$type = $this->getSoil($px, $py);
			
			$sql = "UPDATE `field_contents` SET `soil`=$type WHERE `uid`=" . $square["uid"] . " LIMIT 1";
			$this->mysqli->query($sql);
		}
	}
	
	function getSoil($px, $py) {
		$local = new LocalMap($this->mysqli, $this->gx, $this->gy);//Note, this assumes that the area has been unlocked. It will unlock it for free if it isn't, so it shouldn't be possible to access this if the location is locked
		$local->loadcreate();
		$soil = $local->getSoil2($px, $py);
		return $soil;
	}
	
	function validateFarmland($type) {
		//type 1: regular farmland, type 2 = rice paddy
		//This checks that the land is not too wet or dry, doesn't have too many rocks, bushes or trees
		$local = new LocalMap($this->mysqli, $this->gx, $this->gy);//Note, this assumes that the area has been unlocked
		$local->loadcreate();
		$validList = array();
		$notList = array();
		
		foreach($this->coords_list as $square) {
			$valid = true;
			$px = floor($square["lx"]/10);
			$py = floor($square["ly"]/10);
			
			$vege = $local->getVegetation($px, $py);
			$row = $local->getROW($px, $py);
			$waterLevel = max(0,ceil(min($row["b"]-10,219)/36));
			
			if ($type == 1) {
				if ($vege["r"]>60||$vege["g"]>128||$vege["b"]>75||$row["r"]>115||$waterLevel>3||$waterLevel==0) $valid = false;
			}
			if ($type == 2) {
				if ($vege["r"]>60||$vege["g"]>128||$vege["b"]>75||$row["r"]>115||$waterLevel<5) $valid = false;
			}
			
			if ($valid) $validList[] = $square;
			else $notList[] = $square;
		}
		
		return array(
			"valid" => $validList, 
			"not" => $notList
			);
	}
	
	function processFields($charid, $type, $task, $tool, $seed=0, $maxHarvest=0) {
		//task 0 - plough, 1 = plant, 2 = weed, 3 = harvest partial, 4 = harvest all
		$processed = array();
		$actor = new Character($this->mysqli, $charid);
		$squareStatus = $this->validateFarmland($type);
		
		$ap_per_square = array(
			30,
			10,
			15,
			20,
			20
			);
		//harvest numbers are for scythe. If you harvest partial then it costs less
		//to-do: validate tool and count efficiency
		if (sizeof($squareStatus["valid"])>0) {
			foreach ($squareStatus["valid"] as $square) {
				$status = $this->getStatus($square["uid"]);
				if ($status["status"]==0||$status["status"]==5&&$task==0) {
					//ploughing
					$tool_o = new Obj ($this->mysqli, $tool);
					$eff = $tool_o->validatePool(43);
					if ($eff) {
						$check = $actor->spendAP(round(100/$eff["ap_modifier"]*$ap_per_square[$task]));
						if ($check==1) $check2 = $this->setStatus($square["uid"], 1);
						else break;//Character is out of AP
						if ($check2) $processed[] = $square;
					}
				}
				else if ($status["status"]==1&&$task==1) {
					//plant
					$seeds = new Obj($this->mysqli, $seed);
					if ($status["cropFK"]==0) {
						if ($seeds->weight>=20) {
							$check = $actor->spendAP($ap_per_square[$task]);
							if ($check==1) {
								$seeds->changeSize(-20, 0, $charid);//one field needs 20 grams of seed
								$check2 = $this->setStatus($square["uid"], 2, $seeds->secondary);
							}
							else break;//Character is out of AP
							if ($check2) $processed[] = $square;
							
						}
						else break;
					}
				}
				else if (($status["status"]==2||$status["status"]==3)&&$task==2) {
					//weed
					$check = $actor->spendAP($ap_per_square[$task]*$status["weeds"]);
					if ($check==1) $check2 = $this->weed($square["uid"]);
					else break;//Character is out of AP
					if ($check2) $processed[] = $square;
				}
				else if (($status["status"]==3||$status["status"]==4)&&$task==3&&$maxHarvest>0) {
					//harvest partial
					$restype = new Resource($this->mysqli, $status["cropFK"]);
					$cat = $restype->getCategory();
					if ($cat == 8) $pool = 39;
					else if ($cat == 21) $pool = 44;
					else $pool = 0;
					
					if ($pool==0) $eff["ap_modifier"] = 100;
					else {
						$tool_o = new Obj($this->mysqli, $tool);
						$eff = $tool_o->validatePool($pool);
					}
					
					if ($eff) {
						if ($maxHarvest>=$status["ripe"]) {
							$check = $actor->spendAP(round(100/$eff["ap_modifier"]*$ap_per_square[$task]));
							if ($check==1) $check2 = $this->harvest($square["uid"], $status["ripe"], true);
							else break;//Character is out of AP
							if ($check2) {
								$processed[] = $square;
								$maxHarvest-=$status["ripe"];
								$crops = new Obj($this->mysqli);
								$curTime = new Time($this->mysqli);
								$crops->create(20, 5, 0, "Result of harvest", $this->gx, $this->gy, $square["lx"], $square["ly"], $status["cropFK"], 1, $status["ripe"], $curTime->dateTime, $curTime->minute);
							}
						}
						else {
							$amount = $maxHarvest;
							$multiplier = $amount/$status["ripe"];
							$check = $actor->spendAP(round(100/$eff["ap_modifier"]*$ap_per_square[$task]*$multiplier));
							if ($check==1) $check2 = $this->harvest($square["uid"], round($status["ripe"]*$multiplier), false);
							else break;//Character is out of AP
							if ($check2) {
								$processed[] = $square;
								$maxHarvest = 0;
								$crops = new Obj($this->mysqli);
							$curTime = new Time($this->mysqli);
							$crops->create(20, 5, 0, "Result of harvest", $this->gx, $this->gy, $square["lx"], $square["ly"], $status["cropFK"], 1, round($status["ripe"]*$multiplier), $curTime->dateTime, $curTime->minute);
							}
						}
					}
				}
				else if (($status["status"]==4)&&$task==4) {
					//harvest all
					$restype = new Resource($this->mysqli, $status["cropFK"]);
					$cat = $restype->getCategory();
					if ($cat == 8) $pool = 39;
					else if ($cat == 21) $pool = 44;
					else $pool = 0;
					
					if ($pool==0) $eff["ap_modifier"] = 100;
					else {
						$tool_o = new Obj($this->mysqli, $tool);
						$eff = $tool_o->validatePool($pool);
					}
					
					if ($eff) {
						$check = $actor->spendAP(round(100/$eff["ap_modifier"]*$ap_per_square[$task]));
						if ($check==1) $check2 = $this->harvest($square["uid"], $status["ripe"], true);
						else break;//Character is out of AP
						if ($check2) {
							$crops = new Obj($this->mysqli);
							$curTime = new Time($this->mysqli);
							$crops->create(20, 5, 0, "Result of harvest", $this->gx, $this->gy, $square["lx"], $square["ly"], $status["cropFK"], 1, $status["ripe"], $curTime->dateTime, $curTime->minute);
							$processed[] = $square;
						}
					}
				}
			}
		}
		
		return $processed;
	}
	
	function getStatus($square_id) {
		$sql = "SELECT `status`, `cropFK`, `ripe`, `harvested`, `spoiled`, `weeds`, `datetime` FROM `field_contents` WHERE `uid`=$square_id LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_assoc($result);
			return $row;
		}
		else return -1;
	}
	
	function getStatusAll() {
		$retArr = array();
		$sql = "SELECT `uid`, `lx`, `ly`, `status`, `cropFK`, `ripe`, `harvested`, `spoiled`, `weeds`, `datetime` FROM `field_contents` WHERE `fieldFK`=$this->area_id ORDER BY `status` DESC, `uid`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return -1;
	}
	
	function printStatus() {
		$status = $this->getStatusAll();
		if ($status==-1) para("There are no squares in this field.");
		else {
			$explanation = array(
				"unprocessed",
				"ploughed",
				"planted",
				"growing",
				"ready",
				"harvested",
				"wild"
				);
			
			
			ptag("h2", "Square status");
			foreach ($status as $s) {
				if ($s["cropFK"]>0) {
					$c = new Resource($this->mysqli, $s["cropFK"]);
					$crop = $c->name;
				}
				else $crop = "nothing";
				
				ptag("h4", "Coords: (" . $s["lx"] . "," . $s["ly"] . ")");
				echo "<ul class='normal'>";
				ptag("li", "Status: " . $explanation[$s["status"]] );
				ptag("li", "Crop: " . $crop);
				ptag("li", "Ripe: " . $s["ripe"] . " grams");
				ptag("li", "Harvested: " . $s["harvested"] . " grams");
				ptag("li", "Wasted: " . $s["spoiled"] . " grams");
				ptag("li", "Weed level: " . $s["weeds"] );
				echo "</ul>";
			}
		}
	}
	
	function setStatus($square_id, $newStatus, $crop=-1) {
		$curTime = new Time($this->mysqli);
		if ($crop<0) $sql = "UPDATE `field_contents` SET `status`=$newStatus, `datetime`=$curTime->dateTime WHERE `uid`=$square_id LIMIT 1";
		else $sql = "UPDATE `field_contents` SET `status`=$newStatus, `cropFK`=$crop, `datetime`=$curTime->dateTime WHERE `uid`=$square_id LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function weed($square_id) {
		$sql = "UPDATE `field_contents` SET `weeds`=0 WHERE `uid`=$square_id AND `weeds`>0 LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function harvest($square_id, $amount, $finish=false) {
		$curTime = new Time($this->mysqli);
		if ($finish) $sql = "UPDATE `field_contents` SET `ripe`=`ripe`-$amount, `harvested`=`harvested`+$amount, `status`=5 `datetime`=$curTime->dateTime WHERE `uid`=$square_id AND `ripe`>=$amount LIMIT 1";
		else $sql = "UPDATE `field_contents` SET `ripe`=`ripe`-$amount, `harvested`=`harvested`+$amount, `status`=5 `datetime`=$curTime->dateTime WHERE `uid`=$square_id AND `ripe`>=$amount LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function getAvailableTools($owner, $pool, $userid) {
		$p = new Project ($this->mysqli, 0, $owner, $userid);
		
		return $p->getPoolToolsAvailable($pool);
	}
	
	function getSeeds($owner) {
		$char = new Character($this->mysqli, $owner);
		return $char->getInventorySpecific(5, 24);
	}
}
?>
