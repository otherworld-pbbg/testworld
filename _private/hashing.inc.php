<?php

function myHash($pw)
{
	$pw .= "nytVittuOikeestiLakatkaaJakamastaSit4Avainta!!!";
	return md5($pw);
}

function saveNewHash($mysqli, $password, $uid) {
	$newhash = password_hash($password, PASSWORD_DEFAULT);
	$sql = "UPDATE `users` SET `passhash`='', `passhash2`='$newhash' WHERE `uid`=$uid LIMIT 1";
	$mysqli->query($sql);
	if ($mysqli->affected_rows==1) return true;
	else return false;
}


?>