<?php
include_once("class_character.inc.php");
include_once("class_time.inc.php");

class Player {
	private $mysqli;
	public $uid;
	public $username = "";
	public $email = "";
	public $joined;
	public $passhash = "";
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($uid>0) $this->getInfo();
	}
	
	function getInfo() {
		$sql = "SELECT `username`, `email`, `joined`, `passhash` FROM `users` WHERE `uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)==1) {
			$row = mysqli_fetch_row($res);
			$this->username = $row[0];
			$this->email = $row[1];
			$this->joined = $row[2];
			$this->passhash = $row[3];
			return true;
		}
		return false;
	}
	
	function getUsername() {
		return $this->username;
	}
	
	function getCharacters($role) {
	//role: 1 - player, 2 - watcher, 3 - invisible watcher (usually for staff monitoring purposes)
		$chars = array();
		$res = $this->mysqli->query("SELECT `chars`.`uid` AS 'cuid', `cname` FROM `chars` JOIN `char_players` on `chars`.`uid`=`charFK` WHERE `userFK`='$this->uid' AND `role`='$role' AND `terminated` IS NULL");
		if (!$res) {
			return false;
		}
		else {
			while ($row = $res->fetch_object()) {
				$chars[] = array("cuid" => $row->cuid, "cname" => $row->cname);
			}
			return $chars;
		}
	}
	
	function spawnCharacter($zone, $sex, $age, $cname, $companion = 0) {
		$new = new Character($this->mysqli, 0);
		$gameTime = new Time($this->mysqli);
		$res = $new->create($sex, $gameTime->dateTime, $age, $cname);
		if ($res == -1) return -2;//Character creation failed
		if ($companion>0) {
			$ochar = new Character($this->mysqli, $companion);
			$pos = $ochar->getPosition();
			$res = $new->createBody($age, $pos->x, $pos->y, $gameTime->dateTime, $gameTime->minute, $pos->lx, $pos->ly);
		}
		else {
			$loc = $this->getSpawnLoc($zone);
			if ($loc == -1) return -3;//no spawning locs for this zone
			$vari_x = (rand(0, 10) - 5)*4;
			$vari_y = (rand(0, 10) - 5)*4;
			$loc["x"] += $vari_x;
			$loc["y"] += $vari_y;
			$lx = rand(0,99)*10;
			$ly = rand(0,99)*10;
			$res = $new->createBody($age, $loc["x"], $loc["y"], $gameTime->dateTime, $gameTime->minute, $lx, $ly);
		}
		if ($res == -1) return -4;//Body creation failed
		else if ($res == -2) return -5;//Linking body to spirit failed
		$res2 = $new->setListener($this->uid, 1);
		if ($res2 == -1) return -6;//Assigning listener failed
		else {
			$body = new Obj($this->mysqli, $res);
			$body->getBasicData();
			$body->calculateBlood();
			$new->changeStatus(1);
			return $new->uid;
		}
	}
	
	function fillRole($roleId, $sex, $cname) {
		$requirements = $this->getRole($roleId);
		if ($requirements == -1) return -1;//Invalid id
		
		$rq = new Character($this->mysqli, $requirements["requester"]);
		$relation = $rq->checkPermission($this->uid);
		if ($relation == 1) return -8;//You tried to fill a role requested by one of your own characters
		
		if ($requirements["namesel"]==0) $cname = $requirements["name"];
		if ($requirements["sex"]<4) $sex = $requirements["sex"];
		$result = $this->spawnCharacter(0, $sex, $requirements["age"], $cname, $requirements["requester"]);
		if ($result<0) return $result;
		$newchar = new Character($this->mysqli, $result);
		$res2 = $newchar->claimRole($roleId);
		if (!$res2) return -7;
		return $result;
	}
	
	function getRole($roleId) {
		$sql = "SELECT `requester`, `sex`, `age`, `namesel`, `name`, `desc`, `req`, `why` FROM `requests` WHERE `uid`=$roleId AND `filler`=0 LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_assoc($res);
			return $row;
		}
		return -1;
	}
	
	function getSpawnLoc($zone) {
		$arr = array();
		$sql = "SELECT `x`, `y` FROM `spawning_locs` WHERE `zone`=$zone";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$arr[] = array(
					"x" => $row[0],
					"y" => $row[1]
					);
			}
			return $arr[rand(0, count($arr) - 1)];
		}
		else return -1;
	}
	
	function logLogin() {
		$sql = "INSERT INTO `activity_log` (`userFK`, `timestamp`) VALUES ($this->uid, CURRENT_TIMESTAMP)";
		$this->mysqli->query($sql);
	}
}
?>
