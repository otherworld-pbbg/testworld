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
	echo "Chance to hit " . round($chance) . ", opponent chance to block " . round($chance2) . ": " . max(0,$hits) . " hits out of ". $hit . " attempts successful";
	return max(0,$hits);
}

$s1 = isset($_GET['s1']) ? $_GET['s1'] : 100;
$s2 = isset($_GET['s2']) ? $_GET['s2'] : 100;
$p1 = isset($_GET['p1']) ? $_GET['p1'] : 1;
$p2 = isset($_GET['p2']) ? $_GET['p2'] : 1;
$o = isset($_GET['o']) ? $_GET['o'] : 100;
$d = isset($_GET['d']) ? $_GET['d'] : 100;
$o2 = isset($_GET['o2']) ? $_GET['o2'] : 100;
$d2 = isset($_GET['d2']) ? $_GET['d2'] : 100;

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
echo "Enemy offense (was: $o2)";
ptag("input", "", "type='range' min='0' max='300' name='o2' value='$o2' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Your defense (was: $d)";
ptag("input", "", "type='range' min='0' max='300' name='d' value='$d' style='width: 5em'");
echo "</p>";
echo "<p>";
echo "Enemy defense (was: $d2)";
ptag("input", "", "type='range' min='0' max='300' name='d2' value='$d2' style='width: 5em'");
echo "</p>";
ptag("input", "", "type='submit' value='submit'");
echo "</form>";
echo "<p>Your team: ";
$hits = countHits($s1, $s2, $p1, $p2, $d2, $o);
echo "</p><p>Enemy team: ";
$hits2 = countHits($s2, $s1, $p2, $p1, $d, $o2);
echo "</p>";

if ($hits>$hits2) echo "<p>Your team wins!</p>";
else if ($hits<$hits2) echo "<p>Enemy team wins!</p>";
else echo "<p>It's a tie.</p>";

?>
