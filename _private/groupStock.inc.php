<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("class_preset.inc.php");
include_once("class_group.inc.php");
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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
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
				para("You cannot interact with group stock on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_POST["sel_action2"])||!isset($_POST["sel2"])||!isset($_POST["group"])) {
				include_once "header2.inc.php";
				para("Error: Missing data.");
			}
			else if (!is_numeric($_POST["sel_action2"])||!is_numeric($_POST["sel2"])||!is_numeric($_POST["group"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else if ($_POST["sel_action2"]<1||$_POST["sel_action2"]>6) {
				include_once "header2.inc.php";
				para("Error: Invalid action.");
			}
			else {
				$targetObject = new Obj($mysqli, $_POST["sel2"]);
				$oname = $targetObject->getName(false);
				$ng = new NPCgroup($mysqli, $_POST['group']);
				if ($targetObject->parent!=$ng->uid) para("Error: This object doesn't belong to the selected group.");
				else {
					$is_countable = $targetObject->getAttribute(ATTR_COUNTABLE);
					$is_container_l = $targetObject->getAttribute(ATTR_LARGE_CONTAINER);
					$is_container_s = $targetObject->getAttribute(ATTR_SMALL_CONTAINER);
					$weight = $targetObject->approximateWeight();
					if ($_POST["sel_action2"]==1) {
						if (($is_countable&&$targetObject->pieces==1)||($targetObject->type!=5&&$targetObject->pieces==1)) {
							//redirect directly to processing with 1
							header('Location: index.php?page=take&charid=' . $charcheck . '&userid=' . $currentUser . '&sel=3&targetid=' . $_POST["sel2"] . '&group=' . $_POST["group"]);
						}
						else {
							include_once "header2.inc.php";
							echo "<div class='bar'>\n";
							ptag("h1", "Withdraw $oname");
							echo "<form action='index.php' method='get' id='takeform' name='takeform' class='narrow'>";
							if ($is_countable) para("There are $targetObject->pieces of these.");
							else para("What there is weighs $weight.");
							if (!$is_countable) {
								echo "<p>";
								ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
								echo "Grams to take* ";
								ptag("input", "", "name='grams' type='text'");
								echo "</p>";
							}
							if ($is_countable) {
								echo "<p>";
								ptag("input", "", "name='sel' type='radio' value='2'");
								echo "Or pieces to take ";
								ptag("input", "", "name='pieces' type='text'");
								echo "</p>";
							}
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='3'");
							echo "Or take all</p>";
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='group' value='" . $_POST["group"] . "'");
							ptag("input" , "", "type='hidden' name='page' value='take'");
							ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["sel2"] . "'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Take selected'");
							echo "</p>";
							echo "</form>";
							para("*) Do bear in mind that without a scale, you cannot know the exact weight of what there is or how much you're taking.");
							para("If you choose to take more than what there is, it will take the whole thing.");
							para("If a resource is counted in pieces and you try to take a weight smaller than one piece, it will round it to the smallest possible part.");
						}
					}
					else if ($_POST["sel_action2"]==5) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						if ($targetObject->type==9) {
							ptag("h1", "Dismember $oname");
							$blood = $targetObject->getAttribute(ATTR_BLOOD);
							$skin = $targetObject->getAttribute(ATTR_SKIN_TYPE);
							$brain = $targetObject->getAttribute(ATTR_HAS_BRAINS);
							$intestine = $targetObject->getAttribute(ATTR_HAS_INTESTINE);
							$offal = $targetObject->getAttribute(ATTR_HAS_OFFAL);
							$sinew = $targetObject->getAttribute(ATTR_HAS_SINEW);
							$head = $targetObject->getAttribute(ATTR_HAS_HEAD);
							$horns = $targetObject->getAttribute(ATTR_HAS_HORNS);
							$scapula = $targetObject->getAttribute(ATTR_HAS_SCAPULA);
							$feet = $targetObject->getAttribute(ATTR_HAS_FEET);
							
							$tools = $curChar->getPoolToolsInventory(1);//any knife
							
							$multiplier = round(pow($targetObject->weight+400, 0.3));
							
							echo "<form action='index.php?page=dismember' method='post' id='butcherform' name='butcherform'>";
							
							para("Base AP cost: 10");
							para("Removing certain parts costs extra");
							$nocontinue = false;
							
							$original_wt = $targetObject->getAttribute(ATTR_ORIGINAL_WEIGHT);
							if (!$original_wt) {
								$original_wt = $targetObject->weight;
								$targetObject->setAttribute(ATTR_ORIGINAL_WEIGHT, $original_wt);
							}
							
							if (!$tools) {
								para("You need some sort of a knife to dismember animal carcasses.");
							}
							else {
								ptag("h2", "Cutting tool");
								$checked = "checked='checked'";
								foreach ($tools as $tool) {
									$tOb = new Obj($mysqli, $tool);
									$tOb->getBasicData();
									echo "<p>";
									ptag("input", "", "name='tool' id='tool-$tool' type='radio' value='$tool' $checked");
									ptag("label", $tOb->getName(), "for='tool-$tool'");
									echo "</p>";
									$checked = "";
								}
							}
							
							if ($blood&&$blood>round($original_wt*0.04)) {
								ptag("h3", "Blood");
								echo "<p>";
								
								ptag("input", "", "name='blood' id='blood1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='blood1'");
								
								ptag("input", "", "name='blood' id='blood2' type='radio' value='2'");
								ptag("label", "Pick up", "for='blood2'");
								echo "</p>";
							}
							elseif (!$skin&&!$brain&&!$intestine&&!$offal&&!$sinew&&!$head&&!$horns&&!$scapula&&!$feet) $nocontinue = true;
							
							if ($intestine) {
								ptag("h3", "Intestines");
								echo "<p>";
								
								ptag("input", "", "name='intestine' id='intestine1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='intestine1'");
								
								ptag("input", "", "name='intestine' id='intestine2' type='radio' value='2'");
								ptag("label", "Pick up", "for='intestine2'");
								echo "</p>";
							}
							if ($offal) {
								ptag("h3", "Offal");
								echo "<p>";
								
								ptag("input", "", "name='offal' id='offal1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='offal1'");
								
								ptag("input", "", "name='offal' id='offal2' type='radio' value='2'");
								ptag("label", "Pick up", "for='offal2'");
								echo "</p>";
							}
							if ($skin) {
								ptag("h3", "Skin");
								para("AP cost:" . $multiplier . ", unless left on");
								echo "<p>";
								
								ptag("input", "", "name='skin' id='skin1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='skin1'");
								
								ptag("input", "", "name='skin' id='skin2' type='radio' value='2'");
								ptag("label", "Pick up", "for='skin2'");
								
								ptag("input", "", "name='skin' id='skin3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='skin3'");
								echo "</p>";
							}
							if ($sinew) {
								ptag("h3", "Sinew");
								echo "<p>";
								
								ptag("input", "", "name='sinew' id='sinew1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='sinew1'");
								
								ptag("input", "", "name='sinew' id='sinew2' type='radio' value='2'");
								ptag("label", "Pick up", "for='sinew2'");
								
								ptag("input", "", "name='sinew' id='sinew3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='sinew3'");
								echo "</p>";
							}
							if ($head) {
								ptag("h3", "The head");
								para("AP cost:" . round($multiplier/2) . ", unless left attached");
								echo "<p>";
								
								ptag("input", "", "name='head' id='head1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='head1'");
								
								ptag("input", "", "name='head' id='head2' type='radio' value='2'");
								ptag("label", "Pick up", "for='head2'");
								
								ptag("input", "", "name='head' id='head3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='head3'");
								echo "</p>";
							}
							if ($brain) {
								ptag("h3", "Brains");
								echo "<p>";
								
								ptag("input", "", "name='brain' id='brain1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='brain1'");
								
								ptag("input", "", "name='brain' id='brain2' type='radio' value='2'");
								ptag("label", "Pick up", "for='brain2'");
								
								ptag("input", "", "name='brain' id='brain3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='brain3'");
								echo "</p>";
							}
							if ($horns) {
								if ($horns==1) ptag("h3", "Antlers");
								if ($horns==2) ptag("h3", "Horns");
								if ($horns==3) ptag("h3", "Tusks");
								
								echo "<p>";
								
								ptag("input", "", "name='horn' id='horn1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='horn1'");
								
								ptag("input", "", "name='horn' id='horn2' type='radio' value='2'");
								ptag("label", "Pick up", "for='horn2'");
								
								ptag("input", "", "name='horn' id='horn3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='horn3'");
								echo "</p>";
							}
							if ($scapula) {
								ptag("h3", "Scapula");
								echo "<p>";
								
								ptag("input", "", "name='scapula' id='scapula1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='scapula1'");
								
								ptag("input", "", "name='scapula' id='scapula2' type='radio' value='2'");
								ptag("label", "Pick up", "for='scapula2'");
								
								ptag("input", "", "name='scapula' id='scapula3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='scapula3'");
								echo "</p>";
							}
							if ($feet) {
								ptag("h3", "Feet");
								para("AP cost:" . round($multiplier/8*$feet) . ", unless left attached");
								echo "<p>";
								
								ptag("input", "", "name='feet' id='feet1' type='radio' value='1' checked='checked'");
								ptag("label", "Discard", "for='feet1'");
								
								ptag("input", "", "name='feet' id='feet2' type='radio' value='2'");
								ptag("label", "Pick up", "for='feet2'");
								
								ptag("input", "", "name='feet' id='feet3' type='radio' value='3'");
								ptag("label", "Leave attached", "for='feet3'");
								echo "</p>";
							}
							
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input" , "", "type='hidden' name='carcass' value='" . $_POST["sel2"] . "'");
							
							if ($nocontinue) {
								para("This animal carcass has been completely dressed and only has the torso left. Cooking it will become possible later.");
							}
							else if ($tools) {
								echo "<p class='right'>";
								ptag("input", "", "type='submit' value='Dismember'");
								echo "</p>";
							}
							echo "</form>";
						}
						else para("Currently you can only take apart animal carcasses. Try again later.");
					}
					else if ($_POST["sel_action2"]==6) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						if ($is_container_l||$is_container_s) {
							ptag("h1", "Take from $oname");
							$contents = $targetObject->getContents();
							if ($contents) {
								echo "<form action='index.php?page=emptyContainer' method='post' id='containerform' name='containerform' class='narrow'>";
								for ($j=0; $j<count($contents); $j++) {
									$selected = "";
									if ($j == 0) $selected = "checked='checked'";
									$inItem = new Obj($mysqli, $contents[$j]);
									$inItem->getBasicData();
									$handle = $inItem->getHandle();
									echo "<p>";
									ptag("input", "", "name='contentid' type='radio' value='". $inItem->uid ."' $selected");
									echo $handle;
									echo "</p>";
								}
								ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
								ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
								ptag("input" , "", "type='hidden' name='containerid' value='" . $_POST["sel2"] . "'");
								ptag("input" , "", "type='hidden' name='location' value='3'");
								echo "<p class='right'>";
								ptag("input", "", "type='submit' value='Take selected'");
								echo "</p>";
								echo "</form>";
							}
							else para("This container is empty.");
						}
						else para("This isn't a container");
					}
				}
				echo "<p class='right'>";
				ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
				echo "</p>";
				echo "</div>\n";
			}
		}
	}
}
?>
