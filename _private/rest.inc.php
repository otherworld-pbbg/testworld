<?php
//this needs the following post variables: charid, userid, hours, minutes

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
			displayBodywarning();
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot rest on someone else's behalf when you're a watcher.");
			}
			else {
				if (!isset($_POST["hours"])||!isset($_POST["minutes"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else {
					if (!is_numeric($_POST["hours"])||!is_numeric($_POST["minutes"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
					}
					else if ($_POST["hours"]<0||$_POST["hours"]>6) {
						include_once "header2.inc.php";
						para("Hours out of range. Aborting.");
					}
					else if ($_POST["minutes"]<0||$_POST["minutes"]>59) {
						include_once "header2.inc.php";
						para("Minutes out of range. Aborting.");
					}
					else {
						$minutes = round($_POST["minutes"]);
						$hours = round($_POST["hours"]);
						$rested = $curChar->rest($hours, $minutes);
						header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2&rested=' . $rested);
					}
				}
				
			}
		}
	}
}
?>
