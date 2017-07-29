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
		para("You cannot edit group settings when you're a watcher.");
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
				if (!isset($_POST['join'])||!isset($_POST['command'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				else if (!is_numeric($_POST['join'])||!is_numeric($_POST['command'])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				}
				else {
					$tg = $curChar->getTravelGroup();
					
					if ($tg == -1) {
						include_once "header2.inc.php";
						para("You haven't created a group yet. It's very easy, so why don't you do that first.");
					}
					else {
						$g = new Obj($mysqli, $tg);
						$g->changeGroupRule("join", round($_POST['join']));
						$g->changeGroupRule("command", round($_POST['command']));
						header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&message=1');
					}
				}
			}
		}
	}
}
?>
