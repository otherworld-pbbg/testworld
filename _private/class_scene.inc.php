<?php
include_once("class_time.inc.php");

class Scene {
	private $mysqli;
	public $uid;
	public $privacy=1;
	public $x=0;
	public $y=0;
	public $localx=0;
	public $localy=0;
	public $indoors=0;
	public $startTime=0;
	public $startMinute=0;
	public $endTime=0;
	public $endMinute=0;
	public $title="";
	public $desc="";
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
	}
	
	public function create($priv, $gx, $gy, $lx, $ly, $indoors, $startT, $startM, $endT, $endM, $title, $desc, $starter) {
		$sql = "INSERT INTO `scenes` (`sceneID`, `privacy`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startTime`, `startMinute`, `endTime`, `endMinute`, `title`, `description`) VALUES (NULL, '$priv', '$gx', '$gy', '$lx', '$ly', '$indoors', '$startT', '$startM', '$endT', '$endM', '$title', '$desc');";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->uid = $result;
			$this->privacy = $priv;
			$this->x=$gx;
			$this->y=$gy;
			$this->localx=$lx;
			$this->localy=$ly;
			$this->indoors=$indoors;
			$this->startTime=$startT;
			$this->startMinute=$startM;
			$this->endtime=$endT;
			$this->endMinute=$endM;
			$this->title=$title;
			$this->desc=$desc;
			
			$this->addParticipant($startT, $startM, $starter, 1);//At this point it won't announce if this query fails but if it does, the initiator won't be recorded
			//if the scene is private, nobody will be able to see it and it will exist as a ghost until removed
			return $result;
		}
		else return -1;
	}
	
	public function loadValues() {
		$sql = "SELECT `privacy`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startTime`, `startMinute`, `endTime`, `endMinute`, `title`, `description` FROM `scenes` WHERE `sceneID`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->privacy = $row[0];
			$this->x=$row[1];
			$this->y=$row[2];
			$this->localx=$row[3];
			$this->localy=$row[4];
			$this->indoors=$row[5];
			$this->startTime=$row[6];
			$this->startMinute=$row[7];
			$this->endTime=$row[8];
			$this->endMinute=$row[9];
			$this->title=$row[10];
			$this->desc=$row[11];
			return 1;
		}
		else return -1;
	}
	
	public function addParticipant($joinTime, $joinMinute, $char, $role) {
		$already = $this->getParticipantStatus($char);
		if ($already>0||$already==-5) return false;
		else
		{
			$firstRead = $this->getCurrentRow();
			if ($role==3) {
				$sql = "UPDATE `scene_chars` SET `joinTime`='$joinTime', `joinMinute`='$joinMinute', `role`='2', `firstReadFK`='$firstRead', `lastReadFK`='$firstRead' WHERE `charFK`=$char AND `sceneFK`=$this->uid LIMIT 1";
				$this->mysqli->query($sql);
				$result = $this->mysqli->insert_id;	
				$sql2 = "INSERT INTO `scene_events` (`eventID`, `sceneFK`, `actorFK`, `dateTime`, `minute`, `type`, `contents`) VALUES (NULL, '$this->uid', '$char', '$joinTime', '$joinMinute', '2', '')";
				$this->mysqli->query($sql2);
				$res = $this->mysqli->insert_id;
				if (!$res) return false;
				if ($result) return $result;
				else return false;
			}
			else {
				$sql = "INSERT INTO `scene_chars` (`rowID`, `sceneFK`, `charFK`, `joinTime`, `joinMinute`, `leftTime`, `leftMinute`, `role`, `firstReadFK`, `lastReadFK`) VALUES (NULL, '$this->uid', '$char', '$joinTime', '$joinMinute', '0', '0', '$role', '$firstRead', '$firstRead');";
				$this->mysqli->query($sql);
				$result = $this->mysqli->insert_id;
				if ($role<4) {
					$sql2 = "INSERT INTO `scene_events` (`eventID`, `sceneFK`, `actorFK`, `dateTime`, `minute`, `type`, `contents`) VALUES (NULL, '$this->uid', '$char', '$joinTime', '$joinMinute', '2', '')";
					$this->mysqli->query($sql2);
					$res = $this->mysqli->insert_id;
					if (!$res) return false;
				}
				
				if ($result) return $result;
				else return false;
			}
			
		}
	}
	
	public function getCurrentRow() {
		$sql = "SELECT max(`eventID`) FROM `scene_events` WHERE `sceneFK`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return 0;
	}
	
	public function removeParticipant($dateTime, $minute, $char) {
		$role = $this->getParticipantStatus($char);
		if ($role<1) return 0;//the person has already left
		else
		{
			if ($role==5) $newrole = -4;
			else $newrole = -$role;
			$sql = "UPDATE `scene_chars` SET `leftTime`=$dateTime, `leftMinute`=$minute, `role`=$newrole WHERE `sceneFK`=$this->uid AND `charFK`=$char AND `leftTime`=0";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows) {
				if ($role!=4) $this->addEvent($char, $dateTime, $minute, 3, '');
				return 1;
			}
			else return -1;
		}
	}
	
	public function getChars($curTime, $curMinute, $roleLimit) {
		//roleLimit 1 - not eavesdroppers (4), 2 - all
		$extra = "";
		if ($roleLimit==1) $extra=" AND `role`<>4";
		$retArr = array();
		$sql = "SELECT `rowID`, `charFK` FROM `scene_chars` WHERE `sceneFK`=$this->uid AND (`leftTime`=0 OR `leftTime`>$curTime OR (`leftTime`=$curTime AND `leftMinute`>$curMinute)) AND (`joinTime`<$curTime OR (`joinTime`=$curTime AND `joinMinute`<=$curMinute)$extra) ORDER BY `joinTime`, `joinMinute`, `charFK`";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = array("rowID" => $row[0], "charID" => $row[1]);
			}
			return $retArr;
		}
		else return false;
	}
	
	public function getEventsSpan($startTime, $startMinute, $endTime, $endMinute, $firstRead, $lastRead) {
		$retArr = array();
		$sql = "SELECT `eventID`, `actorFK`, `dateTime`, `minute`, `type`, `contents` FROM `scene_events` WHERE `sceneFK`=$this->uid AND `eventID`<=$lastRead AND `eventID`>$firstRead AND (`dateTime`>$startTime OR (`dateTime`=$startTime AND `minute`>=$startMinute)) AND (`dateTime`<$endTime OR (`dateTime`=$endTime AND `minute`<=$endMinute)) ORDER BY `dateTime`, `minute`, `eventID`";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = array(
					"rowID" => $row[0],
					"charID" => $row[1],
					"dateTime" => $row[2],
					"minute" => $row[3],
					"type" => $row[4],
					"contents" => $row[5]
					);
			}
			return $retArr;
		}
		else return false;
	}
	
	public function getEventsOpenEnded($startTime, $startMinute, $firstRead) {
		$sql = "SELECT `eventID`, `actorFK`, `dateTime`, `minute`, `type`, `contents` FROM `scene_events` WHERE `sceneFK`=$this->uid AND `eventID`>$firstRead AND (`dateTime`>$startTime OR (`dateTime`=$startTime AND `minute`>=$startMinute)) ORDER BY `dateTime`, `minute`, `eventID`";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = array(
					"rowID" => $row[0],
					"charID" => $row[1],
					"dateTime" => $row[2],
					"minute" => $row[3],
					"type" => $row[4],
					"contents" => $row[5]
					);
			}
			return $retArr;
		}
		else return false;
	}
	
	public function addEvent($actor, $curTime, $curMinute, $type, $contents) {
		$sql = "INSERT INTO `scene_events` (`eventID`, `sceneFK`, `actorFK`, `dateTime`, `minute`, `type`, `contents`) VALUES (NULL, '$this->uid', '$actor', '$curTime', '$curMinute', '$type', '$contents');";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result&&$type!=4) {
			$role = $this->getParticipantStatus($actor);
			if ($role==4) $this->revealEavesdropper($actor);
			return $result;
		}
		else return false;
	}
	
	public function getParticipantStatus($char) {
		$sql = "SELECT `role` FROM `scene_chars` WHERE `charFK`=$char AND `sceneFK`=$this->uid ORDER BY `rowID` DESC LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return 0;
	}
	
	public function updateParticipantStatus($char, $role) {
		$sql = "UPDATE `scene_chars` SET `role`=$role WHERE `sceneFK`=$this->uid AND `charFK`=$char AND `leftTime`=0 LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows) return 1;
		else return false;
	}
	
	public function revealEavesdropper($char) {
		$res = false;
		$role = $this->getParticipantStatus($char);
		$curTime = $this->getInternalTime();
		if ($role==4) {
			$res = $this->addEvent($char, $curTime['dateTime'], $curTime['minute'], '4', '');
			$this->updateParticipantStatus($char, '5');
		}
		return $res;
	}
	
	public function getInternalTime() {
		$localMinute = 0;
		$localTime = 0;
		
		$sql = "SELECT max(`dateTime`) AS `highday`, max(`minute`) AS `highminute`, count(`eventID`) AS `num` FROM `scene_events` WHERE `sceneFK`=$this->uid GROUP BY `dateTime`, `minute` ORDER BY highday DESC, highminute DESC";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			if ($row[2]>=12) {
				if ($row[1]>58) {
					$localMinute = 0;//rotate to next hour
					$timeObj = new Time($this->mysqli, $row[0], $row[1]);
					$localTime = $timeObj->increaseByHour();
				}
				else {
				$localMinute = $row[1]+1;
				$localTime = $row[0];
				}
			}
			else {
				$localMinute = $row[1];
				$localTime = $row[0];
			}
			return array(
				"dateTime" => $localTime,
				"minute" => $localMinute
				);
		}
		else return array(
				"dateTime" => $this->startTime,
				"minute" => $this->startMinute
				);
	}
	
	public function getLastID() {
		$sql="SELECT max(`eventID`) FROM `scene_events` WHERE `sceneFK`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return 0;
	}
	
	public function getLastRead($char, $row) {
		$sql = "SELECT `lastReadFK` FROM `scene_chars` WHERE `charFK`=$char AND `sceneFK`=$this->uid AND `rowID`=$row LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return 0;
	}
	
	public function updateLastRead($char, $lastSeen) {
		$sql = "UPDATE `scene_chars` SET `lastReadFK`=$lastSeen WHERE `charFK`=$char AND `sceneFK`=$this->uid AND `leftTime`=0 LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows) return 1;
		else return false;
	}
}

?>
