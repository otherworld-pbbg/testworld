<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once "face.php";
include_once "../_private/generic.inc.php";

$fv = 50;
$bv = 50;
$jv = 50;
$pv = 0;
$hv = 50;
$sv = 0;
$bav = 0;
$nl = 20;
$nv = 50;
$n2v = 50;
$eye = "#553322";
$skin = "#aa7849";
$hair = "#553322";
$er1 = 0;
$er2 = 0;
$ebr1 = 0;
$ebr2 = 0;
$es = 0;
$mw = 50;
$lipv = 0;

if (isset($_POST["fv"])) {
	if (is_numeric($_POST["fv"])) $fv = min(200,max(0,round($_POST["fv"])));
}
if (isset($_POST["bv"])) {
	if (is_numeric($_POST["bv"])) $bv = min(100,max(0,round($_POST["bv"])));
}
if (isset($_POST["jv"])) {
	if (is_numeric($_POST["jv"])) $jv = min(100,max(0,round($_POST["jv"])));
}
if (isset($_POST["pv"])) {
	if (is_numeric($_POST["pv"])) $pv = min(100,max(0,round($_POST["pv"])));
}
if (isset($_POST["hv"])) {
	if (is_numeric($_POST["hv"])) $hv = min(100,max(0,round($_POST["hv"])));
}
if (isset($_POST["sv"])) {
	if (is_numeric($_POST["sv"])) $sv = min(100,max(0,round($_POST["sv"])));
}
if (isset($_POST["bav"])) {
	if (is_numeric($_POST["bav"])) $bav = min(100,max(0,round($_POST["bav"])));
}
if (isset($_POST["nl"])) {
	if (is_numeric($_POST["nl"])) $nl = min(35,max(0,round($_POST["nl"])));
}
if (isset($_POST["nv"])) {
	if (is_numeric($_POST["nv"])) $nv = min(100,max(0,round($_POST["nv"])));
}
if (isset($_POST["n2v"])) {
	if (is_numeric($_POST["n2v"])) $n2v = min(100,max(0,round($_POST["n2v"])));
}
if (isset($_POST["es"])) {
	if (is_numeric($_POST["es"])) $es = min(10,max(-5,round($_POST["es"])));
}
if (isset($_POST["er1"])) {
	if (is_numeric($_POST["er1"])) $er1 = min(25,max(-25,round($_POST["er1"])));
}
if (isset($_POST["er2"])) {
	if (is_numeric($_POST["er2"])) $er2 = min(25,max(-25,round($_POST["er2"])));
}
if (isset($_POST["ebr1"])) {
	if (is_numeric($_POST["ebr1"])) $ebr1 = min(25,max(-25,round($_POST["ebr1"])));
}
if (isset($_POST["ebr2"])) {
	if (is_numeric($_POST["ebr2"])) $ebr2 = min(25,max(-25,round($_POST["ebr2"])));
}
if (isset($_POST["mw"])) {
	if (is_numeric($_POST["mw"])) $mw = min(100,max(0,round($_POST["mw"])));
}
if (isset($_POST["lipv"])) {
	if (is_numeric($_POST["lipv"])) $lipv = min(10,max(-10,round($_POST["lipv"])));
}
if (isset($_POST["eye1"])) {
	if ($secure = checkHex($_POST["eye1"])) $eye = $secure;
}
if (isset($_POST["skin1"])) {
	if ($secure = checkHex($_POST["skin1"])) $skin = $secure;
}
if (isset($_POST["hair1"])) {
	if ($secure = checkHex($_POST["hair1"])) $hair = $secure;
}

include_once "../_private/abbr.inc.php";

include_once "../_private/header.inc.php";

drawFace($fv, $bv, $jv, $pv, $hv, $sv, $bav, $nl, $nv, $n2v, $eye, $skin, $hair, $er1, $er2, $ebr1, $ebr2, $es, $mw+80, $lipv);//mw shows up as 0 to 100 for convenience but it's actually 80 to 200%

?>

<form method='post' class='medium'>
<label for='fvi' class='wide'>Fat (0 to 200)</label>
<?php
echo "<input type='number' id='fvi' name='fv' value='$fv' size='5' min='0' max='200'><br>";
?>
<label for='bvi' class='wide'>Cheekbones (0 to 100)</label>
<?php
echo "<input type='number' id='bvi' name='bv' value='$bv' size='5' min='0' max='100'><br>";
?>
<label for='jvi' class='wide'>Jaw (0 to 100)</label>
<?php
echo "<input type='number' id='jvi' name='jv' value='$jv' size='5' min='0' max='100'><br>";
?>
<label for='pvi' class='wide'>Hairline point (0 to 100)</label>
<?php
echo "<input type='number' id='pvi' name='pv' value='$pv' size='5' min='0' max='100'><br>";
?>
<label for='hvi' class='wide'>Hair thickness (0 to 100)</label>
<?php
echo "<input type='number' id='hvi' name='hv' value='$hv' size='5' min='0' max='100'><br>";
?>
<label for='svi' class='wide'>Sideburns (0 to 100)</label>
<?php
echo "<input type='number' id='svi' name='sv' value='$sv' size='5' min='0' max='100'><br>";
?>
<label for='bavi' class='wide'>Receding hairline (0 to 100)</label>
<?php
echo "<input type='number' id='bavi' name='bav' value='$bav' size='5' min='0' max='100'><br>";
?>
<label for='nl' class='wide'>Nose length (0 to 35)</label>
<?php
echo "<input type='number' id='nl' name='nl' value='$nl' size='5' min='0' max='35'><br>";
?>
<label for='nvi' class='wide'>Nose width (0 to 100)</label>
<?php
echo "<input type='number' id='nvi' name='nv' value='$nv' size='5' min='0' max='100'><br>";
?>
<label for='n2vi' class='wide'>Nose tip vertical angle (0 to 100)</label>
<?php
echo "<input type='number' id='n2vi' name='n2v' value='$n2v' size='5' min='0' max='100'><br>";
?>
<label for='es' class='wide'>Eye spacing (-5 to 10)</label>
<?php
echo "<input type='number' id='es' name='es' value='$es' size='5' min='-5' max='10'><br>";
?>
<label for='er1' class='wide'>Left eye rotate (-25 to 25)</label>
<?php
echo "<input type='number' id='er1' name='er1' value='$er1' size='5' min='-25' max='25'><br>";
?>
<label for='er2' class='wide'>Right eye rotate (-25 to 25)</label>
<?php
echo "<input type='number' id='er2' name='er2' value='$er2' size='5' min='-25' max='25'><br>";
?>
<label for='ebr1' class='wide'>Left eyebrow tilt (-25 to 25)</label>
<?php
echo "<input type='number' id='ebr1' name='ebr1' value='$ebr1' size='5' min='-25' max='25'><br>";
?>
<label for='ebr2' class='wide'>Right eyebrow tilt (-25 to 25)</label>
<?php
echo "<input type='number' id='ebr2' name='ebr2' value='$ebr2' size='5' min='-25' max='25'><br>";
?>
<label for='mw' class='wide'>Mouth width (0 to 100)</label>
<?php
echo "<input type='number' id='mw' name='mw' value='$mw' size='5' min='0' max='100'><br>";
?>
<label for='lipv' class='wide'>Mouth distance (-10 to 10)</label>
<?php
echo "<input type='number' id='lipv' name='lipv' value='$lipv' size='5' min='-10' max='10'><br>";
?>
<label for='eye1' class='wide'>Eye color</label>
<?php
echo '<input name="eye1" class="jscolor {hash:true}" value="' . $eye . '"><br>';
?>
<label for='skin1' class='wide'>Skin color</label>
<?php
echo '<input name="skin1" class="jscolor {hash:true}" value="' . $skin . '"><br>';
?>
<label for='ehair1' class='wide'>Hair color</label>
<?php
echo '<input name="hair1" class="jscolor {hash:true}" value="' . $hair . '"><br>';
?>
<label for='sb' class='wide'></label> <input type='submit' id='sb' value='Update'>
</form>
</body>
</html>
