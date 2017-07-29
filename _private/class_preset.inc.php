<?

Class Preset {
	private $mysqli;//db connection
	var $uid = 0;
	var $name = "unknown object";
	var $parent = 0;

	public function __construct($mysqli, $uid=0) {
		$this->mysqli = $mysqli;
		$this->uid = $uid;
		
		if ($uid>0) $this->loadData();
	}
	
	public function loadData() {
		$sql = "SELECT `name`, `parentFK` FROM `o_presets` WHERE `uid`=$this->uid";
		$res = $this->mysqli->query($sql);
		if (mysqli_num_rows($res)) {
			$row = mysqli_fetch_row($res);
			$this->name = $row[0];
			$this->parent = $row[1];
			return $this->name;
		}
		else return false;
	}
	
	function getAttribute($attr) {
		$res = $this->mysqli->query("SELECT `value` FROM `pr_attrs` WHERE `o_presetFK`=$this->uid AND `attributeFK`=$attr LIMIT 1");
		if (!$res) {
			para("Query failed: " . $this->mysqli->error);
			return false;
		}
		if ($res->num_rows==0) return false;//value doesn't exist
		else {
			$row = $res->fetch_object();
			$value = $row->value;
		}
		return $value;
	}
	
	function getPieces($weight) {
		$countable = $this->getAttribute(44);
		if (!$countable) return 1;
		$min_sm_mass = $this->getAttribute(40);
		$max_sm_mass = $this->getAttribute(41);
		$min_lg_mass = $this->getAttribute(42);
		$max_lg_mass = $this->getAttribute(43);
		$unit_mass = $this->getAttribute(6);
		
		if ($min_sm_mass&&$max_sm_mass) {
			$min_pieces = max(1,round($weight/$max_sm_mass));
			$max_pieces = max(1,round($weight/$min_sm_mass));
			return rand($min_pieces, $max_pieces);
		}
		if ($min_lg_mass&&$max_lg_mass) {
			$min_pieces = max(1,round($weight/$max_lg_mass/1000));
			$max_pieces = max(1,round($weight/$min_lg_mass/1000));
			return rand($min_pieces, $max_pieces);
		}
		if ($unit_mass) {
			return max(1,round($weight/$unit_mass));
		}
		return 1;
	}
}
