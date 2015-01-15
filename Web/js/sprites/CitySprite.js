function CitySprite(x, y, name) {
	this.x = x;
	this.y = y;
	this.name = name

	this.radius = 16;
	this.selected = false;
}

CitySprite.prototype.tick = function () {
	ctx.beginPath();
	ctx.arc(this.x, this.y, this.radius, 0, 2 * Math.PI, false);
	ctx.restore();

	if (this.selected) {
		ctx.fillStyle = '#afa';
	}
	else {
		ctx.fillStyle = '#ddf';	
	}
	
	ctx.fill();

	ctx.lineWidth = 1;
	ctx.strokeStyle = 'black';
	ctx.stroke();
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