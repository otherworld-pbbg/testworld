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
	
	para("There is a bug in the fire system so that when a fire is first started, the container it is in is initially 0 degrees Celsius instead of the temperature of the environment. However, it quickly heats up, so you might notice for 20 minutes or so that a fire pit looks frozen when it's actually heating up. I'm not sure yet how to address this.");
	
	para("It's possible to accidentally set fire to a lot of things in your inventory. This is intended as a feature and not a bug. Basically if you notice that things in your inventory are marked as on fire, quickly put them down or at least inside a container before the fire spreads. It's possible to put out fires with water, but you would need 7 times the weight of the burning material and if a big pile ignites, chance is you don't have that much water handy. Also only the first pile it encounters will be affected, so if there are several, the rest will remain burning.");
	
	para("Currently even if water puts out one burning material, if there are other burning materials in the same container (your inventory counts as a container), it's possible that the items will keep passing the fire back and forth because moisture levels aren't yet recorded.");
	
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
