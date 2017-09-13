<?php
include_once("class_obj.inc.php");
include_once("class_character.inc.php");
include_once("local_map.inc.php");
include_once("generic.inc.php");
include_once("constants.php");

class NPCgroup
{
	private $mysqli;
	public $uid;
	public $gx;
	public $gy;
	public $lx;
	public $ly;
	public $name;
	public $adult_male=0;
	public $adult_female=0;
	public $adult_neuter=0;
	public $wounded_male=0;
	public $wounded_female=0;
	public $wounded_neuter=0;
	public $sick_male=0;
	public $sick_female=0;
	public $sick_neuter=0;
	public $children=0;
	public $disabled=0;
	public $elderly=0;
	public $mood=0;
	public $morale=0;
	public $selfesteem=0;
	public $comfort=0;
	public $nourishment=0;
	public $hostility=500;
	public $individualism=500;
	public $tolerance=500;
	public $freedom=500;

	public function __construct($mysqli, $uid=0) {
		
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($this->uid>0) $this->loadData();
	}
	
	public function validate($gx, $gy, $lx, $ly) {
		$sql = "SELECT `global_x`, `global_y`, `local_x`, `local_y`, `general_type` FROM `objects` WHERE `uid`=$this->uid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			if ($row[4]!=7) return -2;//Not a group
			if ($row[0]!=$gx||$row[1]!=$gy) {
				$this->gx = $row[0];
				$this->gy = $row[1];
				$this->lx = $row[2];
				$this->ly = $row[3];
				return -3;//In another location
			}
			if ($row[2]!=$lx||$row[3]!=$ly) {
				$this->lx = $row[2];
				$this->ly = $row[3];
				return -4;//Same global position but different local
			}
			return 100;//A-OK
		}
		return -1;//Not an existing object
	}
	
	public function loadName() {
		if ($this->name) return $this->name;
		
		$sql = "SELECT `name` FROM `group_names` WHERE `objectFK`=$this->uid LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->name = $row[0];
			return $this->name;
		}
		$this->generateName();
		return $this->name;
	}
	
	public function generateName() {
		$name = $this->getRandomName();
		$sql = "INSERT INTO `group_names` (`objectFK`, `name`) VALUES ($this->uid, '$name')";
		$this->mysqli->query($sql);
		$this->name = $name;
	}
	
	public function loadData() {
		$sql = "SELECT `global_x`, `global_y`, `local_x`, `local_y` FROM `objects` WHERE `uid`=$this->uid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$this->gx = $row[0];
			$this->gy = $row[1];
			$this->lx = $row[2];
			$this->ly = $row[3];
		}
		else return false;
		$sql = "SELECT `attributeFK`, `value` FROM `o_attrs` WHERE (`attributeFK` BETWEEN 75 AND 91 OR `attributeFK` BETWEEN 102 AND 105) AND `objectFK`=$this->uid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				if ($row[0]==ATTR_FUNCTIONAL_ADULT_MALES) $this->adult_male = $row[1];
				if ($row[0]==ATTR_FUNCTIONAL_ADULT_FEMALES) $this->adult_female = $row[1];
				if ($row[0]==ATTR_FUNCTIONAL_ADULT_NEUTERS) $this->adult_neuter = $row[1];
				if ($row[0]==ATTR_WOUNDED_ADULT_MALES) $this->wounded_male = $row[1];
				if ($row[0]==ATTR_WOUNDED_ADULT_FEMALES) $this->wounded_female = $row[1];
				if ($row[0]==ATTR_WOUNDED_ADULT_NEUTERS) $this->wounded_neuter = $row[1];
				if ($row[0]==ATTR_SICK_ADULT_MALES) $this->sick_male = $row[1];
				if ($row[0]==ATTR_SICK_ADULT_FEMALES) $this->sick_female = $row[1];
				if ($row[0]==ATTR_SICK_ADULT_NEUTERS) $this->sick_neuter = $row[1];
				if ($row[0]==ATTR_CHILDREN) $this->children = $row[1];
				if ($row[0]==ATTR_PERMANENTLY_DISABLED_ADULTS) $this->disabled = $row[1];
				if ($row[0]==ATTR_ELDERLY_PEOPLE) $this->elderly = $row[1];
				if ($row[0]==ATTR_MOOD) $this->mood = $row[1];
				if ($row[0]==ATTR_MORALE) $this->morale = $row[1];
				if ($row[0]==ATTR_SELF_ESTEEM) $this->selfesteem = $row[1];
				if ($row[0]==ATTR_COMFORT) $this->comfort = $row[1];
				if ($row[0]==ATTR_NOURISHMENT) $this->nourishment = $row[1];
				if ($row[0]==ATTR_HOSTILITY) $this->hostility = $row[1];
				if ($row[0]==ATTR_INDIVIDUALISM) $this->individualism = $row[1];
				if ($row[0]==ATTR_TOLERANCE) $this->tolerance = $row[1];
				if ($row[0]==ATTR_FREEDOM) $this->freedom = $row[1];
			}
			return true;
		}
		return false;
	}
	
	public function create($gx, $gy, $lx, $ly) {
		$local = new LocalMap($this->mysqli, $gx, $gy);
		$ppl = $local->countPCpresent();
		$minpop = round(pow($ppl, 2.5))+3;
		$maxpop = round(pow($ppl, 2.9))+5;
		$actual = min(10000,rand($minpop, $maxpop));
		
		if ($actual<9) {
			$men = round($actual*0.5);
			$women = $actual-$men;
			$children = 0;
			$disabled = 0;
			$neuters = 0;
		}
		else if ($actual<20) {
			$men = round($actual*0.4);
			$women = round($actual*0.35);
			$children = rand(round($women/8), round($women/3));
			$neuters = $actual-$men-$women-$children;
			$disabled = 0;
		}
		else {
			$men = round($actual*0.36);
			$women = round($actual*0.35);
			$children = rand(round($women/8), round($women/3));
			$disabled = rand(round($actual/10000), ceil($actual/1000)+2);
			$neuters = $actual-$men-$women-$children-$disabled;
		}
		
		$curtime = new Time($this->mysqli);
		$obj = new Obj($this->mysqli);
		$newid = $obj->create(27, 7, 0, "group", $gx, $gy, $lx, $ly, 0, 0, 0, $curtime->dateTime, $curtime->minute);
		$this->uid = $newid;
		
		$this->setAttribute(ATTR_FUNCTIONAL_ADULT_MALES, $men);
		$this->setAttribute(ATTR_FUNCTIONAL_ADULT_FEMALES, $women);
		$this->setAttribute(ATTR_FUNCTIONAL_ADULT_NEUTERS, $neuters);
		$this->setAttribute(ATTR_CHILDREN, $children);
		$this->setAttribute(ATTR_PERMANENTLY_DISABLED_ADULTS, $disabled);
		
		$this->setAttribute(ATTR_MOOD, $this->getWeighedRand());
		$this->setAttribute(ATTR_MORALE, $this->getWeighedRand());
		$this->setAttribute(ATTR_SELF_ESTEEM, $this->getWeighedRand());
		$this->setAttribute(ATTR_COMFORT, $this->getWeighedRand());
		$this->setAttribute(ATTR_NOURISHMENT, $this->getWeighedRand());
		
		$this->setAttribute(ATTR_HOSTILITY, $this->getWeighedRand());
		$this->setAttribute(ATTR_INDIVIDUALISM, $this->getWeighedRand());
		$this->setAttribute(ATTR_TOLERANCE, $this->getWeighedRand());
		$this->setAttribute(ATTR_FREEDOM, $this->getWeighedRand());
		
		$this->loadData();
		$this->generateName();
	}
	
	public function getWeighedRand() {
		$ranges = array(
			array(1, 238),
			array(239,359),
			array(360,429),
			array(430,464),
			array(465,484),
			array(485,499),
			array(500,501),
			array(502,516),
			array(517,536),
			array(537,571),
			array(572,641),
			array(642,761),
			array(762,1000)
			);
		$rand1 = $ranges[rand(0,sizeof($ranges)-1)];
		$rand2 = rand($rand1[0], $rand1[1]);
		return $rand2;
	}
	
	public function setAttribute($attribute, $newVal) {
		$obj = new Obj($this->mysqli, $this->uid);
		$obj->setAttribute($attribute, $newVal);
	}
	
	public function printData() {
		
		ptag("h2", $this->loadName() . " (Group " . $this->uid . ")");
		ptag("h3", "Demographics");
		para("This group has a total of " . $this->countMembers() . " members.");
		para("Out of these, " . $this->countMembers("functional adults") . " are functional adults.");
		para("Overall, there are " . $this->countMembers("men") . " men, " . $this->countMembers("women") . " women and " . $this->countMembers("neuters") . " neuters, (not counting children, disabled people and the elderly).");
		if ($this->sick_male==0&&$this->sick_female==0&&$this->sick_neuter==0) para("Currently nobody is sick.");
		else para($this->sick_male ." of the men, ". $this->sick_female ." of the women and ". $this->sick_neuter ." of the neuters are currently ill.");
		if ($this->wounded_male==0&&$this->wounded_female==0&&$this->wounded_neuter==0) para("Nobody is wounded.");
		else para($this->wounded_male ." of the men, ". $this->wounded_female ." of the women and ". $this->wounded_neuter ." of the neuters are wounded.");
		if ($this->children==0&&$this->disabled==0&&$this->elderly==0) para("There are no children, disabled people or elderly people.");
		else para("There are ". $this->children . " children, " . $this->disabled . " permanently disabled people and " . $this->elderly . " elderly people.");
		ptag("h3", "Status");
		para("Mood: " . $this->describeMood() );
		para("Morale: " . $this->describeMorale() );
		para("Self-esteem: " . $this->describeSelfEsteem() );
		para("Nourishment: " . $this->describeNourishment() );
		para("Comfort: " . $this->describeComfort() );
		ptag("h3", "Moral compass");
		para("Hostility: " . $this->describeHostility() );
		para("Individualism: " . $this->describeIndividualism() );
		para("Tolerance: " . $this->describeTolerance() );
		para("Freedom: " . $this->describeFreedom() );
		ptag("h3", "AP");
		para("Daily AP per functional adult: " . $this->getAPperAdult());
		para("Daily AP per semi-functional member: " . $this->getAPperHalf() . " (this means children, disabled and the elderly)");
		para("Current functional AP left total: " . $this->checkAP());
		para("Current semi-functional AP left total: " . $this->checkAP("semi"));
	}
	
	public function countMembers($specific = false) {
		if (!$specific) {
			$count = $this->adult_male+$this->adult_female+$this->adult_neuter+$this->wounded_male+$this->wounded_female+$this->wounded_neuter+$this->sick_male+$this->sick_female+$this->sick_neuter+$this->children+$this->disabled+$this->elderly;
			return $count;
		}
		if ($specific == "men") {
			$count = $this->adult_male+$this->wounded_male+$this->sick_male;
			return $count;
		}
		if ($specific == "women") {
			$count = $this->adult_female+$this->wounded_female+$this->sick_female;
			return $count;
		}
		if ($specific == "neuters") {
			$count = $this->adult_neuter+$this->wounded_neuter+$this->sick_neuter;
			return $count;
		}
		if ($specific == "functional adults") {
			$count = $this->adult_male+$this->adult_female+$this->adult_neuter;
			return $count;
		}
		if ($specific == "semi-functional") {
			$count = $this->children+$this->elderly+$this->disabled;
			return $count;
		}
		if ($specific == "children") {
			return $this->children;
		}
		if ($specific == "elderly") {
			return $this->elderly;
		}
		if ($specific == "disabled") {
			return $this->disabled;
		}
		if ($specific == "sick men") {
			$count = $this->sick_male;
			return $count;
		}
		if ($specific == "sick women") {
			$count = $this->sick_female;
			return $count;
		}
		if ($specific == "sick neuters") {
			$count = $this->sick_neuter;
			return $count;
		}
		if ($specific == "wounded men") {
			$count = $this->wounded_male;
			return $count;
		}
		if ($specific == "wounded women") {
			$count = $this->wounded_female;
			return $count;
		}
		if ($specific == "wounded neuters") {
			$count = $this->wounded_neuter;
			return $count;
		}
		return false;
	}
	
	public function getRandomName() {
		$currentLocation = new GlobalMap($this->mysqli, $this->gx, $this->gy);
		$animals = $currentLocation->getPossibleAnimals();
		
		if (is_array($animals)&&rand(0,2)==0) {
			$randpos = rand(0,sizeof($animals)-1);
			$adjectives = array(
				"Black",
				"White",
				"Gray",
				"Brown",
				"Golden",
				"Silver",
				"Dark",
				"Muddy",
				"Sandy",
				"Clay",
				"Sitting",
				"Walking",
				"Wandering",
				"Strolling",
				"Flying",
				"Shadowy",
				"Racing",
				"Running",
				"Crawling",
				"Sneaking",
				"Magnificent",
				"Crazy",
				"Dead",
				"Bone",
				"Stone",
				"Barking",
				"Howling",
				"Crying",
				"Silent",
				"Painted",
				"Screaming",
				"Soaring",
				"Cunning",
				"Clever",
				"Wise",
				"Hard-Headed",
				"Grinning",
				"Laughing"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			
			if ($animals[$randpos]["plural"]) $plural = $animals[$randpos]["plural"];
			else $plural = $animals[$randpos]["name"] . "s";
			
			return "The " . $adjectives[$randpos2] . " " . ucwords($plural);
		}
		
		$natural = $currentLocation->getResources();
		
		if ($natural==0||rand(0,2)==0) {
			//There are no local resources or rand is 0
			$adjectives = array(
				"Black",
				"White",
				"Gray",
				"Brown",
				"Golden",
				"Silver",
				"Dark",
				"Muddy",
				"Sandy",
				"Running",
				"Flying",
				"Shadowy",
				"Racing",
				"Sneaking",
				"Magnificent",
				"Crazy",
				"Dead",
				"Silent",
				"Painted",
				"Cunning",
				"Clever",
				"Wise",
				"Hard-Headed",
				"Secret",
				"Hidden"
				);
			
			$nouns = array(
				"Wanderers",
				"Sages",
				"Old Ones",
				"Settlers",
				"Children",
				"People",
				"Chargers",
				"Holders",
				"Treasurers",
				"Knives",
				"Hunters"
				);
			
			$randpos = rand(0,sizeof($nouns)-1);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . $nouns[$randpos];
		}
		
		$resources = $currentLocation->loadResources($natural, 1);
		
		$randpos = rand(0,sizeof($resources)-1);
		$category = $resources[$randpos]["category"];
		
		if ($category==1) {
			$adjectives = array(
				"Black",
				"White",
				"Gray",
				"Brown",
				"Golden",
				"Damp",
				"Fertile",
				"Rich",
				"Rocky"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==2) {
			$adjectives = array(
				"Black",
				"White",
				"Gray",
				"Brown",
				"Red",
				"Colorful",
				"Shining",
				"Dull",
				"Hard",
				"Solid",
				"Crumbling",
				"Polished",
				"Multi-colored",
				"Radiant",
				"Crushed"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==3) {
			$adjectives = array(
				"Dark",
				"White",
				"Weeping",
				"Tall",
				"Powerful",
				"Strong",
				"Flexible",
				"Climbing",
				"Hard",
				"Solid",
				"Majestic",
				"Polished",
				"Lush",
				"Flowering"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==4) {
			$adjectives = array(
				"Black",
				"White",
				"Gray",
				"Brown",
				"Red",
				"Colorful",
				"Shining",
				"Dull",
				"Coarse",
				"Clustered",
				"Shiny",
				"Flowing",
				"Flying",
				"Sinking",
				"Deadly"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==5) {
			$adjectives = array(
				"Dark",
				"Light",
				"Green",
				"Dying",
				"Burned",
				"Billowing",
				"Lush",
				"Sharp",
				"Cut"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==6) {
			$adjectives = array(
				"Dark",
				"White",
				"Green",
				"Brown",
				"Red",
				"Yellow",
				"Colorful",
				"Lush",
				"Dew-dropped",
				"Firm",
				"Soft",
				"Flying",
				"Soarking",
				"Multi-colored",
				"Dry"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==7) {
			$adjectives = array(
				"Cool",
				"Hot",
				"Boiling",
				"Chilled",
				"Flowing",
				"Glistening",
				"Drying",
				"Turmoiling"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==8) {
			$adjectives = array(
				"Rich",
				"Golden",
				"Lush",
				"Bountiful",
				"Flowing",
				"Endless",
				"Nourishing",
				"Growing",
				"Tall"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==9) {
			$adjectives = array(
				"Dark",
				"Sour",
				"Sweet",
				"Bitter",
				"Red",
				"Smashed",
				"Bountiful",
				"Ripe",
				"Fragrant",
				"Juicy",
				"Radiant"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==10) {
			$adjectives = array(
				"Dark",
				"Sour",
				"Sweet",
				"Bitter",
				"Red",
				"Smashed",
				"Bountiful",
				"Ripe",
				"Fragrant",
				"Juicy",
				"Radiant",
				"Bursting",
				"Swollen",
				"Round",
				"Plump",
				"Golden",
				"Succulent"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==11) {
			$adjectives = array(
				"Rotten",
				"Fresh",
				"Sweet",
				"Bitter",
				"Red",
				"Golden",
				"Bountiful",
				"Green",
				"Brown"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==16) {
			$adjectives = array(
				"Thick",
				"Peeling",
				"Curled",
				"Gnarly",
				"Smooth",
				"Golden",
				"Brown",
				"Floating",
				"Hard-To-Cut"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==17) {
			$adjectives = array(
				"Round",
				"Dried",
				"Large",
				"Strong",
				"Small"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==18) {
			$adjectives = array(
				"Hard",
				"Crushed",
				"Polished",
				"Cracked",
				"Smooth",
				"Golden",
				"Brown",
				"Tough",
				"Bitter"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==19) {
			$adjectives = array(
				"Lush",
				"Wild",
				"Tangled",
				"Gnarly",
				"Green",
				"Golden",
				"Drying",
				"Dying",
				"Hard-To-Cut"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==20) {
			$adjectives = array(
				"Bitter",
				"Tough-To-Crack",
				"Energetic",
				"Smooth",
				"Smooth",
				"Golden",
				"Brown",
				"Black",
				"White",
				"Powerful",
				"Full of Life",
				"Potential"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==21) {
			$adjectives = array(
				"Rotten",
				"Fresh",
				"Sweet",
				"Bitter",
				"Red",
				"Golden",
				"Bountiful",
				"Green",
				"Brown",
				"White",
				"Gnarly",
				"Far Reaching"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==23) {
			$adjectives = array(
				"Rotten",
				"Fresh",
				"Sweet",
				"Bitter",
				"Red",
				"Golden",
				"Bountiful",
				"Green",
				"Brown",
				"Poisonous",
				"Slimy",
				"Autumn"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==26) {
			$adjectives = array(
				"Strong",
				"Valuable",
				"Red",
				"Tarnished",
				"Hidden",
				"Secret",
				"Bountiful",
				"Striped",
				"Brown"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else if ($category==27) {
			$adjectives = array(
				"Cracking",
				"Fresh",
				"Bending",
				"Hard-To-Break",
				"Green",
				"Lush",
				"Flowering",
				"Fragrant",
				"Brown"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
		else {
			$adjectives = array(
				"Secret",
				"Hidden",
				"Hard",
				"Burning",
				"Glowing",
				"Golden",
				"Hollow",
				"Heavy",
				"Light",
				"Dark"
				);
			$randpos2 = rand(0,sizeof($adjectives)-1);
			return "The " . $adjectives[$randpos2] . " " . ucwords($resources[$randpos]["plural"]);
		}
	}
	
	public function describeMood() {
		if ($this->mood>900) return "<span class='level9'>ecstatic</span>";
		if ($this->mood>800) return "<span class='level8'>overjoyed</span>";
		if ($this->mood>700) return "<span class='level7'>very happy</span>";
		if ($this->mood>600) return "<span class='level6'>happy</span>";
		if ($this->mood>500) return "<span class='level5'>content</span>";
		if ($this->mood>400) return "<span class='level4'>so-so</span>";
		if ($this->mood>300) return "<span class='level3'>apathetic</span>";
		if ($this->mood>200) return "<span class='level2'>down</span>";
		if ($this->mood>100) return "<span class='level1'>grief-stricken</span>";
		return "<span class='level0'>inconsolable</span>";
	}
	
	public function describeMorale() {
		if ($this->morale>900) return "<span class='level9'>excellent</span>";
		if ($this->morale>800) return "<span class='level8'>great</span>";
		if ($this->morale>700) return "<span class='level7'>pretty great</span>";
		if ($this->morale>600) return "<span class='level6'>very good</span>";
		if ($this->morale>500) return "<span class='level5'>good</span>";
		if ($this->morale>400) return "<span class='level4'>below average</span>";
		if ($this->morale>300) return "<span class='level3'>pretty bad</span>";
		if ($this->morale>200) return "<span class='level2'>bad</span>";
		if ($this->morale>100) return "<span class='level1'>pathetic</span>";
		return "<span class='level0'>bottom level</span>";
	}
	
	public function describeSelfEsteem() {
		if ($this->selfesteem>900) return "<span class='level9'>excellent</span>";
		if ($this->selfesteem>800) return "<span class='level8'>great</span>";
		if ($this->selfesteem>700) return "<span class='level7'>pretty great</span>";
		if ($this->selfesteem>600) return "<span class='level6'>very good</span>";
		if ($this->selfesteem>500) return "<span class='level5'>good</span>";
		if ($this->selfesteem>400) return "<span class='level4'>below average</span>";
		if ($this->selfesteem>300) return "<span class='level3'>pretty bad</span>";
		if ($this->selfesteem>200) return "<span class='level2'>bad</span>";
		if ($this->selfesteem>100) return "<span class='level1'>pathetic</span>";
		return "<span class='level0'>bottom level</span>";
	}
	
	public function describeComfort() {
		if ($this->comfort>900) return "<span class='level9'>luxurious</span>";
		if ($this->comfort>800) return "<span class='level8'>great</span>";
		if ($this->comfort>700) return "<span class='level7'>very good</span>";
		if ($this->comfort>600) return "<span class='level6'>good</span>";
		if ($this->comfort>500) return "<span class='level5'>decent</span>";
		if ($this->comfort>400) return "<span class='level4'>poor</span>";
		if ($this->comfort>300) return "<span class='level3'>pretty bad</span>";
		if ($this->comfort>200) return "<span class='level2'>bad</span>";
		if ($this->comfort>100) return "<span class='level1'>miserable</span>";
		return "<span class='level0'>suffering";
	}
	
	public function describeNourishment() {
		if ($this->nourishment>900) return "<span class='level9'>stuffed</span>";
		if ($this->nourishment>800) return "<span class='level8'>overfed</span>";
		if ($this->nourishment>700) return "<span class='level7'>more than content</span>";
		if ($this->nourishment>600) return "<span class='level6'>optimal</span>";
		if ($this->nourishment>500) return "<span class='level5'>nearly optimal</span>";
		if ($this->nourishment>400) return "<span class='level4'>somewhat lacking</span>";
		if ($this->nourishment>300) return "<span class='level3'>considerably lacking</span>";
		if ($this->nourishment>200) return "<span class='level2'>malnourished</span>";
		if ($this->nourishment>100) return "<span class='level1'>starving</span>";
		return "<span class='level0'>almost dead</span>";
	}
	
	public function describeHostility() {
		if ($this->hostility>900) return "<span class='level9'>explosive</span>";
		if ($this->hostility>800) return "<span class='level8'>very aggressive</span>";
		if ($this->hostility>700) return "<span class='level7'>aggressive</span>";
		if ($this->hostility>600) return "<span class='level6'>somewhat prone to hostility</span>";
		if ($this->hostility>500) return "<span class='level5'>hostile if given a reason</span>";
		if ($this->hostility>400) return "<span class='level4'>hostile if given a good reason</span>";
		if ($this->hostility>300) return "<span class='level3'>somewhat peaceful</span>";
		if ($this->hostility>200) return "<span class='level2'>mostly peaceful</span>";
		if ($this->hostility>100) return "<span class='level1'>peaceful</span>";
		return "<span class='level0'>pacifists</span>";
	}
	
	public function describeIndividualism() {
		if ($this->individualism>900) return "<span class='level9'>individuality means everything</span>";
		if ($this->individualism>800) return "<span class='level8'>individualism is very important</span>";
		if ($this->individualism>700) return "<span class='level7'>individualism is important</span>";
		if ($this->individualism>600) return "<span class='level6'>group is still somewhat important but not as much as self-expression</span>";
		if ($this->individualism>500) return "<span class='level5'>self-expression is slightly more valued than membership in the group</span>";
		if ($this->individualism>400) return "<span class='level4'>membership in the group is slightly more valued than self-expression</span>";
		if ($this->individualism>300) return "<span class='level3'>group membership is somewhat important but self-expression is tolerated up to a point</span>";
		if ($this->individualism>200) return "<span class='level2'>group important, slight individualism allowed</span>";
		if ($this->individualism>100) return "<span class='level1'>group very important, slight individualism allowed</span>";
		return "<span class='level0'>group means everything, individualism is not tolerated</span>";
	}
	
	public function describeTolerance() {
		if ($this->tolerance>900) return "<span class='level9'>they see value in everybody regardless of ability or lifestyle</span>";
		if ($this->tolerance>800) return "<span class='level8'>high tolerance for difference</span>";
		if ($this->tolerance>700) return "<span class='level7'>semi-high tolderance for difference</span>";
		if ($this->tolerance>600) return "<span class='level6'>moderate tolerance for difference</span>";
		if ($this->tolerance>500) return "<span class='level5'>mainly hidden intolerance</span>";
		if ($this->tolerance>400) return "<span class='level4'>difference is scorned upon but reactions are mild</span>";
		if ($this->tolerance>300) return "<span class='level3'>close-minded and moderately vocal about it</span>";
		if ($this->tolerance>200) return "<span class='level2'>notable scorn towards most deviations and handicaps</span>";
		if ($this->tolerance>100) return "<span class='level1'>vocal intolerance concerning anything but the most trivial differences</span>";
		return "<span class='level0'>you must fit the mold or you're eliminated</span>";
	}
	
	public function describeFreedom() {
		if ($this->freedom>900) return "<span class='level9'>they would rather be dead than slaves</span>";
		if ($this->freedom>800) return "<span class='level8'>they might grudgingly follow orders if they really have to</span>";
		if ($this->freedom>700) return "<span class='level7'>they can grudgingly follow orders as long as it's not opposed to their moral code</span>";
		if ($this->freedom>600) return "<span class='level6'>they can grudgingly follow orders as long as it's nothing completely outrageous</span>";
		if ($this->freedom>500) return "<span class='level5'>they can be threatened into following orders</span>";
		if ($this->freedom>400) return "<span class='level4'>they can be coerced to accept orders</span>";
		if ($this->freedom>300) return "<span class='level3'>they are happy to follow orders that don't go against their beliefs</span>";
		if ($this->freedom>200) return "<span class='level2'>they are happy to follow orders</span>";
		if ($this->freedom>100) return "<span class='level1'>they gain pleasure in following orders</span>";
		return "<span class='level0'>they expect to be bossed around in every aspect of life</span>";
	}
	
	public function getAPperAdult() {
		if ($this->nourishment>600) $ap1 = round(1260 - ($this->nourishment*0.8));
		else $ap1 = max(200, round (1.3 * $this->nourishment));
		
		$ap2 = max(200, round (0.78 * $this->morale));
		
		$ap3 = max(200, round (0.78 * $this->comfort));
		
		return round($ap1*0.5+$ap2*0.25+$ap3*0.25);
	}
	
	public function getAPperHalf() {
		return round($this->getAPperAdult()/2);
	}
	
	public function showReputation($charid) {
		$char = new Character($this->mysqli, $charid);
		$reputation = $this->getOpinion($char->bodyId, 1);
		if (!is_numeric($reputation)) return "This group has no opinion on you yet.";
		if ($reputation<-1000) return "This group thinks you're a dangerous fool and everybody would be better off without you. ($reputation)";
		if ($reputation<-500) return "This group thinks you're a clown and it would take a lot of work to prove them otherwise. ($reputation)";
		if ($reputation<-200) return "This group doesn't take you seriously at all. ($reputation)";
		if ($reputation<-100) return "This group thinks you're pretty silly. ($reputation)";
		if ($reputation<0) return "This group isn't very impressed by your doings or their opinions are mixed. ($reputation)";
		if ($reputation<100) return "This group has a fairly neutral opinion on you. ($reputation)";
		if ($reputation<200) return "This group has a tentatively positive opinion on you. ($reputation)";
		if ($reputation<500) return "This group has a very positive opinion on you. ($reputation)";
		if ($reputation<1000) return "This group thinks you are a great person. ($reputation)";
		return "This group thinks you are awesome. ($reputation)";
	}
	
	public function doFavor($actor, $type2) {
		if ($type2 == 1) {
			$type = 0;
			$ap = 15;
		}
		else if ($type2 == 2) {
			$type = 0;
			$ap = 60;
		}
		else if ($type2 == 3) {
			$type = 2;
			$ap = 120;
		}
		else if ($type2 == 4) {
			$type = 1;
			$ap = 240;
		}
		else return -2;//Invalid type
		
		//Types: 0 - generic favor, 1 - hunt, 2 - forage
		$char = new Character($this->mysqli, $actor);
		$check = $char->checkAP($ap);
		if ($check == -2) {
			$char->spendAP($ap);
			$rand = rand(0,99);
			if ($rand==0) {
				$generic = array(
					"You're just trying to be nice but accidentally manage to break a social norm you were unaware of. Everybody is upset.",
					"You accidentally touch an important person in a way that could be considered sexual. They are greatly offended."
					);
				
				$hunting = array(
					"You froze in panic upon seeing a dangerous animal. Now your hunting group thinks you're a pathetic coward.",
					"You ran away in panic when an animal attacked one of your party members instead of helping out. The victim got hurt badly and everyone blames you for what happened.",
					"You get lost and wander around aimlessly, unknowingly drifting even further from the rest of the group. The others have to abandon the hunt in order to search for you. You get told you were lucky they even found you or you would've had to spend the night alone in the wilderness.",
					"You wander off on your own and get intercepted by a dangerous beast. Your cries alert another hunter to your location and he manages to save you but you still got sightly injured."
					);
				
				$foraging = array(
					"You find some mushrooms you are unfamiliar with. You think they might be edible, so you bring them home. Somebody else eats them and almost dies.",
					"You find an unfamiliar fruit. Turns out it's toxic and causes your throat to swell up. You have trouble breathing for several hours and can't talk.",
					"You get lost while foraging and are in a state of panic and exhaustion when you finally find your way back to the others.",
					"You get chased by a wild animal and in order to run away faster, you have to drop what you had gathered."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = max(-1200, -20*$ap);//Total failure, you made an ass out of yourself
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 1,
					"description" => $description
					);
			}
			else if ($rand<5) {
				$generic = array(
					"You stumble and fall against a person of higher status. You feel embarrassed.",
					"You suffer a minor accident while working and spill a bit of blood on something that was supposed to be clean."
					);
				
				$hunting = array(
					"You accidentally dropped your weapon and others had to rescue you from getting mauled.",
					"You stepped in a hole and twisted your ankle. The others had to support you on the way home, so the hunting mission had to be aborted.",
					"A dangerous animal charges at you. You make a lot of noise, scaring it into running into your fellow hunters. Somebody gets hurt.",
					"You get lost from the rest of the group and this makes you scared. It's not long before they manage to find you but your concentration is ruined."
					);
				
				$foraging = array(
					"You find some mushrooms you are unfamiliar with. You think they might be edible, so you bring them home. Somebody else eats them and gets sick.",
					"You find an unfamiliar fruit, so you taste it. Turns out it makes your tongue swell up and you talk like an idiot for the next three hours."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = max(-500, -5*$ap);//You screwed up a bit
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 2,
					"description" => $description
					);
			}
			else if ($rand<25) {
				$generic = array(
					"You stumble and faceplant in front of some people you were trying to impress. They laugh but eventually help you up.",
					"You try to tell a joke but due to cultural differences, nobody gets it. You try to explain it but start feeling like it's completely ruined at this point."
					);
				
				$hunting = array(
					"You stepped on a twig at a critical moment, scaring the prey away. The others were a bit grumpy about this but these things happen.",
					"You got over-enthusiastic chasing an animal and failed to follow the strategy you had agreed on. As a result, the animal got away.",
					"You get scared by a sudden sound. Turns out it's just a harmless creature. The others snicker at you.",
					"You got over-enthusiastic chasing an animal and failed to follow the strategy you had agreed on. The others manage to save the situation but it could've lead into a total disaster."
					);
				
				$foraging = array(
					"You find some mushrooms and bring them home. The others tell you that this particular type tastes like bile and they are completely inedible. They're not toxic but still useless.",
					"You find something you thought was useful, but turns out the group has no use for it.",
					"You find an unfamiliar fruit and taste it. It's delicious! You gather an armful and bring them hope. Everybody is initially pleased, until it turns out that it's in fact a laxative. Everybody is very busy the following night and won't get much sleep."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = max(-200, -1*$ap);//You failed just slightly
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 3,
					"description" => $description
					);
			}
			else if ($rand<75) {
				$generic = array(
					"You manage to say something witty and some people think you're pretty great. This brings you closer together."
					);
				
				$hunting = array(
					"You tried your best in helping out but couldn't catch anything.",
					"You accidentally scare an animal into running straight in the arms of a fellow hunter. They know it has nothing to do with skill but still appreciate the catch.",
					"After trying really hard, you manage to catch a small animal. It's not very useful but the others congratulate you on the kill nevertheless.",
					"You manage to catch multiple small animals. Nothing great but this will feed a couple of people.",
					"You manage to assist the others in catching a moderate-sized beast. This improves the team spirit."
					);
				
				$foraging = array(
					"You manage to find a moderately good resource deposit."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = min(200, $ap);//You succeeded just slightly
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 4,
					"description" => $description
					);
			}
			else if ($rand<95) {
				$generic = array(
					"You come up with a funny joke, which makes everybody laugh except that one slow person. People think you're a riot."
					);
				
				$hunting = array(
					"You are the star of the hunt and make a considerable kill. The others congratulate you. They are clearly impressed.",
					"A dangerous animal charges at one of the weakest party members, but you heroically step in between, managing to drive it away. The others are grateful.",
					"A dangerous animal charges at one of the other hunters. You step in between and get seriously hurt. The others thought this was a bit foolish but still heroic.",
					"One of the party members gets lost and you all split up searching for him. Eventually you manage to find him and call the group back together."
					);
				
				$foraging = array(
					"You manage to find a very good resource deposit."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = min(500, 5*$ap);//You did great
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 5,
					"description" => $description
					);
			}
			else {
				$generic = array(
					"You have a philosophical conversation with some people. They think you're very smart and start respecting you more.",
					"An important person almost falls in front of you but you manage to catch them. They are grateful."
					);
				
				$hunting = array(
					"A dangerous animal charges at one of the other hunters. You intercept it and manage to kill it with a clean strike to the head. The others think you are awesome.",
					"A dangerous animal charges at one of the other hunters. You step in between and after a long struggle, manage to kill the beast. It manages to scratch you a bit but it's nothing serious.",
					"One of the party members gets lost and you all split up searching for him. While you are searching for the missing person, you suddenly run into a dangerous beast! Nobody is around to help you out, but you manage to defeat it unassisted. Once the group gets back together, they are amazed you are even alive.",
					"You run into a particularly smart animal who's good at evading people. You manage to convince the others that your strategy is optimal and thanks to this, you score a spectacular kill."
					);
				
				$foraging = array(
					"You manage to find a huge resource deposit."
					);
				
				if ($type==0) $description = $generic[rand(0,sizeof($generic)-1)];
				else if ($type==1) $description = $hunting[rand(0,sizeof($hunting)-1)];
				else if ($type==2) $description = $foraging[rand(0,sizeof($foraging)-1)];
				
				$change = min(1200, 20*$ap);//It was a tremendous success
				$check2 = $this->updateOpinion($char->bodyId, 1, $change);
				if ($check2==-1) return array(
					"change" => 0,
					"result" => -1
					);
				else return array(
					"change" => $change,
					"result" => 6,
					"description" => $description
					);
			}
		}
		else return -1;//not enough AP
	}
	
	public function updateOpinion($target, $type, $change) {
		//type 1 = respect
		$sql = "UPDATE `group_opinions` SET `value`=`value`+$change WHERE `target_id`=$target AND `group_id`=$this->uid AND `type`=$type LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) {
			$sql2 = "INSERT INTO `group_opinions` (`group_id`, `target_id`, `type`, `value`) VALUES ($this->uid, $target, $type, $change)";
			$this->mysqli->query($sql2);
			if ($this->mysqli->insert_id) return 2;
			else return -1;
		}
		else return 1;
	}
	
	public function getOpinion($target, $type) {
		$sql = "SELECT `value` FROM `group_opinions` WHERE `group_id`=$this->uid AND `target_id`=$target AND `type`=$type LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			return $row[0];
		}
		else return "NaN";
	}
	
	public function getHuntingStrength() {
		$people1 = 0;
		$people2 = 0;
		$weapons = $this->getWeapons();
		$hunters = $this->getHunters()+1;
		
		aasort($weapons, "avg");
		
		$used_weapons = array_slice($weapons, -$hunters);
		
		foreach ($used_weapons as $wp) {
			//At some point skills might come into play here
			$people1 += $wp["offense"]/100;//So people with good weapons generally count as multiple people
			$people2 += $wp["defense"]/100;
		}
		
		return array(
			"offense" => $people1,
			"defense" => $people2
			);
	}
	
	public function checkHurt($enemy_offense, $charid, $alone=false) {
		if ($alone) {
			$lone_hunter = new Character($this->mysqli, $charid);
			$group_defense = $lone_hunter->getHuntingStrength();
		}
		else $group_defense = $this->getHuntingStrength();
		
		$percentage = max(1, min(99, $enemy_offense/pow(($group_defense["defense"]+0.5), 0.2)));
		
		if (rand(0,100)<$percentage) return true;
		else return false;
	}
	
	public function checkKill($enemy_defense, $charid, $alone=false) {
		if ($alone) {
			$lone_hunter = new Character($this->mysqli, $charid);
			$group_offense = $lone_hunter->getHuntingStrength();
		}
		else $group_offense = $this->getHuntingStrength();
		
		$percentage = max(1, min(99, (1-$enemy_defense/101)*pow(($group_offense["offense"]-0.4), 0.2)*100));
		
		if (rand(0,100)<$percentage) return true;
		else return false;
	}
	
	private function countChangeValue($change) {
		if ($change<-500) return 1;
		if ($change<-100) return 2;
		if ($change<0) return 3;
		if ($change>600) return 6;
		if ($change>100) return 5;
		return 4;
	}
	
	public function hunt($actor) {
		$ap_per_round = 48 * $this->getHunters();
		$msg = "";
		$hunter = new Character($this->mysqli, $actor);
		$accident = false;
		$accident_you = false;
		$change = 0;
		$resnumber = 0;
		
		for ($counter=0; $counter<5; $counter++)
		{
			//To-do: Check group ap
			$check = $this->checkAP(false, $ap_per_round);
			if ($check==-2) {
				$this->spendAP($ap_per_round);
				$check = $hunter->checkAP(48);
				if ($check == -2) {
					$hunter->spendAP(48);
				}
				else return array(
					"result" => $this->countChangeValue($change),
					"change" => $change,
					"description" => $msg . " You are too tired to continue, so you have to go home."
					);
		
				if (rand(0,50)==0) {
					//Accident
					$accident = true;
					$seriousness = rand(1,5);
					if (rand(0,10)==0) {
						$accident_you = true;
						$this->updateOpinion($hunter->bodyId, 1, $seriousness*-100);
						$change -= 100;
						if ($seriousness==1) $msg .= " You got lost and this frightened you a little bit, but the others found you fairly soon.";
						if ($seriousness==2) $msg .= " You got lost and it took a while for the others to find you. They scold you for wandering off like that but then decide to go on with the hunt.";
						if ($seriousness==3) $msg .= " You sprained your ankle.";
						if ($seriousness==4) $msg .= " You get seriously lost and end up wandering around in half-panic, unknowingly moving further away from the others who are trying to search for you.";
						if ($seriousness==5) $msg .= " You get seriously lost and this makes you freak out. The others eventually find you huddled up and crying.";
					}
					else {
						if ($seriousness==1) $msg .= " One of the team members gets lost and you waste some time searching for him. Eventually you find him and he is alright, so you can continue.";
						if ($seriousness==2) $msg .= " One of the team members has a minor accident, but the team decides to continue the hunt regardless.";
						if ($seriousness==3) $msg .= " One of the team members accidentally steps in a hole and sprains his ankle.";
						if ($seriousness==4) $msg .= " Suddenly you notice one of the team members has gone missing. You spend ages searching for him. Eventually he is found but a lot of time has been wasted.";
						if ($seriousness==5) {
							$msg .= " One of the team members goes missing. After a long search you are alerted by cries in the distance and quickly run in that direction. ";
							//get local animal
							
							$result = $hunter->searchAnimals(30);
							if ($result == -1) return array(
								"result" => $this->countChangeValue($change),
								"change" => $change,
								"description" => $msg . " Turns out he was just seeing things that weren't there. The search has sucked the juice out of you, so it's time to go home.");
							else if ($result == -2) return array(
								"result" => $this->countChangeValue($change),
								"change" => $change,
								"description" => $msg . "It turns out he was spooked by nothing. Well, at least you found him. But now it's so late that you don't have time to search for prey anymore.");
							else {
								$msg .= " You find him being threatened by a(n) " . $result["name"] . ". The others are nowhere in sight, so you have to fight it alone.";
								$enemy = new Obj($this->mysqli, $result["uid"]);
								
								$danger = $enemy->getDanger();
								$hurt = $this->checkHurt($danger, $actor, true);
								$rand = rand(1,5);
								if ($hurt) {
									if ($rand==1) {
										$msg .= " You chicken out, leading into the other hunter being injured. The others aren't going to like this.";
										$this->updateOpinion($hunter->bodyId, 1, -500);
										$change -= 500;
									}
									else if ($rand==2) $msg .= " Despite your best efforts, the animal manages to wound the other guy.";
									else if ($rand==3) {
										$msg .= " You defend against the animal together, both getting injured but not as badly as if one of you had to face it alone.";
										$this->updateOpinion($hunter->bodyId, 1, 200);
										$change += 200;
									}
									else if ($rand==4) {
										$msg .= " You step in front of the animal, blocking it from hurting the other hunter. It hurts you instead.";
										$this->updateOpinion($hunter->bodyId, 1, 300);
										$change += 300;
									}
									else $msg .= " The other hunter chickens out, leaving you to deal with the enemy all by yourself. As a result, you get injured.";
								}
								$kill = $this->checkKill($danger, $actor, true);
								if ($kill&&$hurt) {
									$enemy->perish($actor);
									$enemy->moveInside($this->uid);
									if ($rand==1) $msg .= " While you watch helplessly from a safe distance, the other guy manages to defeat the animal by himself. 'Thanks a lot', he says sarcastically.";
									if ($rand==3) {
										$msg .= " With teamwork, you manage to defeat the beast.";
										$this->updateOpinion($hunter->bodyId, 1, 80);
										$change += 80;
									}
									else {
										$msg .= " Despite all the trouble, you finally manage to defeat the enemy.";
										$this->updateOpinion($hunter->bodyId, 1, 100);
										$change += 100;
									}
								}
								else if ($kill) {
									$enemy->perish($actor);
									$enemy->moveInside($this->uid);
									$msg .= " You manage to save the other guy without either one of you getting even a scratch. The others are impressed";
									$this->updateOpinion($hunter->bodyId, 1, 300);
									$change += 300;
								}
								else {
									if ($rand==1) {
										$msg .= " The other hunter doesn't stand a chance on his own. The animal gets away scot free. 'Thanks for nothing', the hunter says sarcastically.";
										$this->updateOpinion($hunter->bodyId, 1, -50);
										$change -= 50;
									}
									else $msg .= " Despite all the trouble, the animal manages to run away from you. Nice try.";
								}
							}
						}
					}
					if ($seriousness>2) return array(
						"result" => $this->countChangeValue($change),
						"change" => $change,
						"description" => $msg . " The hunt had to be aborted."
						);
				}
				//Search for an animal
				if ($counter>0) $mo = " more";
				else $mo = "";
				$result = $hunter->searchAnimals(30);
				if ($result == -1) {
					if ($mo == "") {
						$this->updateOpinion($hunter->bodyId, 1, -25);
						$change -= 25;
					}
					
					return array(
					"result" => $this->countChangeValue($change),
					"change" => $change,
					"description" => $msg . " You are completely exhausted, so you decide to call it quits."
					);
				}
				else if ($result == -2) {
					if ($mo == "") {
						$this->updateOpinion($hunter->bodyId, 1, -25);
						$change -= 25;
					}
					
					return array(
					"result" => $this->countChangeValue($change),
					"change" => $change,
					"description" => $msg . " Despite your best efforts, you can't find any$mo animals, so it's time to go home."
					);
				}
				else {
					$msg .= " You spot a(n) " . $result["name"] . ".";
					$this->updateOpinion($hunter->bodyId, 1, 10);
					$change += 10;
					$enemy = new Obj($this->mysqli, $result["uid"]);
					$danger = $enemy->getDanger();
					$hurt = $this->checkHurt($danger, $actor, false);
					$rand = rand(1,5);
					if ($hurt) {
						if ($rand==1) {
							$msg .= " You decide to stay back, not willing to risk your health. As a result, someone gets hurt.";
							$this->updateOpinion($hunter->bodyId, 1, -75);
							$change -= 75;
						}
						else if ($rand==2) $msg .= " You fight well, but the animal still manages to wound one of the party members.";
						else if ($rand==3) {
							$msg .= " You join the hunt and get wounded, but you're not the only one.";
							$this->updateOpinion($hunter->bodyId, 1, 50);
							$change += 50;
						}
						else if ($rand==4) {
							$msg .= " The animal threatens one of the weakest party members. You step in front of the animal, blocking it from hurting the other hunter. It hurts you instead.";
							$this->updateOpinion($hunter->bodyId, 1, 200);
							$change += 200;
						}
						else $msg .= " The team is more cautious than you, resulting in you getting hurt while everybody else is unscraped.";
					}
					$kill = $this->checkKill($danger, $actor, false);
					if ($kill&&$hurt) {
						$enemy->perish($actor);
						$enemy->moveInside($this->uid);
						if ($rand==1) $msg .= " The team eventually manages to defeat the animal, but no thanks to you.";
						
						if ($rand==3) {
							$msg .= " With teamwork, you manage to defeat the beast.";
							$this->updateOpinion($hunter->bodyId, 1, 100);
							$change += 100;
						}
						else {
							$msg .= " Despite all the trouble, you finally manage to defeat the enemy.";
							$this->updateOpinion($hunter->bodyId, 1, 100);
							$change += 100;
						}
					}
					else if ($kill) {
						$enemy->perish($actor);
						$enemy->moveInside($this->uid);
						if ($rand==2) {
							$msg .= " You try your best to assist the others in taking down the animal. Someone else lands the killing blow, but you get thanks for assisting.";
							$this->updateOpinion($hunter->bodyId, 1, 40);
							$change += 40;
						}
						else {
							$msg .= " The fight goes really well and the animal is killed.";
							if ($danger<35) $msg .= " The others shrug it off as too easy.";
							else if ($danger>75) $msg .= " This was a tough fight, so it's a surprise no one got hurt.";
							$this->updateOpinion($hunter->bodyId, 1, $danger);
							$change += $danger;
						}
					}
					else {
						$kill2 = $this->checkKill($danger, $actor, true);
						if ($kill2) {
							$enemy->perish($actor);
							$enemy->moveInside($this->uid);
							if ($danger<35) $msg .= " The others half-heartedly try to catch the animal, but give up when it evades them. But you're not ready to give up that easily. You try once again and easily manage to defeat it.";
							else $msg .= " After everybody else is ready to give up, you rise up for a heroic single attack and manage to defeat the enemy. The others may have weakened it but you did the hardest work.";
							$this->updateOpinion($hunter->bodyId, 1, $danger*2);
							$change += $danger*2;
						}
						else $msg .= " Despite all your efforts, the animal manages to escape. Better luck next time.";
					}
				}
			}
			else return array(
				"result" => $this->countChangeValue($change),
				"change" => $change,
				"description" => $msg . " The team is exhausted enough to call it quits."
				);
		}//end loop
		
		return array(
			"result" => $this->countChangeValue($change),
			"change" => $change,
			"description" => $msg . " Enough time has been spent on this trip. It's time to go home."
			);
	}
	
	public function countSimilarItems($preset, $secMatters=false, $secondary=0) {
		if ($secMatters) $sql = "SELECT COUNT(`uid`) FROM `objects` WHERE `parent`=$this->uid AND `presetFK`=$preset AND `secondaryFK`=$secondary";
		else $sql = "SELECT COUNT(`uid`) FROM `objects` WHERE `parent`=$this->uid AND `presetFK`=$preset";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			return $row[0];
		}
		else return -1;
	}
	
	public function getValueDonation($preset, $secondary, $weight=0, $pieces=1) {
		$sql = "SELECT `value` FROM `o_presets` WHERE `uid`=$preset LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$value = $row[0];
		}
		else return 0;
		
		if ($value==0) return 0;
		else {
			$similar = $this->countSimilarItems($preset);//In the future, secondary will sometimes matter but in the early stage, it is ignored
			$valOne = round($value/((($similar+$pieces)*0.05)+1));
			return $valOne*$pieces;
		}
	}
	
	public function getInternalValue($oid) {
		$obj = new Obj($this->mysqli, $oid);
		$sql = "SELECT `value` FROM `o_presets` WHERE `uid`=$obj->preset LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			$value = $row[0];
		}
		else return 0;
		
		if ($value==0) return 0;
		else {
			return ceil($value*$obj->pieces*1.2);
		}
	}
	
	function getInventory() {
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->uid ORDER BY FIELD(`general_type`,'5',`general_type`), `date_created` DESC, `weight` DESC";
		$res = $this->mysqli->query($sql);
		$inventory = array();
		if ($res) {
			while ($row =  mysqli_fetch_row($res)) {
				$inventory[] = $row[0];
			}
		}
		else return false;
		
		if ($inventory) {
			return $inventory;
		}
		else return false;
	}
	
	function getWeapons() {
		$retArr = array();
		$inventory = $this->getInventory();
		if ($inventory) {
			foreach ($inventory as $possible) {
				$test = new Obj($this->mysqli, $possible);
				$name = $test->getName();
				if ($test->type==1) {
					$offense = $test->getAttribute(ATTR_OFFENSE);
					$defense = $test->getAttribute(ATTR_DEFENSE);
					if ($offense&&$defense) $retArr[] = array(
						"uid" => $possible,
						"offense" => $offense,
						"defense" => $defense,
						"name" => $name,
						"avg" => round($offense+$defense)/2
						);
				}
			}
		}
		return $retArr;
	}
	
	function getHunters() {
		$weapons = $this->getWeapons();
		if (empty($weapons)) return 0;
		$possible = $this->countMembers("functional adults");
		if ($possible>sizeof($weapons)) return sizeof($weapons);
		return $possible;//Everybody has weapons
	}
	
	function withdraw($charid, $item, $method, $amount=0) {
		$value = $this->getInternalValue($item);//in the future, make this take partials into account when dealing with piles. Currently things that stack have a value of 0 so it doesn't count
		$actor = new Character($this->mysqli, $charid);
		$respect = $this->getOpinion($actor->bodyId, 1);
		if ($respect=="NaN"||$respect<$value) return -1;//You aren't respected enough to claim that
		
		$curTime = new Time($this->mysqli);
		$sql3 = "SELECT `uid` FROM `objects` WHERE `uid`=$item AND `parent`=$this->uid AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return false;//the object doesn't exist
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$takeAmount = round($randomVariance*$amount);
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, 1, $takeAmount);
		}
		else if ($method=="pieces") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $amount, $targetItem->weight);
		}
		else if ($method=="whole") {
			$targetItem = new Obj($this->mysqli, $item);
			$res = $targetItem->checkMethod($method, $targetItem->pieces, $targetItem->weight);
		}
		
		$this->updateOpinion($actor->bodyId, 1, -$value);
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					//reduce pile in group
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . " WHERE `parent`=$this->uid AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->uid AND `uid`=$item AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return false;//The object isn't here
					else {
						//increase pile in inventory
						if ($res["countable"]==1) $sql2 = "UPDATE `objects` SET `pieces`=`pieces`+" . $res["pieces"] . " WHERE `parent`=$actor->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						else $sql2 = "UPDATE `objects` SET `weight`=`weight`+" . $res["weight"] . " WHERE `parent`=$actor->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$pile = new Obj($this->mysqli);
							$result = $pile->create($targetItem->preset, $targetItem->type, $actor->bodyId, 'Generated through pickup', 'NULL', 'NULL', 0, 0, $targetItem->secondary, $res["pieces"], $res["weight"], $curTime->dateTime, $curTime->minute);
							if ($result) return 1;
							else return 0;
						}
						else return 1;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $targetItem->pieces;
			$actualTakeWeight = $targetItem->weight;
			//check if countable
			if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`+$pieces WHERE `parent`=$actor->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			else $sql = "UPDATE `objects` SET `weight`=`weight`+$actualTakeWeight WHERE `parent`=$actor->bodyId AND `presetFK`=$targetItem->preset AND `secondaryFK`=$targetItem->secondary AND `general_type`=5 AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
			//merge with pile if exists, non stackable objects don't get merged if they're not resources
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) {
				//move the whole thing
				$sql = "UPDATE `objects` SET `parent`=$actor->bodyId, `global_x`= NULL, `global_y` = NULL, `local_x`=0, `local_y`=0 WHERE `uid`=$item AND `parent`=$this->uid AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) LIMIT 1";
				$this->mysqli->query($sql);
				if ($this->mysqli->affected_rows==0) return 0;
				else return 1;
			}
			else {
				$r=queryDelete($this->mysqli, "objects", "`uid`=$item AND `parent`=$this->uid AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))", "`uid`", 1);
				if ($r==0) return -2;//duplication bug
				else return 1;
			}
		}
	}
	
	public function checkAP($semi = false, $compare = false) {
		//This function returns the number of AP, unless compared to another number, in which case it returns -2 if current AP is greater or equal to compare, otherwise normal
		//Pay attention that if compare is 0, this will always return the whole amount left instead of -2, so you shouldn't use this if something costs 0 ap
		if (!$semi) $attr = 94;//functional
		else $attr = 95;//semi-functional
		
		$sql = "SELECT `value` FROM `o_attrs` WHERE `objectFK`=$this->uid AND `attributeFK`=$attr ORDER BY `startDt` DESC, `startM` DESC LIMIT 1";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			$row = mysqli_fetch_row($result);
			if (!$compare) return $row[0];
			if ($row[0]>=$compare) {
				return -2;//sufficient
			}
			return $row[0];
		}
		else {
			$result = $this->generateAPpool($attr);
			return $result;
		}
	}
	
	public function generateAPpool($attr) {
		if ($attr==94) $newVal = $this->getFunctionalAP();
		else if ($attr==95) $newVal = $this->getSemiAP();
		else return -1;//This shouldn't be possible, it's only possible if this function is accessed wrong
		
		$obj = new Obj($this->mysqli, $this->uid);
		$obj->setAttribute($attr, $newVal);
		return $newVal;
	}
	
	public function getFunctionalAP() {
		return $this->getAPperAdult()*$this->countMembers("functional adults");
	}
	
	public function getSemiAP() {
		return $this->getAPperHalf()*$this->countMembers("semi-functional");
	}
	
	public function spendAP($num, $semi = false) {
		$result = $this->checkAP($semi, $num);
		if ($result==-2) {
			if (!$semi) $attr = 94;//functional
			else $attr = 95;//semi-functional
			
			$sql = "UPDATE `o_attrs` SET `value`=`value`-$num WHERE `objectFK`=$this->uid AND `attributeFK`=$attr ORDER BY `startDt` DESC, `startM` DESC LIMIT 1";
			$this->mysqli->query($sql);
			if ($this->mysqli->affected_rows==0) return -1;//error
			else return -2;//ok
		}
		return $result;//not enough AP
	}
	
	public function refreshAP() {
		$should_be1 = $this->getFunctionalAP();
		$should_be2 = $this->getSemiAP();
		
		$now1 = $this->checkAP();
		$now2 = $this->checkAP("semi");
		
		$change1 = $should_be1-$now1;
		$change2 = $should_be2-$now2;
		
		$sql = "UPDATE `o_attrs` SET `value`=$should_be1 WHERE `objectFK`=$this->uid AND `attributeFK`=94 ORDER BY `startDt` DESC, `startM` DESC LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) $success1 = false;
		else $success1 = true;
		
		$sql = "UPDATE `o_attrs` SET `value`=$should_be2 WHERE `objectFK`=$this->uid AND `attributeFK`=95 ORDER BY `startDt` DESC, `startM` DESC LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==0) $success2 = false;
		else $success2 = true;
		
		return array(
			"success1" => $success1,
			"success2" => $success2,
			"change1" => $change1,
			"change2" => $change2
			);
	}
	
	public function claimLeadership() {
		
	}
	
	public function hostEvent($eventType) {
		//result:
		//riot
		//total flop
		//not a complete failure
		//moderate success
		//great success
		//tremendous success
		
		//x people showed up
		
		//this raised the group mood by x and morale by y, self-esteem by z
		//the following food items were consumed
		//x people made up their differences
		//x arguments broke out
		//x people made a fool of themselves
		//x fights broke out
		//x people passed out drunk
		//x people had a headache the next morning
		
		//you gained/lost x respect and y loyalty
		//you gained/lost x intimidation points
	}
}
