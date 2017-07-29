<?
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once ("generic.inc.php");

//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
}
//end logged in check
//Next check if character selected
if (!isset($_GET["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_GET['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		ptag("p", "Disclaimer: You are a watcher, so you can't carry out actions. You only see what the character sees.");
	}
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {
			if (!isset($_GET['x'])||!isset($_GET['y'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
			else if (!is_numeric($_GET['x'])||!is_numeric($_GET['y'])) {
				header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
			}
			else if ($_GET['x']<0||$_GET['x']>19999||$_GET['y']<-5000||$_GET['y']>4999) {
				include_once "header2.inc.php";
				para("Coordinates out of range. Aborting.");
			}
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				echo "<div class='left_header'>\n";
				ptag("h4", "Current character:");
				para($curChar->cname);
				echo "</div></div>";
				$x = $mysqli->real_escape_string($_GET['x']);
				$y = $mysqli->real_escape_string($_GET['y']);
				$result = $curChar->getLocationName($x, $y);
				echo "<div class='bar'>\n";
				ptag("h1", "Rename location");
				echo "<form action='index.php?page=changeLocName' method='post' class='narrow'>";
				para("Current name: " . $result["name"]);
				para("Coordinates: ($x,$y)");
				echo "<p>";
				ptag("label", "New name: ", "for='nametext'");
				ptag("input", "", "type='text' length='50' maxlength='50' name='nametext' id='nametext' value='" . cleanup($result["name"]) . "'");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				ptag("input" , "", "type='hidden' name='x' value='$x'");
				ptag("input" , "", "type='hidden' name='y' value='$y'");
				echo "</p>";
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Change'");
				echo "</p>";
				echo "</form>";
				echo "</div>";
			}
		}
	}
}
?>
