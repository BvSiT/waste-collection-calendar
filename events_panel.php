

<?php /*
	Config fontawesome fa-info-circle info sign with local installation
		- Download https://use.fontawesome.com/releases/v5.0.8/fontawesome-free-5.0.8.zip
		- Copy \fontawesome-free-5.0.8\web-fonts-with-css\webfonts in folder \resources\fontawesome\5.0.8\
		- Copy \fontawesome-free-5.0.8\web-fonts-with-css\css in folder \resources\fontawesome\5.0.8\
		- Reference in page which uses font-awesome : <link rel="stylesheet" href="./resources/font-awesome/5.0.8/css/fontawesome-all.min.css">

		Note 1.:  Referencing font-awesome.min.css will NOT work allthough this works for the cdn for 4.7.0.:
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">	
			Notice also the hyphen in font-awesome.min.css !!
		Note 2.: all urls in .css are like url("../webfonts/  ..etc. This means the folder \css should be in the same folder as the 		folder \webfonts
*/ ?>

<?php // <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> ?>
<link rel="stylesheet" href=" <?php echo PATH_FONTAWESOME ?>">
	<div class="panel-group">
		<div class="panel panel-default">
			<div class="panel-heading">
			<h3>Collectes à venir
				<button id="info-btn-cal" class="button" title="Tapez sur ou déplacez le curseur vers un cercle coloré pour voir une déscription du type de collecte." data-toggle="tooltip" data-placement="bottom">
						<span class="fa-stack">
								<i class="fa fa-circle fa-stack-1x icon-info-bgr"></i>
								<i class="fa fa-info-circle fa-stack-1x icon-info"></i>
						</span>							
				</button>
			</h3></div>
			<div class="panel-body">
			  <?php echo html_event_tables($cal); /* Insert tables with info on coming waste colections */?>
			</div>
		</div>
	</div> <!-- panel-group -->
		
<?php
	function html_event_tables($cal){
		$html='';
		if ( $events=$cal->format_coming_col_events($cal->get_create_date()) ){	 //get array with info on coming collections, from now is default.
			foreach($events as $waste_type=>$data){
				$title=$events[$waste_type]['waste_type_name']; //when hover show this see also CSS tooltip
				if ($waste_type!=='BUL'){
					$title.= ' ('.mb_strtolower($events[$waste_type]['waste_type_info']).')';
					$sector_name=str_ireplace('DV','',$events[$waste_type]['sector_name']);
					$title.= ' secteur '.$sector_name;					
				}
				/* data-toggle='tooltip' is Bootstrap style of tooltip which accepts attribute 'title' as content
				*  Prereq.: Tooltips must be initialized with jQuery   
				*  See in parent .php '<script> $(document).ready(function().. '
				*  See also: https://www.w3schools.com/bootstrap/bootstrap_tooltip.asp
				*  See for layout (no arrow, shadow, etc.) active .css
				*/
				$tooltip="data-toggle='tooltip' title='{$title}' data-placement='right'";
				$html.="<table class='UpEventsTable'><tr>".
						"<td><div class='$waste_type color-dot' {$tooltip} ></div></td>".
						"<td {$tooltip} ><h4>{$events[$waste_type]['interv']}</h4></td>".
						"</tr><tr>".
						"<td></td>".
						"<td {$tooltip} >{$events[$waste_type]['full_date']}</td>".
						"</tr></table>";
				if (array_keys($events)[count($events)-1]<>$waste_type){
					$html.="<hr>";  // add horizontal separator line between tables
				}
			}
		}
		else {
			$html='Aucun collecte à venir cette annnée';
		}
		return $html;
	}
