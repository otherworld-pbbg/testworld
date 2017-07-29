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
				$fireplace = new Obj($mysqli, $_GET["container"]);
				$fireplace->getBasicData();
				if ($fireplace->x==$pos->x&&$fireplace->y==$pos->y&&$fireplace->localx==$pos->lx&&$fireplace->localy==$pos->ly&&$fireplace->parent==0) {
					//to do: inside building
					$ftype = new FuelProjectType($mysqli, $_GET["ptype"]);
					$ftype->getInfo();
					if ($ftype->machine==$fireplace->preset) {
						$result = $ftype->start($fireplace->uid, $charcheck);
						if ($result<0) {
							include_once "header2.inc.php";
							echo "<div class='bar'>\n";
						}
						if ($result==-1) para("You don't have a suitable tool for starting a fire.");
						else if ($result==-2) para("You don't have enough AP.");
						else if ($result==-3) para("You don't have tinder.");
						else if ($result==-4) para("Failed to ignite.");
						else if ($result==-5) para("Project creation failed for some reason.");
						else header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
					}
					else {
						include_once "header2.inc.php";
						para("You're trying to start a fire with a different fireplace it is associated with.");
					}
				}
				else {
					include_once "header2.inc.php";
					para("This fireplace isn't in the same location as your character.");
				}
				
				echo "<p class='right'>";
				ptag("a", "[Return to Items]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=4' class='clist'");
				echo "</p>";
				echo "</div>";
			}
		}
	}
}
