<?php
//this needs the following get variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_obj.inc.php");

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
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {	
			$combat = $curChar->checkCurrentCombat();
			if ($combat==-1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2&errormessage=1');
			if ($watcherRole>1) header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser);
			
			$check = $curChar->flee();
			
			if ($check<0) {
				include_once "header2.inc.php";
				if ($check==-1) para("Error: Failed to leave combat for some reason.");
				else if ($check==-2) para("The enemy stops you from leaving. Try again if you can.");
				echo "<p class='right'>";
				ptag("a", "[Return to Combat]", "href='index.php?page=combat&charid=" . $charcheck . "&userid=" . $currentUser . "' class='clist'");
				echo "</p>";
			}
			else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
		}
	}
}
?>
