<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_field_area.inc.php");

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
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) echo "This character doesn't have a body so it cannot be played.";
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot create fields on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
			}
			else {	
				$localMap = new LocalMap($mysqli, $pos->x, $pos->y);//to do: what if the char is in a building?
				$localCheck = $localMap->checkIfExists();
				
				if ($localCheck == -1) {
					include_once "header2.inc.php";
					para("You can't farm here since you're in middle of water.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if ($localCheck == -2) {
					include_once "header2.inc.php";
					para("You shouldn't be here because this location hasn't been explored.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if (!isset($_GET["act"])||!isset($_GET["fieldsel"])) {
					include_once "header2.inc.php";
					para("I don't know what you're trying to do. Most likely you forgot to select a field.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else if (!is_numeric($_GET["fieldsel"])) {
					include_once "header2.inc.php";
					para("Invalid field id.");
					para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
				}
				else {
					$target = new FieldArea($mysqli, $pos->x, $pos->y, false, round($_GET["fieldsel"]));//The rounding prevents errors in case some jerk enters a float
					$target->checkCoords();
					if ($target->gx!=$pos->x||$target->gy!=$pos->y) {
						include_once "header2.inc.php";
						para("Error, you are trying to access a field that's in another location.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
					}
					else if ($_GET["act"]==1) {
						$squareList = array();
						for ($y = 0; $y<100; $y++) {
							for ($x = 0; $x<100; $x++) {
								if (isset($_GET["ch-" . $x . "-" . $y])) {
									$squareList[] = array(
										"lx" => $x*10,
										"ly" => $y*10
										);
								}
							}
						}
						if (sizeof($squareList)>0) {
							$result = $target->addSquares($squareList);
							if (isset($_GET["detail"])) {
								if ($_GET["detail"]==1) {
									include_once "header2.inc.php";
									ptag("h4", "Detailed report:");
									para($result["num"] . " squares were added.");
									if (sizeof($result["handled"])>0) {
										para("The following squares were the ones added:");
										foreach ($result["handled"] as $handled) {
											para("(". $handled["lx"] .",". $handled["ly"] .")");
										}
									}
									else para("There were no valid squares to add.");
									if (sizeof($result["already"])>0) {
										para("The following squares were already part of this field and weren't added:");
										foreach ($result["already"] as $handled2) {
											para("(". $handled2["lx"] .",". $handled2["ly"] .")");
										}
									}
									else para("None of the selected squares were previously part of this field.");
									if (sizeof($result["other"])>0) {
										para("The following squares were already part of other squares and thus weren't added:");
										foreach ($result["other"] as $handled3) {
											para("(". $handled3["lx"] .",". $handled3["ly"] .")");
										}
										para("If you want to move them, you need to select the other action.");
									}
									else para("No squares were rejected due to being members of other fields.");
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
								}
							}
							else header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&added=". $result["num"]);
						}
						else header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&added=0");
					}
					else if ($_GET["act"]==2) {
						$squareList = array();
						for ($y = 0; $y<100; $y++) {
							for ($x = 0; $x<100; $x++) {
								if (isset($_GET["ch-" . $x . "-" . $y])) {
									$squareList[] = array(
										"lx" => $x*10,
										"ly" => $y*10
										);
								}
							}
						}
						if (sizeof($squareList)>0) {
							$result = $target->moveFromOtherField($squareList);
							if (isset($_GET["detail"])) {
								if ($_GET["detail"]==1) {
									include_once "header2.inc.php";
										if (!$result["success"]) para("Moving squares failed for some reason. Maybe you were trying to move squares to a field they were already a part of?");
										else {
										ptag("h4", "Detailed report:");
										para($result["num"] . " squares were moved.");
										if (sizeof($result["moved"])>0) {
											para("The following squares were the ones moved:");
											foreach ($result["moved"] as $handled) {
												para("(". $handled["lx"] .",". $handled["ly"] .")");
											}
										}
										else para("There were no valid squares to add.");
										if (sizeof($result["excluded"])>0) {
											para("The following squares were rejected because they weren't part of another field:");
											foreach ($result["excluded"] as $handled2) {
												para("(". $handled2["lx"] .",". $handled2["ly"] .")");
											}
										}
										else para("No squares were rejected due to not belonging to a field at all.");
									}
									
									para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
								}
							}
							else header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&moved=". $result["num"]);
						}
						else header('Location: index.php?page=viewchar&tab=10&userid=' . $currentUser . "&charid=$charcheck&added=0");
					}
					else if ($_GET["act"]==3) {
						include_once "header2.inc.php";
						echo "<div class='displayarea'>";
						ptag("h1", "Field actions");
						para("What you can do depends on the current status of the squares in the selected field. Actions will only target squares that are valid, the rest will be rejected.");
						para("A valid field has 10 or less trees, bush level 1 or 0, rocky level 1 or 0, grass level 1 or 0 and water level between 1 and 3, unless it's a rice paddy, in which case the water level needs to be 5 or more.");
						para("You can view the status of the squares at the bottom of the page (if there are any).");
						
						ptag("h2", "Ploughing");
						$av = $target->getAvailableTools($charcheck, 43, $currentUser);
						if (sizeof($av)>0) {
							echo "<form action='index.php?page=fieldAction2' method='post' class='narrow'>";
							foreach ($av as $tool) {
								echo "<p>";
								ptag("input", "", "type='radio' name='tool' value='" . $tool["uid"] . "'");
								echo " " . $tool["name"] . " (efficiency: " . $tool["ap_multi"] . " %)";
								echo "</p>";
							}
							ptag("input", "", "type='hidden' name='task' value=0");
							ptag("input", "", "type='hidden' name='charid' value=$charcheck");
							ptag("input", "", "type='hidden' name='fieldsel' value=" . round($_GET["fieldsel"]));
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Plough'");
							echo "</p>\n";
							echo "</form>";
						}
						else para("You don't have any suitable tools for this task, so you can't do it.");
						
						ptag("h2", "Sowing");
						para("Only works on fields that have been ploughed.");
						$av = $target->getSeeds($charcheck);
						if ($av) {
							para("You generally need 20 grams per square.");
							echo "<form action='index.php?page=fieldAction2' method='post' class='narrow'>";
							foreach ($av as $suid) {
								$seed = new Obj($mysqli, $suid);
								echo "<p>";
								ptag("input", "", "type='radio' name='seed' value='" . $suid . "'");
								echo " " . $seed->getName() . " (weight: " . $seed->weight . ")";
								echo "</p>";
							}
							ptag("input", "", "type='hidden' name='task' value=1");
							ptag("input", "", "type='hidden' name='charid' value=$charcheck");
							ptag("input", "", "type='hidden' name='fieldsel' value=" . round($_GET["fieldsel"]));
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Sow'");
							echo "</p>\n";
							echo "</form>";
						}
						else para("You don't have any plantable seeds.");
						
						ptag("h2", "Weeding");
						
						echo "<form action='index.php?page=fieldAction2' method='post' class='narrow'>";
						para("Only works on fields that actually have weeds on them. This takes 15 AP per weed level per square.");
						
						ptag("input", "", "type='hidden' name='task' value=2");
						ptag("input", "", "type='hidden' name='charid' value=$charcheck");
						ptag("input", "", "type='hidden' name='fieldsel' value=" . round($_GET["fieldsel"]));
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Weed'");
						echo "</p>\n";
						echo "</form>";
						
						ptag("h2", "Harvesting");
						
						para("Only works on fields that have something to harvest. The tool depends on the crop type. Some can be picked manually. Grains take a sickle or a scythe, while root vegetables take a pitchfork or a shovel");
						
						$av = $target->getAvailableTools($charcheck, 39, $currentUser);
						$av2 = $target->getAvailableTools($charcheck, 44, $currentUser);
						echo "<form action='index.php?page=fieldAction2' method='post' class='narrow'>";
						echo "<p>";
						ptag("input", "", "type='radio' name='tool' value='0' checked='checked'");
						echo " manual (efficiency: 100 %) *) Only works on harvesting that doesn't require tools.";
						echo "</p>";
						if (sizeof($av)>0) {
							
							foreach ($av as $tool) {
								echo "<p>";
								ptag("input", "", "type='radio' name='tool' value='" . $tool["uid"] . "'");
								echo " " . $tool["name"] . " (efficiency: " . $tool["ap_multi"] . " %)";
								echo "</p>";
							}
							
						}
						if (sizeof($av2)>0) {
							
							foreach ($av2 as $tool) {
								echo "<p>";
								ptag("input", "", "type='radio' name='tool' value='" . $tool["uid"] . "'");
								echo " " . $tool["name"] . " (efficiency: " . $tool["ap_multi"] . " %)";
								echo "</p>";
							}
							
						}
						echo "<p>Maximum to harvest: ";
						ptag("input", "", "name='max' type='number'");
						echo " grams</p>";
						para("If this is left blank, it will harvest everything, assuming you have enough AP and a sufficient tool.");
						ptag("input", "", "type='hidden' name='task' value=3");
						ptag("input", "", "type='hidden' name='charid' value=$charcheck");
						ptag("input", "", "type='hidden' name='fieldsel' value=" . round($_GET["fieldsel"]));
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Harvest'");
						echo "</p>\n";
						echo "</form>";
						
						
						$target->printStatus();
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=10' class='clist'>[Return to Large view]</a>");
						echo "</div>";
					}
					else if ($_GET["act"]==4) {
						include_once "header2.inc.php";
						echo "<div class='displayarea'>";
						ptag("h1", "Change color");
						echo "<form class='narrow' action='index.php?page=fieldAction2' method='post'>";
						echo "<p>Border color: ";
						echo '<input name="color1" class="jscolor {hash:true}" value="' . $target->hex . '">';
						echo "</p>";
						ptag("input", "", "type='hidden' name='charid' value=$charcheck");
						ptag("input", "", "type='hidden' name='fieldsel' value=" . round($_GET["fieldsel"]));
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Change color'");
						echo "</p>\n";
						echo "</form>";
						para("Note that if you enter some string that is not a valid color code, pure red will be substituted. You probably don't want that.");
						para("You might also want to pick colors you won't mix with each other, especially if you have some level of color blindness.");
						echo "</div>";
					}
				}
			}
		}
	}
}
?>
