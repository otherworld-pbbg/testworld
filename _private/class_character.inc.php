<?php

include_once("class_time.inc.php");
include_once("local_map.inc.php");
include_once("class_resource.inc.php");
include_once("class_obj.inc.php");
include_once("class_animal_type.inc.php");
include_once("generic.inc.php");//abetween function
include_once("class_group.inc.php");                                                                                               
include_once("class_resource_string.inc.php");
include_once("class_position.inc.php");
include_once("class_event.inc.php");
include_once("constants.php");

class Character {
	private $mysqli;
	public $uid=0;
	public $cname="(unnamed)";
	public $bodyId=0;
	public $x=0;
	public $y=0;
	public $localx=0;
	public $localy=0;
	public $sex;
	public $building=0;
	public $born=0;
	public $age_offset=0;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($uid>0) $this->getBasicData();
	}
	
	public function create($sex, $born, $age_offset, $cname="unnamed") {
		$this->mysqli->query("INSERT INTO `chars` (`uid`, `cname`, `created`, `objectFK`, `sex`, `born`, `age_offset`) VALUES (NULL, '$cname', CURRENT_TIMESTAMP, '0', '$sex', '$born', '$age_offset')");
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->uid = $result;
			$this->cname = $cname;
			$this->sex = $sex;
			$this->born = $born;
			$this->age_offset = $age_offset;
			return $result;//success
		}
		else return -1;//something went wrong
	}
	
	public function getBasicData() {
		$sql1 = "SELECT `objectFK`, `global_x`, `global_y`, `local_x`, `local_y`, `cname`, `parent`, `sex`, `born`, `age_offset` FROM `chars` JOIN `objects` ON `objectFK`=`objects`.`uid` WHERE `chars`.`uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql1);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->bodyId = $row[0];
			$this->x=$row[1];
			$this->y=$row[2];
			$this->localx=$row[3];
			$this->localy=$row[4];
			if ($row[5]=='') $this->cname='(unnamed)';
			else $this->cname=$row[5];
			$this->building = $row[6];
			$this->sex = $row[7];
			$this->born = $row[8];
			$this->age_offset = $row[9];
			return $this->bodyId;
		}
		else return -1;
	}
	
	public function createBody($ageOffset, $x, $y, $datetime, $minute, $lx=0, $ly=0, $building=0) {
		//preset 19 - baby, 26 - toddler, 28 - child, 25 - preteen, 23 - adolescent, 21-adult
		$calcArr = calculateBody($ageOffset);
		$preset = $calcArr->preset;
		$weight = $calcArr->weight;
		
		$sql1 = "SELECT `objectFK` FROM `chars` WHERE `uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql1);//check if it already has a body
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			if ($row[0]>0)
			{
				$this->bodyId = $row[0];
				$this->x=$x;
				$this->y=$y;
				$this->localx=$lx;
				$this->localy=$ly;
				$this->building = $building;
				return $this->bodyId;
			}
		}
		$body = new Obj($this->mysqli, 0);
		$result = $body->create($preset, 2, $building, "Human body created by function", $x, $y, $lx, $ly, 0, 1, $weight, $datetime, $minute);
		if ($result) {
			$this->bodyId = $result;
			$this->x=$x;
			$this->y=$y;
			$this->localx=$lx;
			$this->localy=$ly;
			$this->building = $building;
			
			$sql = "UPDATE `chars` SET `objectFK`=$this->bodyId WHERE `uid`=$this->uid";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				return -2;//could not link body to spirit
			}
			
			$this->logLocation(1);//born
			return $result;//success
		}
		else return -1;//something went wrong
	}
	
	public function calculateBody($age){
		if ($age == 0) {
			$preset = 19;
			$weight = 3000;
		}
		else if ($age < 4) {
			$preset = 26;
			$weight = round(($age-1)*2000+10000);
		}
		else if ($age < 10) {
			$preset = 28;
			$weight = round(($age-3)*2200+12800);
		}
		else if ($age < 13){
			$preset = 25;
			$weight = round(($age-10)*5000+32000);
		}
		else if ($age < 20) {
			$preset = 23;//13 14 15 16 17 18 19 - 46, 48, 52, 53, 54, 57, 57
			$weight = round(($age-12)*1600+44450);
		}
		else {
			$preset = 21;
			$weight = 60000;
		}
		$resArr = array ("preset" => $preset, "weight" => $weight);
		return resArr;
	}
	
	public function advanceAge(){
		$curAge = getAge();
		$bo = new Obj($mysqli, $bodyID);
		$calcArr = calculateBody($curAge);
		$isChanged=false;
		if($bo->weight != $calcArr->weight){ 
			$bo->changeSize($calcArr->weight-$bo->weight,0);
			$isChanged=true;
		}
		if($bo->preset != $calcArr->preset){
			$bo->changeType($calcArr->preset, $bo->secondary, $bo->type);
			$isChanged=true;
		}
		$bo->calculateBlood(); //Update blood count to match weight
		
		return $isChanged;
	}
	
	public function getLocationDB() {
		$sql = "SELECT `global_x`, `global_y` FROM `objects` WHERE `uid`=$this->bodyId LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->x=$row[0];
			$this->y=$row[1];
			
			return $result;
		}
		else return -1;
	}
	public function getLocalDB() {
		$sql = "SELECT `local_x`, `local_y` FROM `objects` WHERE `uid`=$this->bodyId LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->localx=$row[0];
			$this->localy=$row[1];
			
			return $result;
		}
		else return -1;
	}
	
	public function setListener($user, $role) {
		//role = 1 - player, 2 - watcher, 3 - invisible watcher
		$sql = "INSERT INTO `char_players` (`uid`, `charFK`, `userFK`, `role`, `initiated`) VALUES (NULL, $this->uid, $user, $role, CURRENT_TIMESTAMP)";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) return $result;
		else return -1;
	}
	
	public function removeListener($user, $role) {
		//role = 1 - player, 2 - watcher, 3 - invisible watcher
		$sql = "UPDATE `char_players` SET `terminated`=CURRENT_TIMESTAMP WHERE `charFK`=$this->uid AND `userFK`=$user AND `role`=$role LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return 1;
		else return -1;
	}
	
	public function checkPermission($user) {
		$sql = "SELECT `role` FROM `char_players` WHERE `userFK`=$user AND `charFK`=$this->uid AND `terminated` IS NULL LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			return $row[0];
		}
		else return -1;
	}
	
	public function getScenesNearby($privacy) {
		//This doesn't support buildings yet
		$scenes = array();
		$pos = $this->getPosition();
		$minX = $pos->lx-100;
		$maxX = $pos->lx+100;
		$minY = $pos->ly-100;
		$maxY = $pos->ly+100;
		$sql = "SELECT `sceneID` FROM `scenes` WHERE `privacy`=$privacy AND `globalX`=$pos->x AND `globalY`=$pos->y AND `localX`>$minX AND `localX`<$maxX AND `localY`>$minY AND `localY`<$maxY AND `indoors`=0 ORDER BY `sceneID` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$scenes[] = $row[0];
			}
		}
		return $scenes;
	}
	
	public function getParticipationTimes($sceneid) {
		$times = array();
		$sql = "SELECT `rowID`, `joinTime`, `joinMinute`, `leftTime`, `leftMinute`, `firstReadFK`, `lastReadFK` FROM `scene_chars` WHERE `charFK`=$this->uid AND `sceneFK`=$sceneid ORDER BY `joinTime` ASC, `joinMinute` ASC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$times[] = array(
					"rowid" => $row[0],
					"joint" => $row[1],
					"joinm" => $row[2],
					"leftt" => $row[3],
					"leftm" => $row[4],
					"firstread" => $row[5],
					"lastread" => $row[6]
					);
			}
			return $times;
		}
		else return -1;
	}
	
	public function travel($direction, $ap) {
		//this assumes that the ap has been calculated elsewhere and includes the multiplier
		$targetX = $this->x;
		$targetY = $this->y;
		
		if ($direction == "n" || $direction == "ne" || $direction == "nw") {
			$targetY-=4;
			if ($targetY<-5000) $targetY=4996;
		}
		if ($direction == "s" || $direction == "se" || $direction == "sw") {
			$targetY+=4;
			if ($targetY>4996) $targetY=-5000;
		}
		if ($direction == "e" || $direction == "ne" || $direction == "se") {
			$targetX+=4;
			if ($targetX>19996) $targetX=0;
		}
		if ($direction == "w" || $direction == "nw" || $direction == "sw") {
			$targetX-=4;
			if ($targetX<0) $targetX=19996;
		}
		
		$tg = $this->getTravelGroup();
		if ($tg>0) {
			$to = new Obj($this->mysqli, $tg);
			$pas = $to->getPassengers();
			if (is_array($pas)) {
				$res = $to->compareAPgroup($ap);
				//echo "res:" . $res . "<br>";
				if ($res<0) return -4;//This shouldn't happen
				if ($res>0) return -3;//some group members don't have enough ap
				$all = $to->getPassengersInc();//This includes the owner
				foreach ($all as $t) {
					//echo "t:" . $t . "<br>";
					$tc = new Character($this->mysqli, $t);
					$tc->spendAP($ap);
				}
			}
			else {
				//otherwise it's just the owner
				$res = $this->spendAP($ap);
				if ($res<0) return -2;//not enough AP
			}
		}
		else {
			$res = $this->spendAP($ap);
			if ($res<0) return -2;//not enough AP
		}
		
		$sql2 = "UPDATE `objects` SET `global_x`=$targetX, `global_y`=$targetY WHERE `uid`=$this->bodyId LIMIT 1";
		$this->mysqli->query($sql2);
		if ($this->mysqli->affected_rows==0) return -1;
		else {
			$this->logLocation(3);//leave
			if ($tg>0) {
				if (is_array($pas)) {
					foreach ($pas as $b) {
						$bo = new Obj($this->mysqli, $b);
						$p = $bo->getCharid();
						$tc = new Character($this->mysqli, $p);
						$tc->logLocation(3);
					}
				}
			}
			$this->x=$targetX;
			$this->y=$targetY;
			
			$this->updateCharLocTime($targetX, $targetY, $this->localx, $this->localy, 0, 3, $ap);
			
			$this->logLocation(2);//arrive
			if ($tg>0) {
				if (is_array($pas)) {
					foreach ($pas as $b) {
						$bo = new Obj($this->mysqli, $b);
						$p = $bo->getCharid();
						$tc = new Character($this->mysqli, $p);
						$tc->updateCharLocTime($targetX, $targetY, $this->localx, $this->localy, 0, 3, $ap);
						$tc->logLocation(2);
					}
				}
			}
			
			return 1;
		}
	}
	
	public function spendAP($ap) {
		$sql = "UPDATE `char_ap` SET `ap`=`ap`-$ap WHERE `charFK`=$this->uid and `ap`>=$ap LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			return -1;
		}
		else return 1;
	}
	
	public function explore($ap) {
		//this assumes that the ap has been calculated elsewhere
		$res = $this->spendAP($ap);
		if ($res==-1) return -3;
		else {
			$targetLocation = new LocalMap($this->mysqli, $this->x, $this->y);
			$check = $targetLocation->loadcreate();
			return $check;
		}
	}
	
	public function updateCharLocTime($gx, $gy, $lx, $ly, $indoors, $nextStatus, $ap) {
		$sql3 = false;
		$lastTime = $this->getInternalTime();
		if ($lastTime) {
			$endTime = new Time($this->mysqli);
			
			if ($lastTime[3]==$nextStatus) {
				if ($nextStatus==5) $sql2 = "UPDATE `charloctime` SET `endDateTime`=$endTime->dateTime, `endMinute`=$endTime->minute, `ap`=`ap`-$ap, `status`=$nextStatus, `globalX`=$gx, `globalY`=$gy, `localX`=$lx, `localY`=$ly, `indoors`=$indoors WHERE `rowID`=" . $lastTime[0] . " LIMIT 1";
				else $sql2 = "UPDATE `charloctime` SET `endDateTime`=$endTime->dateTime, `endMinute`=$endTime->minute, `ap`=`ap`+$ap, `status`=$nextStatus, `globalX`=$gx, `globalY`=$gy, `localX`=$lx, `localY`=$ly, `indoors`=$indoors WHERE `rowID`=" . $lastTime[0] . " LIMIT 1";
			}
			else $sql2 = "UPDATE `charloctime` SET `endDateTime`=$endTime->dateTime, `endMinute`=$endTime->minute, `globalX`=$gx, `globalY`=$gy, `localX`=$lx, `localY`=$ly, `indoors`=$indoors WHERE `rowID`=" . $lastTime[0] . " LIMIT 1";
			$this->mysqli->query($sql2);
			
			if ($lastTime[3]!=$nextStatus) {
				if ($nextStatus==5) $sql3 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$this->uid', '$gx', '$gy', '$lx', '$ly', '$indoors', '".$lastTime[1]."', '".$lastTime[2]."', '$endTime->dateTime', '$endTime->minute', '$nextStatus', '-$ap')";
				else $sql3 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$this->uid', '$gx', '$gy', '$lx', '$ly', '$indoors', '".$lastTime[1]."', '".$lastTime[2]."', '$endTime->dateTime', '$endTime->minute', '$nextStatus', '$ap')";
			}
			else return $lastTime[0];
		}
		else {
			$curTime = new Time($this->mysqli);
			
			$endTime = new Time($this->mysqli);
			
			if ($nextStatus==5) $sql3 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$this->uid', '$gx', '$gy', '$lx', '$ly', '$indoors', '$curTime->dateTime', '$curTime->minute', '$endTime->dateTime', '$endTime->minute', '$nextStatus', '-$ap')";
			else $sql3 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$this->uid', '$gx', '$gy', '$lx', '$ly', '$indoors', '$curTime->dateTime', '$curTime->minute', '$endTime->dateTime', '$endTime->minute', '$nextStatus', '$ap')";
		}
		if ($sql3)
		{
			$this->mysqli->query($sql3);
			$res2 = $this->mysqli->insert_id;
			
			if ($res2) return $res2;
			else return -1;
		}
	}
	
	public function getTirednessLevel($ap) {
		if ($ap>600) $str = "You are brimming with energy.";
		else if ($ap>480) $str = "You feel energetic.";
		else if ($ap>360) $str = "You feel somewhat energetic.";
		else if ($ap>240) $str = "You feel a little tired.";
		else if ($ap>60) $str = "You feel tired. You will have to stop to rest soon.";
		else if ($ap>0) $str = "You feel very tired. You will have to stop to rest soon.";
		else $str = "You feel totally drained. You need to wait until your powers return.";
		return $str;
	}
	
	public function getInternalTime() {
		$sql = "SELECT `rowID`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status` FROM `charloctime` WHERE `charFK`=$this->uid ORDER BY `rowID` DESC LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			if ($row[3]==0) return array($row[0], $row[1], $row[2], $row[5], 1);//current time is the start time
			else return array($row[0], $row[3], $row[4], $row[5], 2);//current time is the end time
		}
		else {
			$gameTime = new Time($this->mysqli);
			
			$sql2 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$this->uid', '$this->x', '$this->y', '$this->localx', '$this->localy', '$this->building', '$gameTime->dateTime', '$gameTime->minute', '$gameTime->dateTime', '$gameTime->minute', '1', '0')";
			$this->mysqli->query($sql2);
			$result = $this->mysqli->insert_id;
			if ($result) return array($result, $gameTime->dateTime, $gameTime->minute, 1, 2);
			else return false;
		}
	}
	
	public function logLocation($event_type) {
		$pos = $this->getPosition();
		$gameTime = new Time($this->mysqli);
		//this currently doesn't work with buildings
		$sql = "INSERT INTO `loc_log` (`uid`, `dateTime`, `minute`, `x`, `y`, `building`, `event_type`, `charFK`) VALUES (NULL, '$gameTime->dateTime', '$gameTime->minute', '$pos->x', '$pos->y', '0', '$event_type', '$this->uid')";
		$this->mysqli->query($sql);
		
		$result = $this->mysqli->insert_id;
		if ($result) return $result;
		else return false;
	}
	
	public function rest($hours, $minutes) {
		$ap = 147*$hours;
		$ap+=round($minutes*147/60);
				
		$oldAP = $this->getAP();
		if ($oldAP+$ap>1000) $ap = max(0, 1000-$oldAP);
		
		$pos = $this->getPosition();
		
		$this->updateCharLocTime($pos->x, $pos->y, $pos->lx, $pos->ly, 0, 5, $ap);//This doesn't take building into account
		
		if ($oldAP==-1) {
			$sql4 = "INSERT INTO `char_ap` (`rowID`, `charFK`, `ap`) VALUES (NULL, '$this->uid', '$ap')";
			$this->mysqli->query($sql4);
		}
		else if ($ap>0) {
			$sql4 = "UPDATE `char_ap` SET `ap`=`ap`+$ap WHERE `charFK`=$this->uid LIMIT 1";
			$this->mysqli->query($sql4);
		}
		return $ap;
	}
	
	public function moveLocal($x, $y) {
		if (ibetween($x, 0, 999)&&ibetween($y, 0, 999)) {
			$sql = "UPDATE `objects` SET `local_x`=$x, `local_y`=$y WHERE `uid`=$this->bodyId LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				return -1;
			}
			else {
				$this->localx = $x;
				$this->localy = $y;
				return 1;//success
			}
		}
		else return -1;
	}
	
	public function getAge() {
		$bornTime = new Time ($this->mysqli, $this->born, 0);
		$curTime = new Time ($this->mysqli);
		$diff = $bornTime->countDifference($curTime->dateTime, $curTime->minute);
		return array($diff["years"]+$this->age_offset, $diff["months"]);
	}
	
	public function getPronoun() {
		if ($this->sex==1) $str = "his";
		if ($this->sex==2) $str = "her";
		if ($this->sex==3) $str = "their";
		
		return $str;
	}
	
	public function getAgeSex() {
		$diff = $this->getAge();
		$age = $diff[0];
		$str = "";
		if ($age==0) {
			if ($this->sex==1) $str = "a baby boy";
			if ($this->sex==2) $str = "a baby girl";
			if ($this->sex==3) $str = "a baby neuter";
		}
		else if ($age<4) {
			if ($this->sex==1) $str = "a boy toddler";
			if ($this->sex==2) $str = "a girl toddler";
			if ($this->sex==3) $str = "a neuter toddler";
		}
		else if ($age<10) {
			if ($this->sex==1) $str = "a little boy";
			if ($this->sex==2) $str = "a little girl";
			if ($this->sex==3) $str = "a little neuter";
		}
		else if ($age<14) {
			if ($this->sex==1) $str = "an older boy";
			if ($this->sex==2) $str = "an older girl";
			if ($this->sex==3) $str = "an older neuter";
		}
		else if ($age<20) {
			if ($this->sex==1) $str = "a young man";
			if ($this->sex==2) $str = "a young woman";
			if ($this->sex==3) $str = "a young neuter";
		}
		else if ($age<30) {
			if ($this->sex==1) $str = "a grown man";
			if ($this->sex==2) $str = "a grown woman";
			if ($this->sex==3) $str = "a grown neuter";
		}
		else if ($age<40) {
			if ($this->sex==1) $str = "a middle-aged man";
			if ($this->sex==2) $str = "a middle-aged woman";
			if ($this->sex==3) $str = "a middle-aged neuter";
		}
		else if ($age<55) {
			if ($this->sex==1) $str = "a mature man";
			if ($this->sex==2) $str = "a mature woman";
			if ($this->sex==3) $str = "a mature neuter";
		}
		else if ($age<70) {
			if ($this->sex==1) $str = "a senior man";
			if ($this->sex==2) $str = "a senior woman";
			if ($this->sex==3) $str = "a senior neuter";
		}
		else {
			if ($this->sex==1) $str = "an old man";
			if ($this->sex==2) $str = "an old woman";
			if ($this->sex==3) $str = "an old neuter";
		}
		return $str;
	}
	
	public function moveSpiral($step, $startlx, $startly) {
		$change = countSpiral($step);
		$x = min(999, max(0, $startlx+($change["xchange"]*10)));
		$y = min(999, max(0, $startly+($change["ychange"]*10)));
		if ($x == $this->localx && $y == $this->localy) return -2;//Probably due to hitting the edge of the map, you would end up in the same position you already were
		return $this->moveLocal($x, $y);
	}
	
	public function searchDepositsUnexplored($deposits, $hidden, $maxTime) {
		$apcheck = $this->checkAP($maxTime*5);
		if ($apcheck>-1) {
			return -5;
		}
		else if ($apcheck == -1) {
			return -6;
		}
		
		$startlx = $this->localx;
		$startly = $this->localy;
		$step = 1;
		
		$totalPoints = 0;
		$hiddenArray = array();
		if ($hidden) {
			$sql = "SELECT `local_res`.`uid`, `amount`, `resFK` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE " . $this->getCoordsForSQL2() . " AND `hidden`=1 AND `amount`>0 ORDER BY `maxSize` * `amount` DESC";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$totalPoints += $row[1];
					$hiddenArray[] = array (
						"rowId" => $row[0],
						"minPoint" => $totalPoints-$row[1],
						"maxPoint" => $totalPoints,
						"resFK" => $row[2]
						);
				}
			}//otherwise there are no hidden resources
			$found = -1;
			$spentTime = 0;
			if ($hiddenArray) {
				for ($j=0;$j<$maxTime;$j++) {
					$this->moveSpiral($step, $startlx, $startly);
					$step++;
					$random = rand(0, $totalPoints*9+10);//the size of the +10 will be emphasized with rare resources
					for ($i=0;$i<count($hiddenArray);$i++) {
						if ($random>=$hiddenArray[$i]["minPoint"]&&$random<$hiddenArray[$i]["maxPoint"]) {
							//Check here if resource is a tree or aquatic and if spot qualifies
							$found = $i;
						}
					}
					if ($found>-1) {
						$spentTime = $j+1;
						break;
					}
				}
			}
			if ($found>-1) {
				$check = $this->generateDeposit($hiddenArray[$found]["rowId"]);
				if ($check<0) echo "Error code " . -$check . ", please inform developer.";
				else {
					$check2 = $this->spendAP($spentTime*5);
					if ($check2==-1) return -4;
					$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $spentTime*5);
				}
				return $check;
			}
			//If no hidden resource found, continue to search for visible resources.
		}
		$in = "";
		$totalPoints2 = 0;
		if ($deposits) {
			for ($i=0;$i<count($deposits);$i++) {
				if ($in != "") $in .= ", ";
				$in .= $deposits[$i];
			}
			$sql2 = "SELECT `local_res`.`uid`, `amount`, `resFK` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE " . $this->getCoordsForSQL2() . " AND `local_res`.`uid` IN ($in) AND `amount`>0 ORDER BY `maxSize` * `amount` DESC";
			$res = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$totalPoints2 += $row[1];
					$allArray[] = array (
						"rowId" => $row[0],
						"minPoint" => $totalPoints2-$row[1],
						"maxPoint" => $totalPoints2,
						"resFK" => $row[2]
						);
				}
			}
			$found = -1;
			$spentTime = 0;
			if ($allArray) {
				for ($j=0;$j<$maxTime;$j++) {
					$this->moveSpiral($step, $startlx, $startly);
					$step++;
					$range_expand = max(0, (6-$j));//I made this smaller so that it doesn't make finding specific resources too difficult
					$random = rand(0, $totalPoints2+$range_expand);
					for ($i=0;$i<count($allArray);$i++) {
						if ($random>=$allArray[$i]["minPoint"]&&$random<$allArray[$i]["maxPoint"]) {
							$found = $i;
						}
					}
					if ($found>-1) {
						$spentTime = $j+1;
						break;
					}
				}
			}
			if ($found>-1) {
				$check = $this->generateDeposit($allArray[$found]["rowId"]);
				if ($check<0) echo "Error code " . -$check . ", please inform developer.";
				else {
					$check2 = $this->spendAP($spentTime*5);
					if ($check2==-1) return -4;
					
					$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $spentTime*5);//this is complicated enough so if the log isn't updated, it won't be reported
				}
				return $check;
			}
			else {
				$this->spendAP($maxTime*5);
				$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $maxTime*5);
				return 0;//Nothing was found
			}
		}
		else {
			$this->spendAP($maxTime*5);
			$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $maxTime*5);
			return 0;//No visible resources were selected.
		}
	}
	
	public function searchDeposits($deposits, $hidden, $maxTime) {
		$apcheck = $this->checkAP($maxTime*5);
		if ($apcheck>-1) {
			return -5;
		}
		else if ($apcheck == -1) {
			return -6;
		}
		
		$startlx = $this->localx;
		$startly = $this->localy;
		$step = 1;
		
		$localMap = new LocalMap ($this->mysqli, $this->x, $this->y);
		if ($localMap->checkIfExists()<0) {
			$res = $this->searchDepositsUnexplored($deposits, $hidden, $maxTime);
			return $res;
		}
		$localMap->loadcreate();
		
		$totalPoints = 0;
		$hiddenArray = array();
		if ($hidden) {
			$sql = "SELECT `local_res`.`uid`, `amount`, `resFK` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE " . $this->getCoordsForSQL2() . " AND `hidden`=1 AND `amount`>0 ORDER BY `maxSize` * `amount` DESC";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$totalPoints += $row[1];
					$hiddenArray[] = array (
						"rowId" => $row[0],
						"minPoint" => $totalPoints-$row[1],
						"maxPoint" => $totalPoints,
						"resFK" => $row[2]
						);
				}
			}//otherwise there are no hidden resources
			$found = -1;
			$spentTime = 0;
			if ($hiddenArray) {
				for ($j=0;$j<$maxTime;$j++) {
					$this->moveSpiral($step, $startlx, $startly);
					$step++;
					$random = rand(0, $totalPoints*9+10);//the size of the +10 will be emphasized with rare resources
					for ($i=0;$i<count($hiddenArray);$i++) {
						if ($random>=$hiddenArray[$i]["minPoint"]&&$random<$hiddenArray[$i]["maxPoint"]) {
							//Check here if resource is a tree or aquatic and if spot qualifies
							$vege = $localMap->readSpecific(floor($this->localx/10), floor($this->localy/10), 2);
							$row = $localMap->readSpecific(floor($this->localx/10), floor($this->localy/10), 3);
							$soiltype = $localMap->getSoil2(floor($this->localx/10), floor($this->localy/10));
							$waterstatus = $localMap->analyzeWaterLevel($row["water"]);
							$treeCount = $localMap->countTrees($vege["trees"]);
							$r1 = new Resource($this->mysqli, $hiddenArray[$i]["resFK"]);
							$string = $r1->getStrings();
							$s1 = new ResourceString($this->mysqli, $string);
							$environment = $s1->getEnvironment();
							$okay = true;
							
							if ($environment["bush"]) {
								if ($vege["bush"]<=60) $okay = false;
							}
							if ($environment["wood"]) {
								if ($vege["trees"]<=0) $okay = false;
							}
							if ($environment["rock"]) {
								if ($row["rocks"]<=60) $okay = false;
							}
							if ($environment["clay"]) {
								if ($soiltype<3||$soiltype==4||$soiltype==5||$soiltype==14||$soiltype==24) $okay = false;
							}
							if ($environment["water"]) {
								if ($waterstatus["level"]<=5) $okay = false;
							}
							else {
								if ($waterstatus["level"]>5) $okay = false;//non-aquatic resources can't be found in water
							}
							
							if ($okay) $found = $i;
						}
					}
					if ($found>-1) {
						$spentTime = $j+1;
						break;
					}
				}
			}
			if ($found>-1) {
				$check = $this->generateDeposit($hiddenArray[$found]["rowId"]);
				if ($check<0) echo "Error code " . -$check . ", please inform developer.";
				else {
					$check2 = $this->spendAP($spentTime*5);
					if ($check2==-1) return -4;
					$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $spentTime*5);
				}
				return $check;
			}
			//If no hidden resource found, continue to search for visible resources.
		}
		$in = "";
		$totalPoints2 = 0;
		if ($deposits) {
			for ($i=0;$i<count($deposits);$i++) {
				if ($in != "") $in .= ", ";
				$in .= $deposits[$i];
			}
			$sql2 = "SELECT `local_res`.`uid`, `amount`, `resFK` FROM `local_res` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE " . $this->getCoordsForSQL2() . " AND `local_res`.`uid` IN ($in) AND `amount`>0 ORDER BY `maxSize` * `amount` DESC";
			$res = $this->mysqli->query($sql2);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$totalPoints2 += $row[1];
					$allArray[] = array (
						"rowId" => $row[0],
						"minPoint" => $totalPoints2-$row[1],
						"maxPoint" => $totalPoints2,
						"resFK" => $row[2]
						);
				}
			}
			$found = -1;
			$spentTime = 0;
			if ($allArray) {
				for ($j=0;$j<$maxTime;$j++) {
					$this->moveSpiral($step, $startlx, $startly);
					$step++;
					$range_expand = max(0, (6-$j));//I made this smaller so that it doesn't make finding specific resources too difficult
					$random = rand(0, $totalPoints2+$range_expand);
					for ($i=0;$i<count($allArray);$i++) {
						if ($random>=$allArray[$i]["minPoint"]&&$random<$allArray[$i]["maxPoint"]) {
							//Check here if resource is a tree or aquatic and if spot qualifies
							$vege = $localMap->readSpecific(floor($this->localx/10), floor($this->localy/10), 2);
							$row = $localMap->readSpecific(floor($this->localx/10), floor($this->localy/10), 3);
							$soiltype = $localMap->getSoil2(floor($this->localx/10), floor($this->localy/10));
							$waterstatus = $localMap->analyzeWaterLevel($row["water"]);
							$treeCount = $localMap->countTrees($vege["trees"]);
							$r1 = new Resource($this->mysqli, $allArray[$i]["resFK"]);
							$string = $r1->getStrings();
							$s1 = new ResourceString($this->mysqli, $string);
							$environment = $s1->getEnvironment();
							$okay = true;
							
							if ($environment["bush"]) {
								if ($vege["bush"]<=60) $okay = false;
							}
							if ($environment["wood"]) {
								if ($vege["trees"]<=0) $okay = false;
							}
							if ($environment["rock"]) {
								if ($row["rocks"]<=60) $okay = false;
							}
							if ($environment["clay"]) {
								if ($soiltype<3||$soiltype==4||$soiltype==5||$soiltype==14||$soiltype==24) $okay = false;
							}
							if ($environment["water"]) {
								if ($waterstatus["level"]<=5) $okay = false;
							}
							else {
								if ($waterstatus["level"]>5) $okay = false;//non-aquatic resources can't be found in water
							}
							
							if ($okay) $found = $i;
						}
					}
					if ($found>-1) {
						$spentTime = $j+1;
						break;
					}
				}
			}
			if ($found>-1) {
				$check = $this->generateDeposit($allArray[$found]["rowId"]);
				if ($check<0) echo "Error code " . -$check . ", please inform developer.";
				else {
					$check2 = $this->spendAP($spentTime*5);
					if ($check2==-1) return -4;
					
					$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $spentTime*5);//this is complicated enough so if the log isn't updated, it won't be reported
				}
				return $check;
			}
			else {
				$this->spendAP($maxTime*5);
				$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $maxTime*5);
				return 0;//Nothing was found
			}
		}
		else {
			$this->spendAP($maxTime*5);
			$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $maxTime*5);
			return 0;//No visible resources were selected.
		}
	}
	
	function generateDeposit($sourceId) {
		$sql = "SELECT `resFK`, `amount`, `minSize`, `maxSize` FROM `local_res` WHERE " . $this->getCoordsForSQL2() . " AND `uid`=$sourceId LIMIT 1";
		$sql2 = "";
		$sql3 = "";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$resource = new Resource($this->mysqli, $row[0]);
			$rst = new ResourceString($this->mysqli, $resource->getStrings());
			$presets = $rst->getMatchingPresets();
			
			if (empty($presets)) return -6;//This resource has no harvestable types
			
			$depositSize = rand($row[2],$row[3]);
			$sql2 = "INSERT INTO `memorized_deposits` (`uid`, `resFK`, `charFK`, `x`, `y`, `lx`, `ly`) VALUES (NULL, '". $row[0] . "', '$this->uid', '$this->x', '$this->y', '$this->localx', '$this->localy')";
			$sql3 = "UPDATE `local_res` SET `amount`=`amount`-1 WHERE `uid`=$sourceId";
			
		}
		else return -1;
		
		$this->mysqli->query($sql2);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->mysqli->query($sql3);
			if ($this->mysqli->affected_rows==0) return -2;//update failed
			else {
				$instr = "";
				foreach ($presets as $pres) {
					$d = round($depositSize * $this->getSubtypeMultiplier($pres));
					if ($instr != "") $instr .= ", ";
					$instr .= "($result, $pres, $d, 0)";
				}
				
				$instr = "INSERT INTO `sub_deposits` (`depositFK`, `presetFK`, `size`, `gathered`) VALUES " . $instr;
				$this->mysqli->query($instr);
				$result2 = $this->mysqli->insert_id;
				if ($result2) return $result;//returns the main deposit
				else return -5;//Generating sub types failed
			}
		}
		else return -3;//insert failed
	}
	
	function getSubtypeMultiplier($preset) {
		$multipliers = array(
			20 => 1,
			24 => 0.1,
			189 => 0.2,
			190 => 0.2,
			192 => 20,
			203 => 1,
			204 => 4,
			206 => 2,
			364 => 2,
			365 => 5,
			366 => 2,
			438 => 0.5,
			513 => 0.2,
			519 => 2.5,
			520 => 2,
			521 => 4,
			522 => 0.5,
			523 => 2,
			524 => 2,
			526 => 5,
			525 => 2,
			528 => 4
			);
		
		if (array_key_exists($preset, $multipliers)) return $multipliers[$preset];
		return -1;
	}
	
	function loadMemorized($id) {
		$sql = "SELECT `name`, `resFK`, `lx`, `ly` FROM `memorized_deposits` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `memorized_deposits`.`uid`=$id AND `charFK`=$this->uid AND " . $this->getCoordsForSQL2() . " LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$retArr = array(
				"name" => $row[0],
				"res" => $row[1],
				"lx" => $row[2],
				"ly" => $row[3]
				);
			return $retArr;
		}
		else return -1;
	}
	
	function loadMemorizedSub($id, $resource) {
		$retArr = array();
		$pos = $this->getPosition();
		$sql = "SELECT `presetFK`, `size`, `gathered`, `uid` FROM `sub_deposits` WHERE `depositFK`=$id";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$ob = new Resource($this->mysqli, $resource, $row[0]);
				$multi = $ob->getMultiplier($pos->y);
				
				$retArr[] = array(
					"preset" => $row[0],
					"mass" => round($row[1]*$multi),
					"harvested" => $row[2],
					"uid" => $row[3],
					"multi" => $multi
					);
			}
			return $retArr;
		}
		else return -1;
	}
	
	function checkAP($ap) {
		$sql = "SELECT `ap` FROM `char_ap` WHERE `charFK`=$this->uid LIMIT 1";
		
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			if ($row[0]>=$ap) return -2;//in this case this is a good thing
			else return $row[0];//You only have this much
		}
		else {
			$sql2 = "INSERT INTO `char_ap` (`charFK`, `ap`) VALUES ($this->uid, 300)";
			$this->mysqli->query($sql2);
			$res2 = $this->mysqli->insert_id;
			if ($res2) {
				if ($ap<=300) return -2;
				else return 300;
			}
			else return -1;
		}
	}
	
	function getAP() {
		$sql = "SELECT `ap` FROM `char_ap` WHERE `charFK`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else {
			$sql2 = "INSERT INTO `char_ap` (`charFK`, `ap`) VALUES ($this->uid, 300)";
			$this->mysqli->query($sql2);
			$res2 = $this->mysqli->insert_id;
			if ($res2) return 300;
			else return -1;
		}
	}
	
	function getMemorizedList($locLimit) {
		$pos = $this->getPosition();
		$retArr = array();
		if ($locLimit) {
			$sql = "SELECT `name`, `memorized_deposits`.`uid` FROM `memorized_deposits` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `charFK`=$this->uid AND " . $this->getCoordsForSQL2() . " ORDER BY `memorized_deposits`.`uid` DESC";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res))
				{
					$retArr[] = array(
						"name" => $row[0],
						"uid" => $row[1]
						);
				}
				return $retArr;
			}
			else return -1;
		}
		else {
			$sql = "SELECT `name`, `memorized_deposits`.`uid`, `x`, `y` FROM `memorized_deposits` JOIN `res_subtypes` ON `resFK`=`res_subtypes`.`uid` WHERE `charFK`=$this->uid AND (`x`<>$pos->x OR `y`<>$pos->y) ORDER BY `memorized_deposits`.`uid` DESC";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res))
				{
					$retArr[] = array(
						"name" => $row[0],
						"uid" => $row[1],
						"x" => $row[2],
						"y" => $row[3]
						);
				}
				return $retArr;
			}
			else return -2;
		}
	}
	
	function forgetMemorized($uid) {
		$needsReadded = false;
		$sqls = "SELECT `resFK`, `x`, `y` FROM `memorized_deposits` WHERE  `uid`='$uid' AND `charFK`='$this->uid' LIMIT 1";
		$res = $this->mysqli->query($sqls);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
		}
		else return -1;
		$sql = "DELETE FROM `memorized_deposits` WHERE `uid`='$uid' AND `charFK`='$this->uid' LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) return -1;
		else {
			return 1;
		}
	}
	
	function getLocationName($x, $y) {
		$sql = "SELECT `uid`, `name` FROM `loc_naming` WHERE `namerFK`='$this->uid' AND `x`=$x AND `y`=$y LIMIT 1";//this is the dynamic name, there are no static names
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$retArr = array(
				"uid" => $row[0],
				"name" => $row[1]
				);
			if ($retArr["name"]=="") $retArr["name"] = "(unnamed location)";
		}
		else {
			$retArr = array(
				"uid" => -1,
				"name" => "(unnamed location)"
				);
		}
		return $retArr;
	}
	
	function updateLocName($x, $y, $name) {
		$sql = "UPDATE `loc_naming` SET `name`='$name' WHERE `namerFK`='$this->uid' AND `x`=$x AND `y`=$y LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			$sql2 = "INSERT INTO `loc_naming` (`uid`, `namerFK`, `x`, `y`, `name`) VALUES (NULL, '$this->uid', '$x', '$y', '$name')";
			$this->mysqli->query($sql2);
			$result = $this->mysqli->insert_id;
			if ($result) return $result;
			else return -1;
		}
	}
	
	function pruneLocMemory() {
		$sql = "SELECT count(`uid`) FROM `memorized_deposits` WHERE `charFK`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			if ($row[0]>100) $limit=$row[0]-100;
			else return 0;
		}
		else return 0;
		//to do: This should return the resources to the pool
		$sql2 = "DELETE FROM `memorized_deposits` WHERE `charFK`=$this->uid ORDER BY `uid` ASC LIMIT $limit";
		$this->mysqli->query($sql2);
		if ($this->mysqli->affected_rows==0) return -1;
		else return $limit;
	}
	
	function getGatheringSpeed($resource) {
		//in the future this will be affected by skills and tools
		$sql = "SELECT `gathered` FROM `res_subtypes` WHERE `uid`=$resource LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return 0;
	}
	
	function gather($deposit, $deposit2, $ap) {
		$apcheck = $this->checkAP($ap);
		if ($apcheck == -2) {
			$sql1 = "SELECT `resFK`, `sub_deposits`.`size`, `sub_deposits`.`gathered`, `presetFK` FROM `memorized_deposits` join `sub_deposits` ON `memorized_deposits`.`uid`=`depositFK` WHERE `memorized_deposits`.`uid`=$deposit AND `sub_deposits`.`uid`=$deposit2 AND `charFK`=$this->uid AND " . $this->getCoordsForSQL2() . " LIMIT 1";
			$res1 = $this->mysqli->query($sql1);
			if (mysqli_num_rows($res1)) {
				$curTime = new Time($this->mysqli);
				$row1 = mysqli_fetch_row($res1);
				$depositArr = array(
					"res" => $row1[0],
					"size" => $row1[1],
					"harvested" => $row1[2],
					"preset" => $row1[3]
					);
				if ($depositArr["harvested"]>= $depositArr["size"]) return array(
					"amount" => 0,
					"exhausted" => 1
					);
				$resource = new Resource($this->mysqli, $depositArr["res"], $depositArr["preset"]);
				$pos = $this->getPosition();
				$multi = $resource->getMultiplier($pos->y);
				
				$gatherable = round($resource->gathered*($ap/10));
				$gathered_sub = round($gatherable * $this->getSubtypeMultiplier($depositArr["preset"]));
				$resourceLeft = max(0,(round($depositArr["size"]*$multi)) - $depositArr["harvested"]);
				if ($gathered_sub>$resourceLeft) $gathered_sub = $resourceLeft;
				
				$sql2 = "UPDATE `objects` SET `weight`=`weight`+$gathered_sub WHERE `general_type`=5 AND `presetFK`=".$depositArr["preset"]." AND " . $this->getCoordsForSQL3() . " AND `secondaryFK`='" . $depositArr["res"] . "' AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows<=0) {
					$newObj = new Obj($this->mysqli, 0);
					$newObj->create($depositArr["preset"], 5, 0, "generated by gathering", $this->x, $this->y, $this->localx, $this->localy, $depositArr["res"], 1, $gathered_sub, $curTime->dateTime, $curTime->minute);
				}
				$sql4 = "UPDATE `sub_deposits` SET `gathered`=`gathered`+$gathered_sub WHERE `uid`=$deposit2";
				$this->mysqli->query($sql4);
				if ($this->mysqli->affected_rows==0) return -3;
				else {
					$this->spendAP($ap);
					$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $ap);
					if ($gatherable>=$depositArr["size"]-$depositArr["harvested"]) {
						return array(
						"amount" => $gatherable,
						"exhausted" => 1
						);
					}
					else {
						return array(
						"amount" => $gatherable,
						"exhausted" => 0
						);
					}
					
				}
			}
			else return -2;//user is trying to access a deposit in another location or a deposit that belongs to another character
		}
		else return -1;//You don't have enough AP
	}
	function getInventory() {
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `general_type`<>7 ORDER BY FIELD(`general_type`,'5',`general_type`), `date_created` DESC, `weight` DESC";
		$res = $this->mysqli->query($sql);
		$inventory = array();
		if ($res) {
			while ($row =  mysqli_fetch_row($res)) {
				$inventory[] = $row[0];
			}
		}
		else return false;
		
		if ($inventory) {
			return $inventory;
		}
		else return false;
	}
	
	function getInventorySpecific($gen, $preset) {
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `general_type` IN ($gen) AND `presetFK` IN ($preset) ORDER BY `date_created` DESC, `weight` DESC";
		$res = $this->mysqli->query($sql);
		$inventory = array();
		if ($res) {
			while ($row =  mysqli_fetch_row($res)) {
				$inventory[] = $row[0];
			}
		}
		else return false;
		
		if ($inventory) {
			return $inventory;
		}
		else return false;
	}
	
	function dropObject($item, $method, $amount=0) {
		$pos = $this->getPosition();
		$sql3 = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the character isn't carrying the item
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$droppableAmount = round($randomVariance*$amount);
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, 1, $droppableAmount);
		}
		else if ($method=="part") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $amount, $invItem->weight);
		}
		else if ($method=="whole") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $invItem->pieces, $invItem->weight);
		}
		
		$curTime = new Time($this->mysqli);
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The character isn't carrying this item
					else {
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE " . $this->getCoordsForSQL3() . " AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE " . $this->getCoordsForSQL3() . " AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						//this doesn't currently take buildings into account
						//this tries to increase a pile that is in the same location, of the same type and same secondary AND created earlier or at the same minute
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($invItem->preset, $invItem->type, $this->building, 'Generated through dropping', $pos->x, $pos->y, $pos->lx, $pos->ly, $invItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							//to do: what if in a group?
							if ($result) return true;
							else return false;
						}
						else return true;
					}
				}
				else $method = "whole";
			}
			else return false;
		}
		
		if ($method=="whole") {
			$pieces = $invItem->pieces;
			$actualDropWeight = $invItem->weight;
			//check if stackable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE " . $this->getCoordsForSQL3() . " AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualDropWeight WHERE " . $this->getCoordsForSQL3() . " AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non-countable objects won't merge if they're not resources
			//this doesn't currently work with buildings
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows==0) {
				$sql = "UPDATE `objects` SET `parent`=$this->building, `global_x`=$pos->x, `global_y`=$pos->y, `local_x`=$pos->lx, `local_y`=$pos->ly WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql);
				//moving the whole pile
				if ($this->mysqli->affected_rows==0) return false;
				else return true;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows==0) return -2;//duplication bug
				else return true;
			}
		}
	}
	
	function takeObject($item, $method, $amount=0) {
		$curTime = new Time($this->mysqli);
		//this doesn't currently work with buildings
		$sql3 = "SELECT `uid` FROM `objects` WHERE `uid`=$item AND " . $this->getCoordsForSQL3() . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the object isn't in the same location as the character
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$takeAmount = round($randomVariance*$amount);
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, 1, $takeAmount);
		}
		else if ($method=="pieces") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $amount, $targetItem->weight);
		}
		else if ($method=="whole") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $targetItem->pieces, $targetItem->weight);
		}
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					//reduce pile in location
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					//this doesn't currently support buildings
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The object isn't here
					else {
						//increase pile in inventory
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							//This function needs NULL as string or otherwise it won't work
							$result = $pile->create($targetItem->preset, $targetItem->type, $this->bodyId, 'Generated through pickup', 'NULL', 'NULL', 0, 0, $targetItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							
							if ($result) return 1;
							else return 0;
						}
						else return 1;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $targetItem->pieces;
			$actualTakeWeight = $targetItem->weight;
			//check if countable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualTakeWeight WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non stackable objects don't get merged if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows==0) {
				//move the whole thing
				$sql = "UPDATE `objects` SET `parent`=$this->bodyId, `global_x`= NULL, `global_y` = NULL, `local_x`=0, `local_y`=0 WHERE `uid`=$item AND " . $this->getCoordsForSQL3() . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				
				//this doesn't currently support buildings
				$this->mysqli->query($sql);
				if ($this->mysqli->affected_rows==0) return 0;
				else return 1;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE `uid`=$item AND " . $this->getCoordsForSQL3() . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				//this doesn't currently support buildings
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows==0) return -2;//duplication bug
				else return 1;
			}
		}
	}
	
	function storeInventoryObject($item, $container, $method, $amount=0) {
		$curTime = new Time ($this->mysqli);
		$sql3 = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the character isn't carrying the item
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$droppableAmount = round($randomVariance*$amount);
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, 1, $droppableAmount);
		}
		else if ($method=="pieces") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $amount, $invItem->weight);
		}
		else if ($method=="whole") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $invItem->pieces, $invItem->weight);
		}
		//check if selected amount fits in the container, else return false
		$sql4 = "SELECT `uid` FROM `objects` WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$container AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		//this doesn't currently support buildings
		$res2 = $this->mysqli->query($sql4);
		if (!mysqli_num_rows($res2)) return false;//the container is in another location
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The character isn't carrying this item
					else {
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						
						//this tries to increase a pile that is in the same location, of the same type and same secondary
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($invItem->preset, $invItem->type, $container, 'Generated through storing', 'NULL', 'NULL', 0, 0, $invItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							//$sql3 = "INSERT INTO `objects` (`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '$invItem->preset', '$invItem->type', '$container', CURRENT_TIMESTAMP, 'Generated through storing', 'NULL', 'NULL', '0', '0', '$invItem->secondary', '" . $res["pieces"] . "', '" . $res["weight"] . "', '" . $curTime[1] . "', '" . $curTime[2] . "')";
							//$this->mysqli->query($sql3);
							//$result = $this->mysqli->insert_id;
							if ($result) return true;
							else return false;
						}
						else return true;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $invItem->pieces;
			$actualDropWeight = $invItem->weight;
			//check if stackable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualDropWeight WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non-countable objects won't merge if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows<=0) {
				$sql = "UPDATE `objects` SET `parent`=$container, `global_x`=NULL, `global_y`=NULL, `local_x`=0, `local_y`=0 WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql);
				
				if ($this->mysqli->affected_rows<=0) return false;
				else return true;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows<=0) return -2;//duplication bug
				else return true;
			}
		}
	}
	
	function storeGroundObject($item, $container, $method, $amount=0) {
		$curTime = new Time($this->mysqli);
		$sql3 = "SELECT `uid` FROM `objects` WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		//this doesn't currently support buildings
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the character isn't carrying the item
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$droppableAmount = round($randomVariance*$amount);
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, 1, $droppableAmount);
		}
		else if ($method=="pieces") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $amount, $invItem->weight);
		}
		else if ($method=="whole") {
			$invItem = new Obj($this->mysqli, $item);
			$res = $invItem->checkMethod($method, $invItem->pieces, $invItem->weight);
		}
		//to do: check if selected amount fits in the container, else return false
		$sql4 = "SELECT `uid` FROM `objects` WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$container AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		//this doesn't currently support buildings
		$res2 = $this->mysqli->query($sql4);
		if (!mysqli_num_rows($res2)) return false;//the container is in another location
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					//this doesn't currently support buildings
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The item isn't on the ground
					else {
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						
						//this tries to increase a pile that is in the same location, of the same type and same secondary
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($invItem->preset, $invItem->type, $container, 'Generated through storing', 'NULL', 'NULL', 0, 0, $invItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							//$sql3 = "INSERT INTO `objects` (`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '$invItem->preset', '$invItem->type', '$container', CURRENT_TIMESTAMP, 'Generated through storing', 'NULL', 'NULL', '0', '0', '$invItem->secondary', '" . $res["pieces"] . "', '" . $res["weight"] . "', '" . $curTime[1] . "', '" . $curTime[2] . "')";
							//$this->mysqli->query($sql3);
							//$result = $this->mysqli->insert_id;
							if ($result) return true;
							else return false;
						}
						else return true;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $invItem->pieces;
			$actualDropWeight = $invItem->weight;
			//check if stackable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualDropWeight WHERE `parent`=$container AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non-countable objects won't merge if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows==0) {
				$sql = "UPDATE `objects` SET `parent`=$container, `global_x`=NULL, `global_y`=NULL, `local_x`=0, `local_y`=0 WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				//this doesn't currently support buildings
				$this->mysqli->query($sql);
				
				if ($this->mysqli->affected_rows==0) return false;
				else return true;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE " . $this->getCoordsForSQL3() . " AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				//this doesn't currently support buildings
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows==0) return -2;//duplication bug
				else return true;
			}
		}
	}
	
	function takeFromContainer($item, $container, $source, $method, $amount=0) {
		$curTime = new Time();
		if ($source==1) $src_str = "`parent`=$this->bodyId AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
		else if ($source==2) $src_str = $this->getCoordsForSQL3() . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
		$sql3 = "SELECT `uid` FROM `objects` WHERE `uid`=$item AND `parent`=$container AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the object isn't in the same location as the character
		$sql4 = "SELECT `uid` FROM `objects` WHERE `uid`=$container AND $src_str LIMIT 1";
		$res = $this->mysqli->query($sql4);
		if (!mysqli_num_rows($res)) return false;//the container isn't in the inventory or same location
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$takeAmount = round($randomVariance*$amount);
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, 1, $takeAmount);
		}
		else if ($method=="pieces") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $amount, $targetItem->weight);
		}
		else if ($method=="whole") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $targetItem->pieces, $targetItem->weight);
		}
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					//reduce pile in location
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$container AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$container AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The object isn't here
					else {
						//increase pile in inventory
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($targetItem->preset, $targetItem->type, $this->bodyId, 'Generated through pickup from container', 'NULL', 'NULL', 0, 0, $targetItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							if ($result) return true;
							else return false;
						}
						else return true;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $targetItem->pieces;
			$actualTakeWeight = $targetItem->weight;
			//check if countable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualTakeWeight WHERE `parent`=$this->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non stackable objects don't get merged if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows==0) {
				//move the whole thing
				$sql = "UPDATE `objects` SET `parent`=$this->bodyId, `global_x`= NULL, `global_y` = NULL, `local_x`=0, `local_y`=0 WHERE `uid`=$item AND `parent`=$container AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql);
				if ($this->mysqli->affected_rows==0) return false;
				else return true;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE `uid`=$item AND `parent`=$container AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows==0) return -2;//duplication bug
				else return true;
			}
		}
	}
	
	function checkFullness($stat) {
		//1 - stomach, 2 - small intestine, 3 - large intestine
		$sql = "SELECT sum(`amount`) FROM `stomach` WHERE `charFK`=$this->uid AND `status`=$stat";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$fullness = $row[0];
		}
		else $fullness=0;
		return $fullness;
	}
	
	function eat($object, $method, $amount=0, $container=0) {
		//type: 1 - don't check for poison, 2 - check for poison, 3 - check for discomfort/choking
		//method "weight", "pieces", "whole"
		//to do: This doesn't take preset into account yet
		$okay = 0;
		$stomachSize = 800;//in the future this will vary from person to person
		$fullness = $this->checkFullness(1);
		if ($method=="weight") {
			$randomVariance = (rand(0, 20)-10)/100+1;
			$amount = round($randomVariance*$amount);
		}
		
		//toDo: if fullness>stomachSize, induce vomiting
		if ($fullness>=$stomachSize) return -1;//can't eat anymore
		else {
			$curTime = new Time($this->mysqli);
			$capacity = $stomachSize - $fullness;
			$food = new Obj($this->mysqli, $object);
			$food->getBasicData();
			if ($container>0&&$food->parent!=$container) return -2;//trying to eat food that's not here
			else if ($container==0&&$food->parent!=$this->bodyId) return -2;
			else if ($food->parent==$this->bodyId) {
				$source = "person";//eating from inventory
			}
			else $source = "container";//in the future needs to check that the container is in the inventory or in the same location as the character
			
			$edible = $food->getAttribute(ATTR_EDIBLE);
			if (!$edible) return -3;//not edible
			$maxEat = $food->getAttribute(46);
			if ($maxEat&&$method=="weight") {
				if ($amount>$maxEat) $amount=$maxEat;
				if ($amount>$capacity) $amount=$capacity;
				if ($amount>$food->weight) $amount=$food->weight;
				$res = $food->checkMethod($method, 1, $amount);
			}
			else if ($method=="pieces") {
				$res = $food->checkMethod($method, $amount, $food->weight);
				if ($res["weight"]*$res["pieces"]>$maxEat) $amount=$maxEat;
				if ($res["weight"]*$res["pieces"]>$capacity&&$capacity<$maxEat) $amount=$capacity;
				if ($amount<$res["weight"]) $res = $food->checkMethod($method, $res["pieces"], $amount);//must eat less because it exceeds capacity
			}
			else if ($method=="whole") {
				$res = $food->checkMethod($method, $food->pieces, $food->weight);
				if ($res["countable"]==1&&$res["weight"]*$res["pieces"]>$maxEat) return -4;//You can't swallow that much in one go
				if ($res["countable"]==1&&$res["weight"]*$res["pieces"]>$capacity) return -4;
				if ($res["weight"]>$maxEat) return -4;//You can't swallow that much in one go
				if ($res["weight"]>$capacity) return -4;
			}
			
			$curTime = new Time($this->mysqli);
			
			if ($method!="whole") {
				if ($res) {
					if ($res["method"]=='part') {
						//reduce pile in source
						if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$food->parent AND `uid`=$object AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$food->parent AND `uid`=$object AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						$this->mysqli->query($sql);
						if ($this->mysqli->affected_rows==0) return -2;//The object isn't here
						else {
							//increase pile in stomach
							if ($res["countable"]==1) $sql2 = "UPDATE `stomach` SET `amount`=`amount`+(" . $res["weight"]*$res["pieces"] . ") WHERE `charFK`=$this->uid AND `resFK`=$food->secondary AND `status`=1 AND `datetime`=" . $curTime->dateTime. " LIMIT 1";
							else $sql2 = "UPDATE `stomach` SET `amount`=`amount`+" . $res["weight"] . " WHERE `charFK`=$this->uid AND `resFK`=$food->secondary AND `status`=1 AND `datetime`=" . $curTime->dateTime. " LIMIT 1";
							$this->mysqli->query($sql2);
							if ($this->mysqli->affected_rows==0) {
								//There is no pile to merge with, so creating new pile
								if ($res["countable"]==1) $sql3 = "INSERT INTO `stomach`(`uid`, `charFK`, `resFK`, `amount`, `status`, `datetime`) VALUES (NULL, '$this->uid', '$food->secondary', '" . $res["weight"]*$res["pieces"] . "', '1', '$curTime->dateTime')";
								else $sql3 = "INSERT INTO `stomach`(`uid`, `charFK`, `resFK`, `amount`, `status`, `datetime`) VALUES (NULL, '$this->uid', '$food->secondary', '" . $res["weight"] . "', '1', '$curTime->dateTime')";
								$this->mysqli->query($sql3);
								$result = $this->mysqli->insert_id;
								if ($result) $okay = 1;
								else return -5;//failed to create stomach content
							}
							else $okay = 1;
						}
					}
					else $method = "whole";
				}
				else return -2;
			}
			
			if ($method=="whole") {
				$sql2 = "DELETE FROM `objects` WHERE `uid`=$object AND `parent`=$food->parent AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows==0) return -6;//food didn't get deleted
				if ($res["countable"]==1) $sql = "UPDATE `stomach` SET `amount`=`amount`+(" . $res["weight"]*$res["pieces"] . ") WHERE `charFK`=$this->uid AND `resFK`=$food->secondary AND `status`=1 AND `datetime`=" . $curTime->dateTime. " LIMIT 1";
				else $sql = "UPDATE `stomach` SET `amount`=`amount`+" . $res["weight"] . " WHERE `charFK`=$this->uid AND `resFK`=$food->secondary AND `status`=1 AND `datetime`=" . $curTime->dateTime. " LIMIT 1";
				//merge with pile if exists
				$this->mysqli->query($sql);
				if ($this->mysqli->affected_rows==0) {
					//insert new row
					$newweight = $res["weight"]*$res["pieces"];
					if ($res["countable"]==1) $sql = "INSERT INTO `stomach` (`uid`, `charFK`, `resFK`, `amount`, `status`, `datetime`) VALUES (NULL, '$this->uid', '$food->secondary', '" . $newweight . "', '1', '$curTime->dateTime')";
					else $sql = "INSERT INTO `stomach` (`uid`, `charFK`, `resFK`, `amount`, `status`, `datetime`) VALUES (NULL, '$this->uid', '$food->secondary', '" . $res["weight"] . "', '1', '$curTime->dateTime')";
					$this->mysqli->query($sql);
					$result = $this->mysqli->insert_id;
					if ($result) $okay = 1;
					else return -5;
				}
				else $okay = 1;
			}
			//to do: process immediate poisons
			return $okay;
		}
	}
	
	function getObjectDynamicName($objectId) {
		if ($this->bodyId==$objectId) return $this->cname;
		else {
			$oname = "";
			$sql = "SELECT `naming`.`name` FROM `naming` WHERE `namerFK`=$this->uid AND `objectFK`=$objectId LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$oname = $row[0];
			}
			if ($oname == "") return "(unnamed)";
			else return $oname;
		}
	}
	
	function getDynamicName($ocharid) {
		if ($this->uid==$ocharid) return $this->cname;
		else {
			$other = new Character($this->mysqli, $ocharid);
			$oname = "";
			$sql = "SELECT `naming`.`name` FROM `naming` WHERE `namerFK`=$this->uid AND `objectFK`=$other->bodyId LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$oname = $row[0];
			}
			if ($oname == "") return "(unnamed)";
			else return $oname;
		}
	}
	
	function nameObject($objectId, $newname) {
		if ($objectId == $this->bodyId) $sql = "UPDATE `chars` SET `cname`='$newname' WHERE `objectFK`=$objectId AND `uid`=$this->uid LIMIT 1";
		else $sql = "UPDATE `naming` SET `name`='$newname' WHERE `objectFK`=$objectId AND `namerFK`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			$sql2 = "INSERT INTO `naming` (`uid`, `namerFK`, `objectFK`, `name`) VALUES (NULL, '$this->uid', '$objectId', '$newname')";
			$this->mysqli->query($sql2);
			$result = $this->mysqli->insert_id;
			if ($result) return true;
			else return false;
		}
		else return true;
	}
	
	function getVisitsThisLocation() {
		$arr = array();//this doesn't currently support buildings
		$sql = "SELECT `uid`, `dateTime`, `minute`, `event_type` FROM `loc_log` WHERE `charFK`=$this->uid AND " . $this->getCoordsForSQL2() . " ORDER BY `uid` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"dateTime" => $row[1],
					"minute" => $row[2],
					"type" => $row[3]
					);
			}
			return $arr;
		}
		else return -1;
	}
	
	function getCrossingTimelines() {
		$visits = array();
		$myLog = $this->getVisitsThisLocation();
		
		$startTime = 0;
		$endTime = 0;
		$startMinute = 0;
		$endMinute = 0;
		
		if ($myLog==-1) return false;
		
		foreach ($myLog as $entry) {
			//every event has a start time but they might not have an end time.
			if ($entry["type"]<3) {
				//startTime
				
				$startTime = $entry["dateTime"];
				$startMinute = $entry["minute"];
				
				$visits[] = array($startTime, $endTime, $startMinute, $endMinute);
			}
			if ($entry["type"]>2) {
				//endTime
				$endTime = $entry["dateTime"];
				$endMinute = $entry["minute"];
			}	
		}
		$other = $this->getOtherPeoplesVisits();
		$encounters = array();
		if (is_array($other)) {
			for ($i=0;$i<count($visits);$i++) {
				$startA = $visits[$i][0]+($visits[$i][2]/10);
				$endA = $visits[$i][1]+($visits[$i][3]/10);
				
				if ($endA==0) {
					$encounters[] = array(0, "This is your last visit", 0, 0);
					//para("This is your last visit.");
				}
				else {
					$encounters[] = array($this->uid, "left this time", $visits[$i][1], $visits[$i][3]);
					//para("This time you left at " . $endA);
				}
				
				for ($j=0;$j<count($other);$j++) {
					
					$startB = $other[$j][0]+($other[$j][3]/10);
					$endB = $other[$j][1]+($other[$j][4]/10);
					$startType = $other[$j][5];
					$endType = $other[$j][6];
					$ocharid = $other[$j][2];
					
					//echo "you $startA - $endA other $startB - $endB<br>";
					if ($endA==0) {
						if ($endB==0) {
							$encounters[] = array($ocharid, " is staying here for now", 0, 0);
							//para("This is also the last visit for the other one.");
							if ($startA>$startB) {
								$encounters[] = array($ocharid, " was already here when you arrived", 0, 0);
								//para("They were already here when you arrived.");
							}
							else if ($startA==$startB) {
								if ($startType==1) {
									$encounters[] = array($ocharid, " was born at the same time when you arrived", $visits[$i][0], $visits[$i][2]);
									//para("The other one was born " . $startB . " at the same time when you arrived.");
								}
								else if ($startType==2) {
									$encounters[] = array($ocharid, " arrived at the same time when you arrived", $visits[$i][0], $visits[$i][2]);
									//para("The other one arrived " . $startB . " at the same time when you arrived.");
								}
							}
							else if ($startType==1) {
								$encounters[] = array($ocharid, " was born while you were here", $visits[$i][0], $visits[$i][2]);
								//para("The other one was born " . $startB . " while you were here.");
							}
							else if ($startType==2) {
								$encounters[] = array($ocharid, " was arrived while you were here", $visits[$i][0], $visits[$i][2]);
								//para("The other one arrived " . $startB . " while you were here.");
							}
						}
						else {
							if ($startB>$startA) {
								$encounters[] = array($ocharid, " left", $other[$j][1], $other[$j][4]);
								$encounters[] = array($ocharid, " had arrived", $other[$j][0], $other[$j][3]);
								//para("The other one left at " . $endB . " this time, having arrived at " . $startB);
							}
						}
					}
					else {
						if ($endB>0) {
							$encounters[] = array($ocharid, " had previously left", $other[$j][1], $other[$j][4]);
							//para("The other one had already left at " . $endB);
						}
						if ($startA>$startB) {
							$encounters[] = array($ocharid, " was already here when you arrived", 0, 0);
							//para("They were already here when you arrived.");
						}
						else if ($startA==$startB) {
							if ($startType==1) {
								$encounters[] = array($ocharid, " had been born at the same time when you arrived", $other[$j][0], $other[$j][3]);
								//para("The other one was born " . $startB . " at the same time when you arrived.");
							}
							else if ($startType==2) {
								$encounters[] = array($ocharid, " had arrived at the same time with you at", $other[$j][0], $other[$j][3]);
								//para("The other one arrived " . $startB . " at the same time when you arrived.");
							}
						}
						else if ($startType==1) {
							$encounters[] = array($ocharid, " was born while you were here", $other[$j][0], $other[$j][3]);
							//para("The other one was born " . $startB . " while you were here");
						}
						else if ($startType==2) {
							$encounters[] = array($ocharid, " arrived while you were here", $other[$j][0], $other[$j][3]);
							//para("The other one arrived " . $startB . " while you were here");
						}
					}
				}
				$encounters[] = array($this->uid, " arrived this time", $visits[$i][0], $visits[$i][2]);
				//para("This time you arrived at " . $startA);
				
			}
			return $encounters;
		}
		else return false;
	}
	
	function getOtherPeoplesVisits() {
		//this doesn't currently support buildings
		$arr = array();
		$startTime = 0;
		$endTime = 0;
		$startMinute = 0;
		$endMinute = 0;
		$startType = 0;
		$endType = 0;
		$sql = "SELECT `uid`, `dateTime`, `minute`, `event_type`, `charFK` FROM `loc_log` WHERE `charFK`<>$this->uid AND " . $this->getCoordsForSQL2() . " ORDER BY `charFK`, `uid` DESC";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				if ($row[3]<3) {
					//startTime
					$startTime = $row[1];
					$startMinute = $row[2];
					$startType = $row[3];
				}
				if ($row[3]>2) {
					//endTime always comes first except when the person is staying
					$endTime = $row[1];
					$endMinute = $row[2];
					$endType = $row[3];
				}
				
				if ($startTime>0) {
					$arr[] = array($startTime, $endTime, $row[4], $startMinute, $endMinute, $startType, $endType);//every visit has a start time whether it's arrival or birth, but they might not have an end if it's the most recent one
					$startTime = 0;
					$endTime = 0;
					$startMinute = 0;
					$endMinute = 0;
					$startType = 0;
					$endType = 0;
					//the last value can never be end because the log is in reverse order
				}
			}
			return $arr;
		}
		else return -1;
	}
	
	function getCrossingTimelines2() {
		$visits = array();
		$myLog = $this->getVisitsThisLocation();
		if (is_array($myLog)) {
			if ($myLog[0]["type"]<3) {
				//never left this location, this doesn't currently support buildings
				$sql = "SELECT `uid`, `dateTime`, `minute`, `event_type` FROM `loc_log` WHERE `charFK`<>$this->uid AND " . $this->getCoordsForSQL2() . " AND (`dateTime`>" . $myLog[0]["dateTime"] . " OR (`dateTime`=".$myLog[0]["dateTime"]." AND `minute`>=".$myLog[0]["minute"].")) ORDER BY `dateTime` DESC, `minute` DESC";
			
				$startTime = 0;
				$startMinute = 0;
				$endTime = 0;
				$endMinute = 0;
				for ($i=1;$i<count($myLog);$i++) {
					if ($myLog[$i]["type"]>2) {
						$endTime = $myLog[$i]["dateTime"];
						$endMinute = $myLog[$i]["minute"];
					}
					else {
						$startTime = $myLog[$i]["dateTime"];
						$startMinute = $myLog[$i]["minute"];
					}
					
					if ($endTime>0&&$startTime>0) {
						$visits[] = "SELECT `uid`, `dateTime`, `minute`, `event_type` FROM `loc_log` WHERE `charFK`<>$this->uid AND " . $this->getCoordsForSQL2() . " AND (`dateTime`>" . $startTime . " OR (`dateTime`=".$startTime." AND `minute`>=".$startMinute.")) AND (`dateTime`<" . $endTime . " OR (`dateTime`=".$endtTime." AND `minute`<=".$endMinute.")) ORDER BY `dateTime` DESC, `minute` DESC";
						$startTime = 0;
						$startMinute = 0;
						$endTime = 0;
						$endMinute = 0;
					}
				}
				
			}
			else {
				$startTime = 0;
				$startMinute = 0;
				$endTime = 0;
				$endMinute = 0;
				for ($i=0;$i<count($myLog);$i++) {
					if ($myLog[$i]["type"]>2) {
						$endTime = $myLog[$i]["dateTime"];
						$endMinute = $myLog[$i]["minute"];
					}
					else {
						$startTime = $myLog[$i]["dateTime"];
						$startMinute = $myLog[$i]["minute"];
					}
					
					if ($endTime>0&&$startTime>0) {
						$visits[] = "SELECT `uid`, `dateTime`, `minute`, `event_type` FROM `loc_log` WHERE `charFK`<>$this->uid AND " . $this->getCoordsForSQL2() . " AND ((`dateTime`>" . $startTime . " OR (`dateTime`=".$startTime." AND `minute`>=".$startMinute.")) AND `event_type` IN (3,4)) AND (`dateTime`<" . $endTime . " OR (`dateTime`=".$endtTime." AND `minute`<=".$endMinute.")) ORDER BY `dateTime` DESC, `minute` DESC";
						$startTime = 0;
						$startMinute = 0;
						$endTime = 0;
						$endMinute = 0;
					}
				}
			}
		}
	}
	
	function give($ocharid, $item, $method, $amount=0) {
		
		$ochar = new Character($this->mysqli, $ocharid);
		$ochar->getBasicData();
		$returnval = $this->giveNow($ochar->bodyId, $item, $method, $amount);
		return $returnval;
	}
	
	function giveNow($otherBody, $item, $method, $amount=0) {
		$curTime = new Time($this->mysqli);
		$sql3 = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return -1;//the character isn't carrying the item
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$droppableAmount = round($randomVariance*$amount);
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->checkMethod($method, 1, $droppableAmount);
		}
		else if ($method=="pieces") {
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->checkMethod($method, $amount, $invItem->weight);
		}
		else if ($method=="whole") {
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->checkMethod($method, $invItem->pieces, $invItem->weight);
		}
		//to-do: check if selected amount exceeds the other character's carrying capacity, else return some negative number
		//check if the character is in the same location
		$sql4 = "SELECT `uid` FROM `objects` WHERE " . $this->getCoordsForSQL() . " AND `uid`=$otherBody LIMIT 1";//this doesn't support buildings
		$res2 = $this->mysqli->query($sql4);
		if (!mysqli_num_rows($res2)) return -2;//the receiver is in another location
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return -1;//The character isn't carrying this item
					else {
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$otherBody AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$otherBody AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						
						//this tries to increase a pile that is in the other character's inventory
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($invItem->preset, $invItem->type, $otherBody, 'Generated through giving', 'NULL', 'NULL', 0, 0, $invItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							
							if ($result) return 1;
							else return -3;//generating new pile failed
						}
						else return 1;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $invItem->pieces;
			$actualDropWeight = $invItem->weight;
			//check if stackable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$otherBody AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND (`datetime`<'" . $curTime->dateTime . "' OR (`datetime`='" . $curTime->dateTime . "' AND `minute`<='" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualDropWeight WHERE `parent`=$otherBody AND `presetFK`=$invItem->preset AND `secondaryFK`=$invItem->secondary AND `general_type`=5 AND (`datetime`<'" . $curTime->dateTime . "' OR (`datetime`='" . $curTime->dateTime . "' AND `minute`<='" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non-countable objects won't merge if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows==0) {
				$sql = "UPDATE `objects` SET `parent`=$otherBody, `global_x`=NULL, `global_y`=NULL, `local_x`=0, `local_y`=0 WHERE `parent`=$this->bodyId AND `uid`=$item AND (`datetime`<'" . $curTime->dateTime . "' OR (`datetime`='" . $curTime->dateTime . "' AND `minute`<='" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql);
				
				if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
				else return 1;
			}
			else {
				$sql2 = "DELETE FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`datetime`<'" . $curTime->dateTime . "' OR (`datetime`='" . $curTime->dateTime . "' AND `minute`<='" . $curTime->minute . "')) LIMIT 1";
				
				$this->mysqli->query($sql2);
				if ($this->mysqli->affected_rows<=0) return -5;//duplication bug
				else return 1;
			}
		}
	}
	
	function visitedCoords($accurate, $nongraphic = false) {
		//accurate is true or false
		$coords = array();
		if ($nongraphic) $sql = "SELECT `x`, `y` FROM `loc_log` WHERE `charFK`=$this->uid AND `building`=0 AND `event_type`<3 ORDER BY `dateTime` DESC, `minute` DESC, `uid` DESC LIMIT 55";
		else $sql = "SELECT `x`*90, (`y`+5000)*90 FROM `loc_log` WHERE `charFK`=$this->uid AND `building`=0 AND `event_type`<3 ORDER BY `dateTime` DESC, `minute` DESC, `uid` DESC LIMIT 55";
		$res = $this->mysqli->query($sql);
		$xv = 0;
		$yv = 0;
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				if ($nongraphic) $coords[] = array(
						"x" => $row[0],
						"y" => $row[1]
						);
				else if (!$accurate) {
					$xv += $this->biasRand();
					$x = $row[0] + $xv;
					$yv += $this->biasRand();
					$y = $row[1] + $yv;
					
					$coords[] = array(
						"x" => round($x/20),
						"y" => round($y/20)
						);
				}
				else $coords[] = array(
						"x" => round($row[0]/20),
						"y" => round($row[1]/20)
						);
			}
		}
		return $coords;
	}
	
	function biasRand() {
		$ranges = Array(
			Array(0,2,50),
			Array(2,15,40),
			Array(15,50,35),
			Array(50,80,20),
			Array(80,150,5)
		);
		$sel = rand(0,149);
		do {
			$pick = array_shift($ranges);
			$sel -= $pick[2];
		} while($pick && $sel >= 0);
		$random = rand($pick[0],$pick[1]);
		$rand2 = rand(0,1);
		
		if ($rand2==1) return -$random;
		else return $random;
	}
	
	function maxWithKey($array, $key) {
		if (!is_array($array) || count($array) == 0) return false;
		$max = $array[0][$key];
		foreach($array as $a) {
			if($a[$key] > $max) {
				$max = $a[$key];
			}
		}
		return $max;
	}
	
	function minWithKey($array, $key) {
		if (!is_array($array) || count($array) == 0) return false;
		$min = $array[0][$key];
		foreach($array as $a) {
			if($a[$key] < $min) {
				$min = $a[$key];
			}
		}
		return $min;
	}
	
	function validateChopTool($tool, $sel) {
		$sql = "SELECT `presetFK` FROM `objects` WHERE `uid`=$tool AND `parent`=$this->bodyId LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$preset = $row[0];
		}
		else return -1;//Trying to access an item not in inventory
		$local = new LocalMap($this->mysqli, $this->x, $this->y);
		$pool = $local->getClearTools($sel);
		$found = false;
		$efficiency = 0;
		foreach ($pool as $pos) {
			if ($pos["uid"]==$preset) {
				$efficiency = $pos["ap_multi"];
				$found = true;
				break;
			}
		}
		if (!$found) return -2;
		else return $efficiency;
	}
	
	function searchAnimals($ap) {
		$max_ap = $this->getAP();
		if ($max_ap<$ap) return -1;//Tried to use more AP than you have
		$loc = new GlobalMap($this->mysqli, $this->x, $this->y);
		$animals = $loc->getPossibleAnimals();
		if ($animals == -1) {
			$this->spendAP($ap);
			$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $ap);
			return -2;//Couldn't find any animals
		}
		$found = false;
		for ($i=0;$i<$ap;$i+=10) {
			if (rand(0,100)<($i+7)*2) {
				$found = true;
				$ap = $i+10;
				break;
			}
		}
		if (!$found) {
			$this->spendAP($ap);
			$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $ap);
			return -2;//Couldn't find any animals
		}
		$prey = $animals[rand(0,sizeof($animals)-1)];
		$curTime = new Time($this->mysqli);
		$animal = new AnimalType($this->mysqli, $prey["uid"]);
		$new_id = $animal->createRepresentative($this->x, $this->y, $this->localx, $this->localy, $curTime->dateTime, $curTime->minute);
		if ($new_id==-1) return -3;//Generating animal failed
		$this->spendAP($ap);
		$this->updateCharLocTime($this->x, $this->y, $this->localx, $this->localy, $this->building, 4, $ap);
		return array(
			"uid" => $new_id,
			"type" => $prey["uid"],
			"name" => $prey["name"]
			);
	}
	
	function slurSpeech($string, $intensity) {
		$newstr = "";
		for ($i = 0, $j = strlen($string); $i < $j; $i++) {
			if ($string[$i]=="a") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ay";
					else if ($rand==1) $newstr .= "ey";
					else if ($rand==2) $newstr .= "ah";
					else if ($rand==3) $newstr .= "u";
				}
				else $newstr .= "a";
			}
			else if ($string[$i]=="b") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "bb";
					else if ($rand==1) $newstr .= "p";
					else if ($rand==2) $newstr .= "ph";
					else if ($rand==3) $newstr .= "d";
				}
				else $newstr .= "b";
			}
			else if ($string[$i]=="c") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "k";
					else if ($rand==1) $newstr .= "s";
					else if ($rand==2) $newstr .= "ss";
					else if ($rand==3) $newstr .= "shh";
				}
				else $newstr .= "c";
			}
			else if ($string[$i]=="d") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "th";
					else if ($rand==1) $newstr .= "t";
					else if ($rand==2) $newstr .= "dd";
					else if ($rand==3) $newstr .= "dud";
				}
				else $newstr .= "d";
			}
			else if ($string[$i]=="e") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ee";
					else if ($rand==1) $newstr .= "i";
					else if ($rand==2) $newstr .= "ey";
					else if ($rand==3) $newstr .= "u";
				}
				else $newstr .= "e";
			}
			else if ($string[$i]=="f") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ff";
					else if ($rand==1) $newstr .= "ph";
					else if ($rand==2) $newstr .= "bub";
					else if ($rand==3) $newstr .= "peh";
				}
				else $newstr .= "f";
			}
			else if ($string[$i]=="g") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "z";
					else if ($rand==1) $newstr .= "gh";
					else if ($rand==2) $newstr .= "kh";
					else if ($rand==3) $newstr .= "c";
				}
				else $newstr .= "g";
			}
			else if ($string[$i]=="h") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "'";
					else if ($rand==1) $newstr .= "hh";
					else if ($rand==2) $newstr .= "kh";
					else if ($rand==3) $newstr .= "g";
				}
				else $newstr .= "h";
			}
			else if ($string[$i]=="i") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "a";
					else if ($rand==1) $newstr .= "e";
					else if ($rand==2) $newstr .= "y";
					else if ($rand==3) $newstr .= "aye";
				}
				else $newstr .= "i";
			}
			else if ($string[$i]=="j") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "z";
					else if ($rand==1) $newstr .= "h";
					else if ($rand==2) $newstr .= "kh";
					else if ($rand==3) $newstr .= "ee";
				}
				else $newstr .= "j";
			}
			else if ($string[$i]=="k") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "gh";
					else if ($rand==1) $newstr .= "'";
					else if ($rand==2) $newstr .= "kh";
					else if ($rand==3) $newstr .= "q";
				}
				else $newstr .= "k";
			}
			else if ($string[$i]=="l") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ll";
					else if ($rand==1) $newstr .= "'";
					else if ($rand==2) $newstr .= "h";
					else if ($rand==3) $newstr .= "lh";
				}
				else $newstr .= "l";
			}
			else if ($string[$i]=="m") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "mm";
					else if ($rand==1) $newstr .= "mmm";
					else if ($rand==2) $newstr .= "n";
					else if ($rand==3) $newstr .= "mh";
				}
				else $newstr .= "m";
			}
			else if ($string[$i]=="n") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ng";
					else if ($rand==1) $newstr .= "nuhn";
					else if ($rand==2) $newstr .= "m";
					else if ($rand==3) $newstr .= "nh";
				}
				else $newstr .= "n";
			}
			else if ($string[$i]=="o") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "u";
					else if ($rand==1) $newstr .= "oo";
					else if ($rand==2) $newstr .= "u";
					else if ($rand==3) $newstr .= "oh";
				}
				else $newstr .= "o";
			}
			else if ($string[$i]=="p") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "b";
					else if ($rand==1) $newstr .= "pw";
					else if ($rand==2) $newstr .= "'";
					else if ($rand==3) $newstr .= "ph";
				}
				else $newstr .= "p";
			}
			else if ($string[$i]=="q") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "k";
					else if ($rand==1) $newstr .= "kh";
					else if ($rand==2) $newstr .= "kw";
					else if ($rand==3) $newstr .= "w";
				}
				else $newstr .= "q";
			}
			else if ($string[$i]=="r") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "arr";
					else if ($rand==1) $newstr .= "rr";
					else if ($rand==2) $newstr .= "arrh";
					else if ($rand==3) $newstr .= "h";
				}
				else $newstr .= "r";
			}
			else if ($string[$i]=="s") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "ss";
					else if ($rand==1) $newstr .= "ssh";
					else if ($rand==2) $newstr .= "sush";
					else if ($rand==3) $newstr .= "z";
				}
				else $newstr .= "s";
			}
			else if ($string[$i]=="t") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "th";
					else if ($rand==1) $newstr .= "tt";
					else if ($rand==2) $newstr .= "'";
					else if ($rand==3) $newstr .= "d";
				}
				else $newstr .= "t";
			}
			else if ($string[$i]=="u") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "oo";
					else if ($rand==1) $newstr .= "a";
					else if ($rand==2) $newstr .= "w";
					else if ($rand==3) $newstr .= "aw";
				}
				else $newstr .= "u";
			}
			else if ($string[$i]=="v") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "vh";
					else if ($rand==1) $newstr .= "h";
					else if ($rand==2) $newstr .= "w";
					else if ($rand==3) $newstr .= "f";
				}
				else $newstr .= "v";
			}
			else if ($string[$i]=="w") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "vh";
					else if ($rand==1) $newstr .= "hw";
					else if ($rand==2) $newstr .= "kh";
					else if ($rand==3) $newstr .= "f";
				}
				else $newstr .= "w";
			}
			else if ($string[$i]=="x") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "kh";
					else if ($rand==1) $newstr .= "ks";
					else if ($rand==2) $newstr .= "xx";
					else if ($rand==3) $newstr .= "khs";
				}
				else $newstr .= "x";
			}
			else if ($string[$i]=="y") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "i";
					else if ($rand==1) $newstr .= "ee";
					else if ($rand==2) $newstr .= "ay";
					else if ($rand==3) $newstr .= "ch";
				}
				else $newstr .= "y";
			}
			else if ($string[$i]=="z") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "zh";
					else if ($rand==1) $newstr .= "sh";
					else if ($rand==2) $newstr .= "s";
					else if ($rand==3) $newstr .= "ch";
				}
				else $newstr .= "z";
			}
			else if ($string[$i]==" ") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "! ";
					else if ($rand==1) $newstr .= "... ";
					else if ($rand==2) $newstr .= ", ";
					else if ($rand==3) $newstr .= "...mmh...";
				}
				else $newstr .= " ";
			}
			else if ($string[$i]==",") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= ".";
					else if ($rand==1) $newstr .= "...";
					else if ($rand==2) $newstr .= "!";
					else if ($rand==3) $newstr .= "";
				}
				else $newstr .= ",";
			}
			else if ($string[$i]==".") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "..";
					else if ($rand==1) $newstr .= "...";
					else if ($rand==2) $newstr .= "!";
					else if ($rand==3) $newstr .= "-";
				}
				else $newstr .= ".";
			}
			else if ($string[$i]=="!") {
				if (rand(0,100)<$intensity) {
					$rand = rand(0,3);
					if ($rand==0) $newstr .= "!!!";
					else if ($rand==1) $newstr .= "...";
					else if ($rand==2) $newstr .= " y'know, ";
					else if ($rand==3) $newstr .= ".";
				}
				else $newstr .= "!";
			}
			else $newstr .= $string[$i];
		}
		return $newstr;
	}
	
	function attackEnemy($enemy_id, $combatStyle, $weapon) {
		//currently allowed combat styles are 5 incapacitate and 6 quick kill
		$enemy = new Obj($this->mysqli, $enemy_id);
		$enemy->getBasicData();
		$enemy->getName();
		$result_str = "";
		
		$bmap = array(
			8	 => 3,
			9	 => 3,
			11	 => 3,
			33	 => 3,
			43	 => 3,
			50	 => 3,
			51	 => 4,
			52	 => 4,
			59	 => 4,
			60	 => 4,
			61	 => 4,
			62	 => 3,
			63	 => 3,
			64	 => 3,
			65	 => 3,
			69	 => 3,
			469	 => 3,
			470	 => 3,
			471	 => 3,
			472	 => 3,
			473	 => 3,
			474	 => 3,
			475	 => 3,
			476	 => 3,
			477	 => 3,
			478	 => 3,
			479	 => 0,
			480	 => 0,
			481	 => 0,
			482	 => 0,
			483	 => 0,
			484	 => 2,
			485	 => 2,
			486	 => 2,
			487	 => 2,
			19	 => 1,
			26	 => 1,
			28	 => 1,
			25	 => 1,
			23	 => 1,
			21	 => 1
			);//to-do: add more target types
		$pronoun = $this->getPronoun();
		$wpn = new Obj($this->mysqli, $weapon);
		$wpn->getBasicData();
		$wpn->getName();
		
		if ($weapon==0) $wpn->name = "fist";
		
		$info = $this->bodyPartTargeter(10, $bmap[$enemy->preset], $combatStyle);//later on falter will depend on skills
		if (!$info) {
			if ($enemy->type==2) $result_str = "#ATTACKER tries to hit #VICTIM with $pronoun " . $wpn->name . " but misses.";
			else $result_str = "#ATTACKER tries to hit " . $enemy->name . " with $pronoun " . $wpn->name . " but misses.";
			$combat = $this->checkCurrentCombat();
			$this->recordCombatEvent($combat, $result_str, $this->bodyId, $enemy->uid, 1);
			return false;
		}//You missed
		
		if ($weapon==0) {
			$severing = 0;
			$piercing = 0;
			$crushing = 5;
		}
		else {
			$severing = $wpn->getAttribute(56);
			$piercing = $wpn->getAttribute(57);
			$crushing = $wpn->getAttribute(58);
		}
		$broken = 0;
		$efficiency = rand(50,125);
		if ($efficiency==125&&rand(0,99)==99) $efficiency = 150;
		if ($severing>0&&$severing>$crushing&&$severing>$piercing) {
			$severing = round($severing*$efficiency/100);
			$bleed_type = 1;
			$bleed_level = round($severing/30);
			if ($info[0]["sever"]<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER slices through #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", severing it from the rest of the body.";
				else $result_str = "#ATTACKER slices through " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", severing it from the rest of the body.";
				$depth = 9;
				if ($bleed_level<3) $bleed_level = 3;
				if ($info[0]["id"]<4) $info[1] = true;//instakill
				if (ibetween($info[0]["id"], 18, 27))  $info[2] = $true;//crippled
				$broken = 2;
			}
			else if (($info[0]["sever"]*0.90)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER almost severs #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . " so that it''s hanging by a shred.";
				else $result_str = "#ATTACKER almost severs " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . " so that it''s hanging by a shred.";
				$depth = 8;
				if ($bleed_level<3) $bleed_level = 3;
				if (ibetween($info[0]["id"], 18, 27)) $info[2] = $true;//crippled
				$broken = 2;
			}
			else if (($info[0]["sever"]*0.55)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER half severs #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER half severs " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 7;
				if ($bleed_level<3) $bleed_level = 3;
				if (ibetween($info[0]["id"], 18, 27)) $info[2] = $true;//crippled
			}
			else if (($info[0]["sever"]*0.40)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER cuts a deep gash in #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name;
				else $result_str = "#ATTACKER cuts a deep gash in " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name;
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$severing) {
					$result_str .= " exposing bone.";
					$depth = 5;
				}
				else {
					$result_str .= ".";
					$depth = 4;
				}
				if ($bleed_level<2) $bleed_level = 2;
			}
			else if (($info[0]["sever"]*0.20)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER cuts a considerable gash in #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name;
				else $result_str = "#ATTACKER cuts a considerable gash in " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name;
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$severing) {
					$result_str .=  ", exposing bone.";
					$depth = 5;
				}
				else {
					$result_str .=  ", reaching into the muscle.";
					$depth = 4;
				}
				if ($bleed_level<2) $bleed_level = 2;
			}
			else if (($info[0]["sever"]*0.10)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER slashs a wound in #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", reaching into the subcutaneous fat layer.";
				else $result_str = "#ATTACKER slashs a wound in " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", reaching into the subcutaneous fat layer.";
				$depth = 3;
			}
			else if (($info[0]["sever"]*0.05)<=$severing&&$info[0]["sever"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER slices a shallow wound in #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER slices a shallow wound in " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 2;
			}
			else {
				if ($enemy->type==2) $result_str = "#ATTACKER slashes #VICTIM''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER slashes " . $enemy->name . "''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				$depth = 1;
			}
		}
		else if ($crushing>0&&$crushing>$piercing) {
			$crushing = round($crushing*$efficiency/100);
			$bleed_type = 2;
			if ($info[0]["crush"]<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER crushes #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER crushes " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 8;
				$bleed_level = 6;
				$broken = 4;
			}
			else if (($info[0]["crush"]*0.75)<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER badly breaks #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER badly breaks " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 7;
				$bleed_level = 5;
				$broken = 3;
			}
			else if (($info[0]["crush"]*0.50)<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER fractures #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER fractures " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 7;
				$bleed_level = 5;
				$broken = 2;
			}
			else if (($info[0]["crush"]*0.40)<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER smashes #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", causing serious internal bleeding.";
				else $result_str = "#ATTACKER smashes " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", causing serious internal bleeding.";
				$depth = 5;
				$bleed_level = 4;
			}
			else if (($info[0]["crush"]*0.20)<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER smashes #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which will certainly cause a big bruise.";
				else $result_str = "#ATTACKER smashes " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which will certainly cause a big bruise.";
				$depth = 4;
				$bleed_level = 3;
			}
			else if (($info[0]["crush"]*0.10)<=$crushing&&$info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER hit #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which is going to leave a bruise.";
				else $result_str = "#ATTACKER hit " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which is going to leave a bruise.";
				$depth = 3;
				$bleed_level = 2;
			}
			else if ($info[0]["crush"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER hits #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which might cause a small bruise.";
				else $result_str = "#ATTACKER hits " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which might cause a small bruise.";
				$depth = 1;
				$bleed_level = 1;
			}
			else {
				if ($enemy->type==2) $result_str = "#ATTACKER hits #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which is likely to cause slight internal bleeding in the surrounding tissues.";
				else $result_str = "#ATTACKER hits " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ", which is likely to cause slight internal bleeding in the surrounding tissues.";
				$depth = 2;
				$bleed_level = 2;
			}
			if ($piercing>0) {
				$result_str .= "In addition, the weapon causes some external wounds.";
				$bleed_type = 3;
			}
		}
		else if ($piercing>0) {
			$piercing = round($piercing*$efficiency/100);
			$bleed_type = 1;
			if ($info[0]["pierce"]<=$piercing&&$info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER pierces #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER pierces " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 6;
				$bleed_level = 6;
				if ((strpos($info[0]["name"], 'artery') !== false)||(strpos($info[0]["name"], 'heart') !== false)) $bleed_level = 10;
			}
			else if (($info[0]["pierce"]*0.50)<=$piercing&&$info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER pierces #VICTIM''s " . $info[0]["name"] . " halfway with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER pierces " . $enemy->name . "''s " . $info[0]["name"] . " halfway with $pronoun " . $wpn->name . ".";
				$depth = 4;
				$bleed_level = 5;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 9;
			}
			else if (($info[0]["pierce"]*0.40)<=$piercing&&$info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str =  "#ATTACKER stabs #VICTIM''s " . $info[0]["name"] . " deeply with $pronoun " . $wpn->name;
				else $result_str =  "#ATTACKER stabs " . $enemy->name . "''s " . $info[0]["name"] . " deeply with $pronoun " . $wpn->name;
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$piercing) {
					$result_str .= ", colliding with bone.</p>";
					$depth = 5;
				}
				else {
					$result_str .=  ".";
					$depth = 4;
				}
				$bleed_level = 4;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 8;
			}
			else if (($info[0]["pierce"]*0.2)<=$piercing&&$info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER stabs #VICTIM''s " . $info[0]["name"] . " considerably with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER stabs " . $enemy->name . "''s " . $info[0]["name"] . " considerably with $pronoun " . $wpn->name . ".";
				$depth = 3;
				$bleed_level = 3;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 7;
			}
			else if (($info[0]["pierce"]*0.1)<=$piercing&&$info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER wounds #VICTIM''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER wounds " . $enemy->name . "''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				$depth = 2;
				$bleed_level = 2;
			}
			else if ($info[0]["pierce"]>0) {
				if ($enemy->type==2) $result_str = "#ATTACKER pokes #VICTIM''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER pokes " . $enemy->name . "''s " . $info[0]["name"] . " superficially with $pronoun " . $wpn->name . ".";
				$depth = 1;
				$bleed_level = 1;
			}
			else {
				if ($enemy->type==2) $result_str = "#ATTACKER nicks #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				else $result_str = "#ATTACKER nicks " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . ".";
				$depth = 0;
			}
		}
		else {
			if ($enemy->type==2) $result_str = "#ATTACKER bumps #VICTIM''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . " but it doesn't cause any damage.";
			else $result_str = "#ATTACKER bumps " . $enemy->name . "''s " . $info[0]["name"] . " with $pronoun " . $wpn->name . " but it doesn't cause any damage.";//this shouldn't actually be possible because it shouldn't allow picking weapons that aren't weapons
		}
		
		if (isset($bleed_type)&&isset($bleed_level)&&isset($depth)) {
			$curTime = new Time($this->mysqli);
			$sql = "INSERT INTO `wounds` (`objectFK`, `bodypart`, `depth`, `bleed_level`, `bleed_type`, `broken`, `datetime`, `minute`) VALUES ('$enemy->uid', '" . $info[0]["id"] . "', '$depth', '$bleed_level', '$bleed_type', '$broken', '" . $curTime->dateTime . "', '" . $curTime->minute . "')";
			$this->mysqli->query($sql);
			$result = $this->mysqli->insert_id;
			if (!$result) {
				//para("Something went wrong and the wound couldn't be recorded.");
				return false;
			}
		}
		
		if ($info[2]) {
			$result_str .= " The attack cripples the target.";
			$enemy->setAttribute(60, 1);
			$enemy->setAttribute(61, 1);//sprawled
		}
		if ($info[3]) {
			$result_str .= " The target seems disoriented.";
			$enemy->setAttribute(59, 1);//concussion
		}
		if ($info[1]) $result_str .= " There''s no way they''re going to survive that.";
		
		//para($result_str);
		
		$combat = $this->checkCurrentCombat();
		
		if ($enemy->type==2) $res = $this->recordCombatEvent($combat, $result_str, $this->bodyId, $enemy->uid, 3);
		else $res = $this->recordCombatEvent($combat, $result_str, $this->bodyId, $enemy->uid, 1);
		if (is_array($res)) return $res;
		
		return 100;
	}
	
	function recordCombatEvent($combat, $str, $actor, $target, $type) {
		//type: 1 - actor is dynamic name, target is static, 2 - actor is static, target is dynamic, 3 - both actor and target are dynamic, 4 - both are static
		$curTime = new Time($this->mysqli);
		$sql = "INSERT INTO `combat_events` (`combatFK`, `actorFK`, `targetFK`, `dateTime`, `minute`, `type`, `contents`) VALUES ($combat, $actor, $target, ".$curTime->dateTime.", ".$curTime->minute.", $type, '$str')";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			return $result;
		}
		else return array("attempt" => $sql);
	}
	
	function getCombatEvents($combat, $startDt, $startM, $endDt, $endM) {
		$events = array();
		if ($endDt == 0) $sql = "SELECT `eventID`, `actorFK`, `targetFK`, `type`, `contents`, `dateTime`, `minute` FROM `combat_events` WHERE (`dateTime`>$startDt OR (`dateTime`=$startDt AND `minute`>=$startM)) AND `combatFK`=$combat ORDER BY `eventID`";
		else $sql = "SELECT `eventID`, `actorFK`, `targetFK`, `type`, `contents`, `dateTime`, `minute` FROM `combat_events` WHERE (`dateTime`>$startDt OR (`dateTime`=$startDt AND `minute`>=$startM)) AND `combatFK`=$combat AND (`dateTime`<$endDt OR (`dateTime`=$endDt AND `minute`<=$endM)) ORDER BY `eventID`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$events[] = array(
					"uid" => $row[0],
					"actor" => $row[1],
					"target" => $row[2],
					"type" => $row[3],
					"contents" => $row[4],
					"dateTime" => $row[5],
					"minute" => $row[6]
					);
			}
			return $events;
		}
		else return false;
	}
	
	function printCombatEvents($combat, $startDt, $startM, $endDt, $endM) {
		$events = $this->getCombatEvents($combat, $startDt, $startM, $endDt, $endM);
		if (!$events) para("No events in this timespan.");
		else {
			foreach ($events as $event) {
				$timestamp = new Time($this->mysqli, $event["dateTime"], $event["minute"]);
				if ($event["type"]==4) para($timestamp->getDateTime() . " " . $event["contents"]);
				else if ($event["type"]==1) {
					$dynamic = $this->getObjectDynamicName($event["actor"]);
					$str = str_replace("#ATTACKER", $dynamic, $event["contents"]);
					para($timestamp->getDateTime() . " " . $str);
				}
				else if ($event["type"]==2) {
					$dynamic = $this->getObjectDynamicName($event["target"]);
					$str = str_replace("#VICTIM", $dynamic, $event["contents"]);
					para($timestamp->getDateTime() . " " . $str);
				}
				else if ($event["type"]==3) {
					$dynamic1 = $this->getObjectDynamicName($event["actor"]);
					$dynamic2 = $this->getObjectDynamicName($event["target"]);
					$str = str_replace("#ATTACKER", $dynamic1, $event["contents"]);
					$str2 = str_replace("#VICTIM", $dynamic2, $str);
					para($timestamp->getDateTime() . " " . $str2);
				}
			}
		}
	}
	
	function getCombatParticipationTimes($combat) {
		$sql = "SELECT `uid`, `join_dt`, `join_m`, `leave_dt`, `leave_m` FROM `combat_participants` WHERE `objectFK`=$this->bodyId AND `combatFK`=$combat ORDER BY `uid`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$times[] = array(
					"uid" => $row[0],
					"join_dt" => $row[1],
					"join_m" => $row[2],
					"leave_dt" => $row[3],
					"leave_m" => $row[4]
					);
			}
			return $times;
		}
		else return false;
	}
	
	function bodyPartTargeter($falter, $targetType, $combatStyle) {
		//29.7.2017: I had to replace 69 rows of if clauses when switching to the abetween function
		//makes me think that it would be better to use an array and a foreach loop
		//targetType 1 - humanoid, 2 - deer, horse etc, 3 - small/medium quadruped, 4 - emu, ostrich, rhea, crane
		//combatStyle 1 play, 2 practice, 3 punish, 4 disarm, 5 incapacitate, 6 quick kill, 7 slow death, 8 torture, 9 blind, 10 feet, 11 torso, 12 wildly random
		
		if ($targetType==1) $bodyparts = csvIntoArray(dirname(__FILE__) . "/bodyparts.csv");
		if ($targetType==2) $bodyparts = csvIntoArray(dirname(__FILE__) . "/bodyparts2.csv");
		if ($targetType==3) $bodyparts = csvIntoArray(dirname(__FILE__) . "/bodyparts3.csv");
		if ($targetType==4) $bodyparts = csvIntoArray(dirname(__FILE__) . "/bodyparts4.csv");
		
		if (!is_array($bodyparts)) para("Error, reading cvs failed. " . dirname(__FILE__));
		
		if ($targetType==1) {
			if ($combatStyle==1) {
				$rand1 = rand(0,1);
				if ($rand1==0) {
					//fingers
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 15 + rand(-$falter, $falter);
					else $x = 105 + rand(-$falter, $falter);
					$y = 235 + rand(-$falter, $falter);
				}
				else {
					//knees
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 45 + rand(-$falter, $falter);
					else $x = 75 + rand(-$falter, $falter);
					$y = 75 + rand(-$falter, $falter);
				}
			}
			if ($combatStyle==5) {
				$rand1 = rand(0,1);
				if ($rand1==0) {
					//hamstring
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 45 + rand(-$falter, $falter);
					else $x = 75 + rand(-$falter, $falter);
					$y = 100 + rand(-$falter, $falter);
				}
				else {
					//achilles tendon
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 45 + rand(-$falter, $falter);
					else $x = 75 + rand(-$falter, $falter);
					$y = 40 + rand(-$falter, $falter);
				}
			}
			if ($combatStyle==6) {
				//quick kill
				$rand1 = rand(0,1);
				if ($rand1==0) {
					//head
					$x = 60 + rand(-$falter, $falter);
					$y = 250 + rand(-$falter, $falter);
				}
				else {
					//carotid artery
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 55 + rand(-$falter, $falter);
					else $x = 65 + rand(-$falter, $falter);
					$y = 230 + rand(-$falter, $falter);
				}
			}
			if ($combatStyle==10) {
				//feet
				$rand2 = rand(0,1);
				if ($rand2==0) $x = 50 + rand(-$falter, $falter);
				else $x = 70 + rand(-$falter, $falter);
				$y = 5 + rand(-$falter, $falter);
			}
			if ($combatStyle==11) {
				//torso
				$x = 60 + rand(-$falter, $falter);
				$y = 215 + rand(-$falter, $falter);
			}
			if ($combatStyle==12) {
				//wildly random
				$x = 60 + rand(-50-$falter, $falter+50);
				$y = 200 + rand(-100-$falter, $falter+100);
			}
		}
		if ($targetType==2) {
			if ($combatStyle==5) {
				//incapacitate
				$rand1 = rand(0,2);
				if ($rand1==0) {
					//front legs
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 40 + rand(-$falter, $falter);
					else $x = 80 + rand(-$falter, $falter);
					$y = 10 + rand(-$falter, $falter);
				}
				elseif ($rand1==1) {
					//hind legs hamstring
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 5 + rand(-$falter, $falter);
					else $x = 115 + rand(-$falter, $falter);
					$y = 100 + rand(-$falter, $falter);
				}
				else {
					//hind legs achilles tendon
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 5 + rand(-$falter, $falter);
					else $x = 115 + rand(-$falter, $falter);
					$y = 65 + rand(-$falter, $falter);
				}
			}
			if ($combatStyle==6) {
				//quick kill
				$rand1 = rand(0,2);
				if ($rand1==0) {
					//head
					$x = 60 + rand(-$falter, $falter);
					$y = 215 + rand(-$falter, $falter);
				}
				else if ($rand1==1) {
					//carotid artery
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 46 + rand(-$falter, $falter);
					else $x = 74 + rand(-$falter, $falter);
					$y = 175 + rand(-$falter, $falter);
				}
				else {
					//heart
					$x = 60 + rand(-$falter, $falter);
					$y = 95 + rand(-$falter, $falter);
				}
			}
		}
		if ($targetType==3) {
			if ($combatStyle==5) {
				//incapacitate
				$rand1 = rand(0,1);
				if ($rand1==0) {
					//hind leg
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 5 + rand(-$falter, $falter);
					else $x = 75 + rand(-$falter, $falter);
					$y = 150 + rand(-$falter, $falter);
				}
				else {
					//front leg
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 5 + rand(-$falter, $falter);
					else $x = 75 + rand(-$falter, $falter);
					$y = 25 + rand(-$falter, $falter);
				}
			}
			if ($combatStyle==6) {
				//quick kill
				$rand1 = rand(0,2);
				if ($rand1==0) {
					//heart
					$x = 65 + rand(-$falter, $falter);
					$y = 65 + rand(-$falter, $falter);
				}
				else if ($rand1==1) {
					//carotid artery
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 26 + rand(-$falter, $falter);
					else $x = 54 + rand(-$falter, $falter);
					$y = 32 + rand(-$falter, $falter);
				}
				else {
					//head
					$x = 40 + rand(-$falter, $falter);
					$y = 15 + rand(-$falter, $falter);
				}
			}
		}
		if ($targetType==4) {
			if ($combatStyle==5) {
				//incapacitate
				$rand2 = rand(0,5);
				if ($rand2==0) $x = 40 + rand(-$falter, $falter);
				else $x = 80 + rand(-$falter, $falter);
				$y = 60 + rand(-$falter, $falter);		
			}
			if ($combatStyle==6) {
				//quick kill
				$rand1 = rand(0,2);
				if ($rand1==0) {
					//head
					$x = 60 + rand(-$falter, $falter);
					$y = 215 + rand(-$falter, $falter);
				}
				else if ($rand1==1) {
					//carotid artery
					$rand2 = rand(0,5);
					if ($rand2==0) $x = 46 + rand(-$falter, $falter);
					else $x = 74 + rand(-$falter, $falter);
					$y = 175 + rand(-$falter, $falter);
				}
				else {
					//heart
					$x = 60 + rand(-$falter, $falter);
					$y = 95 + rand(-$falter, $falter);
				}
			}
		}
		
		$instakill = false;
		$crippled = false;
		$concussion = false;
		
		if ($targetType==1) {
			if (abetween($x, 30, 90)&&abetween($y, 120, 220)) {
				$bpn = "torso";
				if (abetween($x, 55, 65)&&abetween($y, 190, 200)) {
					$bpn = "heart";
					$instakill = true;
				}
			}
			if (abetween($x, 45, 75)&&abetween($y, 240, 270)) {
				$bpn = "head";
				if (abetween($x, 50, 55)&&abetween($y, 255, 260)) {
					$bpn = "right eye";
				}
				else if (abetween($x, 65, 70)&&abetween($y, 255, 260)) {
					$bpn = "right eye";
				}
				if (rand(0,20)==0) $concussion = true;
			}
			else if (abetween($x, 50, 70)&&abetween($y, 220, 240)) {
				$bpn = "neck";
				if ($x==55) {
					$bpn = "right carotid artery";
					$instakill = true;
				}
				if ($x==65) {
					$bpn = "left carotid artery";
					$instakill = true;
					}
			}
			else if (abetween($x, 20, 30)&&abetween($y, 170, 220)) {
				$bpn = "right upper arm";
			}
			else if (abetween($x, 90, 100)&&abetween($y, 170, 220)) {
				$bpn = "left upper arm";
			}
			else if (abetween($x, 10, 25)&&abetween($y, 160, 170)) {
				$bpn = "right elbow";
			}
			else if (abetween($x, 95, 110)&&abetween($y, 160, 170)) {
				$bpn = "left elbow";
			}
			else if (abetween($x, 5, 20)&&abetween($y, 170, 210)) {
				$bpn = "right forearm";
			}
			else if (abetween($x, 100, 115)&&abetween($y, 170, 210)) {
				$bpn = "left forearm";
			}
			else if (abetween($x, 10, 20)&&abetween($y, 210, 22)) {
				$bpn = "right wrist";
			}
			else if (abetween($x, 100, 110)&&abetween($y, 210, 220)) {
				$bpn = "left wrist";
			}
			else if (abetween($x, 5, 20)&&abetween($y, 220, 230)) {
				$bpn = "right palm";
			}
			else if (abetween($x, 100, 115)&&abetween($y, 220, 230)) {
				$bpn = "left palm";
			}
			/*
else if ($x>=5&&$x<20&&$y>=230&&$y<240) {
	$bpn = "right hand fingers";
}
else if ($x>=100&&$x<115&&$y>=230&&$y<240) {
	$bpn = "left hand fingers";
}
else if ($x>=20&&$x<25&&$y>=225&&$y<235) {
	$bpn = "right thumb";
}
else if ($x>=95&&$x<100&&$y>=225&&$y<235) {
	$bpn = "left thumb";
}*/
			else if (abetween($x, 30, 55)&&abetween($y, 80, 120)) {
				$bpn = "right thigh";
				if ($x==45) {
					$bpn = "right hamstring";
					$crippled = true;
				}
			}
			else if (abetween($x, 65, 90)&&abetween($y, 80, 120)) {
				$bpn = "left thigh";
				if ($x==75) {
					$bpn = "left hamstring";
					$crippled = true;
				}
			}
			else if (abetween($x, 40, 50)&&abetween($y, 70, 80)) {
				$bpn = "right knee";
			}
			else if (abetween($x, 70, 80)&&abetween($y, 70, 80)) {
				$bpn = "left knee";
			}
			else if (abetween($x, 35, 55)&&abetween($y, 20, 70)) {
				$bpn = "right shin";
				if ($y==40) {
					$bpn = "right achilles tendon";
					$crippled = true;
				}
			}
			else if (abetween($x, 65, 85)&&abetween($y, 20, 70)) {
				$bpn = "left shin";
				if ($y==40) {
					$bpn = "left achilles tendon";
					$crippled = true;
				}
			}
			else if (abetween($x, 40, 50)&&abetween($y, 10, 20)) {
				$bpn = "right ankle";
			}
			else if (abetween($x, 70, 80)&&abetween($y, 10, 20)) {
				$bpn = "left ankle";
			}
			else if (abetween($x, 20, 55)&&abetween($y, 0, 10)) {
				$bpn = "right foot";
			}
			else if (abetween($x, 65, 90)&&abetween($y, 0, 10)) {
				$bpn = "left foot";
			}
			else $bpn = "missed";
		}
		else if ($targetType==2) {
			if (abetween($x, 30, 90)&&abetween($y, 80, 160)) {
				$bpn = "torso";
				if (abetween($x, 55, 65)&&abetween($y, 90, 100)) {
					$bpn = "heart";
					$instakill = true;
				}
			}
			else if (abetween($x, 45, 75)&&abetween($y, 160, 190)) {
				$bpn = "neck";
				if ($x==46) {
					$bpn = "right carotid artery";
					$instakill = true;
				}
				if ($x==74) {
					$bpn = "left carotid artery";
					$instakill = true;
				}
			}
			else if (abetween($x, 4, 75)&&abetween($y, 190, 210)) {
				$bpn = "head";
				if (abetween($x, 50, 55)&&abetween($y, 205, 210)) {
					$bpn = "right eye";
				}
				else if (abetween($x, 65, 70)&&abetween($y, 205, 210)) {
					$bpn = "left eye";
				}
				if (rand(0,20)==0) $concussion = true;
			}
			else if (abetween($x, 30, 45)&&abetween($y, 40, 80)) {
				$bpn = "right upper front limb";
			}
			else if (abetween($x, 75, 90)&&abetween($y, 40, 80)) {
				$bpn = "left upper front limb";
			}
			else if (abetween($x, 35, 45)&&abetween($y, 0, 40)) {
				$bpn = "right lower front limb";
				if ($y==10) {
					$bpn = "left front leg ligament";
					$crippled = true;
				}
			}
			else if (abetween($x, 75, 85)&&abetween($y, 0, 40)) {
				$bpn = "left lower front limb";
				if ($y==10) {
					$bpn = "left front leg ligament";
					$crippled = true;
				}
			}
			else if (abetween($x, 10, 30)&&abetween($y, 90, 150)) {
				$bpn = "right side";
			}
			else if (abetween($x, 90, 110)&&abetween($y, 90, 150)) {
				$bpn = "left side";
			}
			else if (abetween($x, 0, 10)&&abetween($y, 60, 120)) {
				$bpn = "right shin";
				if ($y==100) {
					$bpn = "right hamstring";
					$crippled = true;
				}
				if ($y==65) {
					$bpn = "right achilles tendon";
					$crippled = true;
				}
			}
			else if (abetween($x, 110, 120)&&abetween($y, 60, 120)) {
				$bpn = "left shin";
				if ($y==100) {
					$bpn = "left hamstring";
					$crippled = true;
				}
				if ($y==65) {
					$bpn = "left achilles tendon";
					$crippled = true;
				}
			}
			else $bpn = "missed";
		}
		else if ($targetType==3) {
			if (abetween($x, 10, 70)&&abetween($y, 40, 130)) {
				$bpn = "torso";
				if (abetween($x, 60, 70)&&abetween($y, 60, 70)) {
					$bpn = "heart";
					$instakill = true;
				}
			}
			else if (abetween($x, 25, 55)&&abetween($y, 25, 40)) {
				$bpn = "neck";
				if ($x==26) {
					$bpn = "right carotid artery";
					$instakill = true;
				}
				if ($x==54) {
					$bpn = "left carotid artery";
					$instakill = true;
				}
			}
			else if (abetween($x, 25, 55)&&abetween($y, 0, 25)) {
				$bpn = "head";
				if (abetween($x, 30, 35)&&abetween($y, 10, 15)) {
					$bpn = "right eye";
				}
				else if (abetween($x, 45, 50)&&abetween($y, 10, 15)) {
					$bpn = "left eye";
				}
				if (rand(0,20)==0) $concussion = true;
			}
			else if (abetween($x, 0, 10)&&abetween($y, 15, 60)) {
				$bpn = "right front paw";
				if ($y==25) {
					$bpn = "right front leg ligament";
					$crippled = true;
				}
			}
			else if (abetween($x, 70, 80)&&abetween($y, 15, 60)) {
				$bpn = "left front paw";
				if ($y==25) {
					$bpn = "left front leg ligament";
					$crippled = true;
				}
			}
			else if (abetween($x, 0, 10)&&abetween($y, 110, 160)) {
				$bpn = "right shin";
				if ($y==150) {
					$bpn = "right achilles tendon";
					$crippled = true;
				}
			}
			else if (abetween($x, 70, 80)&&abetween($y, 110, 160)) {
				$bpn = "left shin";
				if ($y==150) {
					$bpn = "left achilles tendon";
					$crippled = true;
				}
			}
			else $bpn = "missed";
		}
		else if ($targetType==4) {
			if (abetween($x, 30, 90)&&abetween($y, 90, 160)) {
				$bpn = "torso";
				if (abetween($x, 55, 65)&&abetween($y, 90, 100)) {
					$bpn = "heart";
					$instakill = true;
				}
			}
			else if (abetween($x, 45, 75)&&abetween($y, 160, 190)) {
				$bpn = "neck";
				if ($x==46) {
					$bpn = "right carotid artery";
					$instakill = true;
				}
				if ($x==74) {
					$bpn = "left carotid artery";
					$instakill = true;
				}
			}
			else if (abetween($x, 45, 75)&&abetween($y, 190, 220)) {
				$bpn = "head";
				if (abetween($x, 50, 55)&&abetween($y, 205, 210)) {
					$bpn = "right eye";
				}
				else if (abetween($x, 65, 70)&&abetween($y, 205, 210)) {
					$bpn = "left eye";
				}
				if (rand(0,20)==0) $concussion = true;
			}
			else if (abetween($x, 30, 45)&&abetween($y, 40, 80)) {
				$bpn = "right thigh";
				if ($y==60) {
					$bpn = "right hamstring";
					$crippled = true;
				}
			}
			else if (abetween($x, 75, 90)&&abetween($y, 40, 80)) {
				$bpn = "left thigh";
				if ($y==60) {
					$bpn = "right hamstring";
					$crippled = true;
				}
			}
			else if (abetween($x, 35, 45)&&abetween($y, 0, 40)) {
				$bpn = "right shin";		
			}
			else if (abetween($x, 75, 85)&&abetween($y, 0, 40)) {
				$bpn = "left shin";	
			}
			else if (abetween($x, 10, 30)&&abetween($y, 90, 150)) {
				$bpn = "right side";
			}
			else if (abetween($x, 90, 110)&&abetween($y, 90, 150)) {
				$bpn = "left side";
			}
			else if (abetween($x, 0, 10)&&abetween($y, 60, 120)) {
				$bpn = "right wing";
			}
			else if (abetween($x, 110, 120)&&abetween($y, 60, 120)) {
				$bpn = "left wing";	
			}
			else $bpn = "missed";
		}
		
		if ($bpn=="missed") return false;
		else {
			$info = searchSingle($bodyparts, "name", $bpn);//this function is in generic
			if (!$info) para("Could not find " . $bpn . " inform developer.");
			return array($info, $instakill, $crippled, $concussion);
			//para("The attack hits the " . $bpn . ".");
		}
		//if ($instakill) para("The enemy dies almost instantly.");
		//if ($crippled) para("The attack cripples the enemy.");
		//if ($concussion) para("The enemy seems disoriented.");
		return false;
	}
	
	function checkCurrentCombat() {
		$sql = "SELECT `combat`.`uid` FROM `combat` JOIN `combat_participants` ON `combat`.`uid`=`combatFK` WHERE `combat`.`end_dt`=0 AND `objectFK`=$this->bodyId AND `leave_dt`=0 LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		return -1;
	}
	
	function initiateCombat($enemy_id) {
		$curTime = new Time($this->mysqli);
		$curCombat = $this->checkCurrentCombat();
		if ($curCombat>-1) return $curCombat;//you're already engaged in another battle
		$sql2 = "SELECT `combat`.`uid` FROM `combat` JOIN `combat_participants` ON `combat`.`uid`=`combatFK` WHERE `combat`.`end_dt`=0 AND `objectFK`=$enemy_id AND `leave_dt`=0 LIMIT 1";
		$res = $this->mysqli->query($sql2);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);//the enemy is in another combat
			$sql3 = "INSERT INTO `combat_participants` (`combatFK`, `objectFK`, `join_dt`, `join_m`) VALUES ('" . $row[0] . "', '$this->bodyId', '" . $curTime->dateTime . "', '" . $curTime->minute . "')";
			$this->mysqli->query($sql3);
			$result = $this->mysqli->insert_id;
			if ($result) return $result;
			else return -2;//Joining combat failed
		}
		$sql4 = "INSERT INTO `combat` (`start_dt`, `start_m`) VALUES ('" . $curTime->dateTime . "', '" . $curTime->minute . "')";
		$this->mysqli->query($sql4);
		$result2 = $this->mysqli->insert_id;
		if ($result2) {
			$sql5 = "INSERT INTO `combat_participants` (`combatFK`, `objectFK`, `join_dt`, `join_m`) VALUES ('$result2', '$this->bodyId', '" . $curTime->dateTime . "', '" . $curTime->minute . "'), ('$result2', '$enemy_id', '" . $curTime->dateTime . "', '" . $curTime->minute . "')";
			$this->mysqli->query($sql5);
			if ($this->mysqli->insert_id) return $result2;
			else return -2;//Joining combat failed
		}
		else return -1;//Failed to start combat
	}
	
	function receiveAttack($enemy_id, $attackType, $combatStyle) {
		//combatStyle 1 play, 2 practice, 3 punish, 4 disarm, +5 incapacitate, +6 quick kill, 7 slow death, 8 torture, 9 blind, +10 feet, +11 torso
		//combat styles marked with + are implemented against humans
		$enemy = new Obj($this->mysqli, $enemy_id);
		$enemy->getBasicData();
		$enemy->getName();
		$atype = new AnimalType($this->mysqli, $enemy->secondary);
		$result_str = "";
		$mybody = new Obj($this->mysqli, $this->bodyId);
		$mybody->getBasicData();
		
		$combat = $this->checkCurrentCombat();
		
		$bodypart = "bung";
		$weapon = "weapon";
		
		$attack_types1 = array(
			1 => "bites #VICTIM in the $bodypart",
			2 => "claws at #VICTIM''s $bodypart",
			3 => "kicks #VICTIM in the $bodypart",
			4 => "tramples #VICTIM",
			5 => "headbutts #VICTIM in the $bodypart",
			6 => "gores #VICTIM in the $bodypart with its horn",
			7 => "tears at #VICTIM''s $bodypart with its teeth",
			8 => "strangles #VICTIM",
			9 => "punches #VICTIM in the $bodypart",
			10 => "hits #VICTIM in the $bodypart with $weapon",
			11 => "throws $weapon at #VICTIM''s $bodypart",
			12 => "pecks at #VICTIM''s $bodypart"
		);
		
		$attack_types2 = array(
			1 => "bite #VICTIM",
			2 => "claw at #VICTIM",
			3 => "kick #VICTIM",
			4 => "trample #VICTIM",
			5 => "headbutt #VICTIM",
			6 => "gore #VICTIM with its horn",
			7 => "tear at #VICTIM with its teeth",
			8 => "strangle #VICTIM",
			9 => "punch #VICTIM",
			10 => "hit #VICTIM with $weapon",
			11 => "throw $weapon at #VICTIM",
			12 => "peck at #VICTIM"
		);
		
		$info = $this->bodyPartTargeter(100, 1, $combatStyle);//later on falter will depend on skills
		if (!$info) {
			$result_str = "The " . $enemy->name . " tries to " . $attack_types2[$attackType] . " but misses.";
			$this->recordCombatEvent($combat, $result_str, $enemy->uid, $this->bodyId, 2);
			return 200;
		}//Enemy missed
		//to do: what if it's not an animal?
		$severing = $atype->getAttribute(56);
		$piercing = $atype->getAttribute(57);
		$crushing = $atype->getAttribute(58);
		$broken = 0;
		$efficiency = rand(50,125);
		if ($efficiency==125&&rand(0,99)==99) $efficiency = 150;
		if ($severing>0&&($attackType==2||$attackType==7)) {
			$severing = round($severing*$efficiency/100);
			$bleed_type = 1;
			$bleed_level = round($severing/30);
			$bodypart = $info[0]["name"];
			if (bbetween($info[0]["sever"], 0, $severing)) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", severing it from the rest of #VICTIM''s body.";
				$depth = 9;
				if ($bleed_level<3) $bleed_level = 3;
				if ($info[0]["id"]<4) $info[1] = true;//instakill
				if (ibetween($info[0]["id"], 18, 27)) $info[2] = $true;//crippled
				$broken = 2;
			}
			else if (($info[0]["sever"]*0.90)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . " so that it''s hanging by a shred.";
				$depth = 8;
				if ($bleed_level<3) $bleed_level = 3;
				if (ibetween($info[0]["id"], 18, 27)) $info[2] = $true;//crippled
				$broken = 2;
			}
			else if (($info[0]["sever"]*0.55)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", severing it halfway.";
				$depth = 7;
				if ($bleed_level<3) $bleed_level = 3;
				if (ibetween($info[0]["id"], 18, 27)) $info[2] = $true;//crippled
			}
			else if (($info[0]["sever"]*0.40)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing a deep gash";
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$severing) {
					$result_str .= " and exposing bone.";
					$depth = 5;
				}
				else {
					$result_str .= ".";
					$depth = 4;
				}
				if ($bleed_level<2) $bleed_level = 2;
			}
			else if (($info[0]["sever"]*0.20)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing a considerable gash";
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$severing) {
					$result_str .=  " and exposing bone.";
					$depth = 5;
				}
				else {
					$result_str .=  ", reaching into the muscle.";
					$depth = 4;
				}
				if ($bleed_level<2) $bleed_level = 2;
			}
			else if (($info[0]["sever"]*0.10)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", reaching into the subcutaneous fat layer.";
				$depth = 3;
			}
			else if (($info[0]["sever"]*0.05)<=$severing&&$info[0]["sever"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing a shallow wound.";
				$depth = 2;
			}
			else {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", barely scratching you.";
				$depth = 1;
			}
		}
		else if ($crushing>0&&($attackType==3||$attackType==4||$attackType==5||$attackType==8||$attackType==9)) {
			$bodypart = $info[0]["name"];
			$crushing = round($crushing*$efficiency/100);
			$bleed_type = 2;
			if ($info[0]["crush"]<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", totally shattering it.";
				$depth = 8;
				$bleed_level = 6;
				$broken = 4;
				$bleed_type = 3;
			}
			else if (($info[0]["crush"]*0.75)<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", breaking it badly.";
				$depth = 7;
				$bleed_level = 5;
				$broken = 3;
			}
			else if (($info[0]["crush"]*0.50)<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing a fracture.";
				$depth = 7;
				$bleed_level = 5;
				$broken = 2;
			}
			else if (($info[0]["crush"]*0.40)<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing serious internal bleeding.";
				$depth = 5;
				$bleed_level = 4;
			}
			else if (($info[0]["crush"]*0.20)<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which will certainly cause a big bruise.";
				$depth = 4;
				$bleed_level = 3;
			}
			else if (($info[0]["crush"]*0.10)<=$crushing&&$info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which is going to leave a bruise.";
				$depth = 3;
				$bleed_level = 2;
			}
			else if ($info[0]["crush"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which might cause a small bruise.";
				$depth = 1;
				$bleed_level = 1;
			}
			else {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which is likely to cause slight internal bleeding in the surrounding tissues.";
				$depth = 2;
				$bleed_level = 2;
			}
		}
		else if ($piercing>0&&($attackType==1||$attackType==6||$attackType==12)) {
			$bodypart = $info[0]["name"];
			$piercing = round($piercing*$efficiency/100);
			$bleed_type = 1;
			if ($info[0]["pierce"]<=$piercing&&$info[0]["pierce"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which goes all the way through.";
				$depth = 6;
				$bleed_level = 6;
				if ((strpos($info[0]["name"], 'artery') !== false)||(strpos($info[0]["name"], 'heart') !== false)) $bleed_level = 10;
			}
			else if (($info[0]["pierce"]*0.50)<=$piercing&&$info[0]["pierce"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", which sinks about halfway through.";
				$depth = 4;
				$bleed_level = 5;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 9;
			}
			else if (($info[0]["pierce"]*0.40)<=$piercing&&$info[0]["pierce"]>0) {
				$result_str =  "The " . $enemy->name . " " . $attack_types1[$attackType] . " causing a deep wound";
				if ($info[0]["bone"]<0&&$info[0]["bone"]*-1<=$piercing) {
					$result_str .= " and colliding with bone.";
					$depth = 5;
				}
				else {
					$result_str .= ".";
					$depth = 4;
				}
				$bleed_level = 4;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 8;
			}
			else if (($info[0]["pierce"]*0.2)<=$piercing&&$info[0]["pierce"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . ", causing a considerable wound.";
				$depth = 3;
				$bleed_level = 3;
				if (strpos($info[0]["name"], 'heart') !== false) $bleed_level = 7;
			}
			else if (($info[0]["pierce"]*0.1)<=$piercing&&$info[0]["pierce"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . " but the damage is only superficial.";
				$depth = 2;
				$bleed_level = 2;
			}
			else if ($info[0]["pierce"]>0) {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] .  " but it doesn't hurt that much.";
				$depth = 1;
				$bleed_level = 1;
			}
			else {
				$result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . " not really causing any damage";
				$depth = 0;
			}
		}
		else $result_str = "The " . $enemy->name . " " . $attack_types1[$attackType] . " but surprisingly (or not?) it doesn't cause any damage. Maybe there''s something wrong with it?";//this shouldn't actually be possible because it shouldn't allow picking weapons that aren't weapons
		
		if (isset($bleed_type)&&isset($bleed_level)&&isset($depth)) {
			$curTime = new Time($this->mysqli);
			$sql = "INSERT INTO `wounds` (`objectFK`, `bodypart`, `depth`, `bleed_level`, `bleed_type`, `broken`, `datetime`, `minute`) VALUES ('$this->bodyId', '" . $info[0]["id"] . "', '$depth', '$bleed_level', '$bleed_type', '$broken', '" . $curTime->dateTime . "', '" . $curTime->minute . "')";
			$this->mysqli->query($sql);
			$result = $this->mysqli->insert_id;
			if (!$result) {
				//para("Something went wrong and the wound couldn't be recorded.");
				return false;
			}
		}
		
		if ($info[2]) {
			$result_str .= "#VICTIM is crippled by the attack.";
			$mybody->setAttribute(60, 1);
			$mybody->setAttribute(61, 1);//sprawled
		}
		if ($info[3]) {
			$result_str .= "#VICTIM feels lightheaded.";
			$mybody->setAttribute(59, 1);//concussion
		}
		if ($info[1]) $result_str .= "#VICTIM passes out.";
		
		
		
		$result_str = str_replace("bung", $info[0]["name"], $result_str);
		
		$this->recordCombatEvent($combat, $result_str, $enemy->uid, $this->bodyId, 2);
		
		return 100;
	}
	
	function getWeapons() {
		$body = new Obj($this->mysqli, $this->bodyId);
		$retArr = array();
		$inventory = $this->getInventory();
		if ($inventory) {
			foreach ($inventory as $possible) {
				$test = new Obj($this->mysqli, $possible);
				$test->getName();
				if ($test->type==1) {
					$sever = $test->getAttribute(56);
					$pierce = $test->getAttribute(57);
					$crush = $test->getAttribute(58);
					if ($sever||$pierce||$crush) $retArr[] = array(
						"uid" => $possible,
						"sever" => $sever,
						"pierce" => $pierce,
						"crush" => $crush
						);
				}
			}
		}
		$retArr[] = array(
			"uid" => 0,
			"sever" => false,
			"pierce" => false,
			"crush" => round($body->weight/1000)
			);
		
		return $retArr;
	}
	
	function getWeapons2() {
		$retArr = array();
		$inventory = $this->getInventory();
		if ($inventory) {
			foreach ($inventory as $possible) {
				$test = new Obj($this->mysqli, $possible);
				$name = $test->getName();
				if ($test->type==1) {
					$offense = $test->getAttribute(ATTR_OFFENSE);
					$defense = $test->getAttribute(ATTR_DEFENSE);
					if ($offense&&$defense) $retArr[] = array(
						"uid" => $possible,
						"offense" => $offense,
						"defense" => $defense,
						"name" => $name,
						"avg" => round($offense+$defense)/2
						);
				}
			}
		}
		return $retArr;
	}
	
	public function getHuntingStrength() {
		$off = 0;
		$def = 0;
		$weapons = $this->getWeapons2();
		
		aasort($weapons, "avg");
		
		$wp = array_slice($weapons, -1);
		//in the future, skills will affect
		$off = $wp[0]["offense"]/100;
		$def = $wp[0]["defense"]/100;
		
		return array(
			"offense" => $off,
			"defense" => $def
			);
	}
	
	function flee() {
		$check = $this->getCombatParticipants(false);
		if ($check==-1) {
			$bobj = new Obj($this->mysqli, $this->bodyId);
			$check2 = $bobj->leaveCombat($this->uid);
			if ($check2) return 100;
			else return -1;//failed
		}
		if (rand(1,3)==1) {
			$bobj = new Obj($this->mysqli, $this->bodyId);
			$check2 = $bobj->leaveCombat($this->uid);
			if ($check2) return 100;
			else return -1;//failed
		}
		else return -2;//the enemy is stopping you from leaving
	}
	
	function getCombatParticipants($self) {
		$combat = $this->checkCurrentCombat();
		if ($combat == -1) return -1;
		$retArr = array();
		$curTime = new Time($this->mysqli);
		if (!$self) $sql = "SELECT `objectFK` FROM `combat_participants` WHERE (`join_dt`<".$curTime->dateTime." OR (`join_dt`=".$curTime->dateTime." AND `join_m`<=".$curTime->minute.")) AND `leave_dt`=0 AND `combatFK`=$combat AND `objectFK`<>$this->bodyId";
		else $sql = "SELECT `objectFK` FROM `combat_participants` WHERE (`join_dt`<".$curTime->dateTime." OR (`join_dt`=".$curTime->dateTime." AND `join_m`<=".$curTime->minute.")) AND `leave_dt`=0 AND `combatFK`=$combat";
		
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		else return -1;
	}
	
	function validateInventoryItem($item) {
		$curTime = new Time($this->mysqli);
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
		$res = $this->mysqli->query($sql);
		if ($res) {
			return true;
		}
		else return false;
	}
	
	function processRound($enemy, $type, $wpn) {
		$valid_enemy = $this->getCombatParticipants(false);
		if ($valid_enemy==-1) return -1;//combat participants not registered or not engaged in combat
		if (!in_array($enemy, $valid_enemy)) return -2;//trying to attack someone who isn't a participant in this combat (or only joins in your future)
		if (!$this->validateInventoryItem($wpn)) return -3;//Trying to use a weapon not in your inventory
		if ($type<5||$type>6) return -4;//This combat type hasn't been implemented
		
		$combat = $this->checkCurrentCombat();
		$mybody = new Obj($this->mysqli, $this->bodyId);
		$mybody->getBasicData();
		
		$enemy_o = new Obj($this->mysqli, $enemy);
		$enemy_o->getName();
		
		$blood_left2 = $mybody->calculateBlood($this->uid);
		if ($blood_left2<=$mybody->weight*0.06) {
			$this->recordCombatEvent($combat, "#VICTIM is unconscious, unable to defend against the attacker.", $enemy_o->uid, $this->bodyId, 2);
			$mybody->setAttribute(61, 1);
		}
		else if ($blood_left2<=$mybody->weight*0.07&&rand(1,4)==1) {
			$this->recordCombatEvent($combat, "#VICTIM blacks out momentarily, unable to hit this round.", $enemy_o->uid, $this->bodyId, 2);
			$mybody->setAttribute(61, 1);
		}
		else {
			if ($blood_left2<=$mybody->weight*0.07) $this->recordCombatEvent($combat, "#VICTIM staggers, feeling lightheaded but still manages to keep fighting.", $enemy_o->uid, $this->bodyId, 2);
			$res = $this->attackEnemy($enemy, $type, $wpn);
			if (is_array($res)) return $res;
		}
		
		$down = $mybody->getAttribute(61);//sprawled
		
		$bleed1 = $enemy_o->sumWounds($this->uid);
		if ($bleed1>0) $enemy_o->bleed($bleed1, $this->uid);
		
		$blood_left1 = $enemy_o->calculateBlood($this->uid);
		
		
		if ($blood_left1<=$enemy_o->weight*0.051) {
			if ($enemy_o->type==2) $this->recordCombatEvent($combat, "#VICTIM dies from their injuries.", $this->bodyId, $enemy_o->uid, 3);
			else $this->recordCombatEvent($combat, "The $enemy_o->name dies from its injuries.", $this->bodyId, $enemy_o->uid, 4);
			$enemy_o->leaveCombat($this->uid);
			$enemy_o->perish($this->uid);
			return 300;
		}
		else if ($blood_left1<=$enemy_o->weight*0.060) {
			if ($enemy_o->type==2) $this->recordCombatEvent($combat, "#VICTIM is unconscious due to bloodloss, unable to defend themselves.", $this->bodyId, $enemy_o->uid, 3);
			else $this->recordCombatEvent($combat, "The $enemy_o->name is unconscious due to bloodloss, unable to defend itself.", $this->bodyId, $enemy_o->uid, 4);
			return 400;
		}
		else if ($blood_left1<=$enemy_o->weight*0.070&&rand(1,4)==1) {
			if ($enemy_o->type==2) $this->recordCombatEvent($combat, "#VICTIM momentarily blacks out, unable to attack this round.", $this->bodyId, $enemy_o->uid, 3);
			else $this->recordCombatEvent($combat, "The $enemy_o->name momentarily blacks out, unable to attack this round.", $this->bodyId, $enemy_o->uid, 4);
			return 400;
		}
		else if ($blood_left1<=$enemy_o->weight*0.070) {
			if ($enemy_o->type==2) $this->recordCombatEvent($combat, "#VICTIM staggers.", $this->bodyId, $enemy_o->uid, 3);
			else $this->recordCombatEvent($combat, "The $enemy_o->name staggers.", $this->bodyId, $enemy_o->uid, 4);
		}
		
		$result = $enemy_o->attackCharacter($this->uid, $down);
		if ($result==-1) return -5;
		if ($result==-2) return -6;
		if ($result==-3) return -7;
		if ($result==-4) return -8;
		
		$bleed2 = $mybody->sumWounds($this->uid);
		if ($bleed2>0) $mybody->bleed($bleed2, $this->uid);
		$blood_left2 = $mybody->calculateBlood($this->uid);
		if ($blood_left2<=$mybody->weight*0.051) {
			$this->recordCombatEvent($combat, "#VICTIM dies from ".$this->getPronoun()." injuries.", $enemy_o->uid, $this->bodyId, 2);
			$mybody->leaveCombat($this->uid);
			$mybody->perish($this->uid);
			return 200;
		}
		
		return 100;
	}
	
	function getPoolToolsInventory($pool) {
		$inventory = array();
		$sql = "SELECT `objects`.`uid` FROM `objects` WHERE `presetFK` IN (SELECT `toolFK` FROM `pool_tools` WHERE  `poolFK`=$pool) AND `parent`=$this->bodyId";
		$res = $this->mysqli->query($sql);
		if ($res) {
			while ($row = mysqli_fetch_row($res)) {
				$inventory[] = $row[0];
			}
			return $inventory;
		}
		else return false;
	}
	
	public function changeStatus($newstatus) {
		$sql = "UPDATE `chars` SET `status`=$newstatus WHERE `uid`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) return true;
		else return false;//Fail
	}
	
	public function searchGroups() {
		$apcheck = $this->checkAP(50);//50 is the solid amount of AP it takes to search for groups currently
		if ($apcheck == -2) {
			$this->spendAP(50);
			$local = new LocalMap($this->mysqli, $this->x, $this->y);
			$pcs = $local->countPCpresent();
			$groups = $local->getGroups();
			if (!$groups) $chance = max(10-$pcs, 4);
			else $chance = max(10-$pcs, 4) + sizeof($groups);
			
			if (rand(0, $chance)==0) {
				$new = new NPCgroup($this->mysqli);
				$new->create($this->x, $this->y, $this->localx, $this->localy);
				return 100;//success
			}
			else return -1;//Failed to find anything this time
		}
		else return -3;//not enough AP
	}
	
	public function analyzeTravels() {
		$coords = $this->visitedCoords(true, true);//accurate, non-graphic
		if (empty($coords)) return 0;
		echo "<table>";
		echo "<tr><th>X</th><th>Y</th><th>Tree lvl</th><th>Bush lvl</th><th>Grass lvl</th><th>Rocks</th><th>Organic</th><th>Water lvl</th></tr>";
		foreach ($coords as $c) {
			$loc = new GlobalMap($this->mysqli, $c["x"], $c["y"]);
			
			$row = $loc->getROWlevel();
			$vege = $loc->getVegeLevel();
			
			echo "<tr><td>" . $c["x"] . "</td><td>" . $c["y"] . "</td><td>" . $this->verbalizeEnvLevels($vege["blue"]) . "</td><td>" . $this->verbalizeEnvLevels($vege["green"]) . "</td><td>" . $this->verbalizeEnvLevels($vege["red"]) . "</td><td>" . $this->verbalizeEnvLevels($row["red"]) . "</td><td>" . $this->verbalizeEnvLevels($row["green"]) . "</td><td>" . $this->verbalizeEnvLevels($row["blue"]) . "</td></tr>";
		}
		echo "</table>";
		para("The maximum you can remember is 55 locations");
		return 1;
	}
	
	public function verbalizeEnvLevels($level) {
		switch ($level) {
		case 0:
			$str = getColorSpan($level, "Low to none");
			break;
		case 1:
			$str = getColorSpan($level, "Very low");
			break;
		case 2:
			$str = getColorSpan($level, "Low");
			break;
		case 3:
			$str = getColorSpan($level, "So-so");
			break;
		case 4:
			$str = getColorSpan($level, "Average");
			break;
		case 5:
			$str = getColorSpan($level, "Rather High");
			break;
		case 6:
			$str = getColorSpan($level, "High");
			break;
		case 7:
			$str = getColorSpan($level, "Very High");
			break;
		case 8:
			$str = getColorSpan($level, "Almost maximal");
			break;
		case 9:
			$str = getColorSpan($level, "Maximal");
			break;
		}
		return $str;
	}
	
	public function getMemo() {
		$sql = "SELECT `uid`, `txt` FROM `memo` WHERE `char`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return array(
				"row" => $row[0],
				"txt" => $row[1]
				);
		}
		return -1;
	}
	
	function saveMemo($txt) {
		$info = $this->getMemo();
		if ($info==-1) {
			$sql = "INSERT INTO `memo` (`uid`, `char`, `txt`) VALUES (NULL, '$this->uid', '$txt')";
			$this->mysqli->query($sql);
			$result = $this->mysqli->insert_id;
			if ($result) return 1;
			else return -1;
		}
		$sql = "UPDATE `memo` SET `txt`='$txt' WHERE `char`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			return -2;
		}
		else return 1;
	}
	
	function createTravelGroup() {
		$exist = $this->getTravelGroup();
		if ($exist>0) return $exist;
		$curTime = new Time($this->mysqli);
		$group = new Obj($this->mysqli);
		$res = $group->create(2, 7, $this->bodyId, "travel group", "NULL", "NULL", 0, 0, 0, 0, 0, $curTime->dateTime, $curTime->minute);
		return $res;
	}
	
	function getTravelGroup() {
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `general_type`=7 ORDER BY `uid` LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return -1;
	}
	
	function getCharRule($obj, $rule) {
		//rule: 1 - right to command, 2 - right to join
		$sql = "SELECT `value` FROM `char_rules` WHERE `objFK`=$obj AND `charFK`=$this->uid AND `rule`=$rule LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return -1;
	}
	
	function updateCharRule($obj, $rule, $value) {
		//rule: 1 - right to command, 2 - right to join
		$sql = "UPDATE `char_rules` SET `value`=$value WHERE `objFK`=$obj AND `rule`=$rule and `charFK`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			if ($this->getCharRule($obj, $rule)>-1) return -2;//The status was already the same
			
			$sql = "INSERT INTO `char_rules` (`uid`, `objFK`, `charFK`, `rule`, `value`) VALUES (NULL, '$obj', '$this->uid', '$rule', '$value')";
			$this->mysqli->query($sql);
			$result = $this->mysqli->insert_id;
			if ($result) return 1;//insert success
			else return -1;
		}
		return 2;//update success
	}
	
	function exitObject() {
		if ($this->building == 0) return -1;//you are already outside
		$obj = new Obj($this->mysqli, $this->building);
		//To-do: in the future, check if object is locked and if yes, does the person have a key
		$coords = $obj->getExitCoordinates();
		
		$sql = "UPDATE `objects` SET `global_x`=".$coords["x"].", `global_y`=".$coords["y"].", `local_x`=".$coords["lx"].", `local_y`=".$coords["ly"].", `parent`=0 WHERE `uid`=$this->bodyId LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			return -1;
		}
		else {
			$this->x = $coords["x"];
			$this->y = $coords["y"];
			$this->localx = $coords["lx"];
			$this->localy = $coords["ly"];
			$this->building = 0;
			$this->updateCharLocTime($coords["x"], $coords["y"], $coords["lx"], $coords["ly"], 0, 2, 0);
			return 1;//success
		}
	}
	
	function enterObject($target, $method) {
		//in case of groups, method is "join"
		//for buildings, it's "enter"
		$obj = new Obj($this->mysqli, $target);
		$rule = $obj->getGroupRule($method);
		if (!$rule) return -1;//nobody can join
		if ($rule==2) {
			//invitation only
			$cr = $this->getCharRule($target, 2);//right to join
			if ($cr<1) return -2;//You can't join
			//otherwise it's okay to continue
		}
		//rule is 2 and you are invited OR rule is 1 and everybody is invited
		$sql = "UPDATE `objects` SET `global_x`=NULL, `global_y`=NULL, `local_x`=0, `local_y`=0, `parent`=$target WHERE `uid`=$this->bodyId LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			return -3;//enter failed
		}
		else {
			$this->x = NULL;
			$this->y = NULL;
			$this->localx = 0;
			$this->localy = 0;
			$this->building = $target;
			$this->updateCharLocTime(NULL, NULL, 0, 0, $target, 2, 0);
			return 1;//success
		}
	}
	
	function getOtherBodies() {
		$retArr = array();//this doesn't support buildings
		$sql = "SELECT `uid` FROM `objects` WHERE `uid`<>$this->bodyId AND " . $this->getCoordsForSQL() . " AND `general_type`=2 ORDER BY `uid` LIMIT 1";
		
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		else return -1;
	}
	
	function getOtherTravelGroups() {
		$ppl = $this->getOtherBodies();
		if ($ppl==-1) return -1;//There are no other people
		$retArr = array();
		$searchstr = arrayToComma($ppl);
		$sql = "SELECT `uid` FROM `objects` WHERE `parent` IN ($searchstr) AND `general_type`=7 ORDER BY `uid` LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		else return -2;//The other people have no groups
	}
	
	function getCoordsForSQL($strict = false) {
		//to be used in objects table
		if (is_null($this->x)) {
			$pos = $this->getPosition();
			if (!$strict) return "`global_x`=$pos->x AND `global_y`=$pos->y";
			return "`global_x`=NULL AND `global_y`=NULL";
		}
		return "`global_x`=$this->x AND `global_y`=$this->y";
	}
	
	function getCoordsForSQL2($strict = false) {
		//to be used in projects or others
		if (is_null($this->x)) {
			$pos = $this->getPosition();
			if (!$strict) return "`x`=$pos->x AND `y`=$pos->y";
			return "`x`=NULL AND `y`=NULL";
		}
		return "`x`=$this->x AND `y`=$this->y";
	}
	
	function getCoordsForSQL3($strict = false) {
		//to be used in objects table where local x matters
		if (is_null($this->x)) {
			$pos = $this->getPosition();
			if (!$strict) return "`global_x`=$pos->x AND `global_y`=$pos->y AND `local_x`=$pos->lx AND `local_y`=$pos->ly";
			return "`global_x`=NULL AND `global_y`=NULL";
		}
		return "`global_x`=$this->x AND `global_y`=$this->y AND `local_x`=$this->localx AND `local_y`=$this->localy";
	}
	
	function getCoordsForSQL4($strict = false) {
		//to be used in projects or others where local x matters
		if (is_null($this->x)) {
			$pos = $this->getPosition();
			if (!$strict) return "`x`=$pos->x AND `y`=$pos->y AND `local_x`=$pos->lx AND `local_y`=$pos->ly";
			return "`x`=NULL AND `y`=NULL";
		}
		return "`x`=$this->x AND `y`=$this->y AND `local_x`=$this->localx AND `local_y`=$this->localy";
	}
	
	function getPosition() {
		if ($this->building>0) {
			$o = new Obj($this->mysqli, $this->building);
			$coords = $o->getExitCoordinates();
			$pos = new Position($this->mysqli, $coords["x"], $coords["y"], $coords["lx"], $coords["ly"]);
			return $pos;
		}
		$pos = new Position($this->mysqli, $this->x, $this->y, $this->localx, $this->localy);
		return $pos;
	}
	
	function sameGenericLocation($cx, $cy, $exact=false, $clx=0, $cly=0) {
		$pos = $this->getPosition();
		if ($pos->x==$cx&&$pos->y==$cy) {
			if (!$exact) return true;//local coords don't mattere
			if ($pos->lx==$clx&&$pos->ly==$cly) return true;
			else return false;
		}
		else return false;
	}
	
	function printNameLink($ocharid) {
		ptag("a", $this->getDynamicName($ocharid), "href='index.php?page=formCharName&charid=$this->uid&ocharid=" . $ocharid . "' class='clist'");
	}
	
	function getNameLink($ocharid) {
		return "<a href='index.php?page=formCharName&charid=$this->uid&ocharid=" . $ocharid . "' class='clist'>" . $this->getDynamicName($ocharid) . "</a>";
	}
	
	function getRestAP($dbonly = true) {
		//This prevents it from reading terrains from an image if they don't exist in the db
		//automatic resting is triggered once per real life hour
		//Starts from 250 AP/rl hour
		$ap_rec = 250;
		$pos = $this->getPosition();
		$curTime = new Time($this->mysqli); 
		$local = new LocalMap($this->mysqli, $pos->x, $pos->y);
		$weather = $curTime->getWeather($pos->x, $pos->y, $dbonly);
		$bodyObj = new Obj($this->mysqli, $this->bodyId);
		$blood_per = $bodyObj->getBloodPercentage($this->uid);
		
		//-10 if you don't have a bed
		$beds = $local->checkBed($pos->lx, $pos->ly);
		if ($beds == -1) $ap_rec -= 10;
		//-10 if you don't have shelter
		$ap_rec -= 10;//No shelter has been implemented yet
		//-10 if it's raining and you don't have shelter
		if ($weather["rain"]>0) {
			$ap_rec -= 10;
		}
		//-10 if you are cold
		if ($weather["temp"]<16) {
			$minus = max($weather["temp"]-16,-10);
			$ap_rec += $minus;
		}
		//-10 if you are too hot
		if ($weather["temp"]>36) {
			$plus = min(($weather["temp"]-36)*2,10);
			$ap_rec -= $plus;
		}
		//-10 if you're hungry
		//Hunger isn't implemented yet
		//-10 if you are wounded
		if ($blood_per<95) {
			$minus = round(($blood_per-96)/2);
			$ap_rec += $minus;
		}
		
		return $ap_rec;
	}
	
	public function rest_auto() {
		$oldAP = $this->getAP();
		if ($oldAP>=1000) return 0;
		$ap = $this->getRestAP();
		if ($oldAP+$ap>1000) $ap = max(0, 1000-$oldAP);
		$pos = $this->getPosition();
		
		$this->updateCharLocTime($pos->x, $pos->y, $pos->lx, $pos->ly, 0, 5, $ap);
		
		if ($oldAP==-1) {
		$sql = "INSERT INTO `char_ap` (`rowID`, `charFK`, `ap`) VALUES (NULL, '$this->uid', '$ap')";
		$this->mysqli->query($sql);
		}
		else if ($ap>0) {
		$sql = "UPDATE `char_ap` SET `ap`=`ap`+$ap WHERE `charFK`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		}
		return $ap;
	}
	
	public function requestRole($sex, $age, $namesel, $name="", $desc="", $req="", $why="") {
		if ($namesel) $sql = "INSERT INTO `requests` (`requester`, `sex`, `age`, `namesel`, `desc`, `req`, `why`) VALUES ($this->uid, $sex, $age, 1, '$desc', '$req', '$why')";
		else $sql = "INSERT INTO `requests` (`requester`, `sex`, `age`, `namesel`, `name`, `desc`, `req`, `why`) VALUES ($this->uid, $sex, $age, 0, '$name', '$desc', '$req', '$why')";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			return $result;
		}
		else return -1;
	}
	
	public function updateRequest($request, $sex, $age, $namesel, $name="", $desc="", $req="", $why="") {
		if ($namesel) $sql = "UPDATE `requests` SET `sex`=$sex, `age`=$age, `namesel`=1, `desc`='$desc', `req`='$req', `why`='$why' WHERE `requester`=$this->uid AND `filler`=0 AND `uid`=$request LIMIT 1";
		else $sql = "UPDATE `requests` SET `sex`=$sex, `age`=$age, `namesel`=0, `name`='$name', `desc`='$desc', `req`='$req', `why`='$why' WHERE `requester`=$this->uid AND `filler`=0 AND `uid`=$request LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			return $request;
		}
		else return -2;
	}
	
	public function getActiveRequest() {
		$sql = "SELECT `uid`, `sex`, `age`, `namesel`, `name`, `desc`, `req`, `why` FROM `requests` WHERE `requester`=$this->uid AND `filler`=0 ORDER BY `uid` DESC LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_assoc($res);
			return $row;
		}
		return -1;
	}
	
	public function deleteRequest($request) {
		$sql = "DELETE FROM `requests`  WHERE `requester`=$this->uid AND `filler`=0 AND `uid`=$request LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			return 1;
		}
		else return -1;
	}
	
	public function claimRole($roleId) {
		$sql = "UPDATE `requests` SET `filler`='$this->uid' WHERE `uid`=$roleId AND `filler`=0 LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			return true;
		}
		else return false;
	}
	
	public function killOff() {
		$bo = new Obj($this->mysqli, $this->bodyId);
		return $bo->perish();
	}
	
	public function countOtherInTG($livingOnly=true) {
		$tg = $this->getTravelGroup();
		if ($tg>0) {
			$to = new Obj($this->mysqli, $tg);
			$pas = $to->getPassengers($livingOnly);
			if (is_array($pas)) return sizeof($pas);
			else return 0;
		}
		else return -1;//Has no travel group
	}
	
	public function otherIdsInTG($livingOnly=true) {
		$tg = $this->getTravelGroup();
		if ($tg>0) {
			$to = new Obj($this->mysqli, $tg);
			$pas = $to->getPassengers($livingOnly);
			if (is_array($pas)) {
				$retArr = array();
				foreach ($pas as $bodyid) {
					$bo = new Obj($this->mysqli, $bodyid);
					if ($bo>0) $retArr[] = $bo->getCharid();
				}
				if (empty($retArr)) return -2;//There is an error
				else return $retArr;
			}
			else return 0;
		}
		else return -1;//Has no travel group
	}
	
	public function allIdsInTG() {
		$tg = $this->getTravelGroup();
		if ($tg>0) {
			$to = new Obj($this->mysqli, $tg);
			$pas = $to->getPassengersInc();
			if (is_array($pas)) {
				return $pas;
			}
			else return $pas;//errorcode
		}
		else return -3;//Has no travel group
	}
	
	public function printEventLog($starttime = 1010100) {
		$sql = "SELECT `events`.`uid`, `etype`, `tags`, `custom`, `timestamp`, `minute` FROM `e_witness` JOIN `events` ON `event`=`events`.`uid` WHERE `charid`=$this->uid AND `timestamp`>=$starttime ORDER BY `timestamp`, `minute`, `events`.`uid`";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$e = new Event($this->mysqli, $this->uid);
				$e->fillData($row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
				$e->show();
			}
		}
		else para("There are no events to show.");
	}
}


?>
