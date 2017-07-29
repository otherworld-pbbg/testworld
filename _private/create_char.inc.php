<?php
//Character creation, needs zone 1-4, type 1-3


include_once "class_player.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	if (!isset($_GET["zone"])||!isset($_GET["type"])) {
		header('Location: index.php?page=newchar&userid=' . $currentUser);
	}
	else {
		if (!is_numeric($_GET["zone"])||!is_numeric($_GET["type"])) {
			include_once "header2.inc.php";
			para("A value should be numeric but isn't. Aborting.");
			para("<a href='index.php?page=newchar&userid=$currentUser' class='clist'>[Return to previous page]</a>");
		}
		else {		
			$player = new Player($mysqli, $currentUser);
			$zone = $_GET["zone"];
			$spawntype = $_GET["type"];
			include_once "header2.inc.php";
			echo "<div class='bar'>";
			echo "<p class='right'><a href='index.php?page=newchar&userid=$currentUser' class='clist'>[Return to previous page]</a></p>";
			ptag ("h1", "New character details");
			
			ptag("h2", "Selected zone:");
			if ($zone == 1) para("Tropical");
			else if ($zone == 2) para("Temperate");
			else if ($zone == 3) para("Cold");
			else if ($zone == 4) para("Desert");
			else para("Unknown");
			
			if ($zone!=1) para("You have selected a zone that is unavailable, so you can't start playing there.");
			
			ptag("h2", "Character type:");
			if ($spawntype == 1) para("Adult wanderer");
			else if ($spawntype == 2) para("Child");
			else if ($spawntype == 3) para("Fill a role");
			else para("Unknown");
			
			if ($spawntype == 1&&$zone == 1) {
				echo "<form method='post' action='index.php?page=createchar2' class='narrow' name='charcreate' id='charcreate'>";
				ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
				ptag("h2", "Character details:");
				ptag("h3", "Name");
				echo "<p>";
				ptag("input", "", "type='text' name='name_txt' size='30' maxlength='50'");
				ptag("h3", "Sex");
				echo "</p>";
				echo "<p>";
				ptag("input", "", "type='radio' name='sex' id='sex_male' value='1' checked='checked'");
				ptag("label", "Male", "for='sex_male'");
				ptag("input", "", "type='radio' name='sex' id='sex_female' value='2'");
				ptag("label", "Female", "for='sex_female'");
				ptag("input", "", "type='radio' name='sex' id='sex_neuter' value='3'");
				ptag("label", "Neuter", "for='sex_neuter'");
				echo "</p>";
				ptag("h3", "Starting age");
				echo "<p>";
				echo "<select for='charcreate' name='startage'>";
				for ($i=1;$i<14;$i++) {
					$num = $i+13;
					ptag("option", "$num years old", "value='$i'");
				}
				echo "</select>";
				echo "</p>";
				ptag("input", "", "type='hidden' name='zone' value='$zone'");
				ptag("input", "", "type='hidden' name='type' value='$spawntype'");
				echo "<p class='right'>";
				ptag("input", "", "type='submit' value='Create'");
				echo "</p>";
				echo "</form>";
			}
			else if ($spawntype == 2) {
				para("There are currently no child characters waiting for a player. Check again later.");
			}
			else if ($spawntype == 3) {
				$requests = getActiveRequests($mysqli);
				if ($requests == -1) para("There are currently no roles waiting to be filled. Check again later.");
				else {
					foreach ($requests as $r) {
						$req = $player->getRole($r);
						echo "<form method='post' action='index.php?page=createchar2' class='narrow' name='charcreate' id='charcreate-$r'>";
						ptag("input", "", "type='hidden' name='userid' value='$currentUser'");
						ptag("h3", "Character details:");
						ptag("h4", "Name");
						if ($req["namesel"]==1) {
							para("You get to choose");
							echo "<p>";
							ptag("input", "", "type='text' name='name_txt' size='30' maxlength='50'");
							echo "</p>";
						}
						else {
							para("Preselected (\"" . $req["name"] . "\")");
							ptag("input", "", "type='hidden' name='name_txt' value='" . $req["name"] . "'");
						}
						ptag("h4", "Sex");
						
						if ($req["sex"]==4) {
							para("You get to choose");
							echo "<p>";
							ptag("input", "", "type='radio' name='sex' id='sex_male' value='1' checked='checked'");
							ptag("label", "Male", "for='sex_male'");
							ptag("input", "", "type='radio' name='sex' id='sex_female' value='2'");
							ptag("label", "Female", "for='sex_female'");
							ptag("input", "", "type='radio' name='sex' id='sex_neuter' value='3'");
							ptag("label", "Neuter", "for='sex_neuter'");
							echo "</p>";
						}
						else {
							$arr = array(
								1 => "Male",
								2 => "Female",
								3 => "Neuter"
								);
							para("Preselected (" . $arr[$req["sex"]] . ")");
							ptag("input", "", "type='hidden' name='sex' value='" . $req["sex"] . "'");
						}
						ptag("h4", "Starting age");
						$num = $req["age"]-13;
						para($req["age"] . " years old");
						ptag("input", "", "type='hidden' name='startage' value='$num'");
						
						ptag("h4", "Description:");
						if ($req["desc"]) para($req["desc"]);
						else para("(blank)");
						ptag("h4", "Requests and requirements:");
						if ($req["req"]) para($req["req"]);
						else para("(blank)");
						ptag("h4", "Why you should pick this role:");
						if ($req["why"]) para($req["why"]);
						else para("(blank)");
						
						ptag("input", "", "type='hidden' name='roleid' value='$r'");
						ptag("input", "", "type='hidden' name='zone' value='0'");
						ptag("input", "", "type='hidden' name='type' value='$spawntype'");
						echo "<p class='right'>";
						$rq = new Character($mysqli, $req["requester"]);
						$relation = $rq->checkPermission($currentUser);
						para("Requested by: " . $rq->cname);
						if ($relation == 1) para("(One of your characters)");
						else ptag("input", "", "type='submit' value='Choose'");
						echo "</p>";
						echo "</form>";
					}
				}
			}
			
			echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
			echo "</div>";
		}
	}
}
?>
