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
			displayBodywarning();
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot take things on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_GET["sel"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_GET["sel"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				$material = new Obj($mysqli, $_GET["sel"]);
				if ($material->parent==$curChar->bodyId) {
					//to do: inside building?
					$fire_effect = $material->getAttribute(ATTR_IGNITION);
					if ($fire_effect==1||$fire_effect==2) {
						$starter = $curChar->checkFirestarter();
						if ($starter) {
							$result = $material->ignite();
							if ($result==100||$result==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
							else {
								include_once "header2.inc.php";
								para("There was an error with the ignition.");
							}
						}
						else {
							include_once "header2.inc.php";
							para("You don't have a fire bow, so you can't start a fire.");
						}
					}
				}
				else {
					include_once "header2.inc.php";
					para("The material you're trying to ignite isn't in your inventory.");
				}
				
				echo "<p class='right'>";
				ptag("a", "[Return to Items]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
				echo "</p>";
				echo "</div>";
			}
		}
	}
}
