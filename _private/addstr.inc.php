<?php
//Add strings to resources - admin only

//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	include_once("class_resource.inc.php");
	$abstract = new Resource($mysqli);
	
	if ($currentUser==1) {
		include_once "header2.inc.php";
		
		if (isset($_GET["uids"])&&isset($_GET["str"])) {
			$strings = mysqli_real_escape_string($mysqli, $_GET['str']);
			$ids = mysqli_real_escape_string($mysqli, $_GET['uids']);
			if (isset($_GET['append'])) $append = true;
			else $append = false;
			
			if ($strings=="select") para("Oops, you didn't select any strings.");
			else if ($ids=="select") para("Oops, you didn't select any objects.");
			else {
				$abstract->addStrings($ids, $strings, $append);
				para("Adding selected strings");
			}
		}
		
		ptag("h2", "Add strings to resources");
		
		echo "<form action='index.php' class='medium' method='get'>";
		
		ptag("input" , "", "type='hidden' name='page' value='addstr'");
		
		$arr = $abstract->listResources();
		$lastCat = 0;
		
		foreach ($arr as $resource) {
			$cur = new Resource($mysqli, $resource["uid"]);
			if ($lastCat!=$resource["category"]) {
				$cn = $cur->getCategory();
				ptag("h2", "$cn");
			}
			$curStr = $cur->getStrings();
			echo "<p>";
			ptag("input", "", "type='checkbox' value='" .$resource["uid"] . "' id='res-" .$resource["uid"] . "' name='res-" .$resource["uid"] . "' onclick='processClick2(this)'");
			echo $resource["name"] . " (" . $curStr . ")</p>";
			$lastCat = $resource["category"];
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
		
		echo "<p>Uids: ";
		ptag("input", "", "type='text' value='select' name='uids' id='uids' size='50'");
		echo "</p>";
		
		echo "<p>Str: ";
		ptag("input", "", "type='text' value='select' name='str' id='str' size='50'");
		echo "</p>";
		
		ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
		ptag("input" , "", "type='hidden' name='page' value='addstr'");
		
		echo "<p class='right'>";
		ptag("input", "", "type='checkbox' value='1' name='append'");
		echo " Append, do not overwrite";
		echo "</p>";
		
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
	
	function processClick2(e) {
		if (document.getElementById("uids").value=="select") a = e.value;
		else a = document.getElementById("uids").value + "," + e.value;
		document.getElementById("uids").value = a;
	}
</script>
