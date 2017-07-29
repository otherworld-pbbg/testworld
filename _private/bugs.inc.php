<?php
//Character list


include_once "class_player.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	include_once "header2.inc.php";
	echo "<div class='bar'>";
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	ptag("h1", "Known bugs (and shady features)");
	
	para("The fire system is in the middle of a transition, so you still need to set the fire under the old system, but the fire will spread and get hotter via a cron script. Firewood will also be consumed if you visit the objects page because of the old system. Afterglow doesn't work in the new system yet.");
	
	para("When you are in a travel group, it shows that there is nobody else in the location and only shows one person in your map square. This is an oversight and will eventually be fixed. People aren't supposed to turn invisible when they join a travel group.");
	
	para("The list of visits on the timeline page is generally acting very wonky and will need a complete rewrite.");
	
	para("Exact grams aren't generally displayed. This is a feature rather than a bug. The reason that projects and foraging have exact grams is because I'm lazy.");
	
	para("It is very surprising that eating still works. It doesn't take the preset into account at all, so you might be able to eat some things that technically shouldn't be edible.");
	
	para("Group foraging is currently completely cosmetic. Even though it says you found a deposit, it just means you gained respect and no resources were actually generated. This will change in the future.");
	
	para("When it says in group combat that someone gets hurt, it doesn't actually record it. This will also be reworked in the future.");
	
	para("Sometimes in hunting, the name of the body part will be blank. I'm not sure which one of them is missing. This system will be completely overhauled in the future anyway, so there's probably no point in trying to find the bug.");
	
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	
	echo "</div>";
}
?>
