<?php //<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "
http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php //<meta http-equiv="Content-type" content="text/html; charset=iso-8859-15" /> ?>
<meta name="viewport" content="width=device-width">
<title>Mes jours de collectes</title>

<!-- jquery -->
<script src="<?php echo PATH_JQUERY ?>"></script> <!-- 3.3.1 -->
<?php //<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> ?>

<!-- Bootstrap -->
<?php 
//<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
//<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
?>
<link rel="stylesheet" href="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/css/bootstrap.min.css">
<script src="<?php echo PATH_RESOURCE_BOOTSTRAP ?>/js/bootstrap.min.js"></script>

<script src="<?php echo PATH_COLOR_DOT_JS ?>"></script>

<link href="<?php echo PATH_CSS ?>" rel="stylesheet" type="text/css" />
<script>
$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip();
		
		root_info_btn=$("#info-btn-cal").attr('data-original-title');  //bootstrap button with tooltip
		root_info_btn=root_info_btn.replace("Tapez sur ou déplacez le curseur vers ","");
		checkSize(); //Allows adapting the title of #info-btn-cal to touch screen 
    $(window).resize(checkSize);		
});

//See https://www.fourfront.us/blog/jquery-window-width-and-media-queries
function checkSize(){
    if ($("footer").css("line-height") == "7px" ){
				//In .css : @media only screen and (max-width: 720px) 
			  //console.log('touch');
				$("#info-btn-cal").attr('data-original-title','Tapez sur '+root_info_btn);
    }
		else
		{
				$("#info-btn-cal").attr('data-original-title','Déplacez le curseur vers '+root_info_btn);				
			  //console.log('large screen');
		}
}
</script>


</head>

<body>
<?php include PATH_NAV_BAR; ?> 

<!-- <div class="container-fluid"> -->
<div class="container">
	<div class="row">
		<div id="left-side-panel" class="col-sm-3 no-float">
				<?php require_once('events_panel.php'); /* Insert div with info on coming waste colections */ ?>
		</div>  <!-- #left-side-panel -->
		<div id="main-content" class="col-sm-9 no-float text-center">
			<?php	
        echo format_year_cal($cal); 																	
			?>
		</div> 	<!-- end #main-content -->
</div><!-- end .container -->
<?php require_once('footer.php'); ?>
</body>
</html>

<?php

function format_year_cal($cal) { 
	/* When instantiating WasteColCalendar object by default only dates are added to
	*  WasteColCalendar::col_dates  for waste types with a sector where collection is based on fixed dates.
	*  By calling $cal->get_col_dates() dates are added to $cal->col_dates for waste types with week pattern
	*/	
	$dates_to_mark=$cal->get_col_dates(); //dates are also added to $cal->col_dates for waste types with week pattern
	$waste_types=$cal->get_waste_types();
	$create_date=$cal->get_create_date();
	$excluded_dates=$cal->get_excluded_col_dates();
	$cols_today=$cal->get_cols_today();
	$now=strtotime($create_date);
	$year=date('Y',$now);
	$current_month=date('n',$now);
	//BvS 19-7-2018 Added: always show only from current trimester  
	$current_trimester= ceil($current_month/3); //ceil() rounds to the next highest integer
	$html='';
	$month=$current_trimester*3 - 2;//start with first month of current trimester
	for($r=$current_trimester;$r<=4;$r++){
		$html.=	 '<div class="row">';
		for($c=1;$c<=3;$c++){
			$html.=		'<div class="col-sm-4 '.($month<$current_month?'hidden-xs':'').'" >';
			$month_cal = new MonthCalendar( array('year'=>$year,'month'=>$month,'dates_to_mark'=>$dates_to_mark,
																							'waste_types'=>$waste_types,'created'=>$create_date) );
			$month_cal->set_excluded_dates($excluded_dates);
			if ($cols_today && $current_month==$month) $month_cal->set_cols_today($cols_today);
			//var_dump($month_cal);exit;//debug
			$html.= $month_cal->show();
			$html.=		'</div>';
			unset($month_cal);
			$month+=1;
		}
		$html.=	 '</div>';
	}
	return $html;
}

?>

