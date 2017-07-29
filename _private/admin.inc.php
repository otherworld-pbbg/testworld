<?php
//Admin panel

//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	include_once("class_time.inc.php");
	
	include_once "header2.inc.php";
	
	$curTime = new Time ($mysqli, 1010100, 0);
	$curTime->loadGameTime();
	
	if ($currentUser==1) {
		//in the future the right to view this page will be read from the database, but for now, it's static
		echo "<div class='bar'>";
		if (isset($_GET['success'])) {
			if ($_GET['success']==1) para("Time was advanced successfully.");
		}
		ptag("h1", "Admin panel");
		
		//ptag("h2", "Lock game");
		
		echo "<p class='right'><a href='index.php?page=adminlog' class='clist'>[View most recent logins]</a></p>";
		echo "<p class='right'><a href='index.php?page=addstr&userid=$currentUser' class='clist'>[Add strings to resources]</a></p>";
		echo "<p class='right'><a href='index.php?page=addstr2&userid=$currentUser' class='clist'>[Add strings to project types]</a></p>";
		
		echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		echo "</div>";
	}
	else para("You shouldn't be here.");
}
?>
