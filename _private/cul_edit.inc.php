<?php
//page=editCulture
require_once('../_private/html_form.class.php');
include_once "class_player.inc.php";
include_once "class_culturedraft.inc.php";
include_once "generic.inc.php";
//the part that checks if you're logged in
if (!isset($_SESSION['user_id'])) {
		header('Location: index.php?page=login');
}
else
{
	$currentUser = $_SESSION['user_id'];
	$player = new Player($mysqli, $currentUser);
	
	if (isset($_POST["shortdesc"])&&isset($_POST["longdesc"])&&isset($_POST["chargen"])) {
		$bdesc = mysqli_real_escape_string($mysqli, $_POST["shortdesc"]);
		$ldesc = mysqli_real_escape_string($mysqli, $_POST["longdesc"]);
		$allow = setBint($_POST["chargen"], 0, 1, 0);
		if (isset($_POST["addnew"])) {
			if ($_POST["addnew"]==1) {
				//Trying to add new
				$newCulture = new CultureDraft($mysqli);
				$newid = $newCulture->create($bdesc, $ldesc, $currentUser, $allow);
				$_POST["culid"]=$newid;
				
				if ($newid) {
					include_once "header2.inc.php";
					echo "<div class='alert alert-info'>";
					para("Culture draft was created successfully.");
					echo "</div>";
				}
				else {
					include_once "header2.inc.php";
					echo "<div class='alert alert-danger'>";
					para("Something went wrong and culture draft couldn't be saved.");
					echo "</div>";
				}
			}
		}
		if (isset($_POST["save"])&&isset($_POST["culid"])) {
			//Trying to save
			if ($_POST["save"]==1&&is_numeric($_POST["culid"])) {
				$culid = $_POST["culid"];
				$oldCulture = new CultureDraft($mysqli, $culid);
				$leader = $oldCulture->getLeader();
				if ($leader == $currentUser) {
					if ($oldCulture->saveData($bdesc, $ldesc, $allow)) {
						include_once "header2.inc.php";
						echo "<div class='alert alert-info'>";
						para("Changes saved successfully.");
						echo "</div>";
					}
					else {
						include_once "header2.inc.php";
						echo "<div class='alert alert-info'>";
						para("No changes were recorded.");
						echo "</div>";
					}
				}
				else {
					include_once "header2.inc.php";
					echo "<div class='alert alert-danger'>";
					para("You're not authorized to edit this culture.");
					echo "</div>";
				}
			}
		}
	}
	
	include_once "header2.inc.php";
	
	
	
	echo "<div class='bar'>";
	
	if (isset($_POST["culid"])) {
		$culid = $_POST["culid"];
		$oldCulture = new CultureDraft($mysqli, $culid);
		$leader = $oldCulture->getLeader();
		if ($leader == $currentUser) {
			$allow = $oldCulture->getAllow();
			if ($allow) {
				$gen1 = array('id'=>'chargen1', 'checked' => 'checked');
				$gen0 = array('id'=>'chargen0');
			}
			else {
				$gen1 = array('id'=>'chargen1');
				$gen0 = array('id'=>'chargen0', 'checked' => 'checked');
			}
			
			$frm = new HTML_Form();
				//Note that form string is one long string so there are no semicolons until the end
				//If you later add optional attributes, they go in an array
				$frmStr = $frm->startForm('index.php?page=editCulture', 'post', 'editForm') . PHP_EOL .
				
				$frm->startTag('fieldset') . PHP_EOL .
				$frm->startTag('legend') . 'Edit your culture' . $frm->endTag() . PHP_EOL .
				//short description
				$frm->startTag('p') . 
				$frm->addLabelFor('shortdesc', 'Short description: ') .
				$frm->addEmptyTag('br') . PHP_EOL .
				// using html5 placeholder attribute
				$frm->addTextArea('shortdesc', 4, 40, $oldCulture->getBriefDesc(),
						array('id'=>'shortdesc', 'name'=>'shortdesc', 'placeholder'=>'This shows up in the list of pending cultures. Make it your sales pitch.') ) . 
				$frm->endTag('p') . PHP_EOL .
			
				//detailed description
				$frm->startTag('p') . 
				$frm->addLabelFor('longdesc', 'Long description: ') .
				$frm->addEmptyTag('br') . PHP_EOL .
				//This form gets replaced by CKeditor
				$frm->addTextArea('longdesc', 7, 40, $oldCulture->getLongDesc(),
						array('id'=>'longdesc') ) . 
				$frm->endTag('p') . PHP_EOL .
				
				//whether character creation is open
				$frm->startTag('p') . 
				$frm->addLabelFor('chargen', 'Allow players to create pending characters: ') .
				$frm->addEmptyTag('br') . PHP_EOL .
				$frm->addInput('radio', 'chargen', '1', $gen1  ) . ' Yes' . PHP_EOL .
				$frm->addEmptyTag('br') . PHP_EOL .
				$frm->addInput('radio', 'chargen', '0', $gen0  ) . ' No' . 
				$frm->endTag('p') . PHP_EOL .
				
				$frm->addInput('hidden', 'senderid', $currentUser ) . PHP_EOL .
				$frm->addInput('hidden', 'save', '1' ) . PHP_EOL .
				$frm->addInput('hidden', 'culid', $culid ) . PHP_EOL .
				
				$frm->startTag('p') . 
				$frm->addInput('submit', 'submit', 'Save changes') .
				$frm->endTag('p') . PHP_EOL .
				$frm->endForm();
			
			echo $frmStr;
			?>
<script>
	CKEDITOR.replace( 'longdesc' );
</script>
			<?php
		}
		else {
			echo "<div class='alert alert-danger'>";
			para("You're not authorized to edit this culture.");
			echo "</div>";
		}
	}
	echo "<p class='right'><a href='index.php?page=newCulture' class='clist'>[Propose a different culture]</a></p>";
	echo "<p class='right'><a href='index.php?page=listCultures' class='clist'>[Go to the list of cultures]</a></p>";
	echo "<p class='right'><a href='index.php?page=direwolf' class='clist'>[Return to character list]</a></p>";
	echo "</div>";
}
?>
