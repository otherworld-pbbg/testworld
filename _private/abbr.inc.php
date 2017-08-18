<?php
//abbreviations
//The purpose of these abbreviations is to generally ensure that all tags are closed and followed by a line break so that it won't appear in one line if someone views the source
$br = "<br />\n";

function para($str, $silent="")
{
	$txt = "<p>$str</p>\n";
	//print paragraph
	if ($silent!="silent") echo $txt;
	return $txt;
}

function displayBodywarning() {
	echo '<div class="alert alert-danger">';
	echo "This character doesn't have a body, so it cannot be played.";
	echo "</div>";
}

function ptag($tagname, $contents="", $attr="", $silent="")
{
	//prints html tag with opening and closing tags,
	//contents (if specificied) and possible attributes
	
	$selfclosing = array("area", "base", "basefront", "br", "hr", "input", "img", "link", "meta");//list from w3 schools
		
	if ($attr) $attr = " " . $attr;//add space if there are attributes
	if (in_array($tagname, $selfclosing)) $txt = "<" .$tagname .$attr. " />\n";
	else $txt = "<" .$tagname .$attr. ">" .$contents. "</" .$tagname. ">\n";
	
	if ($silent!="silent") echo $txt;
	return $txt;
}
?>