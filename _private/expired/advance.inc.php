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
	
	$curTime = new Time ($mysqli, 1010100, 0);
	$check = $curTime->loadGameTime();
	
	if ($currentUser==1) {
		//in the future the right to view this page will be read from the database, but for now, it's static
		if (!isset($_GET["year"])||!isset($_GET["month"])||!isset($_GET["day"])) header('Location: index.php?page=admin&userid=' .$currentUser);
		if (!is_numeric($_GET["year"])||!is_numeric($_GET["month"])||!is_numeric($_GET["day"])) header('Location: index.php?page=admin&userid=' .$currentUser);
		if ($check==-1) {
			include_once "header2.inc.php";
			para("Loading current time failed.");
			echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
		}
		else {
			$year = $_GET["year"];
			$month = $_GET["month"];
			$day = $_GET["day"];
			$result = $curTime->setGameTime($year, $month, $day);
			if ($result==-1) {
				include_once "header2.inc.php";
				para("Updating game time failed.");
				echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
			}
			else {
				$errors = $curTime->advanceCharacters();
				if ($errors==-1) {
					include_once "header2.inc.php";
					para("There seems to be no characters to advance.");
					echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
				}
				else if ($errors>0) {
					include_once "header2.inc.php";
					para("There were $errors errors.");
					echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
				}
				else header('Location: index.php?page=admin&userid=' .$currentUser . '&success=1');
			}
		}
	}
	else {
		include_once "header2.inc.php";
		para("You shouldn't be here.");
	}
}
?>
