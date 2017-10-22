<?php
//page=listCultures
require_once('../_private/html_form.class.php');
include_once "class_player.inc.php";
//the part that checks if you're logged in

function getList($mysqli, $currentUser) {
	$sql = "SELECT `uid`, `bdesc`, `leader`, `allow`, `status` FROM `pending_culture` WHERE 1 ORDER BY `uid` DESC";
	$res = $mysqli->query($sql);
	if (mysqli_num_rows($res)) {
		while ($row = $res->fetch_object()) {
			echo "<div>";
			ptag("p", "[id: " . $row->uid . "] " . $row->bdesc, "class='longtext'");
			ptag("h4", "Character creation status:");
			switch ($row->allow) {
			case 0:
				para("Not accepting pending characters");
				break;
			case 1:
				para("Accepting pending characters");
				break;
			}
			ptag("h4", "Project status:");
			switch ($row->status) {
			case 0:
				para("Red light (Has some issues)");
				break;
			case 1:
				para("Yellow light (Pending)");
				break;
			case 2:
				para("Green light (Accepted)");
				break;
			default:
				para("something went wrong");
			}
			para("<a href='index.php?page=viewCulture&culid=". $row->uid ."'>[Read more]</a>");
			if ($currentUser == $row->leader) {
				$frm = new HTML_Form();
				$frmStr = $frm->startForm('index.php?page=editCulture', 'post', 'editForm') . PHP_EOL .
				$frm->startTag('p') . 
				'This culture was added by you, so only you can edit it.' .
				$frm->endTag('p') . PHP_EOL .
				$frm->addInput('hidden', 'culid', $row->uid ) . PHP_EOL .
				$frm->startTag('p') . 
				$frm->addInput('submit', 'submit', 'Edit') .
				$frm->endTag('p') . PHP_EOL .
				$frm->endForm();
				echo $frmStr;
			}
			else {
				$lead = new Player($mysqli, $row->leader);
				para("Created by: " . $lead->getUsername());
			}
			
			echo "</div>";
		}
	}
	else {
		para("There are no pending cultures. Why don't you create one of your own?");
	}
}

if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	$player = new Player($mysqli, $currentUser);
	
	include_once "header2.inc.php";
	echo "<div class='bar'>";
	ptag("h1", "Pending cultures");
	getList($mysqli, $currentUser);
	echo "<p class='right'><a href='index.php?page=newCulture' class='clist'>[Propose a different culture]</a></p>";
	echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
	
	echo "</div>";
}
?>