<?php
include_once("class_character.inc.php");
include_once("class_combat_settings.inc.php");
include_once("constants.php");
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
}
//end logged in check
//Next check if character selected
if (!isset($_GET["charid"])) {
		header('Location: index.php?page=direwolf&userid=' . $currentUser);
}
else {
	//check if the player is allowed to view this character
	$charcheck = $mysqli->real_escape_string($_GET['charid']);
	$curChar = new Character($mysqli, $charcheck);
	$watcherRole = $curChar->checkPermission($currentUser);
	$bodyId = $curChar->getBasicData();
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		echo "<div class='alert alert-info'>";
		echo "<strong>Disclaimer:</strong> You are a watcher, so you can't carry out actions. You only see what the character sees.";
		echo "</div>";
	}
	
	//user is authorized to view this character
	
	if ($bodyId == -1) {
		include_once "header2.inc.php";
		displayBodywarning();
	}
	else {
		$targets = array (
			"random",
			"the fastest",
			"the slowest",
			"the most accurate",
			"the least accurate",
			"the best defender",
			"the worst defender",
			"the least wounded",
			"the most wounded",
			"the person who attacked me last"
			);
		
		$exclusions = array (
		"people with a level 1 bleed (negligible 1)",
		"people with a level 2 bleed (negligible 2)",
		"people with a level 3 bleed (minor 1)",
		"people with a level 4 bleed (minor 2)",
		"people with a level 5 bleed (medium 1)",
		"people who a level 6 bleed (medium 2)",
		"people who a level 7 bleed (serious)",
		"people who a level 8 bleed (profuse)",
		"people who a level 9 bleed (deadly with delay)",
		"people who a level 10 bleed (deadly)",
		"unconscious people",
		"people who have surrendered");
		
		include_once "header2.inc.php";
		echo "<div class='bar'>\n";
		ptag("h4", "Current character:");
		para("<a href='index.php?page=formCharName&charid=$charcheck&userid=$currentUser&ocharid=" . $charcheck . "' class='clist'>" . $curChar->cname . "</a>");
		
		ptag("h1", "Combat settings");
		$preset = isset($_GET['preset']) ? $_GET['preset'] : 0;
		
		if ($preset==0) ptag("h2", "Default:");//otherwise get name from database
		
		echo "<form name='csettings' id='csettings' action='index.php?page=saveSettings&charid=$charcheck&preset=$preset' method='post' class='narrow'>";
		echo "<ol>";
		ptag("li", "Hit action");
		
		
		ptag("input", "", "type='radio' name='hit_act' value='1' checked='checked'");
		ptag("label", "1.1. Hit");
		echo "<div name='hit' id='hit'>";
		ptag("label", "Target if multiple:");
		echo "<select name='target' form='csettings'>";
		for ($i = 0; $i<10; $i++) {
			ptag("option", $targets[$i], "value='$i'");
		}
		echo "</select>";
		echo "<p>";
		ptag("a", "Priorities [edit]", "href='index.php?page=addHitPriority' class='clist'");
		echo "</p>";
		ptag("h4", "Exclude");
		
		
		
		echo "<ul class='left_no_marker'>";
		for ($i = 0; $i<12; $i++) {
		echo "<li>";
			ptag("input", "", "type='checkbox' name='ex$i'");
			ptag("label", $exclusions[$i]);
		}
		echo "</ul>";
		echo "<p>";
		ptag("a", "Always exclude [edit]", "href='index.php?page=addHitExclusionsAlways' class='clist'");
		echo "</p>";
		echo "<p>";
		ptag("a", "Never exclude [edit]", "href='index.php?page=addHitExclusionsNever' class='clist'");
		echo "</p>";
		ptag("h4", "Body parts to exclude");
		
		$bp = array(
			"head",
			"eyes",
			"neck",
			"genitalia",
			"bottom",
			"major tendons",
			"major joints",
			"major arteries"
			);
		
		$labels = array(
			"always, no matter what",
			"if ally is hit here, you can hit back",
			"if you are hit here, you can hit back",
			"never"
			);
		
		echo "<ul>";
		
		for ($i = 0; $i<8; $i++) {
			ptag("li", $bp[$i]);
			echo "<ul class='left_sub'>";
			for ($j = 0; $j<4; $j++) {
				if ($j==0) $sel = "checked='checked'";
				else $sel = "";
				echo "<li>";
				ptag("input", "", "type='radio' name='exb$i-$j' value='$j' $sel");
				ptag("label", $labels[$j]);
			}
			echo "</ul>";
		}
		echo "</ul>";
		
		echo "<p>";
		ptag("input", "", "type='checkbox' name='def2hit'");
		ptag("label", "Sacrifice your defense to boost hit");
		echo "</p>";
		
		echo "</div>";
		
		
		ptag("input", "", "type='radio' name='hit_act' value='2'");
		ptag("label", "1.2. Convert hit to defense");
		echo "<div name='hit2def' id='hit2def'>";
		echo "</div>";
		
		ptag("input", "", "type='radio' name='hit_act' value='3'");
		ptag("label", "1.3. Convert hit to first aid");
		echo "<div name='hit2aid' id='hit2aid'>";
		para("This isn't recommended as a default because usually nobody is bleeding on the first round.");
		ptag("label", "Priority");
		echo "<select name='heal' form='csettings'>";
		ptag("option", "the least wounded", "value='1'");
		ptag("option", "the most wounded", "value='2'");
		echo "</select>";
		echo "<p>";
		ptag("a", "Priorities [edit]", "href='index.php?page=addhHealPriority' class='clist'");
		echo "</p>";
		ptag("h4", "Exclude");
		echo "<ul class='left_no_marker'>";
		for ($i = 0; $i<12; $i++) {
		echo "<li>";
			ptag("input", "", "type='checkbox' name='ex1-$i'");
			ptag("label", $exclusions[$i]);
		}
		echo "</ul>";
		echo "<p>";
		ptag("a", "Always exclude [edit]", "href='index.php?page=addHealExclusionsAlways' class='clist'");
		echo "</p>";
		echo "<p>";
		ptag("a", "Never exclude [edit]", "href='index.php?page=addHealExclusionsNever' class='clist'");
		echo "</p>";
		echo "</div>";
		
		ptag("input", "", "type='radio' name='hit_act' value='4'");
		ptag("label", "1.4. Convert hit to surrender");
		echo "<div name='hit2surrender' id='hit2surrender'>";
		para("This isn't recommended as a default action.");
		echo "</div>";
		
		ptag("li", "Block action");
		
		para("You will always automatically block hits against yourself. You can't turn this off.");
		
		para("1st - 2nd");
		ptag("input", "", "type='radio' name='block_act' value='1' checked='checked'");
		ptag("input", "", "type='radio' name='block_act2' value='1'");
		ptag("label", "2.1. Use unused blocks to defend others");
		echo "<div name='block2other' id='block2other'>";
		echo "</div>";
		
		ptag("input", "", "type='radio' name='block_act' value='2'");
		ptag("input", "", "type='radio' name='block_act2' value='2' checked='checked'");
		ptag("label", "2.2. Convert unused blocks to first aid");
		echo "<div name='block2aid' id='block2aid'>";
		echo "</div>";
		
		ptag("input", "", "type='radio' name='block_act' value='3'");
		ptag("input", "", "type='radio' name='block_act2' value='3'");
		ptag("label", "2.3. Convert unused blocks to defense on next round");
		echo "<div name='block2def' id='block2def'>";
		echo "</div>";
		echo "</ol>";
		ptag("input", "", "type='submit' name='submit' value='Save changes'");
		echo "</form>";
		echo "<p>";
		ptag("a", "[Copy]", "href='index.php?page=copySettings&charid=$charcheck&preset=$preset' class='clist'");
		ptag("a", "[Delete]", "href='index.php?page=deleteSettings&charid=$charcheck&preset=$preset' class='clist'");
		echo "</p>";
		echo "</div>";
	}
}
?>
