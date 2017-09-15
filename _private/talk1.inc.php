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
			displayBodywarning();
		}
		else {
			if (!isset($_GET['group'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
			else {
				$ng = new NPCgroup($mysqli, $_GET["group"]);
				$check = $ng->validate($pos->x, $pos->y, $pos->lx, $pos->ly);
				if ($check==-1||$check==-2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=4');
				if ($check==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=5');//Trying to access a group that's in another location
				
				include_once "header2.inc.php";
				echo "<div class='bar'>";
				$audience = array(
					"Just one or two people",
					"A handful of people",
					"A small group",
					"A medium-sized group",
					"Everybody"
					);
				
				$tone = array(
					"cautious",
					"tentative",
					"conversational",
					"self-confident",
					"assertive",
					"extremist"
					);
				
				$contents = array(
					"Talk to people how we should be prepared in case we are threatened, and ready to fight back.",
					"Talk to people how much nicer it would be if everybody got along and showed some kindness to their neighbors.",
					"Talk to people about how important it is that everybody thinks with their own brain and brings fresh thoughts to the table.",
					"Talk to people about how important it is that we are a unified group.",
					"Talk to people about how we should be more tolerant.",
					"Talk to people about how tolerance has gone too far and how being stricter protects our traditions.",
					"Talk to people about how important freedom is, and how sometimes it's worth dying for.",
					"Talk to people about how important life is, and how it's better to get along with authorities.",
					);
				echo "<form method='get' action='index.php' id='talkform'>";
				ptag("h1", "Talk to people");
				echo "<div class='form-group'>";
				ptag("label", "Size of audience:", "for='audience_id'");
				echo "<select name='audience' form='talkform' id='audience_id'>\n";
				foreach ($audience as $key => $a) {
					ptag ("option", $a, "id='a-$key' value='$key'");			
				}
				echo "</select>";
				echo "</div>";
				echo "<div class='form-group'>";
				ptag("label", "Tone of voice:", "for='tone_id'");
				echo "<select name='tone' form='talkform' id='tone_id'>\n";
				foreach ($tone as $key => $t) {
					ptag ("option", $t, "id='t-$key' value='$key'");	
				}
				echo "</select>";
				echo "</div>";
				echo "<div class='bar'>";
				foreach ($contents as $key => $c) {
					echo "<div class='form-group'>";
					ptag ("input", "", "type='radio' name='topic' id='tc-$key' value='$key'");
					ptag ("label", $c, "for='tc-$key'");
					echo "</div>";
				}
				echo "</div>";
				ptag("input", "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input", "", "type='hidden' name='group' value='".$_GET["group"]."'");
				ptag("input", "", "type='hidden' name='page' value='talk2'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Speak'");
				echo "</p>";
				echo "</form>";
				
				para("Talking to a larger audience has a greater chance of making an influence, but you also risk making more people mad if they disagree with you. Using an assertive tone makes your words have more influence upon success, whereas a more cautious tone means people are less likely to get mad at you even if they disagree with you.");
				echo "<p class='right'>";
				ptag("a", "Return to group view", "href='index.php?page=viewgroup&charid=" . $charcheck . "&groupid=" . $_GET["group"] . "' class='clist'");
				echo "</p>";
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
?>