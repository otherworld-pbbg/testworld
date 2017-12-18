<html>
 <head>
  <title>Combat test</title>
 </head>
 <body>
 <?php
	function generateGroup( $name, $membersNumber, $morale=100, $attitude='fight') {
		$group = array(
			'name' => $name,
			'membersAtStart' => $membersNumber,
			'members' => $membersNumber,
			'con' => array(),
			'morale' => $morale,
			'attitude' => $attitude
		);
		for ($i=0; $i<$membersNumber; $i++) {
			$group['con'][$i] = 100;
		}
		return $group;
	}
	
	function encounter( &$group1, &$group2) {
		report( $group1);
		report( $group2);
		
		// both groups act depending on the attitude
		if( $group1['attitude'] == 'run' && $group2['attitude'] == 'run') {
			echo "<p>both groups evaded each other</p>";
			return;
		}
		else if( $group1['attitude'] == 'run') {
			echo "<p>".$group1['name']." group did run without a fight</p>";
			return;
		}
		else if( $group2['attitude'] == 'run') {
			echo "<p>".$group2['name']." group did run without a fight</p>";
			return;
		}
		else if( $group1['attitude'] == 'stay' && $group2['attitude'] == 'stay') {
			echo "<p>both groups stayed their ground without a fight</p>";			
			return;
		}
		else {
			echo "<p>the combat between groups ensued</p>";
			engagement( $group1, $group2);
		}
	}
	
	function engagement( &$group1, &$group2, $maxCombatLength = 100) {
		$group1['attitude'] = 'fight';
		$group2['attitude'] = 'fight';
		
		// loop of exchanges between sides of an engagement
		for( $i=1; $i <= $maxCombatLength; $i++) {
			report( $group1);
			report( $group2);
			
			// events during the single engagement exchange
			if( $group1['attitude'] == 'fight') {
				echo "<p>the ".$group1['name']." started $i combat round</p>";
				combatRound( $group1, $group2);
			}
			if( $group2['members'] == 0) {
				echo "<p>".$group2['name']." were completly annihilated</p>";	
				$group2['attitude'] = 'destroyed'; // change for the sake of final group raport
				break;
			}
			if( $group2['attitude'] == 'fight') {
				echo "<p>the ".$group2['name']." started $i combat round</p>";
				combatRound( $group2, $group1);
			}
			if( $group1['members'] == 0) {
				echo "<p>".$group1['name']." were completly annihilated</p>";	
				$group1['attitude'] = 'destroyed'; // change for the sake of final group raport
				break;
			}
			if( $group1['attitude'] == 'run' && $group2['attitude'] == 'fight') {
				echo "<p>".$group1['name']." were chased from the battlefield</p>";	
				break;
			}
			else if( $group1['attitude'] == 'fight' && $group2['attitude'] == 'run') {
				echo "<p>".$group2['name']." were chased from the battlefield</p>";	
				break;
			}
			else if( $group1['attitude'] == 'run' && $group2['attitude'] == 'run') {
				echo "<p>both groups disengaged</p>";	
				break;
			}
			updateAttitude( $group1, $group2);
			updateAttitude( $group2, $group1);
		}
		report( $group1);
		report( $group2);
	}
	
	function report( $group) {
		$totalRemainingCon=array_sum( $group['con']);
		echo "<p>\"".$group['name']."\" group report - remaining members: ".$group['members'].", total remaining constitution: ".$totalRemainingCon.
			", morale: ".$group['morale'].", attitude: ".$group['attitude']."</p>";
	}
	
	function updateAttitude( &$group1, &$group2) {
		$numericalRatio = $group1['members'] / $group2['members'];
		$runChance = 1 - ($group1['morale']/100 * $numericalRatio);
		// random float number in [0,1) range
		$runDieToss = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
		if( $runDieToss < $runChance)
			$group1['attitude'] = 'run';
	}
	
	function combatRound( &$attackingGroup, &$defendingGroup) {
		// for simplicity the first group attacks first, the the second, the order should be random
		echo "Hits: "; // hit raport
		for( $i=0; $i < $attackingGroup['members']; $i++) {
			$defendingMember = mt_rand(0,$defendingGroup['members']-1);
			calculateIndividualAttack( $attackingGroup, $i, $defendingGroup, $defendingMember);
			if( $defendingGroup['members'] <= 0)
				break;
		}
	}
	
	function calculateIndividualAttack( &$attackingGroup, $attackingMember, &$defendingGroup, $defendingMember) {
		// hit chance is expressed per mil, then divide by 1000
		$hitChance = (100 + $attackingGroup['con'][$attackingMember] - $defendingGroup['con'][$defendingMember] + $attackingGroup['morale']) / 1000;
		
		// random float number in [0,1) range
		$hitDieToss = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
		
		if( $hitDieToss < $hitChance) {
			$hitDamage = mt_rand(1, 120); // the very simple damage die toss
			if( $hitDamage < $defendingGroup['con'][$defendingMember]) {
				$defendingGroup['con'][$defendingMember] -= $hitDamage;
				$defendingGroup['morale'] -= $hitDamage / (2 * $defendingGroup['members']);
				$attackingGroup['morale'] += $hitDamage / (2 * $attackingGroup['members']);
				echo "$hitDamage "; // hit raport
			}
			else {
				array_splice($defendingGroup['con'], $defendingMember, 1);
				$defendingGroup['members'] -= 1;
				if( $defendingGroup['members'] > 0)
					$defendingGroup['morale'] -= 100 / $defendingGroup['members'];
				else
					$defendingGroup['morale'] = 0;
				$attackingGroup['morale'] += 100 / $attackingGroup['members'];
				echo "kill "; // hit raport
			}
		}		
	}

	// test of the combat system
	$group1 = generateGroup( 'French', 100);
	$group2 = generateGroup( 'British', 100);
	encounter( $group1, $group2);
 ?>
 
 </body>
</html>