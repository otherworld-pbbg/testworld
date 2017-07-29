<?
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
		para("You shouldn't be here since you're a watcher.");
	}
	else if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {
			if (!isset($_GET['favor'])||!isset($_GET['group'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
			else if (!is_numeric($_GET['favor'])) para("Error: Something is not numeric.");
			else {
				$ng = new NPCgroup($mysqli, $_GET["group"]);
				$check = $ng->validate($pos->x, $pos->y, $pos->lx, $pos->ly);
				if ($check==-1||$check==-2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=4');
				if ($check==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=5');//Trying to access a group that's in another location
				if ($_GET['favor']==4) $result = $ng->hunt($charcheck);
				else $result = $ng->doFavor($charcheck, $_GET['favor']);
				include_once "header2.inc.php";
				echo "<div class='bar'>";
				if (is_numeric($result)) {
					if ($result == -1) para("Error: You don't have enough AP.");
					if ($result == -2) para("Error: Invalid task selection.");
				}
				else if ($result["result"]==-1) para("An error occurred and this caused the action not to count. Contact administrator.");
				else {
					if ($result["result"]==1) para("Your actions failed catastrophically.");
					else if ($result["result"]==2) para("You screwed up pretty bad.");
					else if ($result["result"]==3) para("You screwed up slightly.");
					else if ($result["result"]==4) para("Your actions were a moderate success.");
					else if ($result["result"]==5) para("Your actions were a great success.");
					else if ($result["result"]==6) para("Your performance was incredible!");
					
					if ($result["change"]<0) para("You lost " . $result["change"]*-1 . " respect points.");
					else para("You gained " . $result["change"] . " respect points.");
					para("Details: " . $result["description"]);
					echo "<p class='right'>";
					ptag("a", "Return to group view", "href='index.php?page=viewgroup&charid=" . $charcheck . "&groupid=" . $_GET["group"] . "' class='clist'");
					echo "</p>";
				}
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
