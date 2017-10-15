<?php
$privateRoot = "../_private";
$gameRoot = "http://otherworld-pbbg.com";

include_once "root.inc.php";
include_once $privateRoot . "/conn.inc.php";
include_once $privateRoot . "/generic.inc.php";

$displayForm = true;

if (isset($_POST["username"])&&isset($_POST["activation"])&&isset($_POST["submit_btn"])&&isset($_POST["type2"]))
{
	if (is_numeric($_POST["type2"])) {
		$type = $_POST["type2"];
		$activation = $mysqli->real_escape_string($_POST["activation"]);
		if (isUsername($_POST["username"]))
		{
			$uname = $mysqli->real_escape_string($_POST["username"]);//this isn't really necessary now
			//because usernames don't allow special characters
			$check = checkFreeUsername($mysqli, $uname);
			if ($check==1&&$type==1) {
				include_once $privateRoot . "/header.inc.php";
				para("This username has already been activated.");
			}
			else if ($check==2&&$type==1) {
				$check2 = activateAccount($mysqli, $uname, $activation);
				if ($check2==0) {
					include_once $privateRoot . "/header.inc.php";
					para("Wrong activation code!");
				}
				else if ($check2==-1) {
					include_once $privateRoot . "/header.inc.php";
					para("The code was correct but for some reason, an account couldn't be created. Please contact administration.");
				}
				else if ($check2==-2) {
					include_once $privateRoot . "/header.inc.php";
					para("Your account was registered successfully but a ghost was left in the pending registry. Don't worry about that, though, it will be deleted later on.");
					para("Now you can log in at the <a href='index.php?page=login'>login page</a>.");
					$displayForm = false;
				}
				else {
					include_once $privateRoot . "/header.inc.php";
					para("Your account was registered successfully!");
					para("Now you can log in at the <a href='index.php?page=login'>login page</a>.");
					$displayForm = false;
				}
			}
			else if ($check==1&&$type==2) {
				$check2 = activateEmail($mysqli, $uname, $activation);
				if ($check2==0) {
					include_once $privateRoot . "/header.inc.php";
					para("Wrong activation code!");
				}
				else if ($check2==-1) {
					include_once $privateRoot . "/header.inc.php";
					para("The code was correct but for some reason, your email couldn't be changed. Please contact administration.");
				}
				else if ($check2==-2) {
					include_once $privateRoot . "/header.inc.php";
					para("Your email was changed successfully but a ghost was left in the pending registry. Don't worry about that, though, it will be deleted later on.");
					$displayForm = false;
				}
				else {
					include_once $privateRoot . "/header.inc.php";
					para("Your email was changed successfully!");
					$displayForm = false;
				}
			}
			else if ($check==1&&$type==3) {
				$pw = password_hash($_POST["password"], PASSWORD_DEFAULT);
				$check2 = resetPassword($mysqli, $uname, $activation, $pw);
				if ($check2==0) {
					include_once $privateRoot . "/header.inc.php";
					para("Wrong activation code!");
				}
				else if ($check2==-1) {
					include_once $privateRoot . "/header.inc.php";
					para("The code was correct but for some reason, the password couldn't be changed. Please contact administration.");
				}
				else if ($check2==-2) {
					include_once $privateRoot . "/header.inc.php";
					para("Your password was changed successfully but a ghost was left in the pending registry. Don't worry about that, though, it will be deleted later on.");
					para("Now you can log in with your new password at the <a href='index.php?page=login'>login page</a>.");
					$displayForm = false;
				}
				else {
					include_once $privateRoot . "/header.inc.php";
					para("Your password was changed successfully!");
					para("Now you can log in with your new password at the <a href='index.php?page=login'>login page</a>.");
					$displayForm = false;
				}
			}
			else if ($check==0) {
				include_once $privateRoot . "/header.inc.php";
				para("There is no pending account under this username. Make sure you wrote the username correctly.");
			}
			else {
				include_once $privateRoot . "/header.inc.php";
				para("Apparently you somehow managed to enter an invalid activation type. You need to select one of the radio buttons.");
			}
		}
		else {
			include_once $privateRoot . "/header.inc.php";
			para("The username was invalid! Only alphanumeric characters and the underscore are allowed. Try again.");
		}
		
	}
}
if ($displayForm)
{
	include_once $privateRoot . "/header.inc.php";
	ptag("h1", "Enter activation code");
	echo "<form action='activate.php' method='post' class='narrow'>";
	ptag("h1", "Type of activation");
	echo "<p>";
	ptag("input", "", "type='radio' id='type1' name='type2' value='1' checked='checked'");
	ptag("label", "New account", "for='type1'");
	echo "</p>\n<p>";
	ptag("input", "", "type='radio' id='type2' name='type2' value='2'");
	ptag("label", "New email", "for='type2'");
	echo "</p>\n<p>";
	ptag("input", "", "type='radio' id='type3' name='type2' value='3'");
	ptag("label", "New password", "for='type3'");
	echo "</p>\n";
	echo "<p>";
	ptag("label", "Username: ", "for='username'");
	ptag("input", "", "type='text' id='username' name='username' size=20 maxlength=20");
	echo "</p>\n<p>";
	ptag("label", "New password*: ", "for='password'");
	ptag("input", "", "type='password' id='password' name='password' size=20 maxlength=20");
	echo "</p><p>*) Leave blank if you haven't requested a password reset.</p>\n<p>";
	ptag("label", "Activation code: ", "for='email'");
	ptag("input", "", "type='text' id='activation' name='activation' size=30 maxlength=60");
	echo "</p>\n<p>";
	ptag("input", "", "type='submit' id='submit_btn' name='submit_btn' value='Activate'");
	echo "</p>";
	echo "</form>";
}

include_once $privateRoot . "/header.inc.php";
ptag("h2", "Resend activation code");
para("If you have a pending account or request and the activation code hasn't come through in a reasonable time, enter your email address below to have it resent.");
echo "<form action='index.php?page=register' method='post'  class='narrow'>";
ptag("h1", "Type of activation");
echo "<p>";
ptag("input", "", "type='radio' id='type4' name='type' value='1' checked='checked'");
ptag("label", "New account", "for='type4'");
echo "</p>\n<p>";
ptag("input", "", "type='radio' id='type5' name='type' value='2'");
ptag("label", "New email", "for='type5'");
echo "</p>\n<p>";
ptag("input", "", "type='radio' id='type6' name='type' value='3'");
ptag("label", "New password", "for='type6'");
echo "</p>\n";
ptag("label", "Email: ", "for='email'");
ptag("input", "", "type='text' id='email' name='email' size=30 maxlength=60");
echo "</p>\n<p>";
ptag("input", "", "type='submit' id='submit_btn2' name='submit_btn2' value='Send'");
echo "</p>\n";
?>
</body>
</html>
