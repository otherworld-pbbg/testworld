<?php
include ("test_json.php");
?>
<html>
<head>
<title>Terrain test</title>
<script src="https://cdn.rawgit.com/konvajs/konva/1.7.4/konva.min.js"></script>
</head>
<body>
<div id="container"></div>

<script type="text/javascript">

var jsonObject = 
<?php
printJSON();
?>;

var space = 80;
var space2 = 60;

function loadImages(sources, callback, biome) {
	var assetDir = '/graphics/';
	var images = {};
	var loadedImages = 0;
	var numImages = 0;
	for(var src in sources) {
		numImages++;
	}
	for(var src in sources) {
		images[src] = new Image();
		images[src].onload = function() {
			if(++loadedImages >= numImages) {
				callback(images, biome);
			}
		};
		images[src].src = assetDir + sources[src];
	}
}


function initStage(images, biome) {
	var stage = new Konva.Stage({
		container: 'container',
		width: 800,
		height: 600,
		draggable: true,
		dragBoundFunc: function(pos) {
			
			var newx = pos.x < -space*16+790 ? -space*16+790 : pos.x;
			var newy = pos.y < -space2*16+520 ? -space2*16+520 : pos.y;
			
			newx = newx > 0 ? 0 : newx;
			newy = newy > 0 ? 0 : newy;
			
			return {
                x: newx,
                y: newy
            }
        }
	});
	var layer = new Konva.Layer();
	
	if (biome=="savanna") {
		var rect = new Konva.Rect({
		  x: 0,
		  y: 0,
		  width: 1300,
		  height: 1100,
		  fill: '#745d25'
		});
    }
    else if (biome=="swamp") {
		var rect = new Konva.Rect({
		  x: 0,
		  y: 0,
		  width: 1300,
		  height: 1100,
		  fill: '#3d5325'
		});
    }
    else if (biome=="tundra") {
		var rect = new Konva.Rect({
		  x: 0,
		  y: 0,
		  width: 1300,
		  height: 1100,
		  fill: '#1c160e'
		});
    }
    else if (biome=="desert") {
		var rect = new Konva.Rect({
		  x: 0,
		  y: 0,
		  width: 1300,
		  height: 1100,
		  fill: '#914522'
		});
    }
    else {
		var rect = new Konva.Rect({
		  x: 0,
		  y: 0,
		  width: 1300,
		  height: 1100,
		  fill: '#170900'
		});
    }
    // add the shape to the layer
    layer.add(rect);

	for(var key in coords) {
		(function() {
			var coords_object = coords[key];
			var img_o = new Konva.Image({
				image: images[key],
				x: coords_object.x,
				y: coords_object.y
			});
			
			layer.add(img_o);
		})();
	}
	
	stage.add(layer);
}
    
var sources = {};
var counter = 0;
var coords = {};

for (y = 0; y < 16; y++) {
	for (x = 0; x < 16; x++) {
		var baseimg = jsonObject["base"][x][y];
		
		sources[counter] = baseimg + '.png';
		coords[counter] = {
			x: x*space,
			y: y*space2
		};
		counter++;
	}
}

for (y = 0; y < 16; y++) {
	for (x = 0; x < 16; x++) {
		var topimg = jsonObject["top"][x][y];
		
		sources[counter] = topimg + '.png';
		coords[counter] = {
			x: x*space,
			y: y*space2
		};
		counter++;
	}
}

loadImages(sources, initStage, jsonObject["biome"]);
</script>
</body>
</html>