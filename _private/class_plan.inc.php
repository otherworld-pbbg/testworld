<?php

class Plan {
	private $mysqli;
	public $node;
	public $title = "";
	public $created;
	public $changed;
	public $valid;
	
	public function __construct($mysqli, $node) {
		$this->mysqli = $mysqli;
		$this->node = $node;
		$this->valid = $this->getPlan();
	}
	
	public function getPlan() {
		if ($this->node>0&&is_numeric($this->node)) {
			$sql = "SELECT `title`, `created`, `changed` FROM `plans` WHERE `node`=$this->node LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				$this->title = $row[0];
				$this->created = $row[1];
				$this->changed = $row[2];
				return true;
			}
		}
		return false;
	}
	
	public function getContents() {
		if ($this->node>0&&is_numeric($this->node)) {
			$sql = "SELECT `contents` FROM `plans` WHERE `node`=$this->node LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				return $row[0];
			}
		}
		return "Failed to load contents";
	}
	
	public function countComments() {
		$sql = "SELECT COUNT(`uid`) FROM `plan_comment` WHERE `planFK`=$this->node";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			return $row[0];
		}
		else return "?";
	}
	
	public function addComment($currentUser, $com) {
		$sql = "INSERT INTO `plan_comment` (`author`, `planFK`, `contents`) VALUES ($currentUser, $this->node, '$com')";
		$this->mysqli->query($sql);
		if ($this->mysqli->insert_id) return true;
		else return false;
	}
	
	public function printComments() {
		$sql = "SELECT `author`, `contents`, `added`, `uid` FROM `plan_comment` WHERE `planFK`=$this->node ORDER BY `added`";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$player = new Player($this->mysqli, $row[0]);
				$player->getInfo();
				echo "<div class='comment'>";
				para("[" . $row[3] . "] " . $player->username . " wrote on ". $row[2] . ":");
				echo $row[1];
				echo "</div>";
			}
			return true;
		}
		else return false;
	}
}
?>
