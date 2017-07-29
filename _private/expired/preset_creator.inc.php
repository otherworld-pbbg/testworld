<!--Script found: http://stackoverflow.com/questions/5395235/change-text-of-corresponding-label-when-checkbox-is-checked-unchecked -->
<script type="text/javascript">
function changeLabel(thecheckbox, thelabel) {

    var checkboxvar = document.getElementById(thecheckbox);
    var labelvar = document.getElementById(thelabel);
    if (!checkboxvar.checked) {
    	    labelvar.innerHTML = "Preset name:";
    }
    else {
    	    labelvar.innerHTML = "Type name:";
    }
}

</script>

<?php
$addingAttributes = false;
$adding = false;
$current = false;
$uid = false;
if (isset( $_POST["addval"])) $adding = $_POST["addval"];//isset avoids a notice if it's not set
if ($adding) {
	if (isset( $_POST["avalue"])) {
		$avalue = $_POST["avalue"];
		if ($avalue!="" && !is_numeric($avalue)) {
			echo "Error: Value must be numeric or blank.";
			exit();
		}
	}
	if (is_numeric($_POST["h_id"])) $uid = $_POST["h_id"];
	else {
		para("Error: This is not a valid id. Don't tamper with form data.");
		exit();
	}
	if ($_POST["h_type"]=='type'||$_POST["h_type"]=='preset') $current = $_POST["h_type"];
	else {
		para("Error: That's not a type or a preset. Don't tamper with form data.");
		exit();
	}
	if (!is_numeric( $_POST["attr"])) {
		echo "Error: All selections have numeric values. Stop tampering with data.";
		exit();
	}
	else
	{
		$attr = $_POST["attr"];
		$res = mysql_query("SELECT `min`, `max` FROM o_attr_types WHERE `uid`=$attr LIMIT 1");
		$attrtype = mysql_fetch_object($res);
		if ($avalue!=""&&($avalue<$attrtype->min||$avalue>$attrtype->max)) {
			para("Error: Value doesn't fall between $attrtype->min and $attrtype->max.");
			exit();
		}
	}
	if ($current=="type") $table = "ot_attrs";
	if ($current=="preset") $table = "pr_attrs";
	if ($avalue=="") $avalue = "NULL";
	
	$res = mysql_query("INSERT INTO `$table` VALUES ($uid, $attr, $avalue)");
	if (!$res) {
		para("Query failed: " . mysql_error());
	}
	else {
		para("Attribute added successfully.");
		$addingAttributes = true;
		//now we need to get the name again because it doesn't get sent
		if ($current=="type") $table = "o_types";
		if ($current=="preset") $table = "o_presets";
		$res2 = mysql_query("SELECT `name` FROM `$table` WHERE `uid`=$uid LIMIT 1");
		if (!$res2) {
			para("Query failed: " . mysql_error());
			$addingAttributes = false;
		}
		else {
			$typeObject = mysql_fetch_object($res2);
			$pname = $typeObject->name;
		}
	}
}

$submitted = false;//this is for submitting the creation form
$success = false;//this is for creation success

if (isset( $_POST["create"])) $submitted = $_POST["create"];//isset avoids a notice if it's not set
if ($submitted) {
	if (isset($_POST["pname"])) $pname = mysql_real_escape_string($_POST["pname"]);
	else $pname = false;
	if (!$pname) para("Error: No name was specified. All types and presets must have a name.");
	else {
		$boxChecked = false;
		if (isset($_POST["newtype"])) $boxChecked = $_POST["newtype"];
		if ($boxChecked) {
			para("Attempting to create a new type, '$pname'.");
			$res2 = mysql_query("SELECT `name` FROM `o_types` WHERE `name`='$pname' LIMIT 1");
				if (!$res2) {
					para("Query failed: " . mysql_error());
				}
				else {
					$typeObject = mysql_fetch_object($res2);
					if ($typeObject) para("Error: '" . $typeObject->name . "' already exists! Type names must be unique.");
					else {
						$res=mysql_query("INSERT INTO `o_types` VALUES (NULL, '$pname', '0')");
						if ($res) {
						para("Type created successfully.");
						$success = true;
						$current = "type";
						$uid =  mysql_insert_id();
						}
						else para("Query failed: " . mysql_error());
					}
				}
		}
		else {
			para("Attempting to create a new preset, '$pname'.");
			$objectType = $_POST["obtype"];
			if (is_numeric($objectType)) {
				$res2 = mysql_query("SELECT `uid`, `name` FROM `o_types` WHERE `uid`=$objectType LIMIT 1");
				if (!$res2) {
					para("Query failed: " . mysql_error());
				}
				else {
					$typeObject = mysql_fetch_object($res2);
					if ($typeObject) {
						$res=mysql_query("INSERT INTO `o_presets` VALUES (NULL, $objectType, '$pname', '0', 'NULL')");
						if ($res) {
						para("Preset created successfully.");
						$success = true;
						$current = "preset";
						$uid =  mysql_insert_id();
						}
						else para("Query failed: " . mysql_error());
					}
					else para("Error: Presets must represent one of the existing types. You need to select a type from the list or create a new one of the existing ones would work.");
				}
			}
			else para("Please do not tamper with form data, it makes the code monkey cry. :(");
		}
	}
	
}
//editing a type selected from a list
if (isset($_POST["edittype"]))
{
	$addingAttributes = true;
	if (is_numeric($_POST["obtype2"])) $uid = $_POST["obtype2"];
	else {
		para("Error: This is not a valid id. Don't tamper with form data.");
		exit();
	}
	
	$res2 = mysql_query("SELECT `name` FROM `o_types` WHERE `uid`=$uid LIMIT 1");
		if (!$res2) {
			para("Query failed: " . mysql_error());
			$addingAttributes = false;
		}
		else {
			$typeObject = mysql_fetch_object($res2);
			$pname = $typeObject->name;
			$current = "type";
		}
}
// editing a preset selected from the list
if (isset($_POST["editpreset"]))
{
	$addingAttributes = true;
	if (is_numeric($_POST["prtype"])) $uid = $_POST["prtype"];
	else {
		para("Error: This is not a valid id. Don't tamper with form data.");
		exit();
	}
	
	$res2 = mysql_query("SELECT `name` FROM `o_presets` WHERE `uid`=$uid LIMIT 1");
		if (!$res2) {
			para("Query failed: " . mysql_error());
			$addingAttributes = false;
		}
		else {
			$typeObject = mysql_fetch_object($res2);
			$pname = $typeObject->name;
			$current = "preset";
		}
}

if ($success||$addingAttributes) {
	if ($current=="type") $res = mysql_query("SELECT `attributeFK`, oat.`name`, `value` FROM `ot_attrs` JOIN `o_attr_types` oat ON `attributeFK`=oat.`uid` WHERE o_typeFK=$uid");
	if ($current=="preset") $res = mysql_query("SELECT `attributeFK`, oat.`name`, `value` FROM `pr_attrs` JOIN `o_attr_types` oat ON `attributeFK`=oat.`uid` WHERE `o_presetFK`=$uid");
		
	if (!$res) {
		para("Query failed: " . mysql_error());
		exit;
	}
	$attrsOwned = "";
	while ($row = mysql_fetch_array($res))
	{
		$attrsOwned .= ptag("option", "$row[1] ($row[2])", "value='$row[0]'", "silent");
	}
	
	if ($current=="type") $res2 = mysql_query("SELECT `uid`, `name`, `min`, `max`, `notes` FROM `o_attr_types` oat WHERE `uid` NOT IN (SELECT `attributeFK` FROM `ot_attrs` WHERE `o_typeFK`=$uid) ORDER BY `name`");
	if ($current=="preset") $res2 = mysql_query("SELECT `uid`, `name`, `min`, `max`, `notes` FROM `o_attr_types` WHERE `uid` NOT IN (SELECT `attributeFK` FROM `pr_attrs` WHERE `o_presetFK`=$uid) ORDER BY `name`");
		
	if (!$res2) {
		para("Query failed: " . mysql_error());
		exit;
	}
	$attrTable = "<tr><th>Selection</th><th>Name</th><th>Lower limit</th><th>Upper limit</th><th>Notes</th></tr>";
	while ($row = mysql_fetch_object($res2))
	{
		$attrTable .= "<tr><td><input type='radio' name='attr' id='attr' value='$row->uid'></td><td>$row->name</td><td>$row->min</td><td>$row->max</td><td>$row->notes</td></tr>\n";
	}
	
	echo "<form name='addAttributes' id='addAttributes' action='index.php?page=presetCreator' method='post'>\n";
	echo "<fieldset>\n";
	ptag("legend", "<h2>Add attributes</h2>");
	para("ID: $uid");
	ptag("input", "", "type='hidden' name='h_id' id='h_id' value='$uid'");
	para("Name: $pname");
	para("Type: $current");
	ptag("input", "", "type='hidden' name='h_type' id='h_type' value='$current'");
	ptag("label", "Existing attributes and their values: ", "for='attrsOwned'");
	ptag("select", "$attrsOwned", "name='attrsOwned' id='attrsOwned'");
	echo "$br";
	ptag("label", "Possible attributes to add: ", "for='attrsOwned'");
	ptag("table", "$attrTable");
	ptag("label", "Attribute value:", "for='avalue'");
	ptag("input", "", "type='text' name='avalue' id='avalue' value=''");
	ptag("input", "", "type='submit' name='addval' id='addval' value='Add attribute'");
	echo "</fieldset></form>";
}

$selOptions = ptag("option", "--- Select type ---", "value='0' selected='selected'", "silent");
$selOptions2 = ptag("option", "(All types)", "value='0' selected='selected'", "silent");//this is for the edit list

$res = mysql_query("SELECT ot.`uid` as `typeid`, ot.`name` as `typename`, count(op.`uid`) as `presets` FROM `o_types` ot LEFT JOIN `o_presets` op ON ot.`uid`=op.`o_typeFK` GROUP BY ot.`uid` ORDER BY ot.`uid`");

if (!$res) {
	para("Query failed: " . mysql_error());
	exit;
}

echo "<form name='createPreset' id='createPreset' action='index.php?page=presetCreator' method='post'>\n";
echo "<fieldset>";

ptag("legend", "<h2>Preset creator</h2>");
while ($row = mysql_fetch_object($res))
{
	$selOptions .= ptag("option", "$row->typename ($row->presets)", "value='$row->typeid'", "silent");
	$selOptions2 .= ptag("option", "$row->typename ($row->presets)", "value='$row->typeid'", "silent");
}


ptag("label", "Object type:", "for='obtype'");
ptag("select", "$selOptions", "name='obtype' id='obtype'");
ptag("label", " or create a new type: ", "for='newtype'");
ptag("input", "", "type='checkbox' name='newtype' id='newtype' onclick=\"changeLabel('newtype','pnamelabel');\" ");
echo "$br";
ptag("label", "Preset name:", "for='pname' id='pnamelabel'");
ptag("input", "", "type='text' name='pname' id='pname' value=''");
ptag("input", "", "type='submit' name='create' id='create' value='Create'");
para("Notice: Object types should be kept as generic as possible. For example, a body part is a type, a hand or a head is a preset.");
echo "</fieldset></form>";



echo "<form name='selectType' id='selectType' action='index.php?page=presetCreator' method='post'>\n";
echo "<fieldset>";
ptag("legend", "<h2>Edit a type</h2>");
para("Select an existing type for editing or get the presets.");
ptag("label", "Object type:", "for='obtype2'");
ptag("select", "$selOptions2", "name='obtype2' id='obtype2'");
ptag("input", "", "type='submit' name='edittype' id='edittype' value='Edit Type'");
ptag("input", "", "type='submit' name='getpresets' id='getpresets' value='Get Presets'");
echo "</fieldset></form>";

if (isset($_POST["getpresets"]))
{
	$selType = $_POST["obtype2"];
	if (!is_numeric($selType)) {
		para("Please stop dicking with form data! :(");
		exit();
	}
	else {
		if ($selType == 0) $res = mysql_query("SELECT `uid`, `name` FROM `o_presets` WHERE 1 ORDER BY `name`");
		else $res = mysql_query("SELECT `uid`, `name` FROM `o_presets` WHERE `o_typeFK`=$selType ORDER BY `name`");
		if (!$res) {
		para("Query failed: " . mysql_error());
		exit;
		}
		if (mysql_num_rows($res)==0) {
			para("There are no presets of the selected type.");
			exit();
		}
		$selOptions3 = "";
		while ($row = mysql_fetch_object($res))
		{
			$selOptions3 .= ptag("option", "$row->name", "value='$row->uid'", "silent");
		}
	}
	echo "<form name='selectPreset' id='selectPreset' action='index.php?page=presetCreator' method='post'>\n";
	echo "<fieldset>";
	ptag("legend", "<h2>Edit a preset</h2>");
	para("Select an existing preset for editing.");
	ptag("label", "Preset type:", "for='prtype'");
	ptag("select", "$selOptions3", "name='prtype' id='prtype'");
	ptag("input", "", "type='submit' name='editpreset' id='editpreset' value='Edit Preset'");
	echo "</fieldset></form>";
}
?>
