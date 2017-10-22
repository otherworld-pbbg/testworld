<?php
class CultureDraft {
	private $mysqli;
	public $uid;
	private $briefdesc = "";
	private $longdesc = "";
	private $leader;
	private $status=1;
	public $allowPendingChars;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($uid>0) $this->loadData();
	}
	
	function loadData() {
		$sql = "SELECT `bdesc`, `ldesc`, `leader`, `status`, `allow` FROM `pending_culture` WHERE `uid`=$this->uid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)==1) {
			$row = mysqli_fetch_row($res);
			$this->briefdesc = $row[0];
			$this->longdesc = $row[1];
			$this->leader = $row[2];
			$this->status = $row[3];
			$this->allowPendingChars = $row[4];
			return true;
		}
		return false;
	}
	
	function create($bdesc, $ldesc, $leader, $allow) {
		$status = 1;//pending
		$sql = "INSERT INTO `pending_culture` (`bdesc`, `ldesc`, `leader`, `status`, `allow`) VALUES ('$bdesc', '$ldesc', '$leader', '$status', '$allow')";
		$this->mysqli->query($sql);
		if ($this->mysqli->insert_id) {
			$this->briefdesc = $bdesc;
			$this->longdesc = $ldesc;
			$this->leader = $leader;
			$this->status = $status;
			$this->allowPendingChars = $allow;
			$this->uid = $this->mysqli->insert_id;
			return $this->mysqli->insert_id;
		}
		return false;
	}
	
	function saveData($bdesc, $ldesc, $allow) {
		$sql = "UPDATE `pending_culture` SET `bdesc`='$bdesc', `ldesc`='$ldesc', `allow`=$allow WHERE `uid`=$this->uid";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			$this->briefdesc = $bdesc;
			$this->longdesc = $ldesc;
			$this->allowPendingChars = $allow;
			return true;
		}
		return false;
	}
	
	function setStatus($new) {
		$sql = "UPDATE `pending_culture` SET `status`=$new WHERE `uid`=$this->uid";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1) {
			$this->status = $status;
			return true;
		}
		return false;
	}
	
	function getBriefDesc() {
		return $this->briefdesc;
	}
	
	function getLongDesc() {
		return $this->longdesc;
	}
	
	function getLeader() {
		return $this->leader;
	}
	
	function getStatus() {
		return $this->status;
	}
	
	function getAllow() {
		return $this->allowPendingChars;
	}
	
	function printStatus() {
		$status = $this->status;
		switch ($status) {
		case 0:
			return "Red light (Has some issues)";
			break;
		case 1:
			return "Yellow light (Pending)";
			break;
		case 2:
			return "Green light (Accepted)";
			break;
		default:
			return "Unexpected value";
		}
	}
}
?>