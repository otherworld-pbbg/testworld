<?php

function myHash($pw)
{
	$pw .= "nytVittuOikeestiLakatkaaJakamastaSit4Avainta!!!";
	return md5($pw);
}

?>