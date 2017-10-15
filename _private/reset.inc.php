<?php
include_once("generic.inc.php");
$displayForm = true;

if (isset($_POST["username"])&&isset($_POST["email"])&&isset($_POST["submit_btn"]))
{
	if (isUsername($_POST["username"]))
	{
		$uname = $mysqli->real_escape_string($_POST["username"]);//this isn't really necessary now
		//because usernames don't allow special characters
		
		$email = $mysqli->real_escape_string($_POST["email"]);
		
		$info = getExistingAccount($mysqli, $uname);
		if (!is_object($info)&&$info == -2) {
		    para("There was a problem with the database.");
		    exit;
		}		
		if (!is_object($info)&&$info== -1) {
		    para("Username doesn't exist. Go to the register page if you want to register a new account.");
		}
		else {
			$check = generateActivationCode($mysqli, $uname, $email, "being changed", 3, $info->uid);
			if ($check==1) {
				include_once "header.inc.php";
				para("Activation code was sent to the email you provided. Follow the instructions in the email. If the email doesn't come through, you can have it resent. It might go in the spam folder, so check there first.");
				$displayForm = false;
			}
			else if ($check==-1) {
				include_once "header.inc.php";
				para("A password change request was generated successfully but it failed to send you an activation code. Make sure you use a valid email address before requesting a password change.");
			}
			else if ($check==-2) {
				include_once "header.inc.php";
				para("You tried to send a password reset into an email address not associated with the account in question. If your account has the wrong email address, you need to change that first.");
			}
			else {
				include_once "header.inc.php";
				para("Password reset failed. Try again and make sure your email is valid.");
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
	ptag("h1", "Reset your password");
	para("You need to know both your username and the email address associated with the account, otherwise we cannot confirm that it's actually yours. You will be prompted for a new password after using the code that will be emailed to you.");
	echo "<form action='index.php?page=reset' method='post' class='narrow'>";
	echo "<p>";
	ptag("label", "Username: ", "for='username'");
	ptag("input", "", "type='text' id='username' name='username' size=20 maxlength=20");
	echo "</p>\n<p>";
	ptag("label", "Email: ", "for='email'");
	ptag("input", "", "type='text' id='email' name='email' size=30 maxlength=60");
	echo "</p>\n<p>";
	ptag("input", "", "type='submit' id='submit_btn' name='submit_btn' value='Reset'");
	echo "</p>";
	echo '<div class="alert alert-info">';
	para("Disclaimers:");
	para("1) Passwords are case sensitive, usernames are not.");
	para("2) You are responsible for all activities that happen on your account, so if you pick an easy password and someone guesses it, you can be held responsible for anything they do on your account. It's recommended that the password is at least 7 characters and contains at least one letter and number. It's up to you if you comply or not.");
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
