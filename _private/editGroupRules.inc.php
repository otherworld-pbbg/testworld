<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";

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
		para("You cannot edit group settings when you're a watcher.");
	}
	else {
		if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
		else {
			//user is authorized to view this character
			$bodyId = $curChar->getBasicData();
			if ($bodyId == -1) {
				include_once "header2.inc.php";
				displayBodywarning();
			}
			else {
				if (!isset($_GET['rule'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				else if (!is_numeric($_GET['rule'])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11');
				}
				else {
					$tg = $curChar->getTravelGroup();
					
					if ($tg == -1) {
						include_once "header2.inc.php";
						para("You haven't created a group yet. It's very easy, so why don't you do that first.");
					}
					else {
						$g = new Obj($mysqli, $tg);
						if (isset($_GET['ocharid'])&&isset($_GET['value'])) {
							$ochar = new Character($mysqli, $_GET['ocharid']);
							$oBodyId = $ochar->getBasicData();
							if ($oBodyId == -1) {
								include_once "header2.inc.php";
								para("The selected person isn't valid.");
							}
							else if (($_GET['rule']==1||$_GET['rule']==2)&&$_GET['value']==0||$_GET['value']==1) {
								
								//rule: 1 - right to command, 2 - right to join
								$status = $ochar->updateCharRule($tg, $_GET['rule'], $_GET['value']);
								if ($status==1) para("Authorization added successfully");
								if ($status==2) para("Authorization changed successfully");
								if ($status==-1) para("Failed to save authorization");
								if ($status==-2) para("You tried to re-save the same value. That's unnecessary.");
							}
							else {
								include_once "header2.inc.php";
								para("Error: Faulty data.");
							}
						}
						//print form
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						$curTime = new Time($mysqli);
						$pplCurHere = $curTime->getPplCurrentlyLocation($curChar->x, $curChar->y, $curChar->building, $curChar->uid);
						if ($_GET['rule']==1) ptag("h1", "Edit commanders");
						else if ($_GET['rule']==2) ptag("h1", "Edit people allowed to join");
						else ptag("h1", "Invalid rule type");
						ptag("h2", "Authorize people");
						if ($pplCurHere) {
							echo "<form method='get' action='index.php' class='narrow'>";
							echo "<p>";
							echo "<select name='ocharid' for='editgroup'>";
							ptag("option", "No character selected", "value='0'");
							for ($i=0;$i<count($pplCurHere);$i++) {
								$ochar = new Character($mysqli, $pplCurHere[$i]["charid"]);
								
								$ocharName = $curChar->getDynamicName($ochar->uid);
								ptag("option", "$ocharName (" . $ochar->getAgeSex() . ") ", "value='$ochar->uid'");
							}
							echo "</select>";
							echo "</p>";
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='page' value='editgrouprules'");
							ptag("input" , "", "type='hidden' name='value' value='1'");
							ptag("input" , "", "type='hidden' name='rule' value='".$_GET['rule']."'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Authorize'");
							echo "</p>";
							echo "</form>";
						}
						else para("There are no other people in this location.");
						$pplStatus = $g->getPeopleWithStatus($_GET['rule'], 1);
						
						ptag("h2", "Remove authorization");
						if ($pplStatus==-1) {
							para("There are no people with this status.");
						}
						else {
							echo "<form method='get' action='index.php' class='narrow'>";
							para("Note that if you de-authorize a person who isn't currently in the location, you can't re-authorize them until you see them next time.");
							echo "<p>";
							echo "<select name='ocharid' for='editgroup'>";
							ptag("option", "No character selected", "value='0'");
							for ($i=0;$i<count($pplStatus);$i++) {
								$ochar = new Character($mysqli, $pplStatus[$i]);
								
								$ocharName = $curChar->getDynamicName($ochar->uid);
								ptag("option", "$ocharName (" . $ochar->getAgeSex() . ") ", "value='$ochar->uid'");
							}
							echo "</select>";
							echo "</p>";
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='page' value='editgrouprules'");
							ptag("input" , "", "type='hidden' name='value' value='0'");
							ptag("input" , "", "type='hidden' name='rule' value='".$_GET['rule']."'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Cancel authorization'");
							echo "</p>";
							echo "</form>";
						}
						
						echo "</div>";
					}
				}
			}
			echo "<p class='right'>";
			ptag("a", "Return to groups", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
		}
	}
}
?>
