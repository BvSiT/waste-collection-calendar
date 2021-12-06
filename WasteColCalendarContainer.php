<?php

/* BvS See Steve Prettyman-Learn PHP 7_ Object Oriented Modular Programming using HTML5, CSS3,
*  JavaScript, XML, JSON, and MySQL-Apress (2016).pdf
*  p. 136: ?_interface creates and uses the ?_container object to contain, create,
*  and pass any other objects needed (without knowing the name of the objects). The ?_container object
*  uses ?_applications.xml to discover the location and name of files containing the
*  classes it will create [BvS thus providing a primitive form of dependency injection]. 
*  28-2-2019: BvS: this design pattern has some serious flaws. One cannot be sure that the last loaded class 
*  is the correct one in case of several classes in one file. Apparently in PHP 5 and 7 get_declared_classes()
*  gives different results in the order in case of several classes in one file. See WasteColCalendar.php. 
*  Here WasteColCalendar will NOT be loaded after class NoChoiceMadeException in case of PHP 7. 
*  ::create_object will try to return an instance of  NoChoiceMadeException instead of an instance of
*  WasteColCalendar which will generate this error:
*  Fatal error: Uncaught Error: Wrong parameters for NoChoiceMadeException([string $message [, long $code [, Throwable $previous = NULL]]]) in C:\wamp\www\calendrierdecollecte\WasteColCalendarContainer.php:70 Stack trace: #0 C:\wamp\www\calendrierdecollecte\WasteColCalendarContainer.php(70): Exception->__construct(Array) #1 C:\wamp\www\calendrierdecollecte\moncalendrier.php(102): WasteColCalendarContainer->create_object(Array) #2 {main} thrown in C:\wamp\www\calendrierdecollecte\WasteColCalendarContainer.php on line 70
*  Fix: Added to ::create_object an optional parameter to name explicitly the class to be instantiated. 
*/

class WasteColCalendarContainer
{
	private $app; //BvS ID of dependency in ?_applications.xml 

	function __construct($value=null)
	{
		if (function_exists('clean_input')) { //see ?_interface.php
			$this->app = $value;
		}
		else {
			$errorMsg = 'Function does not exist on line '.__LINE__.' in '.__FILE__;
			throw new Exception($errorMsg);
		}
	}

	public function set_app($value){
		$this->app = $value;
	}

	public function get_waste_calendar_application($search_value)
	{
		// BvS. Returns file location of file needed for class creation
		$xmlDoc = new DOMDocument(); 
		if ( file_exists("waste_col_applications.xml") )
		{
			$xmlDoc->load( 'waste_col_applications.xml' ); 
			$searchNode = $xmlDoc->getElementsByTagName( "type" ); 
			foreach( $searchNode as $searchNode ) 
			{ 
				$valueID = $searchNode->getAttribute('ID'); 

				if($valueID == $search_value)
				{
					$xmlLocation = $searchNode->getElementsByTagName( "location" ); 
					return $xmlLocation->item(0)->nodeValue;
					break;
				}
			}
		}
		throw new Exception("waste_col applications xml file missing or corrupt");
		//debug//throw new Exception("waste_col applications xml file missing or corrupt: ".$search_value);
		//  return FALSE;
	}

	function create_object($properties_array=null,$class_name=null)
	{
		$loc = $this->get_waste_calendar_application($this->app);
		if(($loc == FALSE) || (!file_exists($loc)))
		{
			throw new Exception("File $loc missing or corrupt.");
			//return FALSE;
		}
		else
		{
			require_once($loc); //if $loc contains a class it will be loaded in memory
			if ($class_name === null) //if class name is not explicit
			{
				$class_array = get_declared_classes();
				//echo __CLASS__.'::'.__METHOD__.':'.__LINE__;dump_classes(3);exit;
				$last_position = count($class_array) - 1;
				$class_name = $class_array[$last_position];	//last loaded class				
			}
			//echo $class_name;exit;
			$waste_calendar_object = new $class_name($properties_array);
			return $waste_calendar_object;
		}
	}
}

?>