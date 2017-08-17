<?php
//this needs the following post variables: charid, userid

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
	$pos = $curChar->getPosition();
	$watcherRole = $curChar->checkPermission($currentUser);
	
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
				para("You cannot take things on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_GET["container"])||!isset($_GET["ptype"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_GET["container"])||!is_numeric($_GET["ptype"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				$fireplace = new Obj($mysqli, $_GET["container"]);
				if ($fireplace->x==$pos->x&&$fireplace->y==$pos->y&&$fireplace->localx==$pos->lx&&$fireplace->localy==$pos->ly&&$fireplace->parent==0) {
					//to do: inside building?
					//to do: a function that tests if object is in $pos, taking $pos as input data
					//to do: recognize potential tinder and ignite
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
				}
				else {
					include_once "header2.inc.php";
					para("This fireplace isn't in the same location as your character.");
				}
				
				echo "<p class='right'>";
				ptag("a", "[Return to Items]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
				echo "</p>";
				echo "</div>";
			}
		}
	}
}
