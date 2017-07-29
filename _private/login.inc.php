<?php
$displayForm = true;

if (isset($_POST["username"])&&isset($_POST["password"])&&isset($_POST["submit_btn"]))
{
	function isUsername($element)
	{
		return !preg_match ("/[^A-z0-9_\-]/", $element);
	}
	if (isUsername($_POST["username"]))
	{
		$uname = $mysqli->real_escape_string($_POST["username"]);//this isn't really necessary now
		//because usernames don't allow special characters
		$res = $mysqli->query("SELECT uid, passhash FROM users WHERE username like '$uname' LIMIT 1");
		if (!$res) {
		    para("Query failed: " . $mysql->error());
		    exit;
		}		
		if ($res->num_rows == 0) {
		    para("Username doesn't exist. Registering new accounts isn't open to public yet. If you want an account, you'll need to know how to contact the developer.");
		}
		else
		{
			include_once "hashing.inc.php";
			include_once "class_player.inc.php";	
			$pw = myHash($_POST["password"]);
			$row = $res->fetch_object();
			if ($row->passhash==$pw)
			{
				$_SESSION['logged_user'] = $_POST['username'];
				$_SESSION['user_id'] = $row->uid;
				$player = new Player($mysqli, $row->uid);
				$player->logLogin();
				
				header('Location: index.php?page=direwolf&userid='. $row->uid);
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
	para("Make sure to read the main page and plans from time to time in case changes are announced.");
	echo "<form action='index.php?page=login' method='post'>";
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