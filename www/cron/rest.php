<?
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

function getLiveCharacters($mysqli) {
	$retArr = array();
	$sql = "SELECT `uid` FROM `chars` WHERE `status`=1 ORDER BY `uid`";
	$result = $mysqli->query($sql);
	if (mysqli_num_rows($result)) {
		while ($row = mysqli_fetch_row($result)) {
			$retArr[] = $row[0];
		}
		return $retArr;
	}
	else return false;		
}

function doLoop($mysqli) {
	$chars = getLiveCharacters($mysqli);
	if (!$chars) return 0;
	$counter = 0;
	foreach ($chars as $ci) {
		$bodyArray=$ci->calculateBody();
		$bodyWeight=$bodyArray->weight;
		$preset=$bodyArray->preset;
		$hasChanged=$ci->advanceAge();
		if($hasChanged) counter++;
		$c = new Character($mysqli, $ci);
		$result = $c->rest_auto();
		if ($result>0) $counter++;
	}
	echo counter . " people advanced to a new age/weight category";
	return $counter;
}

$result = doLoop($mysqli);

echo $result;

?>
