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
		var cityClicked = null;
		var pathClicked = null;

		for (var i = 0; i < spriteList.length; i++) {
			if (spriteList[i].clicked(x, y)) {
				if (spriteList[i].name != undefined) {
					cityClicked = spriteList[i];
				}
				else if (spriteList[i].distance != undefined) {
					pathClicked = spriteList[i];
				}

				break;
			}
		}	

		if (e.which == 3) {
			if (selectedSprite != null) {
				selectedSprite.setSelected(false);
				selectedSprite = null;
			}

			if (cityClicked != null) {
				sendEvent({
					type : "REMOVE",
					name : cityClicked.name
				});
			}
			else if (pathClicked != null) {
				sendEvent({
					type : "REMOVE_LINK",
					id : pathClicked.id
				});
			}
		}
		else if (cityClicked == null)  {
			if (selectedSprite != null) {
				selectedSprite.setSelected(false);
				selectedSprite = null;
			}

			var name = prompt("Enter name of city");

			if (name != null) {
				sendEvent({
					type : "ADD",
					x : x,
					y : y,
					name : name
				});
			}
		}
		else if (selectedSprite == cityClicked) {
			selectedSprite.setSelected(false);
			selectedSprite = null;
		}
		else if (selectedSprite == null) {
			selectedSprite = cityClicked;
			selectedSprite.setSelected(true);
		}
		else if (cityClicked != null) {
			var km = prompt("Enter distance (in km)");

			if (km != null && !isNaN(km)) {
				sendEvent({
					type : "ADD_LINK",
					name1 : selectedSprite.name,
					name2 : cityClicked.name,
					distance : km
				});

				selectedSprite = null;
			}
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
				spriteList.push(new PathSprite(msg[1][i].city1_x, msg[1][i].city1_y, 
											   msg[1][i].city2_x, msg[1][i].city2_y,
											   msg[1][i].distance, msg[1][i].id));					
			}

			for (var i = 0; i < msg[2].length; i++) {
				spriteList.push(new CitySprite(msg[2][i].x, msg[2][i].y, msg[2][i].name));					
			}
		}
		else if (msg[0] == "REMOVED" || msg[0] == "LINK_ADDED") {
			sendEvent({type : "REFRESH"});
		}
	});
}
