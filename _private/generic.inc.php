<?php

include_once "class_player.inc.php";
include_once "abbr.inc.php";

function csvIntoArray($filename, $delimiter=';') {

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE)
	{
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
		{
			if(!$header)
				$header = $row;
			else
			$data[] = array_combine($header, $row);
		}
		fclose($handle);
	}
	return $data;
}

function searchByField($multi_array, $field, $value)
{
	$retArr = array();
	foreach($multi_array as $key => $sub_array)
	{
		if ($sub_array[$field] == $value) {
			$retArr[] = $multi_array[$key];
		}
	}
	return $retArr;
}

function searchSingle($multi_array, $field, $value)
{
	if (!is_array($multi_array)) return false;
	foreach($multi_array as $key => $sub_array)
	{
		if ($sub_array[$field] == $value) {
			return $multi_array[$key];
		}
	}
	return false;
}

function cleanup($string) {
	$string = str_replace("'","&#39;", $string);
	$string = str_replace("'","&#34;", $string);
	return $string;
}

function arrayToComma($array) {
	$str = "";
	foreach ($array as $item) {
		if ($str != "") $str .= ", ";
		$str .= $item;
	}
	return $str;
}

function checkHex($color) {
	//Check for a hex color string '#c1c2b4'
	if (preg_match('/^#[a-f0-9]{6}$/i', $color))
	{
		return $color;//Color is valid
	}
	else if (preg_match('/^[a-f0-9]{6}$/i', $color))
	{
		return '#' . $color;//Add the hash sign, then return
	}
	else return false;
}

function getColorSpan($level, $text="") {
	if ($text=="") return "<span class='level" . $level . "'>" . $level . "</span>";
	return "<span class='level" . $level . "'>" . $text . "</span>";
}

function countSpiral($step) {
	$sequence = ceil(sqrt($step));//1 =1, 2...4 = 2, 5...9 = 3, 10...16 = 4
	$change = $sequence % 2;
	
	if ($change==0) {
		//even, these aren't final because they don't account for the upright part yet. That's calculated below
		$ychange = floor($sequence/2);
	}
	else {
		//odd
		$ychange = floor(($sequence-1)/2) * -1;
	}
	
	$straight_part = $sequence+1;
	$upright_part = $sequence-2;
	
	$behind = pow($sequence, 2) - $step;
	
	if ($behind<$straight_part) {
		//The x is in the straight part
		if ($ychange<0) $xchange = $ychange*-1 - $behind + 1;
		else if ($ychange>0) $xchange = $ychange*-1 + $behind;
		else $xchange = 1 - $behind;//This takes step 0 into account
	}
	else {
		$xchange = $ychange;
		//The x is in the upright part
		if ($ychange>0) {
			$ychange-=$behind-$straight_part+1;
		}
		else if ($ychange<0) {
			$ychange+=$behind-$straight_part+1;
		}
	}
	
	return array(
		"xchange" => $xchange,
		"ychange" => $ychange
		);
}

function aasort (&$array, $key) {
	//http://stackoverflow.com/questions/2699086/sort-multi-dimensional-array-by-value
	//aasort($your_array,"column");
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}

function getActivityLog($mysqli, $limit=100) {
	$retArr = array();
	$sql = "SELECT `userFK` as `user`, `timestamp` as `time` FROM `activity_log` ORDER BY `uid` DESC LIMIT $limit";
	$res = $mysqli->query($sql);
	if (mysqli_num_rows($res)) {
		while ($row = mysqli_fetch_assoc($res)) {
			$retArr[] = $row;
		}
		return $retArr;
	}
	else return -1;
}

function printActivityLog($mysqli, $log) {
	foreach ($log as $entry) {
		$p = new Player($mysqli, $entry["user"]);
		echo "<p>";
		echo "<a href='index.php?page=viewPlayer&ouser=" . $entry["user"] . "' class='normal'>";
		echo $p->getUsername();
		echo "</a>";
		echo " (id " . $entry["user"] . ")";
		echo " logged in ";
		echo $entry["time"];
		echo "</p>";
	}
}

function getActiveRequests($mysqli) {
	$retArr = array();
	$sql = "SELECT `uid` FROM `requests` WHERE `filler`=0 ORDER BY `uid`";
	$res = $mysqli->query($sql);
	if (mysqli_num_rows($res)) {
		while ($row = mysqli_fetch_row($res)) {
			$retArr[] = $row[0];
		}
		return $retArr;
	}
	else return -1;
}

function printLetterForm($num, $pattern, $arr_v_begin, $arr_v_middle, $arr_v_middle2, $arr_v_end, $arr_c_begin, $arr_c_middle, $arr_c_middle2, $arr_c_end) {
	$vowels = array(
		"a",
		"e",
		"i",
		"o",
		"u",
		"y",
		"aa",
		"ae",
		"ai",
		"ao",
		"au",
		"ay",
		"ea",
		"ee",
		"ei",
		"eo",
		"eu",
		"ey",
		"ia",
		"ie",
		"ii",
		"io",
		"iu",
		"iy",
		"oa",
		"oe",
		"oi",
		"oo",
		"ou",
		"ua",
		"ue",
		"ui",
		"uo",
		"uu",
		"uy",
		"ya",
		"ye",
		"yi",
		"yo",
		"yu",
		"yy"
		);
	
	$consonants = array(
		"b",
		"bb",
		"bbl",
		"bh",
		"bl",
		"ch",	
		"ck",
		"d",
		"dd",
		"dg",
		"dj",
		"dz",
		"f",
		"ff",
		"fl",
		"g",
		"gg",
		"gh",
		"gl",
		"h",
		"hh",
		"ht",
		"j",
		"k",
		"kh",
		"kk",
		"l",
		"lj",
		"lk",
		"ll",
		"lm",
		"ln",
		"lp",
		"lst",
		"m",
		"mj",
		"mm",
		"n",
		"nj",
		"nn",
		"p",
		"pf",
		"ph",
		"pl",
		"ppl",
		"r",
		"rh",
		"rkk",
		"rm",
		"rn",
		"rr",
		"rst",
		"rt",
		"rtt",
		"s",
		"sch",
		"sk",
		"st",
		"t",
		"th",
		"tj",
		"ts",
		"tt",
		"v",
		"x",
		"y",
		"z"
		);
	echo "<form method='post' action='word.php'>";
	
	ptag("label", "Number of words to generate", "for='num'");
	ptag("input", "", "type='number' min='1' max='100' value='$num' id='num' name='num' style='width: 3em'");
	
	ptag("label", "Pattern", "for='pat'");
	ptag("input", "", "value='$pattern' id='pat' name='pattern'");
	
	para("a - start vowel");
	para("i - middle vowel");
	para("o - middle two vowel");
	para("e - end vowel");

	para("s - start consonant");
	para("m - middle consonant");
	para("d - middle two consonant");
	para("n - end consonant");
	para("Spaces, commas and periods are also accepted but other characters will be replaced by a hashtag. If there are no letters in the pool to choose from, there will be a question mark.");
	
	foreach ($vowels as $key => $v) {
		if (array_key_exists($key, $arr_v_begin)) $val1 = $arr_v_begin[$key];
		else $val1 = 0;
		if (array_key_exists($key, $arr_v_middle)) $val2 = $arr_v_middle[$key];
		else $val2 = 0;
		if (array_key_exists($key, $arr_v_middle2)) $val3 = $arr_v_middle2[$key];
		else $val3 = 0;
		if (array_key_exists($key, $arr_v_end)) $val4 = $arr_v_end[$key];
		else $val4 = 0;
		
		echo "<p>";
		echo $v . ": ";
		echo "Start ";
		ptag("input", "", "type='range' min='0' max='100' name='v_begin_$key' value='$val1' style='width: 5em'");
		echo "Middle ";
		ptag("input", "", "type='range' min='0' max='100' name='v_middle_$key' value='$val2' style='width: 5em'");
		echo "Middle2 ";
		ptag("input", "", "type='range' min='0' max='100' name='v_middle2_$key' value='$val3' style='width: 5em'");
		echo "End ";
		ptag("input", "", "type='range' min='0' max='100' name='v_end_$key' value='$val4' style='width: 5em'");
		echo "</p>";
	}
	
	ptag("input", "", "type='hidden' name='v_l' value='". sizeof($vowels)."'");
	
	foreach ($consonants as $key => $c) {
		if (array_key_exists($key, $arr_c_begin)) $val1 = $arr_c_begin[$key];
		else $val1 = 0;
		if (array_key_exists($key, $arr_c_middle)) $val2 = $arr_c_middle[$key];
		else $val2 = 0;
		if (array_key_exists($key, $arr_c_middle2)) $val3 = $arr_c_middle2[$key];
		else $val3 = 0;
		if (array_key_exists($key, $arr_c_end)) $val4 = $arr_c_end[$key];
		else $val4 = 0;
		
		echo "<p>";
		echo $c . ": ";
		echo "Start ";
		ptag("input", "", "type='range' min='0' max='100' name='c_begin_$key' value='$val1' style='width: 5em'");
		echo "Middle ";
		ptag("input", "", "type='range' min='0' max='100' name='c_middle_$key' value='$val2' style='width: 5em'");
		echo "Middle2 ";
		ptag("input", "", "type='range' min='0' max='100' name='c_middle2_$key' value='$val3' style='width: 5em'");
		echo "End ";
		ptag("input", "", "type='range' min='0' max='100' name='c_end_$key' value='$val4' style='width: 5em'");
		echo "</p>";
	}
	ptag("input", "", "type='hidden' name='c_l' value='". sizeof($consonants)."'");
	ptag("input", "", "type='submit' value='Change values'");
	echo "</form>";
}

function createLetterPools($arr_v_begin, $arr_v_middle, $arr_v_middle2, $arr_v_end, $arr_c_begin, $arr_c_middle, $arr_c_middle2, $arr_c_end) {
	
	$vowels = array(
		"a",
		"e",
		"i",
		"o",
		"u",
		"y",
		"aa",
		"ae",
		"ai",
		"ao",
		"au",
		"ay",
		"ea",
		"ee",
		"ei",
		"eo",
		"eu",
		"ey",
		"ia",
		"ie",
		"ii",
		"io",
		"iu",
		"iy",
		"oa",
		"oe",
		"oi",
		"oo",
		"ou",
		"ua",
		"ue",
		"ui",
		"uo",
		"uu",
		"uy",
		"ya",
		"ye",
		"yi",
		"yo",
		"yu",
		"yy"
		);
	
	$consonants = array(
		"b",
		"bb",
		"bbl",
		"bh",
		"bl",
		"ch",	
		"ck",
		"d",
		"dd",
		"dg",
		"dj",
		"dz",
		"f",
		"ff",
		"fl",
		"g",
		"gg",
		"gh",
		"gl",
		"h",
		"hh",
		"ht",
		"j",
		"k",
		"kh",
		"kk",
		"l",
		"lj",
		"lk",
		"ll",
		"lm",
		"ln",
		"lp",
		"lst",
		"m",
		"mj",
		"mm",
		"n",
		"nj",
		"nn",
		"p",
		"pf",
		"ph",
		"pl",
		"ppl",
		"r",
		"rh",
		"rkk",
		"rm",
		"rn",
		"rr",
		"rst",
		"rt",
		"rtt",
		"s",
		"sch",
		"sk",
		"st",
		"t",
		"th",
		"tj",
		"ts",
		"tt",
		"v",
		"x",
		"y",
		"z"
		);
	
	//start vowel - end consonant
	//start vowel - middle consonant - end vowel
	//start vowel - middle consonant - middle vowel - end consonant
	//start vowel - middle consonant - middle vowel - middle consonant2 - end vowel
	//start vowel - middle consonant - middle vowel - middle consonant2 - middle vowel2 - end consonant
	//start consonant - end vowel
	//start consonant - middle vowel - end consonant
	//start consonant - middle vowel - middle consonant - end vowel
	//start consonant - middle vowel - middle consonant - middle vowel2 - end consonant 
	//start consonant - middle vowel - middle consonant - middle vowel2 - middle consonant2 - end vowel
	
	$begin_v = array();
	$middle_v = array();
	$middle2_v = array();
	$end_v = array();
	$begin_c = array();
	$middle_c = array();
	$middle2_c = array();
	$end_c = array();
	
	foreach ($vowels as $key => $v) {
		if ($arr_v_begin[$key]>0) {
			for ($i = 0; $i < $arr_v_begin[$key]; $i++) {
				$begin_v[] = $v;
			}
		}
		if ($arr_v_middle[$key]>0) {
			for ($i = 0; $i < $arr_v_middle[$key]; $i++) {
				$middle_v[] = $v;
			}
		}
		if ($arr_v_middle2[$key]>0) {
			for ($i = 0; $i < $arr_v_middle2[$key]; $i++) {
				$middle2_v[] = $v;
			}
		}
		if ($arr_v_end[$key]>0) {
			for ($i = 0; $i < $arr_v_end[$key]; $i++) {
				$end_v[] = $v;
			}
		}
	}
	
	foreach ($consonants as $key => $c) {
		if ($arr_c_begin[$key]>0) {
			for ($i = 0; $i < $arr_c_begin[$key]; $i++) {
				$begin_c[] = $c;
			}
		}
		if ($arr_c_middle[$key]>0) {
			for ($i = 0; $i < $arr_c_middle[$key]; $i++) {
				$middle_c[] = $c;
			}
		}
		if ($arr_c_middle2[$key]>0) {
			for ($i = 0; $i < $arr_c_middle2[$key]; $i++) {
				$middle2_c[] = $c;
			}
		}
		if ($arr_c_end[$key]>0) {
			for ($i = 0; $i < $arr_c_end[$key]; $i++) {
				$end_c[] = $c;
			}
		}
	}
	
	return array("bv" => $begin_v, "mv" => $middle_v, "m2v" => $middle2_v, "ev" => $end_v, "bc" => $begin_c, "mc" => $middle_c, "m2c" => $middle2_c, "ec" => $end_c);
}

function generateWords($pos, $pattern, $num) {
	
	echo "<p>";
	for ($j = 0; $j < $num; $j++) {
		for ($i = 0; $i < mb_strlen($pattern); $i++) {
			$key = substr($pattern, $i, 1);
			if ($key == "a") {
				if ($pos["bv"]) {
					$rand = rand(0, sizeof($pos["bv"])-1);
					echo $pos["bv"][$rand];
				}
				else echo "?";
			}
			else if ($key == "i") {
				if ($pos["mv"]) {
					$rand = rand(0, sizeof($pos["mv"])-1);
					echo $pos["mv"][$rand];
				}
				else echo "?";
			}
			else if ($key == "o") {
				if ($pos["m2v"]) {
					$rand = rand(0, sizeof($pos["m2v"])-1);
					echo $pos["m2v"][$rand];
				}
				else echo "?";
			}
			else if ($key == "e") {
				if ($pos["ev"]) {
					$rand = rand(0, sizeof($pos["ev"])-1);
					echo $pos["ev"][$rand];
				}
				else echo "?";
			}
			else if ($key == "s") {
				if ($pos["bc"]) {
					$rand = rand(0, sizeof($pos["bc"])-1);
					echo $pos["bc"][$rand];
				}
				else echo "?";
			}
			else if ($key == "m") {
				if ($pos["mc"]) {
					$rand = rand(0, sizeof($pos["mc"])-1);
					echo $pos["mc"][$rand];
				}
				else echo "?";
			}
			else if ($key == "d") {
				if ($pos["m2c"]) {
					$rand = rand(0, sizeof($pos["m2c"])-1);
					echo $pos["m2c"][$rand];
				}
				else echo "?";
			}
			else if ($key == "n") {
				if ($pos["ec"]) {
					$rand = rand(0, sizeof($pos["ec"])-1);
					echo $pos["ec"][$rand];
				}
				else echo "?";
			}
			else if ($key == " ") {
				echo " ";
			}
			else if ($key == ",") {
				echo ",";
			}
			else if ($key == ".") {
				echo ".";
			}
			else echo "#";
		}
		if ($j < $num-1) echo ", ";
	}
	echo "</p>";
}

function generatePseudoWord() {
	$start_consonants = array(
		"b",
		"c",
		"d",
		"f",
		"g",
		"j",
		"l",
		"m",
		"n",
		"p",
		"r",
		"s",
		"t",
		"w",
		"x",
		"z",
		"y",
		"bl",
		"gr",
		"cr",
		"ch",
		"fl",
		"th",
		"sh",
		"dr",
		"sm",
		"sn",
		"bl",
		"br"
		);
	
	$vowels_start = array(
		"a",
		"au",
		"e",
		"o",
		"ee",
		"oo",
		"ai",
		"ou",
		"ea",
		"oa"
		);
	
	$vowels_middle = array(
		"a",
		"e",
		"i",
		"o",
		"u",
		"ee",
		"oo",
		"ai",
		"ou",
		"ea",
		"oa"
		);
	
	$vowels_end = array(
		"y",
		"e",
		"ay",
		"ey",
		"oy",
		"ee",
		"oo",
		"ow",
		"aw",
		"ew",
		"is",
		"as",
		"os",
		"us",
		"er",
		"our",
		"ah",
		"el",
		"um",
		"on",
		"a"
		);
	$end_consonants = array(
		"b",
		"bb",
		"d",
		"dd",
		"ll",
		"ng",
		"ss",
		"sh",
		"t",
		"zz",
		"x",
		"xx"
		);
	
	$middle_consonants = array(
		"bb",
		"bbl",
		"dd",
		"ff",
		"fl",
		"gg",
		"ll",
		"lm",
		"mm",
		"nd",
		"nn",
		"nt",
		"pp",
		"r",
		"rb",
		"rn",
		"rm",
		"rr",
		"ss",
		"tt"
		);
	
	$simple_consonants = array(
		"b",
		"n",
		"k",
		"l",
		"m",
		"t",
		"v",
		"p",
		"s",
		"z",
		"x",
		"y",
		"j",
		"b",
		"n",
		"k",
		"l",
		"m",
		"t",
		"w"
		);
	
	$simple_vowels = array(
		"a",
		"i",
		"u",
		"o",
		"e"
		);
	
	$pattern = rand(0,9);
	
	switch ($pattern) {
	case 0:
		$str = $start_consonants[rand(0,sizeof($start_consonants)-1)] . $vowels_end[rand(0,sizeof($vowels_end)-1)];
		break;
	case 1:
		$vowel = $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		$consonant = $simple_consonants[rand(0,sizeof($simple_consonants)-1)];
		$str = $consonant . $vowel . $consonant . $vowel . $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		break;
	case 2:
		$vowel = $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		$consonant = $simple_consonants[rand(0,sizeof($simple_consonants)-1)];
		$str = $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $vowel . $consonant . $vowel . $consonant . $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		break;
	case 3:
		$vowel = $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		$consonant = $simple_consonants[rand(0,sizeof($simple_consonants)-1)];
		$str = $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $simple_vowels[rand(0,sizeof($simple_vowels)-1)] . $consonant . $vowel . $consonant . $vowel;
		break;
	case 4:
		$vowel = $vowels_end[rand(1,sizeof($vowels_end)-1)];
		$consonant = $start_consonants[rand(0,sizeof($start_consonants)-1)];
		$str = $consonant . $vowel . $consonant . $vowel;
		break;
	case 5:
		$vowel = $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		$str = $start_consonants[rand(0,sizeof($start_consonants)-1)] . $vowel . $middle_consonants[rand(0,sizeof($middle_consonants)-1)] . $vowel . "ge";
		break;
	case 6:
		$str = $start_consonants[rand(0,sizeof($start_consonants)-1)] . $vowels_middle[rand(0,sizeof($vowels_middle)-1)] . $middle_consonants[rand(0,sizeof($middle_consonants)-1)] . "ot";
		break;
	case 7:
		$vowel = $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		$consonant = $simple_consonants[rand(0,sizeof($simple_consonants)-1)];
		$str = $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $vowel . $consonant . $simple_vowels[rand(0,sizeof($simple_vowels)-1)] . $consonant . $vowel;
		break;
	case 8:
		$str = $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $simple_vowels[rand(0,sizeof($simple_vowels)-1)] . $simple_consonants[rand(0,sizeof($simple_consonants)-1)] . $simple_vowels[rand(0,sizeof($simple_vowels)-1)];
		break;
	case 9:
		$str = $start_consonants[rand(0,sizeof($start_consonants)-1)] . $vowels_middle[rand(0,sizeof($vowels_middle)-1)] . $end_consonants[rand(0,sizeof($end_consonants)-1)];
		break;
	}
	
	return $str;
}

function rgb2hsl($r, $g, $b) {
   $var_R = ($r / 255);
   $var_G = ($g / 255);
   $var_B = ($b / 255);

   $var_Min = min($var_R, $var_G, $var_B);
   $var_Max = max($var_R, $var_G, $var_B);
   $del_Max = $var_Max - $var_Min;

   $v = $var_Max;

   if ($del_Max == 0) {
      $h = 0;
      $s = 0;
   } else {
      $s = $del_Max / $var_Max;

      $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
      $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
      $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

      if      ($var_R == $var_Max) $h = $del_B - $del_G;
      else if ($var_G == $var_Max) $h = ( 1 / 3 ) + $del_R - $del_B;
      else if ($var_B == $var_Max) $h = ( 2 / 3 ) + $del_G - $del_R;

      if ($h < 0) $h++;
      if ($h > 1) $h--;
   }

   return array($h, $s, $v);
}

function hsl2rgb($h, $s, $v) {
    if($s == 0) {
        $r = $g = $B = $v * 255;
    } else {
        $var_H = $h * 6;
        $var_i = floor( $var_H );
        $var_1 = $v * ( 1 - $s );
        $var_2 = $v * ( 1 - $s * ( $var_H - $var_i ) );
        $var_3 = $v * ( 1 - $s * (1 - ( $var_H - $var_i ) ) );

        if       ($var_i == 0) { $var_R = $v     ; $var_G = $var_3  ; $var_B = $var_1 ; }
        else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $v      ; $var_B = $var_1 ; }
        else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $v      ; $var_B = $var_3 ; }
        else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $v     ; }
        else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $v     ; }
        else                   { $var_R = $v     ; $var_G = $var_1  ; $var_B = $var_2 ; }

        $r = $var_R * 255;
        $g = $var_G * 255;
        $B = $var_B * 255;
    }    
    return array($r, $g, $B);
}

function changeHue($original, $change) {
	$array = hex2rgb($original);
	$hsl = rgb2hsl($array[0], $array[1], $array[2]);
	if ($hsl[0]<0.7) $change*=-1;
	$hsl[0] = min(1,max(0,($hsl[0]+($change/255))));
	
	$rgb = hsl2rgb($hsl[0], $hsl[1], $hsl[2]);
	return rgb2hex($rgb);
}

function rgb2hex($rgb = array(0, 0, 0)) {
	//https://gist.github.com/Pushplaybang/5432844
	$hex = "#";
	$hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
	$hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
	$hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);
	
	return $hex; // returns the hex value including the number sign (#)
}

function hex2rgb($color = "#000000"){
	//http://www.jonasjohn.de/snippets/php/hex2rgb.htm
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6){ return array(0,0,0); }
    $rgb = array();
    for ($x=0;$x<3;$x++){
        $rgb[$x] = hexdec(substr($color,(2*$x),2));
    }
    return $rgb;
}

function adjustBrightness($hex, $steps) {
	//https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);
    $return = '#';

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

function mix($color_1 = array(0, 0, 0), $color_2 = array(0, 0, 0), $weight = 0.5)
{
	$ca1 = array();
	$ca2 = array();
	$ca3 = array();
	foreach ($color_1 as $c1) {
		$c1 *= $weight;
		$ca1[] = $c1;
	}
	foreach ($color_2 as $c2) {
		$c2 *= 1-$weight;
		$ca2[] = $c2;
	}
	foreach ($ca1 as $key => $c3) {
		$ca3[] = $c3+$ca2[$key];
	}
	
	return $ca3;
}
function tint($color, $weight = 0.5)
{
	$t = $color;
	if(is_string($color)) $t = hex2rgb($color);
	$u = mix($t, array(255, 255, 255), $weight);
	if(is_string($color)) return rgb2hex($u);
	return $u;
}
function tone($color, $weight = 0.5)
{
	$t = $color;
	if(is_string($color)) $t = hex2rgb($color);
	$u = mix($t, array(128, 128, 128), $weight);
	if(is_string($color)) return rgb2hex($u);
	return $u;
}
function shade($color, $weight = 0.5)
{
	$t = $color;
	if(is_string($color)) $t = hex2rgb($color);
	$u = mix($t, array(0, 0, 0), $weight);
	if(is_string($color)) return rgb2hex($u);
	return $u;
}

function between($var, $lower, $upper) {
	if ($var>$lower&&$var<$upper) return true;
	return false;
}

function ibetween($var, $lower, $upper) {
	//inclusive
	if ($var>=$lower&&$var<=$upper) return true;
	return false;
}

function abetween($var, $lower, $upper) {
	//first one inclusive, second one not
	if ($var>=$lower&&$var<$upper) return true;
	return false;
}

function bbetween($var, $lower, $upper) {
	//second one inclusive, first one not
	if ($var>$lower&&$var<=$upper) return true;
	return false;
}
?>
