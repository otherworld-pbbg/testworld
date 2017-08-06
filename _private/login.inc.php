<?php
include_once("generic.inc.php");

$displayForm = true;

if (isset($_POST["username"])&&isset($_POST["password"])&&isset($_POST["submit_btn"]))
{
	if (isUsername($_POST["username"]))
	{
		$uname = $mysqli->real_escape_string($_POST["username"]);//this isn't really necessary now
		//because usernames don't allow special characters
		$info = getExistingAccount($mysqli, $uname);
		if (!is_object($info)&&$info == -2) {
		    para("There was a problem with the database.");
		    exit;
		}		
		if (!is_object($info)&&$info== -1) {
		    para("Username doesn't exist. Go to the register page if you want to register a new account.");
		}
		else
		{
			include_once "hashing.inc.php";
			include_once "class_player.inc.php";	
			$pw = myHash($_POST["password"]);
			if ($info->passhash==$pw)
			{
				$_SESSION['logged_user'] = $_POST['username'];
				$_SESSION['user_id'] = $info->uid;
				$player = new Player($mysqli, $info->uid);
				$player->logLogin();
				
				header('Location: index.php?page=direwolf&userid='. $info->uid);
				$displayForm = false;
			}
			else para("Wrong password!");
		}
	}
	else {
		include_once "header.inc.php";
		para("The username was invalid! Only alphanumeric characters and the underscore are allowed.");
	}
}
if ($displayForm)
{
	include_once "header.inc.php";
	para("Notice to all users: Since I accidentally posted our hashing safeword on Github, we had to change it, which means that nobody's passwords work anymore. You will have to contact the developer for a new password. Also this way we will see which ones of the testers are still active.");
	echo "<form action='index.php?page=login' method='post' class='narrow'>";
	echo "<p>";
	ptag("label", "Username: ", "for='username'");
	ptag("input", "", "type='text' id='username' name='username' size=20 maxlength=20");
	echo "</p>\n<p>";
	
	ptag("label", "Password: ", "for='password'");
	ptag("input", "", "type='password' id='password' name='password' size=20 maxlength=32");
	echo "</p>\n<p>";
	
	ptag("input", "", "type='submit' id='submit_btn' name='submit_btn' value='Log in'");
	echo "</p>";
	echo "</form>";
}

?>