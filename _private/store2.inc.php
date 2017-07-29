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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		para("You shouldn't be here since you're a watcher.");
	}
	else if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {
			if (!isset($_POST['sel2'])||!isset($_POST['sel3'])||!isset($_POST['targetid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_POST['sel2'])||!is_numeric($_POST['sel3'])||!is_numeric($_POST['targetid'])) para("Error: Something is not numeric.");
			else {
				if ($_POST['sel2']==1) {
					if (!isset($_POST['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_POST['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_POST['grams']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res1 = $curChar->storeGroundObject($_POST['targetid'], $_POST['sel3'], "weight", $_POST['grams']);
						if ($res1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						else {
							include_once "header2.inc.php";
							para("Could not store. Possible reasons: the item you're about to store isn't in the same location, the container is in another location, the container is too full.");
						}
					}
				}
				else if ($_POST['sel2']==2) {
					if (!isset($_POST['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_POST['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_POST['pieces']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res2 = $curChar->storeGroundObject($_POST['targetid'], $_POST['sel3'], "pieces", $_POST['pieces']);
						if ($res2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						else {
							include_once "header2.inc.php";
							para("Could not store. Possible reasons: the item you're about to store isn't in the same location, the container is in another location, the container is too full, you're trying to store multiples of an object that doesn't stack.");
						}
					}
				}
				else if ($_POST['sel2']==3) {
					$res3 = $curChar->storeGroundObject($_POST['targetid'], $_POST['sel3'], "whole");
					if ($res3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						include_once "header2.inc.php";
						para("Could not store. Possible reasons: the item you're about to store isn't in the same location, the container is in another location, the container is too full.");
					}
				}
			}
		}
	}
}
?>
