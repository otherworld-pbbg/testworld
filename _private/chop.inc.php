<?php
//this needs the following post variables: sel, charid, userid, slot, res

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
		if ($bodyId == -1) echo "This character doesn't have a body so it cannot be played.";
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot start projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["sel"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else {
					if (!is_numeric($_GET["sel"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else if ($_GET["sel"]<1||$_GET["sel"]>3) {
						include_once "header2.inc.php";
						para("Unknown selection");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else {
						$pos = $curChar->getPosition();
						$localMap = new LocalMap($mysqli, $pos->x, $pos->y);//To do: What if the character is in a building? This already takes groups into account
						$localCheck = $localMap->checkIfExists();
						
						if ($localCheck == -1) {
							include_once "header2.inc.php";
							para("There are no trees here because you're in the middle of water.");
							para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
						}
						else if ($localCheck == -2) {
							include_once "header2.inc.php";
							para("You shouldn't be here because this location hasn't been explored.");
							para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
						}
						else {
							$sel = $_GET["sel"];
							$arr = $localMap->getVegetationSpot($pos->lx, $pos->ly);
							if ($sel == 1) {
								if ($arr["trees"]<56) {
									include_once "header2.inc.php";
									para("There are no trees to cut in this square.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
								else {
									$treeCount = $localMap->countTrees($arr["trees"]);
									$ap = $curChar->getAP();
									$most = min($treeCount, floor($ap/50));
									include_once "header2.inc.php";
									echo "<form name='treeform' id='treeform' method='get' action='index.php' class='narrow'>";
									ptag("input" , "", "type='hidden' name='page' value='chop2'");
									para("There are " . $treeCount . " trees in this square. It would take " . $treeCount*50 . " AP to clear them all with an average tool. You have " . $ap . " AP so at most you can fell " . $most . " trees.");
									$localMap->printClearTools($sel);
									$ok = $localMap->clearToolsAvailable($sel, $charcheck);
									echo "<p>";
									ptag("label", "AP to spend:", "for='ap'");
									ptag("input", "", "value='" . $most*50 . "' name='ap' id='ap'");
									echo "</p>";
									ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
									ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
									ptag("input" , "", "type='hidden' name='sel' value='$sel'");
									if (!$ok) $dis = "disabled='disabled'";
									else $dis = "";
									echo "<p class='right'>";
									ptag("input", "", "type='submit' value='Chop selected' $dis");
									echo "</p>\n";
									echo "</form>";
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
							}
							if ($sel == 2) {
								if ($arr["bushes"]<61) {
									include_once "header2.inc.php";
									para("There are no bushes to cut down in this square.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
								else {
									$bushCount = $arr["bushes"]-60;
									$ap = $curChar->getAP();
									$most = min($bushCount, floor($ap/3));
									include_once "header2.inc.php";
									echo "<form name='bushform' id='bushform' method='get' action='index.php' class='narrow'>";
									ptag("input" , "", "type='hidden' name='page' value='chop2'");
									para("It would take " . $bushCount*3 . " AP to cut down all the bushes from this square with an average tool. You have " . $ap . " AP.");
									$localMap->printClearTools($sel);
									$ok = $localMap->clearToolsAvailable($sel, $charcheck);
									echo "<p>";
									ptag("label", "AP to spend:", "for='ap'");
									ptag("input", "", "value='" . $most*3 . "' name='ap' id='ap'");
									echo "</p>";
									ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
									ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
									ptag("input" , "", "type='hidden' name='sel' value='$sel'");
									if (!$ok) $dis = "disabled='disabled'";
									else $dis = "";
									echo "<p class='right'>";
									ptag("input", "", "type='submit' value='Cut selected' $dis");
									echo "</p>\n";
									echo "</form>";
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
							}
							if ($sel == 3) {
								if ($arr["grass"]<65) {
									include_once "header2.inc.php";
									para("There are no bushes to cut down in this square.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
								else {
									$grassCount = round(($arr["grass"]-64)/2);
									$ap = $curChar->getAP();
									$most = round(min($grassCount, floor($ap)));
									include_once "header2.inc.php";
									echo "<form name='bushform' id='bushform' method='get' action='index.php' class='narrow'>";
									ptag("input" , "", "type='hidden' name='page' value='chop2'");
									para("It would take " . $grassCount . " AP to cut down all the grass from this square with an average tool. You have " . $ap . " AP.");
									$localMap->printClearTools($sel);
									$ok = $localMap->clearToolsAvailable($sel, $charcheck);
									echo "<p>";
									ptag("label", "AP to spend:", "for='ap'");
									ptag("input", "", "value='$most' name='ap' id='ap'");
									echo "</p>";
									ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
									ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
									ptag("input" , "", "type='hidden' name='sel' value='$sel'");
									if (!$ok) $dis = "disabled='disabled'";
									else $dis = "";
									echo "<p class='right'>";
									ptag("input", "", "type='submit' value='Mow selected' $dis");
									echo "</p>\n";
									echo "</form>";
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
							}
						}
					}
				}
				
			}
		}
	}
}
?>
