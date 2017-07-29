<?
include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_scene.inc.php";

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
		include_once "header2.inc.php";
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) echo "This character doesn't have a body so it cannot be played.";
		else {
			if (!isset($_GET["scene"])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=1');
			$sceneid = $mysqli->real_escape_string($_GET['scene']);
			$sceneObj = new Scene($mysqli, $sceneid);
			$sceneObj->loadValues();
			$times=$curChar->getParticipationTimes($sceneid);
			echo "<div class='displayarea'>\n";
			if ($times==-1) {
				para("You can't view this scene as you've never participated in it.");
			}
			else {
				ptag("h1", $sceneObj->title);
				para($sceneObj->desc);
				
				$internalTime = $sceneObj->getInternalTime();
				$timeObj = new Time($mysqli, $internalTime["dateTime"], $internalTime["minute"]);
				para("Current internal time: " . $timeObj->getDateTime());
				echo "<p class='right'>";
				ptag("a", "[Return to scene selection]", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=1' class='clist'");
				echo "</p>";
				echo "<div class='ppl_list'>";
				ptag("h3","Participants:");
				$participants = $sceneObj->getChars($internalTime["dateTime"], $internalTime["minute"], 1);
				if ($participants) {
					echo "<ul class='normal'>";
					for ($h=0;$h<count($participants);$h++) {
						$participant = new Character($mysqli, $participants[$h]["charID"]);
						$desc = $participant->getAgeSex();
						$actorname = $curChar->getDynamicName($participant->uid);
						
						ptag("li", "<a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $participant->uid . "' class='normal'>$actorname</a> ($desc)");
					}
					echo "</ul>";
				}
				echo "</div>";
				echo "<div class='event_list'>";
				for ($i=0;$i<count($times);$i++) {
					
					if ($times[$i]["leftt"]>0) {
						$lastRead = $sceneObj->getLastRead($charcheck, $times[$i]["rowid"]);
						$initTime = new Time($mysqli, $times[$i]["joint"], $times[$i]["joinm"]);
						$quitTime = new Time($mysqli, $times[$i]["leftt"], $times[$i]["leftm"]);
						$events = $sceneObj->getEventsSpan($times[$i]["joint"],$times[$i]["joinm"],$times[$i]["leftt"],$times[$i]["leftm"], $times[$i]["firstread"], $lastRead);
						if (!$events) para("There were no events during this time span (" . $initTime->getDateTime() . " - " . $quitTime->getDateTime() . ").");
						else {
							
							for ($j=0;$j<count($events);$j++) {
								$posttime = new Time($mysqli, $events[$j]["dateTime"], $events[$j]["minute"]);
								$actor = new Character($mysqli, $events[$j]["charID"]);
								$actorname = $curChar->getDynamicName($actor->uid);
								
								if ($events[$j]["type"]==1)
								{
									ptag("p", "(". $posttime->getDateTime() . ") " . $posttime->getDateTime() . ") <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a>: ". $events[$j]["contents"], "class='longtext'");
								}
								else if ($events[$j]["type"]==2) {
									
									para ("(" . $posttime->getDateTime() . ") ". $posttime->getDateTime() . ") <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> has joined.");
								}
								else if ($events[$j]["type"]==3) {
									
									para ("(" . $posttime->getDateTime() . ") ". $posttime->getDateTime() . ") <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> has left.");
								}
								else if ($events[$j]["type"]==4) {
									
									para ("(" . $posttime->getDateTime() . ") ". $posttime->getDateTime() . ") <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> was spotted eavesdropping!");
								}
							}
						}
					}
					else {
						$events = $sceneObj->getEventsOpenEnded($times[$i]["joint"],$times[$i]["joinm"], $times[$i]["firstread"]);
						if (!$events) para("There have been no events after you joined.");
						else {
							$lastSeen = $sceneObj->getLastID();
							$sceneObj->updateLastRead($charcheck, $lastSeen);
							for ($j=0;$j<count($events);$j++) {
								$posttime = new Time($mysqli, $events[$j]["dateTime"], $events[$j]["minute"]);
								$actor = new Character($mysqli, $events[$j]["charID"]);
								$actorname = $curChar->getDynamicName($actor->uid);
								
								if ($events[$j]["type"]==1)
								{
									ptag("p", "(". $posttime->getDateTime() . ") <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a>: " . $events[$j]["contents"], "class='longtext'");
								}
								else if ($events[$j]["type"]==2) {
									
									para ("(" . $posttime->getDateTime() . ")  <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> has joined.");
								}
								else if ($events[$j]["type"]==3) {
									
									para ("(" . $posttime->getDateTime() . ")  <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> has left.");
								}
								else if ($events[$j]["type"]==4) {
									
									para ("(" . $posttime->getDateTime() . ")  <a href='index.php?page=formCharName&charid=$charcheck&ocharid=" . $actor->uid . "' class='normal'>$actorname</a> was spotted eavesdropping!");
								}
							}
							if ($watcherRole>1) para("You cannot post since you're just a watcher.");
							else {
								echo "<form action='index.php?page=postevent' method='post'  autocomplete='off'><p>";
								ptag("input", "", "type='text' name='talkbox' size='130' autofocus");
								ptag("input" , "", "type='hidden' name=charid value='$charcheck'");
								ptag("input" , "", "type='hidden' name=userid value='$currentUser'");
								ptag("input" , "", "type='hidden' name=scene value=$sceneid");
								ptag("input", "", "type='submit' name='submit' value='Speak or emote'");
								echo "</p></form>";
							}
						}
					}
				}
				echo "</div>";
				echo "<p class='right'>";
				ptag("a", "[Return to scene selection]", "href='index.php?page=viewchar&charid=$charcheck&userid=$currentUser&tab=1' class='clist'");
				echo "</p>";
			}
			echo "</div>";
		}
	}
}
?>
