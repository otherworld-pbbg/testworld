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
if (!isset($_GET["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_GET['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole>1) {
		para("You cannot create a group on someone else's behalf when you're a watcher.");
	}
	else {
		if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
		else {
			//user is authorized to view this character
			if (!isset($_GET['ocharid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
			else if (!is_numeric($_GET['ocharid'])) {
				header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
			}
			else {
				$tg = $curChar->getTravelGroup();
				if ($tg == -1) {
					include_once "header2.inc.php";
					para("You can't boot someone from your group if you don't have a group in the first place.");
				}
				else {
					$ochar = new Character($mysqli, $_GET['ocharid']);
					$bodyId = $ochar->getBasicData();
					if ($bodyId == -1) {
						include_once "header2.inc.php";
						echo "Other character id isn't valid.";
					}
					else {
						$go = new Obj($mysqli, $tg);
						$pas = $go->getPassengers();
						if ($pas==-1) para("You can't boot a person from your group if they aren't in it in the first place.");
						else {
							if (in_array($bodyId, $pas)) {
								$ochar->exitObject();
								header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&message=3');
							}
							para("You can't boot a person from your group if they aren't in it in the first place.");
						}
					}
				}
				echo "<p class='right'>";
				ptag("a", "Return to groups", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
				echo "</p>";
			}
		}
	}
}
?>
