<?php
	class CalFunctions {
		private static $locales=array('fr_FR'=>array('fr_FR.utf8','fra'),
						   'en_US'=>array('en_US.utf8','eng'),
						   'nl_NL'=>array('nl_NL.utf8','dutch'),
						   'de_DE'=>array('de_DE.utf8','german')
						   );
		public static function get_month($month_num,$short=true,$locale='fr_FR'){
			$locale_is_set=null;			   
			if (array_key_exists($locale,self::$locales)){
				$locale_is_set=setlocale (LC_TIME, self::$locales[$locale][0],self::$locales[$locale][1]); //see strftime()
			}
			/* setlocale French does not work on mijndomein.nl (nl,en,de do work!)
			$locale_is_set=setlocale(LC_TIME, 'fr_FR.UTF8', 'fr_FR.ISO8859-1',
				'fr_FR.ISO8859-15', 'fr_FR.ISO-8859-15', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
			echo '$locale_is_set='.$locale_is_set._B;
			*/
			if ($locale_is_set){
				$date=mktime(0,0,0,$month_num,1,2017); //date as int for '2017-$month_num-1 00:00:00'
				$month =strftime($short?"%b":"%B",$date); //Abbr. month according to set locale i.e. 'Feb','févr.'
				/* Alt. to get French abbr. month from month num:
				* //Create a DateTime object set to '1970-$month_num-01 00:00:00'
				* $dateObj = DateTime::createFromFormat('!m', $month_num);
				* $date= $dateObj->format("Y-m-d"); //Date as string. Alt: $date=$dateObj->date
				* $date=strtotime($date);  date as int, which is the only way to get to strftime
				* $month= strftime("%b",$date); //Abbr. month according to set locale
				* NB!! DateTime::format with $format('F') produces sometimes the wrong abbr. month:
				* $dateObj = DateTime::createFromFormat('!m', 2);
				* $month = $dateObj->format('F'); // i.e. Febr
				* echo strftime("%b",strtotime($month)); // March: wrong month!!!
				*/			
			}
			else{
				if (!$short) {
					$months_FR= array('janvier','février','mars',
									  'avril','mai','juin',
									  'juillet','août','septembre',
									  'octobre','novembre','décembre');
				}				  
				else {
					$months_FR=array('janv.','févr.','mars','avr.','mai','juin',
									 'juil.','août','sept.','oct.','nov.','déc.');
				}				  
				$month= $months_FR[$month_num-1]; 				  
			}
			/* NB mb_convert_encoding($month, 'UTF-8','ISO-8859-15') will have to be used to
			* to convert multibyte French diacriticals. But do not use 2 times on the same string!
			*/	
			/* mb_check_encoding($month, 'ASCII')=false if diacritical char is present in string
			* mb_check_encoding($month, 'UTF-8')=true if encoding UTF-8 (not the case if strftime() is used)		
			* see also https://stackoverflow.com/questions/16821534/check-if-is-multibyte-string-in-php
			*/
			//var_dump(mb_check_encoding($month, 'ASCII'));
			//var_dump(mb_check_encoding($month, 'UTF-8'));
			
			//if (mb_check_encoding($month, 'ASCII')&&mb_check_encoding($month, 'UTF-8')){
			if ((!mb_check_encoding($month, 'ASCII'))&&(!mb_check_encoding($month, 'UTF-8'))){
				$month=mb_convert_encoding($month, 'UTF-8','ISO-8859-15');
			}
			return $month;
		}
		
		public static function day_of_week_from_date($date,$locale='fr_FR'){
			/* $date is string with format "YYYY-MM-DD HH:MM:SS.000000" */
			$locale_is_set=null;			   
			if (array_key_exists($locale,self::$locales)){
				$locale_is_set=setlocale (LC_TIME, self::$locales[$locale][0],self::$locales[$locale][1]); //see strftime()
			}
			/* setlocale French does not work on mijndomein.nl (nl,en,de do work!)
			$locale_is_set=setlocale(LC_TIME, 'fr_FR.UTF8', 'fr_FR.ISO8859-1',
				'fr_FR.ISO8859-15', 'fr_FR.ISO-8859-15', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
			echo '$locale_is_set='.$locale_is_set._B;
			*/
			/*  Use explode() to trim the time */
			$date=explode(" ",$date)[0];
			/* Or:  without explode() : $date=DateTime::CreateFromFormat('!Y-m-j+',$date); 
			*  The + prevents error for trailing data, only warning in DateTime::getLastErrors() */				
			$date=DateTime::CreateFromFormat('!Y-m-j',$date);

			if ($locale_is_set){
				$str_day=$date->format('d M Y'); //i.e. '12 Dec 2017';					
				$str_day=strftime("%A",strtotime($str_day)); //According to locale Fr-fr i.e. 'jeudi'			
				}
			else {
				$days_of_week_FR= array('dimanche','lundi','mardi','mercredi',
									  'jeudi','vendredi','samedi');
				$day_num=$date->format('w');
				$str_day=$days_of_week_FR[$day_num];
			}
			return $str_day;
		}		
		
		public static function day_of_week_locale_from_EN($day_of_week_EN,$locale='fr_FR'){
			/* $day_of_week_EN can be a short weekday name like 'mon','tue', etc. or 'monday', etc. */
			$locale_is_set=null;			   
			if (array_key_exists($locale,self::$locales)){
				$locale_is_set=setlocale (LC_TIME, self::$locales[$locale][0],self::$locales[$locale][1]); //see strftime()
			}
			/* setlocale() French does not work on mijndomein.nl (nl,en,de do work!)
				$locale_is_set=setlocale(LC_TIME, 'fr_FR.UTF8', 'fr_FR.ISO8859-1',
				'fr_FR.ISO8859-15', 'fr_FR.ISO-8859-15', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
				echo '$locale_is_set='.$locale_is_set._B;
			*/
						
			$date=strtotime($day_of_week_EN);
			if ($date){
				if ($locale_is_set){
					$str_day=strftime("%A",$date); //If locale fr_FR i.e. 'jeudi'			
					}
				else {
					$days_of_week_FR= array('dimanche','lundi','mardi','mercredi',
										  'jeudi','vendredi','samedi');
					$day_num=strftime("%w",$date);
					$str_day=$days_of_week_FR[$day_num];
				}
				return $str_day;
			}
		}		
		
		public static function get_locales(){
			return self::$locales;
		}

		const WEEKLY=1;
		const WEEK1AND3=2;
		const WEEK2AND4=3;
		const LAST_IN_MONTH=4;
		const SPECIAL_SACS=5;
		const EVEN_WEEKS=6; //1 time a week on even weeks during all year
		const UNEVEN_WEEKS=7; //1 time a week on uneven weeks during all year
		
		public static function weekday_pattern($weekday,$flag_pattern=5,$start='2017-01-01',$end='2018-01-01'){
			/* http://www.php.net/manual/en/datetime.formats.relative.php  */
			/* https://www.service-public.fr/particuliers/vosdroits/F24496 */
		
			//Alt. to create DateTime object but without format check: $date=new DateTime($start);
			$end=DateTime::CreateFromFormat('!Y-m-d',$end); //by adding ! time = 00:00:00 
			$start=DateTime::CreateFromFormat('!Y-m-d',$start);
			
			//Restrict search range dates. Always start from beginning of month for correct adding of DateIntervals
			$start_year=DateTime::CreateFromFormat('!Y-m-d', $start->Format('Y-m').'-01');
			
			//$start->modify($weekday); //add if necessary to next occurence of weekday

			/* Alt:
				$start=strtotime($weekday,strtotime($start)); //results in date type
				$start=date('Y-m-d',$start); //convert from date type back to string
				$start = new DateTime($start);
				$end = new DateTime($end);			
			*/
			
			switch ($flag_pattern) {
				case self::WEEKLY :
					$interval = new DateInterval('P1W');
					$start_year->modify($weekday);
					break;
				case self::UNEVEN_WEEKS:
					$interval = new DateInterval('P2W');
					$start_year->modify($weekday);
					//var_dump($start_year);echo (int) $start_year->format("W");echo $start_year->format("D") ;exit;
					if ( ((int) $start_year->format("W")) % 2 == 0 ) { //if week number is even
						$start_year->add(new DateInterval('P1W')); //add 1 week
					}
					break;
				case self::EVEN_WEEKS:
					$interval = new DateInterval('P2W');
					$start_year->modify($weekday);					
					if ( ((int) $start_year->format("W")) % 2 !== 0 ) { //if week number is uneven
						$start_year->add(new DateInterval('P1W')); //add 1 week
					}					
					break;					
				default:
					$interval = new DateInterval('P1M');
					break;
			}
			
			$daterange = new DatePeriod($start_year, $interval ,$end);
			//debug//var_dump($daterange);

			foreach($daterange as $date){
				if ($flag_pattern<>self::WEEKLY) {$date->modify($weekday);} //first occurence of weekday in month
				//debug// echo 'l'.__LINE__.': '.$date->Format('D d-m-Y')._B;	

				switch ($flag_pattern) {
					case self::WEEKLY: 
					case self::EVEN_WEEKS:
					case self::UNEVEN_WEEKS:
						$dates[]= $date->format("Y-m-d");
						break;
					case self::WEEK1AND3:
						if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
						$date->add(new DateInterval('P2W'));
						if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
						break;
					case self::WEEK2AND4:
						$date->add(new DateInterval('P1W')); //start with 2nd occurence
						if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
						$date->add(new DateInterval('P2W'));
						if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
						break;
					case self::LAST_IN_MONTH:
						$date->add(new DateInterval('P3W')); //only 4th weekday in month
						if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
						break;
					case self::SPECIAL_SACS:
						//echo $date->format('M')._B;
						$month=$date->format('M'); //'Jan', etc.
						switch ($month) {
							case 'Jan':  // 2018: In Dec/Jan/Febr only first date of month.   In 2017: no dates in Jan/Feb
							case 'Feb':
								//only first date
								if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }  //new in 2018. See also case 'Dec'.
								break;
							case 'Mar':  // 2018: From 15/3 weekly. Was in 2017: 2 dates every 2 weeks
								//if ($date >= $start) $dates[]= $date->format("Y-m-d");  //Probably not first of month as months before??
								//$date->add(new DateInterval('P1W'));
								while ($month==$date->format('M')){
									if ( (int) $date->format('j')>=15 ){ 
										if ($date >= $start) $dates[]= $date->format("Y-m-d");
									}
									$date->add(new DateInterval('P1W'));
								}								
								break;
							/*	
							case 'May': 
								if ($date->Format('m-d')=='05-01') {$date->add(new DateInterval('P1W'));} //start on 2nd occurence to avoid May the 1st. 
								//weekly, 4 dates
								for ($i=1;$i<=4;$i++){
									if ($month==$date->format('M')){
										if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
									}
									$date->add(new DateInterval('P1W'));
								}
								break;
							case 'Jul':  // Jul and Aug or 2 and 3 or 3 and 2?
														// Start 2 weekly in July and repeat 4 times
													// continuing into August
								for ($i=1;$i<=5;$i++){
									if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
									$date->add(new DateInterval('P2W'));
								}
								break;
							case 'Aug':  //handled in July
								break;								
							case 'Oct': // weekly 5 dates (if possible?)!
								for ($i=1;$i<=5;$i++){
									if ($month==$date->format('M')){
										if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
									}
									$date->add(new DateInterval('P1W'));
								}
								break;
							*/
							case 'Dec': //only first date
								if ($date >= $start){ $dates[]= $date->format("Y-m-d"); }
								break;
							default:  // all weeks in month. Months Apr - Nov
								while ($month==$date->format('M')){
									if ($date >= $start) $dates[]= $date->format("Y-m-d");
									$date->add(new DateInterval('P1W'));
								}
								break;
						}
				}
			}
			
			/* BEGIN DEBUG
			foreach($dates as $date){
				$date=DateTime::CreateFromFormat('Y-m-d',$date)	;
				echo $date->format('D d M Y')._B;
			}
			exit;
			END DEBUG */
			return $dates;
		}		
		
		public static function days_diff($dt1,$dt2){
			/* Return the difference between two DateTime objects in days while ignoring the time component.
			*  If $dt1 < $dt2 then return number of days >0.
			*  If $dt1 > $dt2 then return number of days <0.
			*  If $dt1 = $dt2 then return 0
			*  Note: from PHP7.0.0 one could do something like: (clone $dateTime)->setTime(0,0);
			*   This allows much cleaner code while comparing directly - without using this function -
			*   DateTime objects while ignoring time e.g.:
			*   if ( (clone $dateTime1)->setTime(0,0)> (clone $dateTime2)->setTime(0,0) ) ...
			*   See http://php.net/manual/en/language.oop5.cloning.php
			*/
			$dt1->setTime(0,0);
			$dt2->setTime(0,0);
			$diff=$dt2->diff($dt1);
			var_dump($diff);
			return $diff->invert?$diff->days:$diff->days*-1;
		}
		
		public static function validatedDateTime($date_mysql_format,$allow_missing_time=false,$reset_time=false){
			/* Convert string date with mysql date time format "YYYY-MM-DD HH:MM:SS" to DateTime object 
			*  See also https://stackoverflow.com/questions/13194322/php-regex-to-check-date-is-in-yyyy-mm-dd-format
			*/
			$dt = DateTime::createFromFormat('Y-m-d H:i:s', $date_mysql_format);
			if(!$dt){ //Try to create DateTime object even if the time component is not present with time 00:00:00
				if ($allow_missing_time){$dt=DateTime::CreateFromFormat('!Y-m-d',trim($date_mysql_format));}
			}			
			if ($dt == false || array_sum($dt->getLastErrors())) {
				$err_msg='Error: illegal date format: '.$date_mysql_format.' in line '.__LINE__.' '.__FILE__.'<BR>\n';
				throw new Exception($err_msg);	
			}
			if ($reset_time) {$dt->setTime(0,0);}
			return $dt;
		}
		
		public static function w_col_acces_log($path_log,$extra_info=null){
			$log_info=array('timestamp'=> date('Y-m-d H:i:s'),
											'ip' => $_SERVER['REMOTE_ADDR'],
											//'visited'=> $_SERVER['SCRIPT_NAME'],
											'visited'=>basename($_SERVER['PHP_SELF']),
											'browser' => $_SERVER['HTTP_USER_AGENT'],
											'extra_info' => $extra_info
											);
			$message= implode($log_info,"|")."\n";
			//echo $path_log;
			//echo $message;exit;
			error_log($message,3,$path_log);
		}	

		public static function parse_css($file){
			/* Returns array [selector][property]=value of property e.g. $result['.DGB]['background']='#43b657'
			* Thanks to https://stackoverflow.com/questions/3618381/parse-a-css-file-with-php
			* BvS: Adapted to remove comments
			*/
		
			$css = file_get_contents($file);
			//https://stackoverflow.com/questions/709669/how-do-i-remove-blank-lines-from-text-in-php
			$css=preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $css); //remove empty lines
			$css=preg_replace("/\t+/", '', $css); //remove tabs
			$css = preg_replace('!/\*.*?\*/!s', '', $css); //remove comment /* */
			$css=preg_replace("/\n\s*/", "\n", $css); //remove spaces at start of line
			preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css, $arr);
			$result = array();
			foreach ($arr[0] as $i => $x){
					$selector = trim($arr[1][$i]);
					$rules = explode(';', trim($arr[2][$i]));
					$rules_arr = array();
					foreach ($rules as $strRule){
							if (!empty($strRule)){
									$rule = explode(":", $strRule);
									$rules_arr[trim($rule[0])] = trim($rule[1]);
							}
					}
					$selectors = explode(',', trim($selector));
					foreach ($selectors as $strSel){
							$result[$strSel] = $rules_arr;
					}
			}
			return $result;
		}

		public static function get_background_colors($path_css,$col_dates){
			/* If duplicate dates exist in $col_dates get the color code for classes from corresponding waste type code from css
			*  $col_dates = array('DGB'=>array('2018-01-01','2018-03-23',...),'YEB'=>...etc.)
			*  Return array('DGB'=>'#02ad79','YEB'=>..)	
			*/

			$arr_css=null;	
			$background_colors=null;
			foreach ($col_dates as $waste_type=>$dates){
				$keys=$col_dates;
				unset($keys[$waste_type]); //compare only with other waste types
				foreach($keys as $waste_type_2=>$val){
					foreach($dates as $date){ //dates belonging to $waste_type
						if ( in_array($date,$col_dates[$waste_type_2]) ){ //if the same date exists for the two waste types
							if (!$arr_css){
								$arr_css=self::parse_css($path_css);
							}
							if ( isset($arr_css['.'.$waste_type]['background']) ){
								$background_colors[$waste_type]=$arr_css['.'.$waste_type]['background'];
							}
						}
					}
				}
			}
			return $background_colors;
		}

		public static function add_jquery_doc_ready ($javascript){
			$javascript='<script> $(document).ready( function() {'.
										$javascript.
									'}); </script>';
			return $javascript;
		}
		
		function hex2rgba($color, $opacity = false) {
			//Thanks to https://gist.github.com/colourstheme/d992abc081df381ce656
			$default = 'rgb(0,0,0)';    
			if (empty($color)) return $default;    
			if ($color[0] == '#') $color = substr($color, 1);
			if (strlen($color) == 6)
				$hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
			elseif (strlen($color) == 3)
				$hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
			else
				return $default;
			$rgb = array_map('hexdec', $hex);    
			if ($opacity) {
				if (abs($opacity) > 1)
					$opacity = 1.0;
					$output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
			} else {
        $output = 'rgb(' . implode(",", $rgb) . ')';
			}    
			return $output;
		}
	}
