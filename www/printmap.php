<canvas id="myCanvas" width="800" height="600">Your browser doesn't support the canvas tag.</canvas>

<script type="text/javascript">
var jsonObject = <?php include "map_json.php" ?>;

var variation = 100;
var space = 60;
var offset = 50;

var prevX = 0;
var curX = 0;
var curY = 0;
var prevRow;
var row;
var dataPoint;
var dataPoint2;
var abovePoint;
var abovePrev;

var c=document.getElementById("myCanvas");
var ctx=c.getContext("2d");

for (i = 1; i < 15; i++) {
	row = jsonObject[i];
	var p = i - 1;
	prevRow = jsonObject[p];
	
	
	for (j = 1; j < 14; j++) {
		dataPoint = row[j];
		dataPoint2 = row[j-1];
		abovePoint = prevRow[j];
		abovePrev = prevRow[j-1];
		leftY = Math.round(offset + (abovePrev['y']*space - (abovePrev['raw']*variation*2.5)));
		rightY = Math.round(offset + (abovePoint['y']*space - (abovePoint['raw']*variation*2.5)));
		
		curX = dataPoint['x']*space;
		prevX = dataPoint2['x']*space;
		left2Y = Math.round(offset + (dataPoint2['y']*space - (dataPoint2['raw']*variation*2.5)));
		right2Y = Math.round(offset + (dataPoint['y']*space - (dataPoint['raw']*variation*2.5)));
		
		if (left2Y+2 < right2Y && leftY+2 < rightY) ctx.fillStyle="#77AA77";
		else if (leftY-2 > rightY && left2Y-2 > right2Y) ctx.fillStyle="#558855";
		else if (leftY+2 < rightY && left2Y-2 > right2Y) ctx.fillStyle="#608F60";
		else ctx.fillStyle="#669966";
		ctx.beginPath();
		ctx.moveTo(prevX, leftY);
		ctx.lineTo(curX, rightY);
		ctx.lineTo(curX, right2Y);
		ctx.lineTo(prevX, left2Y);
		ctx.lineTo(prevX, leftY);
		ctx.stroke();
		ctx.fill();	
	}
}
</script>