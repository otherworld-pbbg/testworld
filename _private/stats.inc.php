<?php
//Character list

include_once "class_statistics.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	include_once "header2.inc.php";
	echo "<div class='bar'>";
	ptag ("h1", "Statistics");
	
	para("This information is provided for your entertainment. Technically it's not against the rules to share coordinates OOCly and find out where people are, so if you want to visit locations where others have already been, go ahead. This information won't be as visible in the final product.");
	
	$st = new Statistics($mysqli);
	
	ptag ("h2", "Busiest characters");
	$st->printBusy();
	
	ptag ("h2", "Locations visited by most distinct characters");
	$st->printVisited();
	
	echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
