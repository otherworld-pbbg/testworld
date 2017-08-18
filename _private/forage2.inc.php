<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
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
	$pos = $curChar->getPosition();
	$watcherRole = $curChar->checkPermission($currentUser);
	$inside = $curChar->building;
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		echo "<div class='alert alert-info'>";
		echo "<strong>Disclaimer:</strong> You are a watcher, so you can't carry out actions. You only see what the character sees.";
		echo "</div>";
	}
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "<div class='alert alert-danger'>";
			displayBodywarning();
			echo "</div>";
		}
		else {
			
			if (!isset($_GET['source'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
			else if ($inside>0) {
				include_once "header2.inc.php";
				para("You can't forage when you are in a group or inside a building. You need to exit first.");
			}
			else {
				
				$currentLocation = new GlobalMap($mysqli, $pos->x, $pos->y);
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				echo "<div class='left_header'>\n";
				ptag("h4", "Current character:");
				para("<a href='index.php?page=formCharName&charid=$charcheck&userid=$currentUser&ocharid=" . $charcheck . "' class='clist'>" . $curChar->cname . "</a>");
				$ageArr = $curChar->getAge();
				para("Age: ".$ageArr[0]." years, ".$ageArr[1]." months");
				para ($curChar->getAgeSex());
				echo "</div>\n<div class='icon_header'>\n";
				ptag("img", "", "src='". getGameRoot() ."/graphics/icon_healthy.png' alt='healthy'");//these are currently static
				ptag("img", "", "src='". getGameRoot() ."/graphics/icon_satiated.png' alt='satiated'");
				echo "</div>\n";
				echo "<div class='loc_header'>\n";
				ptag("h4", "Current location:");
				para($curChar->x . ", " . $curChar->y);
				$locnameArr = $curChar->getLocationName($pos->x, $pos->y);
				para("<a href='index.php?page=formLocName&charid=$charcheck&userid=$currentUser&x=" . $pos->x . "&y=" . $pos->y . "' class='clist'>" . $locnameArr["name"] . "</a>");
				echo "</div>\n";
				echo "<div class='time_header'>\n";
				ptag("h4", "Time and date:");
				$curTime = new Time($mysqli);
				para($curTime->getDateTime());
				$curAP = $curChar->getAP();
				para("AP: $curAP");
				echo "</div></div>\n";
				$source = $mysqli->real_escape_string($_GET['source']);
				$deposit = $curChar->loadMemorized($source);
				if ($deposit>0) {
					echo "<div class='bar'>\n";
					if ($deposit["lx"]!=$curChar->localx||$deposit["ly"]!=$curChar->localy) $curChar->moveLocal($deposit["lx"], $deposit["ly"]);
					$gathered = $curChar->getGatheringSpeed($deposit["res"]);
					para("This is a memorized location.");
					
					$subs = $curChar->loadMemorizedSub($source, $deposit["res"]);
					
					if ($subs==-1) para("This deposit is empty.");
					else foreach ($subs as $memory) {
						$gathered_sub = round($gathered * $curChar->getSubtypeMultiplier($memory["preset"]));
						$rawsLeft = $memory["mass"]-$memory["harvested"];
						$subtype = new Resource($mysqli, $deposit["res"], $memory["preset"]);
						
						echo "<form action='index.php?page=gather' id='gatherform-".$memory["uid"]."' method='post' class='narrow'>";
						ptag("h1", "Gather " . $subtype->name);
						if ($memory["multi"]<1) para("(Unoptimal season - " . $memory["multi"]*100 . " %)");
						para("Harvested: " . $memory["harvested"] . " grams");
						para("Remaining: $rawsLeft grams");
						para("Grams you can gather with 10 AP: $gathered_sub");
						ptag("input" , "", "type='hidden' name='source' value='$source'");
						ptag("input" , "", "type='hidden' name='source2' value='" . $memory["uid"] . "'");
						ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
						echo "<p>";
						ptag("label", "AP to spend gathering: ", "for='ap_sel'");
						echo "<select name='ap_sel' id='ap_sel' form='gatherform-".$memory["uid"]."'>";
						ptag("option", "10 AP", "value='1' selected='selected'");
						ptag("option", "20 AP", "value='2'");
						ptag("option", "30 AP", "value='3'");
						ptag("option", "40 AP", "value='4'");
						ptag("option", "50 AP", "value='5'");
						ptag("option", "60 AP", "value='6'");
						ptag("option", "70 AP", "value='7'");
						ptag("option", "80 AP", "value='8'");
						ptag("option", "90 AP", "value='9'");
						ptag("option", "100 AP", "value='10'");
						ptag("option", "110 AP", "value='11'");
						ptag("option", "120 AP", "value='12'");
						echo "</select></p><p class='right'>";
						ptag("input", "", "type='submit' value='Gather'");
						echo "</p>\n";
						echo "</form>\n";
					}
					echo "</div>\n";
				}
				else {
					para("Failed to load memorized location. Please try again and inform developer if this persists.");
				}
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=7' class='clist'>[Return to Memorized Resource Spots]</a>");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
			}
		}
	}
}
?>
