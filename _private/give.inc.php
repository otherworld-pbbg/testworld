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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	$pos = $curChar->getPosition();//to do: what if the character is locked in a building? Currently they can see outside
	
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
			if (!isset($_POST['sel'])||!isset($_POST['targetid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_POST['sel'])||!is_numeric($_POST['targetid'])) para("Error: Something is not numeric.");
			else {
				$possibleGroupGive = false;
				$obj = new Obj($mysqli, $_POST['targetid']);
				if ($_POST['sel']==1) {
					if (!isset($_POST['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_POST['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_POST['grams']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else if (isset($_POST['ochar'])) {
						if (!is_numeric($_POST['ochar'])) para("Error: Receiver id is not numeric.");
						if ($_POST['ochar']==0) $possibleGroupGive = true;
						else {
							$res1 = $curChar->give($_POST['ochar'], $_POST['targetid'], "weight", $_POST['grams']);
							if ($res1==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1');
							include_once "header2.inc.php";
							if ($res1==-1) para("You're trying to give something not in your inventory.");
							else if ($res1==-2) para("You're trying to give something to a person who is currently in another location.");
							else if ($res1==-3) para("Generating a new pile in the receiver's inventory failed.");
							else if ($res1==-4) para("Failed to move stuff from your inventory to the receiver.");
							else if ($res1==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
						}
					}
					else $possibleGroupGive = true;
					if ($possibleGroupGive&&isset($_POST['groupid'])) {
						$group = new NPCgroup($mysqli, $_POST['groupid']);
						$res1 = $curChar->giveNow($_POST['groupid'], $_POST['targetid'], "weight", $_POST['grams']);
						if ($res1==1) {
							$value = $group->getValueDonation($obj->preset, $obj->secondary, $_POST['grams']);
							$group->updateOpinion($curChar->bodyId, 1, $value);
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1');
						}
						include_once "header2.inc.php";
						if ($res1==-1) para("You're trying to give something not in your inventory.");
						else if ($res1==-2) para("You're trying to give something to a group who is currently in another location.");
						else if ($res1==-3) para("Generating a new pile in the group inventory failed.");
						else if ($res1==-4) para("Failed to move stuff from your inventory to the receiver.");
						else if ($res1==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
					}
				}
				else if ($_POST['sel']==2) {
					if (!isset($_POST['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_POST['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_POST['pieces']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else if (isset($_POST['ochar'])) {
						if (!is_numeric($_POST['ochar'])) para("Error: Receiver id is not numeric.");
						if ($_POST['ochar']==0) $possibleGroupGive = true;
						else {
							$res2 = $curChar->give($_POST['ochar'], $_POST['targetid'], "pieces", $_POST['pieces']);
							if ($res2==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1');
							include_once "header2.inc.php";
							if ($res2==-1) para("You're trying to give something not in your inventory.");
							else if ($res2==-2) para("You're trying to give something to a person who is currently in another location.");
							else if ($res2==-3) para("Generating a new pile in the receiver's inventory failed.");
							else if ($res2==-4) para("Failed to move stuff from your inventory to the receiver.");
							else if ($res2==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
						}
					}
					else $possibleGroupGive = true;
					if ($possibleGroupGive&&isset($_POST['groupid'])) {
						$group = new NPCgroup($mysqli, $_POST['groupid']);
						$res1 = $curChar->giveNow($_POST['groupid'], $_POST['targetid'], "pieces", $_POST['pieces']);
						if ($res1==1) {
							$value = $group->getValueDonation($obj->preset, $obj->secondary, false, $_POST['pieces']);
							$group->updateOpinion($curChar->bodyId, 1, $value);
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1');
						}
						include_once "header2.inc.php";
						if ($res1==-1) para("You're trying to give something not in your inventory.");
						else if ($res1==-2) para("You're trying to give something to a group who is currently in another location.");
						else if ($res1==-3) para("Generating a new pile in the group inventory failed.");
						else if ($res1==-4) para("Failed to move stuff from your inventory to the receiver.");
						else if ($res1==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
					}
				}
				else if ($_POST['sel']==3) {
					if (isset($_POST['ochar'])) {
						if (!is_numeric($_POST['ochar'])) para("Error: Receiver id is not numeric.");
						if ($_POST['ochar']==0) $possibleGroupGive = true;
						else {
							$res3 = $curChar->give($_POST['ochar'], $_POST['targetid'], "whole");
							if ($res3==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1');
							include_once "header2.inc.php";
							if ($res3==-1) para("You're trying to give something not in your inventory.");
							else if ($res3==-2) para("You're trying to give something to a person who is currently in another location.");
							else if ($res3==-3) para("Generating a new pile in the receiver's inventory failed.");
							else if ($res3==-4) para("Failed to move stuff from your inventory to the receiver.");
							else if ($res3==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
						}
					}
					else $possibleGroupGive = true;
					if ($possibleGroupGive&&isset($_POST['groupid'])) {
						$group = new NPCgroup($mysqli, $_POST['groupid']);
						$res1 = $curChar->giveNow($_POST['groupid'], $_POST['targetid'], "whole");
						if ($res1==1) {
							$value = $group->getValueDonation($obj->preset, $obj->secondary);
							$group->updateOpinion($curChar->bodyId, 1, $value);
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=1&val='.$value);
						}
						include_once "header2.inc.php";
						if ($res1==-1) para("You're trying to give something not in your inventory.");
						else if ($res1==-2) para("You're trying to give something to a group who is currently in another location.");
						else if ($res1==-3) para("Generating a new pile in the group inventory failed.");
						else if ($res1==-4) para("Failed to move stuff from your inventory to the receiver.");
						else if ($res1==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
					}
				}
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'>[Go back]</a>");
			}
		}
	}
}
?>
