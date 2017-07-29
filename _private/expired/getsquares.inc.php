<?
function getSquares_old($minx, $miny, $maxx, $maxy) {
		
		$im=imagecreatefrompng($this->soilurl);
		$im2=imagecreatefrompng($this->plantsurl);
		$im3=imagecreatefrompng($this->rowurl);
		$im4=imagecreatefrompng($this->alturl);
		
		$returnArray = array();
		$fieldSquares = $this->getFieldSquares();
		
		
		if ($im) {
			for ($i=$miny;$i<=$maxy;$i++) {
				for ($j=$minx;$j<=$maxx;$j++) {
					$rgb=imagecolorat($im, $j, $i);//soil map
					$rgb4=imagecolorat($im4, $j, $i);//altitude map
					$arr = array();
					$arr[0] = ($rgb >> 16) & 0xFF;
					$arr[1] = ($rgb >> 8) & 0xFF;
					$arr[2] = $rgb & 0xFF;
					
					$arr2 = array();
					$arr2[0] = ($rgb4 >> 16) & 0xFF;
					$arr2[1] = ($rgb4 >> 8) & 0xFF;
					$arr2[2] = $rgb4 & 0xFF;
					
					$alt = round((($arr2[0]-129)*80000+$arr2[1]*800+$arr2[2]*3)/100)/10;
					
					$hex = $this->rgb2hex($arr);
					
					$rgb2=imagecolorat($im2, $j, $i);
					
					$grass = ($rgb2 >> 16) & 0xFF;
					$bush = ($rgb2 >> 8) & 0xFF;
					$tree = $rgb2 & 0xFF;
					
					$rgb3=imagecolorat($im3, $j, $i);
					
					$rock = ($rgb3 >> 16) & 0xFF;
					$water = $rgb3 & 0xFF;
					
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
						"grass" => $grass,
						"bush" => $bush,
						"tree" => $tree,
						"rock" => $rock,
						"water" => $water,
						"altitude" => $alt,
						"hex2" => $hex2);
				}
			}
		}
		
		return $returnArray;
	}
?>
