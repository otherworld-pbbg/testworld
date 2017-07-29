<?php
include_once("class_project_type.inc.php");
include_once("class_character.inc.php");
include_once("class_resource.inc.php");
include_once("abbr.inc.php");//abbreviations: $br, para($str), ptag($tagname, $contents, [$attr])
class BuildMenu2 {
	
	private $mysqli;
	private $userid;
	private $charid;
	public $project_list = array();
	
	public function __construct($mysqli, $userid, $charid) {
		$this->mysqli = $mysqli;
		$this->userid = $userid;
		$this->charid = $charid;
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
	
	public function getProjectsHere() {
		$viewer = new Character($this->mysqli, $this->charid);//to do: what if in a building?
		$sql = "SELECT `uid`, `project_type`, `ap_total`, `ap_invested`, `datetime`, `minute`, `multiples`, `starter` FROM `projects` WHERE " . $viewer->getCoordsForSQL4() . " AND `finishedFK` IS NULL ORDER BY `uid` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$retArr = array();
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"type" => $row[1],
					"ap" => $row[2],
					"invested" => $row[3],
					"datetime" => $row[4],
					"minute" => $row[5],
					"multiples" => $row[6],
					"starter" => $row[7]
					);
			}
			return $retArr;
		}
		else return 0;
	}
	
	public function listProjectsHere() {
		$list = $this->getProjectsHere();
		if (!$list) para("None here.");
		else {
			foreach ($list as $project) {
				$percent = round($project["invested"]/$project["ap"] * 100);
				$viewer = new Character($this->mysqli, $this->charid);
				$starter = new Character($this->mysqli, $project["starter"]);
				$pname = $viewer->getDynamicName($starter->uid);
				$desc = $starter->getAgeSex();
				$info = $this->getSingleProjectType($project["type"]);
				
				if (empty($info)) para("Project type not found");
				else {
					if ($info["pre_uid"]==20) {
						$result = new Resource($this->mysqli, $info["secondary"]);
						$result->loadData();
						$info["pre_name"] = $result->name;
					}
					echo "<p>";
					ptag("a", "Manufacturing " . $project["multiples"] . " x " . $info["pre_name"], "href='index.php?page=viewProject&charid=$this->charid&userid=$this->userid&pid=" . $project["uid"] . "' class='clist'");
					echo " ($percent %) - ";
					ptag("a", $pname, "href='index.php?page=formCharName&charid=$this->charid&userid=$this->userid&ocharid=" . $starter->uid . "' class='clist'");
					echo " ($desc)";
					echo "</p>\n";
				}
			}
		}
	}
	
	public function startManuProject($project_type, $multiples) {
		$details = $this->getSingleProjectType($project_type);
		if ($details) {
			if ($multiples>$details["max_multi"]) return -2;//Multiples out of range
			if ($multiples<1) $multiples = 1;
			$ap = $multiples*$details["ap"];
			$starter = new Character($this->mysqli, $this->charid);
			$pos = $starter->getPosition();
			$building = 0;//To Do: If this is actually in a building then building is something other than 0 but currently people can only be in groups, so the project shouldn't be inside a group but in the general space
			$starter->getBasicData();
			$dt = new Time($this->mysqli);
			//$time = $starter->getInternalTime();
			//if (!$time) return -3;//The character doesn't have a timeline
			$sql = "INSERT INTO `projects`(`uid`, `project_type`, `ap_total`, `ap_invested`, `quality`, `x`, `y`, `local_x`, `local_y`, `parent`, `datetime`, `minute`, `multiples`, `starter`, `finishedFK`) VALUES (NULL, '$project_type', '$ap', '0', '100', '$pos->x', '$pos->y', '$pos->lx', '$pos->ly', '$building', '". $dt->dateTime ."', '". $dt->minute ."', '$multiples', '$starter->uid', NULL)";
			$this->mysqli->query($sql);
			$result = $this->mysqli->insert_id;
			if ($result) return $result;
			else return -4;//creation failed
		}
		return -1;//Project type not found
	}
	
	public function getSingleProjectType($uid) {
		$retArr = array();
		$sql = "SELECT `project_types`.`uid`, `presetFK`, `name`, `max_multi`, `movable`, `total_ap`, `gen_type`, `category`, `secondary` FROM `project_types` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE `project_types`.`uid`=$uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$retArr = array(
				"uid" => $row[0],
				"pre_uid" => $row[1],
				"pre_name" => $row[2],
				"max_multi" => $row[3],
				"movable" => $row[4],
				"ap" => $row[5],
				"gen_type" => $row[6],
				"category" => $row[7],
				"secondary" => $row[8]
				);
		}
		return $retArr;
	}
	
	public function printDataSingle($uid) {
		$project = $this->getSingleProjectType($uid);
		if (!$project) para("Sorry, maybe you picked a project type that doesn't exist. Anyway, it couldn't be loaded.");
			else {
			$curProject = new ProjectType($this->mysqli, $project["uid"]);
			if ($project["pre_uid"]==20) {
				$result = new Resource($this->mysqli, $project["secondary"]);
				$result->loadData();
				$project["pre_name"] = $result->name;
			}
			echo "<form method='get' action='index.php' class='medium' name='startManu' id='startManu'>";
			ptag("h1", "Start manufacturing");
			ptag("h2", "Result: " . $project["pre_name"]);
			para("AP: " . $project["ap"] . "(*");
			para("Max multiples: " . $project["max_multi"]);
			if ($project["gen_type"]==1) $type = "regular";
			else if ($project["gen_type"]==6) $type = "fixed structure";
			else $type = "unknown";
			para("Object type: " . $type);
			if ($project["movable"]==1) $move = "yes";
			else if ($project["movable"]==0) $move = "no";
			para("Movable during construction: " . $move);
			ptag("h4", "Resources:");
			$curProject->printResStrings();
			ptag("h4", "Components:");
			$curProject->printComponents();
			ptag("h4", "Tool pools:");
			$curProject->printToolPools();
			ptag("h4", "Individual tools/machines:");
			$curProject->printTools();//This should be changed to affect which tools are available
			
			ptag("h2", "Multiples: ");
			if ($project["max_multi"]<2) {
				para("This project cannot be multiplied.");
				ptag("input", "", "type='hidden' name='multiples' value='1'");
			}
			else {
				echo "<p>";
				echo "<select id='multiples' name='multiples' form='startManu'>";
				for ($i=1;$i<=$project["max_multi"];$i++) {
					echo "<option value='$i'>$i</option>";
				}
				echo "</select>";
				echo "</p>";
			}
			para("*) Some tools are more effective than others.");
			ptag("input", "", "type='hidden' name='page' value='startProject'");
			ptag("input", "", "type='hidden' name='sel' value='". $project["uid"] ."'");
			ptag("input", "", "type='hidden' name='userid' value='". $this->userid ."'");
			ptag("input", "", "type='hidden' name='charid' value='". $this->charid ."'");
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Start manufacturing project'");
			echo "<p>";
			para("After you start the project, you need to add the resources and components to it either from inventory or the ground. Usually there are multiple alternatives to choose from. Once you have picked one type, you cannot add other materials to the same slot unless you first remove the material or component you put in.");
			echo "</form>";
		}
	}
	
	public function getObjectManufacturing() {
		$retArr = array();
		if ($this->project_list) return $this->project_list;
		$sql = "SELECT `project_types`.`uid`, `presetFK`, `name`, `max_multi`, `movable`, `total_ap`, `gen_type`, `category` FROM `project_types` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE (`gen_type`=1 OR `gen_type`=6) AND `hidden`=0 ORDER BY `name`, `presetFK`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"pre_uid" => $row[1],
					"pre_name" => $row[2],
					"max_multi" => $row[3],
					"movable" => $row[4],
					"ap" => $row[5],
					"gen_type" => $row[6],
					"category" => $row[7]
					);
			}
			$this->project_list = $retArr;
		}
		return $retArr;
	}
	
	public function printObjectManufacturing($viewId) {
		$projects = $this->getObjectManufacturing();//The reason this gets everything is that it puts it in a variable, so if there are several calls, it shouldn't load it
		$projects2 = $this->searchByField($projects, "uid", $viewId);//..repeatedly. But if it turns out it is loading it from the db every time then this can be changed
		
		if ($projects2) {
			$curProject = new ProjectType($this->mysqli, $projects2[0]["uid"]);
			echo "<form method='get' action='index.php' class='narrow' name='pro_type-" . $projects2[0]["uid"] . "'>";
			ptag("h3", $projects2[0]["pre_name"], "class='manufacturable'");
			para("AP: " . $projects2[0]["ap"]);
			para("Max multiples: " . $projects2[0]["max_multi"]);
			if ($projects2[0]["gen_type"]==1) $type = "regular";
			else if ($projects2[0]["gen_type"]==6) $type = "fixed structure";
			else $type = "unknown";
			para("Object type: " . $type);
			if ($projects2[0]["movable"]==1) $move = "yes";
			else if ($projects2[0]["movable"]==0) $move = "no";
			para("Movable during construction: " . $move);
			ptag("h4", "Resources:");
			$curProject->printResStrings();
			ptag("h4", "Components:");
			$curProject->printComponents();
			ptag("h4", "Tool pools:");
			$curProject->printToolPools();
			ptag("h4", "Individual tools/machines:");
			$curProject->printTools();
			ptag("input", "", "type='hidden' name='page' value='manufacture'");
			ptag("input", "", "type='hidden' name='sel' value='". $projects2[0]["uid"] ."'");
			ptag("input", "", "type='hidden' name='userid' value='". $this->userid ."'");
			ptag("input", "", "type='hidden' name='charid' value='". $this->charid ."'");
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Choose to make'");
			echo "<p>";
			echo "</form>";
		}
		else echo "<p>Nothing selected</p>";
	}

	public function branch($parentId = null) {
    	$categories = array (
		array("id" => "1", "label" => "Neolithic", "parent" => "0"),
		array("id" => "2", "label" => "Copper tech", "parent" => "0"),
		array("id" => "3", "label" => "Bronze tech", "parent" => "0"),
		array("id" => "4", "label" => "Iron tech", "parent" => "0"),
		array("id" => "5", "label" => "Steel tech", "parent" => "0"),
		array("id" => "6", "label" => "Clay items", "parent" => "0"),
		array("id" => "23", "label" => "Needlework", "parent" => "0"),
		array("id" => "25", "label" => "Wood tech", "parent" => "0"),
		array("id" => "8", "label" => "Tools", "parent" => "1"),
		array("id" => "9", "label" => "Weapons", "parent" => "1"),
		array("id" => "18", "label" => "Components", "parent" => "1"),
		array("id" => "10", "label" => "Tools", "parent" => "2"),
		array("id" => "11", "label" => "Weapons", "parent" => "2"),
		array("id" => "19", "label" => "Components", "parent" => "2"),
		array("id" => "12", "label" => "Tools", "parent" => "3"),
		array("id" => "13", "label" => "Weapons", "parent" => "3"),
		array("id" => "20", "label" => "Components", "parent" => "3"),
		array("id" => "14", "label" => "Tools", "parent" => "4"),
		array("id" => "15", "label" => "Weapons", "parent" => "4"),
		array("id" => "21", "label" => "Components", "parent" => "4"),
		array("id" => "16", "label" => "Tools", "parent" => "5"),
		array("id" => "17", "label" => "Weapons", "parent" => "5"),
		array("id" => "22", "label" => "Components", "parent" => "5"),
		array("id" => "7", "label" => "Clothing", "parent" => "23"),
		array("id" => "24", "label" => "Tools", "parent" => "23"),
		array("id" => "26", "label" => "Tools", "parent" => "25"),
		array("id" => "27", "label" => "Weapons", "parent" => "25"),
		array("id" => "28", "label" => "Components", "parent" => "25")
	);
	$branch = array();
        $split = explode('.', $parentId);
        $type = array_shift($split);
        if (!$type || ($type == 'folder')) {
        	if (!$type) $split = array("", "0");
        	$results = $this->searchByField($categories, "parent", $split[count($split)-1]);
        	$projects = $this->getObjectManufacturing();
        	$results2 = $this->searchByField($projects, "category", $split[count($split)-1]);
        	
        	if ($results) {
        		foreach($results as $key => $result) {
        			$value = $result["id"];
        			$branch["folder..$value"] = $result["label"];
        		}
        	}
        	
        	if ($results2) {
        		foreach($results2 as $key => $result) {
        			$value = $result["uid"];
        			$branch["file..$value"] = $result["pre_name"] . " <input type='button' class='picker' value='Pick' onclick='selectManu(" . $result["uid"] . ")'>";
        		}
        	}
        	
		}
        	return $branch;
	}

	public function itemProps($itemId) {
		$details = explode('.', $itemId);
		$type = array_shift($details);
		if ($type=="folder") {
		return array(
			'inode' => true,
			'open' => false,
			"icon" => 'folder'
			);
		}
		else if ($type=="file") {
		return array(
			'inode' => false,
			'open' => false,
			'icon' => 'file'
			);
		}
		else {
		return array(
			'inode' => null,
			'open' => false,
			'icon' => 'null'
			);
		}
	
	}


	private function _json($parentId, Array &$json) {
		$branch = $this->branch($parentId);
		foreach ($branch as $id => $label) {
			$props = $this->itemProps($id);
			$json[] = array_merge(array(
				'id' => $id,
				'label' => $label,
				'branch' => array()
				), $props);
		}
	}

	public function json($parentId) {
		$json = array();
		$this->_json($parentId, $json);
		header('Content-type: application/json; charset=UTF-8');
		header('Vary: Accept-Encoding');
		echo json_encode($json);
		die;
	}
}
?>
