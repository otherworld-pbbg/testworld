<?php
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
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		para("You cannot change names on someone else's behalf when you're a watcher.");
	}
	else {
		if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
		else {
			//user is authorized to view this character
			$bodyId = $curChar->getBasicData();
			if ($bodyId == -1) {
				include_once "header2.inc.php";
				echo "This character doesn't have a body so it cannot be played.";
			}
			else {
				if (!isset($_POST['x'])||!isset($_POST['y'])||!isset($_POST['nametext'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				else if (!is_numeric($_POST['x'])||!is_numeric($_POST['y'])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				}
				else if ($_POST['x']<0||$_POST['x']>19999||$_POST['y']<-5000||$_POST['y']>4999) {
					include_once "header2.inc.php";
					para("Coordinates out of range. Aborting.");
				}
				else {
					$x = $mysqli->real_escape_string($_POST['x']);
					$y = $mysqli->real_escape_string($_POST['y']);
					$name = $mysqli->real_escape_string($_POST['nametext']);
					$curChar->updateLocName($x, $y, $name);
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=7');
				}
			}
		}
	}
}
?>
