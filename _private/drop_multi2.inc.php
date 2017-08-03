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
		if (isset($_POST['counter'])) {
			$counter = $_POST['counter'];
			if (is_numeric($counter)) {
				$success = 0;
				$fail = 0;
				for ($i=0;$i<$counter-1;$i++) {
					if (isset($_POST['sel-' . $i])) {
						if (is_numeric($_POST['sel-' . $i])) {
							$todrop = $_POST['sel-' . $i];
							$res = $curChar->dropObject($todrop, "whole");
							if ($res) $success++;
							else $fail++;
						}
						else $fail++;
					}
				}
			}			
			header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&success=' . $success . '&fail=' . $fail);
		}
		else {
			include_once "header2.inc.php";
			para("There's a problem with the passed data.");
		}
	}
}
?>
