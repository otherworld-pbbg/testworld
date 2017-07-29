<?php

include_once("class_character.inc.php");

class Statistics {
	private $mysqli;
	
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	private function getBusy() {
		$sql = "SELECT `charFK`, SUM(`ap`) as `spender` FROM `charloctime` WHERE `ap` > '0' GROUP BY `charFK` ORDER BY `spender` DESC LIMIT 20";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$retArr = array();
			while ($row = mysqli_fetch_assoc($res)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		return false;
	}
	
	public function printBusy() {
		$busy = $this->getBusy();
		if ($busy) {
			echo "<table>";
			echo "<tr>";
			echo "<th>ID</th><th>Name</th><th>AP spent</th>";
			echo "</tr>";
			foreach ($busy as $b) {
				$c = new Character($this->mysqli, $b["charFK"]);
				echo "<tr>";
				echo "<td>$c->uid</td><td>$c->cname</td><td>" .$b["spender"]. "</td>";
				echo "</tr>";
			}
			echo "</table>";
			para("Displays max. 20 top results.");
		}
		else para("Logs could not be retrieved.");
	}
	
	private function getVisited() {
		$sql = "SELECT `globalX`, `globalY`, COUNT(DISTINCT `charFK`) as `times` FROM `charloctime` WHERE 1 GROUP BY `globalX`, `globalY` ORDER BY `times` DESC LIMIT 30";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$retArr = array();
			while ($row = mysqli_fetch_assoc($res)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		return false;
	}
	
	public function printVisited() {
		$busy = $this->getVisited();
		if ($busy) {
			echo "<table>";
			echo "<tr>";
			echo "<th>x</th><th>y</th><th>Distinct visitors</th>";
			echo "</tr>";
			foreach ($busy as $b) {
				echo "<tr>";
				echo "<td>" .$b["globalX"]. "</td><td>" .$b["globalY"]. "</td><td>" .$b["times"]. "</td>";
				echo "</tr>";
			}
			echo "</table>";
			para("Displays max. 30 top results.");
		}
		else para("Logs could not be retrieved.");
	}
}
?>
