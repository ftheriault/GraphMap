var ctx = null;

var background = new Image();
background.src = "images/map.png";

var spriteList = [];
var selectedSprite = null;

$(function() {
	document.getElementById("canvas").oncontextmenu = function() {return false;};
	
	$("canvas").mousedown(function (e) {
		var x = e.pageX - $("canvas").offset().left;
		var y = e.pageY - $("canvas").offset().top;
		var spriteClicked = null;

		for (var i = 0; i < spriteList.length; i++) {
			if (spriteList[i].clicked(x, y)) {
				spriteClicked = spriteList[i];
				break;
			}
		}	

		if (e.which == 3 && spriteClicked != null) {
			if (selectedSprite != null) {
				selectedSprite.setSelected(false);
				selectedSprite = null;
			}

			sendEvent({
				type : "REMOVE",
				name : spriteClicked.name
			});
		}
		else if (spriteClicked == null)  {
			if (selectedSprite != null) {
				selectedSprite.setSelected(false);
				selectedSprite = null;
			}

			var name = prompt("Enter name of city");
			sendEvent({
				type : "ADD",
				x : x,
				y : y,
				name : name
			});
		}
		else if (selectedSprite == spriteClicked) {
			selectedSprite.setSelected(false);
			selectedSprite = null;
		}
		else if (selectedSprite == null) {
			selectedSprite = spriteClicked;
			selectedSprite.setSelected(true);
		}
		else {
			//add link
		}
	});

	ctx = document.getElementById("canvas").getContext("2d");
	window.requestAnimationFrame(step);

	sendEvent({ type : "REFRESH" });
});

function step(timestamp) {
	ctx.clearRect(0, 0, 900, 970);

	if (background.complete) {
		ctx.drawImage(background, 0, 0);
	}

	for (var i = 0; i < spriteList.length; i++) {
		spriteList[i].tick();
	}

	window.requestAnimationFrame(step);
}

function sendEvent(data) {
	$.ajax({
		type: "POST",
		dataType : "json",
		url: "digest.php",
		data: data
	})
	.done(function( msg ) {

		if (msg[0] == "ADDED") {
			spriteList.push(new CitySprite(msg[1].x, msg[1].y, msg[1].name));			
		}
		else if (msg[0] == "REFRESHED") {
			spriteList = [];
			
			for (var i = 0; i < msg[1].length; i++) {
				spriteList.push(new CitySprite(msg[1][i].x, msg[1][i].y, msg[1][i].name));					
			}
		}
		else if (msg[0] == "REMOVED") {
			sendEvent({type : "REFRESH"});
		}
	});
}
