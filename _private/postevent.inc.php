<?php
//this needs the following post variables: talkbox, charid, userid, scene

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
			if (!isset($_POST["scene"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
			
			$sceneid = $mysqli->real_escape_string($_POST['scene']);
			$sceneObj = new Scene($mysqli, $sceneid);
			$sceneObj->loadValues();
			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("Seriously, you shouldn't be on this page. You're a Watcher, which means you Do Not play the character, you just see through their eyes. The only way you could enter this page was by data manipulation, so stop doing that.");
			}
			else {
				
				if (!isset($_POST["talkbox"])) {
					header('Location: index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $sceneid);
				}
				else {
					$secureText = mysqli_real_escape_string($mysqli, $_POST['talkbox']);
					$timeArray = $sceneObj->getInternalTime();
					$result = $sceneObj->addEvent($charcheck, $timeArray['dateTime'], $timeArray['minute'], 1, $secureText);
					if (!$result) {
						include_once "header2.inc.php";
						para("Sorry, for some reason that couldn't be posted. Here's what you were trying to post: '" . $secureText . "'");
					}
					else header('Location: index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $sceneid);
				}
				
			}
		}
	}
}
?>
