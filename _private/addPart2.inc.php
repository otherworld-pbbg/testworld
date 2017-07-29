<?php
//this needs the following post variables: pid, charid, userid, slot, preset

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_project.inc.php");
include_once("class_resource.inc.php");

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
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot add components to projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["source"])||!isset($_GET["pid"])||!isset($_GET["slot"])||!isset($_GET["preset"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					if (!is_numeric($_GET["source"])||!is_numeric($_GET["pid"])||!is_numeric($_GET["slot"])||!is_numeric($_GET["preset"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else {
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						$info = $entry->addComponentFromSource($_GET["source"], $_GET["slot"], $_GET["preset"]);
						
						if ($info<0) include_once "header2.inc.php";
						if ($info==-1) para("This project already has progress so it apparently has all it needs.");
						else if ($info==-2) para("There's no need for this component, at least not in this slot.");
						else if ($info==-3) para("Another type has been picked for this slot. If you want to use this type instead, you need to remove the other type first.");
						else if ($info==-4) para("This slot is already full.");
						else if ($info==-5) para("Error: Invalid object selection.");
						else if ($info==-6) para("Error: Adding component failed.");
						else if ($info==-7) para("Error: Duplication bug. Contact developers and don't abuse this.");
						else {
							header('Location: index.php?page=viewProject&userid=' . $currentUser. '&charid=' . $charcheck . '&pid=' . $_GET["pid"]);
						}
						echo "<p class='right'>";
						ptag("a", "[Return to Activities]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
						echo "</p>";
					}
				}
				
			}
		}
	}
}
?>
