<?php
//this needs the following post variables: charid, userid, sel3, sel_action3

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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$pos = $curChar->getPosition();
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
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot commit inventory actions on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_POST["sel_action3"])||!isset($_POST["sel3"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_POST["sel_action3"])||!is_numeric($_POST["sel3"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else if ($_POST["sel_action3"]<1||$_POST["sel_action3"]>7) {
				include_once "header2.inc.php";
				para("Error: Invalid action.");
			}
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				$targetObject = new Obj($mysqli, $_POST["sel3"]);
				//to do: buildings
				if ($targetObject->x!=$pos->x||$targetObject->y!=$pos->y||$targetObject->localx!=$pos->lx||$targetObject->localy!=$pos->ly) para("Error: You're trying to interact with an object in a different location.");
				else {
					$is_countable = $targetObject->getAttribute(ATTR_COUNTABLE);
					$is_container_l = $targetObject->getAttribute(ATTR_LARGE_CONTAINER);
					$is_container_s = $targetObject->getAttribute(ATTR_SMALL_CONTAINER);
					$weight = $targetObject->approximateWeight();
					$oname = $targetObject->getName(false);
					if ($_POST["sel_action3"]==1) {
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
							}
							echo "</ul>";
						}
						
					}
					else if ($_POST["sel_action3"]==2) {
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
								ptag("input" , "", "type='hidden' name='containerid' value='" . $_POST["sel3"] . "'");
								ptag("input" , "", "type='hidden' name='location' value='2'");
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
