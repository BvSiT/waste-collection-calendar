<?php
/**
* With thanks to:
*@author  Xu Ding
*@email   thedilab@gmail.com
*@website http://www.StarTutorial.com
* https://www.startutorial.com/articles/view/how-to-build-a-web-calendar-in-php
*
* Adapted by BvS: 
*  - always 6 weeks in month like Google calendar widget
*  - most properties are set in the constructor
*  - constructor with arguments $year and $month
**/

/*
Google calendar

Header (januari ):
{	font-size: 12px;
   font-weight: 400;
   color: #757575; //light grey
}

//div role='grid'  //grid voor alle dagen
display: table;
    table-layout: fixed;
    width: 100%;
    text-align: center;


color_day_num_cur_month: #212121;
color_day_num: #757575; //

//geen borders en grijze achtergrond:
//border: none
//background-color: inherit
*/
defined("_B") or define("_B","</BR>"); //debug
require_once 'WasteColCalendarContainer.php';
$container = new WasteColCalendarContainer();
defined('PATH_CSS') or define('PATH_CSS',$container->get_waste_calendar_application('css'));
require_once $container->get_waste_calendar_application('CalFunctions');
unset($container);

class MonthCalendar {  

    /********************* PROPERTIES ********************/  
    //private $dayLabels = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
	private $dayLabels = array("L","M","M","J","V","S","D");
    private $currentYear;
    private $currentMonth;
	private $weekdayFirstDayOfMonth;
	private $dateFirstDayOfMonth; //string with format 'Y-m-i'
    private $daysInMonth;
    private $naviHref= null;
	private $dates_to_mark;
	private $background_colors; // e.g. array('DGB'=>'#02ad79',..)	
	private $path_css;
	private $waste_types=null;
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
	private $show_today=true;
	private $show_past=true;
	private $created; // string date('Y-m-d H:i:s')
	private	$date_created; // string date('Y-m-d) //=date('Y-m-d', strtotime($this->created));
	private $month_created; //int intval //(date('m', strtotime($this->created)));		
	private $cols_today; // e.g.	array('DGB'=>array('date'=>'2017-01-05 16:30:00','started'=>false)) 

    /********************* CONSTRUCTOR ********************/  

	public function __construct( $properties_array=array('year'=>null,'month'=>null,'dates_to_mark'=>null,'waste_types'=>null,'created'=>null) ){
		/*
		*	$properties_array['dates_to_mark'] =null // Array with format WasteColCalendar::col_dates
		* e.g. array('DGB'=>array('2018-01-01','2018-03-23',...))	
		* 
		* $properties_array['waste_types'] =null // Array with waste_type info with format WasteColCalendar::waste_types
		*
		* $properties_array['created'] : should be string with format 'Y-m-d H:i:s' 
		*/
		
		$year= isset($properties_array['year'])?intval($properties_array['year']):null;
		$month= isset($properties_array['month'])?intval($properties_array['month']):null;
		$dates_to_mark= isset($properties_array['dates_to_mark'])?$properties_array['dates_to_mark']:null;
		$this->waste_types= isset($properties_array['waste_types'])?$properties_array['waste_types']:null;
		$this->created=isset($properties_array['created'])?$properties_array['created']:date('Y-m-d H:i:s');
		$this->date_created=date('Y-m-d', strtotime($this->created));
		$this->month_created=intval(date('m', strtotime($this->created)));
		$this->naviHref = htmlentities($_SERVER['PHP_SELF']);

		if (!($year && $month)){
			if( isset($_GET['year']) && intval($_GET['year']) ){
			 $year = intval($_GET['year']);
			}
			else {
				$year = intval(date("Y",time()));  
			}
			if( isset($_GET['month']) && intval($_GET['month']) ){
				$month = intval($_GET['month']);
			}
			else {
				$month = intval(date("m",time()));
			}								
		}
		$this->currentYear=$year;
		$this->currentMonth=$month;
		
		//'N' = 1 (for Monday) through 7 (for Sunday) 
		$this->weekdayFirstDayOfMonth = intval(date('N',strtotime($this->currentYear.'-'.$this->currentMonth.'-01')));
		$this->dateFirstDayOfMonth = date($this->currentYear.'-'.$this->currentMonth.'-01');
		$this->daysInMonth=$this->_daysInMonth($this->currentMonth,$this->currentYear);  
		
		if ($dates_to_mark){ //e.g. array('DGB'=>array('2018-01-01','2018-03-23',...))	
			//add only to property $this->dates_to_mark if within current month
			foreach($dates_to_mark as $key=> $dates){
				foreach($dates as $date){
					if ( date('Y-m', strtotime($this->dateFirstDayOfMonth))==date('Y-m', strtotime($date)) ){
						$this->dates_to_mark[$key][]=date('Y-m-d', strtotime($date));
					}
					else {
						if ( date('Y-m', strtotime($date))> date('Y-m', strtotime($this->dateFirstDayOfMonth)) ){
							break;
						}
					}
				}
			}
			$this->background_colors=CalFunctions::get_background_colors(PATH_CSS,$dates_to_mark);
		}
    }
    
    /********************* PUBLIC **********************/  
    
	public function set_excluded_dates($dates){
		if ( is_array($dates) ){
			foreach($dates as $date){
				if ( intval(date('m', strtotime($date)))==$this->currentMonth ) {
					$this->dates_to_mark['EXC'][]=$date;
					if ( !isset($this->waste_types['EXC']) ) {
						$this->waste_types['EXC']['waste_type_name']='Pas de collecte';
					}
				}
			}
		}
	}
		
	public function set_cols_today($col_dates) {
		/*	Import array created by WasteColCalendar::get_cols_today() with structure:
		* 	array('DGB'=>array('date'=>'2017-01-05 16:30:00','started'=>false)) 
		*/
		if ( is_array($col_dates) ){
			//$date_created;
			foreach($col_dates as $key=>$arr) {
				$date_col = date('Y-m-d',strtotime( $arr['date'] ));
				if ($date_col==$this->date_created){
					$this->cols_today[$key]=$arr;
				}
			}	
		}				
	}
    
    /**
    * print out the calendar
    */
    public function show($show_navi=false,$always6Weeks=true) {
			$content= '<div class="boxMonthCal">'.
									'<table class="monthCal">'.
										'<thead>'.
											'<tr>'.$this->_createHeader($show_navi).'</tr>'.
											'<tr>'.$this->_createLabels().'</tr>'.
										'</thead>'.
										'<tbody>';
			
			if($always6Weeks) {
				$weeksInMonth=6;
			}
			else {
				$weeksInMonth = $this->_weeksInMonth($month,$year);																	
			}

			// Create weeks in a month
			for( $i=0; $i<$weeksInMonth; $i++ ){
				$content.= '<tr>';
				//Create days in a week
				for($j=1;$j<=7;$j++){
					$content.=$this->_showDay($i*7+$j);
				}
				$content.= '</tr>';
			}

			$content.= 		'</tbody>'.
																					'<tfoot><tr></tr></tfoot>'.
									'</table>'.
								'</div>';  //<div class="boxMonthCal">
			//var_dump($content);exit;
			return $content;   
    }
     
    /********************* PRIVATE **********************/ 
    /**
    * create the td element for tr
    */
    private function _showDay($cellNumber){
		$cellNumber=intval($cellNumber);
		$attr_class_month=null; 
		$html_attributes_div_cell=null;
		
		switch (true) {
			case ($cellNumber < $this->weekdayFirstDayOfMonth):
				$days= $this->weekdayFirstDayOfMonth - $cellNumber;
				$currentDate = date('Y-m-d', strtotime($this->dateFirstDayOfMonth. ' - '.$days .' days'));
				$cellContent=date('j',strtotime($currentDate));
				$attr_class_month='prev-month';  //adds opacity
				break;
			case ( ($cellNumber >= $this->weekdayFirstDayOfMonth) && 
							($cellNumber < ($this->weekdayFirstDayOfMonth + $this->daysInMonth)) ):
				$currentDay= $cellNumber - ($this->weekdayFirstDayOfMonth - 1);
				$currentDate = date('Y-m-d',strtotime($this->currentYear.'-'.$this->currentMonth.'-'.$currentDay));
				$cellContent = $currentDay;
				$attr_class_month='this-month';					
				break;
			case ( $cellNumber >= ($this->weekdayFirstDayOfMonth + $this->daysInMonth) ):
				$days= $cellNumber - $this->weekdayFirstDayOfMonth;
				$currentDate = date('Y-m-d', strtotime($this->dateFirstDayOfMonth. ' + '.$days .' days'));
				$cellContent=date('j',strtotime($currentDate));
				$attr_class_month='next-month';  //adds opacity
				break;
		}	

		$html_attributes_div_cell=$this->html_attributes_date($currentDate); //if special date mark date by color dot etc.
		//debug // echo $cellNumber.'$css_cell='.$css_cell._B;

		$html_cell='';
		/* If temporary code is added for color dot with 3 colors create canvas */
		if ( preg_match("/<canvas,(.*)>/",$html_attributes_div_cell,$matches) ){
			/* e.g. $matches[0] = '<canvas,#7cccbf,#bfd954,#ffd64c>'
			* 			$matches[1] = '#7cccbf,#bfd954,#ffd64c'
			*/
			if ( isset($matches[1]) ){
				// remove temporary code from html attributes
				$html_attributes_div_cell=str_replace($matches[0],'',$html_attributes_div_cell);
				$html_cell= '<div '.$html_attributes_div_cell.'>'                    ;
				$html_cell.=  '<canvas id="c-'.$currentDate.'" class="canvas">'      ;
				$html_cell.=    'Your browser does not support the HTML5 canvas tag.';
				$html_cell.=  '</canvas>'                                            ;
				$html_cell.=  '<div>'.$cellContent.'</div>'                          ;
				$html_cell.= '</div>'					;
				$colors=explode(',',$matches[1]);
				$colors='"'.implode('","',$colors).'"'; //e.g. "#7cccbf","#bfd954","#ffd64c"
				
				/* First create javascript code e.g. :
				*    draw_color_dot("c-2018-01-01","#7cccbf","#bfd954","#ffd64c")
				*  Then insert the code in jquery document.ready code e.g:
				*  '<script> $(document).ready( 
				* 			function(){ 
				*     		draw_color_dot("myCanvas2","'.$col1.'","'.$col2.'","'.$col3.'");
				*       }
				*     );
				* 	</script>'
				*/
				$html_cell.= CalFunctions::add_jquery_doc_ready( 'draw_color_dot("c-'.$currentDate.'",'.$colors.');' );
			}
		}
		else {
			$html_cell='<div '.$html_attributes_div_cell.'>'.$cellContent.'</div>';
		}
		
		if ($this->show_past){
			if ($this->date_created>$currentDate || $this->currentMonth<$this->month_created){
				$attr_class_month.=' past'; //add opacity to tags <td> with dates that have passed
			}
			//set opacity for today if there are cols but finished
			if ($this->is_today($currentDate)){
				$date_is_marked=false;
				foreach($this->marked_for_date($currentDate) as $key=>$dates){
					$date_is_marked=true;
					if ($key=='EXC') {
						$date_is_marked=false;
						break;
					}
				}
				if ($date_is_marked){
					if (!$this->cols_today){  //if there are no cols that are not finished
						/* Add a div around the existing div with class 'today color-dot' to add
						*  the circle border for today. Remove the circle border from the underlying div by removing class 'today'
						*  and add opacity only here by adding class 'past' to indicate col has finished
						*/
						$html_cell=str_replace('today','past',$html_cell); 
						$html_cell= '<div class="today color-dot">'.$html_cell.'</div>';
					}
				}
			}
		}
	
		return '<td id="li-'.$currentDate.'" class="'.$attr_class_month.'">'.$html_cell.
							//'<div '.$html_attributes_div_cell.'>'.$cellContent.'</div>'.
					'</td>';
    }
     

    /**
    * create header of calendar
    */
    private function _createHeader($show_navi=false){
		if ($show_navi){
			$nextMonth = $this->currentMonth==12?1:intval($this->currentMonth)+1;
			$nextYear = $this->currentMonth==12?intval($this->currentYear)+1:$this->currentYear;
			$preMonth = $this->currentMonth==1?12:intval($this->currentMonth)-1;
			$preYear = $this->currentMonth==1?intval($this->currentYear)-1:$this->currentYear;
		}

		if (function_exists('get_month')){
			$title = ucfirst(get_month($this->currentMonth,false)).' '.$this->currentYear;	// e.g. 'Janvier 2018'
		}
		else {
			$title=date('F Y',strtotime($this->currentYear.'-'.$this->currentMonth.'-1')); // e.g. 'Januari 2018'
		} 
		
		$html=  '<th class="header" colspan="5">';
		$html.=   '<span class="title">'.$title.'</span>';
		$html.= '</th>';			
		if ($show_navi){
			$html.= '<th class="nav-prev">'.
								'<a class="prev" href="'.$this->naviHref.'?month='.sprintf('%02d',$preMonth).'&year='.$preYear.
									'">&#8810;</a>'.
							'</th>'.
							'<th class="nav-next">'.
									'<a class="next" href="'.$this->naviHref.'?month='.sprintf("%02d", $nextMonth).'&year='.$nextYear.
									'">&#8811;</a>'.
							'</th>';
		}
		return $html;
    }		
	
    /**
    * create calendar week labels
    */
    private function _createLabels(){
		$content='';
		foreach($this->dayLabels as $index=>$label){
			 $content.='<th>'.$label.'</th>';
		 }
		return $content;
    }
     
    /**
    * calculate number of weeks in a particular month
    */
    private function _weeksInMonth($month=null,$year=null){
        if( null==($year) ) {
            $year =  date("Y",time()); 
        }
        if(null==($month)) {
            $month = date("m",time());
        }
        // find number of days in this month
        $daysInMonths = $this->_daysInMonth($month,$year);
        $numOfweeks = ($daysInMonths%7==0?0:1) + intval($daysInMonths/7);
        $monthEndingDay= date('N',strtotime($year.'-'.$month.'-'.$daysInMonths));
        $monthStartDay = date('N',strtotime($year.'-'.$month.'-01'));
        if($monthEndingDay<$monthStartDay){
            $numOfweeks++;
        }
        return $numOfweeks;
    }
 
    /**
    * calculate number of days in a particular month
    */
    private function _daysInMonth($month=null,$year=null){
        if(null==($year))
            $year =  date("Y",time()); 
        if(null==($month))
            $month = date("m",time());
        return date('t',strtotime($year.'-'.$month.'-01'));
    }
		
	private function html_attributes_date($date){
		//Return inline html attributes for div if is marked
		//If date = today add class 'today'
	
		$class=null; $html_attr=null;  //NB Both first used as array, after implode as string
		$waste_type_is_set=false;  //NB 'EXC' will not be considered as a waste_type
		$col_finished=null;
		
		foreach( $this->marked_for_date($date) as $key=>$dates){
			if (!$class) $class[]='color-dot';
			$waste_type_is_set = ($key=='EXC'?false:true) ;
			$keys_found[]=$key; //$key = waste type code e.g 'GRB'
		}
		
		if (isset($keys_found)){  //if date is marked
			switch (true){
				case count($keys_found)==1:
					$class[]=$keys_found[0]; //Now e.g. $class[0=>'color-dot',1=>'GRB'] 					
					$waste_type=$keys_found[0];
					if ($this->waste_types[$waste_type]) {
						$html_attr[]='data-toggle="tooltip"';
						$info=$this->waste_types[$waste_type];
						$title=$info['waste_type_name']; //when hover show this see also CSS tooltip
						if ($waste_type!=='EXC'){
							$title.= ( $waste_type=='BUL' ? '' : ' - '.mb_strtolower($info['waste_type_info']).' -' );	
							$title.= ' ('.($info['PM']==true?'soir':'matin').')';
						}
						$html_attr[]='title="'.$title.'"';
					}
					break;
				case count($keys_found)>1:
					foreach($keys_found as $key){
						if (isset($this->background_colors[$key])) {
							$background[$key]=$this->background_colors[$key];  // e.g. '#02ad79'
						}
					}						
					if (isset($background)){

						if (count($background)==2) {
							$html_attr[]='style="background:linear-gradient(125deg, ';
							foreach($background as $key=>$val){
								$background[$key]=$val.' 50%';
							}
							$html_attr[]=implode(' ,',$background).')"';	
						}
						if (count($background)==3) {  
							/* Add temporary code to prepare for color dot creation with 3 colors by using canvas	e.g. <canvas,#7cccbf,#bfd954,#ffd64c> */
							$html_attr[]='<canvas,'.implode(',',$background).'>';
						}
					}
					if ($this->waste_types) {
						$html_attr[]= 'data-toggle="tooltip"';
						$title=null;
						foreach($keys_found as $key){  //$key = waste type code
							$info=$this->waste_types[$key];
							//$title.= $title?' / ':'';
							//See https://stackoverflow.com/questions/26915827/simple-html-and-css-tooltip-with-newline-carriage-return
							$title.= $title?'&#xa;':'';
							$title.=$info['waste_type_name']; //when hover show this see also CSS tooltip
							if ($key!=='EXC'){
								$title.= ( $key=='BUL' ? '' : ' - '.mb_strtolower($info['waste_type_info']).' -' );	
								$title.= ' ('.($info['PM']==true?'soir':'matin').')';
							}
						}
						$html_attr[]='title="'.$title.'"';
					}						
					break;
			}
		}

		//echo implode(' ',array('l'.__LINE__,$this->show_today)).'</BR>';//debug	
		if ($this->show_today){
			if ( $this->is_today($date) ){	
				$class[]='today';
				if (!in_array('color-dot',$class)){$class[]='color-dot';} //Both class 'today' and 'color-dot' are  needed to show a circular border  
			}
		}
		$class=($class?'class="'.implode(' ',$class).'"':null);
		$html_attr=($html_attr?implode(' ',$html_attr):null);
		return $html_attr.($class?$class:'');
	}
		
	private function is_today($date){
		$date=date('Y-m-d', strtotime($date)); //If arg $date has another format then 'Y-m-d' e.g 'Y-m-d H:i:s'
		if ( ($this->date_created==$date) && ($this->currentMonth==$this->month_created) )return true;
		return false;
	}
		
	private function marked_for_date($date){
		/* Return all elements of array ::dates_to_mark for which date is $date  
		*  All other dates are not returned. Returns null if there are no dates found.
		*  Note: if returned array is null foreach will give an error.  
		*/
		$date=date('Y-m-d', strtotime($date)); //If arg $date has another format then 'Y-m-d' e.g 'Y-m-d H:i:s'
		$marked_for_date=null; 
		if (!$this->dates_to_mark) return (array) $marked_for_date;

		foreach($this->dates_to_mark as $key=>$dates){ // e.g. array('DGB'=>array('2018-01-01','2018-03-23',...))	
			if (in_array($date,$dates)){
				$marked_for_date[$key][]=$date; 
			}
		}		
		return (array) $marked_for_date;  // NB if array = null cast array will return empty array, not null.
	}
}