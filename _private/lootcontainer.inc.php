<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";

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
	
	if ($watcherRole>1) para("You shouldn't be here since you're a watcher.");
	else if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			displayBodywarning();
		}
		else {
			if (!isset($_GET['sel'])||!isset($_GET['targetid'])||!isset($_GET['containerid'])||!isset($_GET["location"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_GET['sel'])||!is_numeric($_GET['targetid'])||!is_numeric($_GET['containerid'])||!is_numeric($_GET["location"])) para("Error: Something is not numeric.");
			else {
				if ($_GET['sel']==1) {
					if (!isset($_GET['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_GET['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_GET['grams']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res1 = $curChar->takeFromContainer($_GET['targetid'], $_GET['containerid'], $_GET['location'], "weight", $_GET['grams']);
						if ($res1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						else {
							include_once "header2.inc.php";
							para("Pick up failed. Perhaps you were trying to reach for something that was too far?");
						}
					}
				}
				else if ($_GET['sel']==2) {
					if (!isset($_GET['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_GET['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_GET['pieces']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res2 = $curChar->takeFromContainer($_GET['targetid'], $_GET['containerid'], $_GET['location'], "pieces", $_GET['pieces']);
						if ($res2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						else {
							include_once "header2.inc.php";
							para("Taking failed. Perhaps you were trying to reach for something that was too far or multiples of a non-stackable object?");
						}
					}
				}
				else if ($_GET['sel']==3) {
					$res3 = $curChar->takeFromContainer($_GET['targetid'], $_GET['containerid'], $_GET['location'], "whole");
					if ($res3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						include_once "header2.inc.php";
						para("Pick up failed. Perhaps you were trying to reach for something that was too far?");
					}
				}
			}
		}
	}
}
?>
