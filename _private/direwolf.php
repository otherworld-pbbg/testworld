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
	$player = new Player($mysqli, $currentUser);
	include_once "header2.inc.php";
	echo "<div class='bar'>";
	para("Note to testers: When reporting bugs, include the whole error message, address of the page where it appeared and description of what you did right before it happened.");
	
	echo "<ul class='normal'>";
	echo "<li>";
	ptag ("a", "Read the guide", "href='index.php?page=guide' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Known bugs", "href='index.php?page=bugs' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Read plans and comment on them", "href='index.php?page=showplan' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Statistics", "href='index.php?page=statistics' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Start playing a new character", "href='index.php?page=newchar' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Settings", "href='index.php?page=settings' class='clist'");
	echo "</li>";
	
	echo "<li>";
	ptag ("a", "Propose a new culture", "href='index.php?page=newCulture' class='clist'");
	echo "</li>";
	echo "</ul>";
	
	$charArr = $player->getCharacters(1);
	$charArr2 = $player->getCharacters(2);
	ptag ("h2", "Played characters");
	
	if ($charArr)
	{
		echo "<ul class='normal'>";
		for ($i=0; $i<count($charArr); $i++) {
			$curID = $charArr[$i]['cuid'];
			$curname = $charArr[$i]['cname'];
			$curChar = new Character($mysqli, $curID);
			if ($curname==""||$curname==NULL||$curname==" ") $curname="(unnamed)";
			echo "<li>";
			ptag ("a", $curname, "href='index.php?page=viewchar&charid=$curID&userid=$currentUser&tab=5' class='clist'");
			ptag ("a", "[edit memo]", "href='index.php?page=charmemo&charid=$curID' class='smaller'");
			$memo = $curChar->getMemo();
			if (is_array($memo)) {
				echo "<ul class='small_list'>";
				echo "<li>";
				echo $memo["txt"];
				echo "</li>";
				echo "</ul>";
			}
			echo "</li>";
			
		}
		echo "</ul>";
	}
	else para("You don't have any characters.");
	
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
			ptag ("a", $curname, "href='index.php?page=viewchar&charid=$curID2&userid=$currentUser&tab=5' class='clist'");
			$memo = $c2->getMemo();
			if (is_array($memo)) {
				echo "<ul class='small_list'>";
				echo "<li>";
				echo $memo["txt"];
				echo "</li>";
				echo "</ul>";
			}
			echo "</li>";
		}
		echo "</ul>";
	}
	else para("You don't have any watched characters.");
	
	if ($currentUser==1) {
		echo "<p>";
		ptag ("a", "[Go to admin panel]", "href='index.php?page=admin&userid=$currentUser' class='clist'");
		echo "</p>";
	}
	echo "</div>";
}
?>
