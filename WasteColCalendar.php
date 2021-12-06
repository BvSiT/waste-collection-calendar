<?php
require_once 'functions_wastecalendar.php';

/* BvS NB If we would insert here a class by using require_once() this class will be loaded
* AFTER the classes defined here (??). The class factory WasteColCalendarContainer::create_object() 
* expects the last class WasteColCalendar defined here to be loaded.
*/

class NoChoiceMadeException extends Exception {
  public function errorMessage() {
    $errorMsg = 'Illegal id (-1) on line '.$this->getLine().' in '.$this->getFile();
    return $errorMsg;
  }
}

class WasteColCalendar{
	private $verbose=false;
	private $location_id=0;
	private $name_street;
	private $type_street;
	private $house_nos;
	private $mysqli;
	private $dt_create; //DateTime creation object 
	private $predict_year; //If set to string year e.g. '2018' $this->col_dates are not retrieved from db but calculated in $this->predict_col_dates()
	private $from_now; //default false. Get only col dates beginning with $this->dt_create
	private $waste_types;
	/* Structure of $waste_types: 
	* 'DGB' => array(
    *     'waste_type_name' =>'Bac vert foncé',
		*			'waste_type_info'=>'Ordures ménagères',
    *     'sector_name' => null,
    *     'sector_PM' => null,
    *     'sector_weekday' => null,
		*			'PM'=>1 or 0)  PM for all waste types, also for waste types with week pattern
		*
	*/

	private $week_patterns;
	private $col_dates;
	private $col_times=array('AM'=>array('start'=>'6:00','end'=>'12:00'),
							 'PM'=>array('start'=>'16:30','end'=>'18:00')
							);
							
	private $excluded_col_dates=array("2018-05-01");//No collection on Labour day

	//Added $properties_array to comply to the Prettyman container model	
	function __construct($properties_array){	
		/*
		*	$properties_array = array(
		*		$location_id,  //int  If string then try to find $location_id based on name street
		*		$from_now, //default false or (true = add only col_dates from now)
		*  	$now	// string date with format 'Y-m-d H:i:s' //default current date as string. Parameter will only be set for testing
		* )
		*/
		
		
		//enable the next line to make class construction dependent on the container:
		//if (!(method_exists('WasteColCalendarContainer', 'create_object'))){exit;}	
		
		//load CalFunctions  in memory
		$container = new WasteColCalendarContainer();
		require_once $container->get_waste_calendar_application('CalFunctions');

		$id=$properties_array[0]; 
		if ($this->verbose) { deb_pr("w->id",$id);exit;}
		if (intval($id)<1){
			throw new NoChoiceMadeException();				
		}
		
		//NB isset() will return false even if element exists but is null
		$this->from_now= (isset($properties_array[1])?$properties_array[1]:false);
		
		$now=(isset($properties_array[2])?$properties_array[2]:date('Y-m-d H:i:s')); //if parameter is not set then current date as string

		$this->mysqli=connect_mydb();
		if (!$this->mysqli){
			throw new Exception('Connection to database failed');
		}
		
		$this->dt_create=CalFunctions::validatedDateTime($now); //DateTime creation of this object
		
		/*DEBUG*************************************/
		/*
		$this->excluded_col_dates[]='2018-03-09';  //YEB
		$this->excluded_col_dates[]='2018-03-20';  //DGB
		$this->excluded_col_dates[]='2018-04-24';  //DGB + BUL
		$this->dt_create=DateTime::CreateFromFormat('Y-m-d H:i:s','2018-04-09 13:00:00' );
		*/
		/*END DEBUG**********************************/
		
		if ($this->dt_create->Format("Y")<>$this->max_year_col_dates($this->mysqli)){
			/* If year of col_dates in the db is not equal to the year of creation date (now)
			*   do not use col_dates from the db but calculate them.
			*/
			$this->predict_year=$this->dt_create->Format("Y");  //contains current year e.g. '2018'
		}

		$id= stripslashes($id);
		$id = mysqli_real_escape_string($this->mysqli,$id);	
		if (!is_numeric($id)) {  //try to find on street name (for debugging)
			$id=$this->id_from_name_street($id,$this->mysqli);
		}
		else {
			if ($this->validate_int($id)){
				$id= (int) $id;
			}
			else
			{
				$err_msg='Error: not integer in line '.__LINE__.' '.__FILE__.'<BR>\n';
				throw new Exception($err_msg);							
			}
		}
			
		$this->load_location_data($id,$this->mysqli); //$this->location_id is set by method load_location_data
		if ($this->location_id){
			$this->load_week_pattern_data($this->location_id,$this->mysqli);
			$this->load_waste_type_data($this->location_id,$this->mysqli); //contains also sector data
			/*
			*	::load_waste_type_data gets data from the DB by using view v_waste_types.
			*	Since 2018 sector_id is added to table location__waste_type also for waste types with week pattern (DGB,YEB)
			*	This enables adding sector info to the events_panel for all waste types except (BUL)
			*	For waste types with a week pattern remove unnecessary info to be able to distinguish from sector waste types
			*	See also modification in WasteColCalendar.php:596
			*/
			foreach($this->week_patterns as $waste_type_code=>$properties){
				$this->waste_types[$waste_type_code]['sector_PM']=null;
				$this->waste_types[$waste_type_code]['sector_weekday']=null;
			}			
			
			$this->add_PM_waste_types(); // Add to $this->waste_types a key ['PM'] for all waste types
			if ($this->predict_year) {
				$this->predict_col_dates($this->predict_year,$this->from_now); //e.g. $predict_year = '2018'
				$this->del_excl_dates();
			}
			else {
				$this->load_col_date_data($this->location_id,$this->mysqli,$this->from_now);
			}
		}
		
		//var_dump($this->week_patterns);var_dump($this->col_dates);  //debug
	}
	
	public function get_location_id(){
		return $this->location_id;
	}

	public function get_full_street() {
		
		/* Add a space if street does end with a alphanumeric char
		* If it ends with i.e. "’" do not add space
		* Note: The following will give an error Invalid UTF character:
		* preg_match("/’$/u",$this->type_street)
		* No error if echoed within a page with:
		* <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
		* <head>
		* <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		*/
		$street=$this->type_street.(preg_match("/[a-z]$/i",$this->type_street)?' ':'');
		$street.=$this->name_street.(isset($this->house_nos)?' '.$this->house_nos:'');
		return $street;
	}	
	
	public function get_create_date(){
		return $this->dt_create->Format('Y-m-d H:i:s');
	}
	
	public function get_waste_types($waste_type_code=null){
		if (!$waste_type_code){
			return $this->waste_types;
		}
		else {
			if (array_key_exists($waste_type_code, $this->waste_types)){
				return $this->waste_types[$waste_type_code];
			}
		}
	}	
	
	public function get_sector($waste_type_code){
		if (array_key_exists($waste_type_code,$this->waste_types)){
			return $this->waste_types[$waste_type_code]['sector_name'];			
		}
	}
	
	public function get_col_dates($all_waste_types=true){
		/* return $this->col_dates
		* When instantiating this class $this->col_dates will default only
		* contain col dates for waste types that are organized by sector 
		* If arg $all_waste_types=true add also col dates for the full year
		* for waste types which have a week pattern.
		*/
		
		if ($all_waste_types){
			$year=intval($this->dt_create->Format('Y'));
			if (!$this->from_now){
				$date_start = ($year=='2018')?'2018-04-01':$year.'-01-01';
				//$date_start=$year.'-01-01';
			}
			else {
				$date_start= $this->dt_create->Format('Y-m-d');
			}
			$date_end=($year+1).'-01-01';		
			
			foreach($this->week_patterns as $waste_type=>$pattern){
				$sort_dates=false;$dates_added=false;
				foreach($pattern as $day_name=>$val){
					if ($day_name=='PM'){break;}
					if ($val){
						//echo $day_name.$waste_type.$val._B; //debug											
						$weekly=1;
						$dates[$waste_type]=CalFunctions::weekday_pattern($day_name,$weekly,$date_start,$date_end);
						if ( isset($this->col_dates[$waste_type]) ){  //if dates are already present in ::col_dates for this waste type 
							$this->col_dates[$waste_type]=array_merge($this->col_dates[$waste_type],$dates[$waste_type]);
							$sort_dates=true; //Y-m-d date strings are added to the end, array will have to be sorted
						}
						else{
							$this->col_dates[$waste_type]=$dates[$waste_type];
						}
					}
					$dates_added=true;
				}
				if ($dates_added){ $this->del_excl_dates($waste_type); }
				if ($sort_dates) { sort($this->col_dates[$waste_type]); }
			}
		}
		return $this->col_dates;
	}
	
	public function get_excluded_col_dates(){
		return $this->excluded_col_dates?$this->excluded_col_dates:null;
	}

	private function max_year_col_dates($mysqli){
		/* return latest year of col_dates in the db */
		$query="SELECT MAX(YEAR(col_date)) AS max_year FROM v_col_dates_smaller";
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if (!$result){
			$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);
		}
		if ($result->num_rows ==1) {
			return $result->fetch_object()->max_year;
		}
	}
	
	private function validate_int($val) {
		if (is_numeric($val)) {
			if ((int) $val==$val) {return true;}
		}
		return false;
	}
	
	private function id_from_name_street($name_street,$mysqli){
		//use prepared statement to query
		$query="SELECT id FROM location WHERE name_street=?";
		if (!($stmt = $mysqli->prepare($query))) {
			$err_msg="Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);			
		}
		if (!($stmt->bind_param("s", $name_street))){
			$err_msg = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);						
		}
		if (!$stmt->execute()){
			$err_msg = "Execute failed: (" . $stmt->errno . ") " . $stmt->error."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);									
		}
		$result=$stmt->get_result();		

		if ($result->num_rows >0) {
			/* NB fetch_object moves pointer forward, so next call would throw error: trying to get property of non-object */
			return $result->fetch_object()->id;  
		}
		else {
			$err_msg = "no rows found</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);									
		}
	}

	private function load_location_data($location_id,$mysqli){
		$query="SELECT id,name_street,type_street,house_nos FROM location WHERE id=".$location_id;
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if (!$result){
			$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);
		}
		if ($result->num_rows ==1) {
			while($row = $result->fetch_assoc()) {
				$this->location_id=$row["id"];
				$this->name_street=$row["name_street"];
				$this->type_street=$row["type_street"];
				$this->house_nos=$row["house_nos"];
			}
		}
		else
		{
			$err_msg="Error description: No unique record found. num_of_rows = ". $result->num_rows. "</BR>\n";
			$err_msg.='Query: '.$query."</BR>\n";
			throw new Exception($err_msg);
		}	
	}
	
	private function load_waste_type_data($location_id,$mysqli){
		/* v_waste_types contains also fields 'sector_name','sector_PM','sector_weekday' */
		$query="SELECT * FROM v_waste_types WHERE location_id=".$location_id;
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if (!$result){
			$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);
		}
		if ($result->num_rows >0) {
			while($row = $result->fetch_assoc()) { //TODO more efficient?
				unset($row['location_id']); //TODO skip this in view?
				$waste_type_code=$row['waste_type_code'];
				unset($row['waste_type_code']);
				$this->waste_types[$waste_type_code]=$row;
			}
		}
		else
		{
			$err_msg="Error description: No records found. num_of_rows = ". $result->num_rows. "</BR>\n";
			$err_msg.='Query: '.$query."</BR>\n";
			throw new Exception($err_msg);
		}			
	}	
	
	private function load_week_pattern_data($location_id,$mysqli){
		$query="SELECT * FROM v_week_patterns_small WHERE location_id=".$location_id;
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if (!$result){
			$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);
		}
		if ($result->num_rows >0) {
			while($row = $result->fetch_assoc()) { //TODO more efficient?
				unset($row['id']); 
				unset($row['location_id']); //TODO skip this in view?
				$waste_type_code=$row['waste_type_code'];
				unset($row['waste_type_code']);
				unset($row['waste_type_name']);				
				$this->week_patterns[$waste_type_code]=$row;
			}
		}
		else
		{
			$err_msg="Error description: No records found. num_of_rows = ". $result->num_rows. "</BR>\n";
			$err_msg.='Query: '.$query."</BR>\n";
			throw new Exception($err_msg);
		}			
	}
	
	private function add_PM_waste_types(){
		/* Add key ['PM'] for all waste types to $this->waste_types
		*	Note: table waste_types in DB contains only field 'sector_PM'
		* Key ['PM'] will also contain info for waste types based on week pattern
		*/
		
		if ( !(isset($this->waste_types) && isset($this->week_patterns)) ) {
			$err_msg='Members waste_types and week_patterns should be set'."<BR>\n";
			throw new Exception($err_msg);						
		}
		foreach($this->waste_types as $waste_type=>$properties){
			if ( isset($this->week_patterns[$waste_type]) ) {
				$this->waste_types[$waste_type]['PM']=$this->week_patterns[$waste_type]['PM'];				
			} 
			else {
				$this->waste_types[$waste_type]['PM']=$this->waste_types[$waste_type]['sector_PM'];				
			}
		}		
	}	
	
	private function predict_col_dates($year_to_predict,$from_now=false) {

		$start = $year_to_predict=='2018'?'2018-04-01':$year_to_predict.'-01-01';
		
		if ($from_now){
			$start=$year_to_predict.$this->dt_create->Format('-m-d');
		}
		$end=(intval($year_to_predict)+1).'-01-01';
		
		$weekday=$this->waste_types['GRB']['sector_weekday'];
		/* In 2017 the pattern was for sector A WEEK1AND3, for B and C WEEK2AND4 from which C evening
		* Apparently one has changed this in 2018 to: sector A WEEK2AND4, for B and C WEEK1AND3
		* See http://www.verrieres-le-buisson.fr/spip.php?article164
		*/
		switch ($this->waste_types['GRB']['sector_name']) {
			case 'A':
				//$flag_pattern = 2;  // In 2017: WEEK1AND3
				$flag_pattern = 7;  // UNEVEN_WEEKS
				break;
			case 'B':  //In 2017: WEEK2AND4
			case 'C':
			case 'D':
				//$flag_pattern = 3; //In 2017: WEEK2AND4
				$flag_pattern = 6; //EVEN_WEEKS
				break;
		}
		
		//var_dump($flag_pattern);
		//var_dump($this->waste_types['GRB']); //debug 23-1
		//var_dump(CalFunctions::weekday_pattern($weekday,$flag_pattern,$start,$end));  //debug 23-1
		$this->col_dates['GRB']=CalFunctions::weekday_pattern($weekday,$flag_pattern,$start,$end);
		
		$weekday=$this->waste_types['BUL']['sector_weekday'];
		$flag_pattern=4; //LAST_IN_MONTH;
		$this->col_dates['BUL']=CalFunctions::weekday_pattern($weekday,$flag_pattern,$start,$end);
		
		$weekday=$this->waste_types['SAC']['sector_weekday'];
		$flag_pattern=5; //SPECIAL_SACS
		$this->col_dates['SAC']=CalFunctions::weekday_pattern($weekday,$flag_pattern,$start,$end);		
	}

	
	private function load_col_date_data($location_id,$mysqli,$from_now=false){
		/* v_waste_types contains also field sector_name */
		$query='SELECT * FROM v_col_dates_smaller WHERE location_id='.$location_id;
		if ($from_now){
			$dt_now= clone $this->dt_create;
			$dt_now->setTime(0,0);
			//$query.=' AND col_date >= NOW()';
			$query.=' AND col_date >= "'.$dt_now->Format("Y-m-d").'"';
		}
		$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
		if (!$result){
			$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
			$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
			throw new Exception($err_msg);
		}
		if ($result->num_rows >0) {
			while($row = $result->fetch_assoc()) { //TODO more efficient?
				unset($row['location_id']); //TODO skip this in view?
				$waste_type_code=$row['waste_type_code'];
				unset($row['waste_type_code']);
				/* NB In VIEW v_col_dates col_date is of type DATETIME but mysqli->fetch_assoc()
				* will return the field as a string 'YYYY-MM-DD 00:00:00' */
				$this->col_dates[$waste_type_code][]=$row['col_date'];
			}
		}
		else
		{
			$err_msg="Error description: No records found. num_of_rows = ". $result->num_rows. "</BR>\n";
			$err_msg.='Query: '.$query."</BR>\n";
			throw new Exception($err_msg);
		}			
	}	
		
	public function format_weekpattern_FR($waste_type_code,&$PM){
		/* $this->week_patterns consists of an array with as keys 
		* the field names of table 'week_pattern' i.e. 'mon','tue','wed', etc.
		*/
		$week_pattern=$this->week_patterns[$waste_type_code];
		foreach($week_pattern as $key=>$val){
			if (!$val) {unset($week_pattern[$key]);}
		}
		array_key_exists('PM',$week_pattern)?$PM='soir':$PM='matin';
		unset($week_pattern['PM']);
		if (count($week_pattern)==6){
			$week_pattern_FR='tous les '.$PM.'s (sauf les dimanches)';
			$PM='';
		}
		else {
			foreach($week_pattern as $key=>$val){
				if (setlocale (LC_TIME, 'fr_FR.utf8','fra')) { 
					$week_pattern_FR[]= strftime("%A",strtotime($key));
				}
				else
				{
					/* $key = 'mon','tue',etc */
					$date = strtotime($key); //date as long int from short weekday (!)
					$date=strftime("%Y-%m-%d",$date); //date as string 'YYYY-MM-DD'
					$week_pattern_FR[]=CalFunctions::day_of_week_from_date($date);
				}
			}
			$last=end($week_pattern_FR);			
			$week_pattern_FR=implode(', ',$week_pattern_FR);
			if (max($week_pattern)) {
				$week_pattern_FR=str_replace(', '.$last,' et '.$last,$week_pattern_FR);
			}
		}		
		return $week_pattern_FR;
	}
	
	public function format_col_dates($waste_type_code,$locale='fr_FR',$days_bold=true){
		/* Method load_col_date_data() of this class WasteColCalendar will get the
		* waste collection dates for this waste type from the database and store them
		* in the property $col_dates in the format:
		*	array('GRB' => array('2017-01-05 00:00:00',..,'2017-12-21 00:00:00')
		*	 	  'SAC' => array ('2017-04-25 00:00:00',...)
		*		   etc.)
		* format_col_dates() formats the dates ready to echo to a webpage
		* I.e. '1 et 10 janv. - 5, 15, 25 févr.' etc.
		*
		* To represent dates in other languages then French:
		* 	$locale='en-EN','nl_NL','de-DE', 'en-US',etc.
		*   Set also $str_and to the proper language; 
		*/

		$locales=CalFunctions::get_locales();
		$locales['fr_FR'][]='et';
		$locales['en_US'][]='and';
		$locales['nl_NL'][]='en';
		$locales['de_DE'][]='und';
		
		$now=date('Y-m-d H:m:s');
		
		if (array_key_exists($locale,$locales)){
			setlocale (LC_TIME, $locales[$locale][0],$locales[$locale][1]); //see strftime()			
			$str_and=$locales[$locale][2];
		}
		else {
			echo 'error in locale'; //TODO error handling
		}

		if (array_key_exists($waste_type_code,$this->col_dates)) {
			$col_dates=$this->col_dates[$waste_type_code];
		}
		else
		{
			$s='Aucun date à venir pour 2017';
			$s= mb_convert_encoding($s, 'UTF-8','ISO-8859-15');
			//$s= "<span style='color:grey'>".$s."</span>";
			$s= "<i>".$s."</i>";
			return $s;
		}
		
		
		foreach ($col_dates as $date){
			/* $date is string with format "YYYY-MM-DD HH:MM:SS.000000" 
			*  Use explode() to trim the time */
			$date=explode(" ",$date)[0];
			/* Or:  without explode() : $date=DateTime::CreateFromFormat('!Y-m-j+',$date); 
			*  The + prevents error for trailing data, only warning in DateTime::getLastErrors() */				
			$date=DateTime::CreateFromFormat('!Y-m-j',$date);
			$month_num=(int)$date->format('m');
			$day_num=(int)$date->format('d');
			$months[$month_num][]= ($days_bold?'<strong>':'').$day_num.($days_bold?'</strong>':'');
		}
		foreach($months as $month_num=>$day_nums){
			$days=implode(', ',$day_nums); //string of day numbers separated by comma's
			$last=end($day_nums); 
			if (max($day_nums)){ //replace last comma by i.e. 'et' in French,
				$days=str_replace(', '.$last,' '.$str_and.' '.$last,$days);
			}
			//add abbr. month name in the correct language
			$str_dates[] = $days.' '.CalFunctions::get_month($month_num);
		}
		$str_dates=implode(' - ',$str_dates); // separate months in string by ' - '
		
		/* strftime("%b",) will return diacritical as multibyte characters.
		* French diacritical chars are not shown correctly without  'iso-8859-15'??	
		* To display correctly convert encoding
		* Update 29-11-17 This is now taken care of in get_month()
		* $str_dates=mb_convert_encoding($str_dates, 'UTF-8','ISO-8859-15'); 
		*/
		return $str_dates;
	}	
	
	private function format_day_month($date){
		/* Format a waste collection date first as '[day_num] - [month_num]'.
		   Then format this string dependant on if date < or > today:  
		   - if < today: add <strike> code
		   - if > today: add <bold> code to [day_num]
		   - if date=today then:
				- if current time < col. start time:  add <bold> code to [day_num] and blink
				- if current time >= col. start time and <= end time:  make grey to express uncertainty
				- if current time > end time: ignore the whole date
		*/
		
	}
	
	public function format_sector_weekday($waste_type_code,&$PM,$locale='fr_FR'){
		/* Return full name of the weekday of the waste collection for a sector
		* Also set $PM.
		* To represent dates in other languages then French:
		* $locale='en-EN','nl_NL','de-DE', 'en-US',etc.
		*/
		$this->waste_types[$waste_type_code]['sector_PM']?$PM='soir':$PM='matin';
		$weekday=$this->waste_types[$waste_type_code]['sector_weekday'];  //'mon','tue',etc.
		return CalFunctions::day_of_week_locale_from_EN($weekday,$locale);
	}
	
	public function first_col_dates($from=null){
	/* Return an assoc. array with the first coming col dates for each waste type.
	* Returned array $first_col_dates will have a structure like:
	* array('DGB'=>array('date'=>'2017-01-05 16:30:00','started'=>false))
	* 'date' is saved with start time for the particular waste type
	* The key [started] is true if the waste collection is taking place at the moment.
	*
	* Parameter should be a string with format "Y-m-d H:i:s"
	* Default value for $from is current date and time
	* See also https://stackoverflow.com/questions/13194322/php-regex-to-check-date-is-in-yyyy-mm-dd-format
	*/
		if (!isset($from)) {$from=date('Y-m-d H:i:s');}; //current date and time
		$dt_from = DateTime::createFromFormat("Y-m-d H:i:s", $from);
		if ($dt_from == false || array_sum($dt_from->getLastErrors())) {
			$err_msg='Error: illegal date format in line '.__LINE__.' '.__FILE__.'<BR>\n';
			throw new Exception($err_msg);	
		}
		$first_col_dates=null;  //array to return
		
		foreach($this->waste_types as $key_waste_type=>$properties) {  // $key_waste_type = e.g. "GRB","YEB", etc.
			/*29-3-18 Since we now read in also for waste types DGB and SAC a sector_id the test on
			* $properties['sector_name'] will not work. To distinguish between waste type with or without week pattern
			* now we test on $this->week_patterns[$key_waste_type]
			*/
			//if ($properties['sector_name']){  //if collection for this waste type is organised by sector
			if (!isset($this->week_patterns[$key_waste_type])) { //if collection for this waste type is organised by sector thus has no week_pattern
				/*  If $properties['sector_PM'] is true then waste collection time PM else AM */
				$col_times= ($properties['sector_PM']?$this->col_times['PM']:$this->col_times['AM']); 
				/* $col_times exists of array e.g. in case of PM e.g. array('start'=>'16:30','end'=>'18:00') */
				$start_time_col = explode(':',$col_times['start']); //put hours and minutes separately in an array
				$end_time_col = explode(':',$col_times['end']);

				foreach($this->col_dates[$key_waste_type] as $col_date){
					$dt_col_date = new DateTime($col_date);
					if ($this->excluded_col_date($dt_col_date)){continue;}					
					/* add col. end time to waste col date*/
					$dt_col_date->setTime($end_time_col[0],$end_time_col[1]);
					if ($dt_from < $dt_col_date ) {  //assume $this->col_dates are ordered by date
						/* We are now sure we have a col. date that is after the key date $dt_from
						 * Also we know that the time of the key date is at least earlier than the end time of the col. */
						//echo '$dt_col_date:'._B; var_dump($dt_col_date);echo '$dt_from:'._B;var_dump($dt_from);exit;
						
						
						$dt_col_date->setTime($start_time_col[0],$start_time_col[1]);//Always save with start time collection
						$first_col_dates[$key_waste_type]['date']=$dt_col_date->Format("Y-m-d H:i:s");;
						$first_col_dates[$key_waste_type]['started']=false; //flag waste col. has already started							
						
						if ($dt_col_date->Format("Y-m-d") == $dt_from->Format("Y-m-d")){ 
							/* Both on the same day and also key date/time $dt_from is earlier than end time col.
							* If key time also after start col, col could have already passed by the address. 
							* If so set flag */
							$dt_col_date->setTime($start_time_col[0],$start_time_col[1]);
							if ($dt_from >= $dt_col_date ) {
								$first_col_dates[$key_waste_type]['started']=true; //flag waste col. has already started
							}
						}
						continue 2;//next waste type
					}
				}
			}
			else { //if collection for this waste type is organized weekly by week pattern
				$PM= $this->week_patterns[$key_waste_type]['PM']; //0 or 1
				$col_times= ($PM?$this->col_times['PM']:$this->col_times['AM']); 
				/* $col_times exists of array e.g. in case of PM e.g. array('start'=>'16:30','end'=>'18:00') */
				$start_time_col = explode(':',$col_times['start']); //put hours and minutes separately in an array
				$end_time_col = explode(':',$col_times['end']);
				$dt_col_date = clone $dt_from;
				//echo $key_waste_type._B;var_dump($this->week_patterns[$key_waste_type]);  //debug
				$weekday=strtolower($dt_col_date->Format('D')); 
				//echo $weekday.$this->week_patterns[$key_waste_type][$weekday]._B;  //debug
				if ($this->week_patterns[$key_waste_type][$weekday]) {
					/* A waste col. takes place for this waste type on the weekday of the key date $dt_from 
					* Note: $this->week_patterns[$key_waste_type][$weekday] is 0 or 1
					*/
					$dt_col_date->setTime($end_time_col[0],$end_time_col[1]); // add col. end time to waste col date
					//echo '$dt_col_date:'._B; var_dump($dt_col_date);echo '$dt_from:'._B;var_dump($dt_from);exit; //debug
					if ($dt_from < $dt_col_date ) {  
						/* We have a col. date that is on the same date as the key date $dt_from and
						 * the time of the key date is at least earlier than the end time of the col. */
						//echo '$dt_col_date:'._B; var_dump($dt_col_date);echo '$dt_from:'._B;var_dump($dt_from);exit;  //debug
						//$dt_col_date->setTime(0,0);

						
						//Exclude e.g. 1st of May labour day
						if (!$this->excluded_col_date($dt_col_date)) {
							$dt_col_date->setTime($start_time_col[0],$start_time_col[1]);//Always save with start time collection						
							$first_col_dates[$key_waste_type]['date']=$dt_col_date->Format("Y-m-d H:i:s");
							$first_col_dates[$key_waste_type]['started']=false; //flag waste col. has already started							
							/* If key date time also after start col, col could have already passed by the address. If so set flag */
							//$dt_col_date->setTime($start_time_col[0],$start_time_col[1]);
							if ($dt_from >= $dt_col_date ) {
								$first_col_dates[$key_waste_type]['started']=true; //flag waste col. has already started is set
							}
							continue 1; //next waste type
						}
					}	
				}
				/* Continue search in week pattern for weekday with waste col. */
				do {
					do {$dt_col_date->modify('+1 day');}while ($this->excluded_col_date($dt_col_date));
					$weekday=strtolower($dt_col_date->Format('D')); 					
				} while (!$this->week_patterns[$key_waste_type][$weekday]);
				//$dt_col_date->setTime(0,0);
				$dt_col_date->setTime($start_time_col[0],$start_time_col[1]);//Always save with start time collection										
				$first_col_dates[$key_waste_type]['date']=$dt_col_date->Format("Y-m-d H:i:s");
				$first_col_dates[$key_waste_type]['started']=false; //flag waste col. has already started not set															
			}
			//echo $key_waste_type._B;
			//var_dump($col_times);
		}

		/* Sort on date. See http://php.net/manual/en/function.array-multisort.php */
		foreach($first_col_dates as $waste_type=>$data) {
			$date[$waste_type]= $data['date']; // ass. array with as key $waste_type and as value only date
		}
		array_multisort($date, SORT_ASC, $first_col_dates);
		
		return $first_col_dates; 
	}	
	
	public function format_coming_col_events($from=null){
		/* 
		* Parameter should be a string with format "Y-m-d H:i:s"		
		* $first_col_dates structure like:
		* array('DGB'=>array('date'=>'2017-01-05','started'=>false))
		* The key [started] is true if the waste collection is taking place at the moment.
		* 
		* Return: an assoc. array with structure like:
		* array('DGB'=>array('interv'=>'Ce soir',
		*							 array('full_date'=>'Lundi matin 22 janv.')
		*							 array('waste_type_name')=>'Bac vert foncé'),
		*							 'waste_type_info'=>'Ordures ménagères',
		*       'YEB'=>...etc.
		* );
		*
		*/
		$events=null;
		if (!isset($from)) {$from=date('Y-m-d H:i:s');}; //current date and time
		$first_col_dates=$this->first_col_dates($from);
		$dt_from=DateTime::CreateFromFormat('Y-m-d H:i:s',$from);
		foreach($first_col_dates as $waste_type=>$data){
			$dt=DateTime::CreateFromFormat('Y-m-d H:i:s',$data["date"]);
			
			$PM=($dt->format('A')=='AM'?'matin':'soir');
			$day_num=$dt->format('j'); //Day of the month without leading zeros
			$month_FR=CalFunctions::get_month((int)$dt->format('m'),true);//Abbr. month French			
			$started=$data["started"];
			$weekday=ucfirst(CalFunctions::day_of_week_from_date($dt->Format("Y-m-d H:i:s")));
			
			$interv=$this->format_interval_FR($dt_from,$dt,$data['started']);
			
			$events[$waste_type]['waste_type_name']=$this->waste_types[$waste_type]['waste_type_name'];
			$events[$waste_type]['waste_type_info']=$this->waste_types[$waste_type]['waste_type_info'];
			$events[$waste_type]['sector_name']=$this->waste_types[$waste_type]['sector_name']; //29-3-18
			$events[$waste_type]['interv']=$interv;
			$events[$waste_type]['full_date']=$weekday.' '.$PM.' '.$day_num.' '.$month_FR;
		}
		return $events;	
	}	
	
	public function get_cols_today(){
		/* Return array for waste type if there is a collection today
		*
		* Returned array will have the same structure as produced by ::first_col_dates:
		* array('DGB'=>array('date'=>'2017-01-05 16:30:00','started'=>false))
		*/
		$first_col_dates = $this->first_col_dates($this->get_create_date());
		$now=$this->dt_create->Format('Y-m-d');
		$col_dates=null;
		foreach($first_col_dates as $waste_type => $arr)
		{
			$date_col = date('Y-m-d',strtotime( $arr['date'] ));
			if ($date_col==$now){
				$col_dates[$waste_type]=$arr;
			}
		} 
		return $col_dates; //E.g. array('DGB'=>array('date'=>'2017-01-05 16:30:00','started'=>false))
	}
		
	public function format_interval_FR($dt_now,$dt_key_date,$started){
		//TODO Check parameters DateTime objects
		$PM=($dt_key_date->format('A')=='AM'?'matin':'soir');
		$dt_key_date->SetTime(0,0);
		$dt_now->SetTime(0,0);
		$dt_interv=$dt_now->diff($dt_key_date);
		//var_dump($dt_interv);
		$num_days= $dt_interv->days;
		switch (true){
			case $num_days==0:
				if ($started){
					return mb_convert_encoding('À ce moment', 'UTF-8','ISO-8859-15');
				}
				else
				{
					return 'Ce '.$PM;
				}
				break;
			case $num_days==1:
				return 'Demain '.$PM;
				break;
			case ($num_days % 7):
				return 'Dans '.$num_days.' jours';
				break;
			default:
					return 'Dans '.($num_days/7).' semaine'.(($num_days/7>1)?'s':''); 
			  break;
		}
	}
	
	private function excluded_col_date($dt) {
		if($this->excluded_col_dates){
			foreach($this->excluded_col_dates as $date){
				$dt_excl=DateTime::CreateFromFormat('Y-m-d',$date);
				/* debug
				if ( $dt->Format('Y-m-d')=='2018-03-13' ) {
					var_dump($dt);
					var_dump($dt->Format('m-d'));
					var_dump($dt_excl->Format('m-d'));
				}
				*/
				if( $dt_excl->Format('m-d')==$dt->Format('m-d') ){return true;}
			}
		}
		return false;
	}
	
	private function del_excl_dates($arg_waste_type_code=null){
		if (!$this->col_dates){return;}
		$reindex=false;
		foreach($this->col_dates as $waste_type=>$dates){
			if ( $arg_waste_type_code && ($arg_waste_type_code!==$waste_type) ) {continue;} 
			$reindex=false;
			foreach($dates as $key=>$date){
				$dt=CalFunctions::validatedDateTime($date,true,true); //allow missing time, reset time to 00:00:00
				foreach($this->excluded_col_dates as $excl_date){
					$dt_excl= CalFunctions::validatedDateTime($excl_date,true,true);
					if ( $dt->Format('m-d')==$dt_excl->Format('m-d') ){
						unset($this->col_dates[$waste_type][$key]);
						$reindex=true;
					}
				}
			}
			if ($reindex) { $this->col_dates[$waste_type] = array_values($this->col_dates[$waste_type]) ;}
		}
		return $reindex; //$this->col_dates;//debug
	}

}




?>	
	

