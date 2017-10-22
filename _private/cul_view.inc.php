<?php
//page=viewCulture
include_once "class_player.inc.php";
include_once "class_culturedraft.inc.php";
require_once('../_private/html_form.class.php');
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
	
	if (isset($_GET["culid"])) {
		$culid = $_GET["culid"];
		$oldCulture = new CultureDraft($mysqli, $culid);
		$leader = $oldCulture->getLeader();
		$allow = $oldCulture->getAllow();
		
		ptag("h3", "Short description:");
		ptag("p", $oldCulture->getBriefDesc(), "class='longtext'");
		
		ptag("h3", "Detailed description:");
		echo $oldCulture->getLongDesc();
		
		ptag("h4", "Character creation status:");
		switch ($allow) {
		case 0:
			para("Not accepting pending characters");
			break;
		case 1:
			para("Accepting pending characters");
			break;
		}
		ptag("h4", "Project status:");
		para($oldCulture->printStatus());
		
		if ($currentUser == $leader) {
			$frm = new HTML_Form();
			$frmStr = $frm->startForm('index.php?page=editCulture', 'post', 'editForm') . PHP_EOL .
			$frm->startTag('p') . 
			'This culture was added by you, so only you can edit it.' .
			$frm->endTag('p') . PHP_EOL .
			$frm->addInput('hidden', 'culid', $oldCulture->uid ) . PHP_EOL .
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
		
		if ($leader == $currentUser) {
			
		}
		else {
			echo "<div class='alert alert-danger'>";
			para("You're not authorized to edit this culture.");
			echo "</div>";
		}
	}
	else {
	 para("Culture id is undefined, so I don't know what you're trying to view.");
	}
	
	echo "<p class='right'><a href='index.php?page=newCulture' class='clist'>[Propose a different culture]</a></p>";
	echo "<p class='right'><a href='index.php?page=listCultures' class='clist'>[Go to the list of cultures]</a></p>";
	echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>