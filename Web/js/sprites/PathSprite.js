function PathSprite(x1, y1, x2, y2, distance, id) {
	this.x1 = x1;
	this.y1 = y1;
	this.x2 = x2;
	this.y2 = y2;
	this.id = id;
	this.distance = distance;
	this.inPath = false;

	this.centerX = (parseInt(this.x1) + parseInt(this.x2))/2; 
	this.centerY = (parseInt(this.y1) + parseInt(this.y2))/2;
}

PathSprite.prototype.tick = function () {
	if (!this.inPath) {
		ctx.lineWidth = 3;
		ctx.strokeStyle = '#1f1';	
	}
	else {
		ctx.lineWidth = 5;
		ctx.strokeStyle = '#f0f';		
	}
	
	ctx.beginPath();
	ctx.moveTo(this.x1, this.y1);
	ctx.lineTo(this.x2, this.y2);
    ctx.stroke();

	ctx.lineWidth = 1;

	ctx.strokeStyle = 'black';	
	ctx.fillStyle = '#ddd';	
	ctx.font = "14px Arial";
	ctx.textAlign = "center";
  	ctx.strokeText(this.distance, this.centerX + 1, this.centerY + 11);
  	ctx.fillText(this.distance, this.centerX, this.centerY + 10);
}

PathSprite.prototype.setInPath = function (inPath) {
	this.inPath = inPath;
}

PathSprite.prototype.clicked = function (x, y) {
	var clicked = false;
	var centerRadius = 15;

	if (Math.abs(this.centerX - x) < centerRadius && Math.abs(this.centerY - y) < centerRadius) {
		clicked = true;
	}

	return clicked;
}