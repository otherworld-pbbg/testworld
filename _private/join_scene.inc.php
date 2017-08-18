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
		para("You cannot join new scenes as you are just a watcher. You shouldn't be here.");
	}
	else {
		//user is authorized to manipulate this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			displayBodywarning();
		}
		else {
			if (!isset($_GET["scene"])||!isset($_GET["role"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
			$sceneid = $mysqli->real_escape_string($_GET['scene']);
			$sceneObj = new Scene($mysqli, $sceneid);
			$sceneObj->loadValues();
			$role = $mysqli->real_escape_string($_GET['role']);
			if ($role==2||$role==4) {
				$timeArr = $sceneObj->getInternalTime();
				$res = $sceneObj->addParticipant($timeArr["dateTime"], $timeArr["minute"], $charcheck, $role);
				if (!$res) {
					include_once "header2.inc.php";
					para("Failed to join a scene. Maybe you're already in it or were kicked earlier.");
				}
				else header('Location: index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $sceneid);
			}
			else if ($role==3) {
				//check if actually invited
				$actualRole = $sceneObj->getParticipationStatus($charcheck);
				if ($actualRole==$role) {
					$timeArr = $sceneObj->getInternalTime();
					$res = $sceneObj->addParticipant($timeArr["dateTime"], $timeArr["minute"], $charcheck, $role);
					if (!$res) {
						include_once "header2.inc.php";
						para("Failed to join a scene. Maybe you're already in it.");
					}
					else header('Location: index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $sceneid);
				}
				else {
					include_once "header2.inc.php";
					para("Sorry, this scene is private and you're not invited.");
				}
			}
			else {
				include_once "header2.inc.php";
				para("Sorry, you cannot enter such a role with this form.");
			}
			para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=1' class='clist'>[Return to Scenes]</a>");
		}
	}
}
?>
