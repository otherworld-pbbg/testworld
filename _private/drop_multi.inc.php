<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("class_preset.inc.php");
include_once("constants.php");

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
	$pos = $curChar->getPosition();
	
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
				echo "<div class='bar'>\n";
				para("You cannot drop things on someone else's behalf when you're a watcher.");
			}
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				ptag("h2", "Carried items");
				$inventory = $curChar->getInventory();
				if ($inventory) {
					$counter = 0;
					echo "<form action='index.php?page=dropMulti2' method='post' id='dropform' name='dropform' class='narrow'>";
					foreach ($inventory as $item) {
						echo "<p>";
						$invItem = new Obj($mysqli, $item);
						$handle = $invItem->getHandle();
						ptag("input", "", "type='checkbox' id='sel-$counter' name='sel-$counter' value='$invItem->uid'");
						echo $handle;
						echo "</p>";
						$contents = $invItem->getContents();
						if ($contents) {
							echo "<ul class='small_list'>";
							foreach ($contents as $c) {
								$inItem = new Obj($mysqli, $c);
								$handle2 = $inItem->getHandle();
								ptag("li", "$handle2", "class='small_list'");
							}
							echo "</ul>";
						}
						$counter++;
					}
					ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
					ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
					ptag("input" , "", "type='hidden' name='counter' value='$counter'");
					
					ptag("input", "", "type='submit' id='dropsubmit' value='Drop selected'");
					echo "</p>";
					echo "</form>";
				}
				else para("You are currently carrying nothing.");
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
?>
