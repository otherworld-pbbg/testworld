<?
include_once "../_private/generic.inc.php";
include_once "../_private/header.inc.php";

function countHits($yourspeed, $enemyspeed, $yourppl, $enemyppl, $enemydefense, $youroffense) {
	$hits = 0;
	$hit = round($yourspeed*$yourppl/50)+rand(0,$yourppl);
	$block = round($enemyspeed*$enemyppl/50)+rand(0,$enemyppl);
	$chance = round($youroffense/3.1);
	$chance2 = round($enemydefense/3.1);
	for ($i=0; $i<$hit; $i++) {
		if (rand(0,99)<$chance) $hits++;
	}
	
	for ($i=0; $i<$block; $i++) {
		if (rand(0,99)<$chance2) $hits--;
	}
	echo "Chance to hit " . round($chance) . ", Chance to block " . round($chance2) . ": " . $hits . " hits out of ". $hit . " attempts successful";
}

$s1 = isset($_GET['s1']) ? $_GET['s1'] : 100;
$s2 = isset($_GET['s2']) ? $_GET['s2'] : 100;
$p1 = isset($_GET['p1']) ? $_GET['p1'] : 1;
$p2 = isset($_GET['p2']) ? $_GET['p2'] : 1;
$o = isset($_GET['o']) ? $_GET['o'] : 100;
$d = isset($_GET['d']) ? $_GET['d'] : 100;

echo "<form action='testc.php' method='get' class='narrow'>";
echo "<p>";
echo "Your speed (was: $s1)";
ptag("input", "", "type='range' min='1' max='300' name='s1' value='$s1' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Enemy speed (was: $s2)";
ptag("input", "", "type='range' min='1' max='300' name='s2' value='$s2' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Your people (was: $p1)";
ptag("input", "", "type='range' min='1' max='10000' name='p1' value='$p1' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Enemy people (was: $p2)";
ptag("input", "", "type='range' min='1' max='10000' name='p2' value='$p2' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Your offense (was: $o)";
ptag("input", "", "type='range' min='0' max='300' name='o' value='$o' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Enemy defense (was: $d)";
ptag("input", "", "type='range' min='0' max='300' name='d' value='$d' style='width: 5em'");
echo "</p>";
ptag("input", "", "type='submit' value='submit'");
echo "</form>";
countHits($s1, $s2, $p1, $p2, $d, $o);

?>
