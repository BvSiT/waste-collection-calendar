<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "
http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">

<?php 
	require_once("WasteColCalendarContainer.php");
	function clean_input(){};  //Prereq. for WasteColCalendarContainer object
	$container=new WasteColCalendarContainer('calendar');
	define('PATH_RESOURCE_SELECT2',$container->get_waste_calendar_application('select2'));
	define('PATH_RESOURCE_BOOTSTRAP',$container->get_waste_calendar_application('bootstrap'));
	unset($container);
?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<!-- Bootstrap -->
<link rel="stylesheet" href="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/css/bootstrap.min.css">
<script src="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/js/bootstrap.min.js"></script>

<link href="./css/wastecol1.css" rel="stylesheet" type="text/css" />
<style>
.child {
	width: 80%;
  height: 100px;
  padding: 20px;
	position: absolute;
	top: 20%;
}
</style>

</head>
<body>
<div class="container-fluid" >
	<row>
		<div class="col-sm-3 no-float"></div> 
		<div class="col-sm-5 no-float text-center">
			</br></br>
			<p>Veuillez retourner à la page précédente et sélectionnez votre adresse.</p>
			<a href='index.php'>Retour</a>		
		</div> 
		<div class="col-sm-3 no-float"></div> 
	</row>
</div>
</body>
</html>
