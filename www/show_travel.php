<?
session_start();
include_once("../_private/class_character.inc.php");
include_once("../_private/conn.inc.php");

$ok = true;
//the part that checks if you're logged in
if (!isset($_SESSION["logged_user"])) {
		$ok = false;
}
else
{
	$currentUser = $_SESSION['user_id'];
}
if (!isset($_GET["charid"])) {
		$ok = false;
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_GET['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole<1) $ok = false;
}
	

if (isset($_GET["charid"])&&$ok) {
	if (is_numeric($_GET["charid"])) {
		$charid = $_GET["charid"];
		$char = new Character($mysqli, $charid);
		$coords = $char->visitedCoords(false);
		$min_x = $char->minWithKey($coords, "x");
		$min_y = $char->minWithKey($coords, "y");
		$max_x = $char->maxWithKey($coords, "x");
		$max_y = $char->maxWithKey($coords, "y");
		$image = imagecreate($max_x-$min_x+20, $max_y-$min_y+20);
		$background = imagecolorallocate($image, 0, 0, 0);
		for ($i=1;$i<sizeof($coords);$i++) {
			$blue = max(255-($i*5),0);
			$line_color[$i] = imagecolorallocate($image, $blue, $blue, $blue);
			$x1 = $coords[$i-1]["x"]-$min_x+10;
			$y1 = $coords[$i-1]["y"]-$min_y+10;
			$x2 = $coords[$i]["x"]-$min_x+10;
			$y2 = $coords[$i]["y"]-$min_y+10;
			imageline($image, $x1, $y1, $x2, $y2, $line_color[$i]);
		}
	}
}
else {
	$image = imagecreate(500, 120);
	$background = imagecolorallocate($image, 0, 0, 0);
	$line_color = imagecolorallocate($image, 80, 80, 200);
	$text_color = imagecolorallocate($image, 255, 0, 0 );
	imagestring($image, 4, 30, 25, "Unauthorized access, naughty naughty", $text_color);
}

header("Content-type: image/png");
imagepng($image);
imagecolordeallocate($image, $background);
imagedestroy($image);

?>
