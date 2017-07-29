<?
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("class_preset.inc.php");

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
	$pos = $curChar->getPosition();
	
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
				include_once "header2.inc.php";
				para("You cannot start a fight on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_POST["target"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_POST["target"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				$enemy =  new Obj($mysqli, $_POST["target"]);
				$enemy->getBasicData();
				if ($enemy->type!=4) {
					include_once "header2.inc.php";
					para("The target is not an animal. Currently you can only attack animals.");
				}
				else if ($enemy->x!=$pos->x||$enemy->y!=$pos->y||$enemy->localx!=$pos->lx||$enemy->localy!=$pos->ly) {
					include_once "header2.inc.php";
					para("The target is too far away.");
				}
				else {
					$result = $curChar->initiateCombat($_POST["target"]);
					if ($result == -1) {
						include_once "header2.inc.php";
						para("Error, failed to start combat. Try again or contact developer if this persists.");
					}
					else if ($result == -2) {
						include_once "header2.inc.php";
						para("Error, failed to join combat. Please inform developer.");
					}
					else header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser);
				}
			}
			para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
		}
	}
}
