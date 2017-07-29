<?php

class Position
{
	private $mysqli;
	public $x;
	public $y;
	public $lx;
	public $ly;

	public function __construct($mysqli, $x, $y, $lx, $ly) {
		
		$this->mysqli = $mysqli;
		$this->x = $x;
		$this->y = $y;
		$this->lx = $lx;
		$this->ly = $ly;
	}
}
?>
