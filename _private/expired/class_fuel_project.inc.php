<?php

include_once "class_character.inc.php";
include_once("class_obj.inc.php");

class FuelProject {
	private $mysqli;
	public $uid=0;
	public $container=0;
	public $datetime=1010100;
	public $minute=0;
	public $status=0;
	public $ap=0;
	public $duration=0;
	public $afterglow=0;
	public $ptype=0;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
	}
	
	public function getInfo() {
		$sql = "SELECT `machineFK`, `startDatetime`, `startMinute`, `status`, `ap_invested`, `minutes`, `afterglow`, `ptype` FROM `fuel_projects` WHERE `uid`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->container = $row[0];
			$this->datetime = $row[1];
			$this->minute = $row[2];
			$this->status = $row[3];
			$this->ap = $row[4];
			$this->duration = $row[5];
			$this->afterglow = $row[6];
			$this->ptype = $row[7];
		}
	}
	
	public function checkChangeable($charid, $invested) {
		$machine = new Obj($this->mysqli, $this->container);
		$machine->getBasicData();
		$contents = $machine->getContents();
		if (!$contents) return -1;//There's nothing inside
		$sql = "SELECT `change_typeFK`, `preset_in`, `secondary_in`, `preset_out`, `secondary_out`, `weight_change`, `duration`, `gen_type` FROM `change_project_fuel` JOIN `change_project_types` ON `change_typeFK`=`change_project_types`.`uid` WHERE `fuel_typeFK`=$this->ptype AND `machine`=$machine->preset";
		
		$result = $this->mysqli->query($sql);
		$changes = array();
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$changes[] = array(
					"type" => $row[0],
					"preset" => $row[1],
					"secondary" => $row[2],
					"preset2" => $row[3],
					"secondary2" => $row[4],
					"wt_change" => $row[5],
					"duration" => $row[6],
					"gen_type" => $row[7]
					);
			}
		}
		else return -2;//This can't change anything
		foreach ($contents as $content) {
			
			//to-do: this might need to be checked
			$item = new Obj($this->mysqli, $content);
			$item->getBasicData();
			foreach ($changes as $change) {
				if ($change["preset"]==$item->preset && ($change["secondary"]==$item->secondary||$change["secondary"]==-1)) {
					$info = $item->getChangeStatus();
					if ($info==-1) {
						$newid = $item->createChangeProject($change["type"], $charid);
						if ($newid) {
							$info = $item->getChangeStatus();
						}
						else return -2;
					}
					$item->updateChangeMinutes($info["uid"], $invested);
					if ($info["investedMinutes"]+$invested>=$change["duration"]) {
						$curTime = new Time($this->mysqli);
						$item->finishChangeProject($curTime->dateTime, $curTime->minute);
					}
				}
			}
		}
	}
	
	public function checkProgress($charid) {
		$this->getInfo();
		$observer = new Character($this->mysqli, $charid);
		$curTime = new Time($this->mysqli);
		$project_time = new Time($this->mysqli, $this->datetime, $this->minute);
		if ($project_time->dateTime==$curTime->dateTime&&$project_time->minute==$curTime->minute) return -2;//no progress
		else {
			$arr = $project_time->countDifference($curTime->dateTime, $curTime->minute);
			//para ($arr["minutes"] . " min " . $arr["hours"] . " h " . + $arr["days"] . " d " . + $arr["months"] . " m");
			$change_min2 = $arr["minutes"] + $arr["hours"]*60 + $arr["days"]*720 + $arr["months"]*17280 + $arr["years"]*207360;//how much time has passed
			$change_min = $change_min2-$this->duration;//how much it changed since last time
			if ($this->status==3) return -6;//ashes
			//para("It's been " . $change_min2 . " IG minutes since this fire was lit.");
			if ($this->status==2) {
				if ($change_min2>=$this->afterglow+$this->duration) {
					$this->checkChangeable($charid, $this->afterglow);//adds the afterglow period to change projects affected
					$result = $this->actuallyGoOut();
					if ($result==1) {
						return -6;//ashes
					}
					else return -5;
				}
				else {
					return -4;//embers
				}
			}
			$newduration = $this->duration+$change_min;//This will be shortened if it runs out of fuel
			$project = new FuelProjectType($this->mysqli, $this->ptype);
			$project->getInfo();
			$firewood = $project->checkFirewood($this->container, $charid);
			if ($firewood==-3) return -3;//no firewood
			$okay = false;
			
			if ($newduration<4) {
				foreach($firewood as $pos) {
					if (!$pos["on_fire"]&&$pos["flammable"]==1) {
						$tinder = new Obj($this->mysqli, $pos["uid"]);
						$tinder->ignite($charid);
						$okay = true;//new tinder was added
					}
					else if (!$pos["on_fire"]&&$pos["flammable"]>1) $okay = true;//for some reason something else is burning so it won't go out
					else if ($pos["flammable"]==1) {
						$amount = round(($pos["per_hour"]/60)*$change_min);
						//para("This should burn " . $amount . " grams");
						$tinder = new Obj($this->mysqli, $pos["uid"]);
						$tinder->getBasicData();
						if ($tinder->weight<=$amount&&$amount>0) {
							$actual_change = round($tinder->weight/$amount*$change_min);
							$this->addMinutes($actual_change);
							$tinder->deleteFromDb();
						}
						else if ($change_min>0) {
							$tinder->changeSize(-$amount, 0, $charid);
							$okay = true;
							$this->addMinutes($change_min);
							break;
						}
						else if ($change_min<=0) $okay = true;
					}
				}
				if (!$okay) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else return 1;//this is in tinder stage
			}
			if ($newduration<8) {
				$firestarter = false;
				foreach($firewood as $pos) {
					if ($pos["on_fire"]==1) $firestarter = true;
					if ($pos["on_fire"]==1&&$pos["flammable"]==1) {
						$tinder = new Obj($this->mysqli, $pos["uid"]);
						$tinder->deleteFromDb();//this purgers all the tinder that is on fire
						$pos["deleted"] = true;
					}
				}
				if (!$firestarter) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else {
					foreach($firewood as $pos) {
						if (!$pos["on_fire"]&&$pos["flammable"]<=2) {
							$kindling = new Obj($this->mysqli, $pos["uid"]);
							$kindling->ignite($charid);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$kindling->getBasicData();
							if ($kindling->weight<=$amount&&$amount>0) {
								$actual_change = round($kindling->weight/$amount*$change_min);
								$this->addMinutes($actual_change);
								$kindling->deleteFromDb();
							}
							else if ($change_min>0) {
								$kindling->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								break;
							}
							else if ($change_min<=0) $okay = true;
						}
						else if ($pos["flammable"]<=2&&!$pos["deleted"]) {
							$kindling = new Obj($this->mysqli, $pos["uid"]);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$kindling->getBasicData();
							if ($kindling->weight<=$amount&&$amount>0) {
								$actual_change = round($kindling->weight/$amount*$change_min);
								$this->addMinutes($actual_change);
								$kindling->deleteFromDb();//if all the kindling goes out before the firewood can ignite then it goes out if nothing is okay
								$change_min-=$actual_change;
							}
							else if ($change_min>0) {
								$kindling->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								break;
							}
							else if ($change_min<=0) $okay = true;
						}
					}
				}
				if (!$okay) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else return 2;//this is in kindling stage
			}
			if ($newduration<30) {
				$firestarter = false;
				foreach($firewood as $pos) {
					if ($pos["on_fire"]==1) $firestarter = true;
					if ($pos["on_fire"]==1&&$pos["flammable"]<=2) {
						$kindling = new Obj($this->mysqli, $pos["uid"]);
						$kindling->deleteFromDb();//this purgers all the tinder and kindling that is on fire
						$pos["deleted"] = true;
					}
				}
				if (!$firestarter) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else {
					foreach($firewood as $pos) {
						if (!$pos["on_fire"]&&$pos["flammable"]<=3) {
							$wood = new Obj($this->mysqli, $pos["uid"]);
							$wood->ignite($charid);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$wood->getBasicData();
							if ($wood->weight<=$amount&&$amount>0) {
								$actual_change = round($wood->weight/$amount*$change_min);
								$this->addMinutes($actual_change);
								$this->checkChangeable($charid, $actual_change);
								$wood->deleteFromDb();
								$change_min-=$actual_change;
							}
							else if ($change_min>0) {
								$wood->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								$this->checkChangeable($charid, $change_min);
								break;
							}
							else if ($change_min<=0) $okay = true;
						}
						else if ($pos["flammable"]<=3&&!$pos["deleted"]) {
							$wood = new Obj($this->mysqli, $pos["uid"]);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$wood->getBasicData();
							if ($wood->weight<=$amount&&$amount>0) {
								$actual_change = round($wood->weight/$amount*$change_min);
								$this->addMinutes($actual_change);
								$this->checkChangeable($charid, $actual_change);
								$wood->deleteFromDb();//if all the kindling goes out before the firewood can ignite then it goes out if nothing is okay
								$change_min-=$actual_change;
							}
							else if ($change_min>0) {
								$wood->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								$this->checkChangeable($charid, $change_min);
								break;
							}
							else if ($change_min<=0) $okay = true;
						}
					}
				}
				if (!$okay) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else return 3;//this is in firewood stage
			}
			if ($newduration>=30) {
				$firestarter = false;
				foreach($firewood as $pos) {
					if ($pos["on_fire"]==1) $firestarter = true;
				}
				if (!$firestarter) {
					$result = $this->goOut();
					if ($result==1) return -4;//your fire has gone out
					else return -5;//bug, your fire should've gone out but didn't
				}
				else {
					foreach($firewood as $pos) {
						if (!$pos["on_fire"]&&$pos["flammable"]<=4) {
							$wood = new Obj($this->mysqli, $pos["uid"]);
							$wood->ignite($charid);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$wood->getBasicData();
							if ($wood->weight<=$amount&&$amount>0) $wood->deleteFromDb();
							else if ($change_min>0) {
								$wood->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								$this->checkChangeable($charid, $change_min);
							}
							else if ($change_min<=0) $okay = true;
						}
						else if ($pos["flammable"]<=4) {
							$wood = new Obj($this->mysqli, $pos["uid"]);
							$amount = round(($pos["per_hour"]/60)*$change_min);
							//para("This should burn " . $amount . " grams");
							$wood->getBasicData();
							if ($wood->weight<=$amount&&$amount>0) {
								$actual_change = round($wood->weight/$amount*$change_min);
								$this->addMinutes($actual_change);
								$this->checkChangeable($charid, $actual_change);
								$wood->deleteFromDb();
							}
							else if ($change_min>0) {
								$wood->changeSize(-$amount, 0, $charid);
								$okay = true;
								$this->addMinutes($change_min);
								$this->checkChangeable($charid, $change_min);
							}
							else if ($change_min<=0) $okay = true;
						}
					}
				}
			}
			if (!$okay) {
				$result = $this->goOut();
				if ($result==1) return -4;//your fire has gone out
				else return -5;//bug, your fire should've gone out but didn't
			}
			else return 4;//this is in the burn everything stage
		}
	}
	
	public function addMinutes($minutes) {
		$sql = "UPDATE `fuel_projects` SET `minutes`=`minutes`+$minutes WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			
			$this->duration += $minutes;
			return 1;
		}
		else return -1;
	}
	
	public function goOut() {
		$project = new FuelProjectType($this->mysqli, $this->ptype);
		$project->getInfo();
		$afterglow = round($this->duration/60*$project->reserve);
		$sql = "UPDATE `fuel_projects` SET `status`=2, `afterglow`=$afterglow WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			$this->afterglow = $afterglow;
			return 1;
		}
		else return -1;
	}
	
	public function actuallyGoOut() {
		$project = new FuelProjectType($this->mysqli, $this->ptype);
		$project->getInfo();
		$sql = "UPDATE `fuel_projects` SET `status`=3 WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return 1;
		else return -1;
	}
}
