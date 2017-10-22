<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
//list of page handles and corresponding links will be here
$pages = array(
"addPart" => "addPart.inc.php",
"addPart2" => "addPart2.inc.php",
"addResource" => "add_resource.inc.php",
"addResource2" => "add_resource2.inc.php",
"addstr" => "addstr.inc.php",
"addstr2" => "addstr2.inc.php",
"addtravelgroup" => "addTravelGroup.inc.php",
"admin" => "admin.inc.php",
"adminlog" => "adminlog.inc.php",
"bodyparts" => "bodyparts.inc.php",
"boot" => "boot.inc.php",
"bugs" => "bugs.inc.php",
"cancelGive" => "cancel_give.inc.php",
"changeLocName" => "change_loc_name.inc.php",
"changeCharName" => "change_char_name.inc.php",
"chop" => "chop.inc.php",
"chop2" => "chop2.inc.php",
"createchar2" => "create_char2.inc.php",
"charlist" => "charlist.inc.php",
"charmemo" => "charmemo.inc.php",
"combat" => "combat.inc.php",
"combatSettings" => "combat_settings.inc.php",
"createField" => "create_field.inc.php",
"createField2" => "create_field2.inc.php",
"createchar" => "create_char.inc.php",
"createpass" => "createpass.inc.php",
"createscene" => "create_scene.inc.php",
"deleteRequest" => "delete_request.inc.php",
"direwolf" => "direwolf.php",
"dismember" => "dismember.inc.php",
"doFavor" => "favor.inc.php",
"drop" => "drop.inc.php",
"dropMulti" => "drop_multi.inc.php",
"dropMulti2" => "drop_multi2.inc.php",
"eat" => "eat.inc.php",
"editCulture" => "cul_edit.inc.php",
"editgrouprules" => "editGroupRules.inc.php",
"edittravelgroup" => "edittravelgroup.inc.php",
"emptyContainer" => "empty_container.inc.php",
"euthanize" => "euthanize.inc.php",
"explore" => "explore.inc.php",
"startFire" => "fire_starter.inc.php",
"startFire2" => "fire_starter2.inc.php",
"fieldAction" => "field_action.inc.php",
"fieldAction2" => "field_action2.inc.php",
"fixedAction" => "fixed_action.inc.php",
"flee" => "flee.inc.php",
"forage" => "forage.inc.php",
"forage2" => "forage2.inc.php",
"forgetResource" => "forget_memorized.inc.php",
"formCharName" => "form_char_name.inc.php",
"formLocName" => "form_loc_name.inc.php",
"formRequest" => "form_request.inc.php",
"gather" => "gather.inc.php",
"give" => "give.inc.php",
"goal" => "goal.inc.php",
"goto" => "goto.inc.php",
"groundAction" => "groundAction.inc.php",
"groupStock" => "groupStock.inc.php",
"guide" => "guide.inc.php",
"inventoryAction" => "inv_action.inc.php",
"joingroup" => "joingroup.inc.php",
"joinscene" => "join_scene.inc.php",
"leavegroup" => "leavegroup.inc.php",
"leavescene" => "leave_scene.inc.php",
"listCultures" => "cul_list.inc.php",
"location" => "location.inc.php",
"login" => "login.inc.php",
"lootcontainer" => "lootcontainer.inc.php",
"objectCreator" => "obj_creator.inc.php",
"manufacture" => "manufacturing.inc.php",
"movelocal" => "move_local.inc.php",
"newchar" => "new_character.inc.php",
"newCulture" => "cul_add.inc.php",
"postevent" => "postevent.inc.php",
"presetEditor" => "preset_editor.inc.php",
"register" => "register.inc.php",
"removePart" => "remove_component.inc.php",
"removeResource" => "remove_resource.inc.php",
"requestRole" => "request_role.inc.php",
"reset" => "reset.inc.php",
"resign" => "resign.inc.php",
"resign2" => "resign2.inc.php",
"round" => "round.inc.php",
"scenecreator" => "scene_creator.inc.php",
"searchAnimal" => "search_animal.inc.php",
"searchGroup" => "search_group.inc.php",
"settings" => "settings.inc.php",
"showplan" => "showplan.inc.php",
"startCombat" => "start_combat.inc.php",
"startProject" => "start_project.inc.php",
"statistics" => "stats.inc.php",
"status" => "status.inc.php",
"store" => "store.inc.php",
"store2" => "store2.inc.php",
"testcontainer" => "test_container.inc.php",
"take" => "take.inc.php",
"talk" => "talk1.inc.php",
"talk2" => "talk2.inc.php",
"travel" => "travel.inc.php",
"urldecode" => "urldecode.inc.php",
"viewchar" => "marble.inc.php",
"viewCulture" => "cul_view.inc.php",
"viewgroup" => "view_group.inc.php",
"viewPlayer" => "view_player.inc.php",
"viewProject" => "view_project.inc.php",
"viewscene" => "viewscene.inc.php",
"work" => "work.inc.php",
"work2" => "work2.inc.php"
);

$page = false;
if (isset($_GET["page"])) $page = mysqli_real_escape_string($mysqli, $_GET["page"]);
else {
	include("header.inc.php");
	
	echo "<div class='bar'>";
	ptag("img", "", "src='". getGameRoot() ."/graphics/otherworld_banner-900px.jpg' class='central'");
	ptag("h1", "Welcome to Otherworld!");
	ptag("h2", "What is Otherworld?");
	para("Otherworld is an experimental world simulator that takes the players on a trip through historical eras, starting from Neolithic times. Groups of players can start unique cultures that are first planned OOCly and continue their development inside the game world. You experience the game world through the eyes of your characters, adopting different world views based on their environment and personal history.");
	ptag("h2", "Key aspects");
	echo "<ul class='infolist'>";
	ptag("li", "We strive for realism, but if it gets on the way of player enjoyment, it will be reworked", "class='infolist'");
	ptag("li", "We strive to make every culture unique in some way", "class='infolist'");
	ptag("li", "Farming is the backbone of any larger community", "class='infolist'");
	ptag("li", "This is a multiplayer game. It's more fun to play with other players than by yourself", "class='infolist'");
	ptag("li", "There is strength in teamwork - it is better to be part of a group than a loner", "class='infolist'");
	ptag("li", "You have the right to choose who you play with - if it doesn't work out then one must leave", "class='infolist'");
	ptag("li", "Every character has a mother and a father, even in cases where their identity is undefined", "class='infolist'");
	ptag("li", "If something is boring, make an NPC do it and concentrate on something you enjoy instead", "class='infolist'");
	ptag("li", "Death is irreversible", "class='infolist'");
	ptag("li", "People are allowed to believe in reincarnation, but even if you were someone else in a past life, you shouldn't let it define who you become in this one", "class='infolist'");
	echo "</ul>";
	echo "<p>";
	ptag("a", "[List of implementations and future plans]", "href='index.php?page=status' class='clist'");
	echo "</p>";
	ptag("h3", "Detailed information");
	echo "<p>";
	echo "You can read about plans in detail ";
	ptag ("a", "here", "href='index.php?page=showplan' class='clist'");
	echo ". Note that only logged in users can comment. If you don't have an account, contact me.";
	echo "</p>";
	echo "</div>";
}

if ($page)
{
	if (isset($pages[$page]))
	{
		include_once (dirname(__FILE__) . "/" . $pages[$page]);
	}
	else
	{
		para("Unknown page \"$page\".");
	}
}

?>