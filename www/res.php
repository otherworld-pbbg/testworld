<?php
include("../_private/abbr.inc.php");
include("../_private/generic.inc.php");

function selRandFromArray($array) {
	return $array[rand(0,sizeof($array)-1)];
}

function selWeighedFromArray($array) {
	$last = $array[sizeof($array)-1];
	$rand = rand(0, $last[0]);
	
	foreach ($array as $entry) {
		if ($rand<$entry[0]) return $entry[1];
	}
	return $last[1];
}

function medicine() {
	$arr = array(
	"cures headaches",
	"alleviates toothache",
	"soothes a sore throat",
	"helps you sleep",
	"diuretic",
	"narcotic",
	"helps an upset stomach",
	"laxative",
	"heals nausea",
	"reduces anxiety",
	"alleviates a cough",
	"helps expell mucus from the airways",
	"lowers a fever",
	"heals gingivitis",
	"reduces halitosis",
	"antibacterial",
	"helps wounds heal faster",
	"aborts an unwanted pregnancy"
	);
	return $arr[rand(0, sizeof($arr)-1)];
}

function decipherPotency($p) {
	if ($p<20) return "weak ($p)";
	elseif ($p<60) return "semi-potent ($p)";
	elseif ($p<80) return "potent ($p)";
	else return "very potent ($p)";
}

function decipherFlavor($f) {
	if ($f<20) return "bad ($f)";
	elseif ($f<60) return "palatable ($f)";
	elseif ($f<80) return "tasty ($f)";
	else return "delicious ($f)";
}

function decipherColor($genes) {
	arsort($genes);
	
	foreach ($genes as $key => $value) {
		$newarray[] = array("color" => $key, "value" => $value);
	}
	
	if ($newarray[0]["value"]==0) return "pale green";
	
	if ($newarray[0]["color"]=="black") {
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="blue"&&$newarray[2]["value"]>0) return "dark purple";
			return "dark red";
		}
		if ($newarray[1]["color"]=="blue"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="red"&&$newarray[2]["value"]>0) return "dark violet";
			return "blue-black";
		}
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "dark brown";
		if ($newarray[0]["value"]<3) return "dark purple";
		return "black";
	}
	else if ($newarray[0]["color"]=="blue") {
		if ($newarray[1]["color"]=="black"&&$newarray[1]["value"]>0) return "blue-black";
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="white"&&$newarray[2]["value"]>0) return "light violet";
			return "blue-violet";
		}
		if ($newarray[1]["color"]=="white"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="red"&&$newarray[2]["value"]>0) return "light purple";
			return "light blue";
		}
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "dark brown";
		if ($newarray[0]["value"]<3) return "light blue";
		return "blue";
	}
	else if ($newarray[0]["color"]=="red") {
		if ($newarray[1]["color"]=="black"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="blue"&&$newarray[2]["value"]>0) return "deep purple";
			return "deep red";
		}
		if ($newarray[1]["color"]=="white"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="yellow"&&$newarray[2]["value"]>0) return "light pink coral";
			return "pink";
		}
		if ($newarray[1]["color"]=="yellow"&&$newarray[1]["value"]>0) return "red-orange";
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "reddish brown";
		if ($newarray[0]["value"]<3) return "pink";
		return "red";
	}
	else if ($newarray[0]["color"]=="yellow") {
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="white"&&$newarray[2]["value"]>0) return "light yellow coral";
			return "orange or blushed yellow";
		}
		if ($newarray[1]["color"]=="blue"&&$newarray[1]["value"]>0) {
			return "apple green";
		}
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "yellowish brown";
		if ($newarray[0]["value"]<3) return "light yellow";
		return "yellow";
	}
	else if ($newarray[0]["color"]=="green") {
		if ($newarray[1]["color"]=="black"&&$newarray[1]["value"]>0) return "dark green";
		if ($newarray[1]["color"]=="white"&&$newarray[1]["value"]>0) return "dusty green";
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) return "reddish green";
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "olive green";
		if ($newarray[0]["value"]>7) return "deep green";
		if ($newarray[0]["value"]>4) return "medium green";
		return "green";
	}
	
	else if ($newarray[0]["color"]=="white") {
		if ($newarray[1]["color"]=="black"&&$newarray[1]["value"]>0) return "dusty purple";
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="yellow"&&$newarray[2]["value"]>0) return "light pink coral";
			return "pale pink";
		}
		if ($newarray[1]["color"]=="green") return "light green";
		if ($newarray[2]["color"]=="yellow"&&$newarray[1]["value"]>0) {
			if ($newarray[2]["color"]=="red"&&$newarray[2]["value"]>0) return "light peach";
			return "light yellow";
		}
		if ($newarray[2]["color"]=="blue") return "light blue";
		if ($newarray[1]["color"]=="brown"&&$newarray[1]["value"]>0) return "light brown";
		if ($newarray[0]["value"]<3) return "pale green";
		return "white";
	}
	else if ($newarray[0]["color"]=="brown") {
		if ($newarray[1]["color"]=="black"&&$newarray[1]["value"]>0) return "dark brown";
		if ($newarray[1]["color"]=="red"&&$newarray[1]["value"]>0) return "reddish brown";
		if ($newarray[1]["color"]=="green") return "olive green";
		if ($newarray[2]["color"]=="yellow"&&$newarray[1]["value"]>0) return "yellowish brown";
		if ($newarray[2]["color"]=="blue") return "dark brown";
		if ($newarray[0]["value"]<3) return "light brown";
		return "brown";
	}
	
	return "pale green";
}

function generatePlantResource() {
	$genes["red"] = 0;
	$genes["black"] = 0;
	$genes["yellow"] = 0;
	$genes["blue"] = 0;
	$genes["white"] = 0;
	$genes["green"] = 0;
	$genes["brown"] = 0;
	
	$use = "";
	$category2 = "";
	$potency = 0;
	$flavor = 0;
	
	$rand1 = rand(0,99);//main category
	$rand2 = rand(0,999);//sub category
	
	$cutoffs = array(
		42,//edible
		67,//wood
		83,//manufacturing
		100//decorative
		);
	
	if ($rand1 < $cutoffs[0]) {
		$category = "edible";
	}
	elseif ($rand1 < $cutoffs[1]) {
		$category = "wood";
	}
	elseif ($rand1 < $cutoffs[2]) {
		$category = "manufacturing";
	}
	else {
		$category = "decorative";
	}
	
	switch ($category) {
	case "edible":
		$cutoffs2 = array(
			183,//herb,
			347,//berry,
			475,//fruit tree,
			548,//mushroom,
			621,//taproot,
			675,//cucurbit,
			719,//nut tree,
			763,//nightshade,
			800,//bulb,
			837,//brassica,
			874,//grain,
			910,//leafy,
			939,//banana/plantain,
			954,//stalk,
			968,//rhizome,
			979,//brown algae
			989,//seeds,
			1000//multiple fruit (pineapple)
			);
		
		if ($rand2 < $cutoffs2[0]) {
			$category2 = "herb";
			
			$genes["green"] = rand(0,10);
			
			$uses = array(
				"seasoning",
				"medicine"
				);
			
			$use = selRandFromArray($uses);
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$types = array(
				"basil",
				"fenugreek",
				"chervil",
				"chives",
				"cilantro",
				"dill",
				"lemon balm",
				"a type of mint",
				"marjoram",
				"oregano",
				"a type of parsley",
				"rosemary",
				"tarragon",
				"thyme"
				);
			
			$type = selRandFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[1]) {
			$category2 = "berry";
			
			$genes["red"] = rand(0,10);
			$genes["black"] = rand(0,5);
			$genes["yellow"] = rand(0,4);
			$genes["blue"] = rand(0,3);
			$genes["white"] = rand(0,2);
			$genes["green"] = rand(0,1);
			
			$genes["density"] = rand(0,10);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$grows = array(
				array(5, "a herbaceous plant"),
				array(15, "a vine"),
				array(70, "a shrub"),
				array(100, "a bush")
				);
			$types = array(
				array(60, "true berry"),
				array(80, "aggregate fruit"),
				array(95, "pome"),
				array(100, "aggregate accessory fruit")
				);
			
			$grows_on = selWeighedFromArray($grows);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[2]) {
			$category2 = "fruit";
			
			$genes["red"] = rand(0,10);
			$genes["black"] = rand(0,4);
			$genes["yellow"] = rand(0,10);
			$genes["blue"] = rand(0,2);
			$genes["white"] = rand(0,2);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$grows_on = "a tree";
			$grains = array(
				array(35, "irregular"),
				array(70, "straight"),
				array(90, "wavy"),
				array(95, "spiral"),
				array(100, "interlocked")
				);
			$tree_grain = selWeighedFromArray($grains);
			
			$types = array(
				array(10, "true berry"),
				array(40, "drupe"),
				array(70, "hesperidium"),
				array(95, "pome"),
				array(100, "multiple fruit")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[3]) {
			$category2 = "mushroom";
			
			$genes["red"] = rand(0,8);
			$genes["yellow"] = rand(0,10);
			$genes["blue"] = rand(0,1);
			$genes["white"] = rand(0,3);
			$genes["brown"] = rand(0,10);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$uses = array(
				array(40, "edible"),
				array(55, "dye"),
				array(65, "antibiotic"),
				array(80, "mind-altering"),
				array(100, "toxic")
				);
			$use = selWeighedFromArray($uses);
			
			$types = array(
				array(50, "convex"),
				array(65, "sunken"),
				array(75, "knobbed"),
				array(85, "flat"),
				array(90, "cylindrical"),
				array(94, "conical"),
				array(97, "bell-shaped"),
				array(100, "horse's hoof shaped")
				);
			$type = selWeighedFromArray($types);
			
			if ($type == "horse's hoof shaped") $use = "tinder";
		}
		elseif ($rand2 < $cutoffs2[4]) {
			$category2 = "taproot";
			
			$genes["red"] = rand(0,10);
			$genes["black"] = rand(0,2);
			$genes["yellow"] = rand(0,10);
			$genes["blue"] = rand(0,1);
			$genes["white"] = rand(0,4);
			$genes["brown"] = rand(0,3);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$types = array(
				array(20, "multi-fingered"),
				array(45, "conical"),
				array(60, "fusiform"),
				array(75, "tuberous"),
				array(100, "napiform")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[5]) {
			$category2 = "cucurbit";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,6);
			$genes["black"] = rand(0,3);
			$genes["yellow"] = rand(0,10);
			$genes["green"] = rand(0,6);
			$genes["white"] = rand(0,3);
			$genes["brown"] = rand(0,3);
			
			$genes["density"] = rand(0,10);
			
			$grows_on = "a vine";
			
			$types = array(
				array(20, "melon"),
				array(50, "gourd"),
				array(65, "pumpkin"),
				array(85, "squash"),
				array(100, "cucumber")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[6]) {
			$category2 = "nut";
			
			$genes["brown"] = rand(0,10);
			$genes["white"] = rand(0,3);
			
			$types = array(
				array(25, "chestnut"),
				array(45, "walnut"),
				array(75, "hazel"),
				array(85, "pecan"),
				array(89, "pistachio"),
				array(91, "brazil nut"),
				array(96, "almond"),
				array(100, "cashew")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[7]) {
			$category2 = "vegetable of nightshade family";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$use = "edible";
			
			$genes["red"] = rand(0,10);
			$genes["black"] = rand(0,3);
			$genes["yellow"] = rand(0,10);
			$genes["blue"] = rand(0,3);
			$genes["white"] = rand(0,4);
			$genes["green"] = rand(0,4);
			
			$types = array(
				array(30, "tomato"),
				array(55, "potato"),
				array(65, "eggplant"),
				array(80, "sweet pepper"),
				array(100, "chili pepper")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[8]) {
			$category2 = "bulb";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,7);
			$genes["black"] = rand(0,1);
			$genes["yellow"] = rand(0,10);
			$genes["white"] = rand(0,4);
			
			$use = "edible";
			
			$types = array(
				array(45, "onion"),
				array(75, "garlic"),
				array(85, "leek"),
				array(100, "fennel")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[9]) {
			$category2 = "brassica";
			
			$genes["red"] = rand(0,3);
			$genes["green"] = rand(0,10);
			$genes["white"] = rand(0,4);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$types = array(
				array(25, "enlarged flowerhead"),
				array(45, "enlarged buds"),
				array(75, "multiple leaves"),
				array(85, "more seeds"),
				array(100, "enlarged root")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[10]) {
			$category2 = "grain";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,3);
			$genes["yellow"] = rand(0,6);
			$genes["white"] = rand(0,4);
			$genes["brown"] = rand(0,7);
			
			$types = array(
				array(25, "rice"),
				array(40, "wheat"),
				array(50, "rye"),
				array(65, "oats"),
				array(80, "barley"),
				array(90, "sorghum"),
				array(100, "millet")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[11]) {
			$category2 = "leafy vegetable";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,3);
			$genes["green"] = rand(0,10);
			
			$types = array(
				array(15, "smooth, oval leaves"),
				array(22, "leaves with undulate edges"),
				array(35, "leaves with serrate edges"),
				array(45, "veiny leaves"),
				array(65, "wrinkly leaves"),
				array(80, "curly leaves"),
				array(100, "leaves with lobate edges")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[12]) {
			$category2 = "banana";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,5);
			$genes["yellow"] = rand(0,10);
			
			$types = array(
				array(20, "short banana"),
				array(45, "relatively straight banana"),
				array(70, "curved banana")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[13]) {
			$category2 = "stalk";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,10);
			$genes["green"] = rand(0,10);
			
			$types = array(
				array(2, "celery"),
				array(4, "chard"),
				array(6, "rhubarb")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[14]) {
			$category2 = "rhizome";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$use = "seasoning";
			
			$genes["yellow"] = rand(0,10);
			$genes["white"] = rand(0,10);
			$genes["green"] = rand(0,4);
			
			$types = array(
				array(2, "bumpy"),
				array(4, "full of holes")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[15]) {
			$category2 = "algae";
			
			$genes["yellow"] = rand(0,10);
			$genes["red"] = rand(0,10);
			$genes["green"] = rand(0,10);
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$types = array(
				array(2, "kelp"),
				array(4, "wakame"),
				array(6, "nori")
				);
			$type = selWeighedFromArray($types);
		}
		elseif ($rand2 < $cutoffs2[16]) {
			$category2 = "seeds";
			
			$potency = rand(1,100);
			$flavor = rand(1,100);
			
			$genes["red"] = rand(0,10);
			$genes["yellow"] = rand(0,10);
			$genes["blue"] = rand(0,1);
			$genes["white"] = rand(0,5);
			
			$types = array(
				array(2, "large flower"),
				array(4, "small flower"),
				array(6, "tiny inflorescense")
				);
			$type = selWeighedFromArray($types);
		}
		else {
			$category2 = "multiple fruit";
			
			$flavor = rand(1,100);
			
			$types = array(
				array(2, "fruit covered in diamond shaped scales"),
				array(4, "fruit covered in large bumps"),
				array(7, "fruit covered in tiny bumps")
				);
			$type = selWeighedFromArray($types);
		}
		break;
	case "wood":
		//for building
		$grains = array(
			array(35, "irregular"),
			array(70, "straight"),
			array(90, "wavy"),
			array(95, "spiral"),
			array(100, "interlocked")
			);
		$tree_grain = selWeighedFromArray($grains);
		
		$use = "construction";
		
		$types = array(
			array(20, "palm tree"),
			array(30, "mangrove"),
			array(50, "pine"),
			array(60, "fir"),
			array(70, "spruce"),
			array(85, "birch"),
			array(95, "alder"),
			array(115, "beech"),
			array(140, "oak"),
			array(160, "acacia"),
			array(170, "hickory"),
			array(178, "teak"),
			array(185, "basswood"),
			array(200, "maple"),
			array(210, "ash"),
			array(220, "poplar"),
			array(235, "willow"),
			);
		$type = selWeighedFromArray($types);
		break;
	case "manufacturing":
		//weaving, storage, coverage, insulation
		
		$types = array(
			array(20, "weavable fronds"),
			array(50, "pliable twigs"),
			array(60, "insulating moss")
			);
		$type = selWeighedFromArray($types);
		
		break;
	default:
		//decorative
		$genes["red"] = rand(0,10);
		$genes["black"] = rand(0,1);
		$genes["yellow"] = rand(0,10);
		$genes["blue"] = rand(0,4);
		$genes["white"] = rand(0,10);
		$genes["green"] = rand(0,1);
		
		$category2 = "flower";
			
		$types = array(
				array(15, "flowering bush"),
				array(24, "cactus"),
				array(35, "aroid"),
				array(48, "orchid"),
				array(67, "rose"),
				array(74, "lily"),
				array(84, "tulip"),
				array(94, "crocus"),
				array(100, "hyacinth"),
				array(110, "iris"),
				array(125, "pansy"),
				array(140, "daisy"),
				array(145, "thistle"),
				array(153, "bell flower"),
				array(167, "buttercup")
				);
			$type = selWeighedFromArray($types);
	}
	
	$color = decipherColor($genes);
	
	if ($category =="edible"||$category=="decorative") echo "a(n) " . $color ." " . $category2 . "<br>";
	echo "Type: " . $type;
	echo "<br>Category: " . $category;
	if ($use) echo "<br>Use: " . $use;
	if ($use == "medicine") echo " (" . medicine() . ")";
	if ($potency>0) echo "<br>Potency: " . decipherPotency($potency); 
	if ($flavor>0) echo "<br>Flavor: " . decipherFlavor($flavor);
	if (isset($grows_on)) echo "<br>Grows on $grows_on";
	if (isset($tree_grain)) echo "<br>Tree grain: $tree_grain";
}

if (isset($_GET["num"])) {
	$num = setBint($_GET["num"], 1, 10, 1);
}
else $num = 1;
?>
<html>
<head>
<title>Resource generator</title>
</head>
<body>
<?php
echo "<form action='res.php' method='get' name='numform' id='numform'>";
ptag("label", "Amount to generate:", "for='num'");
echo "<select form='numform' name='num' id='num'>";
for ($i = 1; $i<=10; $i++) {
	if ($i == $num) $s = " selected='selected'";
	else $s = "";
	echo "<option value='$i'$s>$i</option>";
}
ptag("input", "", "type='submit' value='submit'");
echo "</select>";
echo "</form>";


for ($i = 0; $i<$num; $i++) {
	echo "<p>";
	generatePlantResource();
	echo "</p>";
}
?>
</body>
</html>