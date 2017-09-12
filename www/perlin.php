<?php
//This is a port of Ken Perlin's "Improved Noise"
//     http://mrl.nyu.edu/~perlin/noise/

// Originally from http://therandomuniverse.blogspot.com/2007/01/perlin-noise-your-new-best-friend.html
// but the site appears to be down, so here is a mirror of it

class Perlin {
	
	var $p, $permutation, $seed;
	var $_default_size = 64;
	
	function Perlin ($seed=NULL) {
		
		//Initialize the permutation array.
		$this->p = array();
		$this->permutation = array( 151,160,137,91,90,15,
		131,13,201,95,96,53,194,233,7,225,140,36,103,30,69,142,8,99,37,240,21,10,23,
		190, 6,148,247,120,234,75,0,26,197,62,94,252,219,203,117,35,11,32,57,177,33,
		88,237,149,56,87,174,20,125,136,171,168, 68,175,74,165,71,134,139,48,27,166,
		77,146,158,231,83,111,229,122,60,211,133,230,220,105,92,41,55,46,245,40,244,
		102,143,54, 65,25,63,161, 1,216,80,73,209,76,132,187,208, 89,18,169,200,196,
		135,130,116,188,159,86,164,100,109,198,173,186, 3,64,52,217,226,250,124,123,
		5,202,38,147,118,126,255,82,85,212,207,206,59,227,47,16,58,17,182,189,28,42,
		223,183,170,213,119,248,152, 2,44,154,163, 70,221,153,101,155,167, 43,172,9,
		129,22,39,253, 19,98,108,110,79,113,224,232,178,185, 112,104,218,246,97,228,
		251,34,242,193,238,210,144,12,191,179,162,241, 81,51,145,235,249,14,239,107,
		49,192,214, 31,181,199,106,157,184, 84,204,176,115,121,50,45,127, 4,150,254,
		138,236,205,93,222,114,67,29,24,72,243,141,128,195,78,66,215,61,156,180
		);
		
		//Populate it
		for ($i=0; $i < 256 ; $i++) {
			$this->p[256+$i] = $this->p[$i] = $this->permutation[$i]; 
		}
		
		//And set the seed
		if ($seed === NULL) $this->seed = time();
		else $this->seed = $seed;
	}
	
	function noise($x,$y,$z,$size=NULL) {
		
		if ($size == NULL) $size = $this->_default_size;
		
		//Set the initial value and initial size
		$value = 0.0; $initialSize = $size;
		
		//Add finer and finer hues of smoothed noise together
		while($size >= 1) {
	
			$value += $this->smoothNoise($x / $size, $y / $size, $z / $size) * $size;
			$size /= 2.0;

		}
		
		//Return the result over the initial size
		return $value / $initialSize;
	
	}
	
	//This function determines what cube the point passed resides in
	//and determines its value.
	function smoothNoise($x, $y, $z) {
		
		//Offset each coordinate by the seed value
		$x+=$this->seed; $y+=$this->seed; $z+=$this->seed;
		
		$orig_x = $x;
		$orig_y = $y;
		$orig_z = $z;
		
		$X1 = (int)floor($x) & 255;                  // FIND UNIT CUBE THAT
		$Y1 = (int)floor($y) & 255;                  // CONTAINS POINT.
		$Z1 = (int)floor($z) & 255;
		$x -= floor($x);                                // FIND RELATIVE X,Y,Z
		$y -= floor($y);                                // OF POINT IN CUBE.
		$z -= floor($z);
		$u = $this->fade($x);                                // COMPUTE FADE CURVES
		$v = $this->fade($y);                                // FOR EACH OF X,Y,Z.
		$w = $this->fade($z);
		
		$A  = $this->p[$X1]+$Y1;
		$AA = $this->p[$A]+$Z1;
		$AB = $this->p[$A+1]+$Z1;      // HASH COORDINATES OF
		$B  = $this->p[$X1+1]+$Y1;
		$BA = $this->p[$B]+$Z1;
		$BB = $this->p[$B+1]+$Z1;      // THE 8 CUBE CORNERS,
		
		//Interpolate between the 8 points determined
		$result = $this->lerp($w, $this->lerp($v, $this->lerp($u, $this->grad($this->p[$AA  ], $x  , $y  , $z   ),  // AND ADD
																  $this->grad($this->p[$BA  ], $x-1, $y  , $z   )), // BLENDED
												  $this->lerp($u, $this->grad($this->p[$AB  ], $x  , $y-1, $z   ),  // RESULTS
																  $this->grad($this->p[$BB  ], $x-1, $y-1, $z   ))),// FROM  8
								  $this->lerp($v, $this->lerp($u, $this->grad($this->p[$AA+1], $x  , $y  , $z-1 ),  // CORNERS
																  $this->grad($this->p[$BA+1], $x-1, $y  , $z-1 )), // OF CUBE
												  $this->lerp($u, $this->grad($this->p[$AB+1], $x  , $y-1, $z-1 ),
																  $this->grad($this->p[$BB+1], $x-1, $y-1, $z-1 ))));
		
		return $result;
	}
	
	function fade($t) { 
		return $t * $t * $t * ( ( $t * ( ($t * 6) - 15) ) + 10);
	}
	
	function lerp($t, $a, $b) { 
		//Make a weighted interpolaton between points
		return $a + $t * ($b - $a); 
	}
	
	function grad($hash, $x, $y, $z) {
		$h = $hash & 15;                      // CONVERT LO 4 BITS OF HASH CODE
		$u = $h<8 ? $x : $y;                 // INTO 12 GRADIENT DIRECTIONS.
		$v = $h<4 ? $y : ($h==12||$h==14 ? $x : $z);
		
		return (($h&1) == 0 ? $u : -$u) + (($h&2) == 0 ? $v : -$v);
	}
	
	//This function I've added. It creates one dimension of noise.
	function random1D($x) {
   	
		if ($size === NULL) $size = $this->_default_size;
		
		$x += $this->seed;
		
		$value = 0.0; $initialSize = $size = 3;
		
		while($size >= 1){
			$value += $this->smoothNoise($x*3 / $size, 100 / $size, 100 / $size);
			$size--;
		}
		
		if ($value < -1) $value = -1;
		if ($value > 1) $value = 1;
		
		return $value;
   
   }
   
   //Same as random1D() only for 2 dimensions.
   function random2D($x,$y) {
   	
		if ($size === NULL) $size = $this->_default_size;
		
		$x += $this->seed;
		$y += $this->seed;
		
		$value = 0.0; $initialSize = $size = 3;
		
		while($size >= 1) {
			$value += $this->smoothNoise($x*3 / $size, $y*3 / $size, 100 / $size);
			$size--;
		}
		
		if ($value < -1) $value = -1;
		if ($value > 1) $value = 1;
		
		return $value;
   
   }
}

include_once "../_private/generic.inc.php";

//This currently generates a color field that varies on all channels. Earlier it varied only on the red channel and the green channel was the opposite of the red channel, while blue was static.
if (isset($_GET["seed"])) {
	$seed = setBint($_GET["seed"], 0, 10000, rand(1,2000));
}
else $seed = rand(1,2000);

if (isset($_GET["smooth"])) {
	$smooth = setBint($_GET["smooth"], 2, 99, rand(10,90));
}
else $smooth = rand(10,90);

if (isset($_GET["lift"])) {
	$tilt = $_GET["lift"];
	if ($tilt!="n"&&$tilt!="s"&&$tilt!="e"&&$tilt!="w"&&$tilt!="ne"&&$tilt!="nw"&&$tilt!="se"&&$tilt!="sw") $tilt = NULL;
}
else $tilt = NULL;

$bob = new Perlin($seed);
$bill = new Perlin($seed+1);
$peter = new Perlin($seed+2);

$gridsize = 125;

echo "smooth: " . $smooth . "<br>";
echo "seed: " . $seed. "<br>";

for($y=0; $y<$gridsize; $y+=1) {
	for($x=0; $x<$gridsize; $x+=1) {
		$num = $bob->noise($x,$y,0,$smooth);
		$num2 = $bill->noise($x,$y,0,$smooth);
		$num3 = $peter->noise($x,$y,0,$smooth);
		
		$raw = ($num/2)+.5;
		if ($tilt=="n"||$tilt=="ne"||$tilt=="nw") {
			if ($y<($gridsize/2)) {
				$raw += (($gridsize/2)-$y)/$gridsize;
			}
		}
		if ($tilt=="s"||$tilt=="se"||$tilt=="sw") {
			if ($y>($gridsize/2)) {
				$raw += ($y-($gridsize/2))/$gridsize;
			}
		}
		if ($tilt=="w"||$tilt=="nw"||$tilt=="sw") {
			if ($x<($gridsize/2)) {
				$raw += (($gridsize/2)-$x)/$gridsize;
			}
		}
		if ($tilt=="e"||$tilt=="ne"||$tilt=="se") {
			if ($x>($gridsize/2)) {
				$raw += ($x-($gridsize/2))/$gridsize;
			}
		}
		$raw = round($raw*12)/12;
		
		$raw2 = ($num2/2)+.5;
		$raw2 = round($raw2*12)/12;
		
		$raw3 = ($num3/2)+.5;
		$raw3 = round($raw3*12)/12;
		//if ($num == 0) $raw = 0;
		//else $raw = 1/abs( $num );
		
		//$raw = pow((5*$raw)-4,3)+.5;
		//$raw = 1-pow(50 * ($raw - 1), 2);
		
		//if ($raw > .9) $raw = 1;
		//else $raw = 0;
		if ($raw < 0) $raw = 0;
		if ($raw2 < 0) $raw2 = 0;
		if ($raw3 < 0) $raw3 = 0;
		
		if ($raw > 1) $raw = 1;
		
		$num = dechex( $raw*255 );
		$num2 = dechex( $raw2*255 );
		$num3 = dechex( $raw3*255 );
		
		if (strlen($num) < 2) $num = "0".$num;
		if (strlen($num2) < 2) $num2 = "0".$num2;
		if (strlen($num3) < 2) $num3 = "0".$num3;
		
		echo "<div style='background-color:#$num". $num2 . $num3 .";width:10px;display:inline-block'>a</div>";
	}
	echo "<br>";
}

/*
$bob = new Perlin(1);

$place = 0;

//for ($i=0; $i<100000; $i+=100) {
for ($i=0; $i<1000; $i++) {
	$num = round(($bob->random1D($i)/2)+.5,2);
	echo $num.'
';
	echo '';
	$place++;
}
*/
?>