<?php

class Planner {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	public function getPlans() {
		$retArr = array();
		$sql = "SELECT `node` FROM `plans` WHERE 1 ORDER BY `changed` DESC";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$retArr[] = $row[0];
			}
			return $retArr;
		}
		return false;
	}
}
?>
