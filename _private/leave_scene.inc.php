<?php
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
if (!isset($_GET["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_GET['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else if ($watcherRole>1) {
		include_once "header2.inc.php";
		para("You cannot leave scenes on someone else's behalf as you are just a watcher. You shouldn't be here.");
	}
	else {
		//user is authorized to manipulate this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) echo "This character doesn't have a body so it cannot be played.";
		else {
			if (!isset($_GET["scene"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
			$sceneid = $mysqli->real_escape_string($_GET['scene']);
			$sceneObj = new Scene($mysqli, $sceneid);
			$sceneObj->loadValues();
			$timeArr = $sceneObj->getInternalTime();
			$res = $sceneObj->removeParticipant($timeArr["dateTime"], $timeArr["minute"], $charcheck);
			if ($res<1) {
				include_once "header2.inc.php";
				para("Failed to leave the scene.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=1' class='clist'>[Return to Scenes]</a>");
			}
			else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
		}
	}
}
?>
