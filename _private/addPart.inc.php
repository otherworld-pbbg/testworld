<?
//this needs the following post variables: pid, charid, userid, slot, preset

include_once "class_player.inc.php";
include_once "class_character.inc.php";
include_once("class_project.inc.php");
include_once("class_resource.inc.php");
include_once("class_preset.inc.php");

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
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {			
			if ($watcherRole>1) {
				include_once "header2.inc.php";
				para("You cannot add stuff to projects on someone else's behalf when you're a watcher.");
				para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activies]</a>");
			}
			else {
				if (!isset($_GET["pid"])||!isset($_GET["slot"])||!isset($_GET["preset"])) {
					header('Location: index.php?page=viewchar&charid=' . $charcheck . '&userid=' . $currentUser . '&tab=8');
				}
				else {
					if (!is_numeric($_GET["pid"])||!is_numeric($_GET["slot"])||!is_numeric($_GET["preset"])) {
						include_once "header2.inc.php";
						para("A value should be numeric but isn't. Aborting.");
						para("<a href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'>[Return to Activities]</a>");
					}
					else {
						include_once "header2.inc.php";
						echo "<div class='displayarea'>";
						ptag("h1", "Select part to add");
						$entry = new Project($mysqli, $_GET["pid"], $curChar->uid, $currentUser);
						$info = $entry->getAddableComponents($_GET["slot"], $_GET["preset"]);
						if ($info==-1) para("This project already has progress so it apparently has all it needs.");
						else if ($info==-2) para("There's no need for this object type, at least not in this slot.");
						else if ($info==-3) para("Another type has been picked for this slot. If you want to use this type instead, you need to remove the other type first.");
						else if ($info==-4) para("This slot is already full.");
						else {
							$preset = new Preset($mysqli, $_GET["preset"]);
							$preset->loadData();
							echo "<form method='get' action='index.php' class='narrow'>";
							ptag("h2", "Needed amount");
							para("This needs " . $info["need_p"] . " x $preset->name.");
							$sources = $info["sources"];
							if (!$sources) para("There doesn't seem to be any of this type here. Maybe you need to manufacture it first, or check in containers.");
							else {
								ptag("h2", "Available components of this type");
								echo "<ul>";
								$selected = "checked='checked'";
								foreach ($sources as $source) {
									$no = new Obj($mysqli, $source["uid"]);
									$no->getBasicData();
									echo "<li>";
									ptag("input", "", "type='radio' name='source' value='".$source["uid"] . "' $selected");
									ptag("label", $no->getName() . " ". $source["pieces"] . " x (" . $source["source"] . ")", "for='sel'");
									$selected = "";
									echo "</li>";
								}
								echo "</ul>";
								para("If more than one is needed and the component doesn't stack, you will need to add them one by one.");
							}
							ptag("input", "", "type='hidden' name='charid' value='$curChar->uid'");
							ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
							ptag("input", "", "type='hidden' name='pid' value='" . $_GET["pid"] . "'");
							ptag("input", "", "type='hidden' name='slot' value='" . $_GET["slot"] . "'");
							ptag("input", "", "type='hidden' name='preset' value='" . $_GET["preset"] . "'");
							ptag("input", "", "type='hidden' name='page' value='addPart2'");
							echo "<p class='right'>";
							if ($sources) ptag("input", "", "type='submit' value='Add selected'");
							echo "</p>";
							echo "</form>";
						}
						echo "<p class='right'>";
						ptag("a", "[Return to Activities]", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=2' class='clist'");
						echo "</p>";
						echo "</div>";
					}
				}
				
			}
		}
	}
}
?>
