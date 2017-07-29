<?php

class Combat {
	private $mysqli;
	public $uid=0;
	
	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
	}
	
	public function checkParticipant($object_id) {
		$sql2 = "SELECT `combat_participants`.`uid` FROM `combat_participants` WHERE `combatFK`=$this->uid AND `objectFK`=$enemy_id AND `leave_dt`=0 LIMIT 1";
		$res = $this->mysqli->query($sql2);
		if (mysqli_num_rows($res)) {
			return 100;//participating
		}
		else return -1;//Not participating
	}
	
	public function getParticipants() {
		$retArr = array();
		$sql2 = "SELECT `combat_participants`.`objectFK` FROM `combat_participants` WHERE `combatFK`=$this->uid AND `leave_dt`=0";
		$res = $this->mysqli->query($sql2);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		else return -1;//Not participating
	}
}
