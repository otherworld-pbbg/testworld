<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";

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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
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
				para("You cannot butcher on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_POST["carcass"])||!isset($_POST["tool"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_POST["carcass"])||!is_numeric($_POST["tool"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				//to do: what if the carcass is in a different location?
				$targetObject = new Obj($mysqli, $_POST["carcass"]);
				$errors = 0;
				$blood = isset($_POST['blood']) ? $_POST['blood'] : 0;
				$intestine = isset($_POST['intestine']) ? $_POST['intestine'] : 0;
				$offal = isset($_POST['offal']) ? $_POST['offal'] : 0;
				$skin = isset($_POST['skin']) ? $_POST['skin'] : 0;
				$sinew = isset($_POST['sinew']) ? $_POST['sinew'] : 0;
				$head = isset($_POST['head']) ? $_POST['head'] : 0;
				$brain = isset($_POST['brain']) ? $_POST['brain'] : 0;
				$horn = isset($_POST['horn']) ? $_POST['horn'] : 0;
				$scapula = isset($_POST['scapula']) ? $_POST['scapula'] : 0;
				$feet = isset($_POST['feet']) ? $_POST['feet'] : 0;
				
				$parts = array($blood, $intestine, $offal, $skin, $sinew, $head, $brain, $horn, $scapula, $feet);
				foreach ($parts as $part) {
					if (!is_numeric($part)) $errors++;
				}
				
				if ($errors) {
					include_once "header2.inc.php";
					para("Error: " . $errors . " of the given values are not numeric.");
				}
				else {
					$result = $targetObject->dressCarcass($charcheck, $parts, $_POST["tool"]);
					if ($result==-1) {
						include_once "header2.inc.php";
						para("Error: You tried to use an invalid tool or something that's not in your inventory.");
					}
					else if ($result==-2) {
						include_once "header2.inc.php";
						para("You tried to use more AP than you have.");
					}
					else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
				}
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
			echo "</p>";
		}
	}
}
