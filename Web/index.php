<!DOCTYPE html>
<html>
<head>
	<title>Graph Map</title>
	<meta charset="utf-8">
	<script src="js/jquery-2.1.3.min.js"></script>
	<script src="js/sprites/PathSprite.js"></script>
	<script src="js/sprites/CitySprite.js"></script>
	<script src="js/javascript.js"></script>
	<link rel="stylesheet" href="css/global.css">
</head>
<body>
	<div class="description">
		<h1>Graph Map</h1>
		<p>En cliquant, vous pouvez ajouter des villes ainsi que des liaisons entre celles-ci. Pour supprimer une liaison ou une ville, utilisez le bouton droit de la souris.</p>
		<div>
			<ul>
				<li>[<a href="javascript:void(0)" onclick="reinitialize()">Réinitialiser</a>] </li>
				<li>[<a href="javascript:void(0)" onclick="hideCityNames(this)">Cacher le nom des villes</a>]</li>
				<li>[<a href="javascript:void(0)" onclick="switchMode(this)">Mode : <strong>insertion</strong></a>]</li>
			</ul>
		</div>
	</div>
	<div class="canvas-container">
		<canvas width="900" height="970" id="canvas"></canvas>
	</div>
</body>
</html>