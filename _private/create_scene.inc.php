<?php
//this needs the following post variables: duration, scenetitle, scenedesc, privacy, charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_scene.inc.php";

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
				para("You cannot create a scene on someone else's behalf when you're a watcher.");
			}
			else {
				if (!isset($_POST["scenetitle"])||!isset($_POST["scenedesc"])||!isset($_POST["privacy"])||!isset($_POST["duration"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
				}
				else {
					if (!is_numeric($_POST["privacy"])||!is_numeric($_POST["duration"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
					}
					else if ($_POST["privacy"]<1||$_POST["privacy"]>3) {
						include_once "header2.inc.php";
						para("Unknown privacy level. Aborting.");
					}
					else if ($_POST["duration"]<1||$_POST["duration"]>15) {
						include_once "header2.inc.php";
						para("Duration is out of range. Aborting.");
					}
					else {
						$gameTime = new Time($mysqli);
						
						$endTime = new Time($mysqli, $gameTime->dateTime, $gameTime->minute);
						$hours = 0;
						if ($_POST["duration"]<12) $minutes=$_POST["duration"]*5;
						else {
							$minutes=0;
							$hours = $_POST["duration"]-11;
						}
						
						$endTime->addTime($minutes, $hours);
						
						$newScene = new Scene($mysqli, 0);
						$secureNum = mysqli_real_escape_string($mysqli, $_POST['privacy']);
						$secureTitle = mysqli_real_escape_string($mysqli, $_POST['scenetitle']);
						$secureDesc = mysqli_real_escape_string($mysqli, $_POST['scenedesc']);
						
						$startT = $gameTime->dateTime;
						$startM = $gameTime->minute;
						$endT = $endTime->dateTime;
						$endM = $endTime->minute;
						
						$res = $newScene->create($secureNum, $pos->x, $pos->y, $pos->lx, $pos->localy, '0', $startT, $startM, $endT, $endM, $secureTitle, $secureDesc, $charcheck);
						header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
					}
				}
				
			}
		}
	}
}
?>
