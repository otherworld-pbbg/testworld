<?php

class ProjectType {
	private $mysqli;
	public $uid=0;
	public $pre_uid = 0;
	public $pre_name = 0;
	public $max_multi = 0;
	public $movable = 0;
	public $ap = 0;
	public $gen_type = 0;
	public $category = 0;
	public $side_pro = 0;
	public $side_wt = 0;
	public $place = 0;
	public $end_weight = 0;
	public $secondary = 0;
	public $side_pre = 0;
	public $delay = 0;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		if ($uid>0) $this->getInfo();
	}
	
	public function getInfo() {
		$retArr = array();
		$sql = "SELECT `presetFK`, `name`, `max_multi`, `movable`, `total_ap`, `gen_type`, `category`, `side_product`, `side_weight`, `end_placement`, `end_weight`, `secondary`, `side_preset`, `delay` FROM `project_types` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE `project_types`.`uid`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->pre_uid = $row[0];
			$this->pre_name = $row[1];
			$this->max_multi = $row[2];
			$this->movable = $row[3];
			$this->ap = $row[4];
			$this->gen_type = $row[5];
			$this->category = $row[6];
			$this->side_pro = $row[7];
			$this->side_wt = $row[8];
			$this->place = $row[9];
			$this->end_weight = $row[10];
			$this->secondary = $row[11];
			$this->side_pre = $row[12];
			$this->delay = $row[13];
		}
	}
	
	public function getTools() {
		$retArr = array();
		$sql = "SELECT `toolFK`, `name`, `slot`, `ap_multiplier`, `quality`, `place` FROM `needed_tools` JOIN `o_presets` ON `toolFK`=`o_presets`.`uid` WHERE `project_type`=$this->uid ORDER BY `slot`, `ap_multiplier` DESC, `quality`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"slot" => $row[2],
					"ap_multi" => $row[3],
					"quality" => $row[4],
					"place" => $row[5]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function getResources() {
		$retArr = array();
		$sql = "SELECT `resFK`, `name`, `weight`, `slot`, `ap_multiplier`, `quality` FROM `needed_resources` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `project_type`=$this->uid ORDER BY `slot`, `quality` DESC, `ap_multiplier` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"weight" => $row[2],
					"slot" => $row[3],
					"ap_multi" => $row[4],
					"quality" => $row[5]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function getComponents() {
		$retArr = array();
		$sql = "SELECT `presetFK`, `name`, `pieces`, `slot`, `ap_multiplier`, `quality`, `secondary` FROM `needed_components` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE `project_type`=$this->uid ORDER BY `slot`, `quality` DESC, `ap_multiplier` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"pieces" => $row[2],
					"slot" => $row[3],
					"ap_multi" => $row[4],
					"quality" => $row[5],
					"secondary" => $row[6]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function printTools() {
		$tools = $this->getTools();
		$prevSlot = 0;
		
		if ($tools==-1) para("none");
		else {
			echo "<ul class='tool'>";
			for ($i=0;$i<sizeof($tools);$i++) {
				if ($tools[$i]["place"]==1) $requirement = "needs to be held";
				else if ($tools[$i]["place"]==2) $requirement = "needs to be on the spot";
				else if ($tools[$i]["place"]==3) $requirement = "needs to be in a container on the spot or held in hand";
				else if ($tools[$i]["place"]==4) $requirement = "will be bound by project";
				
				if ($prevSlot!=$tools[$i]["slot"]&&$i>0) echo "<li>---</li>";
				echo "<li>";
				if ($prevSlot==$tools[$i]["slot"]) echo "OR ";
				
				echo $tools[$i]["name"] . " (efficiency: " . $tools[$i]["ap_multi"] . " %) - $requirement";
				
				$prevSlot = $tools[$i]["slot"];
			}
			echo "</ul>";
		}
	}
	
	public function getToolPools() {
		$retArr = array();
		$sql = "SELECT `poolFK`, `name` FROM `needed_t_pools` join `tool_pools` on `poolFK`=`tool_pools`.`uid` WHERE `projectFK`=$this->uid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1]
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function printToolPools() {
		$pools = $this->getToolPools();
		if ($pools == -1) para("none");
		else {
			$prevSlot = 0;
			foreach ($pools as $pool) {
				ptag("h4", $pool["name"]);
				$tools = $this->getToolsInPool($pool["uid"]);
				if ($tools == -1) para("Strange, there seems to be nothing here. Probably an oversight.");
				else {
					echo "<ul class='tool'>";
					foreach ($tools as $tool) {
						if ($tool["place"]==1) $requirement = "needs to be held";
						else if ($tool["place"]==2) $requirement = "needs to be on the spot";
						else if ($tool["place"]==3) $requirement = "needs to be in a container on the spot or held in hand";
						else if ($tool["place"]==4) $requirement = "will be bound by project";
						
						echo "<li>";
						if ($prevSlot==$tool["slot"]) echo "OR ";
						if ($tool["fuel"]) $needsFuel = " (needs fuel)";
						else $needsFuel = "";
						echo $tool["name"] . " (efficiency: " . $tool["ap_multi"] . " %)$needsFuel - $requirement";
						
						$prevSlot = $tool["slot"];
					}
					echo "</ul>";
				}
			}
		}
	}
	
	public function getToolsInPool($pool) {
		
		$retArr = array();
		$needsFuel = $this->getMachinesNeedFuel();
		$sql = "SELECT `toolFK`, `name`, `ap_modifier`, `quality`, `place` FROM `pool_tools` JOIN `o_presets` ON `toolFK`=`o_presets`.`uid` WHERE `poolFK`=$pool";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				if (is_array($needFuel)) {
					if (in_array($row[0], $needsFuel)) $thisNeeds = true;
					else $thisNeeds = false;
				}
				else $thisNeeds = false;
				$retArr[] = array(
					"uid" => $row[0],
					"name" => $row[1],
					"slot" => $pool,
					"ap_multi" => $row[2],
					"quality" => $row[3],
					"place" => $row[4],
					"fuel" => $thisNeeds
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function getMachinesNeedFuel() {
		//To-do: This needs reworked
		return -1;
	}
	
	public function printResources() {
		$resources = $this->getResources();
		$prevSlot = 0;
		
		if ($resources==-1) para("none");
		else {
			echo "<ul class='res'>";
			for ($i=0;$i<sizeof($resources);$i++) {
				if ($prevSlot!=$resources[$i]["slot"]&&$i>0) echo "<li>---</li>";
				echo "<li>";
				if ($prevSlot==$resources[$i]["slot"]) echo "OR ";
				
				echo $resources[$i]["weight"] . " grams of " . $resources[$i]["name"] . " (efficiency: " . $resources[$i]["ap_multi"] . " %)";
				
				$prevSlot = $resources[$i]["slot"];
			}
			echo "</ul>";
		}
	}
	
	public function printResStrings() {
		$resources = $this->getResStrings();
		$prevSlot = 0;
		
		if ($resources==-1) para("none");
		else {
			echo "<ul class='res'>";
			for ($i=0;$i<sizeof($resources);$i++) {
				if ($prevSlot!=$resources[$i]["slot"]&&$i>0) echo "<li>and</li>";
				echo "<li>";
				if ($prevSlot==$resources[$i]["slot"]) echo "OR ";
				$rst = new ResourceString($this->mysqli, $resources[$i]["str"]);
				echo $resources[$i]["weight"] . " grams of a substance that " . $rst->beautifyStrings();
				
				$prevSlot = $resources[$i]["slot"];
			}
			echo "</ul>";
		}
	}
	
	public function printComponents() {
		$components = $this->getComponents();
		$prevSlot = 0;
		
		if ($components==-1) para("none");
		else {
			echo "<ul class='comp'>";
			for ($i=0;$i<sizeof($components);$i++) {
				if ($prevSlot!=$components[$i]["slot"]&&$i>0) echo "<li>---</li>";
				echo "<li>";
				if ($prevSlot==$components[$i]["slot"]) echo "OR ";
				echo $components[$i]["pieces"] . " x " . $components[$i]["name"] . " (efficiency: " . $components[$i]["ap_multi"] . " %)";
				
				$prevSlot = $components[$i]["slot"];
			}
			echo "</ul>";
		}
	}
	
	public function getResStrings() {
		$retArr = array();
		$sql = "SELECT `uid`, `slot`, `str`, `weight`, `preset` FROM `needed_res_strings` WHERE `project_type`=$this->uid ORDER BY `slot`, `str`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function getProjectTypes() {
		$retArr = array();
		$sql = "SELECT `project_types`.`uid` as `pid`, `presetFK`, `name`, `max_multi`, `movable`, `total_ap`, `gen_type`, `category`, `side_product`, `side_weight`, `end_placement`, `end_weight`, `secondary`, `side_preset`, `hidden` FROM `project_types` JOIN `o_presets` ON `presetFK`=`o_presets`.`uid` WHERE 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return -1;
	}
	
	public function addString($slot, $string, $weight, $preset) {
		$sql = "INSERT INTO `needed_res_strings`(`project_type`, `slot`, `str`, `weight`, `preset`) VALUES ($this->uid, $slot, '$string', $weight, $preset)";
		$this->mysqli->query($sql);
		if ($this->mysqli->insert_id) return true;
		else return false;
	}
}
	
?>
