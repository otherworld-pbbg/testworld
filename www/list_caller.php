<?
include_once("../_private/class_build_menu2.inc.php");
include_once("../_private/conn.inc.php");

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}



// what branch was requested?
$branch = isset($_GET['branch']) ? $_GET['branch'] : null;
$userId = isset($_GET['userid']) ? $_GET['userid'] : null;
$charId = isset($_GET['charid']) ? $_GET['charid'] : null;

$BuildMenu = new BuildMenu2($mysqli, $userId, $charId);
$BuildMenu->json($branch);

?>
