<?php
include_once("field_sorter.inc.php");
include_once("class_obj.inc.php");
include_once("class_global_map.inc.php");
include_once("class_project_type.inc.php");
include_once("class_group.inc.php");
include_once("generic.inc.php");
include_once("constants.php");

Class LocalMap {

	private $mysqli;
	public $globalx;
	public $globaly;
	private $soilpath = "";
	private $rowpath = "";
	private $plantspath = "";
	private $altpath = "";
	
	public function __construct($mysqli, $gx, $gy) {
		
		$this->mysqli = $mysqli;
		$this->globalx = $gx;
		$this->globaly = $gy;
	}
	
	function getMinMax($level, $type) {
		if ($type == "water") {
			if ($level>229) {
				$min = 71;
				$max = $level;
			}
			else if ($level>203) {
				$min = 71;
				$max = 250;
			}
			else if ($level>179) {
				$min = 61;
				$max = 240;
			}
			else if ($level>153) {
				$min = 51;
				$max = 230;
			}
			else if ($level>110) {
				$min = 41;
				$max = 170;
			}
			else if ($level>70) {
				$min = 31;
				$max = 110;
			}
			else if ($level>30) {
				$min = 11;
				$max = 70;
			}
			else if ($level>10) {
				$min = 0;
				$max = $level+25;
			}
			else {
				$min = 0;
				$max = $level+21;
			}
		}
		if ($type == "rocky") {
			if ($level>229) {
				$min = $level-195;
				$max = $level;
			}
			else if ($level>203) {
				$min = $level-180;
				$max = $level;
			}
			else if ($level>179) {
				$min = $level-152;
				$max = $level+22;
			}
			else if ($level>153) {
				$min = $level-126;
				$max = 180;
			}
			else if ($level>127) {
				$min = $level-100;
				$max = $level+16;
			}
			else if ($level>101) {
				$min = $level-59;
				$max = $level+16;
			}
			else if ($level>60) {
				$min = $level-29;
				$max = $level;
			}
			else if ($level>30) {
				$min = $level-9;
				$max = 80;
			}
			else if ($level>10) {
				$min = $level;
				$max = 70;
			}
			else {
				$min = 0;
				$max = $level+59;
			}
		}
		if ($type == "bush") {
			if ($level>229) {
				$min = $level-195;
				$max = $level;
			}
			else if ($level>203) {
				$min = $level-180;
				$max = $level;
			}
			else if ($level>179) {
				$min = $level-152;
				$max = 191;
			}
			else if ($level>153) {
				$min = $level-126;
				$max = $level-38;
			}
			else if ($level>127) {
				$min = $level-100;
				$max = $level+27;
			}
			else if ($level>101) {
				$min = $level-59;
				$max = $level+1;
			}
			else if ($level>60) {
				$min = $level-29;
				$max = $level+30;
			}
			else if ($level>30) {
				$min = $level-9;
				$max = 63;
			}
			else if ($level>10) {
				$min = $level;
				$max = 62;
			}
			else {
				$min = 0;
				$max = 0;
			}
		}
		if ($type == "grass") {
			if ($level>229) {
				$min = $level-75;
				$max = $level;
			}
			else if ($level>203) {
				$min = $level-180;
				$max = $level;
			}
			else if ($level>179) {
				$min = $level-152;
				$max = 191;
			}
			else if ($level>153) {
				$min = $level-86;
				$max = $level-38;
			}
			else if ($level>127) {
				$min = $level-100;
				$max = $level+27;
			}
			else if ($level>101) {
				$min = $level-59;
				$max = $level+1;
			}
			else if ($level>60) {
				$min = $level-29;
				$max = $level+30;
			}
			else if ($level>30) {
				$min = $level-9;
				$max = 63;
			}
			else if ($level>10) {
				$min = $level;
				$max = 62;
			}
			else {
				$min = 0;
				$max = 0;
			}
		}
		if ($type == "organic") {
			if ($level>229) {
				$min = 128;
				$max = $level;
			}
			else if ($level>203) {
				$min = 128;
				$max = $level;
			}
			else if ($level>179) {
				$min = 60;
				$max = $level;
			}
			else if ($level>153) {
				$min = 60;
				$max = $level;
			}
			else if ($level>127) {
				$min = 60;
				$max = $level;
			}
			else if ($level>101) {
				$min = $level-59;
				$max = 60;
			}
			else if ($level>60) {
				$min = $level-29;
				$max = 60;
			}
			else if ($level>30) {
				$min = $level-20;
				$max = 60;
			}
			else if ($level>10) {
				$min = 10;
				$max = $level+30;
			}
			else {
				$min = 0;
				$max = $level+30;
			}
		}
		if ($type == "altitude") {
			$min = 0;
			$max = $level;
		}
		
		return array(
			"min" => $min,
			"max" => $max
			);
	}
	
	function checkIfExists() {
		$globalPosition = new GlobalMap($this->mysqli, $this->globalx, $this->globaly);
		
		//check if coords is water
		
		$water = $globalPosition->getLevel("water");
		$globalWater = $globalPosition->toPercent($water);
		
		if ($globalWater>90) {
			$this->soilpath = getGamePath() . "/graphics/local/soil.png";
			$this->rowpath = getGamePath() . "/graphics/local/water.png";
			$this->plantspath = getGamePath() . "/graphics/local/plants.png";
			
			return -1;
		}
		else {
			$mapCoords = $globalPosition->getLocalMap($this->globalx, $this->globaly);
			$padx = sprintf('%04d', $mapCoords["x"]);
			$pady = sprintf('%04d', $mapCoords["y"]);
			$filename = getGamePath() . "/graphics/local/soil_" . $padx . "-" . $pady . ".png";
			$filename2 = getGamePath() . "/graphics/local/row_" . $padx . "-" . $pady . ".png";
			$filename3 = getGamePath() . "/graphics/local/plants_" . $padx . "-" . $pady . ".png";
			$filename4 = getGamePath() . "/graphics/local/altitude_" . $padx . "-" . $pady . ".png";
			
			$filecheck = @getimagesize($filename);
			$filecheck2 = @getimagesize($filename2);
			$filecheck3 = @getimagesize($filename3);
			$filecheck4 = @getimagesize($filename4);
			
			
			if ($filecheck&&$filecheck2&&$filecheck3&&$filecheck4) {
				$this->soilpath = $filename;
				$this->rowpath = $filename2;
				$this->plantspath = $filename3;
				$this->altpath = $filename4;
				
				return 1;
			}
			else return -2;
		}
	}
	
	function generate() {
		$globalPosition = new GlobalMap($this->mysqli, $this->globalx, $this->globaly);	
		$neighbors = $globalPosition->getNeighbors();
		
		$nPosition = new GlobalMap($this->mysqli, $neighbors["n"][0], $neighbors["n"][1]);
		$ePosition = new GlobalMap($this->mysqli, $neighbors["e"][0], $neighbors["e"][1]);
		$sPosition = new GlobalMap($this->mysqli, $neighbors["s"][0], $neighbors["s"][1]);
		$wPosition = new GlobalMap($this->mysqli, $neighbors["w"][0], $neighbors["w"][1]);
		
		$water = $globalPosition->getLevel("water");
		$nwater = $nPosition->getLevel("water");
		$ewater = $ePosition->getLevel("water");
		$swater = $sPosition->getLevel("water");
		$wwater = $wPosition->getLevel("water");
		
		$altitude = $globalPosition->getLevel("altitude");
		$n_alt = $nPosition->getLevel("altitude");
		$e_alt = $ePosition->getLevel("altitude");
		$s_alt = $sPosition->getLevel("altitude");
		$w_alt = $wPosition->getLevel("altitude");
		
		$grass = $globalPosition->getLevel("grass");
		$brush = $globalPosition->getLevel("brush");
		$trees = $globalPosition->getLevel("trees");
		
		$rocky = $globalPosition->getLevel("rocky");
		$organic = $globalPosition->getLevel("organic");
		$water = $globalPosition->getLevel("water");
		$sand = $globalPosition->getLevel("sand");
		$silt = $globalPosition->getLevel("silt");
		$clay = $globalPosition->getLevel("clay");
		$terrains = $globalPosition->getTerrains();
		
		if ($clay>85) $dominant = "clay";
		else if ($silt>$sand&&$silt>$clay) $dominant = "silt";
		else if ($sand>$silt) $dominant = "sand";
		else $dominant = "loam";
		
		$isForest = false;
		$isSavanna = false;
		$isJungle = false;
		$isDesert = false;
		$isMountain = false;
		
		if (searchSingle($terrains, 1, "deciduous")||searchSingle($terrains, 1, "taiga")) $isForest = true;
		if (searchSingle($terrains, 1, "desert")) $isDesert = true;
		if (searchSingle($terrains, 1, "rainforest")) $isJungle = true;
		if (searchSingle($terrains, 1, "savanna")) $isSavanna = true;
		if (searchSingle($terrains, 1, "mountains")) $isMountain = true;

	}
	
	function loadcreate() {
		$check=$this->checkIfExists();
		if ($check==-2) {
			$globalPosition = new GlobalMap($this->mysqli, $this->globalx, $this->globaly);
			$mapCoords = $globalPosition->getLocalMap($this->globalx, $this->globaly);
			$water = $globalPosition->getLevel("water");
			$neighbors = $globalPosition->getNeighbors();
			
			$nPosition = new GlobalMap($this->mysqli, $neighbors["n"][0], $neighbors["n"][1]);
			$ePosition = new GlobalMap($this->mysqli, $neighbors["e"][0], $neighbors["e"][1]);
			$sPosition = new GlobalMap($this->mysqli, $neighbors["s"][0], $neighbors["s"][1]);
			$wPosition = new GlobalMap($this->mysqli, $neighbors["w"][0], $neighbors["w"][1]);
			
			$nwater = $nPosition->getLevel("water");
			$ewater = $ePosition->getLevel("water");
			$swater = $sPosition->getLevel("water");
			$wwater = $wPosition->getLevel("water");
			
			$altitude = $globalPosition->getLevel("altitude");
			$n_alt = $nPosition->getLevel("altitude");
			$e_alt = $ePosition->getLevel("altitude");
			$s_alt = $sPosition->getLevel("altitude");
			$w_alt = $wPosition->getLevel("altitude");
			
			$n_alt_diff = min(60,max(0,($altitude-$n_alt+30)))*4;
			$e_alt_diff = min(60,max(0,($altitude-$e_alt+30)))*4;
			$s_alt_diff = min(60,max(0,($altitude-$s_alt+30)))*4;
			$w_alt_diff = min(60,max(0,($altitude-$w_alt+30)))*4;
			
			if ($n_alt==129) $n_alt_diff=0;
			if ($e_alt==129) $e_alt_diff=0;
			if ($s_alt==129) $s_alt_diff=0;
			if ($w_alt==129) $w_alt_diff=0;
			
			$padx = sprintf('%04d', $mapCoords["x"]);
			$pady = sprintf('%04d', $mapCoords["y"]);
			
			$filename = getGamePath() . "/graphics/local/soil_" . $padx . "-" . $pady . ".png";
			$filename2 = getGamePath() . "/graphics/local/row_" . $padx . "-" . $pady . ".png";
			$filename3 = getGamePath() . "/graphics/local/plants_" . $padx . "-" . $pady . ".png";
			$filename4 = getGamePath() . "/graphics/local/altitude_" . $padx . "-" . $pady . ".png";
			
			
			$grass = $globalPosition->getLevel("grass");
			$brush = $globalPosition->getLevel("brush");
			$trees = $globalPosition->getLevel("trees");
			
			$green = $altitude-129/10 + rand(0, 50);
			$rocky = $globalPosition->getLevel("rocky");
			$organic = $globalPosition->getLevel("organic");
			$water = $globalPosition->getLevel("water");
			$sand = $globalPosition->getLevel("sand");
			$silt = $globalPosition->getLevel("silt");
			$clay = $globalPosition->getLevel("clay");
			$terrains = $globalPosition->getTerrains();
			
			if ($clay>85) $dominant = "clay";
			else if ($silt>$sand&&$silt>$clay) $dominant = "silt";
			else if ($sand>$silt) $dominant = "sand";
			else $dominant = "loam";
			
			$clayChance = round($clay/255);
			
			$isForest = false;
			$isSavanna = false;
			$isJungle = false;
			$isDesert = false;
			
			if (searchSingle($terrains, 1, "deciduous")) {
				$isForest = true;
			}
			if (searchSingle($terrains, 1, "desert")) {
				$isDesert = true;
			}
			
			if (searchSingle($terrains, 1, "rainforest")) {
				$isJungle = true;
			}
			if (searchSingle($terrains, 1, "savanna")) {
				$isSavanna = true;
			}
			
			if (searchSingle($terrains, 1, "taiga")) {
				$isForest = true;
			}
			
			
			$grassMM = $this->getMinMax($grass, "grass");
			$brushMM = $this->getMinMax($brush, "bush");
			$altMM = $this->getMinMax($altitude, "altitude");
			$waterMM = $this->getMinMax($water, "water");
			$organicMM = $this->getMinMax($organic, "organic");
			$rockyMM = $this->getMinMax($rocky, "rocky");
			
			if ($isSavanna) {
				if ($isJungle) $treesMM = array(
					"min" => 52,
					"max" => 58
					);
				else if ($isForest) $treesMM = array(
					"min" => 25,
					"max" => 58
					);
				else $treesMM = array(
					"min" => 0,
					"max" => 58
					);
			}
			else if ($isJungle) $treesMM = array(
				"min" => max($trees-100, 20),
				"max" => $trees
				);
			else if ($isForest) $treesMM = array(
				"min" => 10,
				"max" => max($trees, 160)
				);
			else if ($isDesert) $treesMM = array(
				"min" => 0,
				"max" => max($trees, 57)
				);
			else {
				if ($trees>55) $treesMM = array(
					"min" => $trees-55,
					"max" => $trees
					);
				else if ($trees==0) $treesMM = array(
					"min" => 0,
					"max" => 1
					);
				else $treesMM = array(
					"min" => $trees,
					"max" => 56
					);
			}
			
			$terUrl = getGamePath() . "/graphics/blue/blue" . rand(3,21) . ".png";//picks a template
			if (!@getimagesize($terUrl)) para("Could not read altitude map.");

			$im4=imagecreatefrompng($terUrl);
			$tarr = array();
			
			for ($y=0;$y<100;$y++) {
				for ($x=0;$x<100;$x++) {
				$rgb=imagecolorat($im4, $x, $y);
				
				$blue = min(max(round($rgb & 0xFF + rand($altMM["min"], $altMM["max"])),0),255);
				
				$tarr[$x][$y] = $blue;
				}
			}
			
			$arr = array();
			$arr2 = array();
			$arr3 = array();
			$arr4 = array();
			
			for ($y=0; $y<101; $y++)
			{
				for ($x=0; $x<101; $x++)
				{
					$alt_green[$x][$y] = round((($x/100  * $e_alt_diff) + ((1-($x/100)) * $w_alt_diff) + ($y/100  * $s_alt_diff) + ((1-($y/100)) * $n_alt_diff))/2);
					
					if ($y==0) {
						
						if ($dominant=="silt") {
							if (rand(0,100)<$clayChance) $rgb = array(75, 87, 107);//clay
							else if (rand(0,255)<$sand) $rgb = array(152, 112, 82);//silt loam
							else $rgb = array(109,62,56);
						}
						else if ($dominant=="sand") {
							if (rand(0,100)<$clayChance) $rgb = array(141, 138, 114);//sandy clay
							else if (rand(0,255)<$silt) $rgb = array(141, 112, 84);//sandy loam
							else $rgb = array(172,157,125);//sand
						}
						else if ($dominant=="clay") {
							if (rand(0,100)<$clayChance) $rgb = array(75, 87, 107);//clay
							else if (rand(0,255)<$sand) $rgb = array(141, 138, 114);//sandy clay
							else if (rand(0,255)<$silt) $rgb = array(94, 88, 81);//clay loam
							else $rgb = array(51,58,50);//loam
						}
						else {
							if (rand(0,100)<$clayChance) {
								if (rand(0,5)==0) $rgb = array(75, 87, 107);//clay
								else $rgb = array(94,88,81);
							}
							else if (rand(0,255)<$sand) $rgb = array(141, 112, 84);//sandy loam
							else if (rand(0,255)<$silt) $rgb = array(152, 112, 82);//silt loam
							else $rgb = array(51, 58, 50);//loam
						}
						
						$r2 = rand($rockyMM["min"], $rockyMM["max"]);
						$g2 = rand($organicMM["min"], $organicMM["max"]);
						$b2 = rand($waterMM["min"], $waterMM["max"]);
						
						if ($nwater>=230) $b2=255;
						if ($wwater>=230&&$x<20) $b2=255;
						if ($ewater>=230&&$x>80) $b2=255;
						
						if ($nwater>230&&$ewater>230) $b2=255;
						
						$rgb2 = array($r2, $g2, $b2);//rocky, organic and water aren't bound to each other so their sum doesn't need to be 255
						
						$r3 = rand($grassMM["min"], $grassMM["max"]);
						$g3 = rand($brushMM["min"], $brushMM["max"]);
						$b3 = rand($treesMM["min"], $treesMM["max"]);
						
						
						if ($b2>217||$b2<28) {
							$r3 = 0;
							$g3 = 0;
							$b3 = 0;
						}
						else if ($g2<26) {
							$r3 = 0;
							$b3 = round($b3/2);
						}
						else if ($r2>230) {
							$r3 = 0;
							$g3 = 0;
							$b3 = 0;
						}
						if ($b3<51) $b3 = 0;
						
						$rgb3 = array($r3, $g3, $b3);
					}
					
					else
					{
						$random = rand(1,7);
						if ($random<4&&$x>0) {
							$source=($y-1)*101+$x;//pixel above
							
							$r = $arr[$source][2];
							$g = $arr[$source][3];
							$b = $arr[$source][4];
							$rgb = array($r, $g, $b);
						}
						else if ($random<7&&$x>0) {
							$source=$y*101+$x-1;//pixel to the left
							
							$r = $arr[$source][2];
							$g = $arr[$source][3];
							$b = $arr[$source][4];
							$rgb = array($r, $g, $b);
						}
						else {
							if ($dominant=="silt") {
								if (rand(0,100)<$clayChance) $rgb = array(75, 87, 107);//clay
								else if (rand(0,255)<$sand) $rgb = array(152, 112, 82);//silt loam
								else $rgb = array(109, 62, 56);
							}
							else if ($dominant=="sand") {
								if (rand(0,100)<$clayChance) $rgb = array(141, 138, 114);//sandy clay
								else if (rand(0,255)<$silt) $rgb = array(141, 112, 84);//sandy loam
								else $rgb = array(172,157,125);//sand
							}
							else if ($dominant=="clay") {
								if (rand(0,100)<$clayChance) $rgb = array(75, 87, 107);//clay
								else if (rand(0,255)<$sand) $rgb = array(141, 138, 114);//sandy clay
								else if (rand(0,255)<$silt) $rgb = array(94, 88, 81);//clay loam
								else $rgb = array(51,58,50);//loam
							}
							else {
								if (rand(0,100)<$clayChance) {
									if (rand(0,5)==0) $rgb = array(75, 87, 107);//clay
									else $rgb = array(94,88,81);
								}
								else if (rand(0,255)<$sand) $rgb = array(141, 112, 84);//sandy loam
								else if (rand(0,255)<$silt) $rgb = array(152, 112, 82);//silt loam
								else $rgb = array(51, 58, 50);//loam
							}
						}
						
						if (rand(1,4)<4&&$x>0) {
							$source=($y-1)*101+$x;//pixel above
							$source2=$y*101+$x-1;//pixel to the left
							
							$r2 = round(($arr2[$source][2] + $arr2[$source2][2])/2);
							$g2 = round(($arr2[$source][3] + $arr2[$source2][3])/2);
							$b2 = round(($arr2[$source][4] + $arr2[$source2][4])/2);
						}
						else {
							$r2 = rand($rockyMM["min"], $rockyMM["max"]);
							$g2 = rand($organicMM["min"], $organicMM["max"]);
							$b2 = rand($waterMM["min"], $waterMM["max"]);
						}
						
						if (rand(1,7)<3&&$x>0) {
							$source=($y-1)*101+$x;//pixel above
							$source2=$y*101+$x-1;//pixel to the left
							//This makes water attract water
							if ($arr2[$source][4]>229||$arr2[$source2][4]>229) $b2 = 255;
						}
						
						if ($swater>=230&&$y>80) $b2=255;
						if ($ewater>=230&&$x>80) $b2=255;
						if ($nwater>=230&&$y<20) $b2=255;
						if ($wwater>=230&&$x<20) $b2=255;
						
						if ($nwater>230&&$ewater>230) {
							$curve = 0.009*(pow($x, 2))+16;
							if ($y<$curve) $b2=255;
						}
						
						if ($nwater>230&&$wwater>230) {
							$curve = 0.009*(pow(100-$x, 2))+16;
							if ($y<$curve) $b2=255;
						}
						
						if ($swater>230&&$ewater>230) {
							$curve = 0.009*(pow($x, 2))-80;
							if (-$y<$curve) $b2=255;
						}
						
						if ($swater>230&&$wwater>230) {
							$curve =0.009*(pow(100-$x, 2))-80;
							if (-$y<$curve) $b2=255;
						}
						
						if ($ewater>230&&rand(70,230)<$x) $b2=255;
						if ($swater>230&&rand(70,230)<$y) $b2=255;
						
						$rgb2 = array($r2, $g2, $b2);
						
						$random2 = rand(1,12);
						
						if ($random2<5&&$x>0) {
							$source=($y-1)*101+$x;//pixel above
							$source2=$y*101+$x-1;//pixel to the left
							
							$r3 = round(($arr3[$source][2] + $arr3[$source2][2])/2);
							$g3 = round(($arr3[$source][3] + $arr3[$source2][3])/2);
							$b3 = round(($arr3[$source][4] + $arr3[$source2][4])/2);
						}
						else if ($random2<10&&$x>0) {
							$source=($y-1)*101+$x;//pixel above
							$source2=$y*101+$x-1;//pixel to the left
							
							if ($arr3[$source][2]<55||$arr3[$source2][2]<55) $r3=0;
							else {
								$r3 = round(($arr3[$source][2] + $arr3[$source2][2])/2);
							}
							if ($arr3[$source][3]<55||$arr3[$source2][3]<55) $g3=0;
							else {
								$g3 = round(($arr3[$source][3] + $arr3[$source2][3])/2);
							}
							if ($arr3[$source][4]<55||$arr3[$source2][4]<55) $b3=0;
							else {
								$b3 = round(($arr3[$source][4] + $arr3[$source2][4])/2);
							}
						}
						else {
							$r3 = rand($grassMM["min"], $grassMM["max"]);
							$g3 = rand($brushMM["min"], $brushMM["max"]);
							$b3 = rand($treesMM["min"], $treesMM["max"]);
							
							
						}
						
						if ($b2>217||$b2<28) {
							$r3 = round($r3/100);
							$g3 = 0;
							$b3 = round($b3/100);
						}
						else if ($g2<26) {
							$r3 = 0;
							$b3 = round($b3/2);
						}
						else if ($r2>230) {
							$r3 = 0;
							$g3 = 0;
							$b3 = 0;
						}
						
						$rgb3 = array($r3, $g3, $b3);
					}
					
					$arr[] = array($x, $y, $rgb[0], $rgb[1], $rgb[2]);
					$arr2[] = array($x, $y, $rgb2[0], $rgb2[1], $rgb2[2]);
					$arr3[] = array($x, $y, $rgb3[0], $rgb3[1], $rgb3[2]);
					if ($x<100&&$y<100) $arr4[] = array($x, $y, $altitude, $alt_green[$x][$y], $tarr[$x][$y]);
				}
			}
			$max_height = 100;
			$max_width = 100;
			/*
			foreach ($arr as $a)
			{
			//para("x " . $a[0] . " y " . $a[1] . " r " . $a[2] . " g " . $a[3]. " b " . $a[4]);
			    if ($a[0] > $max_width)
			    {
				$max_width = $a[0];
			    }
			    if ($a[1] > $max_height)
			    {
				$max_height = $a[1];
			    }
			}
			*/
			
			$im=imagecreatetruecolor($max_width, $max_height);
			
			foreach ($arr as $b)
			{
			    $col=imagecolorallocate($im, $b[2], $b[3], $b[4]);
			    imagesetpixel ($im , $b[0], $b[1], $col);
			}
			
			imagepng($im, "$filename", NULL, NULL);
			imagedestroy($im);
			
			$im2=imagecreatetruecolor($max_width, $max_height);
			foreach ($arr2 as $b)
			{
			    $col=imagecolorallocate($im2, $b[2], $b[3], $b[4]);
			    imagesetpixel ($im2 , $b[0], $b[1], $col);
			}
		
			imagepng($im2, "$filename2", NULL, NULL);
			imagedestroy($im2);
			
			$im3=imagecreatetruecolor($max_width, $max_height);
			foreach ($arr3 as $b)
			{
			    $col=imagecolorallocate($im3, $b[2], $b[3], $b[4]);
			    imagesetpixel ($im3, $b[0], $b[1], $col);
			}
		
			imagepng($im3, "$filename3", NULL, NULL);
			imagedestroy($im3);
			
			$im5=imagecreatetruecolor($max_width, $max_height);
			foreach ($arr4 as $b)
			{
			    $col=imagecolorallocate($im5, $b[2], $b[3], $b[4]);
			    imagesetpixel ($im5, $b[0], $b[1], $col);
			}
		
			imagepng($im5, "$filename4", NULL, NULL);
			imagedestroy($im5);
			
			if (@getimagesize($filename)) $this->soilpath = $filename;
			else {
				$this->soilpath = getGamePath() . "/graphics/local/soil.png";
				return -1;
			}
			
			if (@getimagesize($filename2)) $this->rowpath = $filename2;
			else {
				$this->rowpath = getGamePath() . "/graphics/local/unknown_row.png";
				return -1;
			}
			
			if (@getimagesize($filename3)) $this->plantspath = $filename3;
			else {
				$this->plantspath = getGamePath() . "/graphics/local/plants.png";
				return -1;
			}
			
			if (@getimagesize($filename4)) $this->altpath = $filename4;
			else {
				$this->altsurl = getGamePath() . "/graphics/local/unknown_alt.png";
				return -1;
			}
			
			return 1;
		}
		else return -2;
	}
	
	function readSpecific($px, $py, $type) {
		//if type 1 - soil, type 2 - plants, type 3 - rocks organic water, 4 - altitude
		if ($type==1) $im=imagecreatefrompng($this->soilpath);
		if ($type==2) $im=imagecreatefrompng($this->plantspath);
		if ($type==3) $im=imagecreatefrompng($this->rowpath);
		if ($type==4) $im=imagecreatefrompng($this->altpath);
		
		if ($im) {
			$result = $this->readPixel($im, $px, $py);
			if (is_array($result)) {
				if ($type==1) return array("sand" => $result[0], "silt" => $result[1], "clay" => $result[2]);
				if ($type==2) return array("grass" => $result[0], "bush" => $result[1], "trees" => $result[2]);
				if ($type==3) return array("rocks" => $result[0], "organic" => $result[1], "water" => $result[2]);
				if ($type==4) return array("red" => $result[0], "green" => $result[1], "blue" => $result[2]);
			}
			else return -1;
		}
		else return -1;//Loading image failed
	}
	
	function readPixel($im, $x, $y) {
		//There is a similar function in MapPixel, but this doesn't generate the $im every time separately
		if ($im) {
			$rgb=imagecolorat($im, $x, $y);
			
			$arr = array();
			$arr[0] = ($rgb >> 16) & 0xFF;
			$arr[1] = ($rgb >> 8) & 0xFF;
			$arr[2] = $rgb & 0xFF;
			return $arr;
		}
		else return -1;//Faulty image
	}
	
	function getSquares($minx, $miny, $maxx, $maxy) {
		
		$soil_im=imagecreatefrompng($this->soilpath);
		$plant_im=imagecreatefrompng($this->plantspath);
		$row_im=imagecreatefrompng($this->rowpath);
		$alt_im=imagecreatefrompng($this->altpath);
		
		$returnArray = array();
		$fieldSquares = $this->getFieldSquares();
		
		
		if ($soil_im) {
			for ($i = $miny; $i <= $maxy; $i++) {
				for ($j = $minx; $j <= $maxx; $j++) {
					
					$soil = $this->readPixel($soil_im, $j, $i);
					$plant = $this->readPixel($plant_im, $j, $i);
					$row = $this->readPixel($row_im, $j, $i);
					$altitude = $this->readPixel($alt_im, $j, $i);
					
					if (!is_array($soil) || !is_array($plant) || !is_array($row) || !is_array($altitude) ) return array();//Returns an empty array
					
					$alt = round((($altitude[0]-129)*80000+$altitude[1]*800+$altitude[2]*3)/100)/10;
					$hex = rgb2hex($soil);
					
					if ($fieldSquares) {
						foreach ($fieldSquares as $fq) {
							$match = false;
							if (round($fq["lx"]/10)==$j&&round($fq["ly"]/10)==$i) {
								$match = true;
								$hex2 = $fq["hex"];
								break;
							}
							if (!$match) $hex2 = false;
						}
					}
					else $hex2 = false;
					
					$returnArray[] = array(
						"x" => $j,
						"y" => $i,
						"hex" => $hex,
						"grass" => $plant[0],
						"bush" => $plant[1],
						"tree" => $plant[2],
						"rock" => $row[0],
						"water" => $this->analyzeWaterLevel($row[2]),
						"altitude" => $alt,
						"hex2" => $hex2);
				}//end loop
			}//end loop
		}
		
		return $returnArray;
	}
	
	function getWaterLevel($px, $py) {
		$im=imagecreatefrompng($this->rowpath);
		$rgb=imagecolorat($im, $px, $py);
		
		return $rgb & 0xFF;
	}
	
	function getVegetation($px, $py) {
		$target = new MapPixel($this->mysqli);
		return $target->readColorArray($this->plantspath, $px, $py);
	}
	
	function getROW($px, $py) {
		$target = new MapPixel($this->mysqli);
		return $target->readColorArray($this->rowpath, $px, $py);
	}
	
	function getSoil($px, $py) {
		$target = new MapPixel($this->mysqli);
		return $target->readColorArray($this->soilpath, $px, $py);
	}
	
	function getSoil2($px, $py) {
		$soil = $this->getSoil($px, $py);
		
		if ($soil["b"]==84) $type = 14;//sandy loam
		else if ($soil["b"]==81) $type = 34;//clay loam
		else if ($soil["b"]==50) $type = 4;//loam
		else if ($soil["b"]==56) $type = 2;//silt
		else if ($soil["b"]==125) $type = 1;//sand
		else if ($soil["b"]==107) $type = 3;//clay
		else if ($soil["b"]==114) $type = 13;//sandy clay
		else if ($soil["b"]==82) $type = 24;//silt loam
		else if ($soil["b"]==39) $type = 5;//compost
		else $type = 0;
		
		return $type;
	}
	
	function getDry($startX, $startY) {
		$finalX = 500;
		$finalY = 500;
		
		for ($i=0;$i<11;$i++)
		{
			//having a limited duration prevents this from ending in an infinite loop if every tested spot is water
			if ($startX<500) $xdir = 50;
			else if ($startX>500) $xdir = -50;
			else $xdir = 0;
			
			if ($startY<500) $ydir = 50;
			elseif ($startY>500) $ydir = -50;
			else $ydir = 0;
			
			if ($this->getWaterLevel(floor($startX/10), floor($startY/10))<229) {
				$finalX = $startX;
				$finalY = $startY;
				break;
			}
			else {
				$startX += $xdir;
				$startY += $ydir;
			}
		}
		
		return array($finalX, $finalY);
	}
	
	function analyzeWaterLevel($value) {
		if ($value>229) return array(
			"level" => 7,
			"desc" => "Covered in water"
			);
		else if ($value>191) return array(
			"level" => 6,
			"desc" => "Pools of stale water"
			);
		else if ($value>140) return array(
			"level" => 5,
			"desc" => "Swampy or muddy"
			);
		else if ($value>110) return array(
			"level" => 4,
			"desc" => "Slightly swampy or muddy"
			);
		else if ($value>70) return array(
			"level" => 3,
			"desc" => "Moist"
			);
		else if ($value>30) return array(
			"level" => 2,
			"desc" => "Humid"
			);
		else if ($value>10) return array(
			"level" => 1,
			"desc" => "Dry"
			);
		else return array(
			"level" => 0,
			"desc" => "Parched"
			);
	}
	
	
	
	public function printLocal_sm($centralx, $centraly, $radius) {
		$centralx = floor($centralx/10);
		$centraly = floor($centraly/10);
		
		$minx = $centralx-$radius;
		$miny = $centraly-$radius;
		$maxx = $centralx+$radius;
		$maxy = $centraly+$radius;
		if ($minx<0) $minx=0;
		if ($miny<0) $miny=0;
		if ($maxx>99) $maxx=99;
		if ($maxy>99) $maxy=99;
		
		$arr = $this->getSquares($minx, $miny, $maxx, $maxy);
		
		if ($arr) {
			para("Displaying (" . $minx*10 . "," . $miny*10 . ") to (" . $maxx*10 . "," . $maxy*10 . ").");
			for ($k=0;$k<count($arr);$k++) {
				if ($arr[$k]["x"]<=$minx) echo "<div class='row_local'>";
				$this->printSquare_sm($arr[$k]);
				if ($arr[$k]["x"]>=$maxx) echo "</div>";
			}
		}
		else echo "Error: No squares selected.";
	}
	
	function printSquare_sm($arr) {
		$val = $arr["x"] . "-" . $arr["y"];
		$bg = $arr["hex"];
		
		if ($arr["hex2"]) {
			$brd = $arr["hex2"];
			$style = "solid";
		}
		else if (($arr["tree"]<76&&$arr["bush"]<129&&$arr["rock"]<116)&&$arr["water"]["level"]>0&&$arr["water"]["level"]<3) {
			$brd = "#2200aa";//living or farming
			$style = "dotted";
		}
		else if (($arr["tree"]<76&&$arr["bush"]<129&&$arr["rock"]<116)&&$arr["water"]["level"]==3) {
			$brd = "#005500";//farming
			$style = "dashed";
		}
		else if (($arr["tree"]<76&&$arr["bush"]<129&&$arr["rock"]<116)&&$arr["water"]["level"]==0) {
			$brd = "#cccc00";//living
			$style = "dashed";
		}
		else if (($arr["tree"]<96&&$arr["rock"]<116)&&$arr["water"]["level"]>0&&$arr["water"]["level"]<3) {
			$brd = "#bb2200";//convertable
			$style = "dotted";
		}
		else {
			$brd = "#bb2200";//wasteland
			$style = "solid";
		}
		
		
		
		echo "<div class='square3' style='background-color: $bg; border-color: $brd ; border-style: $style' id='$val'>";
		echo "<img src='" . getGameRoot() . "/graphics/tiles/water-" . $arr["water"]["level"] . ".png' class='level4' />";
		
		if ($arr["tree"]>188) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-trees-3.png' class='level3' />";
		else if ($arr["tree"]>122) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-trees-2.png' class='level3' />";
		else if ($arr["tree"]>55) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-trees-1.png' class='level3' />";
		else echo "<img src='" . getGameRoot() . "/graphics/tiles/n-trees-0.png' class='level3' />";
		echo "\n";
		
		if ($arr["bush"]>191) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-bush-3.png' class='level2' />";
		else if ($arr["bush"]>128) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-bush-2.png' class='level2' />";
		else if ($arr["bush"]>60) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-bush-1.png' class='level2' />";
		else echo "<img src='" . getGameRoot() . "/graphics/tiles/n-bush-0.png' class='level2' />";
		echo "\n";
		
		if ($arr["rock"]>179) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-rock-3.png' class='level1' />";
		else if ($arr["rock"]>101) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-rock-2.png' class='level1' />";
		else if ($arr["rock"]>60) echo "<img src='" . getGameRoot() . "/graphics/tiles/n-rock-1.png' class='level1' />";
		else echo "<img src='" . getGameRoot() . "/graphics/tiles/n-rock-0.png' class='level1' />";
		echo "\n";
		
		echo "<div class='mapCheckbox'>";
		echo "<input type='checkbox' value='1' class='fieldmap' id='ch-$val' name='ch-$val' />";
		echo "<label for='ch-$val'></label>";
		echo "</div>";
		
		echo "</div>";
	}
	
	function countTrees($blue) {
		return max(0,round(($blue-55)/2));
	}
	
	function printSquare($arr, $buildings, $paths, $type, $ppl, $isnight) {
		$val = $arr["x"] . "-" . $arr["y"];
		$bg = $arr["hex"];
		/*
		for ($i=0;$i<count($paths);$i++) {
			if ($arr[0] == floor($paths[$i]["x"]/10)&&$arr[1] == floor($paths[$i]["y"]/10)) {
				$bg .= "; background-image: url(getGameRoot() . "/graphics/paths/" . $paths[$i]["file"] . ".png\")";
			}
		}*/
		$treeDesc = $this->countTrees($arr["tree"]);
		
		$buildingCounter = 0;
		for ($i=0;$i<count($buildings);$i++) {
			if ($arr["x"] == floor($buildings[$i]["x"]/10)&&$arr["y"] == floor($buildings[$i]["y"]/10)) {
				$buildingCounter++;
			}
		}
		
		$pplCounter = 0;
		for ($i=0;$i<count($ppl);$i++) {
			if ($arr["x"] == floor($ppl[$i]["x"]/10)&&$arr["y"] == floor($ppl[$i]["y"]/10)) {
				$pplCounter++;
			}
		}
		
		if ($arr["bush"]>191) $bush = "Covered in bushes";
		else if ($arr["bush"]>128) $bush = "Several bushes";
		else if ($arr["bush"]>60) $bush = "A few bushes";
		else $bush = "No bushes";
		
		echo "<div class='square' style='background-color: $bg' id='$val' onmouseenter='highlight(this)' onmouseleave='divMouseout(this)' onmouseup='processClick(this)'><span class='infobox'>Altitude: " . $arr["altitude"] . " m<br/>Trees: $treeDesc<br/>$bush<br/>Moisture: " . $arr["water"]["desc"] . "<br/>Buildings: $buildingCounter<br/>People: $pplCounter</span>\n";
		
		//if ($type=="1") echo "<input type='radio' name='square' value='$val' class='left' />\n";
		if ($type=="2") echo "<input type='checkbox' name='square' value='$val' class='left' />\n";
		
		
		if ($isnight) echo "<img src='". getGameRoot() ."/graphics/tiles/night.png' class='level9' />";
		if ($buildingCounter) echo "<img src='" . getGameRoot() . "/graphics/mini_house.png' class='level7' title='room, hut or tent' />";
		if ($pplCounter) echo "<img src='/graphics/mini_person.png' class='level8' title='person' />";
		
		if ($arr["tree"]>188) echo "<img src='" . getGameRoot() . "/graphics/tiles/forest3.png' class='level6' />";
		else if ($arr["tree"]>122) echo "<img src='" . getGameRoot() . "/graphics/tiles/forest2.png' class='level6' />";
		else if ($arr["tree"]>55) echo "<img src='" . getGameRoot() . "/graphics/tiles/forest1.png' class='level6' />";
		
		if ($arr["bush"]>191) echo "<img src='" . getGameRoot() . "/graphics/tiles/bush3.png' class='level5' />";
		else if ($arr["bush"]>128) echo "<img src='" . getGameRoot() . "/graphics/tiles/bush2.png' class='level5' />";
		else if ($arr["bush"]>60) echo "<img src='" . getGameRoot() . "/graphics/tiles/bush1.png' class='level5' />";
		
		for ($i=0;$i<count($paths);$i++) {
			if ($arr["x"] == floor($paths[$i]["x"]/10)&&$arr["y"] == floor($paths[$i]["y"]/10)) {
				echo "<img src='" . getGameRoot() . "/graphics/paths/" . $paths[$i]["file"] . ".png' class='level4' />";
			}
		}
		
		if ($arr["rock"]>179) echo "<img src='" . getGameRoot() . "/graphics/tiles/rock3.png' class='level3' />";
		else if ($arr["rock"]>101) echo "<img src='" . getGameRoot() . "/graphics/tiles/rock2.png' class='level3' />";
		else if ($arr["rock"]>60) echo "<img src='" . getGameRoot() . "/graphics/tiles/rock1.png' class='level3' />";
		
		if ($arr["water"]["level"]==7) echo "<img src='" . getGameRoot() . "/graphics/tiles/water3.png' class='level2' />";
		else if ($arr["water"]["level"]==6) echo "<img src='" . getGameRoot() . "/graphics/tiles/water2.png' class='level2' />";
		else if ($arr["water"]["level"]>=4) echo "<img src='" . getGameRoot() . "/graphics/tiles/water1.png' class='level2' />";
		
		if ($arr["grass"]>191) echo "<img src='" . getGameRoot() . "/graphics/tiles/grass3.png' class='level1' />";
		else if ($arr["grass"]>128) echo "<img src='" . getGameRoot() . "/graphics/tiles/grass2.png' class='level1' />";
		else if ($arr["grass"]>60) echo "<img src='" . getGameRoot() . "/graphics/tiles/grass1.png' class='level1' />";
		else if ($type=="1") echo "<img src='" . getGameRoot() . "/graphics/tiles/grass0.png' class='level1' />";//type 2 will reveal ground if there is no grass
		
		echo "</div>\n";
	}
	
	public function getBuildings() {
		$sql = "SELECT `uid`, `presetFK`, `local_x`, `local_y` FROM `objects` WHERE `general_type`=3 AND `parent`=0 AND `global_x`=$this->globalx AND `global_y`=$this->globaly";
		$result = $this->mysqli->query($sql);
		$arr = array();
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"type" => $row[1],
					"x" => $row[2],
					"y" => $row[3]
					);
			}
		}
		return $arr;
	}
	
	public function getPaths() {
		$sql = "SELECT `uid`, `file`, `local_x`, `local_y` FROM `paths` WHERE `global_x`=$this->globalx AND `global_y`=$this->globaly";
		$result = $this->mysqli->query($sql);
		$arr = array();
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"file" => $row[1],
					"x" => $row[2],
					"y" => $row[3]
					);
			}
		}
		return $arr;
	}
	
	public function getPeople() {
		$sql = "SELECT `uid`, `presetFK`, `local_x`, `local_y` FROM `objects` WHERE `general_type`=2 AND `parent`=0 AND `global_x`=$this->globalx AND `global_y`=$this->globaly";
		$result = $this->mysqli->query($sql);
		$arr = array();
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"uid" => $row[0],
					"type" => $row[1],
					"x" => $row[2],
					"y" => $row[3]
					);
			}
		}
		return $arr;
	}
	
	public function printLocal($centralx, $centraly, $radius, $type, $isnight=false) {
		$centralx = floor($centralx/10);
		$centraly = floor($centraly/10);
		
		$minx = $centralx-$radius;
		$miny = $centraly-$radius;
		$maxx = $centralx+$radius;
		$maxy = $centraly+$radius;
		if ($minx<0) $minx=0;
		if ($miny<0) $miny=0;
		if ($maxx>99) $maxx=99;
		if ($maxy>99) $maxy=99;
		
		$arr = $this->getSquares($minx, $miny, $maxx, $maxy);
		$buildings = $this->getBuildings();
		$paths = $this->getPaths();
		$ppl = $this->getPeople();
		
		if ($arr) {
			for ($k=0;$k<count($arr);$k++) {
				if ($arr[$k]["x"]<=$minx) echo "<div class='row_local'>";
				$this->printSquare($arr[$k], $buildings, $paths, $type, $ppl, $isnight);
				if ($arr[$k]["x"]>=$maxx) echo "</div>";
			}
		}
		else echo "Error: No squares selected.";
	}
	
	function getObjects($gtype, $charid, $container='') {
		//1, 5 - objects, resources
		//6 - fixed structures
		$observer = new Character($this->mysqli, $charid);
		$pos = $observer->getPosition();
		$curTime = new Time($this->mysqli);
		if ($container=='container') $container = "AND `presetFK` IN (SELECT `o_presetFK` FROM `pr_attrs` WHERE `attributeFK` IN (2, 7) AND `value`>0 GROUP BY `o_presetFK`)";
		$sql = "SELECT `uid` FROM `objects` WHERE `global_x`=$this->globalx AND `global_y`=$this->globaly AND `local_x`=$pos->lx AND `local_y`=$pos->ly AND `general_type` IN ($gtype) AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "')) $container ORDER BY FIELD(`general_type`,'5',`general_type`), `date_created` DESC, `weight` DESC";
		$result = $this->mysqli->query($sql);
		$arr = array();
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = $row[0];
			}
			return $arr;
		}
		else return false;
	}
	
	function getContainers($charid) {
		$containers = array();
		$uids = $this->getObjects("1, 6", $charid);
		if ($uids) {
			for ($i=0; $i<count($uids); $i++) {
				$obj = new Obj($this->mysqli, $uids[$i]);
				$obj->getBasicData();
				$isLgContainer = $obj->getAttribute(ATTR_LARGE_CONTAINER);
				$isSmContainer = $obj->getAttribute(ATTR_SMALL_CONTAINER);
				
				if ($isLgContainer||$isSmContainer) {
					$containers[] = array(
						"uid" => $uids[$i],
						"large" => $isLgContainer,
						"small" => $isSmContainer
						);
				}
			}
			if ($containers) return $containers;
			else return false;
		}
		else return false;
	}
	
	function getObjectsNearby($maxDistance, $cat, $localx, $localy) {
		//"1, 5" - objects and resources
		//"2" - people
		$sql = "SELECT `uid`, `local_x`, `local_y` FROM `objects` WHERE `global_x`=$this->globalx AND `global_y`=$this->globaly AND `local_x`>$localx-$maxDistance AND `local_x`<$localx+$maxDistance AND `local_y`>$localy-$maxDistance AND `local_y`<$localx+$maxDistance AND `general_type` IN ($cat) ORDER BY `weight` DESC";
		$result = $this->mysqli->query($sql);
		$arr = array();
		$sorter = new FieldSorter('distance');
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$dist = sqrt(pow(($row[1]-$localx),2)+pow(($row[2]-$localy),2));
				$direction = "";
				if ($localy>$row[2]) $direction .= "north";
				if ($row[2]>$localy) $direction .= "south";
				if ($localx>$row[1]) $direction .= "west";
				if ($row[1]>$localx) $direction .= "east";
				if ($dist>0&&$dist<=$maxDistance) $arr[] = array(
					"uid" => $row[0],
					"distance" => $dist,
					"direction" => $direction
					);
			}
			usort($arr, array($sorter, "cmp"));
			return $arr;
		}
		else return false;
	}
	
	function getVegetationSpot($localx, $localy) {
		
		$im=imagecreatefrompng($this->plantspath);
		$rgb=imagecolorat($im, round($localx/10), round($localy/10));
		
		$grass = ($rgb >> 16) & 0xFF;
		$bush = ($rgb >> 8) & 0xFF;
		$tree = $rgb & 0xFF;
		
		return array(
			"grass" => $grass,
			"bushes" => $bush,
			"trees" => $tree
			);
	}
	
	function getVegeVerbal($localx, $localy) {
		$arr = $this->getVegetationSpot($localx, $localy);
		
		$treeCount = $this->countTrees($arr["trees"]);

		if ($arr["bushes"]>191) $bush = "This spot is covered in bushes.";
		else if ($arr["bushes"]>128) $bush = "There are several bushes around you.";
		else if ($arr["bushes"]>60) $bush = "There are few bushes scattered around.";
		else $bush = "No bushes";
		
		if ($arr["grass"]>191) $grass = "It's very grassy.";
		else if ($arr["grass"]>128) $grass = "It's somewhat grassy.";
		else if ($arr["grass"]>60) $grass = "It's slightly grassy.";
		else $grass = "The ground is bare.";
		
		return array(
			"grass" => $grass,
			"bushes" => $bush,
			"trees" => $treeCount
			);
	}
	
	function setSinglePixel($b, $url) {
		//b is an array with the following keys: x, y, r, g, b
		$im=imagecreatefrompng($url);
		$col=imagecolorallocate($im, $b["r"], $b["g"], $b["b"]);
		imagesetpixel($im, $b["x"], $b["y"], $col);
		imagepng($im, "$url", NULL, NULL);
		imagedestroy($im);
	}
	
	function setPixels($points, $url) {
		//b is an array with the following keys: x, y, r, g, b
		$im=imagecreatefrompng($url);
		foreach ($points as $b) {
			$col=imagecolorallocate($im, $b["r"], $b["g"], $b["b"]);
			imagesetpixel($im, $b["x"], $b["y"], $col);
		}
		imagepng($im, "$url", NULL, NULL);
		imagedestroy($im);
	}
	
	function getClearTools($sel) {
		$ptype = new ProjectType($this->mysqli, 0);
		if ($sel == 1) $tools = $ptype->getToolsInPool(25);//tree felling
		if ($sel == 2) $tools = $ptype->getToolsInPool(26);//wood cutting (bush)
		if ($sel == 3) $tools = $ptype->getToolsInPool(39);//grass mowing
		return $tools;
	}
	
	function printClearTools($sel) {
		$tools = $this->getClearTools($sel);
		para("You need one of the following tools for this:");
		echo "<ul class='tool'>";
		foreach ($tools as $tool) {
			echo "<li>";
			echo $tool["name"] . " (efficiency: ". $tool["ap_multi"] . " %)";
			echo "</li>\n";
		}
		echo "</ul>";
	}
	
	function clearToolsAvailable($sel, $charid) {
		$char = new Character($this->mysqli, $charid);
		$char->getBasicData();
		$curTime = new Time($this->mysqli);
		$tools = $this->getClearTools($sel);
		$available = array();
		foreach ($tools as $possible) {
			$sql="SELECT `uid` FROM `objects` WHERE `parent`=$char->bodyId AND `presetFK`=" . $possible["uid"] . " AND (`exp_d`=-1 OR `exp_d`>'" . $curTime->dateTime . "' OR (`exp_d`='" . $curTime->dateTime . "' AND `exp_m`>'" . $curTime->minute . "'))";
			$result = $this->mysqli->query($sql);
			if (mysqli_num_rows($result)) {
				while ($row = mysqli_fetch_row($result)) {
					$available[] = array(
						"uid" => $row[0],
						"type" => $possible["uid"],
						"name" => $possible["name"],
						"ap_multi" => $possible["ap_multi"],
						"quality" => $possible["quality"]
						);
				}
			}
		}
		if (empty($available)) {
			para("You don't have any of the necessary tools in your inventory, so you cannot proceed.");
			return false;
		}
		else {
			ptag("h4", "Tool to use:");
			foreach ($available as $a) {
				echo "<p>";
				ptag("input", "", "type='radio' value='" . $a["uid"] . "' name='tool' id='tool-" . $a["uid"] . "'");
				ptag("label", $a["name"], "for='tool-" . $a["uid"] . "'");
				echo "</p>";
			}
			return true;
		}
	}
	
	public function chop($charid, $sel, $eff, $ap) {
		$actor = new Character($this->mysqli, $charid);
		$pos = $actor->getPosition();
		$real_ap = $actor->getAP();
		if ($real_ap<$ap) return -1;//You tried to use more AP than you have
		$this->loadcreate();
		$vege = $this->getVegetationSpot($pos->lx, $pos->ly);
		if ($sel == 1) {
			$max = $this->countTrees($vege["trees"]);
			$per = (100/$eff)*50;
		}
		else if ($sel == 2) {
			$max = $vege["bushes"]-60;
			$per = (100/$eff)*3;
		}
		else if ($sel == 3) {
			$max = $vege["grass"]-60;
			$per = (100/$eff)/2;
		}
		$can = floor($ap/$per);
		if ($max<$can) {
			$can = $max;
			$ap = round($max*$per);
		}
		//echo "You can chop " . $can . " with " . $ap . " AP.";
		if ($sel == 1) {
			$b = $vege["trees"]-($can*2);
			$g = $vege["bushes"];
			$r = $vege["grass"];
		}
		else if ($sel == 2) {
			$b = $vege["trees"];
			$g = $vege["bushes"]-$can;
			$r = $vege["grass"];
		}
		else if ($sel == 3) {
			$b = $vege["trees"];
			$g = $vege["bushes"];
			$r = $vege["grass"]-$can;
		}
		$info = array(
			"x" => round($actor->localx/10),
			"y" => round($actor->localy/10),
			"r" => $r,
			"g" => $g,
			"b" => $b
			);
		$this->setSinglePixel($info, $this->plantspath);
		$global = new GlobalMap($this->mysqli, $this->globalx, $this->globaly);
		$curTime = new Time($this->mysqli);
		
		if ($sel == 1) {
			$wood = $global->getResByCategory(3);
			if ($wood == -1||!$wood) $wood = array(
			array(
				"res_uid" => 247,
				"name" => "generic wood",
				"nat_uid" => 196,
				"gathered" => 500,
				"frequency" => 900
				)
			);
			$rand = rand(0, sizeof($wood)-1);
			$woodtype = $wood[$rand]["res_uid"];
			$weight = $wood[$rand]["gathered"]*100;
			for ($i=0;$i<$can;$i++) {
				$felled = new Obj($this->mysqli, 0);
				$felled->create(14, 8, 0, "felled tree", $this->globalx, $this->globaly, $pos->lx, $pos->ly, $woodtype, 1, $weight, $curTime->dateTime, $curTime->minute);
			}
		}
		if ($sel == 2) {
			$weight = rand(1000*$can, 5000*$can);
			$felled = new Obj($this->mysqli, 0);
			$felled->create(528, 5, 0, "cleared shrub", $this->globalx, $this->globaly, $pos->lx, $pos->ly, 205, 1, $weight, $curTime->dateTime, $curTime->minute);
		}
		if ($sel == 3) {
			$weight = rand(100*$can, 500*$can);
			$felled = new Obj($this->mysqli, 0);
			$felled->create(20, 5, 0, "mowed grass", $this->globalx, $this->globaly, $pos->lx, $pos->ly, 441, 1, $weight, $curTime->dateTime, $curTime->minute);
		}
		$actor->spendAP($ap);
		return 100;
	}
	
	public function getFields() {
		$retArr = array();
		$sql = "SELECT `uid`, `hex` FROM `field_areas` WHERE `gx`=$this->globalx AND `gy`=$this->globaly";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return false;
	}
	
	public function getFieldSquares() {
		$fields = $this->getFields();
		if (!$fields) return false;
		$str = "";
		foreach ($fields as $f) {
			if ($str!="") $str .= ",";
			$str .= $f["uid"];
		}
		$retArr = array();
		$sql = "SELECT `field_contents`.`uid` as 'fid', `lx`, `ly`, `fieldFK`, `status`, `cropFK`, `ripe`, `harvested`, `spoiled`, `weeds`, `datetime`, `hex` FROM `field_contents` JOIN `field_areas` ON `fieldFK`=`field_areas`.`uid` WHERE `fieldFK` IN ($str)";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_assoc($result)) {
				$retArr[] = $row;
			}
			return $retArr;
		}
		else return false;
	}
	
	public function countPCpresent() {
		$sql = "SELECT COUNT(`uid`) FROM `objects` WHERE `general_type`=2 AND `global_x`=$this->globalx AND `global_y`=$this->globaly";
		$result = $this->mysqli->query($sql);
		$row = mysqli_fetch_row($result);
		return $row[0];
	}
	
	public function getGroups() {
		$retArr = array();
		$sql = "SELECT `uid`, `local_x` as `lx`, `local_y` as `ly` FROM `objects` WHERE `general_type`=7 AND `global_x`=$this->globalx AND `global_y`=$this->globaly";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)<1) return false;
		while ($row = mysqli_fetch_assoc($result)) {
			$retArr[] = $row;
		}
		return $retArr;
	}
	
	public function listGroups($observer) {
		$groups = $this->getGroups();
		if (!$groups) {
			para("There are no groups in this location.");
			return false;
		}
		para("There are " . sizeof($groups) . " groups in this location.");
		foreach ($groups as $g) {
			$ng = new NPCgroup($this->mysqli, $g["uid"]);
			ptag("h3", "Group #" . $g["uid"] . ": ". $ng->loadName());
			para($ng->countMembers() . " members");
			para("Key position: (" . $g["lx"] . ", " . $g["ly"] . ")");
			para("<a href='index.php?page=viewgroup&charid=$observer&groupid=" . $g["uid"] . "' class='clist'>[Details]</a>");
		}
		return true;
	}
	
	public function checkBed($lx, $ly) {
		$retArr = array();
		$in = "468";//plant bedding, later this list will be continued
		$sql = "SELECT `uid`, `presetFK` FROM `objects` WHERE `presetFK` IN ($in) AND `global_x`=$this->globalx AND `global_y`=$this->globaly AND `local_x`=$lx AND `local_y`=$ly";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)<1) return -1;
		while ($row = mysqli_fetch_assoc($result)) {
			$retArr[] = $row;
		}
		return $retArr;
	}
}

?>
