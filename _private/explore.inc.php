<?
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";

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
	$inside = $curChar->building;
	
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
				para("You cannot explore on someone else's behalf when you're a watcher.");
			}
			else if ($inside>0) {
				include_once "header2.inc.php";
				echo "You cannot explore when you are in a group or inside a building. You need to exit first.";
			}
			else {
				$check = $curChar->explore(50);
				if ($check==1) {
					$local = new LocalMap($mysqli, $pos->x, $pos->y);
					$local->loadcreate();
					$newxy = $local->getDry($pos->lx, $pos->ly);
					$curChar->moveLocal($newxy[0], $newxy[1]);
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				}
				if ($check==-1) {
					include_once "header2.inc.php";
					echo "For some reason the files couldn't be created. Inform the game developer so we can investigate this.";
				}
				else if ($check==-2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=5');
				else if ($check==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=3');
			}
			para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
		}
	}
}
?>
