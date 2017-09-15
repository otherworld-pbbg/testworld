<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "generic.inc.php";
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
			if (!isset($_GET['group'])||!isset($_GET['audience'])||!isset($_GET['tone'])||!isset($_GET['topic'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
			else {
				$ng = new NPCgroup($mysqli, $_GET["group"]);
				$check = $ng->validate($pos->x, $pos->y, $pos->lx, $pos->ly);
				if ($check==-1||$check==-2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=4');
				if ($check==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=5');//Trying to access a group that's in another location
				
				$audience = array(
					"one or two people",
					"a handful of people",
					"a small group",
					"a medium-sized group",
					"everybody"
					);
				
				$tone = array(
					"a cautious",
					"a tentative",
					"a conversational",
					"a self-confident",
					"an assertive",
					"an extremist"
					);
				
				$a = setBint($_GET['audience'], 0, sizeof($audience)-1, 0);
				$t = setBint($_GET['tone'], 0, sizeof($tone)-1, 1);
				
				
				include_once "header2.inc.php";
				echo "<div class='bar'>";
				
				$t1 = $tone[$t];
				$aud = $audience[$a];
				
				$contents = array(
					"In an $t1 tone, you talk to $aud about how we should be prepared in case we are threatened, and ready to fight back.",
					"In an $t1 tone, you talk to $aud how much nicer it would be if everybody got along and showed some kindness to their neighbors.",
					"In an $t1 tone, you talk to $aud about how important it is that everybody thinks with their own brain and brings fresh thoughts to the table.",
					"In an $t1 tone, you talk to $aud about how important it is that we are a unified group.",
					"In an $t1 tone, you talk to $aud about how we should be more tolerant.",
					"In an $t1 tone, you talk to $aud about how tolerance has gone too far and how being stricter protects our traditions.",
					"In an $t1 tone, you talk to $aud about how important freedom is, and how sometimes it's worth dying for.",
					"In an $t1 tone, you talk to $aud about how important life is, and how it's better to get along with authorities.",
					);
				
				$c = setBint($_GET['topic'], 0, sizeof($contents)-1, 0);
				
				para($contents[$c]);
				$ng->talk($a, $t, $c, $charcheck);
				
				echo "<p class='right'>";
				ptag("a", "Return to group view", "href='index.php?page=viewgroup&charid=" . $charcheck . "&groupid=" . $_GET["group"] . "' class='clist'");
				echo "</p>";
			}
			echo "<p class='right'>";
			ptag("a", "Try again", "href='index.php?page=talk&charid=" . $charcheck . "&group=" . $_GET["group"] . "' class='clist'");
			echo "</p>";
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
?>