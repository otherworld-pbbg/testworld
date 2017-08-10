<?php
/*
function getGameRoot() {
	return "http://otherworld.loc:1234";
}

function getGamePath() {
	return "C:/xampp/htdocs/otherworld/www";
}*/

chdir(dirname(__FILE__));

include_once "../root.inc.php";

include_once getGamePath() . "/../_private/class_character.inc.php";
include_once getGamePath() . "/../_private/abbr.inc.php";//abbreviations: para($str), ptag($tagname, $contents, [$attr])
include_once getGamePath() . "/../_private/conn.inc.php";
include_once getGamePath() . "/../_private/class_obj.inc.php";
include_once getGamePath() . "/../_private/constants.php";


function getLiveCharacters($mysqli) {
	$retArr = array();
	$sql = "SELECT `uid` FROM `chars` WHERE `status`=1 ORDER BY `uid`";
	$result = $mysqli->query($sql);
	$rowcounter=0;
	if (mysqli_num_rows($result)) {
		while ($row = mysqli_fetch_row($result)) {
			$retArr[] = $row[0];
            $rowcounter++;
		}
		echo "Row count: " . $rowcounter;
		return $retArr;
	}
	else return false;		
}

function doLoop($mysqli) {
	$chars = getLiveCharacters($mysqli);
	if (!$chars) return 0;
	$counter = 0;
	$counter2 = 0;
	$blood_gained_counter=0;
	foreach ($chars as $ci) {
        $c = new Character($mysqli, $ci);

        $bo = new Obj($mysqli, $c->bodyId);
		$currentBlood = $bo->getAttribute(ATTR_BLOOD);
		$maxBlood = $bo->weight*0.1;
        if($currentBlood != $maxBlood) $blood_gained_counter++; //If this character will gain blood, increment counter
        $missingBloodPercentage = $maxBlood - ($currentBlood/$maxBlood) * 100;
        if($missingBloodPercentage <= 10 || $missingBloodPercentage > 40) $regen = HEAL_BLOOD_GRAMS;
        else $regen = HEAL_BLOOD_BONUS*($missingBloodPercentage-10) + HEAL_BLOOD_GRAMS;
        $newBlood = min($currentBlood+$regen, $maxBlood);
        $bo->setAttribute(ATTR_BLOOD, $newBlood);

		$hasChanged = $c->advanceAge();
		if($hasChanged) $counter2++;
		
		$result = $c->rest_auto();
		if ($result>0) $counter++;
        echo $c->uid . " gained " . $newBlood . " grams of blood.<br/>";
    }
	echo $counter2 . " people advanced to a new age/weight category";
    return $counter;
}

$result = doLoop($mysqli);
echo $result;

?>
