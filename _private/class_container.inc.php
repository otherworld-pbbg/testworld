<?php
//This is a very old file and probably needs to be rewritten
class Container
{
	protected $uid;
	private $mysqli;

	public function __construct($mysqli, $uid) {
		
		$this->mysqli = $mysqli;
		$this->uid = $uid;
	}

	public function store($item)
	{

		$sql = "UPDATE `objects` SET `parent`=$this->uid WHERE `uid`=$item LIMIT 1";
		$this->mysqli->query($sql);
		if (mysql_affected_rows()==1)
			return 1;//success
		else return -1;//something went wrong
	}

	public function remove($item, $newParent)
	{
		$sql = "UPDATE `objects` SET `parent`=$newParent WHERE `uid`=$item AND `parent`=$this->uid LIMIT 1";
		$this->mysqli->query($sql);
		if ($this->mysqli->affected_rows==1)
			return 1;//success
		else return -1;//something went wrong
	}

	public function listContents($verbal="")
	{
		$sql = "SELECT `uid` FROM `objects` WHERE `parent`=$this->uid";
		$result = $this->mysqli->query($sql);
		if (mysqli_num_rows($result)) {
			if ($verbal=="verbal")
			{
			while ($row = mysqli_fetch_row($result))
				echo "Item " . $row[0] . "</ br>";
			}
			return $result;
		}
		else {
			if ($verbal=="verbal") echo "The container is empty.<br />";
			return -1;
		}
	}
}


?>