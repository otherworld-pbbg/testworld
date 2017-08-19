<?php
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once "local_map.inc.php";
include_once("class_obj.inc.php");
include_once("class_build_menu2.inc.php");
include_once("class_field_area.inc.php");
include_once("class_group.inc.php");
include_once("class_event.inc.php");

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
	$bodyId = $curChar->getBasicData();
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	
	$combat = $curChar->checkCurrentCombat();
	if ($combat>0) header('Location: index.php?page=combat&charid=' . $charcheck . '&userid=' . $currentUser);
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		echo "<div class='alert alert-info'>";
		echo "<strong>Disclaimer:</strong> You are a watcher, so you can't carry out actions. You only see what the character sees.";
		echo "</div>";
	}
	
	
	
	//user is authorized to view this character
	
	if ($bodyId == -1) {
		include_once "header2.inc.php";
		displayBodywarning();
	}
	else {
		$pos = $curChar->getPosition();
		if ($curChar->building>0) {
			$building = new Obj($mysqli, $curChar->building);
			$coords = $building->getExitCoordinates();
			$currentLocation = new GlobalMap($mysqli, $coords["x"], $coords["y"]);
			$lx = $coords["lx"];
			$ly = $coords["ly"];
		}
		else {
			$building = false;
			$currentLocation = new GlobalMap($mysqli, $curChar->x, $curChar->y);
			$lx = $curChar->localx;
			$ly = $curChar->localy;
		}
		$pixArr = $currentLocation->getMajorPixel($currentLocation->x, $currentLocation->y);//This is used for reading picture maps
		$bodyObj = new Obj($mysqli, $bodyId);
		$bodyObj->getBasicData();
		
		$curTime = new Time($mysqli);//This resets to default because no starting point is given
		
		$timeofday = $curTime->getTimeofDay($currentLocation->x, $currentLocation->y);
		if ($timeofday=="Darkness") {
			$isnight = true;//This is used to dim the map
			$_SESSION["night"] = "night";
		}
		else {
			$isnight = false;
			$_SESSION["night"] = "not";
		}
		
		include_once "header2.inc.php";//This can only be called after the cookies are set
		
		if ($bodyObj->type==10) {
			if ($watcherRole>1) {
				echo "<div class='alert alert-info'>\n";
				ptag("h3", "Notice:", "");
				para("This character is dead, so you can only see the ongoing scenes.");
				para("Once you are done reading, click below to resign as a watcher.");
				para("<a href='index.php?page=resign&charid=$charcheck&userid=$currentUser' class='clist'>[Resign]</a>");
				echo "</div>\n";
			}
			else {
				echo "<div class='alert alert-info'>\n";
				ptag("h3", "Notice:", "");
				para("This character is dead, so you're only allowed to wrap up scenes that are ongoing in your past. In those cases, your character is not allowed to know that they're going to die or how because from their perspective, it hasn't happened yet.");
				para("Once you've got your character's affairs in order, click the link below to resign from playing this character.");
				para("<a href='index.php?page=resign&charid=$charcheck&userid=$currentUser' class='clist'>[Resign]</a>");
				echo "</div>\n";
			}
		}
		
		echo "</div>\n";
		echo "<div class='row'>\n";
		echo "<div class='col-lg-2'>\n";
		ptag("h4", "Current character:");
		para("<a href='index.php?page=formCharName&charid=$charcheck&userid=$currentUser&ocharid=" . $charcheck . "' class='clist'>" . $curChar->cname . "</a>");
		$ageArr = $curChar->getAge();
		para("Age: ".$ageArr[0]." years, ".$ageArr[1]." months");
		para ($curChar->getAgeSex());
		echo "</div>\n<div class='col-lg-1'>\n";
		$blood_per = $bodyObj->getBloodPercentage();
		if ($blood_per<80) ptag("img", "", "src='". getGameRoot() . "/graphics/icon_wounded.png' alt='seriously wounded'");
		else if ($blood_per<95) ptag("img", "", "src='". getGameRoot() . "/graphics/icon_so-so.png' alt='somewhat wounded'");
		else ptag("img", "", "src='". getGameRoot() . "/graphics/icon_healthy.png' alt='healthy'");
		
		ptag("img", "", "src='" . getGameRoot() . "/graphics/icon_satiated.png' alt='satiated'");//this is currently static
		echo "</div>\n";
		echo "<div class='col-lg-2'>\n";
		ptag("h4", "Current location:");
		para($currentLocation->x . ", " . $currentLocation->y);
		$locnameArr = $curChar->getLocationName($currentLocation->x, $currentLocation->y);
		para("<a href='index.php?page=formLocName&charid=$charcheck&userid=$currentUser&x=" . $currentLocation->x . "&y=" . $currentLocation->y . "' class='clist'>" . $locnameArr["name"] . "</a>");
		if ($curChar->building>0) {
				$po = new Obj($mysqli, $curChar->building);
				if ($po->type==7) {
					para("In group");
					para("<a href='index.php?page=leavegroup&charid=$charcheck' class='normal'>[Leave group]</a>");
				}
				else para("Inside");
			}
		echo "</div>\n";
		echo "<div class='col-lg-2'>\n";
		ptag("h4", "Time and date:");
		
		
		para($curTime->getDateTime());
		
		para($curTime->getSeason($pos->y) . " / " . $timeofday);
		echo "</div>\n";
		echo "<div class='col-lg-5'>\n";
		$curAP = $curChar->getAP();
		para("AP: $curAP");
		$weather = $curTime->getWeather($currentLocation->x, $currentLocation->y);
		para("Temperature: " . $curTime->describeTemperature($weather["temp"]) . " (" . $weather["temp"] . " C)");
		if ($weather["rain"]>0) {
			if ($weather["temp"]<0) para("It's snowing.");
			else if ($weather["temp"]==0) para("Wet snow is falling.");
			else if ($weather["rain"]==2) para("Wet snow is falling. It melts upon hitting the ground.");
			else if ($weather["temp"]<10) para("It's raining and chilly.");
			else if ($weather["temp"]<25) para("It's raining.");
			else para("It's raining but warm.");
		}
		else para("Feels: " . $curTime->describeDewpoint($weather["dew"]));
		echo "</div>\n";
		echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		
		echo "<nav class='navbar navbar-inverse' data-spy='affix' data-offset-top='200'>\n";
		echo "<div class='container-fluid'>";
		echo "<ul class='nav navbar-nav'>";
		echo "<li>";
		ptag("a", "Timeline", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=5'");
		echo "</li><li>";
		ptag("a", "Activities", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=2'");
		echo "</li><li>";
		ptag("a", "Environment", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=3'");
		echo "</li><li>";
		ptag("a", "Items & Resources", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=4'");
		echo "</li><li>";
		ptag("a", "Scenes", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=1'");
		echo "</li><li>";
		ptag("a", "Groups", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=11'");
		echo "</li><li>";
		ptag("a", "Travel log", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=12'");
		echo "</li>";
		echo "</ul>";
		echo "</div>";
		echo "</nav>\n";
		
		echo "<div class='container-fluid'>\n";
		
		if (isset($_GET['tab']))
		{
			$tab = $mysqli->real_escape_string($_GET['tab']);
			if (!is_numeric($tab)||$tab>12||$tab<1) $tab=0;//not showing anything but the top
			else if ($bodyObj->type==10) $tab=1;//Only show scenes when dead
		}
		else $tab=0;
		
		if ($tab==1) {
			include_once $privateRoot . "/class_scene.inc.php";;
			echo "<div class='row'>\n";
			if ($bodyObj->type==2)
			{
				echo "<p class='right'>";
				ptag("a", "[Create new scene]", "href='index.php?page=scenecreator&charid=$charcheck&userid=$currentUser' class='clist'");
				echo "</p>";
			}
			echo "<div class='col-lg-6'>\n";
			ptag("h1", "Public scenes");
			$scenes = $curChar->getScenesNearby(1);//this needs to be changed so that instead of current physical location it uses the charloctime table
			if ($scenes==-1) para("None at this area.");
			else {
				for ($i=0;$i<count($scenes);$i++) {
					$sceneObject = new Scene($mysqli, $scenes[$i]);
					$check = $sceneObject->loadValues();
					if ($check==1) {
						ptag("h2", $sceneObject->title);
						para($sceneObject->desc);
						$participations = $curChar->getParticipationTimes($scenes[$i]);
						$lastPosted = $sceneObject->getCurrentRow();
						
						$charRole = $sceneObject->getParticipantStatus($charcheck);
						
						if ($charRole<4&&$charRole>0) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							echo "<p class='boldP'>Status: Participating ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==5) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							echo "<p class='boldP'>Status: Eavesdropping - EXPOSED ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==4) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							echo "<p class='boldP'>Status: Eavesdropping ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==-5) {
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							echo "<p class='boldP'>Status: Kicked out";
							echo "Cannot rejoin ";
							ptag("a", "[View log]", "href='$link' class='clist'");
							echo "</p>";
						}
						else if ($charRole<0) {
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes[$i];
							$link1 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes[$i] . "&role=2";
							$link2 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes[$i] . "&role=4";
							echo "<p class='boldP'>Status: Left ";
							ptag("a", "[View log]", "href='$link' class='clist'");
							ptag("a", "[Re-join]", "href='$link1' class='clist'");
							ptag("a", "[Eavesdrop]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else {
							$link = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes[$i] . "&role=2";
							$link2 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes[$i] . "&role=4";
							echo "<p class='boldP'>Status: Not participating ";
							ptag("a", "[Join]", "href='$link' class='clist'");
							ptag("a", "[Eavesdrop]", "href='$link2' class='clist'");
							echo "</p>";
						}
					}
				}
			}
			echo "</div><div class='col-lg-6'>\n";
			ptag("h1", "Private scenes");
			$scenes2 = $curChar->getScenesNearby(2);
			if ($scenes2==-1) para("None at this area.");
			else {
				for ($i=0;$i<count($scenes2);$i++) {
					$sceneObject2 = new Scene($mysqli, $scenes2[$i]);
					$check2 = $sceneObject2->loadValues();
					if ($check2==1) {
						ptag("h2", $sceneObject2->title);
						para($sceneObject2->desc);
						$participations = $curChar->getParticipationTimes($scenes2[$i]);
						$lastPosted = $sceneObject2->getCurrentRow();
						
						$charRole = $sceneObject2->getParticipantStatus($charcheck);
						
						if ($charRole==3) {
							$link = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes2[$i] . "&role=3";
							echo "<p class='boldP'>Status: Invited ";
							ptag("a", "[Join]", "href='$link' class='clist'");
							echo "</p>";
						}
						else if ($charRole<3&&$charRole>0) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							echo "<p class='boldP'>Status: Participating ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==5) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							echo "<p class='boldP'>Status: Eavesdropping - EXPOSED ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==4) {
							if ($participations) {
								$lastRead=$participations[count($participations)-1]["lastread"];
								if ($lastRead<$lastPosted) para("(New events)");
								else para("(No new events)");
							}
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							$link2 = 'index.php?page=leavescene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							echo "<p class='boldP'>Status: Eavesdropping ";
							ptag("a", "[View]", "href='$link' class='clist'");
							ptag("a", "[Leave]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole==-5) {
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							echo "<p class='boldP'>Status: Kicked out";
							echo "Cannot rejoin ";
							ptag("a", "[View log]", "href='$link' class='clist'");
							echo "</p>";
						}
						else if ($charRole==-4) {
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							$link2 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes2[$i] . "&role=4";
							echo "<p class='boldP'>Status: Left eavesdropping ";
							echo "[Invite only] ";
							ptag("a", "[View log]", "href='$link' class='clist'");
							ptag("a", "[Re-Eavesdrop]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else if ($charRole<0) {
							$link = 'index.php?page=viewscene&charid=' . $charcheck . '&userid=' . $currentUser . '&scene=' . $scenes2[$i];
							$link1 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes2[$i] . "&role=2";
							$link2 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes2[$i] . "&role=4";
							echo "<p class='boldP'>Status: Left ";
							ptag("a", "[View log]", "href='$link' class='clist'");
							ptag("a", "[Re-join]", "href='$link1' class='clist'");
							ptag("a", "[Eavesdrop]", "href='$link2' class='clist'");
							echo "</p>";
						}
						else {
							$link2 = "index.php?page=joinscene&userid=" . $currentUser . "&charid=" . $charcheck . "&scene=" . $scenes2[$i] . "&role=4";
							echo "<p class='boldP'>Status: Not participating ";
							echo "[Invite only] ";
							ptag("a", "[Eavesdrop]", "href='$link2' class='clist'");
							echo "</p>";
						}
					}
				}
			}
			
			
			echo "</div>\n</div>\n";
		}
		if ($tab==2) {
			echo "<div class='row'>\n";
			
			if (isset($_GET['errormessage'])) {
				if ($_GET['errormessage']=='1') para("You tried to fight when you're not actually engaged in any combat.");
				if ($_GET['errormessage']=='2') para("You couldn't find any resource deposits.");
			}
			
			ptag("h1", "Activities");
			
			$tiredStr = $curChar->getTirednessLevel($curAP);
			para($tiredStr);
			
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);
			$localCheck = $localMap->checkIfExists();
			if ($localCheck == -2) $threshold = 76;
			else $threshold = 1;
			
			$natural = $currentLocation->getResources();
			$resources = $currentLocation->loadResources($natural, $threshold);
			ptag("h2", "Foraging");
			para("Only selected resources will be taken into consideration. There is a chance of failure unless at least one visible resource is selected and AP is at maximum (30). The more AP you spend, the higher the chance of finding something.");
			para("When gathering resources, by default they go on the ground and you need to pick them up if you want to move them. Do bear in mind that your carrying capacity is limited, so it might not make sense to gather more than you can carry.");
			$hiddenHere = 0;
			$visCounter = 0;
			if ($resources) {
				echo "<form name='forageform' id='forageform' action='index.php?page=forage' method='post' class='narrow'><ul class='normal'>";
				for ($i=0;$i<count($resources);$i++) {
					if ($resources[$i]["hidden"]==0||($resources[$i]["hidden"]==1&&$threshold==1)) {
						ptag("li", "<input type='checkbox' name='res" . $i . "' value='". $resources[$i]["uid"] ."'> " . $resources[$i]["name"], "class='reslist'");
						$visCounter++;
					}
					else $hiddenHere++;
				}
				if ($hiddenHere>0) ptag("li", "<input type='checkbox' name='resx' value='hid'> Search for things you cannot see", "class='reslist'");
				ptag("input", "", "type='hidden' name='resnum' value='$visCounter'");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				echo "</ul><p>";
				ptag("label", "Maximum AP to spend searching: ", "for='duration'");
				echo "<select name='duration' form='forageform'>";
				ptag("option", "5 AP", "value='1'");
				ptag("option", "10 AP", "value='2'");
				ptag("option", "15 AP", "value='3'");
				ptag("option", "20 AP", "value='4' selected='selected'");
				ptag("option", "25 AP", "value='5'");
				ptag("option", "30 AP", "value='6'");
				echo "</select></p><p class='right'>";
				ptag("input", "", "type='submit' value='Search'");
				echo "</p></form>\n";
			}
			else para("There are no resources here.");
			
			para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=7' class='clist'>[View memorized resource spots]</a>");
			
			ptag("h2", "Manufacturing items");
			ptag("h3", "<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=8' class='clist'>List of items that can be manufactured</a>");
			ptag("h3", "Existing projects");
			$buildMenu = new BuildMenu2($mysqli, $currentUser, $charcheck);
			$buildMenu->listProjectsHere();
			
			ptag("h2", "Felling trees/Clearing ground vegetation");
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);//to do: what if the char is in a building?
			$localCheck = $localMap->checkIfExists();
			
			if ($localCheck == -1) para("There are no trees here because you're in the middle of water.");
			else if ($localCheck == -2) para("Before you can fell trees, you (or someone else) needs to explore this location. That can be done on the Environment page.");
			else {
				$vege = $localMap->getVegeVerbal($lx, $ly);
				echo "<form method='get' action='index.php' class='narrow'>";
				ptag("input" , "", "type='hidden' name='page' value='chop'");
				echo "<p>";
				ptag("input" , "", "type='radio' name='sel' value='1'");
				ptag("label", "There are " . $vege["trees"] . " trees in your immediate vicinity.<br>");
				ptag("input" , "", "type='radio' name='sel' value='2'");
				ptag("label", $vege["bushes"] . "<br>");
				ptag("input" , "", "type='radio' name='sel' value='3'");
				ptag("label", $vege["grass"] . "<br>");
				echo "</p>";
				
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Cut selected'");
				echo "</p>\n";	
				echo "</form>";
			}
			
			ptag("h2", "Look for wild animals");
			
			echo "<form method='get' action='index.php' class='narrow' name='animalform' id='animalform'>";
			ptag("input" , "", "type='hidden' name='page' value='searchAnimal'");
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			ptag("label", "Maximum AP to spend searching: ", "for='duration'");
			echo "<select name='duration' form='animalform'>";
			ptag("option", "10 AP", "value='1'");
			ptag("option", "20 AP", "value='2'");
			ptag("option", "30 AP", "value='3' selected='selected'");
			ptag("option", "40 AP", "value='4'");
			ptag("option", "50 AP", "value='5'");
			echo "</select></p><p class='right'>";
			ptag("input", "", "type='submit' value='Search'");
			echo "</p></form>\n";
			
			ptag("h2", "Wild animals present");
			$objects = $localMap->getObjects("4", $charcheck);
			if ($objects) {
				para("There is going to be a combat rewrite, and currently there is no healing, so be cautious when attacking animals.");
				echo "<form action='index.php?page=startCombat' method='post' id='animalform2' name='animalform2' class='narrow'>";
				for ($i=0; $i<count($objects); $i++) {
					$selected = "";
					if ($i == 0) $selected = "checked='checked'";
					echo "<p>";
					$animal = new Obj($mysqli, $objects[$i]);
					$animal->getBasicData();
					$handle = $animal->getHandle();
					ptag("input", "", "type='radio' id='target-$animal->uid' name='target' value='$animal->uid' $selected");
					echo $handle;
					echo "</p>";
				}
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Attack'");
				echo "</p>";
				echo "</form>";
			}
			else para("You can't see any wild animals in your immediate vicinity.");
			
			echo "</div>\n";
		}
		if ($tab==3) {
			echo "<div class='row'>\n";
			
			$curChar->updateCharLocTime($currentLocation->x, $currentLocation->y, $lx, $ly, $curChar->building, 2, 0);
			ptag("h1", "Environment");
			
			if (isset($_GET['errormessage'])) {
				if ($_GET['errormessage']=='1') para("You picked an unknown direction to travel.");
				else if ($_GET['errormessage']=='2') para("You tried to travel into impassable terrain (water or steep mountainside).");
				else if ($_GET['errormessage']=='3') para("You don't have enough AP.");
				else if ($_GET['errormessage']=='4') para("Location couldn't be updated for some reason. Sorry I deducted your AP, you should ask for a refund.");
				else if ($_GET['errormessage']=='5') para("You're in water, so you cannot generate a local map.");
				else if ($_GET['errormessage']=='6') para("One or more of the group members doesn't have enough AP to move.");
				else if ($_GET['errormessage']=='7') para("An impossible error occurred.");
				else if ($_GET['errormessage']=='8') para("You tried to move a group you are not authorized to move. If you want to travel solo, you need to leave the group first.");
			}
			
			
			
			echo "<p>Terrain(s): ";
			$currentLocation->listTerrains();
			echo "</p>";
			
			$vegeDesc = $currentLocation->printVegetation();
			para($vegeDesc);
			
			$rowDesc = $currentLocation->printROW();
			para($rowDesc);
			
			$resArr = $currentLocation->getResources();
			
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);//to do: what if the char is in a building?
			$localCheck = $localMap->checkIfExists();
			
			if ($localCheck == -1) para("You are in water. Hopefully you're in a boat.");//in the future this will actually check if you're in a boat
			else if ($localCheck == -2) {
				ptag("h2", "Resources:");
				if (!$resArr) para("There are no natural resources here.");
				else $currentLocation->printResources($resArr, 76);
				
				echo "<form action='index.php?page=explore' method='post' class='narrow' id='exploreform'>";
				para("This location hasn't been explored. Exploring it costs 50 AP. Would you like to unlock it?");
				echo '<div class="alert alert-info">';
				echo "<strong>Disclaimer:</strong> It's not mandatory to explore a location if you're just passing through, but if you're going to drop items, clear away underbrush, cut down trees, or build dwellings or other structures, in those cases the local map needs to be unlocked first.";
				echo "</div>";
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Unlock'");
				echo "</p></form>";
			}
			else {
				para("This location has been explored.");
				
				ptag("h2", "Resources:");
				if (!$resArr) para("There are no natural resources here.");
				else $currentLocation->printResources($resArr, 1);
				
				ptag("h2", "Move locally:");
				
				para("The map will scroll so that you're in the center unless you're already near the edge.");
				
				$localMap->loadcreate();

				echo "<form action='index.php?page=movelocal' method='post' id='moveform'>";
				echo "<div class='localmap'>";
				$localMap->printLocal( $lx, $ly, 5, 1, $isnight);
				echo "</div>";
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				ptag("input" , "", "type='hidden' id='selfield' name='selfield' value=''");
				para("Current local position: (" . $lx . ", " . $ly . ").");
				echo "</form>";
			}
			
			$animals = $currentLocation->getPossibleAnimals();
			ptag("h2", "Wild animals:");
			
			if ($animals == -1) para("You haven't seen any animals around these parts yet.");
			else {
				echo "<p>Animals that might be found here: ";
				foreach ($animals as $key => $animal) {
					if ($key>0) echo ", ";
					if ($animal["plural"]=="") echo $animal["name"] . "s";
					else echo $animal["plural"];
				}
				echo ".</p>";
			}
			
			ptag("h2", "Surrounding locations:");
			
			$multipliers = $currentLocation->getAllTravelMultipliers(false);
			$neighbors = $currentLocation->getNeighbors();

			$npix = $currentLocation->getMajorPixel($neighbors["n"][0],$neighbors["n"][1]);
			echo "<p>North: ";
			$currentLocation->listTerrains($npix["x"], $npix["y"]);
			if ($multipliers["n"]==-1) {
				$neighborLoc = new GlobalMap($mysqli, $neighbors["n"][0], $neighbors["n"][1]);
				echo " - " . $neighborLoc->currentVerbal();
			}
			echo "</p>";
			
			$epix = $currentLocation->getMajorPixel($neighbors["e"][0],$neighbors["e"][1]);
			echo "<p>East: ";
			$currentLocation->listTerrains($epix["x"], $epix["y"]);
			if ($multipliers["e"]==-1) {
				$neighborLoc = new GlobalMap($mysqli, $neighbors["e"][0], $neighbors["e"][1]);
				echo " - " . $neighborLoc->currentVerbal();
			}
			echo "</p>";
			
			$spix = $currentLocation->getMajorPixel($neighbors["s"][0],$neighbors["s"][1]);
			echo "<p>South: ";
			$currentLocation->listTerrains($spix["x"], $spix["y"]);
			if ($multipliers["s"]==-1) {
				$neighborLoc = new GlobalMap($mysqli, $neighbors["s"][0], $neighbors["s"][1]);
				echo " - " . $neighborLoc->currentVerbal();
			}
			echo "</p>";
			
			$wpix = $currentLocation->getMajorPixel($neighbors["w"][0],$neighbors["w"][1]);
			echo "<p>West: ";
			$currentLocation->listTerrains($wpix["x"], $wpix["y"]);
			if ($multipliers["w"]==-1) {
				$neighborLoc = new GlobalMap($mysqli, $neighbors["w"][0], $neighbors["w"][1]);
				echo " - " . $neighborLoc->currentVerbal();
			}
			echo "</p>";
			
			ptag("h2", "Travel:");
			
			
			$ap_km = 15;//if you change this, also change it in travel.inc.php
			
			$aps = array(
				"n" => round($multipliers["n"]*$ap_km),
				"e" => round($multipliers["e"]*$ap_km),
				"s" => round($multipliers["s"]*$ap_km),
				"w" => round($multipliers["w"]*$ap_km),
				"ne" => round($multipliers["ne"]*$ap_km),
				"se" => round($multipliers["se"]*$ap_km),
				"sw" => round($multipliers["sw"]*$ap_km),
				"nw" => round($multipliers["nw"]*$ap_km)
				);
			
			echo "<form action='index.php?page=travel' method='post' class='compass' id='travelform'>";
			
			echo "<div class='row'>";
			
			if ($multipliers["nw"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["nw"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["nw"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "North-west:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='8' />" . $aps["nw"] . " AP/leg");
			}
			echo "</div>";
			if ($multipliers["n"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["n"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["n"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "North:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='1' />" . $aps["n"] . " AP/leg");
			}
			echo "</div>";
			if ($multipliers["ne"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["ne"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["ne"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "North-east:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='2' />" . $aps["ne"] . " AP/leg");
			}
			echo "</div>";
			echo "</div>";
			echo "<div class='row'>";
			if ($multipliers["w"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["w"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["w"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "West:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='7' />" . $aps["w"] . " AP/leg");
			}
			echo "</div>";
			echo "<div class='square2'>";
			//here
			echo "</div>";
			if ($multipliers["e"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["e"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["e"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "East:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='3' />" . $aps["e"] . " AP/leg");
			}
			echo "</div>";
			echo "</div>";
			echo "<div class='row'>";
			if ($multipliers["sw"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["sw"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["sw"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "South-west:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='6' />" . $aps["sw"] . " AP/leg");
			}
			echo "</div>";
			if ($multipliers["s"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["s"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["s"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "South:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='5' />" . $aps["s"] . " AP/leg");
			}
			echo "</div>";
			if ($multipliers["se"]==-3) {
				echo "<div class='square_steep'>";
				para("Blocked: too steep");
			}
			else if ($multipliers["se"]==-2) {
				echo "<div class='square_steep'>";
				para("Blocked: semi-steep");
			}
			else if ($multipliers["se"]==-1) {
				echo "<div class='square_water'>";
				para("Blocked: water");
			}
			else {
				echo "<div class='square2'>";
				ptag("h3", "South-east:", "class='inline'");
				ptag("p", "<input type='radio' name='direction' value='4' />" . $aps["se"] . " AP/leg");
			}echo "</div>";
			
			echo "</div>";
			
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			echo "<p class='right'>";
			ptag("label", "Legs of the road:* ", "for='leg'");
			echo "<select id='leg' name='leg' form='travelform'>";
			ptag("option", "1", "value='1' selected='selected'");
			for ($i=2;$i<11;$i++) {
				ptag("option", "$i", "value='$i'");
			}
			echo "</select>";
			echo "</p>";
			echo "<p class='right'>";
			ptag("input", "", "type='submit' name='submit' value='Travel'");
			echo "</p>";
			echo "</form>";
			para("*) Legs of the road can take more or less AP depending on terrain modifiers. If you don't have enough AP left, the travel will be aborted.");
			para("Things that affect the AP: ground vegetation, bushes, going uphill/downhill (going down shallow hills is faster but steep hills make it harder), water levels (more than 50% is considered swampy or so full of tide pools and small creeks that it hinders travel greatly).");
			echo "</div>\n";
		}
		if ($tab==4) {
			echo "<div class='row'>\n";
			if (isset($_GET['message'])) {
				if ($_GET['message']=='1') para("Object was given successfully.");
				else if ($_GET['message']=='2') para("This message isn't currently in use.");
				else if ($_GET['message']=='3') para("This message isn't currently in use.");
				else if ($_GET['message']=='4') para("Your project is finished. Where the result lands depends mainly on the size.");
			}
			if (isset($_GET['success'])) {
				if ($_GET['success']>0) para("You successfully multi-dropped " . $_GET['success'] . " items or piles.");
			}
			if (isset($_GET['fail'])) {
				if ($_GET['fail']>0) para("You failed to multi-drop " . $_GET['fail'] . " items or piles.");
			}
			ptag("h1", "Items & Resources");
			ptag("h2", "Carried items");
			$inventory = $curChar->getInventory();
			if ($inventory) {
				echo "<h3>";
				ptag ("a", "[Drop multiple]", "href='index.php?page=dropMulti&charid=$charcheck' class='clist'");
				echo "</h3>";
				echo "<form action='index.php?page=inventoryAction' method='post' id='invform' name='invform' class='narrow'>";
				for ($i=0; $i<count($inventory); $i++) {
					$selected = "";
					if ($i == 0) $selected = "checked='checked'";
					echo "<p>";
					$invItem = new Obj($mysqli, $inventory[$i]);
					$invItem->getBasicData();
					$handle = $invItem->getHandle();
					ptag("input", "", "type='radio' id='sel-$invItem->uid' name='sel' value='$invItem->uid' $selected");
					echo $handle . $invItem->getStatus($charcheck);;
					echo "</p>";
					$contents = $invItem->getContents();
					if ($contents) {
						echo "<ul class='small_list'>";
						for ($j=0; $j<count($contents); $j++) {
							$inItem = new Obj($mysqli, $contents[$j]);
							$inItem->getBasicData();
							$handle2 = $inItem->getHandle() . $inItem->getStatus($charcheck);;
							ptag("li", "$handle2", "class='small_list'");
						}
						echo "</ul>";
					}
				}
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				ptag("input" , "", "type='hidden' id='sel_action' name='sel_action' value='0'");
				echo "<p>";
				ptag("input", "", "type='button' id='action-1' value='Drop' onclick='invClick(1)'");
				ptag("input", "", "type='button' id='action-2' value='Give' onclick='invClick(2)'");
				ptag("input", "", "type='button' id='action-3' value='Use' onclick='invClick(3)'");
				//ptag("input", "", "type='button' id='action-4' value='Throw' onclick='invClick(4)'");
				ptag("input", "", "type='button' id='action-5' value='Eat' onclick='invClick(5)'");
				ptag("input", "", "type='button' id='action-6' value='Store' onclick='invClick(6)'");
				ptag("input", "", "type='button' id='action-7' value='Take from inside' onclick='invClick(7)'");
				echo "</p>";
				echo "</form>";
			}
			else para("You are currently carrying nothing.");
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);//to do: what if the char is in a building?
			ptag("h2", "Items on the ground");
			$objects = $localMap->getObjects("1,5,8,9,10,11", $charcheck);
			if ($objects) {
				echo "<form action='index.php?page=groundAction' method='post' id='groundform' name='groundform' class='narrow'>";
				for ($i=0; $i<count($objects); $i++) {
					$selected = "";
					if ($i == 0) $selected = "checked='checked'";
					echo "<p>";
					$groundItem = new Obj($mysqli, $objects[$i]);
					$groundItem->getBasicData();
					$handle = $groundItem->getHandle();
					ptag("input", "", "type='radio' id='sel2-$groundItem->uid' name='sel2' value='$groundItem->uid' $selected");
					echo $handle;
					echo "</p>";
					$contents = $groundItem->getContents("1,5,8,9,10,11");
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
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				ptag("input" , "", "type='hidden' id='sel_action2' name='sel_action2' value='0'");
				echo "<p>";
				ptag("input", "", "type='button' id='action2-1' value='Pick up' onclick='groundClick(1)'");
				ptag("input", "", "type='button' id='action2-2' value='Use' onclick='groundClick(2)'");
				//ptag("input", "", "type='button' id='action2-3' value='Push' onclick='groundClick(3)'");
				ptag("input", "", "type='button' id='action2-4' value='Store' onclick='groundClick(4)'");
				ptag("input", "", "type='button' id='action2-5' value='Take apart' onclick='groundClick(5)'");
				ptag("input", "", "type='button' id='action2-6' value='Take from inside' onclick='groundClick(6)'");
				echo "</p>";
				echo "</form>";
			}
			else para("There are no items on the ground at this spot.");
			
			ptag("h2", "Machines and other fixed structures");
			$machines = $localMap->getObjects("6", $charcheck);
			if ($machines) {
				echo "<form action='index.php?page=fixedAction' method='post' id='fixedform' name='fixedform' class='narrow'>";
				foreach ($machines as $key => $machine) {
					$selected = "";
					if ($key == 0) $selected = "checked='checked'";
					echo "<p>";
					$groundItem = new Obj($mysqli, $machine);
					
					$handle = $groundItem->getHandle();
					ptag("input", "", "type='radio' id='sel3-$groundItem->uid' name='sel3' value='$groundItem->uid' $selected");
					echo $handle;
					echo "</p>";
					$contents = $groundItem->getContents("1,5,8,9,10,11");
					if ($contents) {
						echo "<ul class='small_list'>";
						foreach ($contents as $in_obj) {
							$inItem = new Obj($mysqli, $in_obj);
							$handle2 = $inItem->getHandle() . $inItem->getStatus($charcheck);
							ptag("li", "$handle2", "class='small_list'");
						}
						echo "</ul>";
					}
				}
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				ptag("input" , "", "type='hidden' id='sel_action3' name='sel_action3' value='0'");
				echo "<p>";
				ptag("input", "", "type='button' id='action3-1' value='Use' onclick='fixedClick(1)'");
				ptag("input", "", "type='button' id='action3-2' value='Take from inside' onclick='fixedClick(2)'");
				echo "</p>";
				echo "</form>";
			}
			else para("There are no fixed structures at this spot.");
			
			ptag("h2", "Items nearby");
			$elsewhereObjects = $localMap->getObjectsNearby(55, "1,5,6,8,11", $lx, $ly);
			if ($elsewhereObjects) {
				echo "<form action='index.php?page=goto' method='post' id='gotoform' name='gotoform' class='narrow'>";
				for ($i=0; $i<count($elsewhereObjects); $i++) {
					$selected = "";
					if ($i == 0) $selected = "checked='checked'";
					$elsewhereItem = new Obj($mysqli, $elsewhereObjects[$i]["uid"]);
					$elsewhereItem->getBasicData();
					$handle = $elsewhereItem->getHandle();
					echo "<p>";
					ptag("input", "", "type='radio' id='sel3-" . $elsewhereObjects[$i]["uid"]. "' name='sel3' value='" . $elsewhereObjects[$i]["uid"] . "' $selected");
					echo $handle . " (" . $elsewhereObjects[$i]["direction"] . ")";
					echo "</p>";
				}
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Go to selected'");
				echo "</p></form>";
				echo "</form>";
			}
			else para("There are no items nearby.");
			
			echo "</div>\n";
		}
		if ($tab==5) {
			echo "<div class='row'>\n";
			ptag("h1", "Timeline");
			
			echo "<div id='ediv' class='eventlog'>";
			$curChar->printEventLog();
			echo "</div>";
			?>
			<script>
			$('#ediv').scrollTop($('#ediv')[0].scrollHeight);
			</script>
			<?php
			ptag("h2", "People currently here");
			$pplCurHere = $curTime->getPplCurrentlyLocation($curChar->x, $curChar->y, $curChar->building, $curChar->uid);
			if ($pplCurHere) {
				echo "<div>";
				for ($i=0; $i<count($pplCurHere); $i++) {
					if ($pplCurHere[$i]["status"]==2) $status = "moving locally";
					else if ($pplCurHere[$i]["status"]==3) $status = "traveling";
					else if ($pplCurHere[$i]["status"]==4) $status = "foraging";
					else if ($pplCurHere[$i]["status"]==5) $status = "resting";
					else $status = "spending time here";
					$selected = "";
					if ($i == 0) $selected = "checked='checked'";
					$ochar = new Character($mysqli, $pplCurHere[$i]["charid"]);
					$desc = $ochar->getAgeSex();
					$theirTime = new Time($mysqli, $pplCurHere[$i]["startDateTime"], $pplCurHere[$i]["startMinute"]);
					$endTime = new Time($mysqli, $pplCurHere[$i]["endDateTime"], $pplCurHere[$i]["endMinute"]);
					echo "<p>";
					$curChar->printNameLink($ochar->uid);
					echo " (" . $desc . ") local: (" . $pplCurHere[$i]["lx"] . "," . $pplCurHere[$i]["ly"] . ") " . $theirTime->getDateTime() . " - " . $endTime->getDateTime() . " " . $status;
					echo "</p>";
				}
				echo "</div>";
			}
			else para("No people are currently here");
			
			$pplWasHere = $curChar->getCrossingTimelines();
			if ($pplWasHere) {
				ptag("h2", "People who were here in the past");
				for ($i=0; $i<count($pplWasHere); $i++) {
					$ochar = new Character($mysqli, $pplWasHere[$i][0]);
					$ochar->getBasicData();
					$desc = $ochar->getAgeSex();
					$timetag = new Time ($mysqli, $pplWasHere[$i][2], $pplWasHere[$i][3]);
					echo "<p>";
					if ($pplWasHere[$i][0]>0) {
						$curChar->printNameLink($ochar->uid);
						echo " (" . $desc . ") ";
					}
					echo $pplWasHere[$i][1];
					if ($pplWasHere[$i][2]==0) echo ".";
					else echo ", at " . $timetag->getDateTime();
					echo "</p>";
				}
			}
			else para("No people have been here while you were here.");
			
			echo "</div>\n";
		}
		if ($tab==6) {
			echo "<div class='row'>\n";
			ptag("h1", "Character profile");
			echo "</div>\n";
		}
		if ($tab==7) {
			echo "<div class='row'>\n";
			
			ptag("h1", "Memorized resource spots");
			para("You can memorize up to 100 spots. If you exceed this, you will start forgetting the old ones. You can always choose to forget a spot, but you can only visit them if you're in the same location.");
			$pruned = $curChar->pruneLocMemory();
			if ($pruned>0) para($pruned . " locations were forgotten about since you last viewed this list.");
			$here = $curChar->getMemorizedList(true);
			$elsewhere = $curChar->getMemorizedList(false);
			$i=0;
			
			echo "<form action='index.php?page=forgetResource' method='post' class='form-horizontal'>";
			if ($here==-1) para("There are no memorized spots in this location.");
			else {
				ptag("h2", "In this location");
				for ($i=0;$i<count($here);$i++) {
					$num = $i + 1;
					$vlink = "index.php?page=forage2&charid=" . $charcheck . "&userid=" . $currentUser . "&source=" . $here[$i]["uid"];
					echo "<div class='form-group'>";
					echo "<div class='col-sm-2'>";
					echo "<input type='radio' name='spot' value='". $here[$i]["uid"] ."' id='rad-$i' />";
					echo "</div>";
					ptag("label", $num . ". " . $here[$i]["name"] . " - <a href='$vlink' class='clist'>[Visit]</a>", "for='rad-$i' class='col-lg-10'");
					echo "</div>";
				}
			}
			if ($elsewhere==-2) para("There are no memorized spots in other locations.");
			else {
				ptag("h2", "In other locations");
				for ($j=0;$j<count($elsewhere);$j++) {
					$lname = $curChar->getLocationName($elsewhere[$j]["x"], $elsewhere[$j]["y"]);
					$link = "index.php?page=formLocName&charid=$charcheck&userid=$currentUser&x=" . $elsewhere[$j]["x"] . "&y=" . $elsewhere[$j]["y"];
					$num2 = $i+$j+1;
					echo "<div class='form-group'>";
					echo "<div class='col-sm-2'>";
					echo "<input type='radio' name='spot' value='". $elsewhere[$j]["uid"] ."' id='rad2-$j' /> ";
					echo "</div>";
					ptag("label", $num2 . ". " .  $elsewhere[$j]["name"] . " - <a href='$link' class='clist'>" . $lname["name"] . "</a>", "for='rad2-$j' class='control-albel col-lg-10'");
					echo "</div>";
				}
			}
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Forget selected'");
			echo "</p></form>";
			echo "</div>\n";
		}
		
		if ($tab==8) {
			ptag("h1", "Manufacturing options");
	echo "<div class='row'>";		
?>
<div id="tree" class="aciTree">
</div>
<script>
	$(function() {

	    // listen for the events before we init the tree
	    $('#tree').on('acitree', function(event, api, item, eventName, options) {
		// do some stuff on init
		if (eventName == 'init') {

		    // get the first item
		    var firstItem = api.first();

		    // then select it
		    api.select(firstItem, {
			success: function(item, options) {
			    var itemId = this.getId(item);
			},
			fail: function(item, options) {
			    alert('failed to select the requested item')
			}
		    });

		}
	    });

	    // init the tree
	    $('#tree').aciTree({
		ajax: {
		    url: 'list_caller.php?branch='
		},
		selectable: true
	    });

	});
	</script>
<?php
		echo "<div id='displayInfo' class='infobox'>";
		para("Select something");
		echo "</div>\n</div>\n";
		}
		if ($tab==9) {
			ptag("h1", "Recent travels");
			$link = $gameRoot . "/show_travel.php?charid=" .$charcheck. "&userid=$currentUser";
			
			echo "<div class='row'>";
			echo "<img src='$link' alt='Automatically generated image'>";
			echo "</div>";
		}
		
		if ($tab==10) {
			
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);//to do: what if the char is in a building?
			$localCheck = $localMap->checkIfExists();
			
			if ($localCheck == -1) para("You are in water. Hopefully you're in a boat.");//in the future this will actually check if you're in a boat
			else if ($localCheck == -2) {
				para("This location hasn't been unlocked. Go to Environment to unlock it and access this page.");
			}
			else {
				$localMap->loadcreate();
				
				if (isset($_GET["focusx"])&&isset($_GET["focusy"])) {
					if (is_numeric($_GET["focusx"])&&is_numeric($_GET["focusy"])) {
						$fx = min(1000,max(0,$_GET["focusx"]));
						$fy = min(1000,max(0,$_GET["focusy"]));
					}
					else {
						$fx = $lx;
						$fy = $ly;
					}
				}
				else {
					$fx = $lx;
					$fy = $ly;
				}
				if (isset($_GET["added"])) {
						if ($_GET["added"]==1) para($_GET["added"] . " square was added to a field successfully.");
						else para($_GET["added"] . " squares were added to a field successfully.");
				}
				if (isset($_GET["moved"])) {
						if ($_GET["moved"]==1) para($_GET["moved"] . " square was moved to selected field successfully.");
						else para($_GET["moved"] . " squares were moved to selected field successfully.");
				}
				echo "<form method='get' action='index.php'>";
				echo "<div class='localmap'>";
				$localMap->printLocal_sm($fx, $fy, 10);
				echo "</div>";
				ptag("h3", "Legend:");
				para("Blue dotted border: Suitable for dwelling or farming");
				para("Green dashed border: Suitable for farming, too moist for dwelling");
				para("Yellow dashed border: Suitable for dwelling, too dry for farming");
				para("Red dotted border: Convertable to useful land with a little effort");
				para("Red solid border: Useless land, or so forested that it would take a lot to clear out.");
				para("Raindrop corner: Red - parched, orange - dry, yellow - humid, light green - moist, turquoise - swampy, blue - water");
				para("Upper right corner: Tree level, 0 to 3");
				para("Lower left corner: Bush level, 0 to 3");
				para("Lower right corner: Rocky level, 0 to 3");
				para("Background color: Soil type");
				
				$fields = $localMap->getFields();
				ptag("h3", "Fields:");
				
				ptag("p", "The purpose of fields is to allow processing several squares at the same time. Technically not all squares in a field need to have the same crop or be in the same stage, but if you for example choose to plough a field, it only works for squares that don't already have something growing in them. This protects your squares from accidents. You can only plant one crop at a time but if you start with one seed and don't have enough, then try again with another seed, it won't override the ones that were already seeded. Also if you move squares over from another field, it doesn't affect their status. But for simplicity's sake, you probably want the squares inside a field to be as similar as possible.", "class='longtext'");
				if (!$fields) {
					para("There are no local fields yet. Would you like to create one?");
				}
				else {
					
					para("If you want to add or reassign squares then you need to select them above.");
					foreach ($fields as $field) {
						$fi = new FieldArea($mysqli, $localMap->globalx, $localMap->globaly, $field["hex"], $field["uid"]);
						$squares = $fi->getIncludedSquares();
						if (!$squares) $num = "0";
						else $num = sizeof($squares);
						if (isset($_GET["field"])) {
							if (is_numeric($_GET["field"])&&$_GET["field"]==$field["uid"]) $checked = "checked='checked'";
							else $checked = "";
						}
						else $checked = "";
						para("<input type='radio' name='fieldsel' id='" . $field["uid"] . "' value='" . $field["uid"] . "' $checked>Field #" . $field["uid"] . ", $num square(s), color: <span style='color:" . $field["hex"] . ";'>" . $field["hex"] . "</span>");
					}
					ptag("h4", "Action");
					para("<input type='radio' name='act' value='1' checked='checked'> Add selected squares (excludes already claimed squares)");
					para("<input type='radio' name='act' value='2'> Move squares from other fields into selected (doesn't add squares that are not part of anything)");
					para("<input type='radio' name='act' value='3'> Plough/Sow/Harvest etc. (must have at least one square)");
					para("<input type='radio' name='act' value='4'> Change the border color");
					para("<input type='checkbox' name='detail' value='1'> Select this if you want a list of which squares were successfully processed and which (if any) were rejected.");
					echo "<p class='right'>";
					ptag("input", "", "type='submit' value='Carry out selected action'");
					echo "</p>\n";
				}
				ptag("input", "", "type='hidden' name='charid' value='$charcheck'");
				ptag("input", "", "type='hidden' name='page' value='fieldAction'");
				
				echo "</form>";
				para("<a href='index.php?page=createField&charid=$charcheck' class='clist'>[Create new field]</a>");
			}
			
		}
		if ($tab==11) {
			if (isset($_GET['message'])) {
				if ($_GET['message']=='1') para("Settings saved.");
				else if ($_GET['message']=='2') para("You joined a group.");
				else if ($_GET['message']=='3') para("You booted a person successfully.");
				else if ($_GET['message']=='4') para("placeholder");
			}
			if (isset($_GET['errormessage'])) {
				if ($_GET['errormessage']=='1') para("You couldn't find any groups at this time.");
				else if ($_GET['errormessage']=='2') para("You tried to view details of a group without defining which one.");
				else if ($_GET['errormessage']=='3') para("You don't have enough AP.");
				else if ($_GET['errormessage']=='4') para("This is not a valid group ID.");
				else if ($_GET['errormessage']=='5') para("You tried to view details of a group that's in another location.");
			}
			ptag("h1", "Companion request");
			para("You can request one companion at a time. If someone chooses to fill your request, they appear at your current location, even if you have moved since you made the initial request. You can delete or edit your companion request at any time. Once a request has been filled, you can request a second one straight away.");
			$areq = $curChar->getActiveRequest();
			if (is_array($areq)) {
				para("You already have an active request.");
				para("<a href='index.php?page=formRequest&charid=$charcheck' class='clist'>[Update or delete your companion request]</a>");
			}
			else para("<a href='index.php?page=formRequest&charid=$charcheck' class='clist'>[Request a companion]</a>");
			
			$tg = $curChar->getTravelGroup();
			ptag("h1", "Travel groups");
			if ($curChar->building>0) {
				$po = new Obj($mysqli, $curChar->building);
				if ($po->type==7) {
					para("You are currently inside a group");
					para("<a href='index.php?page=leavegroup&charid=$charcheck' class='normal'>[Leave group]</a>");
				}
			}
			$other = $curChar->getOtherTravelGroups();
			if ($other==-1) {
				para("There are no other significant characters in this location. (This doesn't count people who are already inside your group.)");
			}
			else if ($other==-2) {
				para("None of the other people in this location have travel groups.");
			}
			else {
				ptag("h2", "Other travel groups here");
				foreach ($other as $ogid) {
					$og = new Obj($mysqli, $ogid);
					$ob = new Obj($mysqli, $og->parent);
					$ocharid = $ob->getCharid();
					$ochar = new Character($mysqli, $ocharid);
					echo "<form action='index.php?page=joingroup' method='post' class='narrow'>";
					echo "<p>";
					$curChar->printNameLink($ochar->uid);
					echo " (" . $ochar->getAgeSex() . ") 's travel group";
					echo "</p>";
					ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
					ptag("input" , "", "type='hidden' name='ocharid' value='$ocharid'");
					$rule = $og->getGroupRule("join");
					if ($rule==0) {
						para("Status: Not accepting members");
					}
					else if ($rule==1) {
						para("Status: Everybody is free to join");
						echo "<p class='right'>";
						ptag("input", "", "type='submit' value='Join'");
						echo "</p>";
					}
					else {
						para("Status: Invitation only");
						$invitation = $curChar->getCharRule($ogid, 2);
						if ($invitation==1) {
							para("You are invited");
							echo "<p class='right'>";
							ptag("input", "", "type='submit' value='Join'");
							echo "</p>";
						}
						else para("You are not invited");
					}
					echo "</form>";
				}
				para("Travel groups are only meant for traveling, so you should exit whenever you're planning to do something other than travel.");
			}
			if ($tg == -1) {
				
				echo "<form action='index.php?page=addtravelgroup' method='post' class='narrow'>";
				para("You don't have your own travel group yet. Click the button to create one.");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Start a travel group'");
				echo "</p>";
				echo "</form>";
			}
			else {
				$go = new Obj($mysqli, $tg);
				$command = $go->getGroupRule("command");
				$join = $go->getGroupRule("join");
				if ($command<1) {
					$sel4 = "checked='checked'";
					$sel5 = "";
					$sel6 = "";
				}
				else if ($command==1) {
					$sel4 = "";
					$sel5 = "checked='checked'";
					$sel6 = "";
				}
				else {
					$sel4 = "";
					$sel5 = "";
					$sel6 = "checked='checked'";
				}
				if ($join<1) {
					$sel1 = "checked='checked'";
					$sel2 = "";
					$sel3 = "";
				}
				else if ($join==1) {
					$sel1 = "";
					$sel2 = "checked='checked'";
					$sel3 = "";
				}
				else {
					$sel1 = "";
					$sel2 = "";
					$sel3 = "checked='checked'";
				}
				ptag("h2", "Your travel group");
				echo "<form action='index.php?page=edittravelgroup' method='post' class='narrow'>";
				para("You can alter the settings for your travel group here. By default, no one can join, so if you want your group to be functional, you should change that.");
				ptag("h2", "Who can join?");
				echo "<p>";
				ptag("input", "", "type='radio' name='join' id='join0' value=0 $sel1");
				ptag("label", "Nobody", "for='join0'");
				echo "<br />";
				ptag("input", "", "type='radio' name='join' id='join1' value=1 $sel2");
				ptag("label", "Everybody", "for='join1'");
				echo "<br />";
				ptag("input", "", "type='radio' name='join' id='join2' value=2 $sel3");
				ptag("label", "Invited people only", "for='join2'");
				echo "</p>";
				para("<a href='index.php?page=editgrouprules&charid=$charcheck&rule=2' class='normal'>[Edit invites]</a>");
				ptag("h2", "Who is authorized to move the group?");
				echo "<p>";
				ptag("input", "", "type='radio' name='command' id='command0' value=0 $sel4");
				ptag("label", "Just me", "for='command0'");
				echo "<br />";
				ptag("input", "", "type='radio' name='command' id='command1' value=1 $sel5");
				ptag("label", "Everybody", "for='command1'");
				echo "<br />";
				ptag("input", "", "type='radio' name='command' id='command2' value=2 $sel6");
				ptag("label", "Selected people only", "for='command2'");
				echo "</p>";
				para("<a href='index.php?page=editgrouprules&charid=$charcheck&rule=1' class='normal'>[Edit commanders]</a>");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Save changes'");
				echo "</p>";
				$pas = $go->getPassengers();
				if ($pas==-1) para("There currently aren't any others in the group besides you");
				else {
					ptag("h2", "Group members");
					foreach ($pas as $p) {
						$o = new Obj($mysqli, $p);
						$id = $o->getCharid();
						$ochar = new Character($mysqli, $id);
						echo "<p>";
						$curChar->printNameLink($ochar->uid);
						echo " (" . $ochar->getAgeSex() . ")";
						echo "<a href='index.php?page=boot&charid=$charcheck&ocharid=$ochar->uid' class='normal'> [Boot]</a>";
						echo "</p>";
					}
				}
				para("You as the group starter are allowed to boot people from the group. However if they have trusted you to lead them and you leave them in the middle of nowhere, that wouldn't be nice.");
				echo "</form>";
			}
			
			
			$localMap = new LocalMap($mysqli, $currentLocation->x, $currentLocation->y);//to do: what if the char is in a building?
			$localCheck = $localMap->checkIfExists();
			
			if ($localCheck == -1) para("You are in water. Hopefully you're in a boat.");//in the future this will actually check if you're in a boat
			else {
				ptag("h1", "Local groups");
				echo "<div class='alert alert-info'>\n";
				ptag("h3", "Notice:", "");
				para("This feature is under construction. You can still play with it for the parts that have been implemented.");
				echo "</div>";
				$localMap->listGroups($charcheck);
				
				para("<a href='index.php?page=searchGroup&charid=$charcheck' class='clist'>[Search for groups]</a> (50 AP)");
			}
		}
		if ($tab==12) {
			echo "<div class='row'>\n";
			ptag("h1", "Travel log");
			if (!$curChar->analyzeTravels()) para("Your travel log is empty");//Otherwise it just prints the table
			echo "</div>";
		}
		echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		echo "</div>\n";
	}
	
}
?>

<script>
	function highlight(e) {
		e.style.borderColor= 'red';
	}
	
	function divMouseout(e) {
		e.style.borderColor = 'black';
	}
	
	function processClick(e) {
		document.getElementById("selfield").value = e.id;
		document.forms["moveform"].submit();
	}
	
    	function invClick(num) {
		document.getElementById("sel_action").value = num;
		document.forms["invform"].submit();
    	}
    	
    	function groundClick(num) {
		document.getElementById("sel_action2").value = num;
		document.forms["groundform"].submit();
    	}
    	
    	function fixedClick(num) {
		document.getElementById("sel_action3").value = num;
		document.forms["fixedform"].submit();
    	}
    	
    	function selectManu(sel_id) {
	  var xhttp;
	  if (sel_id=='0') { 
	    document.getElementById("displayInfo").innerHTML = "Nothing selected";
	    return;
	  }
	  xhttp = new XMLHttpRequest();
	  xhttp.onreadystatechange = function() {
	    if (this.readyState == 4 && this.status == 200) {
	      document.getElementById("displayInfo").innerHTML = this.responseText;
	    }
	  };
	  var charid = getUrlVars()["charid"];
	  var userid = getUrlVars()["userid"];
	  xhttp.open("GET", "view_manu.php?sel_item="+sel_id+"&charid="+charid+"&userid="+userid, true);
	  xhttp.send();   
	}
	
	function getUrlVars() {
	var vars = {};
	var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
	vars[key] = value;
	});
	return vars;
	}
</script>
