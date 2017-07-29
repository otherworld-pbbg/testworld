<?php

function randConsonant(){
	$consonants = array(
		"b", "c", "d", "f", "g", "h", "j", "k", "l", "m", 
		"n", "p", "q", "r", "s", "t", "v", "w", "x", "z"
		);
	return $consonants[mt_rand(0,19)];
}

function randVowel(){
	$vowels = array("a", "e", "i", "o", "u", "y");
	return $vowels[mt_rand(0,5)];
}

function getWord()
{
	//returns 1-3 letters, possible patterns
	//k, v, kv, kk, vv, vk, kvk, kvv, vkv, vkk
	//where k=consonant, v=vowel
	$str='';
	
	if (mt_rand(0,1)) {
		$str.=randVowel();
		if (mt_rand(0,1)) $str.=randConsonant();
	}
	else {
		$str.=randConsonant();
		if (mt_rand(0,1)) $str.=randVowel();
	}
	$rint = mt_rand(0,2);
	if ($rint==1) $str.=randVowel();
	else if ($rint==2) $str.=randConsonant();
	//0 = no letter
	$rint = mt_rand(0,2);
	if ($rint==1) $str = strtoupper($str);//capitalizes
	else if ($rint==2) $str = ucfirst($str);//caps first letter
	//0 = remains lowercase
	return $str;
}

function getNumber() {
	$rint = mt_rand(0,2);
	if ($rint==1) return mt_rand(0,9);
	else if ($rint==2) return mt_rand(10,99);
	else return mt_rand(100,999);
}

function getSign() {
	$signs = array("+", ".", "!", "-", "_", "*", "@", "?", "=", "%", "/");
	return ($signs[mt_rand(0,10)]);
}

function createPass() {
	$passtr = getWord();
	$prevrand = 0;
	
	while (strlen($passtr)<7) {
		$rint = mt_rand(0,2);
		if ($rint!=$prevrand) {
			if ($rint==0) $passtr.=getWord();
			else if ($rint==1) $passtr.=getNumber();
			else $passtr.=getSign();
			$prevrand = $rint;
		}
	}
	return $passtr;
}

?>
