<?php

	include_once "../_private/generic.inc.php";
	
	if (isset($_GET["x1"])&&isset($_GET["y1"])&&isset($_GET["x2"])&&isset($_GET["y2"])) {
		if (is_numeric($_GET["x1"])&&is_numeric($_GET["y1"])&&is_numeric($_GET["x2"])&&is_numeric($_GET["y2"])) {
			$x1=$_GET["x1"];
			$y1=$_GET["y1"];
			$x2=$_GET["x2"];
			$y2=$_GET["y2"];
			$result = secondEquation($x1, $y1, $x2, $y2);
			echo "m: " . $result["m"] . ", a: " . $result["a"];
		}
	}

?>

<form method='get' action='mathtest.php'>
x1: <input type='number' name='x1' /><br />
y1: <input type='number' name='y1' /><br />
x2: <input type='number' name='x2' /><br />
y2: <input type='number' name='y2' /><br />
<input type='submit' value='Calculate'>
</form>