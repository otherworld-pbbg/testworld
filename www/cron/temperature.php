<?
chdir(dirname(__FILE__));

include_once "../root.inc.php";

include_once getGamePath() . "/../_private/class_time.inc.php";
include_once getGamePath() . "/../_private/abbr.inc.php";//abbreviations: para($str), ptag($tagname, $contents, [$attr])
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

function heatReact($mysqli, $s, $temp2) {
	$so = new Obj($mysqli, $s);
	$already = $so->getAttribute(ATTR_ON_FIRE);
	$temperature = $so->getAttribute(ATTR_TEMPERATURE);
	$heat_react = $so->getAttribute(ATTR_HEAT_REACT);
	$heat_treated = $so->getAttribute(ATTR_HEAT_TREATED);
	
	if ($heat_react<HEAT_REACT_METAL&&!$already) {
		if ($temperature) {
			if ($temp2>$temperature) {
				$tchange = round(($temp2-$temperature)/2);
				$nt = $temperature+$tchange;
				if ($heat_react==HEAT_REACT_BAKED) $nt = min(100, $nt);
				$res = $so->setAttribute(ATTR_TEMPERATURE, $nt);
				if ($res==100) echo $so->getHandle(false) . " (". $so->uid . ") got hotter by " . $tchange . " degrees<br>";
			}
		}
		else {
			$nt = round($temp2/2);
			if ($heat_react==HEAT_REACT_BAKED) $nt = min(100, $nt);
			$so->setAttribute(ATTR_TEMPERATURE, $nt);
		}
		
		if ($temp2>80) {
			if ($heat_react==HEAT_REACT_DRY) $change = 5;//percent
			else $change = 20;
			if ($heat_treated) {
				$ht = min(100,$heat_treated+$change);
				$heat_treated = $so->setAttribute(ATTR_HEAT_TREATED, $ht);
			}
			else {
				$heat_treated = $so->setAttribute(ATTR_HEAT_TREATED, $change);
			}
		}
	}//Metal softening will be added later if we even decide to use it at all
	else if ($heat_react==HEAT_REACT_CLAY&&!$already) {
		//Range of heat treated in clay:
		//10% - 100C - Dry, unfired
		//30% - 350C - Bonded water starts to escape Dry, brittle
		//50% - 573C - Quartz inversion occurs - Brittle
		//80% - 900C - Porous ceramic
		//90% - 1000C - Earthenware
		//100% - 1390C - Stoneware
		
		if ($temperature) {
			if ($temp2>$temperature) {
				$tchange = round(($temp2-$temperature)/2);
				$nt = $temperature+$tchange;
				
				$res = $so->setAttribute(ATTR_TEMPERATURE, $nt);
				if ($res==100) echo $so->getHandle(false) . " (". $so->uid . ") got hotter by " . $tchange . " degrees<br>";
			}
			else $nt = $temperature;
		}
		else {
			$nt = round($temp2/2);
			$so->setAttribute(ATTR_TEMPERATURE, $nt);
		}
		
		$change = 10;
		if ($heat_treated) {
			//to do: Maybe in the distant future, ceramics will explode if heated too quickly
			$ht = min(100,$heat_treated+$change);
			
			if ($nt<100) $ht = min(5, $ht);
			else if ($nt<350) $ht = min(10, $ht);
			else if ($nt<573) $ht = min(30, $ht);
			else if ($nt<900) $ht = min(50, $ht);
			else if ($nt<1000) $ht = min(80, $ht);
			else if ($nt<1390) $ht = min(90, $ht);
			if ($ht>$heat_treated) {
				$res = $so->setAttribute(ATTR_HEAT_TREATED, $ht);
				if ($res==100) echo "Clay got more fired.<br>";
			}
		}
		else {
			$heat_treated = $so->setAttribute(ATTR_HEAT_TREATED, $change);
		}
	}
}

function ignite($mysqli, $s, $temp2, $o, $b) {
	$so = new Obj($mysqli, $s);
	$already = $so->getAttribute(ATTR_ON_FIRE);
	$flammable = $so->getAttribute(ATTR_IGNITION);
	$temperature = $so->getAttribute(ATTR_TEMPERATURE);
	$ignition = $so->getAttribute(ATTR_IGNITION_TEMPERATURE);
	$firesource = $o->getAttribute(ATTR_TEMPERATURE);
	
	//if hot enough to ignite and not already on fire
	if ($ignition&&($ignition<=$temperature||$firesource>=$ignition)&&!$already) {
		echo $so->getHandle(false) . " (". $so->uid . ") ignites<br>";
		$so->setAttribute(ATTR_ON_FIRE, 1);
		$so->setAttribute(ATTR_TEMPERATURE, $ignition);
	}
	if ($flammable==-1) {
		if ($so->weight>$b["weight"]*7) {
			$res = $o->setAttribute(ATTR_ON_FIRE, 0);//kills fire
			if ($res==100) echo $so->getHandle(false) . " (". $so->uid . ") put out the fire of " . $o->uid . "<br>";
		}
	}
}

function adjustTemperature($mysqli) {
	$burn = getObjects($mysqli, "burn");
	$known = getObjects($mysqli, "temp");
	$safe = array();//starts out empty
	if ($burn) {
		echo sizeof($burn) . " things are on fire.<br>";
		foreach ($burn as $b) {
			$o = new Obj($mysqli, $b["obj"]);
			$po = new Obj($mysqli, $b["parent"]);
			$temp = $o->getAttribute(ATTR_TEMPERATURE);
			$temp2 = $po->getAttribute(ATTR_TEMPERATURE);
			echo $o->getHandle(false) . " (". $o->uid . ") weighs ". $b["weight"] ."<br>";
			if ($b["weight"]<5) {
				echo $o->getHandle(false) . " (". $o->uid . ") was was reduced into nothingness<br>";
				$o->deleteFromDb();
			}
			else {
				if ($temp<200) {
					$o->setAttribute(ATTR_TEMPERATURE, 200);//Minimum on fire temperature is 200. We might get rid of this later on if we want to simulate slow to ignite
					$temp = 200;
				}
				
				if ($b["weight"]>800) $wtchange = 200;
				else $wtchange = round($b["weight"]/4);
				$o->changeSize(-$wtchange, 0);
				echo $wtchange . " grams of " . $o->getHandle(false) . " (". $o->uid . ") went up in smoke<br>";
				
				$newt = min(1100, $temp+100);
				if ($o->preset == 192&&$newt>=600) {
					$o->changeType(20, 11, $o->type);//Charcoal
					echo "Wood turned into charcoal.<br>";
				}
				else if ($newt>=600&&$o->type==5&&$o->secondary!=11) $newt = $temp;//Resources other than charcoal (and coal in the future) cannot burn hotter than 600 degrees
				
				$res = $o->setAttribute(ATTR_TEMPERATURE, $newt);//100 means success, negative numbers mean error or no change
				if ($res==100) echo $o->getHandle(false) . " (". $o->uid . ") got hotter and is now " . $newt . " C<br>";
				if ($temp>$temp2) {
					$res2 = $po->setAttribute(ATTR_TEMPERATURE, min(1100, min($temp, round($temp2 + $temp/4))));
					if ($res2==100) echo $po->getHandle(false) . " (". $po->uid . ") got hotter and is now " . min(1100, min($temp, round($temp2 + $temp2/4))) . " C<br>";
					else echo $po->getHandle(false) . " (". $po->uid . ") failed to get any hotter despite the fire inside it.";
				}
				
				$shared = $po->getContents();//Things in the same container
				if ($shared) {
					foreach ($shared as $s) {
						if ($s!=$o->uid&&!in_array($s, $safe)) {
							heatReact($mysqli, $s, $temp2);
							ignite($mysqli, $s, $temp2, $o, $b);
							$safe[] = $s;
						}
					}
				}
				$safe[] = $b["obj"];
				$safe[] = $po->uid;
			}
		}
	}
	else echo "No things are on fire.<br>";
	
	if ($known) {
		echo sizeof($known) . " things have a set temperature.<br>";
		$warrays = array();
		$time = new Time($mysqli);
		foreach ($known as $k) {
			if (!in_array($k["obj"], $safe)) {
				$no = new Obj($mysqli, $k["obj"]);
				$po = new Obj($mysqli, $k["parent"]);
				
				$temp2 = $po->getAttribute(ATTR_TEMPERATURE);//This is false if parent is 0
				
				$exit = $no->getExitCoordinates();
				$search = "w" . $exit["x"] . "_" . $exit["y"];
				//echo $search . "<br>";
				if (array_key_exists($search, $warrays)) $weather = $warrays[$search];//This will reduce database queries
				else {
					$weather = $time->getWeather($exit["x"], $exit["y"], true);//dbonly=true
					$warrays[$search] = $weather;
				}
				
				if ($temp2) {
					if ($temp2>$weather["temp"]) $environment = $temp2;
					else $environment = round($weather["temp"]);
				}
				else $environment = round($weather["temp"]);
				
				if ($environment<$k["value"]) {
					$newtemp = round(($k["value"]-$environment)*0.4+$environment);
				}
				else if ($environment>$k["value"]) {
					$newtemp = round($environment-$k["value"]*0.2+$k["value"]);
				}
				else $newtemp = $k["value"];
				
				if ($k["value"]!=$newtemp) {
					$res=$no->setAttribute(ATTR_TEMPERATURE, $newtemp);
					if ($res==100) echo "Temperature of " . $no->getHandle(false) . " (" . $no->uid . ") changed into " . $newtemp . "<br>";
				}
				else {
					echo $no->getHandle(false) . " (" . $no->uid . ") reached the temperature of the environment.<br>";
					if (round($weather["temp"])==$newtemp) $no->purgeAttribute(ATTR_TEMPERATURE);//Doesn't purge if the environment temperature is because of a hot container
				}
				
				if ($temp2) heatReact($mysqli, $k["obj"], $temp2);
			}
		}
	}
	else echo "No things differ from the temperature of their environment.<br>";
}
adjustTemperature($mysqli);
echo "End of process.";
?>
