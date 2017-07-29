<?

$arr = array();

for ($y=0; $y<101; $y++)
{
	for ($x=0; $x<101; $x++)
	{
		if ($y==0) {
			$r = rand(40, 63);
			$g = rand(80, 124);
			$b = rand(80, 124);
		}
		
		else
		{
			$source=$y*101-101+$x;//pixel above
			$source2=$y*100+$x;//pixel to the left
			//echo "s ". $source2 . ", ";
			$randomAvg= round(( $arr[$source][2]+rand(-10,10) + $arr[$source2][2]+rand(-10,10) )/2);
			$randomAvg2= round(( $arr[$source][3]+rand(-20,20) + $arr[$source2][3]+rand(-20,20) )/2);
			$randomAvg3= round(( $arr[$source][4]+rand(-20,20) + $arr[$source2][4]+rand(-20,20) )/2);
			//echo $randomAvg .", ";
			$r= max(min($randomAvg,63),40);
			$g= max(min($randomAvg2,124),80);
			$b= max(min($randomAvg3,124),80);
		}
		
		$arr[] = array($x, $y, $r, $g, $b);
	}
}

//$r = red value (0 - 255)
//$g = green value (0 - 255)
//$b = blue value (0 - 255)

$max_height = (int) 0;
$max_width = (int) 0;

foreach ($arr as $a)
{
    if ($a[0] > $max_width)
    {
        $max_width = $a[0];
    }
    if ($a[1] > $max_height)
    {
        $max_height = $a[1];
    }
}

$im = imagecreatetruecolor($max_width, $max_height);
foreach ($arr as $b)
{
    $col = imagecolorallocate($im, $b[2], $b[3], $b[4]);
    imagesetpixel ($im , $b[0] , $b[1] , $col );
}

//header('Content-type: image/png');
//imagepng($im);
imagepng($im, "graphics/test2.png", NULL, NULL);
imagedestroy($im);
echo "<img src='graphics/test2.png' />";
?>
