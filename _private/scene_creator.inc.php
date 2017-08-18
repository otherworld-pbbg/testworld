<?php
include_once $privateRoot . "/class_player.inc.php";
include_once $privateRoot . "/class_character.inc.php";
include_once $privateRoot . "/class_time.inc.php";
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
		include_once "header2.inc.php";
		if ($bodyId == -1) displayBodywarning();
		else {
			ptag("h2" , "Scene creator");
			echo "<form action='index.php?page=createscene' method='post' class='narrow' autocomplete='off' id='sceneform'>";
			echo "<p>";
			ptag("label", "Title: ", "for='scenetitle'");
			ptag("input", "", "type='text' id='scenetitle' name='scenetitle' size='60' autofocus");
			echo "</p><p>";
			ptag("label", "Public description: ", "for='scenedesc'");
			echo "</p><p>";
			ptag("textarea", "", "id='scenedesc' name='scenedesc' rows='4' cols='60'");
			echo "</p><p>";
			echo "<input type='radio' name='privacy' value='1' checked>Public</input>";
			echo "<input type='radio' name='privacy' value='2'>Private*</input>";
			echo "<input type='radio' name='privacy' value='3'>Strictly private**</input>";
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			echo "</p><p>";
			ptag("label", "Duration: ", "for='duration'");
			echo "<select id='duration' name='duration' form='sceneform'>";
			ptag("option", "5 minutes", "value='1' selected='selected'");
			ptag("option", "10 minutes", "value='2'");
			ptag("option", "15 minutes", "value='3'");
			ptag("option", "20 minutes", "value='4'");
			ptag("option", "25 minutes", "value='5'");
			ptag("option", "30 minutes", "value='6'");
			ptag("option", "35 minutes", "value='7'");
			ptag("option", "40 minutes", "value='8'");
			ptag("option", "45 minutes", "value='9'");
			ptag("option", "50 minutes", "value='10'");
			ptag("option", "55 minutes", "value='11'");
			ptag("option", "1 hour", "value='12'");
			ptag("option", "2 hours", "value='13'");
			ptag("option", "3 hours", "value='14'");
			ptag("option", "4 hours", "value='15'");
			echo "</select>";
			echo "</p><p class='right'>";
			ptag("input", "", "type='submit' name='submit' value='Create'");
			echo "</p></form>";
			para("*) Eavesdropping is possible.");
			para("**) Only allowed in selected locations.");
		}
	}
}
?>
