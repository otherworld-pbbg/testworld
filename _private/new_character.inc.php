<?php
//Character options


include_once "class_player.inc.php";
include_once "generic.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
$currentUser = $_SESSION['user_id'];
$player = new Player($mysqli, $currentUser);
include_once "header2.inc.php";
echo "<div class='bar'>";
echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
ptag ("h1", "Start playing a new character");

para("For an explanation of the alternatives, scroll to the bottom of the page.");

ptag("h2", "The steps:");
echo "<ol>";
ptag("li", "Select zone");
ptag("li", "Select character type");
ptag("li", "Possible extra information");
echo "</ol>";

ptag("img", "", "src='" . getGameRoot() . "/graphics/zones-tropical.jpg' alt='Tropical zone' class='headline'");
para("Status: unlocked for all");
para("Recommended for beginners. Clothing optional, food is readily available through foraging.");

ptag("h3", "Options:");
ptag("h4", "Appear as an adult");
para("(Unlimited) <a href='index.php?page=createchar&userid=$currentUser&zone=1&type=1' class='clist'>[proceed]</a>");
ptag("h4", "Take over a child character");
para("Available: 0");
ptag("h4", "Fill a requested role");
$requests = getActiveRequests($mysqli);
if ($requests == -1) para("Available: 0");
else para("Available: " . sizeof($requests) . " <a href='index.php?page=createchar&userid=$currentUser&zone=1&type=3' class='clist'>[proceed]</a>");

ptag("img", "", "src='" . getGameRoot() . "/graphics/zones-temperate.jpg' alt='Temperate zone' class='headline'");
para("Status: locked");
para("For advanced players. Requires a moderate level of strategy. The winter is short but requires wearing clothes or migrating to warmer climates.");

ptag("h3", "Options:");
ptag("h4", "Appear as an adult");
para("(Locked)");
ptag("h4", "Take over a child character");
para("Available: 0");
ptag("h4", "Fill a requested role");
para("Available: 0");

ptag("img", "", "src='" . getGameRoot() . "/graphics/zones-cold.jpg' alt='Cold zone' class='headline'");
para("Status: locked");
para("For experienced players. The nature is barren of food resources during winter months, requiring you to plan ahead or risk starving to death. Clothing is necessary to fight against hypothermia during the cold season.");

ptag("h3", "Options:");
ptag("h4", "Appear as an adult");
para("(Locked)");
ptag("h4", "Take over a child character");
para("Available: 0");
ptag("h4", "Fill a requested role");
para("Available: 0");

ptag("img", "", "src='" . getGameRoot() . "/graphics/zones-desert.jpg' alt='Desert zone' class='headline'");
para("Status: locked");
para("Only for the most extreme players, including the added challenge of water management.");

ptag("h3", "Options:");
ptag("h4", "Take over a child character");
para("Available: 0");
ptag("h4", "Fill a requested role");
para("Available: 0");

echo "<div class='infobox2'>";
ptag("h2", "Description of the alternatives:");
para("When you appear as an adult, you are placed in an uninhabited location and you aren't told which way other people can be found, so if you wander in the wrong direction, a chance is you might not find other people at all.");
echo "<ul class='normal'>";
echo "<li>In the tropical zone, it is assumed that food is readily available, so you won't be given any starting equipment.</li>";
echo "<li>In the temperate zone, you will be given primitive clothing, a stone knife and a few days of food.</li>";
echo "<li>In the cold zone, you are given starting equipment according to what matches the local technology level, including some clothes.</li>";
echo "<li>The desert zone doesn't allow appearing as an adult unless filling a role, because being part of a group is essential for survival.</li>";
echo "</ul>";

para("When you take over a child character, you will have at least one PC parent to help you out.");
echo "<ul class='normal'>";
echo "<li>The child will have existed as an NPC before you took over and you can see the health and nourisment levels, so you can assess your chances of survival before making any commitment.</li>";
echo "<li>Children are more fragile than adults, so it's not recommended to wander off on your own. Help your parents any way you can, after all, they are providing for you.</li>";
echo "<li>If you feel that an available child character is otherwise promising but too young, you can wait for them to mature a bit first - but if someone else is willing to start playing younger, they might snatch the character before you're ready to start playing. So don't wait too long.</li>";
echo "<li>Child characters are expected to act their age. It might be okay to act a bit precocious, but if you're constantly doing things that are uncharacteristic of a child of this age, you can get your right to play as a child taken away, so don't abuse it.</li>";
echo "</ul>";

para("It's also possible to take over a role written by someone else. This allows appearing as an adult in an established community. You will know in advance that your character serves a purpose. Usually these characters are wanted as they fill a hole in a society, but it's also possible that somebody writes a conflicting character that will stir things up. They may even be hated by the townspeople, but behind the scenes the players will love it.");
echo "<ul class='normal'>";
echo "<li>You are expected to read the description and play the character accordingly. It's allowed and recommended to fill what ever gaps were left by the original writer, but you shouldn't write anything that goes completely against the original idea.</li>";
echo "<li>It is allowed to have the character change gradually based on life experiences, but you shouldn't pull a full 180 unless they go through something radical.</li>";
echo "</ul>";
echo "</div>";

echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
echo "</div>";
}
?>
