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
	if ($currentUser==1) {
		if (isset($_GET["ochar"])) $oChar = new Character ($mysqli, round($_GET["ochar"]));
		$player = new Player($mysqli, $currentUser);
		include_once "header2.inc.php";
		echo "<div class='bar'>";
		$oChar->killOff();
		echo "<p class='right'><a href='index.php?page=adminlog&userid=$currentUser' class='clist'>[Return to Activity log]</a></p>";
		echo "<p class='right'><a href='index.php?page=admin&userid=$currentUser' class='clist'>[Return to Admin panel]</a></p>";
	}
	else {
		include_once "header2.inc.php";
		echo "<div class='bar'>";
		para("You aren't authorized to view this page.");
	}
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
