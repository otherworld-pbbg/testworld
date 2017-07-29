<?php
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("class_preset.inc.php");

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
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot commit inventory actions on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_POST["sel_action"])||!isset($_POST["sel"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_POST["sel_action"])||!is_numeric($_POST["sel"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else if ($_POST["sel_action"]<1||$_POST["sel_action"]>7) {
				include_once "header2.inc.php";
				para("Error: Invalid action.");
			}
			else {
				$localMap = new LocalMap($mysqli, $pos->x, $pos->y);
				$targetObject = new Obj($mysqli, $_POST["sel"]);
				$targetObject->getBasicData();
				if ($targetObject->parent!=$curChar->bodyId) para("Error: You're trying to process an inventory action on something that's not in your inventory.");
				else {
					$is_countable = $targetObject->getAttribute(44, $charcheck);
					$is_container_l = $targetObject->getAttribute(2, $charcheck);
					$is_container_s = $targetObject->getAttribute(7, $charcheck);
					$weight = $targetObject->approximateWeight();
					$oname = $targetObject->getName(false);
					if ($_POST["sel_action"]==1) {
						if (($is_countable&&$targetObject->pieces==1)||($targetObject->type!=5&&$targetObject->pieces==1)) {
							//redirect directly to processing with 1
							header('Location: index.php?page=drop&charid=' . $charcheck . '&userid=' . $currentUser . '&sel=3&targetid=' . $_POST["sel"]);
						}
						else {
							include_once "header2.inc.php";
							echo "<div class='bar'>\n";
							ptag("h1", "Drop $oname");
							echo "<form action='index.php' method='get' id='dropform' name='dropform' class='narrow'>";
							if ($is_countable) para("You have $targetObject->pieces of these.");
							else para("What you have weighs $weight.");
							if (!$is_countable) {
								echo "<p>";     
								ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
								echo "Grams to drop* ";
								ptag("input", "", "name='grams' type='text'");
								echo "</p>";
							}
							if ($is_countable) {
								echo "<p>";
								ptag("input", "", "name='sel' type='radio' value='2'");
								echo "Or pieces to drop ";
								ptag("input", "", "name='pieces' type='text'");
								echo "</p>";
							}
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='3'");
							echo "Or drop all</p>";
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input" , "", "type='hidden' name='page' value='drop'");
							ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["sel"] . "'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Drop selected'");
							echo "</p>";
							echo "</form>";
							para("*) Do bear in mind that without a scale, you cannot know the exact weight of what you have or how much you're dropping.");
							para("If you choose to drop more than what you have, it will drop the whole thing.");
							para("If a resource is counted in pieces and you try to drop a weight smaller than one piece, it will round it to the smallest possible part.");
						}
					}
					else if ($_POST["sel_action"]==2) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						$curTime = new Time($mysqli);
						$pplCurHere = $curTime->getPplCurrentlyLocation($curChar->x, $curChar->y, $curChar->building, $curChar->uid);
						ptag("h1", "Give $oname");
						echo "<form action='index.php?page=give' method='post' id='giveform' name='giveform' class='narrow'>";
						if ($is_countable) para("You have $targetObject->pieces of these.");
						else para("What you have weighs $weight.");
						if (!$is_countable) {
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
							echo "Grams to give* ";
							ptag("input", "", "name='grams' type='text'");
							echo "</p>";
						}
						if ($is_countable) {
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='2'");
							echo "Or pieces to give ";
							ptag("input", "", "name='pieces' type='text'");
							echo "</p>";
						}
						echo "<p>";
						ptag("input", "", "name='sel' type='radio' value='3'");
						echo "Or give all</p>";
						ptag("h2", "Select receiver");
						if ($pplCurHere) {
							echo "<p>";
							echo "<select name='ochar' for='giveform'>";
							ptag("option", "No character selected", "value='0'");
							for ($i=0;$i<count($pplCurHere);$i++) {
								$ochar = new Character($mysqli, $pplCurHere[$i]["charid"]);
								$theirTime = new Time ($mysqli, $pplCurHere[$i]["endDateTime"], $pplCurHere[$i]["endMinute"]);
								$ocharName = $curChar->getDynamicName($ochar->uid);
								ptag("option", "$ocharName (" . $ochar->getAgeSex() . ") (" . $theirTime->getDateTime() . ")", "value='$ochar->uid'");
							}
							echo "</select>";
							echo "</p>";
						}
						else para("There are no other people in this location.");
						
						$groupsCurHere = $localMap->getGroups();
						
						if ($groupsCurHere) {
							echo "<p>";
							echo "<select name='groupid' for='giveform'>";
							ptag("option", "No group selected", "value='0'");
							foreach ($groupsCurHere as $g) {
								$ng = new NPCgroup($mysqli, $g["uid"]);
								ptag("option", "Group #" . $g["uid"] . ": ". $ng->loadName(), "value='" . $g["uid"] . "'");
							}
							echo "</select>";
							echo "</p>";
						}
						else para("There are no groups in this location.");
						ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
						ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
						ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["sel"] . "'");
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Give selected'");
						echo "</p>";
						echo "</form>";
						para("*) Do bear in mind that without a scale, you cannot know the exact weight of what you have or how much you're giving.");
						para("If you choose to give more than what you have, it will give the whole thing.");
						para("If a resource is counted in pieces and you try to give a weight smaller than one piece, it will round it to the smallest possible part.");
						para("If you select both a person and a group, person will take precedence over group. So if you are intending to give to a group and there are people present, make sure no person is selected.");
					
					}
					else if ($_POST["sel_action"]==3) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						$uses = $targetObject->getUses();
						if (empty($uses)) para("This item has no default use.");
						else {
							$handle = $targetObject->getName(false);
							ptag("h2", "Uses for $handle");
							echo "<ul class='tool'>";
							foreach ($uses as $use) {
								if ($use["type"]=="manu") {
									if ($use["preset"]==20) {
										$resource = new Resource($mysqli, $use["secondary"]);
										$resource->loadData();
										ptag("li", "Producing " . $resource->name . " <a href='index.php?page=manufacture&sel=" . $use["uid"]. "&userid=$currentUser&charid=$charcheck' class='clist'>[view details]</a>");
									}
									else {
										$preset = new Preset($mysqli, $use["preset"]);
										$preset->loadData();
										ptag("li", "Manufacturing " . $preset->name . " <a href='index.php?page=manufacture&sel=" . $use["uid"]. "&userid=$currentUser&charid=$charcheck' class='clist'>[view details]</a>");
									}
								}
								if ($use["type"]=="fire") {
									ptag("li", "Lighting a fire (" .$use["ap"]. " AP) - afterglow " . $use["reserve"] . " minutes per hour of burning <a href='index.php?page=startFire&charid=$charcheck&userid=$currentUser&container=$targetObject->uid&ptype=" . $use["uid"]. "' class='clist'>[start]</a>");
								}
							}
							echo "</ul>";
						}
					}
					else if ($_POST["sel_action"]==4) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						para("In which direction or towards which person?");
					}
					else if ($_POST["sel_action"]==5) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						ptag("h1", "Eat $oname");
						$edible = $targetObject->getAttribute(45, $charcheck);//check if edible
						if (!$edible) para("That's not edible.");
						else {
							echo "<form action='index.php' method='get' id='eatform' name='eatform' class='narrow'>";
							if ($is_countable) para("You have $targetObject->pieces of these.");
							para("What you have weighs $weight.");
							if ($edible==2) para("You have the feeling this might be poisonous or hallucinogenic. Proceed with caution.");
							else if ($edible==3) para("Either this isn't food or it's only marginally nutritious. Also some things can be harmful when swallowed.");
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
							echo "Grams to eat* ";
							ptag("input", "", "name='grams' type='text'");
							echo "</p>";
							if ($is_countable) {
								echo "<p>";
								ptag("input", "", "name='sel' type='radio' value='2'");
								echo "Or pieces to eat ";
								ptag("input", "", "name='pieces' type='text'");
								echo "</p>";
							}
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='3'");
							echo "Or eat all</p>";
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input" , "", "type='hidden' name='page' value='eat'");
							ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["sel"] . "'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Eat selected'");
							echo "</p>";
							echo "</form>";
							para("*) Do bear in mind that without a scale, you cannot know the exact weight of what you have or how much you're eating.");
							para("If you choose to eat more than what you have, it will eat the maximum you can considering your stomach capacity.");
							para("If a resource is counted in pieces and try to eat less than one piece, it will be rounded up to one piece.");
						}
					}
					else if ($_POST["sel_action"]==6) {
						include_once "header2.inc.php";
						echo "<div class='bar'>\n";
						ptag("h1", "Store $oname");
						
						$containers = $localMap->getContainers($charcheck);
						if ($containers) {
							echo "<form action='index.php?page=store' method='post' id='storeform' name='storeform' class='narrow'>";
							if ($is_countable) para("You have $targetObject->pieces of these.");
							para("What you have weighs $weight.");
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='1' checked='checked'");
							echo "Grams to store* ";
							ptag("input", "", "name='grams' type='text'");
							echo "</p>";
							if ($is_countable) {
								echo "<p>";
								ptag("input", "", "name='sel' type='radio' value='2'");
								echo "Or pieces to store ";
								ptag("input", "", "name='pieces' type='text'");
								echo "</p>";
							}
							echo "<p>";
							ptag("input", "", "name='sel' type='radio' value='3'");
							echo "Or store all</p>";
							para("Containers in this spot:");
							for ($i=0; $i<count($containers); $i++) {
								$selected = "";
								if ($i == 0) $selected = "checked='checked'";
								$obj = new Obj($mysqli, $containers[$i]["uid"]);
								$obj->getBasicData();
								$co_name = $obj->getName();
								echo "<p>";
								ptag("input", "", "name='sel2' type='radio' value='". $containers[$i]["uid"] ."' $selected");
								echo " " . $co_name . "</p>";
								echo "<p>Max capacity: ";
								$capacity = "";
								if ($containers[$i]["large"]) $capacity .= $containers[$i]["large"] . " large units";
								if ($containers[$i]["small"]) {
									if ($capacity) $capacity .= " and ";
									$capacity .= $containers[$i]["small"] . " small units";
								}
								echo $capacity;
								echo "</p>";
								
							}
							ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
							ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input" , "", "type='hidden' name='targetid' value='" . $_POST["sel"] . "'");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Store selected'");
							echo "</p>";
							echo "</form>";
						}
						else para("There are no containers nearby.");
					}
					else if ($_POST["sel_action"]==7) {
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
								ptag("input" , "", "type='hidden' name='containerid' value='" . $_POST["sel"] . "'");
								ptag("input" , "", "type='hidden' name='location' value='1'");
								echo "<p class='right'>";
								ptag("input", "", "type='submit' value='Take selected'");
								echo "</p>";
								echo "</form>";
							}
							else para("This container is empty.");
						}
						else para("This isn't a container");
					}
					echo "<p class='right'>";
					ptag("a", "Go back", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
					echo "</p>";
					echo "</div>\n";
				}
			}
		}
	}
}
?>
