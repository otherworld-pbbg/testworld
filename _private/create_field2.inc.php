<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_field_area.inc.php");

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
	$pos = $curChar->getPosition();
	$watcherRole = $curChar->checkPermission($currentUser);
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) echo "This character doesn't have a body so it cannot be played.";
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot create fields on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
			}
			else {	
				$localMap = new LocalMap($mysqli, $pos->x, $pos->y);//to do: what if the char is in a building?
				$localCheck = $localMap->checkIfExists();
				
				if ($localCheck == -1) {
					include_once "header2.inc.php";
					para("You can't farm here since you're in middle of water.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if ($localCheck == -2) {
					include_once "header2.inc.php";
					para("You shouldn't be here because this location hasn't been explored.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if (isset($_POST["color1"])) {
					$secure = $mysqli->real_escape_string($_POST['color1']);
					
					$new = new FieldArea($mysqli, $pos->x, $pos->y, $secure);
					$check = $new->getUid();
					
					if ($check) header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&field=$check");
					else {
						include_once "header2.inc.php";
						para("Creating a field failed for some reason. Sorry about that.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
					}
				}
				else {
					include_once "header2.inc.php";
					para("This page didn't receiver any information on what color you want your field to be. Go back and pick a color.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
			}
		}
	}
}
?>
