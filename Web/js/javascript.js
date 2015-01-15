var ctx = null;

var background = new Image();
background.src = "images/map.png";

var spriteList = [];
var selectedSprite = null;
var mousePos = {x : 0, y : 0};
var showCityNames = true;
var insertMode = true;
var clearPathOnNextClick = false;
var travelDistance = null;

$(function() {
	document.getElementById("canvas").oncontextmenu = function() {return false;};


	$("canvas").mousemove(function (e) {
		var x = e.pageX - $("canvas").offset().left;
		var y = e.pageY - $("canvas").offset().top;
		mousePos.x = x;
		mousePos.y = y;
	});
	
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

		if (clearPathOnNextClick) {
			clearPathOnNextClick = false;
			travelDistance = null;
			clearPath();
		}

		if (e.which == 3) {
			if (selectedSprite != null) {
				selectedSprite.setSelected(false);
				selectedSprite = null;
			}

			if (insertMode) {
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
		}
		else if (cityClicked == null)  {
			if (insertMode) {
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
			if (insertMode) {
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
			else {
				sendEvent({
					type : "CALCULATE_DISTANCE",
					name1 : selectedSprite.name,
					name2 : cityClicked.name
				});
			}
		}
	});

	ctx = document.getElementById("canvas").getContext("2d");
	window.requestAnimationFrame(step);

	sendEvent({ type : "REFRESH" });
});

function reinitialize() {
	if (confirm("Réinitialiser ?")) {
		sendEvent({ type : "REINITIALIZE" });		
	}
}

function hideCityNames(elem) {
	showCityNames = !showCityNames;

	if (showCityNames) {
		elem.innerHTML = "Cacher le nom des villes";
	}
	else {
		elem.innerHTML = "Afficher le nom des villes";	
	}
}

function switchMode(elem) {
	insertMode = !insertMode;

	if (insertMode) {
		elem.innerHTML = "Mode : <strong>insertion</strong>";
	}
	else {
		elem.innerHTML = "Mode : <strong>calcul de trajets</strong>";
	}
}

function step(timestamp) {
	ctx.clearRect(0, 0, 900, 970);

	if (background.complete) {
		ctx.drawImage(background, 0, 0);
	}

	for (var i = 0; i < spriteList.length; i++) {
		spriteList[i].tick();
	}

	if (travelDistance != null) {
		ctx.fillStyle = 'black';	
		ctx.font = "40px Arial";
	  	ctx.fillText(travelDistance + "km", 450, 950/2 + 11);
		ctx.fillStyle = '#fff';	
	  	ctx.fillText(travelDistance + "km", 450, 950/2 + 10);

	}

	if (mousePos.x != 0) {	
		var text = parseInt(mousePos.x) + ", " + parseInt(mousePos.y);
		ctx.strokeStyle = 'black';	
		ctx.fillStyle = '#fff';	
		ctx.font = "10px Arial";
	  	ctx.strokeText(text, 31, 950 + 11);
	  	ctx.fillText(text, 30, 950 + 10);
	}

	window.requestAnimationFrame(step);
}

function clearPath() {
	for (var i = 0; i < spriteList.length; i++) {
		if (spriteList[i].distance != undefined) {
			spriteList[i].setInPath(false);
		}
	}
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
		else if (msg[0] == "REMOVED" || msg[0] == "LINK_ADDED" || msg[0]== "REINITIALIZED") {
			sendEvent({type : "REFRESH"});
		}
		else if (msg[0] == "CALCULATION_DONE") {
			travelDistance = msg[1];
			clearPath();

			for (var i = 0; i < spriteList.length; i++) {
				if (spriteList[i].distance != undefined) {
					if (msg[2].indexOf(spriteList[i].id) != -1) {
						spriteList[i].setInPath(true);
					}
				}
			}

			clearPathOnNextClick = true;
		}
	});
}
