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
			displayBodywarning();
		}
		else {
			if (!isset($_GET['sel'])||!isset($_GET['targetid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_GET['sel'])||!is_numeric($_GET['targetid'])) {
				include_once "header2.inc.php";
				para("Error: Something is not numeric.");
			}
			else {
				if ($_GET['sel']==1) {
					if (!isset($_GET['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_GET['grams'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_GET['grams']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res1 = $curChar->eat($_GET['targetid'], "weight", $_GET['grams']);
						if ($res1==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						include_once "header2.inc.php";
						if ($res1==-1) para("Your stomach is already full. You need to wait until it's digested.");
						else if ($res1==-2) para("You're trying to eat something in a different location.");
						else if ($res1==-3) para("This isn't edible.");
						else if ($res1==-4) para("You're trying to swallow something whole that's too big to fit down your throat.");
						else if ($res1==-5) para("The food disappeared on it's way down the esophagus, how embarrassing. (Contact developer.)");
					}
				}
				else if ($_GET['sel']==2) {
					if (!isset($_GET['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not set.");
					}
					else if (!is_numeric($_GET['pieces'])) {
						include_once "header2.inc.php";
						para("Error: Amount is not numeric.");
					}
					else if ($_GET['pieces']==0) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					else {
						$res2 = $curChar->eat($_GET['targetid'], "pieces", $_GET['pieces']);
						if ($res2==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
						include_once "header2.inc.php";
						if ($res2==-1) para("Your stomach is already full. You need to wait until it's digested.");
						else if ($res2==-2) para("You're trying to eat something in a different location.");
						else if ($res2==-3) para("This isn't edible.");
						else if ($res2==-4) para("You're trying to swallow something whole that's too big to fit down your throat.");
						else if ($res2==-5) para("The food disappeared on it's way down the esophagus, how embarrassing. (Contact developer.)");
					}
				}
				else if ($_GET['sel']==3) {
					$res3 = $curChar->eat($_GET['targetid'], "whole");
					if ($res3==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					include_once "header2.inc.php";
					if ($res3==-1) para("Your stomach is already full. You need to wait until it's digested.");
					else if ($res3==-2) para("You're trying to eat something in a different location.");
					else if ($res3==-3) para("This isn't edible.");
					else if ($res3==-4) para("You're trying to swallow something whole that's too big to fit down your throat.");
					else if ($res3==-5) para("The food disappeared on it's way down the esophagus, how embarrassing. (Contact developer.)");
				}
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
			echo "</p>";
		}
	}
}
?>
