<?php
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
			if (!isset($_GET['ocharid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=5');
			else if (!is_numeric($_GET['ocharid'])) {
				header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=5');
			}
			else {
				$ochar = new Character($mysqli, $_GET['ocharid']);
				$ochar->getBasicData();
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				echo "<div class='left_header'>\n";
				ptag("h4", "Current character:");
				para($curChar->cname);
				echo "</div></div>";
				$oname = $curChar->getDynamicName($ochar->uid);
				$oname = cleanup($oname);//Use this for any string that gets placed in a text field
				echo "<div class='bar'>\n";
				ptag("h1", "Rename person");
				echo "<form action='index.php?page=changeCharName' method='post' class='narrow'>";
				para("Current name: " . $oname);
				para("Description: " . $ochar->getAgeSex());
				echo "<p>";
				ptag("label", "New name: ", "for='nametext'");
				ptag("input", "", "type='text' length='50' maxlength='50' name='nametext' id='nametext' value='" . $oname . "'");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='ocharid' value='" . $_GET['ocharid'] . "'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
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
