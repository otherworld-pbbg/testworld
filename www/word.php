<?php

include_once "../_private/generic.inc.php";
include_once "../_private/header.inc.php";

$arr_v_begin = array();
$arr_v_middle = array();
$arr_v_middle2 = array();
$arr_v_end = array();
$arr_c_begin = array();
$arr_c_middle = array();
$arr_c_middle2 = array();
$arr_c_end = array();

if (isset($_POST["pattern"])) {
	$pattern = $_POST["pattern"];
}
else $pattern = "sime";

if (isset($_POST["num"])) {
	$num = $_POST["num"];
}
else $num = 1;

if (isset($_POST["v_l"])&&isset($_POST["c_l"])) {
	if (is_numeric($_POST["v_l"])&&is_numeric($_POST["c_l"])) {
		for ($i = 0; $i<$_POST["v_l"]; $i++) {
			$arr_v_begin[] = isset($_POST["v_begin_" . $i]) ? $_POST["v_begin_" . $i] : 1;
			$arr_v_middle[] = isset($_POST["v_middle_" . $i]) ? $_POST["v_middle_" . $i] : 1;
			$arr_v_middle2[] = isset($_POST["v_middle2_" . $i]) ? $_POST["v_middle2_" . $i] : 1;
			$arr_v_end[] = isset($_POST["v_end_" . $i]) ? $_POST["v_end_" . $i] : 1;
		}
		
		for ($i = 0; $i<$_POST["c_l"]; $i++) {
			$arr_c_begin[] = isset($_POST["c_begin_" . $i]) ? $_POST["c_begin_" . $i] : 1;
			$arr_c_middle[] = isset($_POST["c_middle_" . $i]) ? $_POST["c_middle_" . $i] : 1;
			$arr_c_middle2[] = isset($_POST["c_middle2_" . $i]) ? $_POST["c_middle2_" . $i] : 1;
			$arr_c_end[] = isset($_POST["c_end_" . $i]) ? $_POST["c_end_" . $i] : 1;
		}
		
		$pos = createLetterPools($arr_v_begin, $arr_v_middle, $arr_v_middle2, $arr_v_end, $arr_c_begin, $arr_c_middle, $arr_c_middle2, $arr_c_end);
		generateWords($pos, $pattern, $num);
	}
}

printLetterForm($num, $pattern, $arr_v_begin, $arr_v_middle, $arr_v_middle2, $arr_v_end, $arr_c_begin, $arr_c_middle, $arr_c_middle2, $arr_c_end);



?>
