<?php
//this needs the following post variables: sel, charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_build_menu2.inc.php");

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
			displayBodywarning();
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot start projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=8' class='clist'>[Return to Manufacturing options]</a>");
			}
			else {
				if (!isset($_GET["sel"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					if (!is_numeric($_GET["sel"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=8' class='clist'>[Return to Manufacturing options]</a>");
					}
					else {
						include_once "header2.inc.php";
						echo "<div class='displayarea'>";
						$entry = new BuildMenu2($mysqli, $currentUser, $curChar->uid);
						$entry->printDataSingle($_GET["sel"]);
						echo "<p class='right'>";
						ptag("a", "[Return to Manufacturing options]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=8' class='clist'");
						echo "</p>";
						echo "</div>";
					}
				}
				
			}
		}
	}
}
?>
