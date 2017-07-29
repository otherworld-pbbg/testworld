<?php
include_once("class_project_type.inc.php");

class BuildMenu {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function getObjectManufacturing() {
		$retArr = array();
		$sql = "SELECT `project_types`.`uid`, `presetFK`, `name`, `max_multi`, `movable`, `total_ap`, `gen_type` FROM `project_types` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE `secondary`=0 ORDER BY `presetFK`";
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
					"gen_type" => $row[6]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function printObjectManufacturing() {
		$projects = $this->getObjectManufacturing();
		
		if ($projects==-1) para("Currently there are none of this kind.");
		else {
			for ($i=0;$i<sizeof($projects);$i++) {
				$curProject = new ProjectType($this->mysqli, $projects[$i]["uid"]);
				echo "<form class='narrow' name='pro_type-" . $projects[$i]["uid"] . "'>";
				ptag("h3", $projects[$i]["pre_name"], "class='manufacturable'");
				para("AP: " . $projects[$i]["ap"]);
				para("Max multiples: " . $projects[$i]["max_multi"]);
				if ($projects[$i]["gen_type"]==1) $type = "regular";
				else if ($projects[$i]["gen_type"]==6) $type = "fixed structure";
				else $type = "unknown";
				para("Object type: " . $type);
				if ($projects[$i]["movable"]==1) $move = "yes";
				else if ($projects[$i]["movable"]==0) $move = "no";
				para("Movable during construction: " . $move);
				ptag("h4", "Resources:");
				$curProject->printResources();
				ptag("h4", "Components:");
				$curProject->printComponents();
				ptag("h4", "Tools/machines:");
				$curProject->printTools();
				ptag("input", "", "type='hidden' name='sel' value='". $projects[$i]["uid"] ."'");
				ptag("input", "", "type='submit' value='Choose to make'");
				echo "</form>";
			}
		}
	}
	
	function searchParentKey($categories, $field, $value)
	{
	   foreach($categories as $key => $category)
	   {
	   	   
	      if ( $category[$field] == $value )
		 return $key;
	   }
	   return -1;
	}
	
	public function getCategories() {
		$categories = array();
		$sql = "SELECT `uid`, `name`, `parent` FROM `project_categories` WHERE 1 order by `parent`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$categories[] = array(
					"id" => $row[0],
					"name" => $row[1],
					"parent" => $row[2],
					"childcount" => 0
					);
			}
		}
		
		for ($i=0; $i<count($categories);$i++) {
			if ($categories[$i]["parent"]>0) {
				$key = $this->searchParentKey($categories, "id", $categories[$i]["parent"]);
				if ($key>-1) $categories[$key]["childcount"]++;
			}
		}
		return $categories;
	}
}

?>
