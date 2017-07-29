<?
//this needs the following post variables: pid, charid, userid, slot, res

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
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						$usedSlots = $entry->getUsedToolSlots();
						$entry2 = new ProjectType($mysqli, $entry->type);
						$usedPools = $entry2->getToolPools();//uid, name
						$ok = true;
						echo "<div class='displayarea'>";
						
						echo "<form class='medium'>";
						ptag("h2", "Available tools");
						if ($usedPools==-1) para("This doesn't require any pooled tools.");
						else foreach($usedPools as $slot) {
							$tools = $entry->getPoolToolsAvailable($slot["uid"]);
							if (!$tools) {
								para("No tool available for this slot");
								ptag("input", "", "type='hidden' name='slot2-" . $slot . "' value='0'");
								$ok = false;
							}
							else {
								$selected = "checked='checked'";
								foreach ($tools as $option) {
									$tool_o = new Obj($mysqli, $option["uid"]);
									$tool_o->getBasicData();
									if ($option["fuel"]) $needsFuel = " (needs fuel)";
									else $needsFuel = "";
									echo "<p>";
									ptag("input", "", "type='radio' name='slot2-" . $slot["uid"] . "' value='" . $option["uid"] . "' $selected");
									ptag("label", $tool_o->getName() . $needsFuel);
									echo "</p>";
									$selected = "";
								}
							}
							para("---");
						}
						
						if (!$usedSlots) para("This doesn't require any individual tools.");
						else foreach($usedSlots as $slot) {
							$tools = $entry->getToolsAvailable($slot);
							if (!$tools) {
								para("No tool available for this slot");
								ptag("input", "", "type='hidden' name='slot-" . $slot . "' value='0'");
								$ok = false;
							}
							else {
								$selected = "checked='checked'";
								foreach ($tools as $option) {
									echo "<p>";
									ptag("input", "", "type='radio' name='slot-" . $slot . "' value='" . $option["uid"] . "' $selected");
									ptag("label", $option["name"]);
									echo "</p>";
									$selected = "";
								}
							}
							para("---");
						}
						ptag("h2", "AP to invest");
						
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
							$needed_ap = $entry->ap-$entry->invested;
							para("This project needs " . $needed_ap . " AP to be finished with average tools and resources.");
							para("The efficiency of tools and hardness of materials can affect AP.");
							para("If you're working with less than optimal tools or harder materials, it's okay to invest some extra AP because it won't deduct any more than is needed.");
							echo "<p>";
							ptag("label", "AP to invest: ", "for='ap'");
							ptag("input", "", "type='text' name='ap' value='$needed_ap' size='6'");
							echo "</p>";
							ptag("input", "", "type='hidden' name='charid' value='$curChar->uid'");
							ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input", "", "type='hidden' name='pid' value='" . $_GET["pid"] . "'");
							ptag("input", "", "type='hidden' name='page' value='work2'");
							echo "<p class='right'>";
							if (!$usedSlots&&$usedPools==-1) ptag("input", "", "type='submit' value='Work with no tools'");
							else if ($ok) ptag("input", "", "type='submit' value='Work with selected tools'");
							else ptag("input", "", "type='button' disabled='disabled' value=\"Can't proceed (tools absent)\"");
							echo "</p>";
						}
						
						
						echo "</form>";
						
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
