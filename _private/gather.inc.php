<?php
//this needs the following post variables: ap_sel, source, charid, userid

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
				para("You cannot gather on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
			}
			else {
				if (!isset($_POST["ap_sel"])||!isset($_POST["source"])||!isset($_POST["source2"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=2');
				}
				else if ($inside>0) {
					include_once "header2.inc.php";
					para("You can't forage when you are in a group or inside a building. You need to exit first.");
				}
				else {
					if (!is_numeric($_POST["ap_sel"])||!is_numeric($_POST["source"])||!is_numeric($_POST["source2"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						
					}
					else if ($_POST["ap_sel"]<1||$_POST["ap_sel"]>12) {
						include_once "header2.inc.php";
						para("AP is out of range. Aborting.");
					}
					else {
						$ap = $_POST["ap_sel"]*10;
						$result = $curChar->gather($_POST['source'], $_POST['source2'], $ap);
						
						if ($result==-1) {
							include_once "header2.inc.php";
							para("You don't have enough AP.");
							para("<a href='index.php?page=forage2&charid=" . $charcheck . "&userid=" . $currentUser . "&source=". $_POST["source"] . "' class='clist'>[Return to deposit]</a>");
						}
						else if ($result==-2) {
							include_once "header2.inc.php";
							para("This deposit isn't in the same location as you, belongs to another character or doesn't exist at all.");
						}
						else if ($result["exhausted"]==1) {
							include_once "header2.inc.php";
							para("The deposit has been exhausted.");
							para("(You gathered " . $result["amount"] . " grams.)");
						}
						else {
							header('Location: index.php?page=forage2&charid=' . $charcheck . '&userid=' . $currentUser . '&source=' . $_POST["source"]);
						}
					}
				}
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
			}
		}
	}
}
?>
