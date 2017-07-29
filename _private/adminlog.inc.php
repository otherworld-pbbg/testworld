<?php
//Add strings to resources - admin only

//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	
	if ($currentUser==1) {
		include_once "header2.inc.php";
		include_once "generic.inc.php";
		echo "<div class='bar'>";
		ptag("h1", "User activity log");
		
		if (isset($_GET["limit"])) {
			if (is_numeric($_GET["limit"])) $limit = round($_GET["limit"]);
			else $limit=25;
		}
		else $limit=25;
		
		$log = getActivityLog($mysqli, $limit);//this function is currently located in generic, later it can be changed into a class
		
		echo "<form method='get' action='index.php' class='narrow'>";
		echo "<p>Limit: ";
		ptag("input" , "", "type='number' name='limit' value='$limit'");
		echo "</p>";
		ptag("input" , "", "type='hidden' name='page' value='adminlog'");
		ptag("input" , "", "type='submit' name='submit' value='Limit rows'");
		echo "</form>";
		
		if (is_array($log)) {
			printActivityLog($mysqli, $log);
		}
		else para("Nothing to show.");
		
		echo "<p class='right'><a href='index.php?page=admin&userid=$currentUser' class='clist'>[Return to Admin panel]</a></p>";
		
		echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		echo "</div>";
	}
	else {
		include_once "header2.inc.php";
		para("You shouldn't be here.");
	}
}
?>
