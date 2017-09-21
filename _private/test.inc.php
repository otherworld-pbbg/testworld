<?php
Class Test {
	public $seed1=1;
	public $seed2=1;
	public $grass=127;
	public $bush=56;
	public $tree=53;
	public $rock=56;
	public $organic=127;
	public $water=30;
	public $sand=127;
	public $silt=127;
	public $clay=55;
	public $north=-0.5;
	public $east=0;
	public $south=0;
	public $west=0;
	public $smooth=20;
	
	public function __construct() {
		//Do nothing
		$this->grass=rand(0,255);
		$this->bush=rand(0,255);
		$this->tree=rand(0,255);
		$this->rock=rand(0,255);
		$this->organic=rand(0,255);
		$this->water=rand(0,127);
		$this->sand=rand(0,255);
		$this->silt=rand(0,255);
		$this->clay=rand(0,255);
	}
}