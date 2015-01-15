function CitySprite(x, y, name) {
	this.x = x;
	this.y = y;
	this.name = name;

	this.radius = 10;
	this.selected = false;
}

CitySprite.prototype.tick = function () {
	ctx.beginPath();
	ctx.arc(this.x, this.y, this.radius, 0, 2 * Math.PI, false);
	ctx.restore();

	if (this.selected) {
		ctx.fillStyle = '#4d4';
	}
	else {
		ctx.fillStyle = '#ddf';	
	}
	
	ctx.fill();

	ctx.lineWidth = 1;
	ctx.strokeStyle = 'black';
	ctx.stroke();

	if (showCityNames) {
		ctx.font = "16px Arial";
		ctx.textAlign = "center";
		ctx.fillStyle = 'black';	
	  	ctx.fillText(this.name, this.x + 1, this.y - 19);
		ctx.fillStyle = '#ddd';	
	  	ctx.fillText(this.name, this.x, this.y - 20);
	}
}

CitySprite.prototype.setSelected = function (selected) {
	this.selected = selected;
}

CitySprite.prototype.clicked = function (x, y) {
	var clicked = false;

	if (Math.abs(this.x - x) < this.radius && Math.abs(this.y - y) < this.radius) {
		clicked = true;
	}

	return clicked;
}