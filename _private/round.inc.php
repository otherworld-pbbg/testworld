<?php
//this needs the following get variables: charid, type_sel, wpn_sel

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
			if ($watcherRole>1||!isset($_GET['target_sel'])||!isset($_GET['type_sel'])||!isset($_GET['wpn_sel'])) header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser);
			if (!is_numeric($_GET['target_sel'])||!is_numeric($_GET['type_sel'])||!is_numeric($_GET['wpn_sel'])) header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser);
			
			$type = $_GET['type_sel'];
			$wpn = $_GET['wpn_sel'];
			$target = $_GET['target_sel'];
			
			$check = $curChar->processRound($target, $type, $wpn);
			
			if (is_array($check)) {
				include_once "header2.inc.php";
				echo "Failed trying to use query: " . $check["attempt"] . " Check for errors";
			}
			else if ($check<0) {
				include_once "header2.inc.php";
				if ($check==-1) para("Error: You tried to attack someone who is not participating in this combat.");
				else if ($check==-2) para("Error: You tried to attack someone who is not participating in this combat or is ahead of you on their timeline.");
				else if ($check==-3) para("Error: You tried to use a weapon that is not in your inventory.");
				else if ($check==-4) para("Error: You tried to use a combat type that is not yet implemented.");
				else if ($check==-5) para("Error: The defender is not an animal");
				else if ($check==-6) para("Error: The defender has no attack types");
				else if ($check==-7) para("Error: The defender has no strategy");
				else if ($check==-8) para("Error: Processing counter attack failed");
				echo "<p class='right'>";
				ptag("a", "[Return to Combat]", "href='index.php?page=combat&charid=" . $charcheck . "&userid=" . $currentUser . "' class='clist'");
				echo "</p>";
			}
			else header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser . '&type_sel=' . $type . '&wpn_sel=' . $wpn);
		}
	}
}
?>
