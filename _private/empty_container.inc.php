<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("constants.php");

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
			else if (!isset($_POST["contentid"])||!isset($_POST["containerid"])||!isset($_POST["location"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_POST["contentid"])||!is_numeric($_POST["containerid"])||!is_numeric($_POST["location"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else if ($_POST["location"]<1||$_POST["location"]>2) {
				include_once "header2.inc.php";
				para("You're telling me that the container is not in your inventory or on the ground in the same location, what are you expecting me to do?");
			}
			else {
				$targetObject = new Obj($mysqli, $_POST["contentid"]);
				$targetObject->getBasicData();
				$oname = $targetObject->getName(false);
				if ($targetObject->parent!=$_POST["containerid"]) {
					include_once "header2.inc.php";
					para("Error: You're trying to interact with an object that's in another location.");
				}
				else {
					$is_countable = $targetObject->getAttribute(ATTR_COUNTABLE);
					$weight = $targetObject->approximateWeight();
					
					if ($is_countable&&$targetObject->pieces==1) {
						//redirect directly to processing with 1
						header('Location: index.php?page=lootcontainer&charid=' . $charcheck . '&userid=' . $currentUser . '&sel=3&targetid=' . $_POST["contentid"] . '&containerid=' . $_POST["containerid"] . '&location=' . $_POST["location"]);
					}
					else {
						include_once "header2.inc.php";
						ptag("h1", "Take out $oname");
						echo "<form action='index.php' method='get' id='lootform' name='lootform' class='narrow'>";
						if ($is_countable) para("There are $targetObject->pieces of these.");
						para("What there is weighs $weight.");
						echo "<p>";
						ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
						echo "Grams to take* ";
						ptag("input", "", "name='grams' type='text'");
						echo "</p>";
						if ($is_countable) {
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='2'");
							echo "Or pieces to take ";
							ptag("input", "", "name='pieces' type='text'");
							echo "</p>";
						}
						echo "<p>";
						ptag("input", "", "name='sel' type='radio' value='3'");
						echo "Or take all</p>";
						ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
						ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
						ptag("input" , "", "type='hidden' name='page' value='lootcontainer'");
						ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["contentid"] . "'");
						ptag("input" , "", "type='hidden' name='containerid' value='" . $_POST["containerid"] . "'");
						ptag("input" , "", "type='hidden' name='location' value='" . $_POST["location"] . "'");
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Take selected'");
						echo "</p>";
						echo "</form>";
						para("*) Do bear in mind that without a scale, you cannot know the exact weight of what there is or how much you're taking.");
						para("If you choose to take more than what there is, it will take the whole thing.");
						para("If a resource is counted in pieces and you try to take a weight smaller than one piece, it will round it to the smallest possible part.");
					}
					
				}
			}
		}
	}
}
