<?
chdir(dirname(__FILE__));

include_once "../root.inc.php";

include_once getGamePath() . "/_private/class_time.inc.php";
include_once getGamePath() . "/_private/abbr.inc.php";//abbreviations: para($str), ptag($tagname, $contents, [$attr])
include_once getGamePath() . "/../_private/conn.inc.php";

function getObjects($mysqli, $method) {
	$retArr = array();
	if ($method == "burn") $sql = "SELECT `objectFK`, `value`, `global_x`, `global_y`, `parent`, `weight` FROM `o_attrs` JOIN `objects` ON `objectFK`=`objects`.`uid` WHERE `attributeFK`=49 AND `value`>0 GROUP BY `objectFK` ORDER BY `o_attrs`.`uid` DESC";
	if ($method == "temp") $sql = "SELECT `objectFK`, `value`, `global_x`, `global_y`, `parent`, `weight` FROM `o_attrs` JOIN `objects` ON `objectFK`=`objects`.`uid` WHERE `attributeFK`=98 GROUP BY `objectFK` ORDER BY `o_attrs`.`uid` DESC";
	$result = $mysqli->query($sql);
	if (mysqli_num_rows($result)) {
		while ($row = mysqli_fetch_row($result)) {
			$retArr[] = array (
				"obj" => $row[0],
				"value" => $row[1],
				"x" => $row[2],
				"y" => $row[3],
				"parent" => $row[4],
				"weight" => $row[5]
				);
		}
		return $retArr;
	}
	else return false;
}

function adjustTemperature($mysqli) {
	$burn = getObjects($mysqli, "burn");
	$known = getObjects($mysqli, "temp");
	$safe = array();//empty
	if ($burn) {
		foreach ($burn as $b) {
			$o = new Obj($mysqli, $b["obj"]);
			$po = new Obj($mysqli, $b["parent"]);
			$temp = $o->getAttribute(98);
			$temp2 = $po->getAttribute(98);
			echo $o->getHandle(false) . " (". $o->uid . ") weighs ". $b["weight"] ."<br>";
			if ($b["weight"]<5) {
				echo $o->getHandle(false) . " (". $o->uid . ") was was reduced into nothingness<br>";
				$o->deleteFromDb();
			}
			else {
				if ($temp<200) {
					$o->setAttribute(98, 200);
					$temp = 200;
				}
				
				if ($b["weight"]>800) $wtchange = 200;
				else $wtchange = round($b["weight"]/4);
				$o->changeSize(-$wtchange, 0);
				echo $wtchange . " grams of " . $o->getHandle(false) . " (". $o->uid . ") went up in smoke<br>";
				
				$newt = min(1100, $temp+100);
				if ($o->preset == 192&&$newt>=600) {
					$o->changeType(20, 11, $o->type);//Charcoal
				}
				else if ($newt>=600&&$o->type==5&&$o->secondary!=11) $newt = $temp;
				
				$res = $o->setAttribute(98, $newt);
				if ($res==100) echo $o->getHandle(false) . " (". $o->uid . ") got hotter and is now " . $newt . " C<br>";
				if ($temp>$temp2) {
					echo $po->getHandle(false) . " (". $po->uid . ") got hotter and is now " . min(1100, $temp2+$wtchange/4) . " C<br>";
					$po->setAttribute(98, min(1100, round($temp2+$wtchange/4)));
				}
				
				$shared = $po->getContents();
				if ($shared) {
					foreach ($shared as $s) {
						if ($s!=$o->uid&&!in_array($s, $safe)) {
							$so = new Obj($mysqli, $s);
							$already = $so->getAttribute(49);
							$flammable = $so->getAttribute(48);
							$temperature = $so->getAttribute(98);
							$heat_react = $so->getAttribute(99);
							$heat_treated = $so->getAttribute(100);
							$ignition = $so->getAttribute(101);
							
							if ($heat_react<5&&!$already) {
								if ($temperature) {
									if ($temp2>$temperature) {
										$tchange = round($temp2-$temperature/2);
										$nt = $temperature+$tchange;
										if ($heat_react==2) $nt = min(100, $nt);
										$so->setAttribute(98, $nt);
										echo $so->getHandle(false) . " (". $so->uid . ") got hotter by " . $tchange . " degrees<br>";
									}
								}
								else {
									$nt = round($temp2/2);
									if ($heat_react==2) $nt = min(100, $nt);
									$so->setAttribute(98, $nt);
								}
								
								if ($temp2>80) {
									if ($heat_react==1) $change = 5;
									else $change = 20;
									if ($heat_treated) {
										$ht = min(100,$heat_treated+$change);
										$heat_treated = $so->setAttribute(100, $ht);
									}
									else {
										$heat_treated = $so->setAttribute(100, $change);
									}
								}
							}
							
							if ($ignition&&$ignition<=$temperature&&!$already) {
								echo $so->getHandle(false) . " (". $so->uid . ") ignites<br>";
								$so->setAttribute(49, 1);
								$so->setAttribute(98, $ignition);
							}
							if ($flammable==-1) {
								if ($so->weight>$b["weight"]*7) {
									echo $so->getHandle(false) . " (". $so->uid . ") put out the fire of " . $o->uid . "<br>";
									$o->setAttribute(49, 0);//kills fire
								}
							}
						}
					}
				}
				$safe[] = $b["obj"];
				$safe[] = $b["parent"];
			}
		}
	}
	
	if ($known) {
		$warrays = array();
		$time = new Time($mysqli);
		foreach ($known as $k) {
			if (!in_array($k["obj"], $safe)) {
				$no = new Obj($mysqli, $k["obj"]);
				$exit = $no->getExitCoordinates();
				$search = $exit["x"] . "_" . $exit["y"];
				//echo $search . "<br>";
				if (array_key_exists($search, $warrays)) $weather = $warrays[$search];//This will reduce database queries
				else {
					$weather = $time->getWeather($exit["x"], $exit["y"], true);//dbonly=true
					$warrays[$search] = $weather;
				}
				if (round($weather["temp"])<$k["value"]) {
					$newtemp = round(($k["value"]-$weather["temp"])*0.4+$weather["temp"]);
				}
				else if (round($weather["temp"])>$k["value"]) {
					$newtemp = round(($weather["temp"]-$k["value"])*0.2+$k["value"]);
				}
				else $newtemp = -300;
				
				if ($newtemp>-300&&$k["value"]!=$newtemp) {
					echo "Temperature of " . $no->getHandle(false) . " (" . $no->uid . ") changed into " . $newtemp . "<br>";
					$no->setAttribute(98, $newtemp);
				}
				
				$shared = $no->getContents();
				if ($shared) {
					foreach ($shared as $s) {
						if (!in_array($s, $safe)) {
							$so = new Obj($mysqli, $s);
							$already = $so->getAttribute(49);
							$temperature = $so->getAttribute(98);
							$heat_react = $so->getAttribute(99);
							$heat_treated = $so->getAttribute(100);
							
							if ($heat_react<5&&!$already) {
								if ($temperature) {
									if ($newtemp>$temperature) {
										$tchange = round($newtemp-$temperature/2);
										$nt = $temperature+$tchange;
										if ($heat_react==2) $nt = min(100, $nt);
										$so->setAttribute(98, $nt);
										echo $so->getHandle(false) . " (". $so->uid . ") is now " . $nt . " degrees<br>";
									}
								}
								else {
									$nt = round($newtemp/2);
									if ($heat_react==2) $nt = min(100, $nt);
									$so->setAttribute(98, $nt);
								}
								
								if ($newtemp>80) {
									if ($heat_react==1) $change = 5;
									else $change = 20;
									if ($heat_treated) {
										$ht = min(100,$heat_treated+$change);
										$heat_treated = $so->setAttribute(100, $ht);
									}
									else {
										$heat_treated = $so->setAttribute(100, $change);
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
adjustTemperature($mysqli);
?>
