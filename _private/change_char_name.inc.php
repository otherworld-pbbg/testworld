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
		para("You cannot change names on someone else's behalf when you're a watcher.");
	}
	else {
		if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
		else {
			//user is authorized to view this character
			$bodyId = $curChar->getBasicData();
			if ($bodyId == -1) {
				include_once "header2.inc.php";
				displayBodywarning();
			}
			else {
				if (!isset($_POST['ocharid'])||!isset($_POST['nametext'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=5');
				else if (!is_numeric($_POST['ocharid'])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=5');
				}
				else {
					$ochar = new Character($mysqli, $_POST['ocharid']);
					$oBodyId = $ochar->getBasicData();
					if ($oBodyId == -1) {
						include_once "header2.inc.php";
						para("It appears you're trying to name someone incorporeal.");
					}
					else {
						$name = $mysqli->real_escape_string($_POST['nametext']);
						$curChar->nameObject($oBodyId, $name);
						header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=5');
					}
				}
			}
		}
	}
}
?>
