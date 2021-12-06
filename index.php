<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "
http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">

<?php 
	require_once('autoload.php');
	//var_dump($_SERVER['SERVER_NAME']);
	require_once("WasteColCalendarContainer.php");
	function clean_input(){};  //NB: clean_input() must be defined to enable creation of WasteColCalendarContainer object
	$container=new WasteColCalendarContainer();
	require_once $container->get_waste_calendar_application('CalFunctions');
	defined('PATH_JQUERY') or define('PATH_JQUERY',$container->get_waste_calendar_application('jquery'));	
	defined('PATH_RESOURCE_BOOTSTRAP') or define('PATH_RESOURCE_BOOTSTRAP',$container->get_waste_calendar_application('bootstrap'));
	defined('PATH_RESOURCE_SELECT2') or define('PATH_RESOURCE_SELECT2',$container->get_waste_calendar_application('select2'));
	defined('APP_ACCESS_LOG') or define('APP_ACCESS_LOG',$container->get_waste_calendar_application('app_access_log'));
	defined('PATH_CSS') or define( 'PATH_CSS',$container->get_waste_calendar_application('css') );
	defined('PATH_CONFIG_SELECT2') or define( 'PATH_CONFIG_SELECT2',$container->get_waste_calendar_application('config_select2') );	
	
	CalFunctions::w_col_acces_log(APP_ACCESS_LOG);
	unset($container);
?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- jquery -->
<script src="<?php echo PATH_JQUERY ?>"></script> <!-- 3.3.1 -->
<?php // <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script> ?>

<!-- Bootstrap -->
<link rel="stylesheet" href="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/css/bootstrap.min.css">
<script src="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/js/bootstrap.min.js"></script>

<!-- select2 -->
<link rel="stylesheet" href="<?php echo PATH_RESOURCE_SELECT2 ?>/css/select2.min.css">
<?php // <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.css" rel="stylesheet"/> ?>
<script src="<?php echo PATH_CONFIG_SELECT2 ?>"></script>

<script src="<?php echo PATH_RESOURCE_SELECT2 ?>/js/select2.min.js"></script>
<?php // <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.js"></script> ?>

<!-- CSS -->
<link href="<?php echo PATH_CSS ?>" rel="stylesheet" type="text/css" />

<title>Calendrier de collecte Verrieres-le-Buisson</title>
</head>

<body>
<div class="container text-center vertical">
	<h1>Calendrier de collecte</h1>
	<br/>
	<h3>Verrières-le-Buisson</h3>
	</br>
<form method="post" action="moncalendrier.php">
	<input type="hidden" name="waste_col_app" id="waste_col_app" value="waste_col" />
	<div id="street-select">
	<?php echo get_select_street(); ?> 
	</div>
	</br>
	<input type="submit" id="Submit" value="Cliquez pour votre calendrier de collecte" class="btn btn-success btn-primary btn-round" /> 
</form>
</div><!-- end .container -->

<script>
	$( document ).ready(function() {
			config_select2("#street");
	});
</script>
</body>
</html>

<?php
function get_select_street(){
	$container = new WasteColCalendarContainer("selectbox");
	$properties_array = array("selectbox");
	$obj = $container->create_object($properties_array); //instance of class Streets
	$method_array = get_class_methods($obj);
	$last_position = count($method_array) - 1; 
	$method_name = $method_array[$last_position]; //Streets::get_select 
	$result = $obj->$method_name();
	//file_put_contents ('streets.php',$result);  //debug: save full select 
	return $result;
}
?>