<?php
session_start();
$gameRoot = "http://otherworld.loc";// online it would be different
$privateRoot = "../_private";

include_once $privateRoot . "/abbr.inc.php";//abbreviations: para($str), ptag($tagname, $contents, [$attr])
include_once "root.inc.php";
include_once $privateRoot . "/conn.inc.php";

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

include $privateRoot."/pages.inc.php";

$mysqli->close();//closes database connection
?>
</div>
</body>
</html>