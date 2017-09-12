<?php
//Character list


include_once "class_player.inc.php";
include_once "class_character.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	$player = new Player($mysqli, $currentUser);
	
	if (isset($_POST["password"])&&isset($_POST["email"])&&isset($_POST["submit_btn"]))
	{
		if (password_verify($_POST["password"], $player->passhash2)) {
			
			$email = $mysqli->real_escape_string($_POST["email"]);
			$check2 = generateActivationCode($mysqli, $_SESSION['logged_user'], $email, $player->passhash2, 2, $player->uid);
			if ($check2==1) {
				include_once "header2.inc.php";
				para("Activation code was sent to the email you provided. Follow the instructions in the email. If the email doesn't come through, you can have it resent. It might go in the spam folder, so check there first.");
			}
			else if ($check2==-1) {
				para("A pending change was generated successfully but it failed to send you an activation code. Make sure you use a valid email address.");
			}
			else {
				para("Generating an email change failed. Try again and make sure your email is valid.");
			}
		}
		else header('Location: index.php?page=login');
	}
	
	include_once "header2.inc.php";
	echo "<div class='bar'>";
	ptag("h1", "Player settings");
	ptag("h2", "Update email");
	echo "<form action='index.php?page=settings' method='post' class='narrow'>";
	para("Current email: " . $player->email);
	echo "<p>";
	ptag("label", "New email: ", "for='email'");
	ptag("input", "", "type='text' id='email' name='email' size=30 maxlength=60");
	echo "</p>\n<p>";
	ptag("label", "Current password: ", "for='password'");
	ptag("input", "", "type='password' id='password' name='password' size=20 maxlength=32");
	echo "</p>\n<p>";
	ptag("input", "", "type='submit' id='submit_btn' name='submit_btn' value='Send activation code'");
	echo "</p>";
	para("You need to actually have access to the email address you're entering here. If it belongs to someone else, they can enter the activation code and reset the password to override it with their own, effectively taking over your account.");
	echo "</form>";
	
	para("If you want to reset your password, go to index.php?page=reset");
	
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
