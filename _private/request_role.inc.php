<?php
//Request create or update


include_once "class_character.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	if (!isset($_POST["charid"])||!isset($_POST["sex"])||!isset($_POST["startage"])||!isset($_POST["namesel"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
	}
	
	else {
		$charcheck = $mysqli->real_escape_string($_POST['charid']);
		$curChar = new Character($mysqli, $charcheck);
		$watcherRole = $curChar->checkPermission($currentUser);
		
		if ($watcherRole==1) {
			//user is authorized to play this character
			$bodyId = $curChar->getBasicData();
			if ($bodyId == -1) {
				include_once "header2.inc.php";
				echo "This character doesn't have a body so it cannot be played.";
				echo "<p class='right'>";
				para("<a href='index.php?page=clist&userid=$currentUser' class='clist'>[Return to Character List]</a>");
			}
			else if (!is_numeric($_POST["sex"])||!is_numeric($_POST["startage"])||!is_numeric($_POST["namesel"])) {
				include_once "header2.inc.php";
				para("A value should be numeric but isn't. Aborting.");
				echo "<p class='right'>";
				para("<a href='index.php?page=formRequest&charid=$charcheck' class='clist'>[Return to previous page]</a>");
			}
			else {
				if ($_POST["sex"]>4||$_POST["sex"]<1) $sex = 4;
				else $sex = round($_POST["sex"]);//This eliminates possible decimal insertion
				if ($_POST["startage"]>13) $age = 26;
				else if ($_POST["startage"]<1) $age = 14;
				else $age = round($_POST["startage"])+13;
				
				if ($_POST["namesel"]==0) $namesel = false;
				else $namesel = true;
				
				if (isset($_POST["name_txt"])) $name = $mysqli->real_escape_string($_POST["name_txt"]);
				else {
					$name = "";
					$namesel = true;
				}
				
				if (isset($_POST["desc"])) $desc = $mysqli->real_escape_string($_POST["desc"]);
				else {
					$desc = "";
				}
				if (isset($_POST["req"])) $req = $mysqli->real_escape_string($_POST["req"]);
				else {
					$req = "";
				}
				if (isset($_POST["why"])) $why = $mysqli->real_escape_string($_POST["why"]);
				else {
					$why = "";
				}
				
				$areq = $curChar->getActiveRequest();
				if (is_array($areq)) {
					$form_uid = $areq["uid"];
					$exists = true;
				}
				else {
					$form_uid = 0;
					$exists = false;
				}
				
				if ($exists) $res = $curChar->updateRequest($form_uid, $sex, $age, $namesel, $name, $desc, $req, $why);
				else $res = $curChar->requestRole($sex, $age, $namesel, $name, $desc, $req, $why);
				if ($res<0) {
					include_once "header2.inc.php";
					if ($res == -1) para("Request creation failed.");
					if ($res == -2) para("Updating request failed.");
				}
				else header('Location: index.php?page=viewchar&userid=' . $currentUser. '&charid=' . $charcheck . '&tab=11');
				
				echo "<p class='right'>";
				ptag("a", "Return to groups", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
				echo "</p>";
			}
		}
		else header('Location: index.php?page=direwolf&userid=' . $currentUser);
	}
}
?>
