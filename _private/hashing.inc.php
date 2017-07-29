<?php

function myHash($pw)
{
	$pw .= "voiVittuMit4Paskaa1985";
	return md5($pw);
}

?>