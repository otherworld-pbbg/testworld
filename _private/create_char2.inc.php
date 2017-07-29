<?php
//Character creation, needs zone 1-4, type 1-3


include_once "class_player.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	if (!isset($_POST["zone"])||!isset($_POST["type"])||!isset($_POST["sex"])||!isset($_POST["startage"])||!isset($_POST["name_txt"])) {
		header('Location: index.php?page=newchar&userid=' . $currentUser);
	}
	else {
		if (!is_numeric($_POST["zone"])||!is_numeric($_POST["type"])||!is_numeric($_POST["sex"])||!is_numeric($_POST["startage"])) {
			include_once "header2.inc.php";
			para("A value should be numeric but isn't. Aborting.");
			para("<a href='index.php?page=createchar&userid=$currentUser' class='clist'>[Return to previous page]</a>");
		}
		else {		
			$player = new Player($mysqli, $currentUser);
			$zone = $_POST["zone"];
			$spawntype = $_POST["type"];
			if ($_POST["sex"]>3||$_POST["sex"]<1) $sex = 3;
			else $sex = round($_POST["sex"]);//This eliminates possible decimal insertion
			if (($spawntype==1||$spawntype==3)&&$_POST["startage"]>13) $age = 26;
			else if (($spawntype==1||$spawntype==3)&&$_POST["startage"]<1) $age = 14;
			else if (($spawntype==1||$spawntype==3)) $age = round($_POST["startage"])+13;
			//if it's a child then the age comes from the NPC
			$name = $mysqli->real_escape_string($_POST["name_txt"]);
			if ($spawntype==3) {
				if (!isset($_POST["roleid"])) {
					include_once "header2.inc.php";
					para("You are trying to claim a role without defining which one.");
					if (!is_numeric($_POST["roleid"])) {
						include_once "header2.inc.php";
						para("Invalid role id.");
					}
				}
				else {
					$roleId = round($_POST["roleid"]);
					$res = $player->fillRole($roleId, $sex, $name);
					if ($res<0) include_once "header2.inc.php";
					if ($res == -1) para("Invalid role id.");
					else if ($res == -2) para("Character creation failed.");
					else if ($res == -4) para("Body creation failed.");
					else if ($res == -5) para("Linking body to spirit failed");
					else if ($res == -6) para("The character was created successfully but we failed to assign you as the player. Contact developer.");
					else if ($res == -7) para("The character was created successfully but the request couldn't be marked as claimed. This can lead into multiple characters filling the same request. Contact developer.");
					else if ($res == -8) para("You can't fill a role requested by one of your own characters.");
					else header('Location: index.php?page=direwolf&userid=' . $currentUser);
					}
			}
			else if ($zone == 1) {
				$res = $player->spawnCharacter($zone, $sex, $age, $name);
				if ($res<0) include_once "header2.inc.php";
				if ($res == -1) para("The game clock is not set, cannot create character.");
				else if ($res == -2) para("Character creation failed.");
				else if ($res == -3) para("There are no starting locations for this zone.");
				else if ($res == -4) para("Body creation failed.");
				else if ($res == -5) para("Linking body to spirit failed");
				else if ($res == -6) para("The character was created successfully but we failed to assign you as the player. Contact developer.");
				else header('Location: index.php?page=direwolf&userid=' . $currentUser);
			}
			else {
				include_once "header2.inc.php";
				para("You're trying to create a character in a locked zone.");
			}
		}
	}
}
?>
