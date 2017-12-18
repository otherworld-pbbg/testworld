<?php

function selWeighedFromArray($array) {
	//the array must be structured so that in the first column there is an integer in ascending order
	//the second column is the value
	$last = $array[sizeof($array)-1];
	$rand = rand(0, $last[0]);
	
	foreach ($array as $entry) {
		if ($rand<$entry[0]) return $entry[1];
	}
	return $last[1];
}

Class Combatant {
	public $id = 0;
	public $skill = 0;
	public $strength = 0;
	public $weapon;
	public $health;
	public $morale;
	public $tag;
	public $teamcolor;
	public $color = "black";
	
	public function __construct($id, $tag, $teamColor) {
		$this->id = $id;
		$this->skill = rand(1, 100);
		$this->strenght = rand(1,20);
		$this->weapon = new Weapon();
		$this->health = 100;
		$this->morale = rand(50,100);
		$this->tag = $tag;
		$this->teamcolor = $teamColor;
		$this->color = $this->createColor();
	}
	
	public function createColor() {
		$green = rand(0,99);
		return $this->teamcolor += $green;
	}
	
	function getName() {
		return "<span style=\"color:#" . $this->color . " !important;font-weight: bold;\">". $this->tag . "#" . $this->id . "</span>";
	}
	
	function attack($enemy) {
		$minChance = 5;
		$chance2hit = max($minChance,floor($this->health/100*$this->skill*0.95));
		$chance2block = max($minChance,floor($enemy->health/100*$enemy->skill*0.50));
		
		if (rand(0,100)<$chance2hit) {
			if (rand(0,100)>=$chance2block) {
				$dmg = $this->weapon->countDamage($this->strength);
				$enemy->health -= $dmg;
				$this->morale += 5;
				$enemy->morale -= 10;
				if ($enemy->health<=0) return $this->getName() . " kills " . $enemy->getName() . " with " . $this->weapon->name ." (" . $dmg . ")";
				return $this->getName() . " hits " . $enemy->getName() . " with " . $this->weapon->name ." (" . $dmg . ")";
			}
			else {
				$this->morale -= 5;
				$enemy->morale += 5;
				return $this->getName() . " tries to hit " . $enemy->getName() . " but gets blocked";
			}
		}
		$this->morale -= 5;
		$enemy->morale += 2;
		return $this->getName() . " tries to hit " . $enemy->getName() . " but misses";
	}
	
	function checkLoyalty($team) {
		if ($this->morale<30) {
			echo $this->getName() . " from " . $team->name . " runs away.<br>";
			$team->removeMember($this->id);
		}
	}
	
	function report() {
		echo "- " . $this->getName() . " is armed with " . $this->weapon->name . ", has " . $this->skill . " skill, " . $this->morale . " morale and " . $this->health . " HP<br>";
	}
}

Class Weapon {
	public $type = 0;
	public $name = "";
	public $speed = 0;//how many times you can attack in a round
	public $efficiency = 0;//range 5 to 100
	
	public function __construct($type = false) {
		$chances = array(
			array(30, 0),//chance (incremental), key to weapons
			array(40, 1),
			array(60, 2),
			array(90, 3),
			array(100, 4),
			);
		
		$weapons = array(
			array("their bare hands", 5, 5),//name, speed, efficiency
			array("a dagger", 6, 15),
			array("a club", 4, 60),
			array("a spear", 3, 80),
			array("a sword", 2, 100),
			);
		
		if (!$type||$type>sizeof($weapons)-1||$type<0) {
			$type = selWeighedFromArray($chances);
		}
		$this->type = $type;
		$this->name = $weapons[$type][0];
		$this->speed = $weapons[$type][1];
		$this->efficiency = $weapons[$type][2];
	}
	
	function countDamage($strength) {
		return rand(floor($this->efficiency*0.5), floor($this->efficiency*1.25+$strength));
	}
}

Class Team {
	public $name = "";
	public $startPpl = 0;
	public $currentPpl = 0;
	public $morale = 100;
	public $attitude = "fight";
	public $combatants = array();
	public $tag;
	public $teamcolor;

	public function __construct($name, $tag, $teamColor) {
		$attitudes = array(
			"fight",
			"negotiate",
			"run"
			);
		
		$this->name = $name;
		$this->attitude = $attitudes[rand(0,sizeof($attitudes)-1)];
		$this->startPpl = $this->currentPpl = rand(10,20);
		$this->tag = $tag;
		$this->teamcolor = $teamColor;
		
		for ($i = 0; $i<$this->startPpl; $i++) $this->combatants[] = new Combatant($i, $this->tag, $teamColor);
	}
	
	public function attack($team2) {
		foreach ($this->combatants as $c) {
			if (sizeof($team2->combatants)<=0) break;
			$opponent = $team2->combatants[rand(0, sizeof($team2->combatants)-1)];
			$result = $c->attack($opponent);
			echo $result . "<br>";
			if (strpos($result, "kill") !== false) {
				$this->morale += 5;
				$team2->morale -= 5;
				$team2->removeMember($opponent->id);
			}
			else $opponent->checkLoyalty($team2);
			$c->checkLoyalty($this);
		}
	}
	
	public function removeMember($id) {
		foreach ($this->combatants as $key => $c) {
			if ($c->id==$id) {
				array_splice($this->combatants, $key, 1);
				$this->currentPpl--;
				break;
			}
		}
	}
	
	public function evaluateAttitude() {
		if ($this->attitude == "fight") {
			if ($this->morale<50||$this->currentPpl/$this->startPpl<0.5) $this->attitude = "negotiate";
		}
		if ($this->attitude == "negotiate") {
			if ($this->morale<30||$this->currentPpl/$this->startPpl<0.3) $this->attitude = "run";
		}
	}
	
	public function report() {
		echo "Team " . $this->name . " has " . $this->currentPpl . " combatants left out of " . $this->startPpl . ", their attitude is \"" . $this->attitude . "\" and their morale is " . $this->morale . "<br>";
	}
	
	public function rollCall() {
		foreach ($this->combatants as $c) {
			$c->report();
		}
	}
}

function fightround($team1, $team2) {
	$over = false;
	if ($team1->attitude == "run") {
		if ($team2->attitude == "run") {
			echo "Both teams retreat.<br>";
			$over = true;
		}
		else if ($team2->attitude == "negotiate") {
			echo $team1->name . " flees and the enemy lets them go.<br>";
			$over = true;
		}
		else if ($team2->attitude == "fight") {
			echo $team1->name . " tries to flee but " . $team2->name . " goes after them and attacks.<br>";
			$team2->attack($team1);
			if ($team1->currentPpl<=0) {
				echo $team1->name . " has been wiped out.<br>";
				$over = true;
			}
			else {
				echo $team1->name . " has no choice but to fight back.<br>";
				$team1->attack($team2);
				if ($team2->currentPpl<=0) {
					echo $team2->name . " has been wiped out.<br>";
					$over = true;
				}
			}
		}
		else "Something is missing.<br>";
	}
	else if ($team2->attitude == "run") {
		if ($team1->attitude == "negotiate") {
			echo $team2->name . " flees and the enemy lets them go.<br>";
			$over = true;
		}
		else if ($team1->attitude == "fight") {
			echo $team2->name . " tries to flee but " . $team1->name . " goes after them and attacks.<br>";
			$team1->attack($team2);
			if ($team2->currentPpl>0) {
				echo $team2->name . " has no choice but to fight back.<br>";
				$team2->attack($team1);
				if ($team1->currentPpl<=0) {
					echo $team1->name . " has been wiped out.<br>";
					$over = true;
				}
			}
			else {
				echo $team2->name . " has been wiped out.<br>";
				$over = true;
			}
		}
		else "Something is missing.<br>";
	}
	else if ($team1->attitude == "negotiate") {
		if ($team2->attitude == "negotiate") {
			echo "The teams decide to settle their differences and form a truce.<br>";
			$over = true;
		}
		else if ($team2->attitude == "fight") {
			echo $team1->name . " tries to plead to " . $team2->name . " but they attack anyway.<br>";
			$team2->attack($team1);
			if ($team1->currentPpl>0) {
				echo $team1->name . " has no choice but to keep fighting.<br>";
				$team1->attack($team2);
				if ($team2->currentPpl<=0) {
					echo $team2->name . " has been wiped out.<br>";
					$over = true;
				}
			}
			else {
				echo $team1->name . " has been wiped out.<br>";
				$over = true;
			}
		}
		else "Something is missing.<br>";
	}
	else if ($team2->attitude == "negotiate") {
		if ($team1->attitude == "fight") {
			echo $team2->name . " tries to plead to " . $team1->name . " but they attack anyway.<br>";
			$team1->attack($team2);
			if ($team2->currentPpl>0) {
				echo $team2->name . " has no choice but to keep fighting.<br>";
				$team2->attack($team1);
				if ($team1->currentPpl<=0) {
					echo $team1->name . " has been wiped out.<br>";
					$over = true;
				}
			}
			else {
				echo $team2->name . " has been wiped out.<br>";
				$over = true;
			}
		}
		else "Something is missing.<br>";
	}
	else {
		echo "Both teams are in the mood for fighting.<br>";
		$first = rand(0,1);
		if ($first) {
			echo $team1->name . " strikes first.<br>";
			$team1->attack($team2);
			if ($team2->currentPpl<=0) {
				echo $team2->name . " has been wiped out.<br>";
				$over = true;
			}
			else {
				$team2->attack($team1);
				if ($team1->currentPpl<=0) {
					echo $team1->name . " has been wiped out.<br>";
					$over = true;
				}
			}
		}
		else {
			echo $team2->name . " strikes first.<br>";
			$team2->attack($team1);
			if ($team1->currentPpl<=0) {
				echo $team1->name . " has been wiped out.<br>";
				$over = true;
			}
			else {
				$team1->attack($team2);
				if ($team2->currentPpl<=0) {
					echo $team2->name . " has been wiped out.<br>";
					$over = true;
				}
			}
		}
	}
	$team1->evaluateAttitude();
	$team2->evaluateAttitude();
	return $over;
}

$c1 = 100000 + rand(5500, 9900);
$c2 = rand(55, 99)*10000;

$team1 = new Team("Pumas", "P", $c1);
$team2 = new Team("Cougars", "C", $c2);

$team1->report();
$team1->rollCall();

$team2->report();
$team2->rollCall();

for ($round = 0; $round < 100; $round++) {
	echo "<br>Round " . ($round+1) . "<br>";
	$over = fightround($team1, $team2);
	if ($over) break;
	else {
		$team1->report();
		$team2->report();
	}
}

echo "The fight is over.<br>";
?>