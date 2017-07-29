<?
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
			para("This character doesn't have a body so it cannot be played.");
		}
		else {
			$result = $curChar->removeListener($currentUser, $watcherRole);
			if ($result == 1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				para("For some reason, removing listener failed. If this persists, contact administrator.");
				echo "</div>\n";
			}
		}
	}
}
