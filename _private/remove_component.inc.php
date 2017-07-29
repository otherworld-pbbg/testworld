<?
//this needs the following post variables: pid, charid, userid, slot

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
				para("You cannot interact with projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["pid"])||!isset($_GET["slot"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					if (!is_numeric($_GET["pid"])||!is_numeric($_GET["slot"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else {
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						
						if ($entry->invested>0) {
							include_once "header2.inc.php";
							echo "<div class='displayarea'>";
							ptag("h1", "Attempting to remove components from a project");
							para("This project already has progress so you can't take stuff off anymore.");
						}
						else {
							$success = $entry->emptyComponentSlot($_GET["slot"]);
							if ($success==-1) {
								include_once "header2.inc.php";
								echo "<div class='displayarea'>";
								ptag("h1", "Attempting to remove components from a project");
								para("The slot was already empty to begin with.");
							}
							else if ($success==-2) {
								include_once "header2.inc.php";
								echo "<div class='displayarea'>";
								ptag("h1", "Attempting to remove components from a project");
								para("Removing the component failed for some reason.");
							}
							else if ($success==-3) {
								include_once "header2.inc.php";
								echo "<div class='displayarea'>";
								ptag("h1", "Attempting to remove components from a project");
								para("The slot was emptied but accidentally the components inside got deleted. Sorry about that.");
							}
							else header('Location: index.php?page=viewProject&userid=' . $currentUser. '&charid=' . $charcheck . '&pid=' . $_GET["pid"]);
						}
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
?>
