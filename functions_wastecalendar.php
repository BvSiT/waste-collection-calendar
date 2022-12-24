<?php

function NL($numLines=1){
	return nl2br(str_repeat("\n",($numLines)));
}

function street_name($full_street){
    /* Extract name of  street.
	* Ex. street_name('Grenier à neige (rue du) SECTEUR B (1 pour les déchets verts)') = 'Grenier à neige'
	* https://stackoverflow.com/questions/2174362/remove-text-between-parentheses-php
	* /  - opening delimiter (necessary for regular expressions, can be any character that doesn't appear in the regular expression
	* \( - Match an opening parenthesis
	* .* - zero to many characters
	* [^)]+ - Match 1 or more character that is not a closing parenthesis
	* \) - Match a closing parenthesis
	* /  - Closing delimiter
	*/
	//return trim(preg_replace("/\([^)]+\)/","",$full_street)); //Old style: will only remove (..) but leave all chars after that
	return trim(preg_replace('/\(.*/','',$full_street)); //remove all from '('
}

function street_type($full_street){
	/* Extract type of  street.
	* Ex. street_type('Abreuvoir (rue de l’)') = 'rue de l’'	
	*/
	if (preg_match('/\((.*?)\)/',$full_street,$matches)){
		return trim($matches[1]);	//will return null if no match
	}
}

function week_pattern($s) {
	/* return array with days of week (1 or 0) and AM (1 or 0)
	* Ex. If $s='lundi, mercredi, vendredi matin' function returns:
	* 	ass. array("Mon"=>1,"Tue"=>0,"Wed"=>1,"Thu"=>0,"Fri"=>1,"Sat"=>0,"Sun"=>0,"AM"=1)
	*/
	$weekdays_PM_FR=[
		"lundi",
		"mardi",
		"mercredi",
		"jeudi",
		"vendredi",
		"samedi",
		"dimanche",
		"soir",  //presume weekpattern is all AM or all PM
		];
	
	$keys = ['mon','tue','wed','thu','fri','sat','sun','PM'];	
	
	if (strpos($s,"tous les matins")!==false){
		$week_pattern = array_fill_keys($keys, 1);
		$week_pattern["sun"]=0;
		$week_pattern["PM"]=0;
	}
	else	
	{
		$week_pattern = array_fill_keys($keys, 0);

		foreach($weekdays_PM_FR as $key=>$val ) {
			//debug// echo $key . ',' . $val . "<br />\n";;
			if (strpos($s,$val)!==false) {
				$week_pattern[$keys[$key]]=1;
				//alt.: $week_pattern[array_keys($week_pattern)[$key]]=true;
			}	
		}
	}
	$week_pattern["sun"]=0; //never a sunday
	return $week_pattern;
}

function test() {
	/*We don't use setlocale(),strftime() to manipulate day names in French 
	* For this you need to have the locale installed. 
	* See also jddayofweek() jddayofweek(0,2) results in "Sun" 
	*/
	
	$a='secteur DV1';
	$col=null;
	$arr["name"]=str_replace('secteur ','',$a);
	add_to_col($arr,$col);
	$a='secteur DV2';	
	$arr["name"]=str_replace('secteur ','',$a);
	add_to_col($arr,$col);
	var_dump($col);exit;	
	exit;

	$a=array("Mon"=>1,"Tue"=>1,"Wed"=>0);
	$col=null;
	echo add_to_col($a,$col)."<br/>";;
	$a=array("Mon"=>0,"Tue"=>1,"Wed"=>0);
	echo add_to_col($a,$col)."<br/>";;
	$a=array("Mon"=>1,"Tue"=>1,"Wed"=>0);
	echo add_to_col($a,$col)."<br/>";	
	var_dump($col);exit;	
	$a=array("Mon"=>0,"Tue"=>1,"Wed"=>0);
	add_to_col($a,$col);
	$a=array("Mon"=>1,"Tue"=>1,"Wed"=>0);
	add_to_col($a,$col);
	$a=array("Mon"=>0,"Tue"=>0,"Wed"=>0);
	add_to_col($a,$col);	
	$a=array("Mon"=>0,"Tue"=>0,"Wed"=>0);
	add_to_col($a,$col,false);	
	var_dump($col);exit;	
	$a=['A','B','C'];
	$col = add_unique_array2($a,$col);
	$a=['D','E','F'];
	$col = add_unique_array2($a,$col);	
	var_dump($col);exit;	
	
	//print_r($col);
	//var_dump($col);
	
	exit;
	$s='tous les matins (sauf le dimanche)';
	$s='mardi, jeudi, samedi soir';
	//$s='vendredi soir';
	$w=week_pattern($s);
	//print_r($w);
	exit;
}

function add_to_col($arr,&$col,$unique=true) {
	/* Add $arr to second subscript of multidimensional $col[][]
	*  Return index of $col[] where $arr added. If not added return index of where duplicate is found.
	*/
	if ($unique){
		if (isset($col)){
			foreach($col as $y=>$val_y){
				if (array_values($col[$y])== array_values($arr)){
					return $y; //don't add if $arr is not unique 
				}
			}		
		}
	}
	$col[]=$arr;		
	return max(array_keys($col));
}

function deb_pr($var_name,$var) {
	echo $var_name . ' = ' . $var."<br/>";
}

function fill_db_waste_collection_streets() {
	$show_result_per_street=false;
	$show_result_arrays=false;

	//Add also sector_id to table location__waste_type for waste types with week pattern (DGB,YEB)
	//Test confirmed that this has no effect on view v_week_patterns_small
	//This enables adding sector info to the events_panel for all waste types except (BUL)
	//See also modification in WasteColCalendar.php:596
	$add_sector_id_for_wp_wt=true; //Add also sector_id for waste types with week pattern (DGB,YEB) to table location__waste_type

	$filepath='RuesVerrieres2018.txt';
	//shows file //echo file_get_contents($filepath);
	$lines = file($filepath);

	/*
	* $filepath = path to the file with list of streetnames. The origin of this file is
	* http://www.verrieres-le-buisson.fr/IMG/pdf/rue_par_rue.pdf
	* Using Acrobat only the list is exported to a text file which is saved at $filepath.
	* Changed manually:
	*  - for some streets removed too many CRLFs
	*  - Acacias (rue des) Added by hand: SECTEUR B (1 pour les déchets verts) sector 1 was not present in original file rue_par_rue.pdf)  
	*  - for some streets no sector 1 or 2, reconstructed with aid of map and Google maps
	*     	http://www.verrieres-le-buisson.fr/IMG/pdf/calendrier_verrieres_2018_bdef-2.pdf:
	*				line 'Bas-Vaupéreux (allée du) SECTEUR D' => 'Bas-Vaupéreux (allée du) SECTEUR D (2 pour les déchets verts BvS)'
	*				Line #33 : 'Belvédère (allée du) SECTEUR D' => 'Belvédère (allée du) SECTEUR D (1 pour les déchets verts BvS)'
	*				Line #123: Foch (boulevard du maréchal) 'du n° 23 à 31 (côté impair) SECTEUR A' => 'du n° 23 à 31 (côté impair) SECTEUR A (1 pour les déchets verts BvS)'
	*
	* NB To be checked!! Line "Jardiniers (allée des) Jean Jaurès (rue)" Probably this is a print error
	* and should be as follows (can also be checked in the map for 2018). Only for "Jean Jaurès (rue)" there are different groups of house numbers.
	* Org:
	* Jardiniers (allée des) Jean Jaurès (rue) SECTEUR B (1 pour les déchets verts)
	* du n° 1 à 19 (côté impair) SECTEUR C (1 pour les déchets verts)
	* du ...etc.
	*
	* Changed manually into:
	* Jardiniers (allée des) SECTEUR B (1 pour les déchets verts)
	* Jean Jaurès (rue)
	* du n° 1 à 19 (côté impair) SECTEUR C (1 pour les déchets verts)
	* du n° 2 à 14 (côté pair) SECTEUR C (1 pour les déchets verts)
	* du n° 21 à 63 (côté impair) SECTEUR B (1 pour les déchets verts)
	* du n° 16 à 44 (côté pair) SECTEUR B (1 pour les déchets verts)
	*/

	/* Fill table 'sector' in db */
	truncate_table('sector');
	$sectors=fill_db_waste_collection_date_sectors();

	//Save waste type data to table 'waste_type' in database 'wastecollection'
	$waste_types=array(
					array(
						'name'=>'Bac vert foncé',
						'code'=>'DGB',
						'info'=>'Ordures ménagères'
					),
					array(
						'name'=>'Bac jaune',
						'code'=>'YEB',
						'info'=>'Papier, carton, journaux, plastique, métal'
					),
					array(
						'name'=>'Bac vert',
						'code'=>'GRB',
						'info'=>'Verre'
					),
					array(
						'name'=>'Sacs',
						'code'=>'SAC',
						'info'=>'Déchets végétaux'
					),
					array(
						'name'=>'Encombrants',
						'code'=>'BUL',
						'info'=>'Objets encombrants'
						)
					);
	
	//info in $col_days from http://www.verrieres-le-buisson.fr/IMG/pdf/calendrier_verrieres_2018_bdef-2.pdf 
	$col_days_DGB= array("A"=>'lundi et vendredi matin',
											"B"=>'mardi et samedi matin',
											"C"=>'tous les matins sauf dimanche',
											"D"=>'mardi et samedi soir');
											
	$col_days_YEB= array("A"=>'vendredi matin',
											"B"=>'vendredi matin',
											"C"=>'vendredi matin',
											"D"=>'jeudi soir');
					
	//	Write waste type data to db.
	//	NB autonum primary key 'id' is 1 based by default in mysql
	insert_col('waste_type',$waste_types); 				

	//Lines are read and analyzed before transferring data to the database
	$num_lines=count($lines);
	$street=null;
	$col_week_patterns=null;
	$location=[];$col_locations=[];
	$location_waste_type=[];$col_location_waste_types=[];

	for($i=0;$i<($num_lines-1);$i++){
		//echo street_type($lines[$i])._B;
		$street_data=	null;  
		if ( preg_match ( '/^[^(du)].*\(.*\).*SECTEUR.*/', trim($lines[$i])) ){  //not starting with du, contains (...) and 'SECTEUR'
			// e.g. 'Église (place de l’) SECTEUR C (1 pour les déchets verts)'
			//echo 'street: '. $lines[$i]._B;
			//var_dump(get_street_data($lines[$i]));
			$street_data_no_sector=null;  //var is set if line contains only street name and type and is followed by lines with house nos. 
			$street_data=	get_street_data($lines[$i]); // get ass. array with all data about street 
		}			
		
		elseif ( preg_match ( '/^[^(du)].*\(.*\)/', trim($lines[$i])) ){  //not starting with du, contains (...)
			// e.g. 'Fabre (rue)'
			$street_data_no_sector=get_street_data($lines[$i]); 
			//echo 'new street: '. $lines[$i]._B;
			//var_dump(get_street_data($lines[$i]));
		}		
		
		elseif (preg_match ('/^du /', trim($lines[$i]), $matches, PREG_OFFSET_CAPTURE)){  //starting with du
			// e.g. 'du n° 2 à 36 (côté pair) SECTEUR C (1 pour les déchets verts)'
			// echo 'house nos: '.$lines[$i]._B;
			// var_dump(get_street_data($lines[$i]));
			
			$street_data=get_street_data($lines[$i]);
			if ($street_data_no_sector) {
				$street_data['name']=$street_data_no_sector['name'];
				$street_data['type']=$street_data_no_sector['type'];
			}
		}
		
		if ($street_data){
			if ($show_result_per_street) { echo "Line #<b>".$i."</b> : " .htmlspecialchars($lines[$i]);}
			//add location data to assoc. array
			$location['name_street']=$street_data['name'];
			$location['type_street']=$street_data['type'];
			$location['house_nos']=isset($street_data['house_nos'])?$street_data['house_nos']:null;
			$location['min_house_no']= isset($street_data['min_house_no'])?$street_data['min_house_no']:null;
			$location['max_house_no']= isset($street_data['max_house_no'])?$street_data['max_house_no']:null;
			$id_location=add_to_col($location,$col_locations);
			
			/* DEBUG
			if ((int) $id_location==7){  //1 lower than eventually in DB
				var_dump($street_data);exit;
			}
			*/
			
			if ($show_result_per_street) {var_dump($street_data);}
		
			$sector=$street_data['sector']; //A,B,C or D
			$dgreen_bin=$col_days_DGB[$sector]; //e.g. 'lundi, mercredi, vendredi matin'				
			$yellow_bin=$col_days_YEB[$sector]; //e.g. 'lundi, mercredi, vendredi matin'
			
			//extract array with weekpattern and add to collection if it does not exist yet.
			$dgreen_bin_week_pattern_id=add_to_col(week_pattern($dgreen_bin),$col_week_patterns);
			$yellow_bin_week_pattern_id=add_to_col(week_pattern($yellow_bin),$col_week_patterns);				
			
			//sector for GRB (verre) is A,B,C or D
			$green_bin=$street_data['sector'];
			$green_bin_sector_id=array_search($sector,array_column($sectors, 'name'));

			//sector for SAC
			$sacks=$street_data['sector_DV'];
			$sector=$street_data['sector_DV']; //DV1 or DV2				
			$sacks_sector_id=array_search($sector,array_column($sectors, 'name'));				


			if ($show_result_per_street) {
				//SHOW RESULTS		
				//echo "Line #<b>".($i-3)."</b> : " . $street . "<br />\n";
				echo 
				", DGB: " . $dgreen_bin .
				"," . 
				'wp_id='.$dgreen_bin_week_pattern_id .			
				
				", YEB: " . $yellow_bin .
				"," . 
				'wp_id='. $yellow_bin_week_pattern_id .			
				
				" ,GRB: " . $green_bin .
				"," . 
				's_id='. $green_bin_sector_id .
				
				" ,SAC: " . $sacks .
				"," . 
				's_id='. $sacks_sector_id .
				"<br />\n";	
				var_dump($location);	
			}
			
			//waste_type 'Bac vert foncé'
			$waste_type_id=array_search('DGB', array_column($waste_types, 'code'));
			$location_waste_type=array(
				'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
				'waste_type_id'=>$waste_type_id+1,
				//'sector_id'=>null,
				'week_pattern_id'=>$dgreen_bin_week_pattern_id+1
				);

			//Add the same sector_id as for 'GRB'
			if ($add_sector_id_for_wp_wt){$location_waste_type['sector_id']=$green_bin_sector_id+1;}					
				
			add_to_col($location_waste_type,$col_location_waste_types);
			
			//waste_type 'Bac jaune'
			$waste_type_id=array_search('YEB', array_column($waste_types, 'code'));
			$location_waste_type=array(
				'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
				'waste_type_id'=>$waste_type_id+1,
				//'sector_id'=>null,	
				'week_pattern_id'=>$yellow_bin_week_pattern_id+=1
				);
				
			//Add the same sector_id as for 'GRB'
			if ($add_sector_id_for_wp_wt){$location_waste_type['sector_id']=$green_bin_sector_id+1;}					
			
			add_to_col($location_waste_type,$col_location_waste_types);				
			
			//waste_type 'Bac vert'
			$waste_type_id=array_search('GRB', array_column($waste_types, 'code'));				
			$location_waste_type=array(
				'location_id'=>$id_location+1, 
				'waste_type_id'=>$waste_type_id+1,
				'sector_id'=>$green_bin_sector_id+1,	
				//'week_pattern_id'=>null
				);
			add_to_col($location_waste_type,$col_location_waste_types);				
			
			//waste_type 'Sacs'
			$waste_type_id=array_search('SAC', array_column($waste_types, 'code'));								
			$location_waste_type=array(
				'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
				'waste_type_id'=>$waste_type_id+1,
				'sector_id'=>$sacks_sector_id+1,	
				//'week_pattern_id'=>null
				);
			add_to_col($location_waste_type,$col_location_waste_types);								
			
			//waste_type 'Encombrants'
			$waste_type_id=array_search('BUL', array_column($waste_types, 'code'));												
			$location_waste_type=array(
				'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
				'waste_type_id'=>$waste_type_id+1,
				'sector_id'=>array_search('ALL',array_column($sectors,'name'))+1,	
				//'week_pattern_id'=>null
				);
			add_to_col($location_waste_type,$col_location_waste_types);	
			//if ($id_location>1){exit;}				
		}
	}

	if ($show_result_arrays){	
		var_dump($col_location_waste_types);
		var_dump($col_week_patterns);
		var_dump($col_locations);
	}
	//write $col_? to database wastecollection tables 	
	insert_col('location',$col_locations);
	insert_col('week_pattern',$col_week_patterns);
	insert_col('location__waste_type',$col_location_waste_types);
	
}

function fill_db_waste_collection_streets2017() {
	$filepath='RuesVerrieres.txt';
	//shows file //echo file_get_contents($filepath);
	$lines = file($filepath);

	/*
	* $filepath = path to the file with list of streetnames. The origin of this file is
	* http://www.mairie-verrieres-91.fr/ville/IMG/pdf/guide_tri_cps_verrieres-mini.pdf.
	* Using Acrobat only the list is exported to a text file which is saved at $filepath.
	* The exported file consists of lines only separated by CRLF.
	* The file consists of blocks of 5 or 6 lines with information per street,
	* broken by non-essential data like: a line with letter of alphabet, headings for a new page, etc. 
	*
	* In case of 5 lines:
	* line 1: street (i.e. "Belle-feuille (allée de la)") or housenumbers (i.e. "du n° 1 à 5")
	* line 2: collection weekpattern for dark green bin (DGreenBin). FR: "Bac vert foncé".
	* Ex. "mardi, jeudi, samedi matin"
	* line 3: collection weekpattern for yellow bin (YellowBin). FR: "Bac jaune". Ex. "vendredi matin"
	* line 4: code collection sector for green bin (GreenBin). FR: "Bac vert". Code is one of these:
	* "secteur A","secteur B","secteur C"
	* line 5: code collection sector for sacks for garden disposal (Sacks). FR: "Sacs". Code is one of these:
	* "secteur DV1","secteur DV2"
	*
	* In case of line 1 consisting of house numbers the line may be preceded or by a line with the streetname or
	* another full 5 line group with in the first line the actual street name.
	*
	* NB To be checked!! Line "Jardiniers (allée des) Jean Jaurès (rue)" Probably this is a print error
	* and should be as follows. Only for "Jean Jaurès (rue)" there are different groups of house numbers.
	*
	* Jardiniers (allée des)
	* mardi, jeudi, samedi matin
	* vendredi matin
	* secteur B
	* secteur DV1
	* Jean Jaurès (rue) <= this line is probably missing
	* du n° 1 à 19 (côté impair)
	* tous les matins (sauf le dimanche)
	* vendredi matin
	* secteur B
	* secteur DV1
	* etc.
	*/

	/* Fill table 'sector' in db */
	$sectors=fill_db_waste_collection_date_sectors();

	//Save waste type data to table 'waste_type' in database 'wastecollection'
	$waste_types=array(
					array(
						'name'=>'Bac vert foncé',
						'code'=>'DGB',
						'info'=>'Ordures ménagères'
					),
					array(
						'name'=>'Bac jaune',
						'code'=>'YEB',
						'info'=>'Papier, carton, journaux, plastique, métal'
					),
					array(
						'name'=>'Bac vert',
						'code'=>'GRB',
						'info'=>'Verre'
					),
					array(
						'name'=>'Sacs',
						'code'=>'SAC',
						'info'=>'Déchets végétaux'
					),
					array(
						'name'=>'Encombrants',
						'code'=>'BUL',
						'info'=>'Objets encombrants'
						)
					);
	//	Write waste type data to db.
	//	NB autonum primary key 'id' is 1 based by default in mysql
	insert_col('waste_type',$waste_types); 				

	//Lines are read and analyzed to transfer data to the database
	$num_lines=count($lines);
	$street=null;
	$col_week_patterns=null;
	$location=[];$col_locations=[];
	$location_waste_type=[];$col_location_waste_types=[];

	for($i=0;$i<($num_lines-1);$i++){
		$street_nums=null;$min_house_no=null;$max_house_no=null;
		$dgreen_bin=null;$yellow_bin=null;$green_bin=null;$sacks=null;
		/*
		*	Regex:
		*	- ^ : match begin of string
		*	- [a-z0-9]{1,3} : match digits & letters 1 to 3 times
		*	- $ : match end of string	NB trim() removes CRLF, without $ would fail.
		*/
		
		//All groups of 5 lines end with two lines which contain "secteur"
		if (preg_match ('/^secteur [A-Z0-9]{1,3}$/', trim($lines[$i]), $matches, PREG_OFFSET_CAPTURE)) {
		//or: //if (strpos($lines[$i], 'secteur') !== false) {
			if (preg_match ('/^secteur [A-Z0-9]{1,3}$/', trim($lines[$i+1]), $matches, PREG_OFFSET_CAPTURE)) {
				//line with name street or 'du n°':
				if ((strpos($lines[($i-3)], 'du n') !== false)) {
					$street_nums= $lines[($i-3)];
					//if line before contains '(' this means it contains a street name
					if ((strpos($lines[$i-4], '(') !== false)){
						$street=$lines[($i-4)]; // last found street name
					}
					if (strcmp($street,'Jardiniers (allée des)')==0) {
						$street='Jean Jaurès (rue)';
					}
				}
				else //if line contains street name 
				{  
					$street=$lines[($i-3)]; // last found street name
				}

				if (strpos($street,'Jardiniers (allée des) Jean Jaurès (rue)')!==false){
						$street='Jardiniers (allée des)';
				}
				
				/* Find all strings of digits in $street_nums. Function preg_match_all() will return
				* the number of full matches. $match exists of a multi-dimensional array. $match[0]  contains an array with all full matches.
				* Ex. If $street_nums = 'du n° 1 à 5' regex pattern '/\d+/' will return 2 and set
				* $match[0][0]=1, $match[0][1]=5
				*/
				if (!is_null($street_nums)){
					if (preg_match_all('/\d+/', $street_nums, $match)>1) {
						$min_house_no=$match[0][0];
						$max_house_no=$match[0][1];
					}
				}
				
				$dgreen_bin=$lines[($i-2)];  //line with text i.e. 'lundi, mercredi, vendredi matin'
				$yellow_bin=$lines[($i-1)];  //line with text i.e. 'mardi matin'
				$green_bin=$lines[($i)];  //line i.e. 'secteur A'
				$sacks=$lines[($i+1)]; //line i.e. 'secteur DV2'
				
				//extract array with weekpattern and add to collection if it does not exist yet.
				$dgreen_bin_week_pattern_id=add_to_col(week_pattern($dgreen_bin),$col_week_patterns);
				$yellow_bin_week_pattern_id=add_to_col(week_pattern($yellow_bin),$col_week_patterns);
				
				$sector=trim(str_replace('secteur ','',$green_bin));
				$green_bin_sector_id=array_search($sector,array_column($sectors, 'name'));
				$sector=trim(str_replace('secteur ','',$sacks));
				$sacks_sector_id=array_search($sector,array_column($sectors, 'name'));
			
				//SHOW RESULTS		
				
				//echo "Line #<b>".($i-3)."</b> : " . $street . "<br />\n";
				
				echo "Line #<b>".($i-3)."</b> : " .
				htmlspecialchars($lines[($i-3)]) . 
				/*
				" ," .$street.
				" ," .street_name($street) .
				" ,".street_type($street) .
				"," . $street_nums . 
				"," . $min_house_no .
				"," . $max_house_no .
				*/
				"," . $dgreen_bin .
				"," . 
				//'$dgreen_bin_week_pattern_id='.
				$dgreen_bin_week_pattern_id .			
				"," . $yellow_bin .
				"," . 
				//'$yellow_bin_week_pattern_id='.
				$yellow_bin_week_pattern_id .			
				"," . $green_bin .
				"," . $green_bin_sector_id .
				"," . $sacks .
				"," . $sacks_sector_id .
				"<br />\n";	
				//add location data to assoc. array
				$location['name_street']=street_name($street);
				$location['type_street']=street_type($street);
				$location['house_nos']=$street_nums;
				$location['min_house_no']= $min_house_no;
				$location['max_house_no']= $max_house_no;
				$id_location=add_to_col($location,$col_locations);
				var_dump($id_location);
				
				//waste_type 'Bac vert foncé'
				$waste_type_id=array_search('DGB', array_column($waste_types, 'code'));
				$location_waste_type=array(
					'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
					'waste_type_id'=>$waste_type_id+1,
					//'sector_id'=>null,	
					'week_pattern_id'=>$dgreen_bin_week_pattern_id+1
					);
				add_to_col($location_waste_type,$col_location_waste_types);
				
				//waste_type 'Bac jaune'
				$waste_type_id=array_search('YEB', array_column($waste_types, 'code'));
				$location_waste_type=array(
					'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
					'waste_type_id'=>$waste_type_id+1,
					//'sector_id'=>null,	
					'week_pattern_id'=>$yellow_bin_week_pattern_id+=1
					);
				add_to_col($location_waste_type,$col_location_waste_types);				
				
				//waste_type 'Bac vert'
				$waste_type_id=array_search('GRB', array_column($waste_types, 'code'));				
				$location_waste_type=array(
					'location_id'=>$id_location+1, 
					'waste_type_id'=>$waste_type_id+1,
					'sector_id'=>$green_bin_sector_id+1,	
					//'week_pattern_id'=>null
					);
				add_to_col($location_waste_type,$col_location_waste_types);				
				
				//waste_type 'Sacs'
				$waste_type_id=array_search('SAC', array_column($waste_types, 'code'));								
				$location_waste_type=array(
					'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
					'waste_type_id'=>$waste_type_id+1,
					'sector_id'=>$sacks_sector_id+1,	
					//'week_pattern_id'=>null
					);
				add_to_col($location_waste_type,$col_location_waste_types);								
				
				//waste_type 'Encombrants'
				$waste_type_id=array_search('BUL', array_column($waste_types, 'code'));												
				$location_waste_type=array(
					'location_id'=>$id_location+1, //mysql db primary keys are 1 based by default
					'waste_type_id'=>$waste_type_id+1,
					'sector_id'=>array_search('ALL',array_column($sectors,'name'))+1,	
					//'week_pattern_id'=>null
					);
				add_to_col($location_waste_type,$col_location_waste_types);	

				
				//if ($id_location>1){exit;}
			}	
		}
	}

	//var_dump($col_location_waste_types);
	//var_dump($col_week_patterns);
	//var_dump($col_locations);
	//write $col_? to database wastecollection tables 	
	insert_col('location',$col_locations);
	insert_col('week_pattern',$col_week_patterns);
	insert_col('location__waste_type',$col_location_waste_types);
}

function fill_db_waste_collection_date_sectors(){
	/* Version 2018
	*  Before 2018 the waste types with week pattern (DGB, YEB) did not have a sector.
	*  Sectors A,B,C only concerned waste type GRB (verre), sectors DV1 and DV2 only waste type SAC (déchets végétaux)
	*  Now also waste types DGB, YEB have a sector but will not be used. We save this in the DB to represent the real world situation
	*  as accurately as possible without having to change the DB model completely. 
	*  In table 'sector' [PM] and [weekday] still are only used for waste types with a date pattern.
	*  GRB has sectors A, B, C, D. SAC sectors DV1 or DV2 (like before). 
	*/	
	
	//Save waste sector data to table 'sector' in database 'wastecollection'
	//Create assoc. array for waste sectors
	//Note: info inserted manually and originates from http://www.mairie-verrieres-91.fr/ville/IMG/pdf/guide_tri_cps_verrieres-mini.pdf.

	$sectors=array(	array('name'=>'A',  'PM'=>0,'weekday'=>'thu'),
				array('name'=>'B',  'PM'=>0,'weekday'=>'thu'),
				array('name'=>'C',  'PM'=>0,'weekday'=>'thu'),
				array('name'=>'D',  'PM'=>0,'weekday'=>'thu'),  //new in 2018
				array('name'=>'DV1','PM'=>0,'weekday'=>'mon'),
				array('name'=>'DV2','PM'=>0,'weekday'=>'tue'),
				array('name'=>'ALL','PM'=>0,'weekday'=>'tue')
			   );	
	
	/* Alt. 1 to combine arrays and fill $sectors:
	*	$fields=array('name','PM','weekday');
	*	$sector_name=array('A','B','C','DV1','DV2','ALL');
	*	$sector_PM=array(0,0,1,0,0,0);
	*	$sector_weekday=array('thu','thu','thu','mon','tue','tue');
	*	foreach($sector_name as $key=>$name) {
	* 		$sectors[]=array_combine($fields,array($name,$sector_PM[$key],$sector_weekday[$key]));
	* 	}
	*/
	
	/* Alt. 2 to combine arrays and fill $sectors:
	*	foreach($sector_name as $key=>$name) {
	*		$sectors[$key]['name']=$name;
	*		$sectors[$key]['PM']=$sector_PM[$key];
	*		$sectors[$key]['weekday']=$sector_weekday[$key];
	*	}
	*/
	
	// Write sector data to db.
	// 	NB autonum primary key 'id' is 1 based by default in mysql 
	insert_col('sector',$sectors);
	return $sectors; //used in fill_db_waste_collection_streets() 
}

function fill_db_waste_collection_date_patterns() {
	$filepath='CollectionDatesVerrieres.txt';
	//echo file_get_contents($filepath); //shows files
	$lines = file($filepath);
	//deb_pr("count($lines)",count($lines));
	$line_sector=null;
	$col_date_patterns=null;
	$dates=[];
	//Waste collection morning or evening is directly written to table sector in fill_db_waste_collection_streets() line 211 //$AM=null;$find_AM=false;
	foreach($lines as $key=>$line) {
		//echo "line ".$key. ":".  $line."<br />\n";
		//If line begins with digit
		if (preg_match ('/^[0-9]/', trim($line), $match, PREG_OFFSET_CAPTURE)) {
			$find_AM=true; //start searching for a line with AM/PM info for a sector i.e. 'Jeudi matin'
			if (preg_match ('/^[0-9]/', $lines[$key-1], $match, PREG_OFFSET_CAPTURE)==false) {
				//preceding line that does not begin with digit will contain info on the sector
				//NB stripos() is case-insensitive
				if (stripos($lines[$key-1],'secteur')!==false) {
					if (!empty($dates)) { //first time line with sector info is found $dates is still empty
						//Waste collection morning or evening is directly written to table sector// var_dump($AM);
						var_dump($dates);
						insert_date_pattern($line_sector,$dates);
					}
					$line_sector=trim(str_ireplace('secteur ','',$lines[$key-1]));
					$dates=[];
				}	
				if (stripos($lines[$key-1],'toute la ville')!==false) {
					if (!empty($dates)) {
						//Waste collection morning or evening is directly written to table sector//var_dump($AM);
						var_dump($dates);
						insert_date_pattern($line_sector,$dates);
						}					
					$line_sector="ALL"; //bulky waste type for whole town
					$dates=[];
				}
			}
			$dates=array_merge($dates,date_pattern(trim($line)));	
			echo "line ".$key. ":".  trim($line). ",".$line_sector.","._B;					
		}
		else
		{ 
			/* Waste collection morning or evening is directly written to table sector
			if ($find_AM){
				if (preg_match('$matin|soir$i',$line,$match)){
					$AM=strtolower($match[0]);
				}
			}
			*/
		}
		if ($key==(count($lines)-1)) {
			var_dump($AM);
			if (!empty($dates)) {
				var_dump($dates);
				insert_date_pattern($line_sector,$dates);
			}
		}
	}
}

function insert_date_pattern($sector_name,$dates=[]) {
	/*	In database wastecollection populate tables 'sector__waste_col_date' and 'waste_col_date'.
		Par. $dates should be an array of date objects and are saved
		in mysql  as DATETIME type which has 'YYYY-MM-DD HH:MM:SS' format. 	
	*/
	$mysqli=connect_mydb();
	//find id sector
	$sql="SELECT id FROM sector WHERE name='".$sector_name."'";
	$result = mysqli_query($mysqli,$sql) or die (__LINE__.': Couldn’t execute query: '.$sql);
	if ($result->num_rows > 0){
		//return $result->fetch_object()->id;
		//TODO: simplify only add when not exist and return id
		$sector_id=$result->fetch_object()->id;
		if ($sector_id){
			foreach($dates as $date){
				var_dump($date);
				$sql="SELECT id FROM waste_col_date WHERE date='".$date->format('Y-m-d')."'"; //format of mysql DATETIME field type
				echo __LINE__.$sql._B;				
				$res_date = mysqli_query($mysqli,$sql) or die (__LINE__.': Couldn’t execute query: '.$sql);
				if ($res_date->num_rows > 0){ /*date already exists in table waste_col_date */
					$date_id=$res_date->fetch_object()->id;
				}
				else { /* if date does not exist add to table 'waste_col_date'*/
					$sql = "INSERT INTO waste_col_date (date) VALUES ('".$date->format('Y-m-d')."')";
					$result = mysqli_query($mysqli,$sql) or die (__LINE__.': Couldn’t execute query: '.$sql);
					$date_id=$mysqli->insert_id;
					echo __LINE__.$sql._B;
				}
				deb_pr('$date_id',$date_id);deb_pr('$sector_id',$sector_id);deb_pr('$sector_name',$sector_name);
				/* add to assoc. (cross-reference) table 'sector__waste_col_date' */
				$sql="INSERT INTO sector__waste_col_date (sector_id,waste_col_date_id) VALUES (".$sector_id.",".$date_id.")";
				//$result = mysqli_query($mysqli,$sql) or die (__LINE__.': Couldn’t execute query: '.$sql);
				if (!mysqli_query($mysqli,$sql)) {
					$err_msg=__LINE__.": Error executing ".$sql._B.$mysqli->error._B;
					printf($err_msg);
					//printf("Error executing".$sql." %s\n", $mysqli->error);
				}				
				echo __LINE__.$sql._B;				
			}			
		}
	}
	return;
}

function date_pattern($s){	
	/*	Extract dates from string and return them in an array
	* Ex. If $s='7 et 21 mars - 4, 8 et 25 avril' then date_pattern($s) returns:
	*	array(date('7-3-2017 00:00:00'),date('21-3-2017 00:00:00)',date('4-4-2017
	*	00:00:00',date('8-4-2017 00:00:00'),date('25-4-2017 00:00:00'))
	*/
	
	$months_FR=["janv","févr","mars","avril","mai","juin","juil","août","sept","oct","nov","déc"];
	
	/* Alternative to create $months_FR
	*	setlocale (LC_TIME, 'fr_FR.utf8','fra'); //To enable strftime() to depict French day and month names 
	*	for($i=1;$i<=12;$i++){
	*		$month=strftime('%b', mktime(0, 0, 0, $i)); //short month. If locale is French i.e. 'févr.', etc.
	*		$month=rtrim($month,'.'); //remove period to enable search for long month names
	*		$months_FR[]=mb_convert_encoding($month, 'UTF-8','ISO-8859-15');//convert 2-byte French diacriticals 
	*	}
	*/	

	$regex_months='/'.implode("|",$months_FR).'/';  // regex expression with OR 
	/* preg_split() with PREG_SPLIT_OFFSET_CAPTURE will return an array which
	* consists in itself of an array with at [0] the string of each piece
	* and at [1] the offset of each piece.
	*/
	$pieces = preg_split($regex_months,$s,NULL,PREG_SPLIT_OFFSET_CAPTURE);
	foreach($pieces as $key=>$val){
		$sep=null;
		if (($key+1)<count($pieces)){
			$offset_sep=$val[1]+strlen($val[0]);
			$len_sep=$pieces[$key+1][1]-($val[1]+strlen($val[0]));
			$sep=substr($s,$offset_sep,$len_sep);
			$month_num=array_search($sep,$months_FR)+1;
			if (preg_match_all('/\d+/', $val[0], $match)>0) {  //find groups of digits
				foreach($match as $day_no_s) {
					foreach($day_no_s as $val){
					//$dates[]=$val."-".$month_num."-".date("Y");
					//In DateTime::createFromFormat '!' in $format sets time of date to 00:00:00
					/* _YEAR_CALENDAR is defined in waste_calendar_fill.php */
					$dates[]=DateTime::createFromFormat('!j-m-Y', $val."-".$month_num."-"._YEAR_CALENDAR);
					}
				}
			}			
		}
		else {break;} //after last piece will not be followed by month name, so ignore
	}
	return $dates;
}

function connect_mydb($echo_connect_info=false) {
	//TODO test http://php.net/manual/en/mysqli.set-charset.php
	
	/*Following gives error:
		return mysqli_connect($host,$user,$password,$dbname) or die('Couldn’t connect to server.');
	The problem is the 'or die etc. so this will be OK:
		return mysqli_connect($host,$user,$password,$dbname);
	PHP will also show the call stack in the browser if:
		ini_set('display_errors', '1'); //default or set in php.ini?
	*/
	
	//Environment variables are loaded with autoload.php
	$host=env('DB_HOST');
	$user=env('DB_USERNAME');
	$password=env('DB_PASSWORD');
	$dbname=env('DB_NAME');

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions.	
	
	if ($echo_connect_info) {
		echo __FUNCTION__.':'.__LINE__.':'. 'Trying to connect to DB:<BR>';
		echo 'DB_NAME = '.env('DB_NAME').'</BR>';  //!!
	}

	try {
		$mysqli = mysqli_connect($host,$user,$password,$dbname);
	}
	catch (mysqli_sql_exception $e) { // Failed to connect? Lets see the exception details..
        //echo "MySQLi Error Code: " . $e->getCode() . "<br />";
        //echo "Exception Msg: " . $e->getMessage();
		$date = date('m.d.Y h:i:s'); 
		$eMessage =  $date . " | MySQLi Error | " . $e->getMessage() . " | " . $e->getFile() . " | ". $e->getLine() . "\n";
		if ($echo_connect_info) echo $eMessage;
		error_log($eMessage,3,ERROR_LOG); // writes message to error log file		
		if ($echo_connect_info) echo $eMessage;
        exit; // exit and close connection.
	}
	
	// Change to charset UTF-8 to store French characters correctly.
	// TODO But still need mb_fr function to show diacrtitical chars correctly in PHP???
	// Note BvS: apparently mysqli->set_charset does not throw an error, so cannot be tested with try catch block??
	if (!$mysqli->set_charset("utf8")) {
		$date = date('m.d.Y h:i:s'); 
		$err_msg= "Error loading character set utf8: ".$mysqli->error;
		$eMessage =  $date . " | MySQLi Error | " . $err_msg . " | " . __FILE__ . ':' . __FUNCTION__  . " | " . __LINE__ . "\n";
		if ($echo_connect_info) echo $eMessage.'<br>';
		error_log($eMessage,3,ERROR_LOG); // writes message to error log file						
	} else {
		if ($echo_connect_info) printf("Jeu de caractère initial : %s\n<br>", $mysqli->character_set_name());
	}	
			
	//$mysqli->query("SET NAMES 'utf8'"); 
	//$mysqli->query("SET CHARACTER SET utf8");  
	//$mysqli->query("SET SESSION collation_connection = 'utf8_unicode_ci'"); 
	
	if ($mysqli){
		if ($echo_connect_info){
			echo 'Connection succeeded. DB_NAME='.$dbname. PHP_EOL.'</br>';
			echo "Host information: " . mysqli_get_host_info($mysqli) . PHP_EOL.'</br>';
		}
		return $mysqli;
	} 
	else {
		$err_msg='Connect Error: ' . mysqli_connect_error();
		if ($echo_connect_info) echo $err_msg;
		die($err_msg);
		return;			
	}
}


function connect_mydbx($echo_connect_info=true) {

	//TODO test http://php.net/manual/en/mysqli.set-charset.php
	
	/*Following gives error:
		return mysqli_connect($host,$user,$password,$dbname) or die('Couldn’t connect to server.');
	The problem is the 'or die etc. so this will be OK:
		return mysqli_connect($host,$user,$password,$dbname);
	PHP will also show the call stack in the browser if:
		ini_set('display_errors', '1'); //default or set in php.ini?
	*/
	
	//Environment variables are loaded with autoload.php
	$host=env('DB_HOST');
	$user=env('DB_USERNAME');
	$password=env('DB_PASSWORD');
	$dbname=env('DB_NAME');
	
	if ($echo_connect_info) {
		echo __FUNCTION__.':'.__LINE__.':'. 'Trying to connect to DB:<BR>';
		echo 'DB_NAME = '.env('DB_NAME').'</BR>';
	}
	
	try {
		$mysqli = mysqli_connect($host,$user,$password,$dbname);
	}
	catch (mysqli_sql_exception $e) { // Failed to connect? Lets see the exception details..
        //echo "MySQLi Error Code: " . $e->getCode() . "<br />";
        //echo "Exception Msg: " . $e->getMessage();
		$date = date('m.d.Y h:i:s'); 
		$eMessage =  $date . " | System Error | " . $e->getMessage() . " | " . $e->getFile() . " | ". $e->getLine() . "\n";
		if (true) {var_dump($eMessage);}  //debug
		error_log($eMessage,3,ERROR_LOG); // writes message to error log file		
        exit; // exit and close connection.
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	$mysqli = mysqli_connect($host,$user,$password,$dbname);
	mysqli_set_charset($mysqli, 'utf8'); // Does not work?? Still need mb_fr function to show diacrtitical chars correctly
	//mysqli_select_db($db, DB_NAME);	 
	//printf("Jeu de caractère initial : %s\n", $mysqli->character_set_name());

	// Change to charset UTF-8 to store French characters correctly.
	// But still need mb_fr function to show diacrtitical chars correctly in PHP???
	if (!$mysqli->set_charset("utf8")) {
		//http://php.net/manual/fr/mysqli.set-charset.php
		
		//TODO: error handling zoals in moncalendrier.php l 150
		
		
		echo ("Error setting charset to UTF-8 : ".$mysqli->error);
	}
	
	//$mysqli->query("SET NAMES 'utf8'"); 
	//$mysqli->query("SET CHARACTER SET utf8");  
	//$mysqli->query("SET SESSION collation_connection = 'utf8_unicode_ci'"); 
	
	if ($mysqli){
		if ($echo_connect_info){
			echo "Host information: " . mysqli_get_host_info($mysqli) . PHP_EOL.'</br>';
			echo 'DATABASENAME='.$dbname;
			}
		return $mysqli;
	} 
	else {
		$err_msg='Connect Error: ' . mysqli_connect_error();
		if ($echo_connect_info) echo $err_msg;
		die($err_msg);
		return;			
	}
	
}

function test_db($table='location'){
	//$mysqli=connect_mydb('wastecollection',true);
	$mysqli=connect_mydb(true);  //DB env. variables must have been loaded. See autoload.php
	$query='SELECT * FROM '.$table;
	
	$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
	if (!$result){
		$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
		$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
		throw new Exception($err_msg);
	}
	if ($result->num_rows >0) {
		while($row = $result->fetch_assoc()) { //TODO more efficient?
			//var_dump($row);
			$col_rows[]=$row;
		}
		return $col_rows;
	}
	else
	{
		$err_msg="Error description: No records found. num_of_rows = ". $result->num_rows. "</BR>\n";
		$err_msg.='Query: '.$query."</BR>\n";
		throw new Exception($err_msg);
	}	
}

function truncate_table($nameTable){
	$mysqli = connect_mydb();
	$query = "TRUNCATE ".$nameTable;
	$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
}

function insert_col($name_table,$col){
	/* Insert in table an array which consists of a hash table in this format:
	*	$col[autonum id][field_name]=>value field
	*	I.e. :	$col=array(0 => array('field_name1'=>'value1',
	*				  'field_name2'=>0),
	*				  ...
	*			);
	*/
	$mysqli = connect_mydb();
	foreach($col as $fields){  //keys consist of field names
		foreach($fields as $field_name=>$value) {  
			if (is_null($value)){
				unset($fields[$field_name]);
				continue;
			}
			if (is_string($value)){$fields[$field_name]="'".$value."'";}
		}	
		$sql="INSERT INTO ".$name_table." (".implode(",",array_keys($fields)).") VALUES ".
		" (".implode(",",array_values($fields)).")";		
		echo __LINE__.$sql._B;
		if (!mysqli_query($mysqli,$sql)) {
			$err_msg=__LINE__.": Error executing ".$sql."</BR>".$mysqli->error."</BR>";
			die($err_msg);
		}
	}
}

function print_data_calendar($i,$var_dump=false,$all_data=false) {
		try {$w=new WasteColCalendar($i);}
		catch (Exception $e) {
			echo __LINE__.$e->getMessage();
		}
		if ($var_dump){var_dump($w);}
		if ($all_data){
			echo $i.$w->name_street._B;
			$PM='';
			foreach(array('DGB','YEB') as $waste_type_code) {
				echo $waste_type_code.':'.$w->format_weekpattern_FR($waste_type_code,$PM).($PM?',':'').$PM._B;			
			}
			if (true){
				foreach(array('BUL','SAC','GRB') as $waste_type_code){
					echo $waste_type_code.':'.$w->format_col_dates($waste_type_code).' ,'.$w->format_sector_weekday($waste_type_code,$PM)._B;
				}
			}
		}
		else  //only data per street as shown in RuesVerriers.txt
		{ 
			echo $w->name_street.' ('.$w->type_street.')'._B;
			$PM='';
			echo $w->format_weekpattern_FR('DGB',$PM).($PM?' ':'').$PM._B;
			echo $w->format_weekpattern_FR('YEB',$PM).($PM?' ':'').$PM._B;
			echo 'secteur '.$w->get_sector('GRB')._B;
			echo 'secteur '.$w->get_sector('SAC')._B;
		}
		/*
			Bas-Vaupéreux (allée du)
			mardi, jeudi, samedi soir
			vendredi soir
			secteur C
			secteur DV2
		*/
}

function dump_classes($last=0){
	$class_array = get_declared_classes();
	$max=count($class_array);
	$start= ($last>0?$max-$last:0);
	for ($i=$start; $i<$max;$i++) {
		echo $class_array[$i].'</BR>';
	}
}

function create_test_set(){
	/* return set of location_id and street_name random with in each set combination of sectors: 
	- A + DV1
	- B + DV1
	- C + DV1
	- D + DV1
	- A + DV2
	- B + DV2
	- C + DV2
	- D + DV2
	*/
	
	$mysqli=connect_mydb();

	$sac=array('DV1','DV2');
	$dgb=array('A','B','C','D');  //New in 2018 'D'
	
	foreach($sac as $sac_val){
		foreach($dgb as $dgb_val){
			$query = "SELECT * FROM v_t_sector_info_2018 WHERE sn_grb='{$dgb_val}' AND sn_sac='{$sac_val}' ORDER BY RAND()";
			$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
			if (!$result){
				$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
				$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
				throw new Exception($err_msg);
			}
			if ($result->num_rows >0) {
				while($row = $result->fetch_assoc()) {
					unset($row['wtc_sac']);
					unset($row['wtc_grb']);
					$test_set[]=$row;
					break;
				}
			}
			else
			{
				//$err_msg="Error description: No unique record found. num_of_rows = ". $result->num_rows. "</BR>\n";
				//$err_msg.='Query: '.$query."</BR>\n";
				//throw new Exception($err_msg);
			}				
		}
	}
	var_dump($test_set);
}

function create_test_set_2017(){
	/* return set of location_id and street_name random with in each set combination of sectors: 
	- A + DV1
	- B + DV1
	- C + DV1
	- A + DV2
	- B + DV2
	- C + DV2
	*/
	
	$mysqli=connect_mydb();

	$sac=array('DV1','DV2');
	$dgb=array('A','B','C');
	
	foreach($sac as $sac_val){
		foreach($dgb as $dgb_val){
			$query = "SELECT * FROM v_t_sector_info WHERE sn_grb='{$dgb_val}' AND sn_sac='{$sac_val}' ORDER BY RAND()";
			$result = mysqli_query($mysqli,$query) or die ('Couldn’t execute query: '.$query);
			if (!$result){
				$err_msg="Error description: " . mysqli_error($mysqli)."</BR>\n";
				$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
				throw new Exception($err_msg);
			}
			if ($result->num_rows >0) {
				while($row = $result->fetch_assoc()) {
					unset($row['wtc_sac']);
					unset($row['wtc_grb']);
					$test_set[]=$row;
					break;
				}
			}
			else
			{
				//$err_msg="Error description: No unique record found. num_of_rows = ". $result->num_rows. "</BR>\n";
				//$err_msg.='Query: '.$query."</BR>\n";
				//throw new Exception($err_msg);
			}				
		}
	}
	var_dump($test_set);
}

/*
-- OK on mijndomein.nl
USE mdxxxxxxxxx;
DROP TABLE IF EXISTS user;
CREATE TABLE IF NOT EXISTS user (
  `host` VARCHAR(20) NOT NULL COMMENT 'host ip',
  `location_id` INT NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` TIMESTAMP NOT NULL,
  PRIMARY KEY (`host`)
	)
	
*/

function get_user_location_id($host=null){
	$mysqli=connect_mydb();
	if (!$host) { $host=$_SERVER['REMOTE_ADDR']; }
	$query = "SELECT location_id FROM user WHERE host=?";
	if (!($stmt = $mysqli->prepare($query))) {
		$err_msg="Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error."</BR>\n";
		$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
		throw new Exception($err_msg);			
	}
	if (!($stmt->bind_param("s", $host))){
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

	if ($result->num_rows == 1) {
		/* NB fetch_object moves pointer forward, so next call would throw error: trying to get property of non-object */
		return $result->fetch_object()->location_id;  
	}
}

function save_user_location_id($location_id,$host=null){
  //use prepared statement to query
	$mysqli=connect_mydb();
	if (!$host) { $host=$_SERVER['REMOTE_ADDR']; }
	
	/* 21-4-19 Quick fix host string too long for field host CHARVAR(20) error. Probably because of IPv6 type address
		Just only save the last 20 chars for the moment.
		Better: convert to ipv4 format (if possible?)
		See https://stackoverflow.com/questions/12435582/php-serverremote-addr-shows-ipv6
	*/
	if (strlen($host)>20){ //Probably an IPv6 address
		$host= str_replace(':','',$host);		
		$host = substr(str_replace(':','',$host),strlen($host)-20,20);
	} 
	
	$location_id = (int) $location_id;
	//$query="INSERT INTO user (host,location_id) VALUES(?,?) ON DUPLICATE KEY UPDATE location_id=?;";

	/*	On mijndomein.nl when generating table user an expression can only be added to the first TIMESTAMP field
	*	This means the following is not allowed on mijndomein.nl (though allowed in WAMP mysql 5.7.19  :
	*	`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	*	`updated` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP
	*
	*	This works on mijndomein.nl :
	*
	*		USE db_name?;
	*		DROP TABLE IF EXISTS user;
	*		CREATE TABLE IF NOT EXISTS user (
	*			`host` VARCHAR(20) NOT NULL COMMENT 'host ip',
	*			`location_id` INT NOT NULL,
	*			`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	*			`updated` TIMESTAMP NOT NULL,
	*			PRIMARY KEY (`host`)
	*		)
	*
	* This complicates somewhat updating field 'updated'.
	* Simple version on wamp:
	* $query="INSERT INTO user (host,location_id) VALUES(?,?) ON DUPLICATE KEY UPDATE location_id=?;";
	* Now: see $query below for mijndomein.nl.
	*/
	
	$query="INSERT INTO user (host,location_id,updated) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE location_id=?,updated=NOW();";	
	if (!($stmt = $mysqli->prepare($query))) {
		$err_msg="Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error."</BR>\n";
		$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
		throw new Exception($err_msg);			
	}
	if (!($stmt->bind_param("sii", $host,$location_id,$location_id))){
		$err_msg = "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error."</BR>\n";
		$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
		throw new Exception($err_msg);						
	}
	if (!$stmt->execute()){
		$err_msg = "Execute failed: (" . $stmt->errno . ") " . $stmt->error."</BR>\n";
		$err_msg.='Couldn’t execute query: '.$query."<BR>\n";
		throw new Exception($err_msg);									
	}
	return true;
}


function get_class_css($class_name,$path_css) {
	$fh = fopen($path_css, 'r') or die($php_errormsg);
	while (!feof($fh)) {
		$line = fgets($fh, 4096);
  if (preg_match($pattern, $line)) { $ora_books[ ] = $line; }
	}
	fclose($fh);
}

function get_street_data($s){
	/* Return array with all street data
	* E.g. $s='Grenier à neige (rue du) SECTEUR B (1 pour les déchets verts)' 
	* will return:
	*		array('name' => 'Grenier à neige',
	*			 'type' => 'rue du',
	*			 'sector' => 'B',
	*			 'sector_DV' => 'DV1'  //Before 2018 this sector for déchets verts was called DV1 or DV2, now simply 1 or 2. For compatibility of code
	*              still save this in the DB as DV1 or DV2
	*			 )
	*
	* If $s contains house nos e.g. 'du n° 1 à 19 (côté impair) SECTEUR C (1 pour les déchets verts)' 
	* will return:
	*		array('house_nos' => 'du n° 1 à 19 (côté impair)',
	*			 'min_house_no' => '1',
	*			 'max_house_no' => '19',	
	*			 'sector' => 'B',
	*			 'sector_DV' => 'DV1'  //Before 2018 this sector for déchets verts was called DV1 or DV2, now simply 1 or 2. For compatibility of code
	*              still save this in the DB as DV1 or DV2
	*			 )
	*
	* 
	* !!! https://www.regular-expressions.info
	* https://stackoverflow.com/questions/2174362/remove-text-between-parentheses-php
	* /  - opening delimiter (necessary for regular expressions, can be any character that doesn't appear in the regular expression
	* \( - Match an opening parenthesis
	* .* - zero to many characters
	* [^)]+ - Match 1 or more character that is not a closing parenthesis
	* \) - Match a closing parenthesis
	* /  - Closing delimiter			
	*/
	$data=[];
	if ( preg_match('/^du/',$s) ) { //starting with 'du'
		//if (preg_match('/(.*?\))/',$s,$matches)){ //does not match in case e.g. 'du n° 1 à 5 SECTEUR B (1 pour les déchets verts)'
		if (preg_match('/(.*?) SECTEUR/',$s,$matches)){ //substring before ' SECTEUR'
			//var_dump($matches);	
			$data['house_nos']= trim($matches[1]);
			if (preg_match_all('/\d+/', $data['house_nos'], $match)>1) {
				$data['min_house_no']=$match[0][0];
				$data['max_house_no']=$match[0][1];
			}
		}				
	}
	else
	{	
		$data['name']=trim(preg_replace('/\(.*/',"",$s));
		if (preg_match('/\((.*?)\)/',$s,$matches)){
			//var_dump($matches);	
			$data['type']= $matches[1];
		}		
	}

	if (preg_match('/( SECTEUR (.*?) \()/',$s,$matches)){  //e.g. 'SECTEUR B (' => 'B' in $matches[2]
		//var_dump($matches);	
		$data['sector']=$matches[2];
	}		

	if (preg_match('/\((\d)/',$s,$matches)){
		$data['sector_DV']='DV'.trim($matches[1]);	  // (1 pour les déchets verts) => '1' in $matches[1]
	}
	return $data;
}

//23-12-22 Obsolete. Uses strftime() which is deprecated in 8.1. Now only CalFunctions::get_month() is used
//function get_monthx($month_num,$short=true,$locale='fr_FR'){
//	$locales=array('fr_FR'=>array('fr_FR.utf8','fra'),
//				   'en_US'=>array('en_US.utf8','eng'),
//				   'nl_NL'=>array('nl_NL.utf8','dutch'),
//				   'de_DE'=>array('de_DE.utf8','german')
//				   );
//	$locale_is_set=null;
//	if (array_key_exists($locale,$locales)){
//		$locale_is_set=setlocale (LC_TIME, $locales[$locale][0],$locales[$locale][1]); //see strftime()
//	}
//	/* setlocale French does not work on mijndomein.nl (nl,en,de do work!)
//	*	$locale_is_set=setlocale(LC_TIME, 'fr_FR.UTF8', 'fr_FR.ISO8859-1',
//	*		'fr_FR.ISO8859-15', 'fr_FR.ISO-8859-15', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
//	*	echo '$locale_is_set='.$locale_is_set._B;
//	*/
//	if ($locale_is_set){
//		$date=mktime(0,0,0,$month_num,1,2017); //date as int for '2017-$month_num-1 00:00:00'
//		$month =strftime($short?"%b":"%B",$date); //Abbr. month according to set locale i.e. 'Feb','févr.'
//		/* Alt. to get French abbr. month from month num:
//		*	//Create a DateTime object set to '1970-$month_num-01 00:00:00'
//		*	$dateObj = DateTime::createFromFormat('!m', $month_num);
//		*	$date= $dateObj->format("Y-m-d"); //Date as string. Alt: $date=$dateObj->date
//		*	$date=strtotime($date);  date as int, which is the only way to get to strftime
//		*	$month= strftime("%b",$date); //Abbr. month according to set locale
//		* //NB!! DateTime::format with $format('F') produces sometimes the wrong abbr. month:
//		*	$dateObj = DateTime::createFromFormat('!m', 2);
//		*	$month = $dateObj->format('F'); // i.e. Febr
//		*	echo strftime("%b",strtotime($month)); // March: wrong month!!!
//		*/
//	}
//	else{
//		if (!$short) {
//			$months_FR= array('janvier','février','mars',
//							  'avril','mai','juin',
//							  'juillet','août','septembre',
//							  'octobre','novembre','décembre');
//		}
//		else {
//			$months_FR=array('janv.','févr.','mars','avr.','mai','juin',
//							 'juil.','août','sept.','oct.','nov.','déc.');
//		}
//		$month= $months_FR[$month_num-1];
//	}
//	/* NB mb_convert_encoding($month, 'UTF-8','ISO-8859-15') will have to be used to
//	* to convert multibyte French diacriticals. But do not use 2 times on the same string!
//	* mb_check_encoding($month, 'ASCII')=false if diacritical char is present in string
//	* mb_check_encoding($month, 'UTF-8')=true if encoding UTF-8 (not the case if strftime() is used)
//	* see also https://stackoverflow.com/questions/16821534/check-if-is-multibyte-string-in-php
//	*/
//	//var_dump(mb_check_encoding($month, 'ASCII'));
//	//var_dump(mb_check_encoding($month, 'UTF-8'));
//	//if (mb_check_encoding($month, 'ASCII')&&mb_check_encoding($month, 'UTF-8')){
//
//	if ((!mb_check_encoding($month, 'ASCII'))&&(!mb_check_encoding($month, 'UTF-8'))){
//		$month=mb_convert_encoding($month, 'UTF-8','ISO-8859-15');
//	}
//	return $month;
//}