function draw_color_dot(idEl,color1,color2,color3){
	var c = document.getElementById(idEl);
	c.style.width ='100%';
	c.style.height='100%';
	//set the internal size to match the parent
	c.width  = c.offsetWidth;
	c.height = c.offsetHeight;
	
	/*
	var style = c.currentStyle || window.getComputedStyle(c);
	alert("Current marginLeft: " + style.marginLeft);
	*/
	
	var ctx = c.getContext("2d");
	var x=c.width/2;
	var y=c.width/2;
	var r=c.width/2;
	slice(ctx,x,y,r,30,120,color1);
	slice(ctx,x,y,r,150,120,color2);
	slice(ctx,x,y,r,270,120,color3);	
}

function rad(degrees){
	return Math.PI/180*degrees;
}

function slice(ctx,x,y,r,startDegr,lengthDegr,color){
	ctx.beginPath();
	ctx.strokeStyle = "transparent";
	ctx.moveTo(x, y);
	theta=rad(startDegr);
	ctx.lineTo(x + r * Math.cos(theta), y + r * Math.sin(theta));
	ctx.moveTo(x, y);
	theta=rad(startDegr+lengthDegr);
	ctx.lineTo(x + r * Math.cos(theta), y + r * Math.sin(theta));
	ctx.moveTo(x, y);
	ctx.arc(x, y, r, rad(startDegr),theta);
	ctx.fillStyle = color;
	ctx.fill();
	ctx.stroke();
}