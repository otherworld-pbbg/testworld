<?php
//this needs the following post variables: res0, res1... resx, resnum, duration, charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_global_map.inc.php";

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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	$inside = $curChar->building;
	
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
				para("You cannot forage on someone else's behalf when you're a watcher.");
				
			}
			else if ($inside>0) {
				include_once "header2.inc.php";
				para("You can't search for deposits when you are in a group or inside a building. You need to exit first.");
			}
			else {
				if (!isset($_POST["duration"])||!isset($_POST["resnum"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else {
					if (!is_numeric($_POST["duration"])||!is_numeric($_POST["resnum"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
					}
					else if ($_POST["duration"]<1||$_POST["duration"]>6||$_POST["resnum"]>200||$_POST["resnum"]<0) {
						include_once "header2.inc.php";
						para("Something is out of range. Aborting.");
					}
					else {
						$resArr = array();
						$searchHidden = false;
						
						if (isset($_POST["resx"])) {
							if ($_POST["resx"]=="hid") $searchHidden = true;
						}
						
						$resnum = $_POST["resnum"];
						for ($i=0;$i<$resnum;$i++) {
							$str = "res" . $i;
							if (isset($_POST[$str])) {
								if (is_numeric($_POST[$str])) {
									$resArr[] = $_POST[$str];
								}
							}
						}
						
						$result = $curChar->searchDeposits($resArr, $searchHidden, $_POST["duration"]);
						if ($result==-4) {
							include_once "header2.inc.php";
							para("You found a resource but due to a glitch, AP wasn't deducted. Please inform developer.");
						}
						else if ($result<1) {
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2&errormessage=2');
						}
						else {
							header('Location: index.php?page=forage2&charid=' . $charcheck . '&userid=' . $currentUser . '&source=' . $result);
						}
					}
				}
			}
			para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
		}
	}
}
?>
