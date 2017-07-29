<?php
//Character list


include_once "class_player.inc.php";
include_once "class_character.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	if ($currentUser==1) {
		if (isset($_GET["ouser"])) $oUser = round($_GET["ouser"]);
		$player = new Player($mysqli, $currentUser);
		$player2 = new Player($mysqli, $oUser);
		include_once "header2.inc.php";
		echo "<div class='bar'>";
		
		$charArr = $player2->getCharacters(1);
		$charArr2 = $player2->getCharacters(2);
		ptag ("h2", "Characters for " . $player2->username . " (id " . $oUser . ")");
		
		if ($charArr)
		{
			echo "<ul class='normal'>";
			for ($i=0; $i<count($charArr); $i++) {
				$curID = $charArr[$i]['cuid'];
				$curname = $charArr[$i]['cname'];
				$curChar = new Character($mysqli, $curID);
				if ($curname==""||$curname==NULL||$curname==" ") $curname="(unnamed)";
				echo "<li>";
				echo $curname . " (id " . $curID . ")";
				echo " <a href='index.php?page=euthanize&ochar=$curID' class='normal'>[kill]</a>";
				echo "</li>";
			}
			echo "</ul>";
		}
		else para("This player doesn't have any characters.");
		
		ptag ("h2", "Watched characters");
		if ($charArr2)
		{
			echo "<ul class='normal'>";
			for ($i2=0; $i2<count($charArr2); $i2++) {
				$curID2 = $charArr2[$i2]['cuid'];
				$c2 = new Character($mysqli, $curID2);
				$curname = $charArr2[$i2]['cname'];
				if ($curname==""||$curname==NULL||$curname==" ") $curname="(unnamed)";
				echo "<li>";
				echo $curname . " (id " . $curID2 . ")";
				echo "</li>";
			}
			echo "</ul>";
		}
		else para("This player doesn't have any watched characters.");
		
		echo "<p class='right'><a href='index.php?page=adminlog&userid=$currentUser' class='clist'>[Return to Activity log]</a></p>";
		echo "<p class='right'><a href='index.php?page=admin&userid=$currentUser' class='clist'>[Return to Admin panel]</a></p>";
	}
	else {
		include_once "header2.inc.php";
		echo "<div class='bar'>";
		para("You aren't authorized to view this page.");
	}
	echo "<p class='right'><a href='index.php?page=direwolf&userid=$currentUser' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
