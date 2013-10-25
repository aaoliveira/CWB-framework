<div style="border:black 5px dotted">
	<h1 style="border:blue 3px solid">INCLUED to {framework}</h1>
	<p style='color:red'>this square dotted was included by {@include="inclued.php"} in the file on parser</p>
	<?php
	if($vdd) {
		echo '<p style="color:blue">Certo é vdd</p>';
	} else {
		echo '<p style="color:red">Errado é vdd</p>';
	}
	?>
</div>