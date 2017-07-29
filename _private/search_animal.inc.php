<?php
//this needs the following post variables: charid, userid, hours, minutes

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
				para("You cannot search for animals on someone else's behalf when you're a watcher.");
				echo "<p class='right'>";
				ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
				echo "</p>";
			}
			else {
				if (!isset($_GET["duration"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else {
					if (!is_numeric($_GET["duration"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						echo "<p class='right'>";
						ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
						echo "</p>";
					}
					else if ($_GET["duration"]<1||$_GET["duration"]>5) {
						include_once "header2.inc.php";
						para("AP out of range. Aborting.");
						echo "<p class='right'>";
						ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
						echo "</p>";
					}
					else {
						$ap = round($_GET["duration"])*10;
						$status = $curChar->searchAnimals($ap);
						if ($status<0) include_once "header2.inc.php";
						if ($status==-1) para("You tried to use more AP than you have.");
						else if ($status==-2) para("You couldn't find any animals.");
						else if ($status==-3) para("Animal generation failed. Please inform developer.");
						if ($status<0) {
							echo "<p class='right'>";
							ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
							echo "</p>";
						}
						else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
					}
				}
				
			}
		}
	}
}
?>
