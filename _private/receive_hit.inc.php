<?
function receiveHit($enemy_id, $weapon) {
		$enemy = new Obj($this->mysqli, $enemy_id);
		$enemy->getBasicData();
		$enemy->getName();
		
		if ($enemy->type==4) {
			$sql = "SELECT `attack_types` FROM `animals` WHERE `uid`=$enemy->secondary LIMIT 1";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				$row = mysqli_fetch_row($res);
				if ($row[0] == "") {
					para("This enemy doesn't know how to attack so it remains immobile.");
					return -1;
				}
				else {
					$pos_attacks = explode(",", $row[0]);
				}
			}
		}
		else {
			para("Non-animal attacks haven't been programmed yet, so this enemy remains immobile.");
			return -1;
		}
		
		$a_num = rand(0, sizeof($pos_attacks)-1);
		$curAttack = $pos_attacks[$a_num];
		
		$bodypart = "undefined";
		$adverb = "somehow";
		
		$bodyparts = array(
			"left foot",
			"right foot",
			"left ankle",
			"right ankle",
			"left shin",
			"right shin",
			"left knee",
			"right knee",
			"left thigh",
			"right thigh",
			"groin",
			"butt",
			"left hip",
			"right hip",
			"lower back",
			"left wrist",
			"right wrist",
			"left forearm",
			"right forearm",
			"left elbow",
			"right elbow",
			"upper back",
			"chest",
			"neck",
			"head",
			"eye"
			);
		
		$bones = array(
			"metatarsal",
			"metatarsal",
			"ankle bone",
			"ankle bone",
			"shin bone",
			"shin bone",
			"left kneecap",
			"right kneecap",
			"thigh bone",
			"thigh bone",
			"pubic bone",
			"tailbone",
			"pelvis",
			"pelvis",
			"lumbar vertebra",
			"wrist bones",
			"wrist bones",
			"ulna",
			"ulna",
			"elbow",
			"elbow",
			"thoracic vertebra",
			"ribcage",
			"cervical vertebra",
			"skull",
			"eye socket"
			);
		
		$roll = rand(0,100);
		
		if ($roll<75) {
			//success
			//to-do: if you are lying down, the animals can reach anywhere
			if ($enemy->weight<1000) $b_num = rand(0, 3);
			else if ($enemy->weight<8000) $b_num = rand(0, 5);
			else if ($enemy->weight<20000) $b_num = rand(0, 9);
			else if ($enemy->weight<20000) $b_num = rand(0, 16);
			else if ($enemy->weight<30000) $b_num = rand(0, 18);
			else if ($enemy->weight<40000) $b_num = rand(0, 20);
			else $b_num = rand(0, sizeof($bodyparts)-1);
			$bodypart = $bodyparts[$b_num];
			$bone = $bones[$b_num];
			$bonebreak = 0;
			
			if ($curAttack == 1) {
				if ($enemy->weight<1000) {
					$adverb = "pathetically";
					$pain = "It only stings a little bit.";
				}
				else if ($enemy->weight<10000) {
					$adverb = "sharply";
					$pain = "It sends a jolt of pain through your body.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "hard";
					$pain = "You feel searing pain.";
				}
				else if ($enemy->weight<50000) {
					$adverb = "crushingly";
					$pain = "Your $bodypart feels explosive pain.";
					if ($roll<10) $bonebreak = 1;
				}
				else {
					$adverb = "devastatingly";
					$pain = "Your $bodypart goes numb as your senses overload.";
					
					if ($roll<10) $bonebreak = 2;
					else if ($roll<20) $bonebreak = 1;
				}
			}
			else if ($curAttack == 2) {
				if ($enemy->weight<100) {
					$adverb = "lightly";
					$pain = "You feel a little scratch.";
				}
				else if ($enemy->weight<1000) {
					$adverb = "sharply";
					$pain = "You feel a jolt of pain.";
				}
				else if ($enemy->weight<10000) {
					$adverb = "severely";
					$pain = "You feel searing pain.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "savagely";
					$pain = "You feel as if you're being torn apart.";
				}
				else {
					$adverb = "mercilessly";
					$pain = "Your $bodypart goes numb as your senses overload.";
				}
			}
			else if ($curAttack == 3) {
				if ($enemy->weight<1000) {
					$adverb = "amusingly";
					$pain = "It doesn't even hurt.";
				}
				else if ($enemy->weight<8000) {
					$adverb = "lightly";
					$pain = "You might feel a little something.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "semi-hard";
					$pain = "You feel a slight jolt of pain.";
				}
				else if ($enemy->weight<200000) {
					$adverb = "hard";
					$pain = "You feel blunt pain.";
					if ($roll<10) $bonebreak = 1;
				}
				else {
					$adverb = "stunningly";
					$pain = "The impact site goes numb.";
					if ($roll<10) $bonebreak = 2;
					else if ($roll<20) $bonebreak = 1;
				}
			}
			else if ($curAttack == 4) {
				if ($enemy->weight<5000) {
					$adverb = "hilariously";
					$pain = "If you weren't in the middle of combat, this could pass for a type of massage.";
				}
				else if ($enemy->weight<15000) {
					$adverb = "lightly";
					$pain = "You get kicked a few times in process.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "hard";
					$pain = "Sharp blows land in various parts of your body.";
					if ($roll<5) $bonebreak = 1;
				}
				else if ($enemy->weight<200000) {
					$adverb = "crushingly";
					$pain = "Your senses explode with pain.";
					$bonebreak = 1;
				}
				else {
					$adverb = "shatteringly";
					$pain = "You feel like you've been run over by a freight train.";
					$bonebreak = 2;
				}
			}
			else if ($curAttack == 5) {
				if ($enemy->weight<5000) {
					$adverb = "adorably";
					$pain = "You're too amused to feel pain.";
				}
				else if ($enemy->weight<15000) {
					$adverb = "lightly";
					$pain = "You might feel a little something.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "hard";
					$pain = "It sends a blunt pain up your nerveways.";
				}
				else if ($enemy->weight<200000) {
					$adverb = "stunningly";
					$pain = "The impact site goes numb.";
					if ($roll<10) $bonebreak = 1;
				}
				else {
					$adverb = "like a freight train";
					$pain = "You feel like you're being crushed into a pancake.";
					if ($roll<10) $bonebreak = 2;
					else if ($roll<20) $bonebreak = 1;
				}
			}
			else if ($curAttack == 6) {
				if ($enemy->weight<6000) {
					$adverb = "just slightly";
					$pain = "You feel a little sting.";
				}
				else if ($enemy->weight<15000) {
					$adverb = "somewhat alarmingly";
					$pain = "You are struck with sharp pain.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "considerably";
					$pain = "You feel your body split open.";
				}
				else if ($enemy->weight<100000) {
					$adverb = "severely";
					$pain = "You feel like you were being torn in half.";
				}
				else {
					$adverb = "critically";
					$pain = "Your senses explode with pain.";
				}
			}
			if ($curAttack == 7) {
				if ($enemy->weight<1000) {
					$adverb = "just slightly";
					$pain = "You feel sharp pain.";
				}
				else if ($enemy->weight<5000) {
					$adverb = "sharply";
					$pain = "You feel searing pain.";
				}
				else if ($enemy->weight<10000) {
					$adverb = "hard";
					$pain = "Your $bodypart feels like it's being shredded.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "seriously";
					$pain = "You feel as if a large chunk of your $bodypart is getting torn off.";
					if ($roll<5) $bonebreak = 1;
				}
				else {
					$adverb = "savagely";
					$pain = "You feel like your $bodypart is getting torn off.";
					if ($roll<10) $bonebreak = 2;
				}
			}
			else if ($curAttack == 8) {
				if ($enemy->weight<5000) {
					$adverb = "adorably";
					$pain = "You feel just slight pressure.";
				}
				else if ($enemy->weight<10000) {
					$adverb = "lightly";
					$pain = "You feel a squeezing sensation.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "hard";
					$pain = "You feel crushing pain.";
					if ($roll<10) $bonebreak = 1;
				}
				else {
					$adverb = "crushingly";
					$pain = "You feel as if your body was stuck in vice.";
					if ($roll<10) $bonebreak = 2;
					else if ($roll<20) $bonebreak = 1;
				}
			}
			else if ($curAttack == 9) {
				if ($enemy->weight<1000) {
					$adverb = "adorably";
					$pain = "You can hardly even feel it.";
				}
				else if ($enemy->weight<8000) {
					$adverb = "lightly";
					$pain = "You feel a light punt.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "semi-hard";
					$pain = "You feel blunt pain.";
				}
				else if ($enemy->weight<100000) {
					$adverb = "hard";
					$pain = "The impact site is ringing with pain.";
					if ($roll<3) $bonebreak = 2;
					else if ($roll<10) $bonebreak = 1;
				}
				else {
					$adverb = "like a cement truck";
					$pain = "You feel stunned.";
					if ($roll<5) $bonebreak = 2;
					else if ($roll<15) $bonebreak = 1;
				}
			}
			else if ($curAttack == 10) {
				if ($enemy->weight<6000) {
					$adverb = "lightly";
					$pain = "It might hurt a little.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "almost considerably";
					$pain = "You think you actually felt that.";
				}
				else if ($enemy->weight<60000) {
					$adverb = "painfully";
					$pain = "Pain radiates through your body.";
				}
				else if ($enemy->weight<80000) {
					$adverb = "hard";
					$pain = "Your $bodypart is splitting with pain.";
				}
				else {
					$adverb = "devastatingly";
					$pain = "It feels as if your $bodypart is being destroyed, which is probably is.";
					if ($roll<15) $bonebreak = 1;
				}
			}
			else if ($curAttack == 11) {
				if ($enemy->weight<6000) {
					$adverb = "lightly";
					$pain = "You feel a blunt pain upon impact.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "almost considerably";
					$pain = "You feel a sharp pain upon impact.";
				}
				else if ($enemy->weight<60000) {
					$adverb = "painfully";
					$pain = "Your brain is screaming with alarm.";
				}
				else if ($enemy->weight<80000) {
					$adverb = "hard";
					$pain = "Pain radiates through your $bodypart.";
				}
				else {
					$adverb = "stunningly";
					$pain = "Your body explodes with pain.";
					if ($roll<5) $bonebreak = 2;
					else if ($roll<15) $bonebreak = 1;
				}
			}
			else if ($curAttack == 12) {
				if ($enemy->weight<100) {
					//this needs to be changed from weight to beak sharpness
					$adverb = "weakly";
					$pain = "You almost felt that.";
				}
				else if ($enemy->weight<1000) {
					$adverb = "with all its might";
					$pain = "You feel blunt pain.";
				}
				else if ($enemy->weight<10000) {
					$adverb = "hard";
					$pain = "You feel needle-sharp pain.";
				}
				else if ($enemy->weight<30000) {
					$adverb = "sharply";
					$pain = "You feel like you're being stabbed.";
				}
				else {
					$adverb = "critically";
					$pain = "You feel as if chucks of flesh are getting torn off.";
				}
			}
		}
		
		
		
		$attack_types1 = array(
			1 => "bites you in the $bodypart",
			2 => "claws at your $bodypart",
			3 => "kicks you in the $bodypart",
			4 => "tramples you",
			5 => "headbutts you in the $bodypart",
			6 => "gores you in the $bodypart with its horn",
			7 => "tears at your $bodypart with its teeth",
			8 => "strangles you",
			9 => "punches you in the $bodypart",
			10 => "hits you in the $bodypart with $weapon",
			11 => "throws $weapon at your $bodypart",
			12 => "pecks at your $bodypart"
		);
		
		$attack_types2 = array(
			1 => "bite you",
			2 => "claw at you",
			3 => "kick you",
			4 => "trample you",
			5 => "headbutt you",
			6 => "gore you with its horn",
			7 => "tear at you with its teeth",
			8 => "strangle you",
			9 => "punch you",
			10 => "hit you with $weapon",
			11 => "throw $weapon at you",
			12 => "peck at you"
		);
		
		
		
		if ($roll<75) {
			para("The $enemy->name ".$attack_types1[$curAttack] . ". $pain");
			
			//echo "The $enemy->name $action_1. Your armor absorbs some of the damage."
			
			//echo "The $enemy->name tries to $action_2 in the $bodypart but your shield deflects the attack, making it hit your $bodypart2 instead."
			if ($bonebreak==1) para("You feel your $bone breaking.");
			if ($bonebreak==2) para("You feel your $bone shattering.");
			return 200;
		}
		else {
			para("The $enemy->name tries to " . $attack_types2[$curAttack] . " but you dodge.");
			
			//echo "The $enemy->name tries to $action_2 but you block it with your shield."
			
			//echo "The $enemy->name tries to $action_2 but it is absorbed by your armor."
			return 100;
		}
	}
	?>
