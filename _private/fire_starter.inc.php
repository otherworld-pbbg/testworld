<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";

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
	$pos = $curChar->getPosition();
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot take things on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_GET["sel"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_GET["sel"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				$material = new Obj($mysqli, $_GET["sel"]);
				if ($material->parent==$curChar->bodyId) {
					$fire_effect = $material->getAttribute(ATTR_IGNITION);
					if ($fire_effect==1||$fire_effect==2) {
						echo "<form method='get' action='index.php' class='narrow'>";
						para("This material can be used as tinder or kindling.");
						para("You need a fire bow in order to start a fire.");
						para("Important disclaimer: Once you ignite this, you need to put it inside a container such as a fire pit fairly swiftly, or otherwise it can and will ignite other things in your inventory. Seriously, don't play with this, your stuff will go up in flames. You have been warned.");
						ptag("input", "", "type='hidden' name='page' value='startFire2'");
						ptag("input", "", "type='hidden' name='charid' value='$charcheck'");
						ptag("input", "", "type='hidden' name='sel' value='".$_GET["sel"] ."'");
						echo "<p class='right'>\n";
						ptag("input", "", "type='submit' value='Attempt to start a fire'");
						echo "</p>";
						echo "</form>";
					}
					else {
						para("Even though this material is technically flammable, it needs to be exposed to other burning material long enough to heat up sufficiently. It cannot be used as tinder.");
					}
				}
				else {
					include_once "header2.inc.php";
					para("The material you're trying to ignite isn't in your inventory.");
				}
				echo "</div>";
			}
		}
	}
}
