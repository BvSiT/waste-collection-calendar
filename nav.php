


<nav class="navbar navbar-inverse">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>                        
			</button>
			<!-- <a class="navbar-brand" href="#">Logo</a> -->
		</div>
		<div class="collapse navbar-collapse" id="myNavbar">
			<ul class="nav navbar-nav">
				<li><a href="./index.php">Acceuil</a></li>
				<?php
				/*
				<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Versions<span class="caret"></span></a>
					<ul class="dropdown-menu">
						<?php
							$link=htmlentities($_SERVER['PHP_SELF']).'?id='.$cal->get_location_id();
							echo '<li><a href="'.$link.'&ver=memo">Mémo traditionnel</a></li>';
							echo '<li><a href="'.$link.'&ver=ann">Calendrier annuel</a></li>';
						?>
					</ul>
				</li>
				*/
				?>
				<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">Télécharger<span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li>
							<a href="http://www.verrieres-le-buisson.fr/IMG/pdf/calendrier_verrieres_2018_bdef-2.pdf" >Calendrier de collecte 2018</a>						
						</li>
						<li>
							<a href="http://www.verrieres-le-buisson.fr/IMG/pdf/collecte_changements-2.pdf" >Changements au 1er avril 2018</a>						
						</li>						
					</ul>
				</li>		

				<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">A propos<span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li>
							<a href="http://www.bvsit.nl/code_samples/code_sample7.html" >Informations techniques</a>						
						</li>
					</ul>
				</li>				
				
<!--				<li><a href="http://www.bvsit.nl">A propos</a></li> -->
				<li><a href="http://www.bvsit.nl/contact1.html">Contact</a></li>				
			</ul>
			<!-- 
			<ul class="nav navbar-nav navbar-right">
				<li><a href="#"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
			</ul>
			-->
		</div>
	</div>
</nav> <!-- navbar navbar-inverse -->