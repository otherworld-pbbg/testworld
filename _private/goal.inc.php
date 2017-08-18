<?php
//Set goal
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
				para("You cannot set goals on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
			}
			else {
				include_once "header2.inc.php";
				$currentLocation = new GlobalMap($mysqli, $curChar->x, $curChar->y);
				$localMap = new LocalMap($mysqli, $curChar->x, $curChar->y);
				$localCheck = $localMap->checkIfExists();
				if ($localCheck == -2) $threshold = 76;
				else $threshold = 1;
				
				if (isset($_GET["presel"])) {
					if (is_numeric($_GET["presel"])) $pre_selection = $_GET["presel"];
					else $pre_selection = false;
				}
				else $pre_selection = false;
				
				$natural = $currentLocation->getResources();
				$resources = $currentLocation->loadResources($natural, $threshold);
				ptag("h1", "Setting goals");
				$hiddenHere = 0;
				$visCounter = 0;
				ptag("h2", "Set a resource goal");
				if ($resources) {
					echo "<form name='goalform' id='goalform' action='index.php' method='get' class='narrow'><ul>";
					ptag("input" , "", "type='hidden' name='page' value='setgoal'");
					ptag("label", "Target resource:", "for='res_sel'");
					echo "<select form='goalform' name='sel' id='res_sel'>";
					for ($i=0;$i<count($resources);$i++) {
						if ($resources[$i]["hidden"]==0||($resources[$i]["hidden"]==1&&$threshold==1)) {
							if ($pre_selection==$resources[$i]["uid"]) $select = "selected='selected'";
							else $select = "";
							ptag("option", $resources[$i]["name"], "value='". $resources[$i]["uid"] ."' $select");
							$visCounter++;
						}
						else $hiddenHere++;
						if ($hiddenHere>0) ptag("option", "anything hidden", "value='x'");
					}
					echo "</select>";
					echo "<br>\n";
					ptag("label", "Amount to get (grams):", "for='amount'");
					ptag("input" , "", "type='text' name='amount' id='amount' value='10000' size='8'");
					echo "<br>\n";
					ptag("label", "Max AP to spend:", "for='maxap'");
					ptag("input" , "", "type='text' name='maxap' id='maxap' value='120' size='4'");
					echo "<br>\n";
					ptag("input", "", "type='checkbox' name='search1' id='search1' value='1'");
					ptag("label", "Use old deposits if not exausted", "for='search1'");
					echo "<br>\n";
					ptag("input", "", "type='checkbox' name='search2' id='search2' value='1'");
					ptag("label", "Search for new deposits", "for='search2'");
					echo "<br>\n";
					ptag("label", "Priority:", "for='priority'");
					ptag("input" , "", "type='text' name='priority' id='priority' value='999' size='5'");
					echo "<br>\n";
					ptag("label", "Status:", "for='activity'");
					echo "<select form='goalform' name='activity' id='activity'>";
					ptag("option", "Active", "value='1'");
					ptag("option", "Inactive", "value='2'");
					echo "</select>";
					
					ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
					ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
					
					echo "<p class='right'>";
					ptag("input", "", "type='submit' value='Add goal'");
					echo "</p></form>\n";
				}
				else para("There are no resources here you could set as a goal.");
			
			}
		}
	}
}
?>
