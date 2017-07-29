<?
//this needs the following post variables: charid, userid

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_fuel_project_type.inc.php");

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
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot take things on someone else's behalf when you're a watcher.");
			}
			else if (!isset($_GET["container"])||!isset($_GET["ptype"])) {
				include_once "header2.inc.php";
				para("Error: Nothing was selected.");
			}
			else if (!is_numeric($_GET["container"])||!is_numeric($_GET["ptype"])) {
				include_once "header2.inc.php";
				para("Error: Value is not numeric.");
			}
			else {
				include_once "header2.inc.php";
				echo "<div class='bar'>\n";
				$fireplace = new Obj($mysqli, $_GET["container"]);
				$fireplace->getBasicData();
				if ($fireplace->x==$pos->x&&$fireplace->y==$pos->y&&$fireplace->localx==$pos->lx&&$fireplace->localy==$pos->ly&&$fireplace->parent==0) {
					//to do: handle buildings
					$ftype = new FuelProjectType($mysqli, $_GET["ptype"]);
					$ftype->getInfo();
					if ($ftype->machine==$fireplace->preset) {
						echo "<form method='get' action='index.php' class='narrow'>";
						para("You need a fire bow in order to start a fire. There also needs to be sufficient tinder, kindling and something that burns for a long time.");
						ptag("input", "", "type='hidden' name='page' value='startFire2'");
						ptag("input", "", "type='hidden' name='charid' value='$charcheck'");
						ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
						ptag("input", "", "type='hidden' name='ptype' value='".$_GET["ptype"] ."'");
						ptag("input", "", "type='hidden' name='container' value='".$_GET["container"] ."'");
						echo "<p class='right'>\n";
						ptag("input", "", "type='submit' value='Attempt to start a fire ($ftype->ap AP)'");
						echo "</p>";
						echo "</form>";
					}
					else para("You're trying to start a fire with a different fireplace it is associated with.");
				}
				else para("This fireplace isn't in the same location as your character.");
				echo "</div>";
			}
		}
	}
}
