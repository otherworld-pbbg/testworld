<?php
//Delete your request


include_once "class_character.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	if (!isset($_POST["charid"])) {
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
			else {
				$areq = $curChar->getActiveRequest();
				if (is_array($areq)) {
					$form_uid = $areq["uid"];
					$exists = true;
				}
				else {
					$form_uid = 0;
					$exists = false;
				}
				
				if ($exists) {
					$res = $curChar->deleteRequest($form_uid);
					if ($res<0) {
						include_once "header2.inc.php";
						if ($res == -1) para("Request deletion failed.");
					}
					else header('Location: index.php?page=viewchar&userid=' . $currentUser. '&charid=' . $charcheck . '&tab=11');
				}
				else para("You don't have an active request to delete. You cannot delete requests that have already been filled.");
				
				echo "<p class='right'>";
				ptag("a", "Return to groups", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
				echo "</p>";
			}
		}
		else header('Location: index.php?page=direwolf&userid=' . $currentUser);
	}
}
?>
