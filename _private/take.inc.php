<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once("class_group.inc.php");
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
	$pos = $curChar->getPosition();
	
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
			displayBodywarning();
		}
		else {
			if (!isset($_GET['sel'])||!isset($_GET['targetid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_GET['sel'])||!is_numeric($_GET['targetid'])) {
				include_once "header2.inc.php";
				para("Error: Something is not numeric.");
			}
			else {
				if (isset($_GET['group'])) {
					if (!is_numeric($_GET['group'])) {
							include_once "header2.inc.php";
							para("Error: Something is not numeric.");
							$group = false;
					}
					else $group = true;
				}
				else $group = false;
				if ($_GET['sel']==1) {
					if (!isset($_GET['grams'])) para("Error: Amount is not set.");
					else if (!is_numeric($_GET['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_GET['grams']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						if ($group) {
							$ng = new NPCgroup($mysqli, $_GET['group']);
							$res1 = $ng->withdraw($charcheck, $_GET['targetid'], "weight", $_GET['grams']);
						}
						else $res1 = $curChar->takeObject($_GET['targetid'], "weight", $_GET['grams']);
						if ($res1==-2) {
							include_once "header2.inc.php";
							para("Duplication bug, please report");
						}
						else if ($res1==-1) {
							include_once "header2.inc.php";
							para("Claiming that would make the group lose any remaining respect they have for you, so you can't do that.");
						}
						else if ($res1==1) {
							if ($group) header('Location: index.php?page=viewgroup&charid=' . $charcheck . '&groupid=' . $_GET['group']);
							else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						}
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
						if ($group) {
							$ng = new NPCgroup($mysqli, $_GET['group']);
							$res2 = $ng->withdraw($charcheck, $_GET['targetid'], "pieces", $_GET['pieces']);
						}
						else $res2 = $curChar->takeObject($_GET['targetid'], "pieces", $_GET['pieces']);
						if ($res2==-2) {
							include_once "header2.inc.php";
							para("Duplication bug, please report");
						}
						else if ($res2==-1) {
							include_once "header2.inc.php";
							para("Claiming that would make the group lose any remaining respect they have for you, so you can't do that.");
						}
						else if ($res2==1) {
							if ($group) header('Location: index.php?page=viewgroup&charid=' . $charcheck . '&groupid=' . $_GET['group']);
							else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						}
						else {
							include_once "header2.inc.php";
							para("Taking failed. Perhaps you were trying to reach for something that was too far or multiples of a non-stackable object?");
						}
					}
				}
				else if ($_GET['sel']==3) {
					if ($group) {
						$ng = new NPCgroup($mysqli, $_GET['group']);
						$res3 = $ng->withdraw($charcheck, $_GET['targetid'], "whole");
					}
					else $res3 = $curChar->takeObject($_GET['targetid'], "whole");
					if ($res3==-2) {
							include_once "header2.inc.php";
							para("Duplication bug, please report");
						}
					else if ($res3==-1) {
						include_once "header2.inc.php";
						para("Claiming that would make the group lose any remaining respect they have for you, so you can't do that.");
					}
					else if ($res3==1) {
						if ($group) header('Location: index.php?page=viewgroup&charid=' . $charcheck . '&groupid=' . $_GET['group']);
						else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					}
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
