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
	ptag("h1", "Tester's guide");
	
	para("Part of being a tester is thinking outside the box and coming up with things not mentioned in this manual, but if you have trouble deciding how to start, here are some pointers.");
	
	para("When you are placed into the world, the location is random and it is very unlikely that there is anyone else there. Other people will show up on the Timeline page if there are any. I recommend that you unlock the location if it's not already unlocked. If it is unlocked, this means someone else has already been there, which means you are near other players.");
	
	echo '<div class="alert alert-warning">';
	echo "<strong>Disclaimer:</strong> Don't attack animals yet. At this stage, it's more likely they kill you than you kill them. First aid isn't yet implemented, so any wounds you get will be with you for a long time.";
	echo "</div>";
	
	para("Pay attention to the list of resources on the Environment and Activities pages. They aren't 100% the same but each resource on the Environment page should correspond to at least one on the Activities page, unless a deposit has been exhausted. Some resources have different names like 'random deciduous trees' vs 'junkwood'. If you see that there is a resource without a clear match, report it as it could be a bug.");
	
	para("It is also possible to search for hidden resources while the location is still locked, but often if sand is a hidden resource, you are more likely to find that because it has more deposits than other resources, so it can hinder your search.");
	
	ptag("h2", "How to advance on the tech tree");
	
	para("Currently only the Neolithic, Clay and Wood tech levels have been implemented, but it's also possible to make soft metal items out of gold. It's not yet possible to manufacture copper, bronze, iron or steel. I will announce when it's implemented.");
	
	para("The basic Neolithic tools are the manual grindstone and the knapping stone. Manual grindstone is made of a stone that is hard and polishable. Knapping stone is impact resistant and polishable. Generally polishable means that it has relatively small grain that isn't arranged in flat planes that would chip away. Your best bets are looking for granite and flint or chert, but if you find something else first, see if it works.");
	
	para("Once you have your base tools and lithic stone, make a stone knife. You might also want to make a stone awl.");
	
	para("Next start searching for wood. You will need this for handles and some other things. Once you have wood, make a large wooden handle, then make a stone axe. You can also make a stone hammer, but this requires an awl to make a hole in the stone.");
	
	para("Search for cotton, I can't remember if there's other twinable things on the test region. Make a hand-woven string. Next make a fire bow. Then a wooden shovel. Assuming you are in a good location, dig a fire pit. You might want to travel around to see if certain locations are more optimal than others. For example, you don't want to be far from a wood source.");
	
	ptag("h2", "Clearing vegetation");
	
	para("You can only clear away vegetation if the location has been unlocked. You need to be in a square that has vegetation. Trees take a lot of AP to fell, and you need an axe or an adze. Bushes can be cleared away with a knife as well, but it's not as efficient. If you have a sickle, you can cut grass.");
	
	para("Felled trees can be processed into planks. In the future you can make firewood too, but not yet.");
	
	ptag("h2", "How to make fire");
	
	para("Currently, you need tinder, kindling and firewood separately. For tinder, use cotton, camphor or sinew (which you shouldn't have yet if you followed my advice not to hunt). For kindling, use ferns, shrubs (mainly gained through clearing away bushes), camphor wood, thin citrus/pear/cherry branches or the most available: shavings. You get shavings every time you carve something out of wood, but you can also produce shavings by clicking use your stone knife, then turning any wood into shavings. In the future, things like bamboo will be excluded because it's not good for fires. For firewood, basically use any wood except bamboo or camphor. You can also use coal.");
	
	para("Place the cotton, shavings and firewood inside the firepit using the store button. You need to experiment to find the optimal amounts. In the beginning, it is possible that if you use too little tinder or kindling, your fire will go out before your firewood catches fire, but if you use too much, the extra will be destroyed as the fire becomes hot enough, so you don't want that either. Click the use button on the fire pit and make fire using your fire bow.");
	
	para("Look at the fire. It will say a fire has just been started, or the tinder is burning. Wait a little while and it will say the kindling is burning. Then the firewood is starting to burn. Full blaze means that in the future, it will be able to destroy certain hard to burn materials like corpses. Currently no material is assigned as hard to burn yet. Technically you can take firewood out while it's on fire, but I wouldn't recommend that.");
	
	para("While a fire is ongoing, you can work on projects that require an ongoing fire, such as making a bamboo blowpipe. If you have a bamboo blowpipe, you can make gold items in the Copper tech section using a fire pit.");
	
	para("You can also roast cocoa pods by placing them inside the firepit, then having a fire ongoing long enough for them to turn into roasted cocoa beans. The afterglow will also count, but the effects of afterglow are only assigned at one time when the glow ends, so if you remove the cocoa pods before that time, you lose the afterglow effect.");
	
	ptag("h2", "Hunting");
	
	para("Go to the Activities page and search for animals. You might not find anything every time. Currently you can only find one animal per search, but the found animals won't go away yet, so they wait patiently for you to attack them.");
	
	para("If you feel unsure, you can attack something safe like a chincilla. You can use different weapons and choose between quick kill or cripple/incapacitate. Currently I recommend quick kill because broken limbs don't yet slow down an enemy or prevent them from attacking.");
	
	para("Experiment with different weapons, but keep an eye on the messages and the teardrop graphic. If the teardrop turns so-so, you are at a risk but you might still be able to finish the fight. If the teardrop turns red, escape immediately and don't engage in another fight until healing is implemented. Bleeding is applied every round and bleeding never fades away, so any damage you get will build up. Your goal is to kill your enemy before it kills you. Some big animals like deer have more blood (hit points) than you, so they take a long time to bleed out. Once they lose enough blood, they will fall unconscious, but currently there is not a 'finish off' mechanic yet. There will be in the future.");
	
	para("Once you're brave enough, you probably want to kill a deer as it gives you antlers. However, deer have a very powerful kick, and if they hit you in the heart, you can die in one turn. However, their aim is very poor.");
	
	para("Once you have killed an animal, you need to take it apart. Go to the Items page and use the take apart button. You will need a knife. You can choose for different parts whether to discard, pick up or leave attached. You will want the brains, sinew, skin and antlers. The scapula can also be useful in making a bone shovel or bone scraper.");
	
	ptag("h2", "Processing skins");
	
	para("Once you have a skin/pelt, you can use it to make rawhide. Use your stone or bone scraper to scrape a hide and you will have rawhide.");
	
	para("You can also make preserved pelts. But for this you will need a pot to make brain tanning solution, and currently the only pot you can have access to is the baked clay pot. I will tell you more about that below.");
	
	para("If you have a pot, you will need a fire to cook brain tanning solution. Place the brains and fresh water into a project and use AP to complete. It only works if the firewood has caught fire. Tinder or kindling fire is too cold to have effect.");
	
	para("Once you have brain tanning solution, make a treated pelt by using a scraper and brain tanning solution. You will need to wait for it to take effect. The waiting time depends on the size of the pelt, but won't be multiplied if you have multiple pelts on the same project.");
	
	ptag("h2", "Clay tech");
	
	para("If you make a potter's wheel, you can make unfired clay items. They take a while to dry afterwards. To make softened clay, use a knife and a stone hammer. You can access the project through either tool.");
	
	para("The most difficult part about clay tech is acquiring the clay for the kiln. This will require a lot of clicking since clay is currently slow to gather, and you can't yet use a shovel to speed it up. It's possible you will gather all the clay in a location and will have to move on to a different one.");
	
	para("Be prepared to spend several days on gathering clay. This is very mind-numbing and you will wish that NPCs were implemented. If you lose your patience, take a break.");
	
	para("Once you have your clay, you need to gather stone as well. This is much faster in comparison.");
	
	para("Oh yeah, and you need a wooden trowel too. Or you can make a gold trowel (soft metal) but this is more complicated.");
	
	para("So finally once you have all your materials and tools, you can make a kiln. It works similar to a fire pit, but you will need to make a big fire that lasts a long time. Kilns are more efficient when it comes to afterglow, so it's most likely the afterglow, not the active fire, that will turn your unfired clay pot into baked clay pot. As mentioned above, afterglow effects are only applied when it goes out, so if you take the pot out before it's finished, you lose the effect. And besides, you shouldn't touch it anyway, it's supposed to be hot.");
	
	para("Now you can make any clay items, assuming you have enough firewood. Congratulations.");
	
	ptag("h2", "Scenes");
	
	para("If you are lucky enough to find other people, you can create a scene and if they join, you can chat. Scenes follow their own internal timeline. 12 messages is one minute. I forgot what happened if you reach the end of the scheduled duration.");
	
	ptag("h2", "Other things you can do");
	
	para("You can eat things, but digestion isn't implemented yet, so what ever you eat will stay in your stomach and take up space.");
	
	para("You can chop various fruit and such. You can make a salad out of zucchini or cucumber and ancestral tomatoes, but currently it doesn't have a nutritional value yet, so you can just look at it.");
	
	para("You can make plant bedding out of ferns. You can't take it with you, though, since it's a fixed object.");
	
	para("You can make 'organic' items out of antlers.");
	
	para("You can make a stone adze once you have acquired rawhide as binding material.");
	
	para("This topic will be updated once new things are implemented, so remember to check back every once in a while. And try to do things this doesn't cover.");
	
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	
	echo "</div>";
}
?>
