<?
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
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot clear vegetation on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["sel"])||!isset($_GET["tool"])||!isset($_GET["ap"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else {
					if (!is_numeric($_GET["sel"])||!is_numeric($_GET["tool"])||!is_numeric($_GET["ap"])) {
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
						$localMap = new LocalMap($mysqli, $pos->x, $pos->y);//to do: what if the char is in a building?
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
							$tool = $_GET["tool"];
							$ap = $_GET["ap"];
							if ($sel == 1) {
								if ($arr["trees"]<56) {
									include_once "header2.inc.php";
									para("There are no trees to cut in this square.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
								else {
									$eff = $curChar->validateChopTool($tool, $sel);
									if ($eff==-1) {
										include_once "header2.inc.php";
										para("You are trying to access a tool not in your inventory. Naughty naughty.");
									}
									else if ($eff==-2) {
										include_once "header2.inc.php";
										para("Error: This is not a valid tool.");
									}
									else {
										$result = $localMap->chop($charcheck, $sel, $eff, $ap);
										if ($result == -1) {
											include_once "header2.inc.php";
											para("You tried to use more AP than you have!");
										}
										else if ($result == 100) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
									}
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
							}
							if ($sel == 2) {
								if ($arr["bushes"]<65) {
									include_once "header2.inc.php";
									para("There are no bushes to cut down in this square.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
								}
								else {
									$eff = $curChar->validateChopTool($tool, $sel);
									if ($eff==-1) {
										include_once "header2.inc.php";
										para("You are trying to access a tool not in your inventory. Naughty naughty.");
									}
									else if ($eff==-2) {
										include_once "header2.inc.php";
										para("Error: This is not a valid tool.");
									}
									else {
										$result = $localMap->chop($charcheck, $sel, $eff, $ap);
										if ($result == -1) {
											include_once "header2.inc.php";
											para("You tried to use more AP than you have!");
										}
										else if ($result == 100) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
									}
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
									$eff = $curChar->validateChopTool($tool, $sel);
									if ($eff==-1) {
										include_once "header2.inc.php";
										para("You are trying to access a tool not in your inventory. Naughty naughty.");
									}
									else if ($eff==-2) {
										include_once "header2.inc.php";
										para("Error: This is not a valid tool.");
									}
									else {
										$result = $localMap->chop($charcheck, $sel, $eff, $ap);
										if ($result == -1) {
											include_once "header2.inc.php";
											para("You tried to use more AP than you have!");
										}
										else if ($result == 100) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
									}
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
