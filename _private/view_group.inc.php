<?php
//this needs the following post variables: groupid, charid

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
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			displayBodywarning();
		}
		else {	
			if (!isset($_GET["groupid"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=2');//No group id
			if (!is_numeric($_GET["groupid"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=4');//Not a valid group id
			$ng = new NPCgroup($mysqli, $_GET["groupid"]);
			$check = $ng->validate( $curChar->x, $curChar->y, $curChar->localx, $curChar->localy);
			if ($check==-1||$check==-2) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=4');
			if ($check==-3) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=11&errormessage=5');//Trying to access a group that's in another location
			include_once "header2.inc.php";
			echo "<div class='bar'>";
			if ($watcherRole>1) {
				
				para("Note that you're a watcher.");
			}
			
			ptag("h1", "Viewing group details");
			$ng->printData();
			
			ptag("h2", "Your current reputation");
			
			para($ng->showReputation($charcheck));
			
			ptag("h2", "Try to gain respect in the group");
			
			echo "<form action='index.php' method='get' class='narrow'>";
			
			echo "<p>";
			ptag("input", "", "type='radio' name='favor' value='1' id='sel1'");
			ptag("label", "Do a little favor (15 AP)", "for='sel1'");
			echo "</p>";
			echo "<p>";
			ptag("input", "", "type='radio' name='favor' value='2' id='sel2'");
			ptag("label", "Work for the common good (60 AP)", "for='sel2'");
			echo "</p>";
			echo "<p>";
			ptag("input", "", "type='radio' name='favor' value='3' id='sel3'");
			ptag("label", "Forage to contribute to group stock (120 AP)", "for='sel3'");
			echo "</p>";
			echo "<p>";
			ptag("input", "", "type='radio' name='favor' value='4' id='sel4'");
			ptag("label", "Participate in a hunting trip (up to 240 AP)<br>(" . $ng->getHunters() . " equipped participants in addition to you)", "for='sel4'");
			echo "</p>";
			ptag("input", "", "type='hidden' name='charid' value=$charcheck");
			ptag("input", "", "type='hidden' name='page' value='doFavor'");
			ptag("input", "", "type='hidden' name='group' value=" . $_GET["groupid"]);
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Participate'");
			echo "</p>\n";
			echo "</form>";
			
			ptag("h2", "Try to alter the group's moral compass");
			echo "<form action='index.php' method='get' class='narrow'>";
			ptag("input", "", "type='hidden' name='charid' value=$charcheck");
			ptag("input", "", "type='hidden' name='page' value='talk'");
			ptag("input", "", "type='hidden' name='group' value=" . $_GET["groupid"]);
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Hold a speech'");
			echo "</p>\n";
			echo "</form>";
			
			ptag("h2", "Group stock");
			$respect = $ng->getOpinion($curChar->bodyId, 1);
			if ($respect=="NaN"||$respect<1) para("The group doesn't trust you enough to reveal its stock to you.");
			else {
				$inventory = $ng->getInventory();
				if ($inventory) {
					echo "<form action='index.php?page=groupStock' method='post' id='invform' name='invform' class='narrow'>";
					for ($i=0; $i<count($inventory); $i++) {
						$selected = "";
						if ($i == 0) $selected = "checked='checked'";
						echo "<p>";
						$invItem = new Obj($mysqli, $inventory[$i]);
						$val = $ng->getInternalValue($inventory[$i]);
						$handle = $invItem->getHandle();
						ptag("input", "", "type='radio' id='sel-$invItem->uid' name='sel2' value='$invItem->uid' $selected");
						echo $handle;
						echo " [" . $val . " res]";
						echo "</p>";
						$contents = $invItem->getContents();
						if ($contents) {
							echo "<ul class='small_list'>";
							for ($j=0; $j<count($contents); $j++) {
								$inItem = new Obj($mysqli, $contents[$j]);
								$inItem->getBasicData();
								$handle2 = $inItem->getHandle();
								ptag("li", "$handle2", "class='small_list'");
							}
							echo "</ul>";
						}
					}
					ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
					ptag("input", "", "type='hidden' name='group' value=" . $_GET["groupid"]);
					ptag("input" , "", "type='hidden' id='sel_action2' name='sel_action2' value='0'");
					echo "<p>";
					ptag("input", "", "type='button' id='action2-1' value='Withdraw' onclick='stockClick(1)'");
					ptag("input", "", "type='button' id='action2-5' value='Take apart' onclick='stockClick(5)'");
					ptag("input", "", "type='button' id='action2-6' value='Take from inside' onclick='stockClick(6)'");
					echo "</p>";
					echo "</form>";
				}
				else para("The group has no possessions you are aware of.");
			}
			echo "<p class='right'>";
			ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
?>
<script>
function stockClick(num) {
		document.getElementById("sel_action2").value = num;
		document.forms["invform"].submit();
    	}
</script>
