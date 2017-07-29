<?php
//this needs the following post variables: direction, charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once "class_time.inc.php";
include_once "class_global_map.inc.php";
include_once("class_tag.inc.php");
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
if (!isset($_POST["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_POST['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$pos = $curChar->getPosition();
	$watcherRole = $curChar->checkPermission($currentUser);//to do: group travel
	
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
				para("You cannot travel on someone else's behalf when you're a watcher.");
			}
			else {
				if (!isset($_POST["direction"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
				}
				else {
					if (!is_numeric($_POST["direction"])) {
						include_once "header2.inc.php";
						para("The direction should be a number.");
					}
					else if ($_POST["direction"]<1||$_POST["direction"]>8) {
						header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=1');
					}
					else {
						$dir="undefined";
						
						if ($_POST["direction"]=='1') $dir="n";
						else if ($_POST["direction"]=='2') $dir="ne";
						else if ($_POST["direction"]=='3') $dir="e";
						else if ($_POST["direction"]=='4') $dir="se";
						else if ($_POST["direction"]=='5') $dir="s";
						else if ($_POST["direction"]=='6') $dir="sw";
						else if ($_POST["direction"]=='7') $dir="w";
						else if ($_POST["direction"]=='8') $dir="nw";
						
						
						if ($dir=="undefined") {
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=1');
						}
						if (!isset($_POST["leg"])) {
							header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
						}
						else if ($_POST["leg"]<1||$_POST["leg"]>10||!is_numeric($_POST["leg"])) {
						}
						else {
							$currentLocation = new GlobalMap($mysqli, $pos->x, $pos->y);
							$okay = true;
							if ($curChar->building>0) {
								//at this point it can only be a travel group, but later it can be a building too
								$tg = new Obj($mysqli, $curChar->building);
								$rule = $tg->getGroupRule("command");
								if ($rule<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=8');
								else if ($rule==2) {
									$authorization = $curChar->getCharRule($curChar->building, 1);
									if ($authorization<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=8');
								}
								$obody = new Obj($mysqli, $tg->parent);
								$ocharid = $obody->getCharid();
								if ($ocharid<1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=7');
								$ochar = new Character($mysqli, $ocharid);
							}
							$success = 0;
							for ($i=1;$i<$_POST["leg"]+1;$i++) {
								$multiplier = $currentLocation->getTravelMultiplier($dir, false);
								
								if ($multiplier<0) {
									$okay = false;
									$error = 2;
									break;//This is in case it fails to redirect for some reason
								}
								$ap = round($multiplier*15);
								
								if ($curChar->building>0) $travelCheck = $ochar->travel($dir, $ap);
								else $travelCheck = $curChar->travel($dir, $ap);
								
								if ($travelCheck==-2) {
									$okay = false;
									$error = 3;
									break;
								}
								else if ($travelCheck==-1) {
									$okay = false;
									$error = 4;
									break;
								}
								else if ($travelCheck==-3) {
									$okay = false;
									$error = 6;
									break;
								}
								else if ($travelCheck==-4) {
									$okay = false;
									$error = 7;
									break;
								}
								else {
									//if you haven't been redirected by now
									$success++;
									if ($curChar->building>0) {
										$currentLocation->x=$ochar->x;
										$currentLocation->y=$ochar->y;
									}
									else {
										$currentLocation->x=$curChar->x;
										$currentLocation->y=$curChar->y;
									}
								}
							}
							
							if ($success>=1) {
								$dc = new Tag($mysqli, "DCNAME", 1, array("ID" => $curChar->uid));
								$di = new Tag($mysqli, "DIR", 1, array("VAL" => $_POST["direction"]));
								if ($curChar->building>0) {
									$dc2 = new Tag($mysqli, "DCNAME", 2, array("ID" => $ochar->uid));
									$pascount = $ochar->countOtherInTG();
									if ($pascount == 1) $etype = 101;//with another
									else $etype = 103;//with another's party
									
									$witnesses = $ochar->allIdsInTG();
								}
								else {
									$witnesses = $curChar->allIdsInTG();
									$pascount = $curChar->countOtherInTG();
									if ($pascount == 1) {
										$result = $curChar->otherIdsInTG();
										if (is_array($result)) {
											$dc2 = new Tag($mysqli, "DCNAME", 2, array("ID" => $result[0]));
											$etype = 101;//with one
										}
										else $etype = 100;//alone
									}
									else if ($pascount > 1) {
										$pr = new Tag($mysqli, "PRONOUN", 1, array("VAL" => $curChar->getPronoun()));
										$etype = 102;//with her group
									}
									else $etype = 100;
								}
								if (!is_array($witnesses)) $witnesses = array($curChar->uid);//if there is no travel group or there are errors in the travel group then the only witness is who triggered traveling
								$e = new Event($mysqli, $curChar->uid);
								if ($success>1) {
									$co = new Tag($mysqli, "COUNT", 1, array("VAL" => $success));
									$etype += 10;
									if ($etype == 110) $e->create($etype, array($dc, $di, $co));
									if ($etype == 111||$etype==113) $e->create($etype, array($dc, $di, $dc2, $co));
									if ($etype == 112) $e->create($etype, array($dc, $di, $pr, $co));
								}
								else {
									if ($etype == 100) $e->create($etype, array($dc, $di));
									if ($etype == 101||$etype==103) $e->create($etype, array($dc, $di, $dc2));
									if ($etype == 102) $e->create($etype, array($dc, $di, $pr));
								}
								
								$e->addWitness($witnesses);
							}
							
							
							if ($okay == true) {
								header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3');
							}
							else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=3&errormessage=' . $error);
						}
					}
				}
				
			}
		}
	}
}
?>
