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
		if ($bodyId == -1) displayBodywarning();
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot create fields on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
			}
			else {	
				$localMap = new LocalMap($mysqli, $curChar->x, $curChar->y);//to do: what if the char is in a building?
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
				else if (!isset($_POST["fieldsel"])) {
					include_once "header2.inc.php";
					para("I don't know what you're trying to do. Most likely you forgot to select a field.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if (!is_numeric($_POST["fieldsel"])) {
					include_once "header2.inc.php";
					para("Invalid field id.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if (isset($_POST["color1"])) {
					$target = new FieldArea($mysqli, $curChar->x, $curChar->y, false, round($_POST["fieldsel"]));//The rounding prevents errors in case some jerk enters a float
					$target->checkCoords();
					if ($target->gx!=$curChar->x||$target->gy!=$curChar->y) {
						include_once "header2.inc.php";
						para("Error, you are trying to access a field that's in another location.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
					}
					else {
						$check = $target->updateHex($_POST["color1"]);
						if ($check) header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck");
						else {
							include_once "header2.inc.php";
							para("Updating color failed for some reason. Maybe you were trying to save it as the same value it already was?");
							para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
						}
					}
				}
				else if (isset($_POST["task"])) {
					$target = new FieldArea($mysqli, $pos->x, $pos->y, false, round($_POST["fieldsel"]));//The rounding prevents errors in case some jerk enters a float
					$target->checkCoords();
					if ($target->gx!=$pos->x||$target->gy!=$pos->y) {
						include_once "header2.inc.php";
						para("Error, you are trying to access a field that's in another location.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
					}
					else {
						if ($_POST["task"]==0) {
							//Plough
							if (isset($_POST["tool"])) {
								if (is_numeric($_POST["tool"])) {
									$processed = $target->processFields($charcheck, 1, 0, round($_POST["tool"]));
									$count= sizeof($processed);
									header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&processed=$count");
								}
								include_once "header2.inc.php";
								para("Invalid tool id.");
								para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
							}
							else {
								include_once "header2.inc.php";
								para("You can't plough without using some sort of a tool.");
								para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
							}
						}
						if ($_POST["task"]==1) {
							//Sow
							if (isset($_POST["seed"])) {
								if (is_numeric($_POST["seed"])) {
									$processed = $target->processFields($charcheck, 1, 1, 0, round($_POST["seed"]));
									$count= sizeof($processed);
									header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&processed=$count");
								}
								include_once "header2.inc.php";
								para("Invalid seed.");
								para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
							}
							else {
								include_once "header2.inc.php";
								para("You need some sort of seed in order to sow.");
								para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
							}
						}
					}
				}
			}
		}
	}
}
?>
