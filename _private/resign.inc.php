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
			include_once "header2.inc.php";
			echo "<div class='bar'>\n";
			ptag("h1", "Resign as watcher");
			para("You're about to resign as a watcher for " . $curChar->cname . ". In most cases, this is permanent. Are you sure you want to do this?");
			para("<a href='index.php?page=resign2&charid=$charcheck&userid=$currentUser' class='clist'>[Yes, I'm sure]</a>");
			para("<a href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=1' class='clist'>[No, take me back]</a>");
			echo "</div>";
		}
	}
}
