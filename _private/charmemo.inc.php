<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once ("generic.inc.php");

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
if (!isset($_REQUEST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_REQUEST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	
	
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else if ($watcherRole>1) {
		include_once "header2.inc.php";
		ptag("p", "You are a watcher, so you're not authorized to edit another person's memo.");
	}
	else {
		//user is authorized to view this character
		
		include_once "header2.inc.php";
		echo "<div class='bar'>\n";
		if (isset($_POST["txt"])) {
			$safetext = $mysqli->real_escape_string($_POST['txt']);
			$result = $curChar->saveMemo($safetext);
			if ($result==1) para("Status: Saved");
			if ($result==-1) para("Adding memo failed");
			if ($result==-2) para("Couldn't update memo. Maybe there were no changes?");
		}
		echo "<div class='left_header'>\n";
		ptag("h4", "Current character:");
		para($curChar->cname);
		echo "</div></div>";
		$memo = $curChar->getMemo();
		if ($memo == -1) $memo2 = "";
		else $memo2 = cleanup($memo["txt"]);//Use this for any string that gets placed in a text field
		echo "<div class='bar'>\n";
		ptag("h1", "Update character memo");
		echo "<form action='index.php?page=charmemo' method='post' class='narrow'>";
		if ($memo2 == "") para("No memo has been recorded yet");
		else para("Current memo: " . $memo2);
		echo "<p>";
		ptag("textarea", $memo2, "type='text' rows='3' cols='60' maxlength='400' name='txt' id='txt'");
		ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
		ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
		echo "</p>";
		echo "<p class='right'>";
		ptag("input", "", "type='submit' value='Save'");
		echo "</p>";
		echo "</form>";
		para("You can store for example your character's plans and goals, or record a message for your watchers. Also if your character has an rp trait you need to keep in mind, you can record it here.");
		echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		echo "</div>";
		
	}
}
?>
