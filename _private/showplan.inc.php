<?php
//View plans and comment on them
include_once "class_player.inc.php";
include_once("class_planner.inc.php");
include_once("class_plan.inc.php");


if (isset($_SESSION['user_id'])) {
	$currentUser = $_SESSION['user_id'];
	include_once "header2.inc.php";
}
else {
	$currentUser = 0;
	include_once "header.inc.php";
}
echo "<div class='bar'>";
if ($currentUser>0) echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
else echo "<p class='right'><a href='index.php' class='clist'>[Return to Main Page]</a></p>";
ptag("h1", "Plans");

if (isset($_GET["node"])) {
	if (!is_numeric($_GET["node"])) {
		para("Error loading data.");
	}
	else {
		$p2 = round($_GET["node"]);
		$p = new Plan($mysqli, $p2);
		if ($p->valid) {
			
			if (isset($_POST["comment"])&&$currentUser>0) {
				$com = mysqli_real_escape_string($mysqli, $_POST["comment"]);
				$res = $p->addComment($currentUser, $com);
				if ($res) para("Comment added successfully.");
				else para("Adding comment failed.");
			}
			
			ptag("h2", $p->title);
			para("Posted: " . $p->created . ", edited: " . $p->changed);
			ptag("p", $p->getContents(), "class='longtext'");
			if ($p->countComments()>0) $p->printComments();
			else para("This has no comments yet.");
			
			if ($currentUser>0) {
			
			ptag("h3", "Add comment:");
			echo "<form action='index.php?page=showplan&node=$p2' method='post' name='commentform' id='commentform'>";
			echo "<div class='comment'>";
			ptag("textarea", "", "form='commentform' cols='100' rows='4' name='comment' id='comment'");
?>
<script>
	CKEDITOR.replace( 'comment' );
</script>
<?php
			ptag("input", "", "type='submit' value='Comment'");
			echo "</div>";
			echo "</form>";
			}
			else para("In order to comment, you need to be logged in.");
		}
	}
}

$planner = new Planner($mysqli);

$plans = $planner->getPlans();

if (is_array($plans)) {
	ptag("h2", "List of plans");
	foreach ($plans as $plan) {
		$p = new Plan($mysqli, $plan);
		echo "<p>";
		ptag("a", $p->title, "href='index.php?page=showplan&node=$plan' class='clist'");
		echo "</p>";
		echo "<p>Last changed: " . $p->changed;
		echo " (" . $p->countComments() . " comments)";
		echo "</p>";
	}
}

if ($currentUser>0) echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
else echo "<p class='right'><a href='index.php' class='clist'>[Return to Main Page]</a></p>";

echo "</div>";
?>
