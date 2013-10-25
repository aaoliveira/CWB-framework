<head>
	<title>{title}</title>
	<style>
		.php{
			width:896px;
			height:auto;
			border:2px solid gray;
		}
	</style>
</head>
<body>
	<div style="width:900px;margin:0 auto;background:whitesmoke">

		<center>
			<h3>Welcome to {framework}</h3>
		</center>
		<hr>
		<?php
		if($vdd) {
			echo '<p style="color:blue">Certo é vdd</p>';
		} else {
			echo '<p style="color:red">Errado é vdd</p>';
		}
		?>
		<hr>
		Controller file:
		<div class="php">{controllerFile}</div>
		<br>
		<hr>
		<h4>params</h4>
		<ul>
			<li>sub.object: <b>{sub.object}</b></li>

			<li>filmes: <br>
				<ul>
					{filmes}
					<li>nome: <b>{nome}</b></li>
					<li>categoria: <b>{categoria}</b></li>
					<li>catalago.id: <b>{catalago.id}</b> -- catalogo.nome: <b>{catalago.nome}</b></li>
					<li>
						tags:<br>
						<ul>
							{tags}
							<li>id: <b>{id}</b> = nome: <b>{nome}</b></li>
							{/tags}
						</ul>
					</li>
					<br>
					{/filmes}
				</ul>
			</li>
		</ul>

		<hr>

		<ul>
					{filmes}
					<li>nome: <b>{nome}</b></li>
					<li>categoria: <b>{categoria}</b></li>
					<li>catalago.id: <b>{catalago.id}</b> -- catalogo.nome: <b>{catalago.nome}</b></li>
					<br>
					{/filmes}
				</ul>

		<hr>
		{@include="inclued.php"}
	</div>
</body>