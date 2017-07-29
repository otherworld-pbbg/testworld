<?php
//http://pressf1.pcworld.co.nz/archive/index.php/t-60179.html
function isUsername($element)
{
return !preg_match ("/[^A-z0-9_\-]/", $element);
}
?>