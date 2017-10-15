<html>
<head></head>
<body>

<?php

	include_once "../_private/generic.inc.php";
	include_once "../_private/abbr.inc.php";
	
	if (isset($_POST["string"])) {
		$str = $_POST["string"];
	}
	else $str = getRandomPhrase();
	$secure =password_hash($str, PASSWORD_DEFAULT);
	
	para($str . " / " . $secure);
	
	echo "<form method='post' action='testpasscreator.php'>";
	ptag("input", "", "name='string'");
	ptag("input", "", "type='submit' value='Hash'");
	echo "</form>";
?>

</body>
</html>