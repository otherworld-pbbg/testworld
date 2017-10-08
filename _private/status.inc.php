<?php
include_once "header.inc.php";
echo "<div class='bar'>";
ptag("h1", "Update history and future plans");
ptag("h2", "Implemented so far");
?>
<ol>
<li> login</li>
<li> character list</li>
<li> reading image maps to get resources and terrain types</li>
<li> global moving</li>
<li> spending AP, logging flexitime - flexitime has since become obsolete</li>
<li> local map and local moving</li>
<li> resting</li>
<li> adding tools and machines into presets and assigning attributes such as weight</li>
<li> objects list (includes inventory at the top of the page)</li>
<li> drop, pick up</li>
<li> assigning which resources are edible</li>
<li> stomach system/eating mechanic</li>
<li> seeing things inside containers</li>
<li> go-to function that allows approaching items even in unexplored locations</li>
<li> storing things in containers</li>
<li> taking things out of containers</li>
<li> dynamic naming of people; changing your own name</li>
<li> showing age and sex</li>
<li> people list</li>
<li> defining a person's location on the timeline</li>
<li> give</li>
<li> change charloctime to bunch up actions that have the same type and AP cost, such as multitraveling</li>
<li> manufacturing system/entering items into the database - a lot of this was later rewritten</li>
<li> mechanic to turn resources into other resources by investing AP</li>
<li> fire projects</li>
<li> character creation (assumes NPC parents)</li>
<li> defining which animals can appear on which region</li>
<li> tool pools (new idea)</li>
<li> (at this stage, first testers were accepted)</li>
<li> ability to cut down trees</li>
<li> ability to make planks</li>
<li> animal stats (test island)</li>
<li> preventing you from getting more than a month ahead of other players - later obsolete</li>
<li> manually switching to a new month when most testers have reached the end of the previous month - later obsolete</li>
<li> hunting system (tracking animals, combat with animals)</li>
<li> extracting resources from kills</li>
<li> change projects, requiring fire in order to transmutate objects (takes time and no AP)</li>
<li> rewriting the resources system to use tags</li>
<li> most items made of one resource show their material, while some with two or more show the material in slot 1</li>
<li> using fire for projects that are not automatic</li>
<li> disallowing multiple trees per square on savannas and deserts / adjusting the way trees are generated</li>
<li> redesign of minimap graphics</li>
<li> timezones, expression of morning/day/night</li>
<li> seasons</li>
<li> weather system part 1: temperature</li>
<li> hiding projects that still use the old resource system instead of tags</li>
<li> weather system part 2: rain - changes on the hour and is the same for everybody at the same time in the same location</li>
<li> ability to process skins into rawhide</li>
<li> converting from individual timelines to time being the same for everybody</li>
<li> bug fix to how fire powered change projects work. Currently this only affects roasting cocoa pods</li>
<li> a delay mechanic for projects, keeping them from being finished immediately. Currently it's only used for drying clay, but be prepared for more</li>
<li> a bit that swaps out the css style sheet if it's night</li>
<li> now using sessions instead of cookies</li>
<li> added a guide for testers</li>
<li> some pretty major changes to how local maps are generated. Now water attracts water and plants are clustered more.</li>
<li> A change how soil type is calculated. Players can't yet view it but it required resetting explored locations, so everything needs to be unlocked from scratch.</li>
<li> farming system (at least in rudimentary form)</li>
<li> recording when people log in</li>
<li> groups system: basic mechanic for gaining respect</li>
<li> groups system: donate items to group in order to gain respect</li>
<li> groups system: claim items from group stock with the expense of respect</li>
<li> a change that connects resources of the same origin</li>
<li> several background changes to how local maps are displayed. The threshold for showing rocks has been lowered, so be prepared to see more rocks.</li>
<li> gathering system update</li>
<li> yet another change to how local maps are generated. Now rocks are more likely even in areas where they are very rare</li>
<li> travel log that shows the vegetation, rock and water levels of the places you passed through, even if you just went through it without stopping</li>
<li> added the possibility to record a memo for each character</li>
<li> travel groups: creation, edit rules, invite, join, exit</li>
<li> travel groups: referencing coordinates of the group instead of the character inside. This took almost three hours and involved updating 37 files.</li>
<li> travel groups: actual travel + what to do with unrelated actions</li>
<li> travel groups: view members</li>
<li> travel groups: kick group member</li>
<li> fix to adding resources to projects with multiple possibilities, also fixed the display bug that made an added resource show up for all resource strings</li>
<li> made certain resource subtypes depend on seasons. If the values are found too restrictive, resources will be made available more widely. Flowers and sap are only found during spring, plural things (generally vegetables) are available through the summer, seeds, nuts, fruit and berries are there during the fall, and leaves are available outside of winter</li>
<li> got rid of countable resources because the system was clumsy and not even used everywhere. Later removed all mention of resource pieces in projects</li>
<li> minor css changes</li>
<li> ability to request companions and fill requests</li>
<li> background changes to fix elements that weren't properly object oriented</li>
<li> background changes: combined ten almost identical functions into one</li>
<li> first functional cronjob (automatic resting)</li>
<li> manual resting disabled</li>
<li> event log: background functionality, travel logging</li>
<li> fixed bug that prevented animals from attacking back</li>
<li> register new account</li>
<li> <h3>End of stage 1: ready to accept more testers</h3></li>
<li> ability to reset password (requires a functional email address on file)</li>
<li> ability to change email address associated with your account (requires knowing the password)</li>
<li> drop multiple</li>
<li> aging system (switching body preset and increasing weight/blood when certain thresholds are passed)</li>
<li> natural healing (requires cron)</li>
<li> The old fire system has been deimplemented. As a result, there is currently no way to start a fire. Hopefully I can come up with a new solution soon.</li>
<li> The new way to start fires was implemented. The thing is currently you have to ignite your tinder or kindling in your hand, then put it in the fire pit or whatever before the temperature cron script is run the next time, or things in your inventory will start igniting. This is actually pretty hilarious, so I think I'm going to keep this.</li>
<li> Switching to bootstrap for front end.</li>
<li> Ability to affect NPC group moral compass through holding a speech.</li>
<li> You can now use a RTF editor when entering comments, so you don't have to enter html for line breaks or other stuff like that.</li>
</ol>
<?php
ptag("img", "", "src='". getGameRoot() . "/graphics/resource_presets.png' alt='mind map of new way to handle resources'");
ptag("h2", "Things I'm currently working on:");
?>
<ul>
</ul>
<?php
ptag("h2", "Things collaborators are working on:");
?>
<ul>
<li> wounds system rewrite</li>
<li> first aid, stitching of wounds, bandages</li>
</ul>                                                              
<?php
ptag("h2", "Things that are on hold:");
?>
<ul>
<li> weapons/hunting system rewrite</li>
<li> groups system: new hunting system where damage and kills are actually recorded</li>
<li> event log continues</li>
<li> brain tanning / more generally: change projects that have other requirements than fire</li>
</ul>
<?php


ptag("h2", "Things that need to be worked on in the future approximately in this order:");
?>
<ol>
<li> groups system: foraging into group stock</li>
<li> making planted seeds grow into plants (requires cron) (requires growth rates and temperature preferences)</li>
<li> limiting how much AP can be spent in an hour / character tiredness</li>
<li> the ability to ford rivers</li>
<li> crossbreeding system (to create new crops)</li>
<li> using tools to speed up resource collection</li>
<li> routines to automate repeated tasks</li>
<li> expiry of perishable objects/resources</li>
<li> genetics for passing hair, skin and eye color to your offspring</li>
<li> mating and pregnancy, possibility of taking over child characters</li>
<li> foods/cooking/nutritional values</li>
<li> hunger and digestion (digestion and daily cooking should be automatic)</li>
<li> more complex cooking system</li>
<li> buildings</li>
<li> locks and lockpicking</li>
<li> PvP combat system</li>
<li> surrender mechanic, movement restrictions for prisoners of war</li>
<li> sailing system (initially with one ship type)
</li>
<li> <h3>End of stage 2: the game will now accept all testers</h3>
</li>
<li> preventing people from leaving their comfort zones to create safe spaces for people who want to avoid newbies</li>
<li> animal stats (tropical and temperate regions)</li>
<li> clothing</li>
<li> armor</li>
<li> making certain objects increase AP gains through resting</li>
<li> following/tracking system (can this be done anymore?)</li>
<li> the ability to assign activities to other people or cooperate on foraging/farming/hunting</li>
<li> burden slows down traveling speed, individual capacity is bound to strength</li>
<li> ability to share the load with someone else by using stretchers etc.</li>
<li> animal domestication</li>
<li> possibility to cut stone blocks of a selected size</li>
<li> means to transport heavy things (such as using rollers)</li>
<li> animal attacks
</li>
<li> <h3>End of stage 3: Cold region will be unlocked</h3></li>
<li> animal stats (cold region)</li>
<li> skills affecting what you can manufacture and its quality</li>
<li> riding and carts</li>
<li> using NPCs in combat</li>
<li> introducing local OOC codes of conduct</li>
<li> signal fires/ability to see smoke from a distance</li>
<li> ability to assign other characters as watchers and kick current watchers</li>
<li> make it possible to view a log of how you've spent your AP</li>
<li> adding more ship types</li>
<li> vegetable tanning
</li>
<li> <h3>End of stage 4: Deserts will become harder to survive on</h3>
</li>
<li> thirst mechanic (only used on deserts)</li>
<li> poisons and toxins </li>
<li> more flexible rivers system, allowing people to dig channels to redirect water</li>
<li> release of a new world map with more logical placement of resources based on feedback from testers</li>
</ol>

<h2>Things that aren't strictly necessary but would be nice:</h2>
<ul>
<li> a system that generates new wild fruit/berries with random traits to encourage local diversity</li>
<li> meteorite system to introduce random iron</li>
<li> chains and other movement restrictions, bondage</li>
<li> ability to swallow small items such as needles and rings; damage from swallowing sharp items</li>
<li> used capacity counter for containers, volumes of objects</li>
<li> more natural resources</li>
<li> better diversification of wood and stone types</li>
<li> throw</li>
<li> kick items</li>
<li> water-powered mills</li>
<li> mining tunnels, other underground structures, possibility of natural caves</li>
<li> ability to dive for corals and pearls</li>
<li> steam engine</li>
<li> secrets such as ancient magic portals</li>
</ul>
<p class='right'><a href='index.php' class='clist'>[Return to main page]</a></p>
</div>
