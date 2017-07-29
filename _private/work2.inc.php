<?php
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
				if (!isset($_GET["pid"])||!isset($_GET["ap"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					if (!is_numeric($_GET["pid"])||!is_numeric($_GET["ap"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else {
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						$usedSlots = $entry->getUsedToolSlots();
						$tools = array();
						foreach ($usedSlots as $toolslot) {
							if (isset($_GET["slot-" . $toolslot])) {
								if (is_numeric($_GET["slot-" . $toolslot])) $tools[] = array (
									"slot" => $toolslot,
									"uid" => $_GET["slot-" . $toolslot]
								);
								else $tools[] = array (
									"slot" => $toolslot,
									"uid" => 0
								);
							}
							else $tools[] = array (
									"slot" => $toolslot,
									"uid" => 0
								);
						}
						$ptype = new ProjectType($mysqli, $entry->type);
						$usedPools = $ptype->getToolPools();
						$tools2 = array();
						
						if ($usedPools == -1) {
						}
						else {
							foreach ($usedPools as $pool) {
								if (isset($_GET["slot2-" . $pool["uid"]])) {
									if (is_numeric($_GET["slot2-" . $pool["uid"]])) $tools2[] = array (
										"slot" => $pool["uid"],
										"uid" => $_GET["slot2-" . $pool["uid"]]
									);
									else $tools2[] = array (
										"slot" => $pool["uid"],
										"uid" => 0
									);
								}
								else $tools2[] = array (
										"slot" => $pool["uid"],
										"uid" => 0
									);
							}
						}
						
						$result = $entry->investAP($_GET["ap"], $tools, $tools2);
						if ($result<100) {
							include_once "header2.inc.php";
							echo "<div class='displayarea'>";
						}
						
						if ($result==-1) para("You are completely out of AP.");
						else if ($result==-2) para("This project has already been finished before.");
						else if ($result==-3) para("Error: Object creation failed.");
						else if ($result==-4) para("You worked successfully but the project isn't finished yet.");
						else if ($result==-5) para("Not all of the tools are available.");
						else if ($result==-6) para("The fire is too small to be used on this fire. The firewood needs to catch fire first.");
						else if ($result==-7) para("There is no fire even though this needs one.");
						else if ($result==-8) para("The fire went out while you were working.");
						else if ($result==50) para("This project involves a passive waiting time until you can pick up the result. Check back later.");
						else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=4');
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
