<?//OBSOLETE
include_once "class_player.inc.php";
include_once "class_character.inc.php";

//the part that checks if you're logged in
if (!isset($_GET["userid"])) {
		header('Location: index.php?page=login');
}

$user = '';
$pass = '';

$currentUser = $mysqli->real_escape_string($_GET['userid']);
$res = $mysqli->query("SELECT username, passhash FROM users WHERE uid='$currentUser' LIMIT 1");
if (!$res) {
    para("Unknown userid.");
    exit;
}
else if ($res->num_rows == 1) {
	$row = $res->fetch_object();
	$user = $row->username;
	$pass = $row->passhash;
}


if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
    
    if (($_COOKIE['username'] != $user) || ($_COOKIE['password'] != $pass)) {    
        header('Location: index.php?page=login');
    }
} else {
    header('Location: index.php?page=login');//if you're not authenticated then you get the boot
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
	
	if ($watcherRole<1||$watcherRole>1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {
			if (!isset($_GET['giveid'])) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			else if (!is_numeric($_GET['giveid'])) {
				header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4');
			}
			else {
				$res1 = $curChar->cancelPendingGive($_GET['giveid']);
				if ($res1==1) header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=4&message=3');
				include_once "header2.inc.php";
				if ($res1==-1) para("You're in a different location so you shouldn't even have this link.");
				else if ($res1==-4) para("Failed to move stuff from your inventory to the receiver.");
				else if ($res1==-5) para("There was a duplication bug. Inform developer and don't try to replicate it.");
			}
		}
	}
}
?>
