<?php

getChildParts(0,0);

function getChildParts($parentPart, $depth)
{
	$res2 = mysql_query("SELECT `uid`, `name`, `plural`, `multiples` from `bodyparts` WHERE `partof`=$parentPart order by `uid`");
	
	if (!$res2) {
	para("Query failed: " . mysql_error());
	exit;
	}
	
	if ($depth>0) echo "<ul>";
	while ($row2 = mysql_fetch_object($res2))
	{
		if ($depth==0) $h_str = "h2";
		else $h_str = "li";
		
		if ($row2->multiples>1) $h_text = $row2->plural . " (" . $row2->multiples . ")";
		else $h_text = $row2->name;
	
		ptag($h_str, $h_text);
		
		getChildParts($row2->uid, $depth+1);
		
	}
	if ($depth>0) echo "</ul>";

}

?>