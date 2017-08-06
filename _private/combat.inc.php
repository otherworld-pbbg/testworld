<?php
//this needs the following get variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_obj.inc.php");

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
			$combat = $curChar->checkCurrentCombat();
			if ($combat==-1) {
				header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2&errormessage=1');
			}
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("Disclaimer: You're a watcher, so you cannot affect the outcome of the fight, you can just see the events.");
			}
			include_once "header2.inc.php";
			echo "<div class='bar'>\n";
			echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
			ptag("h1", "Combat (id: $combat)");
			if (isset($_GET['type_sel'])) $type = $_GET['type_sel'];
			else $type = 5;
			
			if (isset($_GET['wpn_sel'])) $wp = $_GET['wpn_sel'];
			else $wp = 0;
			
			$participants = $curChar->getCombatParticipants(true);
			if (is_array($participants)) {
				ptag("h2", "Participants");
				foreach ($participants as $body_id) {
					$bodyObj = new Obj($mysqli, $body_id);
					$bodyObj->getBasicData();
					
					$blood_per = $bodyObj->getBloodPercentage();
					echo "<p>";
					if ($blood_per<80) ptag("img", "", "src='". getGameRoot() . "/graphics/icon_wounded.png' alt='seriously wounded' class='inline'");
					else if ($blood_per<95) ptag("img", "", "src='". getGameRoot() . "/graphics/icon_so-so.png' alt='somewhat wounded' class='inline'");
					else ptag("img", "", "src='". getGameRoot() . "/graphics/icon_healthy.png' alt='healthy'");
					if ($bodyObj->type==2) {
						$ochar_id = $bodyObj->getCharid();
						if ($ochar_id>0) {
							$ochar = new Character($mysqli, $ochar_id);
							$desc = $ochar->getAgeSex();
							$curChar->printNameLink($ochar->uid);
							echo " (" . $desc . ")";
						}
						else echo $curChar->getObjectDynamicName($body_id);
					}
					else echo $bodyObj->getName();
					echo "</p>";
				}
			}
			
			$times = $curChar->getCombatParticipationTimes($combat);
			if (!$times) para("You don't seem to be registered in this combat log. It's probably a bug.");
			else {
				ptag("h2", "Log");
				foreach ($times as $time) {
					$curChar->printCombatEvents($combat, $time["join_dt"], $time["join_m"], $time["leave_dt"], $time["leave_m"]);
					para("...");
				}
			}
			
			echo "<form action='index.php' name='attackform' method='get' class='narrow'>";
			
			$opponents = $curChar->getCombatParticipants(false);
			if ($opponents==-1) para("There is no one left to fight against.");
			else {
				ptag("label", "Attack target:", "for='target_sel'");
				echo "<p><select for='attackform' name='target_sel' id='target_sel'>";
				foreach ($opponents as $opponent) {
					$enemy = new Obj($mysqli, $opponent);
					$enemy->getBasicData();
					$enemy->getName();
					
					if ($enemy->type==2) ptag("option", $curChar->getObjectDynamicName($opponent), "value='$opponent'");
					else ptag("option", $enemy->name, "value='$opponent'");
				}
				echo "</select></p>";
			}
			
			ptag("label", "Attack type:", "for='type_sel'");
			echo "<p><select for='attackform' name='type_sel' id='type_sel'>";
			if ($type==5) ptag("option", "Cripple/Incapacitate", "value='5' selected='selected'");
			else ptag("option", "Cripple/Incapacitate", "value='5'");
			if ($type==6) ptag("option", "Quick kill", "value='6' selected='selected'");
			else ptag("option", "Quick kill", "value='6'");
			echo "</select></p>";
			
			$weapons = $curChar->getWeapons();
			ptag("label", "Weapon:", "for='wpn_sel'");
			echo "<p><select for='attackform' name='wpn_sel' id='wpn_sel'>";
			foreach ($weapons as $wpn) {
				if ($wpn["uid"]==$wp) $sel = "selected='selected'";
				else $sel = "";
				
				if ($wpn["uid"]==0) ptag("option", "Unarmed", "value='0' $sel");
				else {
					$wo = new Obj($mysqli, $wpn["uid"]);
					$wo->getBasicData();
					$name = $wo->getName();
					ptag("option", $name, "value='" . $wpn["uid"] . "' $sel");
				}
			}
			echo "</select></p>";
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			ptag("input" , "", "type='hidden' name='page' value='round'");
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Attack'");
			echo "</p>\n";	
			echo "</form>";
			
			echo "<form action='index.php' name='fleeform' method='get' class='narrow'>";
			if ($opponents==-1) {
				para("Nobody is stopping you from leaving. You are free to go.");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Leave fight scene'");
				echo "</p>\n";
			}
			else {
				para("Your opponent might stop you from leaving or attack you while your back is turned. Are you willing to take the risk?");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Run away'");
				echo "</p>\n";
			}
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			ptag("input" , "", "type='hidden' name='page' value='flee'");
			echo "</form>";
			echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
			echo "</div>\n";
		}
	}
}
?>
