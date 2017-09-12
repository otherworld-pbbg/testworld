<?php
include_once("generic.inc.php");
$displayForm = true;

if (isset($_POST["email"])&&isset($_POST["submit_btn2"])&&isset($_POST["type"])){
	$email = $mysqli->real_escape_string($_POST["email"]);
	$type = $_POST["type"];
	
	if ($type==1) $check = mailActivation($mysqli, $email);
	if ($type==2) $check = mailEmailChange($mysqli, $email);
	if ($type==3) $check = mailPasswordReset($mysqli, $email);
	
	if ($check == 1) {
		include_once "header.inc.php";
		para("The email was sent successfully. Check your inbox (and spam folder if it's not in the former) and follow the instructions.");
		$displayForm = false;
	}
	else  {
		include_once "header.inc.php";
		para("Resending activation code failed. Make sure the email address is correct and that you even requested for an account or a change in the first place.");
	}
}

if (isset($_POST["username"])&&isset($_POST["password"])&&isset($_POST["email"])&&isset($_POST["submit_btn"]))
{
	if (isUsername($_POST["username"]))
	{
		$uname = $mysqli->real_escape_string($_POST["username"]);//this isn't really necessary now
		//because usernames don't allow special characters
		$check = checkFreeUsername($mysqli, $uname);
		if ($check==1) {
			include_once "header.inc.php";
			para("This username is already in use. Pick something else.");
		}
		else if ($check==2) {
			include_once "header.inc.php";
			para("A pending account already exists under this username. If this is yours and you don't have the activation code, have it resent. If it belongs to someone else, you need to pick some other username.");
		}
		else {
			include_once "hashing.inc.php";
			$passhash = password_hash($_POST["password"], PASSWORD_DEFAULT);
			$email = $mysqli->real_escape_string($_POST["email"]);
			$check2 = generateActivationCode($mysqli, $uname, $email, $passhash);
			if ($check2==1) {
				include_once "header.inc.php";
				para("Activation code was sent to the email you provided. Follow the instructions in the email. If the email doesn't come through, you can have it resent. It might go in the spam folder, so check there first.");
				$displayForm = false;
			}
			else if ($check2==-1) {
				include_once "header.inc.php";
				para("A pending account was generated successfully but it failed to send you an activation code. Make sure you use a valid email address.");
			}
			else {
				include_once "header.inc.php";
				para("Generating a pending account failed. Try again and make sure your email is valid.");
			}
		}
	}
	else {
		include_once "header.inc.php";
		para("The username was invalid! Only alphanumeric characters and the underscore are allowed. Try again.");
	}
}
if ($displayForm)
{
	include_once "header.inc.php";
	ptag("h1", "Register new account");
	para("Register only once. If the activation code doesn't come through, ask to have it resent. Only if it's been over 24 hours, you will have to try again.");
	echo "<form action='index.php?page=register' method='post' class='narrow'>";
	echo "<p>";
	ptag("label", "Username: ", "for='username'");
	ptag("input", "", "type='text' id='username' name='username' size=20 maxlength=20");
	echo "</p>\n<p>";
	ptag("label", "Password: ", "for='password'");
	ptag("input", "", "type='password' id='password' name='password' size=20 maxlength=32");
	echo "</p>\n<p>";
	ptag("label", "Email: ", "for='email'");
	ptag("input", "", "type='text' id='email' name='email' size=30 maxlength=60");
	echo "</p>\n<p>";
	ptag("input", "", "type='submit' id='submit_btn' name='submit_btn' value='Register'");
	echo "</p>";
	echo '<div class="alert alert-info">';
	para("Disclaimers:"); 
	para("1) Usernames shouldn't use any special characters (underscores are allowed though).");
	para("2) Passwords are case sensitive, usernames are not.");
	para("3) You are responsible for all activities that happen on your account, so if you pick an easy password and someone guesses it, you can be held responsible for anything they do on your account. It's recommended that the password is at least 7 characters and contains at least one letter and number. It's up to you if you comply or not.");
	echo "</div>";
	echo "</form>";
}

include_once $privateRoot . "/header.inc.php";
ptag("h2", "Resend activation code");
para("If you have a pending account or request and the activation code hasn't come through in a reasonable time, enter your email address below to have it resent.");
echo "<form action='index.php?page=register' method='post'  class='narrow'>";
ptag("h1", "Type of activation");
echo "<p>";
ptag("input", "", "type='radio' id='type1' name='type' value='1' checked='checked'");
ptag("label", "New account", "for='type1'");
echo "</p>\n<p>";
ptag("input", "", "type='radio' id='type2' name='type' value='2'");
ptag("label", "New email", "for='type2'");
echo "</p>\n<p>";
ptag("input", "", "type='radio' id='type3' name='type' value='3'");
ptag("label", "New password", "for='type3'");
echo "</p>\n";
ptag("label", "Email: ", "for='email'");
ptag("input", "", "type='text' id='email' name='email' size=30 maxlength=60");
echo "</p>\n<p>";
ptag("input", "", "type='submit' id='submit_btn2' name='submit_btn2' value='Send'");
echo "</p>\n";
?>
