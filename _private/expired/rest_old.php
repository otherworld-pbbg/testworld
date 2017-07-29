<?
echo "<form name='restform' id='restform' action='index.php?page=rest' method='post' class='narrow'>";
			para("Select how long you want to rest. There are 12 hours in a day. In the future having a bed increases the AP gain.");
			echo "<p>";
			ptag("label", "Hours: ", "for='hours'");
			echo "<select name='hours' form='restform'>";
			ptag("option", "0 hours", "value='0' selected='selected'");
			ptag("option", "1 hour", "value='1'");
			ptag("option", "2 hours", "value='2'");
			ptag("option", "3 hours", "value='3'");
			ptag("option", "4 hours", "value='4'");
			ptag("option", "5 hours", "value='5'");
			ptag("option", "6 hours", "value='6'");
			echo "</select></p>";
			
			echo "<p>";
			ptag("label", "Minutes: ", "for='minutes'");
			echo "<select name='minutes' form='restform'>";
			ptag("option", "0 minutes", "value='0' selected='selected'");
			ptag("option", "5 minutes", "value='5'");
			ptag("option", "10 minutes", "value='10'");
			ptag("option", "15 minutes", "value='15'");
			ptag("option", "20 minutes", "value='20'");
			ptag("option", "30 minutes", "value='30'");
			ptag("option", "45 minutes", "value='45'");
			echo "</select></p>";
			ptag("input" , "", "type='hidden' name='charid' value='$charcheck'");
			ptag("input" , "", "type='hidden' name='userid' value='$currentUser'");
			echo "<p class='right'>";
			ptag("input", "", "type='submit' value='Rest'");
			echo "</p>\n";	
			echo "</form>\n";
?>
