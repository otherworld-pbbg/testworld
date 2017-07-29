<?php
//this needs the following post variables: pid, charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_project.inc.php");

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
				para("You cannot start projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["pid"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					include_once "header2.inc.php";
					if (!is_numeric($_GET["pid"])) {
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					
					else {
						echo "<div class='displayarea'>";
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						if ($entry->invested>=$entry->ap) {
							$check = $entry->finish();
							if ($check==100) para("This project is ready now. You can go to the Items page for results");
							else if ($check==50) para("You've done all you can to advance this project, now you will have to wait a while until it's finished.");
							else if ($check==-3) para("An error occurred and this project couldn't be finished.");
							else if ($check==-4) para("You followed an expired link. This project has already been finished earlier.");
							else para("Unexpected scenario, please report.");
							
							echo "<p class='right'>";
							ptag("a", "[Go to Items page]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
							echo "</p>";
						}
						else {
							$entry->printInfo();
							$readiness = $entry->getReadiness();
							if ($readiness) {
								echo "<form action='index.php' class='medium'>";
								para("This project has all the resources and components it needs, so you can work on it assuming you have the necessary tools.");
								echo "<p class='right'>";
								ptag("input", "", "type='submit' value='Proceed'");
								echo "</p>";
								ptag("input", "", "type='hidden' name='page' value='work'");
								ptag("input", "", "type='hidden' name='charid' value='$curChar->uid'");
								ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
								ptag("input", "", "type='hidden' name='pid' value='" . $_GET["pid"] . "'");
								echo "</form>";
							}
							else para("This project is missing build requirements, so it cannot be worked on yet.");
							echo "<p class='right'>";
							ptag("a", "[Return to Activities]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
							echo "</p>";
							echo "</div>";
						}
					}
				}
				
			}
		}
	}
}
?>
