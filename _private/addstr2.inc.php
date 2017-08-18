<?php
//Add strings to project types - admin only

//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	include_once("class_project_type.inc.php");
	include_once("class_resource.inc.php");
	$abstract = new ProjectType($mysqli);
	
	if ($currentUser==1) {
		include_once "header2.inc.php";
		include_once ("class_resource_string.inc.php");
		
		if (isset($_GET["pt_sel"])&&isset($_GET["str"])&&isset($_GET["slot"])&&isset($_GET["weight"])&&isset($_GET["preset"])) {
			if (!is_numeric($_GET["pt_sel"])||!is_numeric($_GET["slot"])||!is_numeric($_GET["weight"])||!is_numeric($_GET["preset"])) {
					para("Error: Something should be numeric but isn't");
			}
			else {
				$string = mysqli_real_escape_string($mysqli, $_GET['str']);
				$pid = $_GET["pt_sel"];
				$slot = $_GET["slot"];
				$weight = $_GET["weight"];
				$preset = $_GET["preset"];
				
				$current = new ProjectType($mysqli, $pid);
				if ($string=="select") para("Oops, you didn't select any strings.");
				else {
					$current->addString($slot, $string, $weight, $preset);
					para("Adding selected strings");
				}
			}
		}
		
		ptag("h2", "Add strings to project types");
		
		echo "<form action='index.php' method='get'>";
		
		ptag("input" , "", "type='hidden' name='page' value='addstr2'");
		
		$arr = $abstract->getProjectTypes();
		
		foreach ($arr as $pete) {
			$cur = new ProjectType($mysqli, $pete["pid"]);
			
			$infoArr = $cur->getResStrings();
			echo "<div class='form-group'>";
			ptag("input", "", "type='radio' value='" . $pete["pid"] . "' id='pt-" . $pete["pid"] . "' name='pt_sel'");
			ptag("label", $pete["name"] . " (" . $pete["pid"] . ")", "for='pt-" . $pete["pid"] . "'");
			if ($pete["hidden"]==1) para("HIDDEN");
			echo "</div>";
			ptag("h4", "Resources under the old system:");
			$cur->printResources();
			ptag("h4", "Tool pools:");
			$cur->printToolPools();
			if (is_array($infoArr)) {
				foreach ($infoArr as $insideArr) {
					para ($insideArr["uid"] . ": [Slot " . $insideArr["slot"] . "] Type: " . $insideArr["preset"] . " Wt: " . $insideArr["weight"] . " | " . $insideArr["str"]);
					$rst = new ResourceString($mysqli, $insideArr["str"]);
					$matches = $rst->getMatchingResTypes();
					if (!$matches) para("Currently there are no resources which match this criteria.");
					else {
						ptag("h4", "Possible alternatives");
						echo "<ul class='res'>";
						foreach ($matches as $m) {
							$resource = new Resource($mysqli, $m);
							$resource->loadData();
							ptag("li", $resource->name);
						}
						echo "</ul>";
					}
				}
				ptag("h4", "Requirements as seen in game:");
				$cur->printResStrings();
			}
			else para("No assigned strings");
			ptag("hr");
		}
		
		$pos_strs = array(
			"bakable",
			"bark",
			"boilable",
			"brick",
			"carvable",
			"choppable",
			"clay",
			"coarse",
			"combustible",
			"crushable",
			"cuttable",
			"deflect_rain",
			"dicable",
			"drillable",
			"dryable_brittle",
			"dryable_crunchy",
			"dryable_soft",
			"dryable_tough",
			"durable",
			"edible",
			"fabric",
			"fermentable",
			"fibre",
			"fine",
			"fragrant",
			"generic",
			"grain_coarse",
			"grain_fine",
			"gratable",
			"grindable",
			"hallucinogenic",
			"hard_organic",
			"heat_resistant",
			"hollow",
			"impact_resistant",
			"insulates",
			"liquid",
			"malleable",
			"mashable",
			"metal_extra_hard",
			"metal_hard",
			"metal_medium",
			"metal_soft",
			"mortar",
			"narrow",
			"neutral",
			"nonflammable",
			"oily",
			"padding",
			"palm_wood",
			"peelable_hammer",
			"peelable_knife",
			"peelable_manual",
			"pits",
			"planks",
			"plant",
			"pliable",
			"poisonous",
			"powder",
			"processed",
			"residue_dry",
			"residue_sticky",
			"residue_wet",
			"root",
			"rust-proof",
			"slicable",
			"smokable",
			"soakable",
			"soft",
			"softens_when_boiled",
			"steamable",
			"sticky",
			"stone_can_cabochon",
			"stone_can_facet",
			"stone_hard",
			"stone_lithic",
			"stone_polishable",
			"stone_soft",
			"stone_thermoresistant",
			"string",
			"tastes_bad",
			"tastes_bitter",
			"tastes_bland",
			"tastes_good",
			"tastes_salty",
			"tastes_sour",
			"tastes_spicy",
			"tastes_sweet",
			"tool_bone",
			"tool_bronze",
			"tool_copper",
			"tool_iron",
			"tool_steel",
			"tool_stone",
			"treated",
			"twig",
			"twinable",
			"unprocessed",
			"waterproof",
			"weavable",
			"whetstone",
			"wide",
			"wood",
			"wood_barrel",
			"wood_bow",
			"wood_grain_straight",
			"wood_resinous",
			"yarn"
			);
		
		$i = 0;
		foreach ($pos_strs as $string) {
			ptag("input", "", "type='button' value='$string' id='str-" . $i . "' name='str-" . $i . "' onclick='processClick(this)'");
			$i++;
		}
		
		echo "<p>Str: ";
		ptag("input", "", "type='text' value='select' name='str' id='str' size='50'");
		echo "</p>";
		
		echo "<p>Slot: ";
		ptag("input", "", "type='text' value='1' name='slot' id='slot' size='3'");
		echo "</p>";
		
		echo "<p>Preset: ";
		ptag("input", "", "type='text' value='20' name='preset' id='slot' size='4'");
		echo "</p>";
		
		echo "<p>Weight (grams): ";
		ptag("input", "", "type='text' value='0' name='weight' id='weight' size='11'");
		echo "</p>";
		
		ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
		ptag("input" , "", "type='hidden' name='page' value='addstr2'");
		echo "<p class='right'>";
		ptag("input", "", "type='submit' value='Add'");
		echo "</p>";
		echo "</form>";
		
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
<script>
	function processClick(e) {
		if (document.getElementById("str").value=="select") a = e.value;
		else a = document.getElementById("str").value + "," + e.value;
		document.getElementById("str").value = a;
	}
</script>
