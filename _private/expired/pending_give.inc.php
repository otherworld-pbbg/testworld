<?
/*
	function createPendingGive($timestamp, $ocharid, $item, $method, $amount=0) {
		$curTime = $this->getInternalTime();
		$sql3 = "SELECT `uid` FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item LIMIT 1";
		$res = $this->mysqli->query($sql3);
		if (!mysqli_num_rows($res)) return -1;//the character isn't carrying the item
		if ($method=="weight") {
			$randomVariance = (rand(0, 40)-20)/100+1;
			$droppableAmount = round($randomVariance*$amount);
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->getSubPieces($droppableAmount, $this->uid);
		}
		else if ($method=="pieces") {
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->getSubWeight($amount, $this->uid);
		}
		else if ($method=="whole") {
			$invItem = new Obj($this->mysqli, $item);
			$invItem->getBasicData();
			$res = $invItem->getSubWeight($invItem->pieces, $this->uid);
		}
		
		if ($method!="whole") {
			if ($res) {
				if ($res["method"]=='part') {
					if ($res["countable"]==1) $sql = "UPDATE `objects` SET `pieces`=`pieces`-" . $res["pieces"] . ", `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`datetime`<'" . $curTime[1] . "' OR (`datetime`='" . $curTime[1] . "' AND `minute`<='" . $curTime[2] . "')) LIMIT 1";
					else $sql = "UPDATE `objects` SET `weight`=`weight`-" . $res["weight"] . " WHERE `parent`=$this->bodyId AND `uid`=$item AND (`datetime`<'" . $curTime[1] . "' OR (`datetime`='" . $curTime[1] . "' AND `minute`<='" . $curTime[2] . "')) LIMIT 1";
					
					$this->mysqli->query($sql);
					if ($this->mysqli->affected_rows==0) return -1;//The character isn't carrying this item
					else {
						if ($res["countable"]==1) $sql2 = "UPDATE `pending_give` SET `pieces`=`pieces`+" . $res["pieces"] . ", `weight`=`weight`+" . $res["weight"] . " WHERE `receiverFK`=$ocharid AND `giverFK`=$this->uid AND `presetFK`=$invItem->preset AND `secondary`=$invItem->secondary AND `timestamp`=$timestamp AND `x`=$this->x AND `y`=$this->y AND `building`=$this->building LIMIT 1";
						else $sql2 = "UPDATE `pending_give` SET `weight`=`weight`+" . $res["weight"] . " WHERE `receiverFK`=$ocharid AND `giverFK`=$this->uid AND `presetFK`=$invItem->preset AND `secondary`=$invItem->secondary AND `timestamp`=$timestamp AND `x`=$this->x AND `y`=$this->y AND `building`=$this->building LIMIT 1";
						
						//this tries to increase a pile that is in pending_give table
						$this->mysqli->query($sql2);
						if ($this->mysqli->affected_rows==0) {
							//There is no pile to merge with, so creating new pile
							$sql3 = "INSERT INTO `pending_give` (`uid`, `presetFK`, `general_type`, `secondary`, `pieces`, `weight`, `giverFK`, `receiverFK`, `timestamp`, `x`, `y`, `building`) VALUES (NULL, '$invItem->preset', '$invItem->type', '$invItem->secondary', '" . $res["pieces"] . "', '" . $res["weight"] . "', '$this->uid', '$ocharid', '$timestamp', '$this->x', '$this->y', '$this->building')";
							$this->mysqli->query($sql3);
							
							$result = $this->mysqli->insert_id;
							if ($result) return 1;
							else return -3;//generating new pile failed
						}
						else return 1;
					}
				}
				else $method = "whole";
			}
		}
		
		if ($method=="whole") {
			$pieces = $invItem->pieces;
			$actualDropWeight = $invItem->weight;
			//check if stackable
			if ($res["countable"]==1) $sql = "UPDATE `pending_give` SET `pieces`=`pieces`+$pieces, `weight`=`weight`+$actualDropWeight WHERE `receiverFK`=$ocharid AND `giverFK`=$this->uid AND `presetFK`=$invItem->preset AND `secondary`=$invItem->secondary AND `timestamp`=$timestamp AND `x`=$this->x AND `y`=$this->y AND `building`=$this->building LIMIT 1";
			else $sql = "UPDATE `pending_give` SET `weight`=`weight`+" . $actualDropWeight . " WHERE `receiverFK`=$ocharid AND `giverFK`=$this->uid AND `presetFK`=$invItem->preset AND `general_type`=5 AND `secondary`=$invItem->secondary AND `timestamp`=$timestamp AND `x`=$this->x AND `y`=$this->y AND `building`=$this->building LIMIT 1";
			//merge with pile if exists, non-countable objects won't merge if they're not resources
			$this->mysqli->query($sql);
			
			if ($this->mysqli->affected_rows<=0) {
				$sql = "INSERT INTO `pending_give` (`uid`, `presetFK`, `general_type`, `secondary`, `pieces`, `weight`, `giverFK`, `receiverFK`, `timestamp`, `x`, `y`, `building`) VALUES (NULL, '$invItem->preset', '$invItem->type', '$invItem->secondary', '" . $res["pieces"] . "', '" . $res["weight"] . "', '$this->uid', '$ocharid', '$timestamp', '$this->x', '$this->y', '$this->building')";
				$this->mysqli->query($sql);
				
				if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
			}
			
			$sql2 = "DELETE FROM `objects` WHERE `parent`=$this->bodyId AND `uid`=$item AND (`datetime`<'" . $curTime[1] . "' OR (`datetime`='" . $curTime[1] . "' AND `minute`<='" . $curTime[2] . "')) LIMIT 1";
			
			$this->mysqli->query($sql2);
			if ($this->mysqli->affected_rows<=0) return -5;//duplication bug
			else return 1;
		}
	}*/
	
function listPendingGives($role) {
		//1-giver, 2-receiver
		$retArr = array();
		if ($role == 1) {
			$sql = "SELECT `uid`, `receiverFK`, `timestamp`, `x`, `y`, `building`, `presetFK`, `general_type`, `secondary`, `pieces`, `weight` FROM `pending_give` WHERE `giverFK`=$this->uid";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$retArr[] = array(
						"uid" => $row[0],
						"ocharid" => $row[1],
						"time" => $row[2],
						"x" => $row[3],
						"y" => $row[4],
						"building" => $row[5],
						"preset" => $row[6],
						"type" => $row[7],
						"secondary" => $row[8],
						"pieces" => $row[9],
						"weight" => $row[10]
						);
				}
				return $retArr;
			}
			else return -1;
		}
		else if ($role == 2) {
			$sql = "SELECT `uid`, `giverFK`, `timestamp`, `x`, `y`, `building`,  `presetFK`, `general_type`, `secondary`, `pieces`, `weight` FROM `pending_give` WHERE `receiverFK`=$this->uid";
			$res = $this->mysqli->query($sql);
			if (mysqli_num_rows($res)) {
				while ($row = mysqli_fetch_row($res)) {
					$retArr[] = array(
						"uid" => $row[0],
						"ocharid" => $row[1],
						"time" => $row[2],
						"x" => $row[3],
						"y" => $row[4],
						"building" => $row[5],
						"preset" => $row[6],
						"type" => $row[7],
						"secondary" => $row[8],
						"pieces" => $row[9],
						"weight" => $row[10]
						);
				}
				return $retArr;
			}
			else return -1;
		}
		else return -2;//invalid role
	}
	
function checkForDueGives() {
		$successCounter = 0;
		$cancelCounter = 0;
		$curTime = $this->getInternalTime();
		$sql = "SELECT `uid`, `presetFK`, `general_type`, `secondary`, `pieces`, `weight`, `giverFK`, `timestamp`, `x`, `y`, `building` FROM `pending_give` WHERE `timestamp`<=".$curTime[1]." AND `receiverFK`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			while ($row = mysqli_fetch_row($res)) {
				if ($row[8]==$this->x&&$row[9]==$this->y&&$row[10]==$this->building) {
					$result = $this->receivePendingGive($row[0]);
					if ($result<0) return $result;
					else $successCounter++;
				}
				else {
					$this->cancelPendingGive($row[0]);
					$cancelCounter++;
				}
			}
			return array($successCounter, $cancelCounter);
		}
		else return -1;//You have no gives due
	}
	
/*function receivePendingGive($giveUid) {
		$curTime = $this->getInternalTime();
		//note: this function should only be called if it has already been established that the receiver is in the same location
		$sql = "SELECT `presetFK`, `general_type`, `secondary`, `pieces`, `weight`, `giverFK`, `receiverFK`, `timestamp`, `x`, `y`, `building` FROM `pending_give` WHERE `uid`=$giveUid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
		}
		//to do: check if inventory is full
		$pile = new Obj($this->mysqli);
		$result = $pile->create($row[0], $row[1], $this->bodyId, 'Received give', 'NULL', 'NULL', 0, 0, $row[2], $row[3], $row[4], $curTime[1], $curTime[2]);
		//$sql2 = "INSERT INTO `objects`(`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '".$row[0]."', '".$row[1]."', '$this->bodyId', CURRENT_TIMESTAMP, 'Received give', NULL, NULL, '0', '0', '".$row[2]."', '".$row[3]."', '".$row[4]."', '".$curTime[1]."', '".$curTime[2]."')";
		//$this->mysqli->query($sql2);
		//if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
		if (!$result) return -4;
	
		
		$sql3 = "DELETE FROM `pending_give` WHERE `uid`=$giveUid LIMIT 1";
		$this->mysqli->query($sql3);
		if ($this->mysqli->affected_rows<=0) return -5;//duplication bug
		else return 1;
	}*/
	/*
	function cancelPendingGive($giveUid) {
		$sql = "SELECT `presetFK`, `general_type`, `secondary`, `pieces`, `weight`, `giverFK`, `receiverFK`, `timestamp`, `x`, `y`, `building` FROM `pending_give` WHERE `uid`=$giveUid LIMIT 1";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			if ($row[5]==$this->uid) {
				//the canceller is the giver
				if ($row[8]==$this->x&&$row[9]==$this->y&&$row[10]==$this->building) {
					//return to giver
					//$sql2 = "INSERT INTO `objects`(`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '".$row[0]."', '".$row[1]."', '$this->bodyId', CURRENT_TIMESTAMP, 'Returned to sender', NULL, NULL, '0', '0', '".$row[2]."', '".$row[3]."', '".$row[4]."', '".$curTime[1]."', '".$curTime[2]."')";
					//$this->mysqli->query($sql2);
					//if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
					$pile = new Obj($this->mysqli);
					$result = $pile->create($row[0], $row[1], $this->bodyId, 'Returned to sender', 'NULL', 'NULL', 0, 0, $row[2], $row[3], $row[4], $curTime[1], $curTime[2]);
					if (!$result) return -4;
				}
				else return -1;//you're in a different location
			}
			else if ($row[6]==$this->uid) {
				//the canceller is the receiver
				$receiver = new Character($this->mysqli, $row[5]);
				$receiver->getBasicData();
				if ($row[8]==$receiver->x&&$row[9]==$receiver->y&&$row[10]==$receiver->building) {
					//return to sender
					$pile = new Obj($this->mysqli);
					$result = $pile->create($row[0], $row[1], $receiver->bodyId, 'Returned to sender', 'NULL', 'NULL', 0, 0, $row[2], $row[3], $row[4], $curTime[1], $curTime[2]);
					//$sql2 = "INSERT INTO `objects`(`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '".$row[0]."', '".$row[1]."', '$receiver->bodyId', CURRENT_TIMESTAMP, 'Returned to sender', NULL, NULL, '0', '0', '".$row[2]."', '".$row[3]."', '".$row[4]."', '".$curTime[1]."', '".$curTime[2]."')";
					//$this->mysqli->query($sql2);
					//if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
					if (!$result) return -4;
				}
				else {
					//drop on the ground
					$pile = new Obj($this->mysqli);
					$result = $pile->create($row[0], $row[1], $row[10], 'Dropped returned give because no one was there', $row[8], $row[9], 0, 0, $row[2], $row[3], $row[4], $curTime[1], $curTime[2]);
					//$sql2 = "INSERT INTO `objects`(`uid`, `presetFK`, `general_type`, `parent`, `date_created`, `comments`, `global_x`, `global_y`, `local_x`, `local_y`, `secondaryFK`, `pieces`, `weight`, `datetime`, `minute`) VALUES (NULL, '".$row[0]."', '".$row[1]."', '".$row[10]."', CURRENT_TIMESTAMP, 'Dropped returned give because no one was there', '".$row[8]."', '".$row[9]."', '0', '0', '".$row[2]."', '".$row[3]."', '".$row[4]."', '".$curTime[1]."', '".$curTime[2]."')";
					//$this->mysqli->query($sql2);
					//if ($this->mysqli->affected_rows<=0) return -4;//moving pile failed
					if (!$result) return -4;
				}
			}
			//if it hasn't returned anything by now then the pile was moved successfully
			$sql3 = "DELETE FROM `pending_give` WHERE `uid`=$giveUid LIMIT 1";
			$this->mysqli->query($sql3);
			if ($this->mysqli->affected_rows<=0) return -5;//duplication bug
			else return 1;
		}
	}*/
?>
