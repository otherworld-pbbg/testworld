<?php
session_start();

function printJSON() {
	
	$treecount = rand(0,100);
	$treesleft = $treecount;
	$counter = 0;
	$lift = array(
		"north" => rand(0,1),
		"east" => rand(0,1),
		"south" => rand(0,1),
		"west" => rand(0,1)
		);
	
	$biome = array(
		"rainforest",
		"savanna",
		"swamp"
		);
	
	$brand = rand(0,2);
	
	switch ($brand) {
	case 0:
		$grounds = array(
			"ground",
			"water",
			"rocky",
			"mossy",
			"rockmoss"
			);
		$tops = array(
			"tree1",
			"tree2",
			"tree3",
			"tree4",
			"plant1",
			"plant2"
			);
		break;
	case 1:
		$grounds = array(
			"ground",
			"water",
			"grassy",
			"rocky"
		);
	
		$tops = array(
			"tree1",
			"tree2",
			"bush1"
		);
		break;
	case 2:
		$grounds = array(
			"moss",
			"water",
			"moist1",
			"moist2",
			"moist3"
		);
	
		$tops = array(
			"tree1",
			"tree2",
			"bush1"
		);
		break;
	}
	
	
	$array = array(
		"biome" => $biome[$brand]
		);
	
	for ($y = 0; $y<16; $y++) {
		for ($x = 0; $x<16; $x++) {
			$array["base"][$x][$y] = $biome[$brand] . "_" . $grounds[rand(0, sizeof($grounds)-1 ) ];
			
			if ($x>0&&!rand(0,3)) {
				$array["base"][$x][$y] = $array["base"][$x-1][$y];
			}
			if ($y>0&&!rand(0,3)) {
				$array["base"][$x][$y] = $array["base"][$x][$y-1];
			}
			
			if (rand(0,255-$counter)<$treesleft&&$array["base"][$x][$y]!=$biome[$brand] . "_" . "water") {
				$treesleft--;
				$array["top"][$x][$y] = $biome[$brand] . "_" . $tops[rand(0, sizeof($tops)-1 ) ];
			}
			else $array["top"][$x][$y] = "blank";
			$counter++;
			
			if ($lift["north"]&&$y==0) {
				$array["base"][$x][$y] = $biome[$brand] . "_" . "north";
				$array["top"][$x][$y] = "blank";
			}
			if ($lift["east"]&&$x==15) {
				$array["base"][$x][$y] = $biome[$brand] . "_" . "east";
				$array["top"][$x][$y] = "blank";
			}
			if ($lift["south"]&&$y==15) {
				$array["base"][$x][$y] = $biome[$brand] . "_" . "south";
				$array["top"][$x][$y] = "blank";
			}
			if ($lift["west"]&&$x==0) {
				$array["base"][$x][$y] = $biome[$brand] . "_" . "west";
				$array["top"][$x][$y] = "blank";
			}
		}
	}
	
	echo json_encode($array);
}

?>