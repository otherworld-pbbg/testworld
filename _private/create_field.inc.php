<?php
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
				else {	
					include_once "header2.inc.php";
					echo "<div class='displayarea'>";
					ptag("h1", "Create field");
					para("All you need to do here is select the color for the outline to show on the map and you're all set. Squares will be added separately.");
					echo "<form class='narrow' action='index.php?page=createField2' method='post'>";
					echo "<p>Border color: ";
					echo '<input name="color1" class="jscolor {hash:true}" value="#99cc00">';
					echo "</p>";
					ptag("input", "", "type='hidden' name='charid' value=$charcheck");
					echo "<p class='right'>";
					ptag("input", "", "type='submit' value='Create field'");
					echo "</p>\n";
					echo "</form>";
					para("Note that if you enter some string that is not a valid color code, pure red will be substituted. You probably don't want that.");
					para("You might also want to pick colors you won't mix with each other, especially if you have some level of color blindness.");
					echo "</div>";
				}
			}
		}
	}
}
?>
