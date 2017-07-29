<?php
include_once("class_character.inc.php");

class Time {
	private $mysqli;
	
	public $dateTime;
	public $year;
	public $month;
	public $day;
	public $hour;
	public $minute;
		
	public function __construct($mysqli, $dateTime=0, $minute=0) {
		$this->mysqli = $mysqli;
		if ($dateTime==0) $this->loadTime();
		else {
			$this->minute = $minute;
			$this->dateTime = $dateTime;
			$this->hour = intval(substr($dateTime, -2));
			$this->day = intval(substr($dateTime, -4, 2));
			$this->month = intval(substr($dateTime, -6, 2));
			$temp = intval(substr($dateTime, -6));
			$this->year = ($dateTime-$temp)/1000000;
		}
	}
	
	public function getDateTime() {
		$str = $this->prefix($this->year, 4) . "/" . $this->prefix($this->month,2) . "/" . $this->prefix($this->day,2) . "-" . $this->prefix($this->hour,2) . ":" . $this->prefix($this->minute, 2);
		return $str;
	}
	
	public function loadGameTime() {
		//in the past the internal time depended on the real life time, but now it starts from the beginning of the current day
		
		$sql = "SELECT `year`, `month`, `day` FROM `global_date` WHERE 1 ORDER BY `turnID` DESC LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->year = $row[0];
			$this->month=$row[1];
			$this->day=$row[2];
			$this->dateTime = "" . $this->year . $this->prefix($this->month,2) . $this->prefix($this->day,2) . $this->prefix(0,2);//the last part is hour
			$this->hour = 0;
			$this->minute = 0;
			return 1;
		}
		else return -1;
	}
	
	public function setGameTime($year, $month, $day) {
		$sql = "INSERT INTO `global_date` (`year`, `month`, `day`) VALUES ($year, $month, $day);";
		$this->mysqli->query($sql);
		$result = $this->mysqli->insert_id;
		if ($result) {
			$this->year = $year;
			$this->month = $month;
			$this->day = $day;
			$this->dateTime = "" . $this->year . $this->prefix($this->month,2) . $this->prefix($this->day,2) . $this->prefix(0,2);//the last part is hour
			return 1;
		}
		else return -1;
	}
	
	public function advanceCharacters() {
		$errors = 0;
		$sql = "SELECT `uid` FROM `chars` WHERE `status`<2";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				$curChar = new Character($this->mysqli, $row[0]);
				$theirTime = $curChar->getInternalTime();
				if (is_array($theirTime)) {
					if ($theirTime[1]<$this->dateTime) {
						$curChar->getBasicData();
						$sql2 = "INSERT INTO `charloctime` (`rowID`, `charFK`, `globalX`, `globalY`, `localX`, `localY`, `indoors`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status`, `ap`) VALUES (NULL, '$curChar->uid', '$curChar->x', '$curChar->y', '$curChar->localx', '$curChar->localy', '$curChar->building', '" . $theirTime[1] . "', '" . $theirTime[2] . "', '$this->dateTime', '$this->minute', '1', '0')";
						$this->mysqli->query($sql2);
						$success = $this->mysqli->insert_id;
						if ($success<1||!$success) $errors++;
					}
				}
				else $errors++;
			}
			return $errors;
		}
		else return -1;//No characters found
	}
	
	public function prefix($val, $digits) {
		//digits 2 or 4
		$str = "";
		if ($digits==2&&!preg_match("~^0\d+$~", $val)) {
			if ($val<10) {
				$str= "0" . $val;
			}
			else $str = $val;
		}
		else $str = $val;
		if ($digits==4) {
			if ($val<10) $str= "00" . $val;
			else if ($val<100) $str = "0". $val;
			else  $str = $val;
		}
		return $str;
	}
	
	public function countDifference($dateTime, $minute) {
		$compTimeMin = $this->timeInMinutes($dateTime, $minute);
		$ownTimeMin = $this->timeInMinutes($this->dateTime, $this->minute);
		$diff = $compTimeMin - $ownTimeMin;
		$arr = $this->convertMinutes($diff);
		return $arr;
	}
	
	public function countDifferenceMinutes($dateTime, $minute) {
		$compTimeMin = $this->timeInMinutes($dateTime, $minute);
		$ownTimeMin = $this->timeInMinutes($this->dateTime, $this->minute);
		return ($compTimeMin - $ownTimeMin);
	}
	
	public function timeInMinutes($dateTime, $minute) {
		$Hour = substr($dateTime, -2);
		$Day = substr($dateTime, -4, 2);
		$Month = substr($dateTime, -6, 2);
		$temp = substr($dateTime, -6);
		$Year = ($dateTime-$temp)/1000000;
		
		$total = $minute + ($Hour)*60 + ($Day-1)*720 + ($Month-1)*17280 + ($Year-1)*207360;
		return $total;
	}
	
	public function convertMinutes($minutes) {
		if ($minutes<207360) $years=0;
		else {
			$years=floor($minutes/207360);
		}
		$minutes= $minutes - ($years*207360);
		if ($minutes<17280) $months=0;
		else {
			$months=floor($minutes/17280);
		}
		$minutes = $minutes - ($months*17280);
		if ($minutes<720) $days=0;
		else {
			$days=floor($minutes/720);
		}
		$minutes = $minutes - ($days*720);
		if ($minutes<60) $hours=0;
		else {
			$hours=floor($minutes/60);
		}
		$minutes = $minutes - ($hours*60);
		
		return array(
			"years" => $years,
			"months" => $months,
			"days" => $days,
			"hours" => $hours,
			"minutes" => $minutes
			);
	}
	
	public function increaseByHour() {
		if ($this->hour<11) $this->hour++;
		else {
			$this->hour = 0;
			if ($this->day<24) $this->day++;
			else {
				$this->day = 1;
				if ($this->month<12) $this->month++;
				else {
					$this->month = 1;
					$this->year++;
				}
			}
		}
		$this->dateTime = "" . $this->year . $this->prefix($this->month,2) . $this->prefix($this->day,2) . $this->prefix($this->hour,2);
		return $this->dateTime;
	}
	
	public function addTime($minutes, $hours=0, $days=0, $months=0, $years=0) {
		$this->minute+=$minutes;
		while ($this->minute>59) {
			$this->minute-=60;
			$this->hour++;
		}
		$this->hour+=$hours;
		while ($this->hour>11) {
			$this->hour-=12;
			$this->day++;
		}
		$this->day+=$days;
		while ($this->day>24) {
			$this->day-=24;
			$this->month++;
		}
		$this->month+=$months;
		while ($this->month>12) {
			$this->month-=12;
			$this->year++;
		}
		$this->year+=$years;
		$this->dateTime = "" . $this->year . $this->prefix($this->month,2) . $this->prefix($this->day,2) . $this->prefix($this->hour,2);
		return $this->dateTime;
	}
	
	public function getPplCurrentlyLocation($x, $y, $indoors, $ignore) {
		if (is_null($x)) {
			$x = "NULL";
			$y = "NULL";
		}
		$charlist = "";
		$sql = "SELECT `chars`.`uid` FROM `objects` JOIN `chars` ON `objects`.`uid`=`chars`.`objectFK` WHERE `general_type`=2 AND `chars`.`status`=1 AND `global_x`=$x AND `global_y`=$y AND `parent`=$indoors AND `chars`.`uid`<>$ignore";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				if ($charlist) $charlist .= ", ";
				$charlist .= $row[0];
			}
		}
		else return false;
		$arr = array();
		$sql2 = "SELECT `charFK`, `localX`, `localY`, `startDateTime`, `startMinute`, `endDateTime`, `endMinute`, `status` FROM `charloctime` WHERE `rowID` IN (SELECT MAX(`rowID`) FROM `charloctime` WHERE 1 GROUP BY `charFK`) AND (`charFK` IN ($charlist)) ORDER BY `charFK`";
		$result = $this->mysqli->query($sql2);
		if (mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$arr[] = array(
					"charid" => $row[0],
					"lx" => $row[1],
					"ly" => $row[2],
					"startDateTime" => $row[3],
					"startMinute" => $row[4],
					"endDateTime" => $row[5],
					"endMinute" => $row[6],
					"status" => $row[7]
					);
			}
			return $arr;
		}
		else return false;
	}
	
	public function getTimeofDay($x, $y) {
		$index = round(($y+5000)/1000);
		$timezone = floor(($x/20000)*12);
		
		$daylight = array(
			0 => array(1 => 2,
				2 => 4,
				3 => 6,
				4 => 8,
				5 => 10,
				6 => 12,
				7 => 10,
				8 => 8,
				9 => 6,
				10 => 4,
				11 => 1,
				12 => 0),
			1 => array(1 => 4,
				2 => 5,
				3 => 6,
				4 => 7,
				5 => 8,
				6 => 9,
				7 => 8,
				8 => 7,
				9 => 6,
				10 => 5,
				11 => 4,
				12 => 3),
			2 => array(1 => 5,
				2 => 5.5,
				3 => 6,
				4 => 6.5,
				5 => 7,
				6 => 7.5,
				7 => 7,
				8 => 6.5,
				9 => 6,
				10 => 5.5,
				11 => 5,
				12 => 4.5),
			3 => array(1 => 5.33,
				2 => 5.67,
				3 => 6,
				4 => 6.33,
				5 => 6.67,
				6 => 7,
				7 => 6.67,
				8 => 6.33,
				9 => 6,
				10 =>5.67,
				11 => 5.33,
				12 => 5),
			4 => array(1 => 5.67,
				2 => 5.83,
				3 => 6,
				4 => 6.17,
				5 => 6.33,
				6 => 6.5,
				7 => 6.33,
				8 => 6.17,
				9 => 6,
				10 => 5.83,
				11 => 5.67,
				12 => 5.5),
			5 => array(1 => 6,
				2 => 6,
				3 => 6,
				4 => 6,
				5 => 6,
				6 => 6,
				7 => 6,
				8 => 6,
				9 => 6,
				10 => 6,
				11 => 6,
				12 => 6),
			6 => array(1 => 6.33,
				2 => 6.17,
				3 => 6,
				4 => 5.83,
				5 => 5.67,
				6 => 5.5,
				7 => 5.67,
				8 => 5.83,
				9 => 6,
				10 => 6.17,
				11 => 6.33,
				12 => 6.5),
			7 => array(1 => 6.67,
				2 => 6.33,
				3 => 6,
				4 => 5.67,
				5 => 5.33,
				6 => 5,
				7 => 5.33,
				8 => 5.67,
				9 => 6,
				10 => 6.33,
				11 => 6.67,
				12 => 7),
			8 => array(1 => 7,
				2 => 6.5,
				3 => 6,
				4 => 5.5,
				5 => 5,
				6 => 4.5,
				7 => 5,
				8 => 5.5,
				9 => 6,
				10 => 6.5,
				11 => 7,
				12 => 7.5),
			9 => array(1 => 8,
				2 => 7,
				3 => 6,
				4 => 5,
				5 => 4,
				6 => 3,
				7 => 4,
				8 => 5,
				9 => 6,
				10 => 7,
				11 => 8,
				12 => 9),
			10 => array(1 => 10,
				2 => 8,
				3 => 6,
				4 => 4,
				5 => 2,
				6 => 0,
				7 => 2,
				8 =>4,
				9 => 6,
				10 => 8,
				11 => 10,
				12 => 12)
			);
		
		$noon = 6;
		
		$daybreak = $noon - round($daylight[$index][$this->month]/2);
		
		$nightfall = $noon + round($daylight[$index][$this->month]/2);
		
		
		$localhour = $timezone + $this->hour;
		while ($localhour>12) $localhour-=12;
		
		//para($x . "," . $y . " Localtime: " . $localhour . " Dawn: " . $daybreak . " Noon: " . $noon . " Nightfall: " . $nightfall);
		if (($index==0&&$this->month==12)||($index==10&&$this->month==6)) {
			if (round($noon)==$localhour) return "Twilight";
			else return "Darkness";
		}
		else {
			if ($localhour>$daybreak&&$localhour<$nightfall) return "Daylight";
			else if ($localhour==$daybreak) return "Dawn";
			else if ($localhour==$nightfall) return "Dusk";
			else return "Darkness";
		}
	}
	
	public function getSeason($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => "Midwinter",
				2 => "Winter",
				3 => "Late winter",
				4 => "Spring",
				5 => "Early summer",
				6 => "Midsummer",
				7 => "Summer",
				8 => "Late summer",
				9 => "Early fall",
				10 => "Late fall",
				11 => "Early winter",
				12 => "Midwinter"),
			1 => array(1 => "Winter",
				2 => "Late winter",
				3 => "Early spring",
				4 => "Spring",
				5 => "Early summer",
				6 => "Midsummer",
				7 => "Summer",
				8 => "Late summer",
				9 => "Early fall",
				10 => "Late fall",
				11 => "Early winter",
				12 => "Midwinter"),
			2 => array(1 => "Early spring",
				2 => "Spring",
				3 => "Late spring",
				4 => "Early summer",
				5 => "Early summer",
				6 => "Midsummer",
				7 => "Summer",
				8 => "Late summer",
				9 => "Early fall",
				10 => "Fall",
				11 => "Late fall",
				12 => "Winter"),
			3 => array(1 => "Early spring",
				2 => "Spring",
				3 => "Late spring",
				4 => "Early summer",
				5 => "Summer",
				6 => "Midsummer",
				7 => "Summer",
				8 => "Summer",
				9 => "Late summer",
				10 => "Late fall",
				11 => "Late fall",
				12 => "Cool season"),
			4 => array(1 => "Spring",
				2 => "Early summer",
				3 => "Summer",
				4 => "Summer",
				5 => "Summer",
				6 => "Midsummer",
				7 => "Summer",
				8 => "Summer",
				9 => "Late summer",
				10 => "Early fall",
				11 => "Fall",
				12 => "Tepid season"),
			5 => array(1 => "Eternal summer",
				2 => "Eternal summer",
				3 => "Eternal summer",
				4 => "Eternal summer",
				5 => "Eternal summer",
				6 => "Eternal summer",
				7 => "Eternal summer",
				8 => "Eternal summer",
				9 => "Eternal summer",
				10 => "Eternal summer",
				11 => "Eternal summer",
				12 => "Eternal summer"),
			6 => array(1 => "Summer",
				2 => "Summer",
				3 => "Late summer",
				4 => "Early fall",
				5 => "Fall",
				6 => "Tepid season",
				7 => "Spring",
				8 => "Early summer",
				9 => "Summer",
				10 => "Summer",
				11 => "Summer",
				12 => "Midsummer"),
			7 => array(1 => "Summer",
				2 => "Summer",
				3 => "Late summer",
				4 => "Early fall",
				5 => "Late fall",
				6 => "Cool season",
				7 => "Early spring",
				8 => "Spring",
				9 => "Late spring",
				10 => "Early summer",
				11 => "Summer",
				12 => "Midsummer"),
			8 => array(1 => "Summer",
				2 => "Late summer",
				3 => "Early fall",
				4 => "Fall",
				5 => "Late fall",
				6 => "Winter",
				7 => "Early spring",
				8 => "Spring",
				9 => "Late spring",
				10 => "Early summer",
				11 => "Early summer",
				12 => "Midsummer"),
			9 => array(1 => "Summer",
				2 => "Late summer",
				3 => "Early fall",
				4 => "Late fall",
				5 => "Early winter",
				6 => "Midwinter",
				7 => "Winter",
				8 => "Late winter",
				9 => "Early spring",
				10 => "Spring",
				11 => "Early summer",
				12 => "Midsummer"),
			10 => array(1 => "Summer",
				2 => "Late summer",
				3 => "Early fall",
				4 => "Late fall",
				5 => "Early winter",
				6 => "Midwinter",
				7 => "Winter",
				8 => "Late winter",
				9 => "Early spring",
				10 => "Spring",
				11 => "Early summer",
				12 => "Midsummer")
			);
		return $seasons[$index][$this->month];
	}
	
	public function getWeather($x, $y, $dbonly=false) {
		$timeofday = $this->getTimeofDay($x, $y);
		$currentLocation = new GlobalMap($this->mysqli, $x, $y);
		$terrains = $currentLocation->getTerrains($dbonly);
		if ($dbonly) $water = 50;
		else $currentLocation->getLevel("water");
		
		$index = round(($y+5000)/1000);
		$cycle = $this->year%7;
		$cycle2 = ($this->day-$this->month+11)%17;//The divisors are arbitrary
		
		$humidity_avg = array(
			0 => array(1 => 5,
				2 => 5,
				3 => 5,
				4 => 5,
				5 => 5,
				6 => 5,
				7 => 5,
				8 => 5,
				9 => 5,
				10 => 5,
				11 => 5,
				12 => 5),
			1 => array(1 => 80,
				2 => 81,
				3 => 78,
				4 => 80,
				5 => 77,
				6 => 80,
				7 => 81,
				8 => 82,
				9 => 80,
				10 => 80,
				11 => 80,
				12 => 80),
			2 => array(1 => 82,
				2 => 79,
				3 => 72,
				4 => 64,
				5 => 64,
				6 => 64,
				7 => 65,
				8 => 64,
				9 => 74,
				10 => 78,
				11 => 82,
				12 => 83),
			3 => array(1 => 75,
				2 => 75,
				3 => 75,
				4 => 75,
				5 => 75,
				6 => 73,
				7 => 72,
				8 => 73,
				9 => 76,
				10 => 77,
				11 => 78,
				12 => 78),
			4 => array(1 => 60,
				2 => 54,
				3 => 53,
				4 => 47,
				5 => 46,
				6 => 49,
				7 => 59,
				8 => 61,
				9 => 60,
				10 => 60,
				11 => 61,
				12 => 62),
			5 => array(1 => 80,
				2 => 80,
				3 => 80,
				4 => 82,
				5 => 80,
				6 => 78,
				7 => 72,
				8 => 70,
				9 => 74,
				10 => 78,
				11 => 81,
				12 => 81),
			6 => array(1 => 58,
				2 => 52,
				3 => 36,
				4 => 28,
				5 => 29,
				6 => 43,
				7 => 67,
				8 => 72,
				9 => 62,
				10 => 42,
				11 => 41,
				12 => 57),
			7 => array(1 => 83,
				2 => 82,
				3 => 78,
				4 => 76,
				5 => 80,
				6 => 77,
				7 => 76,
				8 => 75,
				9 => 78,
				10 => 77,
				11 => 82,
				12 => 83),
			8 => array(1 => 71,
				2 => 70,
				3 => 63,
				4 => 62,
				5 => 64,
				6 => 66,
				7 => 62,
				8 => 63,
				9 => 67,
				10 => 65,
				11 => 66,
				12 => 71),
			9 => array(1 => 84,
				2 => 82,
				3 => 81,
				4 => 69,
				5 => 61,
				6 => 62,
				7 => 68,
				8 => 71,
				9 => 76,
				10 => 82,
				11 => 83,
				12 => 85),
			10 => array(1 => 25,
				2 => 22,
				3 => 18,
				4 => 12,
				5 => 12,
				6 => 9,
				7 => 10,
				8 => 8,
				9 => 12,
				10 => 15,
				11 => 19,
				12 => 24)
			);
		
		$temp_max = array(
			0 => array(1 => -15,
				2 => -20,
				3 => -15,
				4 => -10,
				5 => -5,
				6 => 10,
				7 => 12,
				8 => 20,
				9 => 8,
				10 => -5,
				11 => -10,
				12 => -12),
			1 => array(1 => 4,
				2 => 4,
				3 => 7,
				4 => 12,
				5 => 22,
				6 => 28,
				7 => 29,
				8 => 30,
				9 => 26,
				10 => 18,
				11 => 10,
				12 => 5),
			2 => array(1 => 13,
				2 => 8,
				3 => 14,
				4 => 22,
				5 => 26,
				6 => 33,
				7 => 35,
				8 => 38,
				9 => 28,
				10 => 20,
				11 => 19,
				12 => 18),
			3 => array(1 => 18,
				2 => 20,
				3 => 23,
				4 => 25,
				5 => 28,
				6 => 36,
				7 => 38,
				8 => 42,
				9 => 32,
				10 => 25,
				11 => 20,
				12 => 15),
			4 => array(1 => 28,
				2 => 28,
				3 => 29,
				4 => 29,
				5 => 32,
				6 => 39,
				7 => 42,
				8 => 48,
				9 => 39,
				10 => 36,
				11 => 32,
				12 => 27),
			5 => array(1 => 56,
				2 => 56,
				3 => 56,
				4 => 56,
				5 => 56,
				6 => 56,
				7 => 56,
				8 => 56,
				9 => 56,
				10 => 56,
				11 => 56,
				12 => 56),
			6 => array(1 => 42,
				2 => 48,
				3 => 39,
				4 => 36,
				5 => 32,
				6 => 27,
				7 => 28,
				8 => 28,
				9 => 29,
				10 => 29,
				11 => 32,
				12 => 39),
			7 => array(1 => 38,
				2 => 42,
				3 => 32,
				4 => 25,
				5 => 23,
				6 => 15,
				7 => 18,
				8 => 18,
				9 => 19,
				10 => 25,
				11 => 28,
				12 => 36),
			8 => array(1 => 35,
				2 => 38,
				3 => 28,
				4 => 20,
				5 => 13,
				6 => 8,
				7 => 13,
				8 => 14,
				9 => 18,
				10 => 22,
				11 => 26,
				12 => 33),
			9 => array(1 => 29,
				2 => 30,
				3 => 26,
				4 => 18,
				5 => 10,
				6 => 5,
				7 => 4,
				8 => 4,
				9 => 7,
				10 => 12,
				11 => 22,
				12 => 28),
			10 => array(1 => 12,
				2 => 20,
				3 => 8,
				4 => -5,
				5 => -10,
				6 => -12,
				7 => -15,
				8 => -20,
				9 => -15,
				10 => -10,
				11 => -5,
				12 => 10)
			);
		$temp_min = array(
			0 => array(1 => -89,
				2 => -60,
				3 => -60,
				4 => -40,
				5 => -28,
				6 => -24,
				7 => -18,
				8 => -10,
				9 => -23,
				10 => -29,
				11 => -31,
				12 => -45),
			1 => array(1 => -33,
				2 => -38,
				3 => -28,
				4 => -13,
				5 => -4,
				6 => -1,
				7 => 7,
				8 => 6,
				9 => 3,
				10 => -8,
				11 => -15,
				12 => -26),
			2 => array(1 => -21,
				2 => -21,
				3 => -15,
				4 => -2,
				5 => 0.5,
				6 => 5,
				7 => 12,
				8 => 13,
				9 => 7,
				10 => -2,
				11 => -5,
				12 => -18),
			3 => array(1 => -16,
				2 => -12,
				3 => -8,
				4 => 0.5,
				5 => 4,
				6 => 9,
				7 => 13,
				8 => 13,
				9 => 11,
				10 => 5,
				11 => -3,
				12 => -17),
			4 => array(1 => 9,
				2 => 9,
				3 => 9,
				4 => 10,
				5 => 12,
				6 => 13,
				7 => 13,
				8 => 13,
				9 => 13,
				10 => 13,
				11 => 10,
				12 => 8),
			5 => array(1 => 13,
				2 => 12,
				3 => 13,
				4 => 13,
				5 => 13,
				6 => 13,
				7 => 13,
				8 => 13,
				9 => 13,
				10 => 13,
				11 => 13,
				12 => 13),
			6 => array(1 => 13,
				2 => 13,
				3 => 13,
				4 => 13,
				5 => 13,
				6 => 11,
				7 => 9,
				8 => 8,
				9 => 9,
				10 => 10,
				11 => 12,
				12 => 13),
			7 => array(1 => 13,
				2 => 13,
				3 => 10,
				4 => 5,
				5 => -3,
				6 => -17,
				7 => -16,
				8 => -16,
				9 => -12,
				10 => 0.5,
				11 => 4,
				12 => 9),
			8 => array(1 => 12,
				2 => 13,
				3 => 7,
				4 => -2,
				5 => -5,
				6 => -18,
				7 => -21,
				8 => -21,
				9 => -15,
				10 => -2,
				11 => 0.5,
				12 => 5),
			9 => array(1 => 7,
				2 => 6,
				3 => 3,
				4 => -8,
				5 => -15,
				6 => -26,
				7 => -33,
				8 => -38,
				9 => -28,
				10 => -13,
				11 => -4,
				12 => -1),
			10 => array(1 => -13,
				2 => -10,
				3 => -23,
				4 => -29,
				5 => -31,
				6 => -45,
				7 => -89,
				8 => -60,
				9 => -40,
				10 => -28,
				11 => -24,
				12 => -18)
			);
		$temp_avg = array(
			0 => array(1 => -40,
				2 => -38,
				3 => -60,
				4 => -22,
				5 => -8,
				6 => -6,
				7 => 8,
				8 => 12,
				9 => 15,
				10 => 7,
				11 => -9,
				12 => -25),
			1 => array(1 => -12,
				2 => -12,
				3 => -7,
				4 => -3,
				5 => 8,
				6 => 16,
				7 => 18,
				8 => 20,
				9 => 15,
				10 => 6,
				11 => 2,
				12 => -12),
			2 => array(1 => -7,
				2 => -7,
				3 => 1,
				4 => 8,
				5 => 14,
				6 => 21,
				7 => 24,
				8 => 25,
				9 => 18,
				10 => 8,
				11 => 6,
				12 => 0.5),
			3 => array(1 => 10,
				2 => 10,
				3 => 12,
				4 => 16,
				5 => 19,
				6 => 23,
				7 => 26,
				8 => 28,
				9 => 24,
				10 => 18,
				11 => 14,
				12 => 8),
			4 => array(1 => 19,
				2 => 19,
				3 => 21,
				4 => 25,
				5 => 29,
				6 => 32,
				7 => 33,
				8 => 33,
				9 => 31,
				10 => 27,
				11 => 23,
				12 => 18),
			5 => array(1 => 24.1,
				2 => 26.7,
				3 => 26.9,
				4 => 28.3,
				5 => 27.4,
				6 => 26.7,
				7 => 25.3,
				8 => 26.5,
				9 => 26.1,
				10 => 28,
				11 => 27.4,
				12 => 26.2),
			6 => array(1 => 33,
				2 => 33,
				3 => 31,
				4 => 27,
				5 => 23,
				6 => 19,
				7 => 18,
				8 => 19,
				9 => 21,
				10 => 25,
				11 => 29,
				12 => 32),
			7 => array(1 => 26,
				2 => 28,
				3 => 24,
				4 => 18,
				5 => 14,
				6 => 8,
				7 => 10,
				8 => 10,
				9 => 10,
				10 => 16,
				11 => 19,
				12 => 23),
			8 => array(1 => 24,
				2 => 25,
				3 => 18,
				4 => 8,
				5 => 6,
				6 => -7,
				7 => -7,
				8 => -7,
				9 => 1,
				10 => 8,
				11 => 14,
				12 => 21),
			9 => array(1 => 18,
				2 => 20,
				3 => 15,
				4 => 6,
				5 => 2,
				6 => -12,
				7 => -12,
				8 => -12,
				9 => -7,
				10 => -3,
				11 => 8,
				12 => 16),
			10 => array(1 => 12,
				2 => 15,
				3 => 7,
				4 => -9,
				5 => -16,
				6 => -38,
				7 => -38,
				8 => -32,
				9 => -22,
				10 => -8,
				11 => -6,
				12 => 8)
			);
		
		$multipliers = array(
			0 => 0.9,
			1 => 0.95,
			2 => 1,
			3 => 1.11,
			4 => 1.15,
			5 => 1.2,
			6 => 1.09,
			7 => 1.1,
			8 => 1.06,
			9 => 1.18,
			10 => 1.04,
			11 => 0.98,
			12 => 0.91,
			13 => 0.89,
			14 => 0.97,
			15 => 0.89,
			16 => 0.85
			);
		
		
		
		$sql = "SELECT `type`, `temperature` FROM `weather` WHERE `datetime`=$this->dateTime AND `x`=$x AND `y`=$y LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$rain = $row[0];
			$temperature = $row[1];
			$dewpoint = round($temperature-((100-$humidity_avg[$index][$this->month])/5));
		}
		else {
			$temperature = $temp_avg[$index][$this->month]*$multipliers[$cycle];
			
			if ($timeofday == "Daylight") $temperature += rand(5,8);
			else if ($timeofday == "Dusk") $temperature += rand(3,5);
			else if ($timeofday == "Twilight") $temperature += rand(-1,2);
			else if ($timeofday == "Dusk") $temperature -= rand(2,5);
			else $temperature -= rand(2,9);
			
			
			if (searchSingle($terrains, 1, "desert")) {
					$temperature *= 1.2;
					if ($timeofday = "Darkness") $temperature -= 8;
					if ($timeofday = "Daylight") $temperature += 10;
			}
			if (searchSingle($terrains, 1, "savanna")) {
					$temperature *= 1.05;
			}
			if (searchSingle($terrains, 1, "deciduous")) {
					$temperature *= 0.85;
			}
			if (searchSingle($terrains, 1, "mountains")) {
					$temperature *= 1.05;
			}
			
			$temperature *= $multipliers[$cycle2];
			
			if ($cycle==0&&$cycle2==0) $temperature += 10;//super warm
			else if ($cycle==4&&$cycle2==4) $temperature -= 10;//super cold
			
			if (searchSingle($terrains, 1, "rainforest")) {
					$temperature = max(20,min(34, $temperature));
			}
			
			if ($temperature>$temp_max[$index][$this->month]) $temperature = $temp_max[$index][$this->month];
			else if ($temperature<$temp_min[$index][$this->month]) $temperature = $temp_min[$index][$this->month];
			
			$dewpoint = round($temperature-((100-$humidity_avg[$index][$this->month])/5));
			
			if ($dewpoint<$temperature) {
				$chance_of_rain = min(10,$water*0.2);
				if (searchSingle($terrains, 1, "desert")) {
					$chance_of_rain = min(5, $chance_of_rain);
				}
				if (rand(0,100)<$chance_of_rain) {
					if ($temperature<=0) $rain = 3;
					else if ($temperature<4&&rand(0,2)==0) $rain = 2;
					else $rain = 1;
				}
				else $rain = 0;
			}
			else $rain = 0;
			
			$sql2 = "INSERT INTO `weather` (`x`, `y`, `datetime`, `type`, `temperature`) VALUES ($x, $y, $this->dateTime, $rain, ". round($temperature) . ")";
			$this->mysqli->query($sql2);
		}
		
		return array(
			"temp" => round($temperature),
			"dew" => $dewpoint,
			"rain" => $rain,
			"moisture" => $humidity_avg[$index][$this->month]
			);
	}
	
	public function describeTemperature($temperature) {
		if ($temperature<-35) return "'hell freezes over' cold";
		else if ($temperature<-21) return "'freeze your ears off' cold";
		else if ($temperature<-14) return "'long underwear' cold";
		else if ($temperature<-7) return "nippy freezing";
		else if ($temperature<0) return "slightly below freezing";
		else if ($temperature<6) return "cold";
		else if ($temperature<11) return "somewhat cold";
		else if ($temperature<17) return "lukewarm";
		else if ($temperature<22) return "slightly warm";
		else if ($temperature<27) return "warm";
		else if ($temperature<31) return "hot";
		else if ($temperature<39) return "'who needs clothes?' hot";
		else return "hot as a sauna";
	}
	
	public function describeDewpoint($dew) {
		if ($dew>20) return "uncomfortably muggy";
		else if ($dew>17) return "muggy";
		else if ($dew>15) return "humid";
		else if ($dew>12) return "pleasant";
		else if ($dew>9) return "refreshing";
		else if ($dew>0) return "brisk";
		else return "dry";
	}
	
	public function getRealTime() {
		
		$t = time();
		$d = $t/60/60/4 - 103070;//one day irl is 6 days ig
		
		$fraction = $d - floor($d);
		
		if ($fraction<(1/12)) $hour = 0;
		else if ($fraction<(1/12)*2) $hour = 1;
		else if ($fraction<(1/12)*3) $hour = 2;
		else if ($fraction<(1/12)*4) $hour = 3;
		else if ($fraction<(1/12)*5) $hour = 4;
		else if ($fraction<(1/12)*6) $hour = 5;
		else if ($fraction<(1/12)*7) $hour = 6;
		else if ($fraction<(1/12)*8) $hour = 7;
		else if ($fraction<(1/12)*9) $hour = 8;
		else if ($fraction<(1/12)*10) $hour = 9;
		else if ($fraction<(1/12)*11) $hour = 10;
		else $hour = 11;
		
		for ($minute = 59; $minute>-1; $minute--) {
			if ((($hour*60+$minute)/720)<$fraction) {
				$min = $minute;
				break;
			}
		}
		
		return array(
			"day" => floor($d),
			"hour" => $hour,
			"minute" => $min,
			"rt" => $d
			);
	}
	
	function loadTime() {
		$info = $this->getRealTime();
		$year = floor($info["day"]/288);
		$month = floor(($info["day"]-($year*288))/24);
		$day = ($info["day"]-($year*288)-($month*24));
		
		$this->year = $year+1;
		$this->month= $month+1;
		$this->day= $day+1;
		$this->dateTime = "" . $this->year . $this->prefix($this->month,2) . $this->prefix($this->day,2) . $this->prefix($info["hour"],2);//the last part is hour
		$this->hour = $info["hour"];
		$this->minute = $info["minute"];
	}
	
	public function getMultiplierLateFall($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.3,
				10 => 1,
				11 => 0.2,
				12 => 0),
			1 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.4,
				10 => 1,
				11 => 0.2,
				12 => 0),
			2 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.5,
				10 => 1,
				11 => 0.8,
				12 => 0),
			3 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.3,
				10 => 1,
				11 => 0.8,
				12 => 0.2),
			4 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.2,
				10 => 0.6,
				11 => 1,
				12 => 0.4),
			5 => array(1 => 0,
				2 => 0,
				3 => 0.8,
				4 => 0,
				5 => 0,
				6 => 0.7,
				7 => 0,
				8 => 0,
				9 => 0.6,
				10 => 0,
				11 => 0,
				12 => 0.7),
			6 => array(1 => 0,
				2 => 0,
				3 => 0.2,
				4 => 0.6,
				5 => 1,
				6 => 0.4,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			7 => array(1 => 0,
				2 => 0,
				3 => 0.3,
				4 => 1,
				5 => 0.8,
				6 => 0.2,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			8 => array(1 => 0,
				2 => 0,
				3 => 0.5,
				4 => 1,
				5 => 0.8,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			9 => array(1 => 0,
				2 => 0,
				3 => 0.4,
				4 => 1,
				5 => 0.2,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			10 => array(1 => 0,
				2 => 0.3,
				3 => 1,
				4 => 0.2,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0)
			);
		return $seasons[$index][$this->month];
	}
	
	public function getMultiplierSpring($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 1,
				5 => 0.5,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			1 => array(1 => 0,
				2 => 0,
				3 => 0.5,
				4 => 1,
				5 => 0.5,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			2 => array(1 => 0.5,
				2 => 1,
				3 => 0.5,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			3 => array(1 => 0.4,
				2 => 1,
				3 => 0.75,
				4 => 0.2,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			4 => array(1 => 1,
				2 => 0.2,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0.5,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			5 => array(1 => 1,
				2 => 0,
				3 => 0,
				4 => 1,
				5 => 0,
				6 => 0,
				7 => 1,
				8 => 0,
				9 => 0,
				10 => 1,
				11 => 0,
				12 => 0),
			6 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 1,
				8 => 0.2,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0.5),
			7 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0.5,
				8 => 1,
				9 => 0.5,
				10 => 0,
				11 => 0,
				12 => 0),
			8 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0.5,
				8 => 1,
				9 => 0.5,
				10 => 0,
				11 => 0,
				12 => 0),
			9 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.4,
				10 => 1,
				11 => 0.2,
				12 => 0),
			10 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.3,
				10 => 1,
				11 => 0,
				12 => 0)
			);
		return $seasons[$index][$this->month];
	}
	
	public function getMultiplierSummer($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0.1,
				5 => 0.6,
				6 => 0.8,
				7 => 1,
				8 => 0.8,
				9 => 0.2,
				10 => 0.1,
				11 => 0,
				12 => 0),
			1 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0.2,
				5 => 0.6,
				6 => 0.8,
				7 => 1,
				8 => 0.8,
				9 => 0.3,
				10 => 0.1,
				11 => 0,
				12 => 0),
			2 => array(1 => 0,
				2 => 0,
				3 => 0.1,
				4 => 0.3,
				5 => 0.5,
				6 => 0.8,
				7 => 1,
				8 => 0.8,
				9 => 0.2,
				10 => 0.1,
				11 => 0,
				12 => 0),
			3 => array(1 => 0,
				2 => 0,
				3 => 0.2,
				4 => 0.5,
				5 => 0.7,
				6 => 0.8,
				7 => 0.9,
				8 => 1,
				9 => 0.7,
				10 => 0.2,
				11 => 0.1,
				12 => 0),
			4 => array(1 => 0.2,
				2 => 0.4,
				3 => 0.7,
				4 => 0.8,
				5 => 0.9,
				6 => 1,
				7 => 1,
				8 => 0.8,
				9 => 0.6,
				10 => 0.2,
				11 => 0.1,
				12 => 0),
			5 => array(1 => 0.5,
				2 => 1,
				3 => 0.7,
				4 => 0.5,
				5 => 1,
				6 => 0.7,
				7 => 0.5,
				8 => 1,
				9 => 0.7,
				10 => 0.5,
				11 => 1,
				12 => 0.7),
			6 => array(1 => 1,
				2 => 0.9,
				3 => 0.7,
				4 => 0.2,
				5 => 0.1,
				6 => 0,
				7 => 0.2,
				8 => 0.4,
				9 => 0.7,
				10 => 0.8,
				11 => 0.9,
				12 => 1),
			7 => array(1 => 1,
				2 => 0.9,
				3 => 0.8,
				4 => 0.2,
				5 => 0.1,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.2,
				10 => 0.4,
				11 => 0.7,
				12 => 0.9),
			8 => array(1 => 1,
				2 => 0.9,
				3 => 0.2,
				4 => 0.1,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.2,
				10 => 0.4,
				11 => 0.7,
				12 => 0.9),
			9 => array(1 => 1,
				2 => 0.8,
				3 => 0.2,
				4 => 0.1,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0.2,
				11 => 0.3,
				12 => 0.6),
			10 => array(1 => 1,
				2 => 0.8,
				3 => 0.1,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0.2,
				11 => 0.3,
				12 => 0.6)
			);
		return $seasons[$index][$this->month];
	}
	
	public function getMultiplierHarvest($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0.3,
				8 => 0.8,
				9 => 1,
				10 => 0.2,
				11 => 0,
				12 => 0),
			1 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0.2,
				8 => 0.6,
				9 => 1,
				10 => 0.6,
				11 => 0,
				12 => 0),
			2 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0.2,
				9 => 0.7,
				10 => 1,
				11 => 0.3,
				12 => 0),
			3 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.3,
				10 => 1,
				11 => 0.5,
				12 => 0),
			4 => array(1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.4,
				10 => 1,
				11 => 0.8,
				12 => 0.2),
			5 => array(1 => 0,
				2 => 1,
				3 => 0,
				4 => 0,
				5 => 1,
				6 => 0,
				7 => 0,
				8 => 1,
				9 => 0,
				10 => 0,
				11 => 1,
				12 => 0),
			6 => array(1 => 0,
				2 => 0,
				3 => 0.4,
				4 => 1,
				5 => 0.8,
				6 => 0.2,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			7 => array(1 => 0,
				2 => 0,
				3 => 0.3,
				4 => 1,
				5 => 0.8,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			8 => array(1 => 0,
				2 => 0.2,
				3 => 0.5,
				4 => 1,
				5 => 0.5,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			9 => array(1 => 0,
				2 => 0.2,
				3 => 1,
				4 => 0.6,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0),
			10 => array(1 => 0,
				2 => 0.3,
				3 => 1,
				4 => 0.5,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0,
				10 => 0,
				11 => 0,
				12 => 0)
			);
		return $seasons[$index][$this->month];
	}
	
	public function getMultiplierWarm($y) {
		$index = round(($y+5000)/1000);
		
		$seasons = array(
			0 => array(1 => 0,
				2 => 0,
				3 => 0.1,
				4 => 0.2,
				5 => 0.7,
				6 => 0.9,
				7 => 1,
				8 => 0.9,
				9 => 0.5,
				10 => 0.2,
				11 => 0.1,
				12 => 0),
			1 => array(1 => 0,
				2 => 0,
				3 => 0.1,
				4 => 0.3,
				5 => 0.8,
				6 => 0.9,
				7 => 1,
				8 => 0.9,
				9 => 0.5,
				10 => 0.2,
				11 => 0.1,
				12 => 0),
			2 => array(1 => 0,
				2 => 0.1,
				3 => 0.2,
				4 => 0.3,
				5 => 0.6,
				6 => 0.9,
				7 => 1,
				8 => 0.9,
				9 => 0.4,
				10 => 0.2,
				11 => 0.1,
				12 => 0),
			3 => array(1 => 0,
				2 => 0.1,
				3 => 0.3,
				4 => 0.6,
				5 => 0.7,
				6 => 0.9,
				7 => 1,
				8 => 1,
				9 => 0.8,
				10 => 0.5,
				11 => 0.2,
				12 => 0.1),
			4 => array(1 => 0.3,
				2 => 0.5,
				3 => 0.6,
				4 => 0.9,
				5 => 1,
				6 => 1,
				7 => 1,
				8 => 0.9,
				9 => 0.7,
				10 => 0.4,
				11 => 0.2,
				12 => 0.1),
			5 => array(1 => 0.6,
				2 => 1,
				3 => 0.8,
				4 => 0.6,
				5 => 1,
				6 => 0.8,
				7 => 0.6,
				8 => 1,
				9 => 0.8,
				10 => 0.6,
				11 => 1,
				12 => 0.7),
			6 => array(1 => 1,
				2 => 0.9,
				3 => 0.7,
				4 => 0.3,
				5 => 0.2,
				6 => 0.1,
				7 => 0.2,
				8 => 0.5,
				9 => 0.7,
				10 => 0.8,
				11 => 0.9,
				12 => 1),
			7 => array(1 => 1,
				2 => 0.9,
				3 => 0.8,
				4 => 0.3,
				5 => 0.2,
				6 => 0.1,
				7 => 0,
				8 => 0.1,
				9 => 0.3,
				10 => 0.4,
				11 => 0.8,
				12 => 0.9),
			8 => array(1 => 1,
				2 => 0.9,
				3 => 0.5,
				4 => 0.3,
				5 => 0.1,
				6 => 0,
				7 => 0,
				8 => 0.1,
				9 => 0.4,
				10 => 0.6,
				11 => 0.8,
				12 => 0.9),
			9 => array(1 => 1,
				2 => 0.8,
				3 => 0.4,
				4 => 0.2,
				5 => 0.1,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.1,
				10 => 0.3,
				11 => 0.4,
				12 => 0.7),
			10 => array(1 => 1,
				2 => 0.8,
				3 => 0.5,
				4 => 0.1,
				5 => 0,
				6 => 0,
				7 => 0,
				8 => 0,
				9 => 0.1,
				10 => 0.3,
				11 => 0.4,
				12 => 0.7)
			);
		return $seasons[$index][$this->month];
	}
}
?>
