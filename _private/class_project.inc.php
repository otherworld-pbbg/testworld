<?
include_once("class_project_type.inc.php");
include_once("class_character.inc.php");
include_once("class_obj.inc.php");
include_once("local_map.inc.php");
include_once("class_preset.inc.php");
include_once("class_resource.inc.php");
include_once("generic.inc.php");
include_once("class_fuel_project.inc.php");
include_once("class_time.inc.php");

class Project {
	private $mysqli;
	public $uid=0;
	public $charid=0;
	public $userid=0;
	public $type=0;
	public $ap=0;
	public $invested=0;
	public $datetime=0;
	public $minute=0;
	public $multiples=0;
	public $starter=0;
	public $finished=0;
	public $quality=0;
	public $end_dt=0;
	public $end_m=0;
	
	public function __construct($mysqli, $uid, $observer, $userid) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		$this->charid = $observer;
		$this->userid = $userid;
		if ($uid>0) $this->getProject();
	}
	
	function searchByField($categories, $field, $value)
	{
		$retArr = array();
		foreach($categories as $key => $category)
		{
			if ($category[$field] == $value) {
				$retArr[] = $categories[$key];
			}
		}
		return $retArr;
	}
	
	function getProject() {
		//to do: What if the character is in a building?
		$viewer = new Character($this->mysqli, $this->charid);
		//this doesn't currently support buildings
		$sql = "SELECT `project_type`, `ap_total`, `ap_invested`, `datetime`, `minute`, `multiples`, `starter`, `finishedFK`, `quality`, `end_dt`, `end_m` FROM `projects` WHERE " . $viewer->getCoordsForSQL4() . " AND `uid`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$retArr = array();
			$row = mysqli_fetch_row($result);
			
			$this->type = $row[0];
			$this->ap = $row[1];
			$this->invested = $row[2];
			$this->datetime = $row[3];
			$this->minute = $row[4];
			$this->multiples = $row[5];
			$this->starter = $row[6];
			$this->finished = $row[7];
			$this->quality = $row[8];
			$this->end_dt = $row[9];
			$this->end_m = $row[10];
			
			return true;
		}
		else return false;
	}
	
	function getAvailableResources() {
		//to do: building?
		$observer = new Character($this->mysqli, $this->charid);
		$observer->getBasicData();
		$curTime = new Time($this->mysqli);
		$sql = "SELECT `uid`, `presetFK`, `secondaryFK`, `parent` FROM `objects` WHERE (`parent`=$observer->bodyId OR (" . $observer->getCoordsForSQL3() . " AND `parent`=0)) AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
		
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$retArr = array();
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return -1;
	}
	
	function printInfo() {
		$project = $this->getProject();
		
		$project_type = new ProjectType($this->mysqli, $this->type);
		
		$res_strings = $project_type->getResStrings();
		$components = $project_type->getComponents();
		
		$res_a = $this->getAddedResources();
		$preset_a = $this->getAddedComponents();
		
		$available = $this->getAvailableResources();
		
		if ($project_type->pre_uid==20) {
			$result = new Resource($this->mysqli, $project_type->secondary);
			$result->loadData();
			$project_type->pre_name = $result->name;
		}
		
		ptag("h1", "Manufacturing " . $this->multiples . " x " . $project_type->pre_name);
		ptag("h2", "Action points:");
		para($this->invested . " of " . $this->ap . " invested");
		$prevSlot = 0;
		ptag("h2", "Resources:");
		if ($res_strings == -1) para("None needed");
		else {
			echo "<ul class='res'>";
			foreach($res_strings as $ingredient) {
				$found_one = false;
				$pr = new Preset($this->mysqli, $ingredient["preset"]);
				$rst = new ResourceString($this->mysqli, $ingredient["str"]);
				$matches = $rst->getMatchingResTypes();
				$description = $rst->beautifyStrings();
				
				if ($prevSlot==$ingredient["slot"]&&$prevSlot>0) echo "<li>OR</li>";
				echo "<li>$pr->name: $description</li>";
				if ($matches) {
					echo "<ul class='res'>";
					if ($return = searchSingle($res_a, "slot", $ingredient["slot"])) {
						if ($ingredient["preset"]==$return["preset"]) {
							$res_ = new Resource($this->mysqli, $return["res_type"], $return["preset"]);
							$res_->loadData();
							echo "<li> " . $res_->name . " (" . $return["weight_a"] . " of " . $return["weight_n"] . " grams";
							if ($this->invested==0) {
									echo " ";
									ptag("a", "[remove]", "href='index.php?page=removeResource&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $ingredient["slot"] . "&res=" . $return["res_type"] . "&preset=". $return["preset"] . "' class='clist'");
									$found_one = true;
							}
							if ($return["weight_a"]<$return["weight_n"]) {
								$availability = $this->getAddableResources($ingredient["slot"], $return["res_type"], $ingredient["preset"]);
								echo " ";
								if (is_array($availability)) {
									ptag("a", "[add more]", "href='index.php?page=addResource&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $ingredient["slot"] . "&res=" . $return["res_type"] . "&preset=". $return["preset"] . "' class='clist'");
									$found_one = true;
								}
								else "(needs more)";
							}
							echo "</li>";
						}
						else {
							ptag("li", "Not chosen");
							$found_one = true;
						}
					}
					else if (is_array($available)) foreach ($available as $av) {
						if (in_array($av["secondaryFK"], $matches) && $ingredient["preset"]==$av["presetFK"]) {
							$res_obj = new Obj($this->mysqli, $av["uid"]);
							$res_obj->getBasicData();
							echo "<li>" . $res_obj->getName();
							if ($av["parent"]==0) echo " (on the ground)";
							else echo " (held)";
							echo " ";
							ptag("a", "[add]", "href='index.php?page=addResource&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $ingredient["slot"] . "&res=" . $res_obj->secondary . "&preset=". $av["presetFK"] . "' class='clist'");
							echo "</li>";
							$found_one = true;
						}
					}
					else $found_one = false;
					if (!$found_one) ptag("li", "No resources nearby which meet the criteria.");
					echo "</ul>";
				}
				else ptag("li", "There are no resources that currently fill the criteria. Please inform developer.");
				
				$prevSlot = $ingredient["slot"];
			}
			echo "</ul>";
		}
		
		$prevSlot = 0;
		ptag("h2", "Components:");
		if ($components == -1) para("None needed");
		else {
			foreach($components as $part) {
				$amount = $part["pieces"]*$this->multiples;
				
				$foundMatch = array();
				if ($preset_a != -1) {
					$foundMatch = $this->searchByField($preset_a, "slot", $part["slot"]);
				}
				
				if (!$foundMatch) {
					echo "<p>";
					if ($prevSlot==$part["slot"]&&$prevSlot>0) echo "OR ";
					echo $amount. " x " . $part["name"];
					echo " ";
					ptag("a", "[add]", "href='index.php?page=addPart&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $part["slot"] . "&preset=" . $part["uid"] . "' class='clist'");
					echo "</p>";
				}
				else {
					foreach ($foundMatch as $match) {
						if ($match["preset"]==$part["uid"]) {
							echo "<p>";
							if ($prevSlot==$part["slot"]&&$prevSlot>0) echo "OR ";
							echo $amount. " x " . $part["name"] . " needed";
							echo " CHOSEN - " . $match["pieces_a"] . " pieces added";
							if ($this->invested==0) {
								echo " ";
								ptag("a", "[remove]", "href='index.php?page=removePart&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $part["slot"] . "&preset=" . $part["uid"] . "' class='clist'");
							}
							if ($match["pieces_a"]<$amount) {
								echo " ";
								ptag("a", "[add more]", "href='index.php?page=addPart&pid=$this->uid&charid=$this->charid&userid=$this->userid&slot=" . $part["slot"] . "&preset=" . $part["uid"] . "' class='clist'");
							}
							echo "</p>";
						}
						else {
							echo "<p>";
							echo "<span class='unavailable'>";
							if ($prevSlot==$part["slot"]&&$prevSlot>0) echo "OR ";
							echo $amount. " x " . $part["name"];
							echo "</span>";
							echo "</p>";
						}
					}
				}
				$prevSlot = $part["slot"];
			}
		}
		ptag("h2", "Tool pools:");
		$project_type->printToolPools();
		ptag("h2", "Unique tools:");
		$project_type->printTools();
			
		
	}
	
	function getAddedResources($slot=0) {
		if ($slot>0) $sql = "SELECT `uid`, `res_typeFK`, `weight_added`, `weight_needed`, `slot`, `preset` FROM `added_resources` WHERE `projectFK`=$this->uid AND `slot`=$slot LIMIT 1";
		else $sql = "SELECT `uid`, `res_typeFK`, `weight_added`, `weight_needed`, `slot`, `preset` FROM `added_resources` WHERE `projectFK`=$this->uid ORDER BY `slot`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$retArr = array();
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"res_type" => $row[1],
					"weight_a" => $row[2],
					"weight_n" => $row[3],
					"slot" => $row[4],
					"preset" => $row[5]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	function getAddedComponents($slot=0) {
		if ($slot>0) $sql = "SELECT `uid`, `num_added`, `slot`, `presetFK`, `secondary`, `weight` FROM `added_components` WHERE `projectFK`=$this->uid  AND `slot`=$slot";
		else $sql = "SELECT `uid`, `num_added`, `slot`, `presetFK`, `secondary`, `weight` FROM `added_components` WHERE `projectFK`=$this->uid ORDER BY `slot`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$retArr = array();
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"pieces_a" => $row[1],
					"slot" => $row[2],
					"preset" => $row[3],
					"secondary" => $row[4],
					"weight" => $row[5]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	function getResNeeded($slot, $res, $preset) {
		//this project should only be called if it has been established there are no entries in the added resources table
		
		if ($this->invested>0) return -1;//This project has already been worked on so it can't be missing resources
		$pt = new ProjectType($this->mysqli, $this->type);
		$strings = $pt->getResStrings();
		$for_this_slot = $this->searchByField($strings, "slot", $slot);
		$found = false;
		foreach ($for_this_slot as $pos) {
			$rst = new ResourceString($this->mysqli, $pos["str"]);
			$arr = $rst->getMatchingResTypes();
			if (!$arr) $found = false;
			else if ($pos["preset"]==$preset&&(array_search($res, $arr)===0||array_search($res, $arr)>0)) {
				$found = true;
				$match = $pos;
				break;
			}
		}
		
		if ($found) {
			return array(
				"weight" => $match["weight"]*$this->multiples,
				"uid" => NULL,
				"preset" => $match["preset"]
				);
		}
		return -2;//This project doesn't need a resource of this type or in this slot
	}
	
	function getComponentNeeded($slot, $preset) {
		if ($this->invested>0) return -1;//This project has already been worked on so it can't be missing resources
		$sql = "SELECT `pieces` FROM `needed_components` WHERE `presetFK`=$preset AND `slot`=$slot AND `project_type`=".$this->type ." LIMIT 1";
		$res = $this->mysqli->query($sql);
		if ($res) {
			$row = mysqli_fetch_row($res);
			return $row[0]*$this->multiples;
		}
		else return -2;//This project doesn't need a component of this type or in this slot
	}
	
	function getAddableResources($slot, $res, $preset) {
		$actor = new Character($this->mysqli, $this->charid);
		$actor->getBasicData();
		$curTime = new Time($this->mysqli);
		$arr = $this->getAddedResources($slot);
		if ($arr==-1) {
			$need = $this->getResNeeded($slot, $res, $preset);
			if ($need==-1) return -1;//project already has progress
			if ($need==-2) return -2;//project doesn't need this particular resource at least in this slot
		}
		else {
			if ($arr[0]["res_type"]!=$res) return -3;//Another type has already been picked for this slot
			else if ($arr[0]["weight_a"]>=$arr[0]["weight_n"]) return -4;//This slot is full
			else $need = array(
				"weight" => $arr[0]["weight_n"]-$arr[0]["weight_a"],
				"uid" => $arr[0]["uid"],
				"preset" => $arr[0]["preset"]
				);
		}
		
		$retArr = array();
		$sql1 = "SELECT `uid`, `weight` FROM `objects` WHERE `secondaryFK`=$res AND `presetFK`=" . $preset . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) AND `global_x` IS NULL AND `global_y` IS NULL AND `local_x`=0  AND `local_y`=0 AND `parent`=$actor->bodyId";
		$sql2 = "SELECT `uid`, `weight` FROM `objects` WHERE `secondaryFK`=$res AND `presetFK`=" . $preset ." AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) AND " . $actor->getCoordsForSQL3();
		//to do: building?
		$res1 = $this->mysqli->query($sql1);
		if ($res1) {
			while ($row = mysqli_fetch_row($res1)) {
			$retArr[] = array(
				"uid" => $row[0],
				"weight" => $row[1],
				"source" => "inventory"
				);
			}
		}
		$res2 = $this->mysqli->query($sql2);
		if ($res2) {
			while ($row = mysqli_fetch_row($res2)) {
			$retArr[] = array(
				"uid" => $row[0],
				"weight" => $row[1],
				"source" => "ground"
				);
			}
		}
		
		return array(
				"need_w" => $need["weight"],
				"row" => $need["uid"],
				"sources" => $retArr,
				"preset" => $need["preset"]
				);
	}
	
	function getAddableComponents($slot, $preset) {
		$actor = new Character($this->mysqli, $this->charid);
		$actor->getBasicData();
		$curTime = new Time($this->mysqli);
		$arr = $this->getAddedComponents($slot);
		
		$need = $this->getComponentNeeded($slot, $preset);
		if ($need==-1) return -1;//project already has progress
		if ($need==-2) return -2;//project doesn't need this particular resource at least in this slot
		
		if (is_array($arr)) {
			if ($arr[0]["preset"]!=$preset) return -3;//Another type has already been picked for this slot
			$added = 0;
			$addedRows = array();
			foreach ($arr as $row) {
				$added += $row["pieces_a"];
				$addedRows[] = array(
					"row" => $row["uid"],
					"added" => $row["pieces_a"],
					"preset" => $row["preset"],
					"secondary" => $row["secondary"]
					);
			}
			
			if ($added>=$need) return -4;//This slot is full
		}
		else {
			$addedRows = false;
			$added = 0;
		}
		
		$retArr = array();
		$sql1 = "SELECT `uid`, `pieces`, `weight`, `secondaryFK` FROM `objects` WHERE `presetFK`=$preset AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) AND `global_x` IS NULL AND `global_y` IS NULL AND `local_x`=0  AND `local_y`=0 AND `parent`=$actor->bodyId";
		$sql2 = "SELECT `uid`, `pieces`, `weight`, `secondaryFK` FROM `objects` WHERE `presetFK`=$preset AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) AND " . $actor->getCoordsForSQL3();
		//to do: building
		$res1 = $this->mysqli->query($sql1);
		if ($res1) {
			while ($row = mysqli_fetch_row($res1)) {
			$retArr[] = array(
				"uid" => $row[0],
				"pieces" => $row[1],
				"secondary" => $row[2],
				"source" => "inventory"
				);
			}
		}
		$res2 = $this->mysqli->query($sql2);
		if ($res2) {
			while ($row = mysqli_fetch_row($res2)) {
			$retArr[] = array(
				"uid" => $row[0],
				"pieces" => $row[1],
				"secondary" => $row[2],
				"source" => "ground"
				);
			}
		}
		
		return array(
				"need_p" => $need-$added,
				"already" => $addedRows,
				"sources" => $retArr
				);
	}
	
	function addResFromSource($obj, $slot, $res) {
		$pile = new Obj($this->mysqli, $obj);
		
		$result = $this->getAddableResources($slot, $res, $pile->preset);
		if (is_numeric($result)&&$result<0) return $result;//error code
		
		$sources = $result["sources"];
		$arr = $this->searchByField($sources, "uid", $obj);
		if (empty($arr)) return -5;//Invalid pile
		
		
		if ($pile->weight>$result["need_w"]) {
			//the pile has more material than needed
			//reduce size of pile and complete added_resources
			if (is_null($result["row"])) $sql = "INSERT INTO `added_resources` (`uid`, `projectFK`, `res_typeFK`, `weight_added`, `weight_needed`, `slot`, `preset`) VALUES (NULL, '$this->uid', '$res', '".$result["need_w"]."', '".$result["need_w"]."', '$slot', " . $pile->preset . ")";
			else $sql = "UPDATE `added_resources` SET `weight_added`=`weight_added`+".$result["need_w"]." WHERE `uid`=" . $result["row"] . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			$result2 = $pile->changeSize(-$result["need_w"], 0, $this->charid);
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
		else if ($pile->weight<$result["need_w"]) {
			//the pile has less material than needed
			//delete pile and add weight to added_resources
			if (is_null($result["row"])) $sql = "INSERT INTO `added_resources` (`uid`, `projectFK`, `res_typeFK`, `weight_added`, `weight_needed`, `slot`, `preset`) VALUES (NULL, '$this->uid', '$res', '".$pile->weight."', '".$result["need_w"]."', '$slot', " . $pile->preset . ")";
			else $sql = "UPDATE `added_resources` SET `weight_added`=`weight_added`+$pile->weight WHERE `uid`=" . $result["row"] . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			$result2 = $pile->deleteFromDb();
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
		else {
			//the pile has exactly the material needed
			//delete pile and complete added_resources
			if (is_null($result["row"])) $sql = "INSERT INTO `added_resources` (`uid`, `projectFK`, `res_typeFK`, `weight_added`, `weight_needed`, `slot`, `preset`) VALUES (NULL, '$this->uid', '$res', '".$result["need_w"]."', '".$result["need_w"]."', '$slot', " . $pile->preset . ")";
			else $sql = "UPDATE `added_resources` SET `weight_added`=`weight_added`+".$result["need_w"]." WHERE `uid`=" . $result["row"] . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			$result2 = $pile->deleteFromDb();
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
	}
	
	function addComponentFromSource($obj, $slot, $preset) {
		$pile = new Obj($this->mysqli, $obj);
		$pile->getBasicData();
		$result = $this->getAddableComponents($slot, $preset);
		if (is_numeric($result)&&$result<0) return $result;//error code
		
		$sources = $result["sources"];
		$arr = $this->searchByField($sources, "uid", $obj);
		if (empty($arr)) return -5;//Invalid pile
		
		if ($pile->preset==0) return -5;
		
		$matchFound = false;
		if (is_array($result)) {
			$rows = $result["already"];
			if (!empty($rows)) {
				foreach ($rows as $row) {
					if ($row["secondary"]==$pile->secondary) {
						$matchFound = $row["row"];
						break;
					}
				}
			}
		}
		
		if ($pile->pieces>$result["need_p"]) {
			$unitWt = $pile->getAttribute(6, $this->charid);//small unit mass
			if (!$unitWt) $unitWt = $pile->weight/$pile->pieces;
			$weight = $result["need_p"]*$unitWt;
			//the pile has more pieces than needed
			//reduce size of pile and complete added_pieces
			if (!$matchFound) $sql = "INSERT INTO `added_components` (`uid`, `projectFK`, `presetFK`, `num_added`, `num_needed`, `slot`, `secondary`, `weight`) VALUES (NULL, '$this->uid', '$pile->preset', '".$result["need_p"]. "', '".$result["need_p"]. "', '$slot', '$pile->secondary', '$weight')";
			else $sql = "UPDATE `added_components` SET `num_added`=`num_added`+".$result["need_p"].", `weight`=`weight`+$weight WHERE `uid`=" . $matchFound . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			
			$result2 = $pile->changeSize(-1*($result["need_p"]*$unitWt), -$result["need_p"]);
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
		else if ($pile->pieces<$result["need_p"]) {
			//the pile has less pieces than needed
			//delete pile and add weight to added_components
			if (!$matchFound) $sql = "INSERT INTO `added_components` (`uid`, `projectFK`, `presetFK`, `num_added`, `num_needed`, `slot`, `secondary`, `weight`) VALUES (NULL, '$this->uid', '$pile->preset', '".$pile->pieces. "', '".$result["need_p"]. "', '$slot', '$pile->secondary', '$pile->weight')";
			else $sql = "UPDATE `added_components` SET `pieces_added`=`pieces_added`+$pile->pieces, `weight`=`weight`+$pile->weight WHERE `uid`=" . $matchFound . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			//to-do: In the future, if the component has a custom description, it will be preserved as immaterial and entered in the added_special table
			$result2 = $pile->deleteFromDb();
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
		else {
			//the pile has exactly the pieces needed
			//delete pile and complete added_components
			if (!$matchFound) $sql = "INSERT INTO `added_components` (`uid`, `projectFK`, `presetFK`, `num_added`, `num_needed`, `slot`, `secondary`, `weight`) VALUES (NULL, '$this->uid', '$pile->preset', '".$result["need_p"]. "', '".$result["need_p"]. "', '$slot', '$pile->secondary', '$pile->weight')";
			else $sql = "UPDATE `added_components` SET `num_added`=`num_added`+".$result["need_p"]. ", `weight`=`weight`+$pile->weight WHERE `uid`=" . $matchFound . " LIMIT 1";
			$res3 = $this->mysqli->query($sql);
			if (!$res3) return -6;
			$result2 = $pile->deleteFromDb();
			if (!$result2) return -7;//Duplication bug
			else return 100;
		}
	}
	
	function getReadiness() {
		$project_type = new ProjectType($this->mysqli, $this->type);
		$strings_needed = $project_type->getResStrings();
		
		$has_all_res = false;
		if ($strings_needed==-1) $has_all_res = true;
		else {
			foreach ($strings_needed as $possible) {
				$added_this_slot = $this->getAddedResources($possible["slot"]);
				if ($added_this_slot==-1) {
				$has_all_res = false;
				break;
				}
				else {
					if ($added_this_slot[0]["weight_a"]>=$added_this_slot[0]["weight_n"]) $has_all_res = true;
					else
					{
						$has_all_res = false;
						break;
					}
				}
			}
		}
		//check for components next
		$component_needed = $project_type->getComponents();
		$has_all_comp = false;
		if ($component_needed==-1) $has_all_comp = true;
		else {
			foreach ($component_needed as $possible) {
				$added_this_slot = $this->getAddedComponents($possible["slot"]);
				if ($added_this_slot==-1) {
				$has_all_comp = false;
				break;
				}
				else {
					$sum = 0;
					foreach ($added_this_slot as $adder) {
						$sum += $adder["pieces_a"];
					}
					
					$needtotal =  $this->getComponentNeeded($possible["slot"], $added_this_slot[0]["preset"]);
					
					if ($needtotal <= $sum) $has_all_comp = true;
					else
					{
						$has_all_comp = false;
						break;
					}
				}
			}
		}
		
		if ($has_all_res&&$has_all_comp) return true;
		else return false;
	}
	
	function getUsedToolSlots() {
		$retArr = array();
		$sql = "SELECT `slot` FROM `needed_tools` WHERE `project_type`=" . $this->type . " GROUP BY `slot` ORDER BY `slot` ";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = $row[0];
			}
		}
		return $retArr;
	}
	
	function getUsedComponentSlots() {
		$retArr = array();
		$sql = "SELECT `slot` FROM `needed_components` WHERE `project_type`=" . $this->type . " GROUP BY `slot` ORDER BY `slot` ";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = $row[0];
			}
		}
		return $retArr;
	}
	
	function getToolsAvailable($slot) {
		$char = new Character($this->mysqli, $this->charid);
		$pos = $char->getPosition();
		$curTime = new Time($this->mysqli);
		$localMap = new LocalMap($this->mysqli, $pos->x, $pos->y);
		$containers = $localMap->getObjects("1,6", $this->charid, "container");
		$in_str = "";
		if ($containers) {
			foreach ($containers as $cont) {
				if ($in_str) $in_str .= ", ";
				$in_str .= $cont;
			}
			$in_str = " OR `uid` IN (" . $in_str . ")";
		}
		$ptype = new ProjectType($this->mysqli, $this->type);
		$posTools = $ptype->getTools();
		$available = array();
		foreach ($posTools as $possible) {
			if ($possible["slot"]==$slot) {
				$source = $possible["place"];
				if ($source==1) $sql="SELECT `uid` FROM `objects` WHERE `parent`=$char->bodyId AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
				if ($source==2) $sql="SELECT `uid` FROM `objects` WHERE " . $char->getCoordsForSQL3() . " AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
				if ($source==3) $sql="SELECT `uid` FROM `objects` WHERE (`parent`=$char->bodyId $in_str ) AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
				if ($source==4) $sql="SELECT `uid` FROM `objects` WHERE `parent`=$char->bodyId AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
				//to do: what if in a building?
				//to do: handle source 4 properly
				$result = $this->mysqli->query($sql);
				if (mysqli_num_rows($result)) {
					while ($row = mysqli_fetch_row($result)) {
						$available[] = array(
							"uid" => $row[0],
							"type" => $possible["uid"],
							"name" => $possible["name"],
							"ap_multi" => $possible["ap_multi"],
							"quality" => $possible["quality"]
							);
					}
				}
			}
		}
		return $available;
	}
	
	function getPoolToolsAvailable($pool) {
		$char = new Character($this->mysqli, $this->charid);
		$pos = $char->getPosition();
		$curTime = new Time($this->mysqli);
		$localMap = new LocalMap($this->mysqli, $pos->x, $pos->y);
		$containers = $localMap->getObjects("1,6", $this->charid, "container");
		$in_str = "";
		if ($containers) {
			foreach ($containers as $cont) {
				if ($in_str) $in_str .= ", ";
				$in_str .= $cont;
			}
			$in_str = " OR `uid` IN (" . $in_str . ")";
		}
		$ptype = new ProjectType($this->mysqli, $this->type);
		$posTools = $ptype->getToolsInPool($pool);
		$available = array();
		foreach ($posTools as $possible) {
			$source = $possible["place"];
			if ($source==1) $sql="SELECT `uid` FROM `objects` WHERE `parent`=$char->bodyId AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			if ($source==2) $sql="SELECT `uid` FROM `objects` WHERE " . $char->getCoordsForSQL3() . " AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			if ($source==3) $sql="SELECT `uid` FROM `objects` WHERE (`parent`=$char->bodyId $in_str ) AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			if ($source==4) $sql="SELECT `uid` FROM `objects` WHERE `parent`=$char->bodyId AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			//to do: buildings
			//to do: source 4 may need reworked
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				while ($row = mysqli_fetch_row($result)) {
					$available[] = array(
						"uid" => $row[0],
						"type" => $possible["uid"],
						"name" => $possible["name"],
						"ap_multi" => $possible["ap_multi"],
						"quality" => $possible["quality"],
						"fuel" => $possible["fuel"]
						);
				}
			}
		}
		return $available;
	}
	
	function checkTools($tools) {
		$ap = 0;
		$quality = 0;
		$counter = 0;
		$ptype = new ProjectType($this->mysqli, $this->type);
		$possible = $ptype->getTools();
		if ($possible==-1) return array(
			"ap" => 100,
			"quality" => 100
			);//No tools needed
		foreach ($tools as $tool) {
			$obj = new Obj($this->mysqli, $tool["uid"]);
			$obj->getBasicData();
			$result = $this->searchByField($possible, "slot", $tool["slot"]);
			if (!$result) return false;
			else {
				$result2 = $this->searchByField($result, "uid", $obj->preset);
				if (!$result2) return false;
				else {
					$ap += $result2[0]["ap_multi"];
					$quality += $result2[0]["quality"];
					$counter++;
				}
			}
		}
		$final_ap_multi = round($ap/$counter);
		$final_quali_multi = round($quality/$counter);
		return array(
			"ap" => $final_ap_multi,
			"quality" => $final_quali_multi
			);
	}
	
	function checkPoolTools($tools) {
		$ap = 0;
		$quality = 0;
		$counter = 0;
		$ptype = new ProjectType($this->mysqli, $this->type);
		$possible = $ptype->getToolPools();
		if ($possible==-1) return array(
			"ap" => 100,
			"quality" => 100
			);//No tools needed
		$pools = array();
		foreach ($possible as $pool) {
			$the_pool = $ptype->getToolsInPool($pool["uid"]);
			foreach ($the_pool as $pool2) {
				$pools[] = $pool2;
			}
		}
		foreach ($tools as $tool) {
			$obj = new Obj($this->mysqli, $tool["uid"]);
			$obj->getBasicData();
			
			$result = $this->searchByField($pools, "slot", $tool["slot"]);
			if (!$result) return false;
			else {
				$result2 = $this->searchByField($result, "uid", $obj->preset);
				if (!$result2) return false;
				else {
					$ap += $result2[0]["ap_multi"];
					$quality += $result2[0]["quality"];
					$counter++;
				}
			}
		}
		$final_ap_multi = round($ap/$counter);
		$final_quali_multi = round($quality/$counter);
		return array(
			"ap" => $final_ap_multi,
			"quality" => $final_quali_multi
			);
	}
	
	function investAP($ap, $tools, $tools2) {
		$tpoolcheck = $this->checkPoolTools($tools2);
		if (!$tpoolcheck) return -5;//Not all tools
		//$tools is an array with keys "slot", "uid"
		$toolcheck = $this->checkTools($tools);
		if (!$toolcheck) return -5;//Not all tools
		//to-do: in the future apply deterioration to tools used
		$will_finish = false;
		$worker = new Character($this->mysqli, $this->charid);
		$available = $worker->getAP();
		$curTime = new Time($this->mysqli);
		if ($available<1) return -1;//Out of AP
		if ($available<$ap) $ap = $available;
		if ($this->finished) return -2;//This project has already been finished
		else if ($this->invested>=$this->ap) {
			$finished = $this->finish();
			return $finished;
		}
		else {
			$newQuality = round($this->quality*$toolcheck["quality"]/100)*$tpoolcheck["quality"]/100;
			if ($toolcheck["ap"]==100) $ap_multiplier = 100/$tpoolcheck["ap"];
			else if ($tpoolcheck["ap"]==100) $ap_multiplier = 100/$toolcheck["ap"];
			else $ap_multiplier = (100/$toolcheck["ap"] + 100/$tpoolcheck["ap"])/ 2;
			$ptype = new ProjectType($this->mysqli, $this->type);
			$need_ap = $this->ap - $this->invested;
			$modified = $need_ap*$ap_multiplier;
			if ($ap<$modified) {
				$spend_ap = $ap;
				$actual_ap = round($ap/$ap_multiplier);
			}
			else {
				$spend_ap = $modified;
				$actual_ap = $need_ap;
				$will_finish = true;
			}
			$worker->spendAP($spend_ap);
			$worker->getBasicData();
			$worker->updateCharLocTime($worker->x, $worker->y, $worker->localx, $worker->localy, $worker->building, 1, $spend_ap);
			
			$pt = new ProjectType($this->mysqli, $this->type);
			$needFuel = $pt->getMachinesNeedFuel();
			$thisNeedFuel = false;
			foreach ($tools2 as $t) {
				$to = new Obj($this->mysqli, $t["uid"]);
				$to->getBasicData();
				if (in_array($to->preset, $needFuel)) {
					$thisNeedFuel = $to->uid;
					$break;
				}
			}
			
			if ($thisNeedFuel) {
				$sql2 = "SELECT `uid` FROM `fuel_projects` WHERE `machineFK`=$thisNeedFuel ORDER BY `startDatetime` DESC, `startMinute` DESC LIMIT 1";
				$result = $this->mysqli->query($sql2);
				if (mysqli_num_rows($result)) {
					$row = mysqli_fetch_row($result);
					$ft = new FuelProject($this->mysqli, $row[0]);
					$ft->getInfo();
					$fireStatus = $ft->checkProgress($this->charid);
					//para($fireStatus);
					if ($fireStatus==1||$fireStatus==2||$fireStatus==-3||$fireStatus==-5) {
						return -6;
					}
					else if ($fireStatus==-6) {
						return -8;//The fire went out while you were working
					}//otherwise this can continue
				}
				else return -7;
			}
			
			$sql = "UPDATE `projects` SET `ap_invested`=`ap_invested`+$actual_ap, `quality`=$newQuality WHERE `uid`=$this->uid LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) return -2;//AP was deducted but adding it to the project failed
			if ($will_finish) {
				$finished = $this->finish();
				return $finished;
			}
		}
		return -4;//There's still work to do
	}
	
	function countEndTime() {
		$pt = new ProjectType($this->mysqli, $this->type);
		if ($pt->delay>0) {
			$time = new Time($this->mysqli);
			$time->addTime($pt->delay);
			$sql = "UPDATE `projects` SET `end_dt`=$time->dateTime, `end_m`=$time->minute WHERE `uid`=$this->uid LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) return -1;//Project type not found
			else {
				$this->end_dt = $time->dateTime;
				$this->end_m = $time->minute;
				return 1;//Success
			}
		}
		return 2;//This has no delay
	}
	
	function finish() {
		if ($this->finished>0) return -4;//This project has already been finished
		$ok = false;
		$pt = new ProjectType($this->mysqli, $this->type);
		
		if ($pt->delay>0&&$this->end_dt==0) {
			$check = $this->countEndTime();
			if ($check<2) $ok = false;//Cannot continue
			else $ok = true;
		}
		else if ($pt->delay>0) {
			//delay has already been defined
			$time = new Time($this->mysqli);
			if (($time->dateTime>$this->end_dt)||$time->dateTime==$this->end_dt&&$time->minute>=$this->end_m) $ok = true;
			else $ok = false;
		}
		else $ok = true;//Delay is 0
		if (!$ok) return 50;//You wait
		//Otherwise finish
		$char = new Character($this->mysqli, $this->charid);
		$pos = $char->getPosition();
		$building = 0;//if this is an actual building and not a group, then it needs to be equal to char->building, but then $pos must be NULL
		$curTime = new Time($this->mysqli);
		$preset = new Preset($this->mysqli, $pt->pre_uid);
		if ($pt->pre_uid==20) {
			$resource = new Resource($this->mysqli, $pt->secondary);
			$stackable = $resource->getAttr(44);
		}
		else $stackable = $preset->getAttribute(44);
		if ($pt->secondary==-1) {
			//if secondary is -1, it means the type of the resource in the first slot is copied
			$sql = "SELECT `res_typeFK` FROM `added_resources` WHERE `projectFK`=$this->uid ORDER BY `slot` LIMIT 1";
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				$row = mysqli_fetch_row($result);
				$secondary = $row[0];
			}
			else $secondary = -1;
		}
		else if ($pt->secondary==-2) {
			//if secondary is -2, it means the secondary of the first component is copied
			$sql = "SELECT `secondary` FROM `added_components` WHERE `projectFK`=$this->uid ORDER BY `slot` LIMIT 1";
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				$row = mysqli_fetch_row($result);
				$secondary = $row[0];
			}
			else $secondary = -2;
		}
		else $secondary = $pt->secondary;
		
		if ($pt->end_weight<0) {
			//-100 = same weight
			//-50 = half weight
			$sql = "SELECT SUM(`weight`) FROM `added_components` WHERE `projectFK`=$this->uid";
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				$row = mysqli_fetch_row($result);
				$weight = round((-1*$pt->end_weight)/100*$row[0]);
			}
			else $weight = 0;
		}
		
		//to-do: enter quality in o_attrs
		if ($pt->place==1) {
			if ($stackable == 1||$pt->pre_uid==20) {
				if (isset($weight)) $multi_wt=$weight;
				else $multi_wt = $pt->end_weight * $this->multiples;
				$newItem = new Obj($this->mysqli);
				if ($pt->pre_uid==20) $pieces = $resource->getPieces($multi_wt);
				else $pieces = $preset->getPieces($multi_wt);
				$id = $newItem->create($pt->pre_uid, $pt->gen_type, $char->bodyId, "Created by finished manufacturing", 'NULL', 'NULL', 0, 0, $secondary, $pieces, $multi_wt, $curTime->dateTime, $curTime->minute);
				if (!$this->enterFinished($id)) return -3;
			}
			else
			{
				if (isset($weight)) $one_wt = $weight/$this->multiples;
				else $one_wt = $pt->end_weight;
				for ($i=0;$i<$this->multiples;$i++) {
					$newItem = new Obj($this->mysqli);
					$id = $newItem->create($pt->pre_uid, $pt->gen_type, $char->bodyId, "Created by finished manufacturing", 'NULL', 'NULL', 0, 0, $secondary, 1, $one_wt, $curTime->dateTime, $curTime->minute);
					if (!$this->enterFinished($id)) return -3;
					
				}
			}
			$side_am = $this->multiples * $pt->side_wt;
			$side = new Obj($this->mysqli);
			if ($pt->side_pro!=0) {
				if ($pt->side_pro==-1) {
					//if secondary is -1, it means the type of the resource in the first slot is copied
					$sql = "SELECT `res_typeFK` FROM `added_resources` WHERE `projectFK`=$this->uid ORDER BY `slot` LIMIT 1";
					$result = $this->mysqli->query($sql);
					if (mysqli_num_rows($result)) {
						$row = mysqli_fetch_row($result);
						$secondary2 = $row[0];
					}
					else $secondary2 = -1;
					$side->create($pt->side_pre, 5, $building, "Manufacturing side product", $pos->x, $pos->y, $pos->lx, $pos->ly, $secondary2, 1, $side_am, $curTime->dateTime, $curTime->minute);
				}
				else $side->create($pt->side_pre, 5, $building, "Manufacturing side product", $pos->x, $pos->y, $pos->lx, $pos->ly, $pt->side_pro, 1, $side_am, $curTime->dateTime, $curTime->minute);
			}
		}
		else if ($pt->place==2) {
			//to do: handle in building correctly
			if ($stackable == 1||$pt->pre_uid==20) {
				if (isset($weight)) $multi_wt=$weight;
				else $multi_wt = $pt->end_weight * $this->multiples;
				$newItem = new Obj($this->mysqli);
				if ($pt->pre_uid==20) $pieces = $resource->getPieces($multi_wt);
				else $pieces = $preset->getPieces($multi_wt);
				$id = $newItem->create($pt->pre_uid, $pt->gen_type, $building, "Created by finished manufacturing", $pos->x, $pos->y, $pos->lx, $pos->ly,  $secondary, $pieces, $multi_wt, $curTime->dateTime, $curTime->minute);
				if (!$this->enterFinished($id)) return -3;
			}
			else {
				if (isset($weight)) $one_wt = $weight/$this->multiples;
				else $one_wt = $pt->end_weight;
				for ($i=0;$i<$this->multiples;$i++) {
					$newItem = new Obj($this->mysqli);
					$id = $newItem->create($pt->pre_uid, $pt->gen_type, $building, "Created by finished manufacturing", $pos->x, $pos->y, $pos->lx, $pos->ly,  $secondary, 1, $one_wt, $curTime->dateTime, $curTime->minute);
					if (!$this->enterFinished($id)) return -3;
				}
			}
			$side_am = $this->multiples * $pt->side_wt;
			$side = new Obj($this->mysqli);
			if ($pt->side_pro!=0) {
				if ($pt->side_pro==-1) {
					//if secondary is -1, it means the type of the resource in the first slot is copied
					$sql = "SELECT `res_typeFK` FROM `added_resources` WHERE `projectFK`=$this->uid ORDER BY `slot` LIMIT 1";
					$result = $this->mysqli->query($sql);
					if (mysqli_num_rows($result)) {
						$row = mysqli_fetch_row($result);
						$secondary2 = $row[0];
					}
					else $secondary2 = -1;
					$side->create($pt->side_pre, 5, $building, "Manufacturing side product", $pos->x, $pos->y, $pos->lx, $pos->ly, $secondary2, 1, $side_am, $curTime->dateTime, $curTime->minute);
				}
				else $side->create($pt->side_pre, 5, $building, "Manufacturing side product", $pos->x, $pos->y, $pos->lx, $pos->ly, $pt->side_pro, 1, $side_am, $curTime->dateTime, $curTime->minute);
			}
		}
		//to do: place 3 is container
		return 100;
	}
	
	function enterFinished($obj) {
		$sql = "UPDATE `projects` SET `finishedFK`=$obj WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;
	}
	
	function emptyResourceSlot($slot) {
		$resources = $this->getAddedResources($_GET["slot"]);
		if ($resources==-1) return -1;//There are no resources in this slot
		$amount = $resources[0]["weight_a"];
		$type = $resources[0]["res_type"];
		$pr = $resources[0]["preset"];
		$sql = "DELETE FROM `added_resources` WHERE `slot`=$slot AND `projectFK`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			//if ($amount==0) return -1;
			$char = new Character($this->mysqli, $this->charid);
			$pos = $char->getPosition();
			$building = 0;//If the character is actually in a building and not a group then POS needs to be NULL
			$curTime = new Time($this->mysqli);
			$newItem = new Obj($this->mysqli);
			$id = $newItem->create($pr, 5, $building, "Removed from a manufacturing project", $pos->x, $pos->y, $pos->lx, $pos->ly, $type, 1, $amount, $curTime->dateTime, $curTime->minute);
			if ($id) return 100;
			else return -3;//I accidentally the whole pile
		}
		else return -2;//Removing resource failed
	}
	
	function emptyComponentSlot($slot) {
		//to-do: Restore preserved parts
		$added = $this->getAddedComponents($slot);
		if ($added==-1) return -1;//There are no resources in this slot
		
		$sql = "DELETE FROM `added_components` WHERE `slot`=$slot AND `projectFK`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			foreach ($added as $row) {
				$preset = new Preset($this->mysqli, $row["preset"]);
				$weight = $preset->getAttribute(6);//small weight
				$stackable = $preset->getAttribute(44);
				$pieces = $row["pieces_a"];
				if ($pieces>1&&$stackable==1) {
					$multiweight = $pieces*$weight;
					$char = new Character($this->mysqli, $this->charid);
					$pos = $char->getPosition();
					$building = 0;//If the character is actually in a building and not a group then POS needs to be NULL
					$curTime = new Time($this->mysqli);
					$newItem = new Obj($this->mysqli);
					$id = $newItem->create($row["preset"], 1, $building, "Removed from a manufacturing project", $pos->x, $pos->y, $pos->lx, $pos->ly, $row["secondary"], $pieces, $multiweight, $curTime->dateTime, $curTime->minute);
					if ($id) return 100;
					else return -3;
				}
				else {
					$char = new Character($this->mysqli, $this->charid);
					$pos = $char->getPosition();
					$building = 0;//If the character is actually in a building and not a group then POS needs to be NULL
					$curTime = new Time($this->mysqli);
					for ($i=0;$i<$pieces;$i++) {
						$newItem = new Obj($this->mysqli);
						$id = $newItem->create($row["preset"], 1, $building, "Removed from a manufacturing project", $pos->x, $pos->y, $pos->lx, $pos->ly, $row["secondary"], 1, $weight, $curTime->dateTime, $curTime->minute);
						if (!$id) return -3;
					}
			}
				return 100;
			}
		}
		else return -2;
	}
}

?>
