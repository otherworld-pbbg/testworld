<?php

//Converted to mysqli 9/8/2012

function listPresets($mysqli, $parent) {
	$html_str = "";
	
	$res = $mysqli->query("SELECT `uid`, `name` FROM `o_presets` WHERE `parentFK`=$parent");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
	}
	else {
		while ($row = $res->fetch_object()) {
			$subs = countSubtypes($mysqli, $row->uid);
			$html_str .= ptag("option", "$row->name ($subs)", "value='$row->uid'", "silent");
		}
	}
	$res->close();
	return $html_str;
}

function listAttributes($mysqli, $id, $incl) {
	//if $incl==true, inclusive, for attributes of the preset
	//if $incl==false, exclusive, for attributes the preset doesn't have
	$html_str = "";
	
	if ($incl) $res = $mysqli->query("SELECT `attributeFK`, `name`, `value` FROM `pr_attrs` JOIN `o_attr_types` ON `attributeFK`=`o_attr_types`.`uid` WHERE `o_presetFK`=$id ORDER BY `groupFK`, `o_attr_types`.`uid`");
	else $res = $mysqli->query("SELECT `uid`, `name`, `min`, `max`, `notes` FROM `o_attr_types` WHERE `uid` NOT IN (SELECT `attributeFK` FROM `pr_attrs` WHERE `o_presetFK`=$id) ORDER BY `groupFK`, `o_attr_types`.`uid`");
	if (!$res) para("Query failed: " . $mysqli->error);
	else {
		while ($row = $res->fetch_object())
		{
			if ($incl) $html_str .= ptag("option", "$row->name ($row->value)", "value='$row->attributeFK'", "silent");//this is for a combo box
			else $html_str .= "<tr><td><input type='radio' name='attr' id='attr-$row->uid' value='$row->uid'></td><td>$row->name</td><td>$row->min</td><td>$row->max</td><td>$row->notes</td></tr>\n";//this is for a table
		}
	}
	return $html_str;
}

function countSubtypes($mysqli, $parent) {
	$res = $mysqli->query("SELECT count(`uid`) AS `num` FROM `o_presets` WHERE `parentFK`=$parent");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return "?";
	}
	else {
		$row = $res->fetch_object();
		return $row->num;
	}
}

function addPreset($mysqli, $name,$parent) {
	//Must check that name is not in use before using this function
	$res = $mysqli->query("INSERT INTO `o_presets` (`parentFK`, `name`) VALUES ($parent, '$name')");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		return $mysqli->insert_id;
	}
}

function addAttribute($mysqli, $id, $attribute, $value) {
	$res = $mysqli->query("INSERT INTO `pr_attrs` (`o_presetFK`,`attributeFK`,`value`) VALUES ($id, $attribute, $value)");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

function updateName($mysqli, $id, $newName) {
	$res = $mysqli->query("UPDATE `o_presets` SET `name`='$newName' WHERE `uid`=$id LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

function updateAttribute($mysqli, $id, $attribute, $value) {
	$res = $mysqli->query("UPDATE `pr_attrs` SET `value`=$value WHERE `o_presetFK`=$id AND `attributeFK`=$attribute");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

function changeParent($mysqli, $id,$newParent) {
	$res = $mysqli->query("UPDATE `o_presets` SET `parentFK`=$newParent WHERE `uid`=$id LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

function checkValidity($mysqli, $attribute, $value) {
	$res = $mysqli->query("SELECT `min`, `max` FROM `o_attr_types` WHERE `uid`=$attribute LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		$row = $res->fetch_object();
		if ($value>=$row->min&&$value<=$row->max) return true;
		else return false;
	}
}

function checkName($mysqli, $name)
{
	$res = $mysqli->query("SELECT count(`uid`) AS `num` FROM `o_presets` WHERE `name` LIKE '$name' LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		$row = $res->fetch_object();
		if ($row->num == 0) return true;
		else return false;
	}
}

function checkIDExists($mysqli, $uid)
{
	$res = $mysqli->query("SELECT count(`uid`) AS `num` FROM `o_presets` WHERE `uid`=$uid LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		$row = $res->fetch_object();
		if ($row->num == 1) return true;
		else return false;
	}
}

function checkAttrExists($mysqli, $uid)
{
	$res = $mysqli->query("SELECT count(`uid`) AS `num` FROM `o_attr_types` WHERE `uid`=$uid LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		$row = $res->fetch_object();
		if ($row->num == 1) return true;
		else return false;
	}
}

function getPresetName($mysqli, $uid)
{
	//Do not pass fake ids to this
	$res = $mysqli->query("SELECT `name` FROM `o_presets` WHERE `uid`=$uid LIMIT 1");
	if (!$res) para("Query failed: " . $mysqli->error);
	else {
		$row = $res->fetch_object();
		$res->close();
		return $row->name;
	}
	return "?";
}

function getAttrInfo($mysqli, $attrID)
{
	$returnArray = array(
	"name" => "",
	"min" => 0,
	"max" => 0
	);
	$res = $mysqli->query("SELECT `name`, `min`, `max` FROM `o_attr_types` WHERE `uid`=$attrID LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return false;
	}
	else {
		$row = $res->fetch_object();
		$returnArray["name"] = $row->name;
		$returnArray["min"] = $row->min;
		$returnArray["max"] = $row->max;
		$res->close();
		return $returnArray;
	}
}

function noIDErrors($mysqli, $id)
{
	if ($id==0) para("Error: No preset was selected.");
	else {
		if (!is_numeric($id)) para("Error: Given preset id is not a number. Don't tamper with data.");
		else {
			if (!checkIDExists($mysqli, $id)) para("Error: Selected preset doesn't exist.");
			else return true;
		}
	}
	return false;
}

function getAttrValue($mysqli, $prID, $attrID) {
	$res = $mysqli->query("SELECT `value` FROM `pr_attrs` WHERE `o_presetFK`=$prID AND `attributeFK`=$attrID LIMIT 1");
	if (!$res) {
		para("Query failed: " . $mysqli->error);
		return "error";
	}
	else {
		if ($res->num_rows==0) {
			$res->close();
			return "none";
		}
		else {
			$row = $res->fetch_object();
			$res->close();
			return $row->value;
		}
	}
}

function deleteAttr($mysqli, $prID, $attrID) {
	$res = $mysqli->query("DELETE FROM `pr_attrs` WHERE `o_presetFK`=$prID AND `attributeFK`=$attrID LIMIT 1");
	if (!$res) {
		para("Attribute wasn't found or query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

function deletePreset($mysqli, $prID) {
	$mysqli->query("DELETE FROM `pr_attrs` WHERE `o_presetFK`=$prID");//deletes all attributes first
	$res = $mysqli->query("DELETE FROM `o_presets` WHERE `uid`=$prID LIMIT 1");
	if (!$res) {
		para("Preset wasn't found or query failed: " . $mysqli->error);
		return false;
	}
	else return true;
}

include_once "header.inc.php";
//Get subtypes
if (isset($_POST["getpresets"])&&isset($_POST["obtype"])) {
	$curLevel = $_POST["obtype"];
	if (is_numeric($curLevel) && $curLevel>0) {
		if (!checkIDExists($mysqli, $curLevel)) {
			para("Notice: Given id doesn't exist. Resetting to root.");
			$curLevel = 0;
		}
	}
	else $curLevel = 0;//if negative or NaN, resetting to 0
}
else $curLevel = 0;//also 0 if not set



$attributes = false;
//Saving a change to an existing attribute
if (isset($_POST["updateval"])&&isset($_POST["pid"])&&isset($_POST["newAvalue"])&&isset($_POST["attrid"])) {
	$selected = $_POST["pid"];
	$ok = true;
	if (noIDErrors($mysqli, $selected)) $attributes = true;
	else $ok = false;
	//validate parameters
	if ($ok)
	{
		$attrid = $_POST["attrid"];
		$newAvalue = $_POST["newAvalue"];
		
		if (!is_numeric($attrid)) {
			para("Error: Attribute id is not a number.");
		}
		else {
			$curAvalue = getAttrValue($mysqli, $selected,$attrid);
			if ($curAvalue=="error") {
				//The error message was already printed by the function
			}
			else if ($curAvalue=="none") {
				para("Error: The preset doesn't have this attribute.");
			}
			else if ($curAvalue==$newAvalue) {
				para("The new and old value are the same, so no need to change anything.");
			}
			else if (!is_numeric($newAvalue)) {
				para("Error: The attribute value is not a number");
			}
			else if (!checkValidity($mysqli, $attrid, $newAvalue)) {
				para("Error: The new value is out of bounds.");
			}
			else if (updateAttribute($mysqli, $selected, $attrid, $newAvalue)) {
				para("Attribute value was updated successfully.");
			}
		}
	}
}

$editExisting = false;
//Edit or delete existing attribute
if ( ( isset($_POST["editAttr"])||isset($_POST["delAttr"]) ) &&isset($_POST["pid"])&&isset($_POST["ownedAttrs"])) {
	$selected = $_POST["pid"];
	$ok = true;
	if (noIDErrors($mysqli, $selected)) $attributes = true;
	else $ok = false;
	//validate parameters
	if ($ok)
	{
		$attrid = $_POST["ownedAttrs"];//attribute id
		if (!is_numeric($attrid)) {
			para("Error: Attribute id is not a number.");
		}
		else if (isset($_POST["editAttr"])) {
			//edit attribute
			$curAvalue = getAttrValue($mysqli, $selected,$attrid);
			if ($curAvalue=="error") {
				//The error message was already printed by the function
			}
			else if ($curAvalue=="none") {
				para("Error: The preset doesn't have this attribute.");
			}
			else
			{
				//get $attrname, $attrMin, $attrMax, set $editExisting
				$attrInfo = getAttrInfo($mysqli, $attrid);
				if (!$attrInfo) para("Error: Could not get attribute data.");
				else {
					$attrname = $attrInfo["name"];
					$attrMin = $attrInfo["min"];
					$attrMax = $attrInfo["max"];
					$editExisting = true;
				}
			}
		}
		else {
			//delete attribute
			if ( deleteAttr($mysqli, $selected, $attrid) ) para("Attribute deleted successfully.");
		}

	}
}

//Change name button was clicked
if (isset($_POST["changePName"])&&isset($_POST["npname"])&&isset($_POST["pid"])) {
	$selected = $_POST["pid"];
	if (noIDErrors($mysqli, $selected)) {
		$pname = $mysqli->real_escape_string($_POST["npname"]);//preset name
		if (!checkName($mysqli, $pname)) para("Error: Name is already in use.");
		else if (updateName($mysqli, $selected, $pname)) para("Name was changed successfully.");
		$attributes = true;
	}
}
//Add preset button was clicked, name and radio value sent, combo box and previoius level information
if (isset($_POST["createPreset"])&&isset($_POST["pname"])&&isset($_POST["newPlacement"])&&isset($_POST["obtype"])&&isset($_POST["prevLevel"])) {
	$pname = $mysqli->real_escape_string($_POST["pname"]);//preset name
	$np = $_POST["newPlacement"];//0 -  to this level, 1 - as subtype to selected
	$parentID = $_POST["obtype"];//combo box value for current level
	$prevLevel = $_POST["prevLevel"];//hidden value, needed in case the user wants to add to current level
	$okToAdd = true;
	
	if ($np == "0") {
		//to current level, check prevLevel
		if ($prevLevel!="0") if (!noIDErrors($mysqli, $prevLevel)) $okToAdd = false;//This can be 0
	}
	else if ($np == "1") {
		//to selected level, check selected
		if (!noIDErrors($mysqli, $parentID)) $okToAdd = false;//This can't be 0
	}
	else {
		para("Error: Invalid selection for radio button.");
		$okToAdd = false;
	}
	if ($okToAdd) {
		if (!checkName($mysqli, $pname)) para("Error: Name is already in use.");
		else {
			if ($np == "0") $selected = addPreset($mysqli, $pname, $prevLevel);
			if ($np == "1") $selected = addPreset($mysqli, $pname, $parentID);
			if ($selected) {
				para("New preset was added successfully. Now you can give it attributes.");
				$attributes = true;
			}
		} 
	}

}

//Delete preset button was pressed, combo box value sent
if (isset($_POST["delpreset"])&&isset($_POST["obtype"])) {
	$selected = $_POST["obtype"];
	if (noIDErrors($mysqli, $selected)) {
		if (deletePreset($mysqli, $selected)) {
			para("Preset $selected was deleted successfully.");
			$selected = 0;
		}
	}
}

//Edit preset button was pressed, combo box value sent
if (isset($_POST["editpreset"])&&isset($_POST["obtype"])) {
	$selected = $_POST["obtype"];
	if (noIDErrors($mysqli, $selected)) $attributes = true;
}

//Attribute is to be added, attribute id, value, preset id
if (isset($_POST["addval"])&&isset($_POST["attr"])&&isset($_POST["avalue"])&&isset($_POST["pid"])) {
	$selected = $_POST["pid"];
	$ok = true;
	if (noIDErrors($mysqli, $selected)) $attributes = true;
	else $ok = false;
	//validate parameters
	$avalue = $_POST["avalue"];//attribute value
	$attr = $_POST["attr"];//attribute id
	
	if ($ok&&!is_numeric($avalue)) {
		para("Error: Attribute value is not a number.");
		$ok = false;
	}
	else if ($ok) {
		$avalue = intval($avalue);//just in case someone threw decimals in there
		$ok = true;
	}
	
	if ($ok&&!is_numeric($attr)) {
		para("Error: Attribute id is not a number. Stop tampering with form data.");
		$ok = false;
	}
	else if ($ok) {
		if (!checkAttrExists($mysqli, $attr)) {
			para("Error: Attribute doesn't exist.");
			$ok = false;
		}//otherwise $ok remains true
	}
	
	//process adding
	if ($ok) {
		if (!checkValidity($mysqli, $attr, $avalue)) {
			para("Error: Attribute value is not within bounds.");
			$ok = false;
		}
	}
	
	if ($ok) {
		if (addAttribute($mysqli, $selected, $attr, $avalue)) para("Attribute was added successfully.");
		else para("Failed to add attribute.");	
	}
}

//Edit preset (attributes)
if ($attributes) {
	$currName = getPresetName($mysqli, $selected);
	$possibleDisable2 = "";
	$ownedOptions = listAttributes($mysqli, $selected, true);
	if ($ownedOptions=="") {
		$ownedOptions=ptag("option", "none", "value='0'", "silent");
		$possibleDisable2 = " disabled='disabled'";
	}
	$attrTable = listAttributes($mysqli, $selected, false);
	if ($attrTable=="") {
		$attrTable = "<tr><td>Well, the unexpected has happened and a preset has all the attributes imaginable. This certainly wasn't designed to be used this way.</td></tr>\n";
	}
	else $attrTable = "<tr><th>Selection</th><th>Name</th><th>Lower limit</th><th>Upper limit</th><th>Notes</th></tr>\n" . $attrTable;
	//Editing form
	echo "<form name='editPreset' id='editPreset' action='index.php?page=presetEditor' method='post'>\n";
	echo "<fieldset>";
	ptag("legend", "<h2>Editing preset '$currName' (ID $selected)</h2>");
	ptag("label", "Preset name:", "for='npname'");
	ptag("input", "", "type='text' name='npname' id='npname' value='$currName'");
	ptag("input", "", "type='submit' name='changePName' id='changePName' value='Change'");
	echo "</fieldset><fieldset>";
	ptag("legend", "<h3>Existing attributes:</h3>");
	ptag("label", "Existing attributes:", "for='ownedAttrs'");
	ptag("select", "$ownedOptions", "name='ownedAttrs' id='ownedAttrs'$possibleDisable2");
	ptag("input", "", "type='submit' name='editAttr' id='editAttr' value='Edit'$possibleDisable2");
	ptag("input", "", "type='submit' name='delAttr' id='delAttr' value='Delete'$possibleDisable2");
	echo "</fieldset><fieldset>";
	if ($editExisting) {
		ptag("legend", "<h3>Editing an existing attribute:</h3>");
		ptag("input", "", "type='hidden' name='attrid' id='attrid' value='$attrid'");
		para("Attribute to edit: $attrname, ID: $attrid");
		para("Minimum: $attrMin, Maximum: $attrMax");
		ptag("label", "Attribute value:", "for='newAvalue'");
		ptag("input", "", "type='text' name='newAvalue' id='newAvalue' value='$curAvalue'");
		ptag("input", "", "type='submit' name='updateval' id='updateval' value='Update'");
		echo "</fieldset><fieldset>";
	}
	ptag("legend", "<h3>Possible attributes to add:</h3>");
	ptag("table", "$attrTable");
	ptag("label", "Attribute value:", "for='avalue'");
	ptag("input", "", "type='text' name='avalue' id='avalue' value=''");
	ptag("input", "", "type='hidden' name='pid' id='pid' value='$selected'");
	ptag("input", "", "type='submit' name='addval' id='addval' value='Add attribute'");
	para("Notice: All attribute values must be integers within bounds.");
	echo "</fieldset></form>";
}

$selOptions = listPresets($mysqli, $curLevel);
$possibleDisable = "";
if ($selOptions == "") {
	$selOptions = ptag("option", "--- No subtypes ---", "value='0'", "silent");
	$possibleDisable = " disabled='disabled'";
}
else $selOptions = ptag("option", "--- Select Preset ---", "value='0'", "silent") .$selOptions;

//The form
echo "<form name='selectPreset' id='selectPreset' action='index.php?page=presetEditor' method='post'>\n";
echo "<fieldset>";
ptag("legend", "<h2>Preset editor</h2>");
ptag("label", "Preset type (number of subtypes in parenthesis):", "for='obtype'");
echo "$br";
ptag("select", "$selOptions", "name='obtype' id='obtype'");
ptag("input", "", "type='submit' name='getpresets' id='getpresets' value='Get Subtypes'$possibleDisable");
echo "$br";
ptag("input", "", "type='submit' name='editpreset' id='editpreset' value='Edit Preset'");
ptag("input", "", "type='submit' name='delpreset' id='delpreset' value='Delete Preset'");
echo "</fieldset><fieldset>";
ptag("legend", "<h3>Add new preset</h3>");
echo "<table><tr><td>";
ptag("label", "Preset name:", "for='pname'");
ptag("input", "", "type='text' name='pname' id='pname' value=''");
echo "</td><td>";
ptag("input", "", "type='radio' id='np0' name='newPlacement' value='0'");
ptag("label", "To this level", "for='np0'");
echo "$br";
ptag("input", "", "type='radio' id='np1' name='newPlacement' value='1' checked='checked'");
ptag("label", "As subtype to selected", "for='np1'");
echo "</td><td>";
ptag("input", "", "type='hidden' name='prevLevel' id='prevLevel' value='$curLevel'");
ptag("input", "", "type='submit' name='createPreset' id='createPreset' value='Add'");
echo "</td></tr></table>";

echo "</fieldset></form>";

?>
