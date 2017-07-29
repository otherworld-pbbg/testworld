<?
include_once "class_character.inc.php";
include_once ("generic.inc.php");

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
	
	if ($watcherRole>1) {
		include_once "header2.inc.php";
		ptag("p", "Disclaimer: You are a watcher, so you cannot submit this form on behalf of the player.");
	}
	
	if ($watcherRole<1) header('Location: index.php?page=direwolf&userid=' . $currentUser);
	else {
		//user is authorized to view this character
		$bodyId = $curChar->getBasicData();
		if ($bodyId == -1) {
			include_once "header2.inc.php";
			echo "This character doesn't have a body so it cannot be played.";
		}
		else {
			include_once "header2.inc.php";
			echo "<div class='bar'>\n";
			echo "<div class='left_header'>\n";
			ptag("h4", "Current character:");
			para($curChar->cname);
			echo "</div></div>";
			
			$areq = $curChar->getActiveRequest();
			if (is_array($areq)) {
				$form_uid = $areq["uid"];
				$form_sex = $areq["sex"];
				$form_age = $areq["age"]-13;
				$form_namesel = $areq["namesel"];
				$form_name = cleanup($areq["name"]);
				$form_desc = cleanup($areq["desc"]);
				$form_req = cleanup($areq["req"]);
				$form_why = cleanup($areq["why"]);
				$exists = true;
			}
			else {
				$form_uid = 0;
				$form_sex = 4;
				$form_age = 1;
				$form_namesel = 1;
				$form_name = "";
				$form_desc = "";
				$form_req = "";
				$form_why = "";
				$exists = false;
			}
			
			//cleanup();//Use this for any string that gets placed in a text field
			echo "<div class='bar'>\n";
			if ($exists) ptag("h1", "Update your role request");
			else ptag("h1", "Request a role");
			echo "<form action='index.php?page=requestRole' method='post' class='narrow' id='roleform'>";
			ptag("h3", "Name");
			echo "<p>";
			$arr = array(
				1 => "Let the player decide",
				0 => "Preset:"
				);
			for ($i=1;$i>-1;$i--) {
				if ($i == $form_namesel) $sel = " checked='checked'";
				else $sel = "";
				ptag("input", "", "type='radio' name='namesel' id='namesel-$i' value='$i'$sel");
				ptag("label", $arr[$i], "for='namesel-$i'");
				if ($i == 1) echo "<br />";
			}
			ptag("input", "", "type='text' length='50' maxlength='50' name='name_txt' id='name-txt' value='$form_name'");
			echo "</p>";
			ptag("h3", "Sex");
			$arr2 = array(
				1 => "Male",
				2 =>"Female",
				3 => "Neuter",
				4 => "Let the player decide"
				);
			echo "<p>";
			for ($i=1;$i<5;$i++) {
				if ($i == $form_sex) $sel = " checked='checked'";
				else $sel = "";
				ptag("input", "", "type='radio' name='sex' id='sexsel-$i' value='$i'$sel");
				ptag("label", $arr2[$i], "for='namesel-$i'");
				echo "<br />";
			}
			echo "</p>";
			echo "<p>";
			ptag("label", "Starting age", "for='age'");
			echo "<br />";
			echo "<select for='roleform' name='startage' id='age'>";
			for ($i=1;$i<14;$i++) {
				$num = $i+13;
				if ($i == $form_age) $sel = " selected='selected'";
				else $sel = "";
				ptag("option", "$num years old", "value='$i'$sel");
			}
			echo "</select>";
			echo "</p>";
			para("It is recommended that you fill out all the following fields.");
			echo "<p>";
			ptag("label", "Description", "for='desc'");
			echo "<br />";
			ptag("textarea", $form_desc, "name='desc' id='desc' form='roleform' rows='4' cols='60'");
			echo "<br />";
			ptag("label", "Requests and Requirements", "for='req' form='roleform'");
			echo "<br />";
			ptag("textarea", $form_req, "name='req' id='req' rows='4' cols='60'");
			echo "<br />";
			ptag("label", "Why you should pick this character", "for='why' form='roleform'");
			echo "<br />";
			ptag("textarea", $form_why, "name='why' id='why' rows='4' cols='60'");
			echo "</p>";
			para("This is your sales pitch for why someone should join you instead of someone else. You can for example promise to give them equipment.");
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Save changes'");
			echo "</p>";
			echo "</form>";
			
			if ($exists) {
				echo "<form action='index.php?page=deleteRequest' method='post' class='narrow' id='delform'>";
				ptag("h2", "Delete existing request");
				para("There is no confirmation page, so if you click this, the deletion is instantaneous.");
				ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='I understand'");
				echo "</p>";
				echo "</form>";
			}
			
			echo "<p class='right'>";
			ptag("a", "Return to groups", "href='index.php?page=viewchar&charid=" . $charcheck . "&userid=" . $currentUser . "&tab=11' class='clist'");
			echo "</p>";
			echo "</div>";
		}
	}
}
?>
