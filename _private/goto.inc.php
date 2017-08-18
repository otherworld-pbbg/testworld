<?php
//this needs the following post variables: direction, charid, userid

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
	$pos = $curChar->getPosition();
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			displayBodywarning();
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot move on someone else's behalf when you're a watcher.");
			}
			else {
				if (!isset($_POST["sel3"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				}
				else if (!is_numeric($_POST["sel3"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				else {
					$target = new Obj($mysqli, $_POST["sel3"]);
					$target->getBasicData();
					if ($target->x!=$pos->x||$target->y!=$pos->y) {
						include_once "header2.inc.php";
						para("You're trying to jump to another location without using AP. Naughty naughty.");
					}
					else {
						if ($curChar->building>0) {
							//at this point it can only be a travel group, but later it can be a building too
							$tg = new Obj($mysqli, $curChar->building);
							$rule = $tg->getGroupRule("command");
							if ($rule<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=8');
							else if ($rule==2) {
								$authorization = $curChar->getCharRule($curChar->building, 1);
								if ($authorization<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=8');
							}
							$obody = new Obj($mysqli, $tg->parent);
							$ocharid = $obody->getCharid();
							if ($ocharid<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=7');
							$ochar = new Character($mysqli, $ocharid);
						}
						if ($curChar->building>0) $check = $ochar->moveLocal($target->localx, $target->localy);
						else $check = $curChar->moveLocal($target->localx, $target->localy);
						if ($check==-1) {
							include_once "header2.inc.php";
							echo "Failed to move for some reason. Maybe you were trying to move into a square in which you already were.";
						}
						else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
					}
				}
				
			}
		}
	}
}
?>
