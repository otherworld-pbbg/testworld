<?php

function drawFace($fv, $bv, $jv, $pv, $hv, $sv, $bav, $nl, $nv, $n2v, $eye, $skincolor, $haircolor, $er1, $er2, $ebr1, $ebr2, $espace, $mw, $lipv) {
	$fatval = $fv/100;
	$boneval = $bv/100;
	$jawval = $jv/100;
	$lineval = $bav/100;
	$pointval = $pv/100;
	$sideval = $sv/100;
	$thickval = $hv/100;
	if ($hv<20) $thin = $thickval+0.05;//makes hair partially transparent if it's thin
	else $thin = 1;
	$nwval = $nv/100;
	$nlval = $n2v/100;
	$nl2val = $nl/35;
	$mouth = $mw/100;
	$nshadow_lower = $nl2val*35;
	$lipvertical = $nl2val*35+$lipv;
	$eyewhite = "#EEEEDD";
	$skinhi = adjustBrightness($skincolor, 15);
	$edge = adjustBrightness($skincolor, -45);
	$edge2 = adjustBrightness($haircolor, -45);
	$skinshadow = rgb2hex(mix(hex2rgb($skincolor), array(11, 11, 22), 0.7));
	$skinhi = changeHue($skinhi, 2);
	$lip = adjustBrightness($skincolor, -45);
	$lip2 = rgb2hex(mix(hex2rgb($lip), array(140, 40, 60), 0.6));
	$lip = rgb2hex(mix(hex2rgb($lip), array(130, 20, 70), 0.6));
	$cheekshadow = adjustBrightness($skincolor, -20*$boneval*((2-$fatval)/2)-1);
	$cheekshadow = rgb2hex(mix(hex2rgb($cheekshadow), array(11, 11, 22), 0.9));
	$cheekedge = adjustBrightness($skincolor, -45*$boneval*((2-$fatval)/2)-1);
	$espace2 = -$espace;
	$ebr1 += $er1;
	$ebr2 += $er2;
	
	$coords = array(
		array(0, 0, "M", ","),
		array(40, 1, "C", ","),
		array(70, 10, ",", "C"),
		array(83, 27, ",", ","),
		array(100, 41, "C", ","),
		array(105, 83, ",", "C"),
		array(110, 108, ",", ","),
		array(110, 142, "C", ","),
		array(100, 160, ",", "C"),
		array(105, 180, ",", ","),
		array(110, 209, "C", ","),
		array(99, 217, ",", "C"),
		array(94, 232, ",", ","),
		array(80, 289, "C", ","),
		array(92, 300, ",", "C"),
		array(45, 330, ",", ","),
		array(30, 335, "C", ","),
		array(20, 350, ",", "C"),
		array(0, 353, ",", "L")
	);//face basic shape
	
	
	$coords_hairline = array(
		array(0, -20, "M", ","),
		array(40, -20, "C", ","),
		array(80, -20, ",", "C"),
		array(200, -20, ",", ","),
		array(200, -20, "C", ","),
		array(200, 100, ",", "C"),
		array(160, 168, ",", ","),
		array(160, 182, "C", ","),
		array(160, 190, ",", "C"),
		array(160, 190, ",", ","),
		array(110, 190, "C", ","),//sideburns
		array(100, 180, ",", "C"),
		array(92, 142, ",", ","),
		array(90, 108, "C", ","),
		array(95, 105, ",", "C"),
		array(45, 97, ",", ","),
		array(30, 95, "C", ","),
		array(20, 101, ",", "C"),
		array(0, 100, ",", "L")
	);//basic hairline shape
	
	$c_nose = array(
		array(0, 220, "M", ","),
		array(3, 220, "C", ","),
		array(4, 224, ",", "C"),
		array(7, 226, ",", ","),//first corner
		array(8, 226, "C", ","),
		array(10, 226, ",", "C"),
		array(11, 226, ",", ","),//second corner
		array(22, 232, "C", ","),
		array(18, 239, ",", "C"),
		array(16, 242, ",", ","),//end of the groove
		array(15, 244, "C", ","),
		array(12, 245, ",", "C"),
		array(10, 246, ",", ","),//nostril
		array(5, 245, "C", ","),
		array(6, 245, ",", "C"),
		array(14, 238, ",", ","),//nostril
		array(12, 230, "C", ","),
		array(9, 242, ",", "C"),
		array(0, 243, ",", "L")
	);
	
	$c_nose_hi = array(
		array(0, 200, "M", ","),
		array(2, 200, "C", ","),
		array(2, 201, ",", "C"),
		array(2, 204, ",", ","),
		array(2, 207, "C", ","),
		array(3, 207, ",", "C"),
		array(3, 210, ",", ","),
		array(4, 220, "C", ","),
		array(4, 220, ",", "C"),
		array(5, 220, ",", ","),
		array(5, 225, "C", ","),
		array(4, 225, ",", "C"),
		array(4, 228, ",", ","),
		array(3, 228, "C", ","),
		array(3, 229, ",", "C"),
		array(0, 230, ",", "L")
	);
	
	$c_upper = array(
		array(0, 277, "M", ","),
		array(4, 276, "C", ","),
		array(4, 275, ",", "C"),
		array(9, 275, ",", ","),//arc
		array(12, 277, "C", ","),
		array(12, 277, ",", "C"),
		array(16, 278, ",", ","),
		array(22, 279, "C", ","),
		array(22, 279, ",", "C"),
		array(27, 281, ",", ","),//outer corner
		array(18, 282, "C", ","),
		array(16, 280, ",", "C"),
		array(9, 281, ",", ","),
		array(5, 282, "C", ","),
		array(5, 282, ",", "C"),
		array(0, 282, ",", "L")
	);
	
	$c_lower = array(
		array(0, 282, "M", ","),
		array(5, 282, "C", ","),
		array(5, 282, ",", "C"),
		array(9, 281, ",", ","),
		array(16, 280, "C", ","),
		array(18, 282, ",", "C"),
		array(27, 281, ",", ","),//outer corner
		array(19, 287, "C", ","),
		array(19, 287, ",", "C"),
		array(9, 288, ",", ","),
		array(5, 289, "C", ","),
		array(5, 289, ",", "C"),
		array(0, 289, ",", "L")
	);
	
	$c_cheek = array(
		array(67, 228, "M", ","),
		array(74, 223, "C", ","),
		array(74, 223, ",", "C"),
		array(82, 224, ",", ","),//cheek
		array(105, 230, "C", ","),
		array(135, 220, ",", "C"),
		array(137, 168, ",", ","),//outer corner
		array(162, 205, "C", ","),
		array(145, 216, ",", "C"),
		array(130, 224, ",", ","),//outside
		array(112, 238, "C", ","),
		array(114, 226, ",", "C"),
		array(95, 235, ",", ","),//groove in
		array(84, 237, "C", ","),
		array(84, 237, ",", "C"),
		array(74, 247, ",", ","),//lower corner
		array(74, 236, "C", ","),
		array(74, 236, ",", "C"),
		array(67, 228, ",", "M")
	);
	
	$c_brow = array(
		array(12, 178, "M", ","),
		array(16, 170, "C", ","),
		array(16, 170, ",", "C"),
		array(26, 165, ",", ","),//cheek
		array(45, 159, "C", ","),
		array(45, 159, ",", "C"),
		array(63, 165, ",", ","),//outer corner
		array(71, 168, "C", ","),
		array(73, 173, ",", "C"),
		array(69, 182, ",", ","),//outside
		array(61, 187, "C", ","),
		array(61, 187, ",", "C"),
		array(54, 180, ",", ","),//groove in
		array(44, 176, "C", ","),
		array(44, 179, ",", "C"),
		array(21, 183, ",", ","),//lower corner
		array(12, 183, "C", ","),
		array(12, 188, ",", "C"),
		array(12, 178, ",", "M")
	);
	
	$c_eye = array(
		array(19, 190, "M", ","),
		array(25, 183, "C", ","),
		array(25, 183, ",", "C"),
		array(33, 180, ",", ","),
		array(39, 179, "C", ","),
		array(39, 179, ",", "C"),
		array(47, 181, ",", ","),
		array(53, 181, "C", ","),
		array(53, 181, ",", "C"),
		array(60, 186, ",", ","),
		array(55, 190, "C", ","),
		array(55, 190, ",", "C"),
		array(48, 191, ",", ","),
		array(39, 192, "C", ","),
		array(39, 192, ",", "C"),
		array(32, 192, ",", ","),
		array(29, 190, "C", ","),
		array(29, 190, ",", "C"),
		array(19, 190, ",", "M")
	);
	
	$c_eb = array(
		array(8, 177, "M", ","),//inner corner
		array(9, 173, "C", ","),
		array(10, 173, ",", "C"),
		array(11, 168, ",", ","),//inner corner2
		array(23, 158, "C", ","),
		array(24, 160, ",", "C"),
		array(39, 157, ",", ","),//top
		array(55, 155, "C", ","),
		array(54, 155, ",", "C"),
		array(66, 160, ",", ","),//top2
		array(70, 160, "C", ","),
		array(74, 163, ",", "C"),
		array(77, 168, ",", ","),//outer tip
		array(69, 163, "C", ","),
		array(67, 164, ",", "C"),
		array(47, 165, ",", ","),//lower curve
		array(26, 167, "C", ","),
		array(22, 170, ",", "C"),
		array(8, 177, ",", "M")
	);//eyebrow
	
	$nose_w = array(
		array(0, 0),
		array(10, 0),
		array(27, 8),
		array(20, 2),//first corner
		array(20, 0),
		array(15, 0),
		array(20, 0),//second corner
		array(20, -2),
		array(23, 0),
		array(27, 2),//end of the groove
		array(30, 16),
		array(20, 7),
		array(20, 0),//nostril
		array(18, 0),
		array(15, 0),
		array(8, 0),//nostril
		array(6, 0),
		array(4, 0),
		array(0, 0)
	);
	
	$nose_l = array(
		array(0, 30),
		array(13, 22),
		array(-2, 10),
		array(-2, 5),//first corner
		array(-3, -1),
		array(5, -5),
		array(5, 0),//second corner
		array(0, 0),
		array(5, 0),
		array(5, 0),//end of the groove
		array(5, 0),
		array(10, 0),
		array(10, 0),//nostril
		array(10, 0),
		array(10, 0),
		array(0, 10),//nostril
		array(-2, 18),
		array(3, 20),
		array(0, 25)
	);
	
	$nose_hi_w = array(
		array(0, 0),
		array(3, 1),
		array(4, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, 0),
		array(5, -5),
		array(8, -5),
		array(10, -5),
		array(15, -1),
		array(0, 0)
	);
	
	$nose_hi_l = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(-2, 0),
		array(-2, 0),
		array(-2, 0),
		array(-4, 0),
		array(-3, 0),
		array(-4, 0),
		array(-4, 0),
		array(-2, 10),
		array(0, 15),
		array(0, 15),
		array(5, 18),
		array(0, 20)
	);
	
	$nose_hi_l2 = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 5),
		array(0, 5),
		array(0, 10),
		array(0, 10),
		array(0, 20),
		array(0, 30),
		array(0, 30),
		array(0, 30),
		array(5, 35),
		array(0, 35)
	);
	
	$linecurve = array(
		array(0, 70),
		array(30, 65),
		array(-60, 50),
		array(-50, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),//sideburns
		array(20, 0),
		array(0, 0),
		array(30, 0),
		array(10, -120),
		array(0, -55),
		array(-25, -20),
		array(0, -15),
		array(0, -5)
	);//widow's peak
	
	$point = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),//sideburns
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(-10, 0),
		array(0, 25)
	);//hair point
	
	$sideburns = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(-20, 70),
		array(-35, 130),//sideburns
		array(10, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0)
	);
	
	$thick = array(
		array(0, -15),
		array(8, -16),
		array(8, -10),
		array(16, -5),
		array(10, -5),
		array(12, -5),
		array(7, 0),
		array(6, 0),
		array(16, 0),
		array(5, 0),
		array(11, 0),//sideburns
		array(10, 0),
		array(10, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0)
	);//hair thickness
	
	$cheekbones = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 4),
		array(-4, 0),
		array(-2, 0),
		array(1, 0),
		array(20, 0),
		array(-4, 1),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0)
	);
	
	$jaw = array(
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(0, 0),
		array(5, 0),
		array(22, 8),
		array(6, 4),
		array(4, 1),
		array(3, 1),
		array(7, 7),
		array(0, 0)
	);
	
	$fat = array(
		array(0, -3),
		array(0, -3),
		array(1, -2),
		array(1, -1),
		array(1, -1),
		array(4, -1),
		array(2, 0),
		array(3, 0),
		array(3, 0),
		array(5, 0),
		array(5, -3),
		array(10, 2),
		array(18, 7),
		array(30, 12),
		array(12, 19),
		array(11, 9),
		array(16, 10),
		array(5, 15),
		array(0, 10)
		);
	
	$store = array (170, 20);
	
	$ex1 = $store[0]+40;
	$ey1 = $store[1]+185;
	$ex2 = $store[0]-40;
	$ey2 = $store[1]+185;
	
	$string = "";
	$string2 = "";
	$stringh = "";
	$stringn = "";
	$stringc1 = "";
	$stringc2 = "";
	$stringb1 = "";
	$stringb2 = "";
	$stringe1 = "";
	$stringe2 = "";
	$stringnh = "";
	$stringeb1 = "";
	$stringeb2 = "";
	$stringu = "";
	$stringlow = "";
	
	foreach ($coords as $key => $c) {
		$x = $c[0]+$store[0]+($fat[$key][0]*$fatval)+$cheekbones[$key][0]*$boneval+$jaw[$key][0]*$jawval;
		$y = $c[1]+$store[1]+($fat[$key][1]*$fatval)+$cheekbones[$key][1]*$boneval+$jaw[$key][1]*$jawval;
		$x1 = $x + $thick[$key][0]*$thickval;
		$y1 = $y + $thick[$key][1]*$thickval;
		$string .= $c[2] . " " . $x . " " . $y . " ";
		$stringh .= $c[2] . " " . $x1 . " " . $y1 . " ";
	}
	
	for ($i = sizeof($coords)-1; $i>=0; $i--) {
		$x2 = -$coords[$i][0]+$store[0]-($fat[$i][0]*$fatval)-$cheekbones[$i][0]*$boneval-$jaw[$i][0]*$jawval;
		$y2 = $coords[$i][1]+$store[1]+($fat[$i][1]*$fatval)+$cheekbones[$i][1]*$boneval+$jaw[$i][1]*$jawval;
		$x1 = $x2 - $thick[$i][0]*$thickval;
		$y1 = $y2 + $thick[$i][1]*$thickval;
		$string .= " " . $coords[$i][3] . " " . $x2 . " " . $y2;
		$stringh .= " " . $coords[$i][3] . " " . $x1 . " " . $y1;
	}
	
	foreach ($coords_hairline as $key => $c) {
		$x = $c[0]+$store[0]+$linecurve[$key][0]*$lineval+$point[$key][0]*$pointval+$sideburns[$key][0]*$sideval;
		$y = $c[1]+$store[1]+$linecurve[$key][1]*$lineval+$point[$key][1]*$pointval+$sideburns[$key][1]*$sideval;
		$string2 .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($coords_hairline)-1; $i>=0; $i--) {
		$x2 = -$coords_hairline[$i][0]+$store[0]-$linecurve[$i][0]*$lineval-$point[$i][0]*$pointval-$sideburns[$i][0]*$sideval;
		$y2 = $coords_hairline[$i][1]+$store[1]+$linecurve[$i][1]*$lineval+$point[$i][1]*$pointval+$sideburns[$i][1]*$sideval;
		$string2 .= " " . $coords_hairline[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_nose as $key => $c) {
		$x = $c[0]+$store[0]+$nose_w[$key][0]*$nwval+$nose_l[$key][0]*$nlval;
		$y = $c[1]+$store[1]+$nose_w[$key][1]*$nwval+$nose_l[$key][1]*$nlval;
		$stringn .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_nose)-1; $i>=0; $i--) {
		$x2 = -$c_nose[$i][0]+$store[0]-$nose_w[$i][0]*$nwval-$nose_l[$i][0]*$nlval;
		$y2 = $c_nose[$i][1]+$store[1]+$nose_w[$i][1]*$nwval+$nose_l[$i][1]*$nlval;
		$stringn .= " " . $c_nose[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_nose_hi as $key => $c) {
		$x = $c[0]+$store[0]+$nose_hi_w[$key][0]*$nwval+$nose_hi_l[$key][0]*$nlval+$nose_hi_l2[$key][0]*$nl2val;
		$y = $c[1]+$store[1]+$nose_hi_w[$key][1]*$nwval+$nose_hi_l[$key][1]*$nlval+$nose_hi_l2[$key][1]*$nl2val;
		$stringnh .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_nose_hi)-1; $i>=0; $i--) {
		$x2 = -$c_nose_hi[$i][0]+$store[0]-$nose_hi_w[$i][0]*$nwval-$nose_hi_l[$i][0]*$nlval-$nose_hi_l2[$i][0]*$nl2val;
		$y2 = $c_nose_hi[$i][1]+$store[1]+$nose_hi_w[$i][1]*$nwval+$nose_hi_l[$i][1]*$nlval+$nose_hi_l2[$i][1]*$nl2val;
		$stringnh .= " " . $c_nose_hi[$i][3] . " " . $x2 . " " . $y2;
	}
	foreach ($c_upper as $key => $c) {
		$x = $c[0]*$mouth+$store[0];
		$y = $c[1]+$store[1];
		$stringu .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_upper)-1; $i>=0; $i--) {
		$x2 = -$c_upper[$i][0]*$mouth+$store[0];
		$y2 = $c_upper[$i][1]+$store[1];
		$stringu .= " " . $c_upper[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_lower as $key => $c) {
		$x = $c[0]*$mouth+$store[0];
		$y = $c[1]+$store[1];
		$stringlow .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_lower)-1; $i>=0; $i--) {
		$x2 = -$c_lower[$i][0]*$mouth+$store[0];
		$y2 = $c_lower[$i][1]+$store[1];
		$stringlow .= " " . $c_lower[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_cheek as $key => $c) {
		$x = $c[0]+$store[0];
		$y = $c[1]+$store[1];
		$stringc1 .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_cheek)-1; $i>=0; $i--) {
		$x2 = -$c_cheek[$i][0]+$store[0];
		$y2 = $c_cheek[$i][1]+$store[1];
		$stringc2 .= " " . $c_cheek[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_brow as $key => $c) {
		$x = $c[0]+$store[0];
		$y = $c[1]+$store[1];
		$stringb1 .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_brow)-1; $i>=0; $i--) {
		$x2 = -$c_brow[$i][0]+$store[0];
		$y2 = $c_brow[$i][1]+$store[1];
		$stringb2 .= " " . $c_brow[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_eye as $key => $c) {
		$x = $c[0]+$store[0];
		$y = $c[1]+$store[1];
		$stringe1 .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_eye)-1; $i>=0; $i--) {
		$x2 = -$c_eye[$i][0]+$store[0];
		$y2 = $c_eye[$i][1]+$store[1];
		$stringe2 .= " " . $c_eye[$i][3] . " " . $x2 . " " . $y2;
	}
	
	foreach ($c_eb as $key => $c) {
		$x = $c[0]+$store[0];
		$y = $c[1]+$store[1];
		$stringeb1 .= $c[2] . " " . $x . " " . $y . " ";
	}
	
	for ($i = sizeof($c_eb)-1; $i>=0; $i--) {
		$x2 = -$c_eb[$i][0]+$store[0];
		$y2 = $c_eb[$i][1]+$store[1];
		$stringeb2 .= " " . $c_eb[$i][3] . " " . $x2 . " " . $y2;
	}
	
	echo "<svg width='350' height='430' xmlns='http://www.w3.org/2000/svg'>";
	echo "<defs>";
	echo "<clipPath id='bald'>";
	echo "<path d='$stringh z' />";
	echo "</clipPath>";
	echo "<clipPath id='faces'>";
	echo "<path d='$string z' />";
	echo "</clipPath>";
	echo "<clipPath id='eye1'>";
	echo "<path d='$stringe1 z'  transform='translate($espace) rotate($er1 $ex1 $ey1)' />";
	echo "</clipPath>";
	echo "<clipPath id='eye2'>";
	echo "<path d='$stringe2 z' transform='translate(-$espace) rotate($er2 $ex2 $ey2)' />";
	echo "</clipPath>";
	echo "</defs>";
	
	echo "<path id='face' d='$string z' stroke='$edge' fill='$skincolor' />";
	
	echo "<path id='upperlip' d='$stringu z' stroke='$edge' fill='$lip' transform='translate(0 $lipvertical)'/>";
	echo "<path id='lowerlip' d='$stringlow z' stroke='$edge' fill='$lip2' transform='translate(0 $lipvertical)'/>";
	
	echo "<path id='nose' d='$stringn z' stroke='$edge' fill='$skinshadow' transform='translate(0 $nshadow_lower)' />";
	echo "<path id='nosehi' d='$stringnh z' stroke='$skinhi' fill='$skinhi' />";
	
	echo "<path id='leftcheek' d='$stringc1 z' stroke='$cheekedge' fill='$cheekshadow' clip-path='url(#faces)'/>";
	echo "<path id='rightcheek' d='$stringc2 z' stroke='$cheekedge' fill='$cheekshadow' clip-path='url(#faces)' />";
	
	echo "<path id='leftbrow' d='$stringb1 z' stroke='$edge' fill='$skinshadow' transform='translate($espace) rotate($er1 $ex1 $ey1)' />";
	echo "<path id='rightbrow' d='$stringb2 z' stroke='$edge' fill='$skinshadow' transform='translate($espace2) rotate($er2 $ex2 $ey2)' />";
	echo "<path id='leftebrow' d='$stringeb1 z' stroke='$edge2' fill='$haircolor' transform='translate($espace) rotate($ebr1 $ex1 $ey1)' />";
	echo "<path id='rightebrow' d='$stringeb2 z' stroke='$edge2' fill='$haircolor' transform='translate($espace2) rotate($ebr2 $ex2 $ey2)' />";
	
	echo "<path id='hair' d='$string2 z' stroke='$edge2' fill='$haircolor' stroke-width='3' fill-opacity='$thin' stroke-opacity='$thin' clip-path='url(#bald)' />";
	
	echo "<g clip-path='url(#eye1)'>";
	echo "<path id='lefteye' d='$stringe1 z' stroke='$edge' fill='$eyewhite' stroke-width='2' transform='translate($espace) rotate($er1 $ex1 $ey1)' />";
	echo "<circle cx='$ex1' cy='$ey1' r='8' stroke='black' fill='$eye' transform='translate($espace) rotate($er1 $ex1 $ey1)' />";
	echo "<circle cx='$ex1' cy='$ey1' r='3' stroke='black' fill='black' transform='translate($espace) rotate($er1 $ex1 $ey1)' />";
	echo "</g>";
	
	echo "<g clip-path='url(#eye2)'>";
	echo "<path id='righteye' d='$stringe2 z' stroke='$edge' fill='$eyewhite' stroke-width='2' transform='translate($espace2) rotate($er2 $ex2 $ey2)' />";
	echo "<circle cx='$ex2' cy='$ey2' r='8' stroke='black' fill='$eye' transform='translate($espace2) rotate($er2 $ex2 $ey2)' />";
	echo "<circle cx='$ex2' cy='$ey2' r='3' stroke='black' fill='black' transform='translate($espace2) rotate($er2 $ex2 $ey2)' />";
	echo "</g>";
	echo "</svg>";
}



?>
