<?php
//const USER_ERROR_LOG = "../Logs/User_Errors.log";
//const ERROR_LOG = "../Logs/Errors.log";
//NB BvS Unsecure to place logs here??
const USER_ERROR_LOG = "./logs/User_Errors.log";
const ERROR_LOG = "./logs/Errors.log";


define('DEBUG',1); //BvS
function clean_input($value)
{
	$value = htmlentities($value);
	// Removes any html from the string and turns it into &lt; format
	$value = strip_tags($value);
	if (get_magic_quotes_gpc())  //BvS NB always returns FALSE as of PHP 5.4.0.
	{
		$value = stripslashes($value);// Gets rid of unwanted slashes
	}
	$value = htmlentities($value); 	// Removes any html from the string and turns it into &lt; format
	$bad_chars = array( "{", "}", "(", ")", ";", ":", "<", ">", "/", "$" );
	$value = str_ireplace($bad_chars,"",$value);			
	return $value;
}


//echo __FILE__;exit;


//----------------Main Section-------------------------------------
try {

	if ( file_exists("autoload.php"))
	{
		require_once("autoload.php");
	}
	else
	{
		throw new Exception("Waste collection autoload file missing or corrupt");
	}	
	
	if ( file_exists("WasteColCalendarContainer.php"))
	{
		require_once("autoload.php");
		Require_once("WasteColCalendarContainer.php");//BvS
		//NB: clean_input() must be defined to enable creation of WasteColCalendarContainer object
		$container=new WasteColCalendarContainer();
		require_once $container->get_waste_calendar_application('CalFunctions');
		// ------------------- Set constants ------------------
		defined('PATH_JQUERY') or define('PATH_JQUERY',$container->get_waste_calendar_application('jquery'));	
		defined('PATH_RESOURCE_SELECT2') or define('PATH_RESOURCE_SELECT2',$container->get_waste_calendar_application('select2'));
		defined('PATH_RESOURCE_BOOTSTRAP') or define('PATH_RESOURCE_BOOTSTRAP',$container->get_waste_calendar_application('bootstrap'));
		defined('APP_ACCESS_LOG') or define('APP_ACCESS_LOG',$container->get_waste_calendar_application('app_access_log'));
		defined('PATH_CSS') or define( 'PATH_CSS',$container->get_waste_calendar_application('css') );
		defined('PATH_NAV_BAR') or define( 'PATH_NAV_BAR',$container->get_waste_calendar_application('nav_bar') );		
		defined('PATH_COLOR_DOT_JS') or define( 'PATH_COLOR_DOT_JS',$container->get_waste_calendar_application('color_dot_js') );		
		defined('PATH_FONTAWESOME') or define( 'PATH_FONTAWESOME',$container->get_waste_calendar_application('font-awesome') );		
		unset($container);
	}
	else
	{
		throw new Exception("Waste collection container file missing or corrupt");
	}
	
	$location_id=null;
	$version=null;

	
  if ( isset($_GET['id']) ) {  // if calling itself by using Menu > Versions
		if ( isset($_GET['ver']) ) {
			$version=clean_input($_GET['ver']);
			if ( $version=='memo' || $version=='ann' ) {
				$location_id = intval(clean_input($_GET['id']));
			}
		}
	}
	
	if (! $location_id){
		if (isset($_POST['waste_col_app']))		
		{
			if (isset($_POST['street']))
			{
				$location_id = intval(clean_input($_POST['street']));
				//if no javascript -1 will be returned if no choice is made
				//if javascript is enabled select2 will return an empty string in $_POST['street'], so $location_id = 0
				//Force NoChoiceMadeException by setting $location_id = -1
				if ($location_id==0) $location_id=-1; 
			}
		}
		else // select box
		{
			//var_dump($_SERVER);
			//echo  'http://' . $_SERVER['HTTP_HOST'] .dirname($_SERVER['PHP_SELF']);
			//header( 'Location: http://' . $_SERVER['HTTP_HOST'] .dirname($_SERVER['PHP_SELF']) );
			//exit;
			$container = new WasteColCalendarContainer("selectbox");
			$properties_array = array("selectbox");
			$obj = $container->create_object($properties_array); //instance of class Streets
			$method_array = get_class_methods($obj);
			$last_position = count($method_array) - 1; 
			$method_name = $method_array[$last_position]; //Streets::get_select 
			$result = $obj->$method_name();
			print $result;
		}
	}
		
	if ($location_id){
		//echo '98: $location_id='.$location_id;exit;  //debug
		$from_now=false;
		$container = new WasteColCalendarContainer('calendar');
		$cal = $container->create_object(array($location_id,$from_now),'WasteColCalendar'); //create WasteColCalendar object
				
		//var_dump($cal);exit;//debug
		/* If testing for a certain date:
		* $cal = $container->create_object(array($location_id,$from_now,'Y-m-d H:i:s'));
		* e.g. $cal = $container->create_object(array($location_id,$from_now,"2018-05-22 05:00:00"));
		*/
		
		save_user_location_id($location_id); //save for this client to db enabling as default option in select box   
		CalFunctions::w_col_acces_log(APP_ACCESS_LOG,$location_id.':'.$cal->get_full_street());
		
		/* To debug with different date then now:
		* $now="2018-5-1 00:00:00";//debug
		* $cal = $container->create_object(array($location_id,$from_now,$now));
		* Note: The WasteColCalendar object creates also the CalFunctions object
		*/
		
		if ($version=='memo'){
			include 'mycal_tradi.php';  //View traditional calendar 
		} 
		else {    //$version=='ann' or null, so default
			require_once( $container->get_waste_calendar_application('MonthCalendar') ); //class MonthCalendar
			include 'mycal_annual.php'; 
		}		
	}
}

catch (NoChoiceMadeException $e){  //class is defined in WasteColCalendar.php
	ob_start();
	header('Location: '.'retour.php');
	ob_end_flush();
	die();	
}

catch(Exception $e)
{
	echo "The system is currently unavailable. Please try again later."; // displays message to the user
	$date = date('m.d.Y h:i:s'); 
	$eMessage =  $date . " | System Error | " . $e->getMessage() . " | " . $e->getFile() . " | ". $e->getLine() . "\n";
	if (DEBUG) {var_dump($eMessage);}
	error_log($eMessage,3,ERROR_LOG); // writes message to error log file
	/* BvS
	error_log("Date/Time: $date - Serious System Problems with WasteCollection Application. Check error log for details", 1, "noone@helpme.com", "Subject: WasteCollection Application Error \nFrom: System Log <systemlog@helpme.com>" . "\r\n");
	// e-mails personnel to alert them of a system problem
	*/
}