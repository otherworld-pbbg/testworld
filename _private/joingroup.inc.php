<?
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
		para("You cannot join a group on someone else's behalf when you're a watcher.");
	}
	else {
		if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
		else {
			//user is authorized to view this character
			$bodyId = $curChar->getBasicData();
			if ($bodyId == -1) {
				include_once "header2.inc.php";
				echo "This character doesn't have a body so it cannot be played.";
			}
			else {
				if (!isset($_POST['ocharid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				else if (!is_numeric($_POST['ocharid'])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				}
				else {
					$ochar = new Character($mysqli, $_POST["ocharid"]);
					$tg = $ochar->getTravelGroup();
					
					if ($tg == -1) {
						include_once "header2.inc.php";
						para("This person hasn't set up a group yet or the character id isn't valid.");
					}
					else {
						$g = new Obj($mysqli, $tg);
						$rule = $g->getGroupRule("join");
						if ($rule==0) {
							include_once "header2.inc.php";
							para("You can't join this group since it's not accepting new members.");
						}
						else if ($rule==1) {
							$curChar->enterObject($tg, "join");
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&message=2');
						}
						else {
							$invitation = $curChar->getCharRule($tg, 2);
							if ($invitation==1) {
								$curChar->enterObject($tg, "join");
								header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&message=2');
							}
							else {
								include_once "header2.inc.php";
								para("You can't join this group since you're not invited.");
							}
						}
						
					}
				}
			}
		}
	}
}
?>
