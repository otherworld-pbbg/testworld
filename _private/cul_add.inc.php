<?php
//page=newCulture
require_once('../_private/html_form.class.php');
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
	$frm = new HTML_Form();
		//Note that form string is one long string so there are no semicolons until the end
		//If you later add optional attributes, they go in an array
		$frmStr = $frm->startForm('index.php?page=editCulture', 'post', 'createForm') . PHP_EOL .
		
		$frm->startTag('fieldset') . PHP_EOL .
		$frm->startTag('legend') . 'Propose a new culture' . $frm->endTag() . PHP_EOL .
		//short description
		$frm->startTag('p') . 
		$frm->addLabelFor('shortdesc', 'Short description: ') .
		$frm->addEmptyTag('br') . PHP_EOL .
		// using html5 placeholder attribute
		$frm->addTextArea('shortdesc', 4, 40, '',
				array('id'=>'shortdesc', 'name'=>'shortdesc', 'placeholder'=>'This shows up in the list of pending cultures. Make it your sales pitch.') ) . 
		$frm->endTag('p') . PHP_EOL .
	
		//detailed description
		$frm->startTag('p') . 
		$frm->addLabelFor('longdesc', 'Long description: ') .
		$frm->addEmptyTag('br') . PHP_EOL .
		//This form gets replaced by CKeditor
		$frm->addTextArea('longdesc', 7, 40, '',
				array('id'=>'longdesc') ) . 
		$frm->endTag('p') . PHP_EOL .
		
		//whether character creation is open
		$frm->startTag('p') . 
		$frm->addLabelFor('chargen', 'Allow players to create pending characters: ') .
		$frm->addEmptyTag('br') . PHP_EOL .
		$frm->addInput('radio', 'chargen', '1', array('id'=>'chargen')  ) . ' Yes' . PHP_EOL .
		$frm->addEmptyTag('br') . PHP_EOL .
		$frm->addInput('radio', 'chargen', '0', array('checked' => 'checked')) . ' Not yet' . 
		$frm->endTag('p') . PHP_EOL .
		
		$frm->addInput('hidden', 'senderid', $currentUser ) . PHP_EOL .
		$frm->addInput('hidden', 'addnew', '1' ) . PHP_EOL .
		
		$frm->startTag('p') . 
		$frm->addInput('submit', 'submit', 'Add') .
		$frm->endTag() . PHP_EOL .
		$frm->endForm();
	
	echo $frmStr;
	echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
<script>
	CKEDITOR.replace( 'longdesc' );
</script>