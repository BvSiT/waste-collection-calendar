<?php

	include_once ("functions_wastecalendar.php");
	include_once ("WasteColCalendarContainer.php");
	include_once ("WasteColCalendar.php");
	function clean_input(){};
	define("_B","</BR>"); //debug
	
	define("_YEAR_CALENDAR",'2018');

echo __FILE__."</BR>";
echo "Last modified: ". date ("F d Y H:i:s.", filemtime(__FILE__))."</BR>";
echo NL(5);

/* Remove dates from DB
truncate_table('waste_col_date');
truncate_table('sector__waste_col_date');
exit;
*/

$reset_db=false;
$id_location = 270;

if ($reset_db) {
	truncate_table('sector');

	truncate_table('week_pattern');
	truncate_table('location');
	truncate_table('waste_type');
	truncate_table('location__waste_type');


	fill_db_waste_collection_streets();

	//fill_db_waste_collection_date_patterns();
	echo 'database wastecollection filled with waste collection data. _YEAR_CALENDAR ='._YEAR_CALENDAR._B;
}


echo NL(8);
echo 'test:'._B;


try {$w=new WasteColCalendar(array($id_location));}
catch (Exception $e) {
	echo __LINE__.$e->getMessage();
}
var_dump($w);

exit;

/*

***************************************************************************************************************

29-3-2018 BEGIN Views actually used in the application wastecalendar

1. v_waste_types
Used in WasteColCalendar.php:
	Line 300: 		$query="SELECT * FROM v_col_dates_smaller WHERE location_id=".$location_id;

DROP VIEW IF EXISTS v_waste_types;
CREATE VIEW v_waste_types AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id;

2. v_col_dates_smaller
Used in WasteColCalendar.php
	Line 405: 		$query='SELECT * FROM v_col_dates_smaller WHERE location_id='.$location_id;	

DROP VIEW IF EXISTS v_col_dates_smaller;
CREATE VIEW v_col_dates_smaller AS SELECT l.id AS location_id, w.code AS waste_type_code, wcd.date AS col_date FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN sector s ON lw.sector_id = s.id INNER JOIN sector__waste_col_date swd ON s.id=swd.sector_id INNER JOIN waste_col_date wcd ON swd.waste_col_date_id = wcd.id  ORDER BY col_date;	
	
3. v_week_patterns_small
Used in WasteColCalendar.php
	Line 325: 		$query="SELECT * FROM v_week_patterns_small WHERE location_id=".$location_id;

DROP VIEW IF EXISTS v_week_patterns_small;
CREATE VIEW v_week_patterns_small AS SELECT l.id AS location_id , w.code AS waste_type_code, w.name AS waste_type_name, wp.* FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN week_pattern wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_week_patterns_small v WHERE v.location_id=30

29-3-2018 END Views actually used in the application wastecalendar

*****************************************************************************************************************
DROP VIEW IF EXISTS v_waste_types_t1;
CREATE VIEW v_waste_types_t1 AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday, wp.PM AS week_pattern_PM FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id RIGHT JOIN week_pattern AS wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_waste_types_t1 AS v ORDER BY v.location_id ;

DROP VIEW IF EXISTS v_waste_types_t1;
CREATE VIEW v_waste_types_t1 AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.weekday AS sector_weekday, COALESCE(s.PM,wp.PM) AS PM FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id RIGHT JOIN week_pattern AS wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_waste_types_t1;

SELECT v_w.waste_type_code,l.name_street,l.type_street,l.house_nos, v_w.PM FROM v_waste_types_t1 AS v_w JOIN location AS l ON v_w.location_id=l.id ORDER BY l.id,v_w.waste_type_code WHERE l.id=5;

//OK
DROP VIEW IF EXISTS v_waste_types_t1;
CREATE VIEW v_waste_types_t1 AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday, wp.PM AS week_pattern_PM FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id LEFT JOIN week_pattern AS wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_waste_types_t1 AS v ORDER BY v.location_id ;

//OK
DROP VIEW IF EXISTS v_waste_types_t1;
CREATE VIEW v_waste_types_t1 AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday, COALESCE(wp.PM,s.PM) AS PM FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id LEFT JOIN week_pattern AS wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_waste_types_t1 AS v ORDER BY v.location_id ;

//OK
SELECT l.id, l.name_street, l.type_street, l.house_nos, v_w.waste_type_code , v_w.PM FROM v_waste_types_t1 AS v_w JOIN location AS l ON v_w.location_id = l.id ORDER BY l.id

//
SELECT l.id, l.name_street, l.type_street, l.house_nos, v_w.waste_type_code , v_w.PM FROM v_waste_types_t1 AS v_w JOIN location AS l ON v_w.location_id = l.id WHERE v_w.PM=1 ORDER BY l.id

***************************************************************************************************
29-3-18 Version application 2018 does not fill de DB with col dates but uses exclusively prediction.
This means that views which use tables with col dates had to be updated.

Function create_test_set() depends on the existence of the following views:

//v_waste_types should always exist for the applicaton to be able to work.

//OK and without col dates!
DROP VIEW IF EXISTS v_sectors_waste_types_2018;
CREATE VIEW v_sectors_waste_types_2018  AS  SELECT v_wt.location_id AS location_id,v_s.name AS sector_name,v_s.PM AS sector_PM,v_wt.waste_type_code AS waste_type_code from (v_waste_types AS v_wt join sector AS v_s on((v_wt.sector_name = v_s.name))) ;

//OK
DROP VIEW IF EXISTS v_t_grb_2018;
CREATE VIEW `v_t_grb_2018`  AS  select `v_sectors_waste_types_2018`.`location_id` AS `location_id`,`v_sectors_waste_types_2018`.`waste_type_code` AS `waste_type_code`,`v_sectors_waste_types_2018`.`sector_name` AS `sector_name` from `v_sectors_waste_types_2018` where (`v_sectors_waste_types_2018`.`waste_type_code` = 'GRB') order by `v_sectors_waste_types_2018`.`location_id` ;

//OK and shorter:
DROP VIEW IF EXISTS v_t_grb_2018;
CREATE VIEW v_t_grb_2018  AS SELECT v_s_wt.location_id AS location_id,v_s_wt.waste_type_code AS waste_type_code,v_s_wt.sector_name AS sector_name from v_sectors_waste_types_2018 AS v_s_wt where (v_s_wt.waste_type_code = 'GRB') order by v_s_wt.location_id ;

//OK and shorter:
DROP VIEW IF EXISTS v_t_sac_2018;
CREATE VIEW v_t_sac_2018  AS SELECT v_s_wt.location_id AS location_id,v_s_wt.waste_type_code AS waste_type_code,v_s_wt.sector_name AS sector_name from v_sectors_waste_types_2018 AS v_s_wt where (v_s_wt.waste_type_code = 'SAC') order by v_s_wt.location_id ;

//OK depends on VIEWS above
DROP VIEW IF EXISTS v_t_sector_info_2018;
CREATE VIEW v_t_sector_info_2018 AS select l.name_street AS name_street,l.type_street AS type_street,l.house_nos AS house_nos,sac.location_id AS location_id,sac.waste_type_code AS wtc_sac,sac.sector_name AS sn_sac,grb.waste_type_code AS wtc_grb,grb.sector_name AS sn_grb from ((location l join v_t_sac_2018 sac on((l.id = sac.location_id))) join v_t_grb_2018 grb on((sac.location_id = grb.location_id))) order by sac.location_id

*****************************************************************************************************************
21-1-2017 Extra views for test purposes (see also wc_test5.php)

v_sectors, v_sectors_waste_types are prerequisites for all v_t_ views:

//BEGIN

DROP VIEW IF EXISTS v_sectors;
CREATE VIEW v_sectors AS SELECT s.name AS sector_name, s.PM as sector_PM , wcd.date AS waste_col_date , WEEKDAY(wcd.date) AS weekday_col_date FROM sector as s LEFT JOIN sector__waste_col_date AS s_wcd ON s.id=s_wcd.sector_id JOIN waste_col_date AS wcd ON wcd.id=s_wcd.waste_col_date_id GROUP BY (sector_name);

DROP VIEW IF EXISTS v_sectors_waste_types;
CREATE VIEW v_sectors_waste_types AS SELECT v_wt.location_id, v_s.sector_name,v_s.sector_PM, v_s.weekday_col_date, v_s.waste_col_date, v_wt.waste_type_code FROM v_waste_types AS v_wt INNER JOIN v_sectors as v_s ON v_wt.sector_name=v_s.sector_name;

DROP VIEW IF EXISTS v_t_grb;
CREATE VIEW v_t_grb AS	
SELECT location_id,waste_type_code,sector_name FROM v_sectors_waste_types WHERE (waste_type_code='GRB') ORDER BY location_id;
DROP VIEW IF EXISTS v_t_sac;
CREATE VIEW v_t_sac AS	
SELECT location_id,waste_type_code,sector_name FROM v_sectors_waste_types WHERE (waste_type_code='SAC') ORDER BY location_id;
DROP VIEW IF EXISTS v_t_sector_info;
CREATE VIEW v_t_sector_info AS	
SELECT l.name_street,l.type_street,l.house_nos,sac.location_id, sac.waste_type_code as wtc_sac, sac.sector_name as sn_sac, grb.waste_type_code as wtc_grb, grb.sector_name as sn_grb  FROM location l  INNER JOIN v_t_sac sac ON l.id = sac.location_id INNER JOIN v_t_grb grb ON sac.location_id= grb.location_id ORDER BY sac.location_id;

//END


************************************************************************************************************************








*****************************************************************************************************************

$sql="SELECT w.date,s.name FROM waste_col_date w,sector s WHERE s.id = w.sector_id";
$sql="SELECT w.date,s.name FROM waste_col_date w INNER JOIN sector s ON s.id = w.sector_id";
$sql="SELECT s.name,d.date FROM sector s INNER JOIN sector__waste_col_date c ON s.id = c.sector_id INNER JOIN waste_col_date d ON c.waste_col_date_id = d.id";

SELECT l.name_street, w.name , wp.* FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN week_pattern wp ON lw.week_pattern_id = wp.id WHERE l.id=3

SELECT l.name_street, w.code , s.name , DATE_FORMAT(wcd.date, '%d-%m') FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN sector s ON lw.sector_id = s.id INNER JOIN sector__waste_col_date swd ON s.id=swd.sector_id INNER JOIN waste_col_date wcd ON swd.waste_col_date_id = wcd.id WHERE l.id=3

DROP VIEW IF EXISTS v_col_dates;
CREATE VIEW v_col_dates AS SELECT l.*, w.code , s.name , wcd.date AS col_date, DATE_FORMAT(wcd.date, '%d-%m') AS day_month FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN sector s ON lw.sector_id = s.id INNER JOIN sector__waste_col_date swd ON s.id=swd.sector_id INNER JOIN waste_col_date wcd ON swd.waste_col_date_id = wcd.id;
SELECT id AS location_id,type_street,name_street,min_house_no AS no_from,max_house_no AS no_to, v.code AS waste_type_code, col_date , day_month FROM v_col_dates v WHERE v.id=8


DROP VIEW IF EXISTS v_col_dates_small;
CREATE VIEW v_col_dates_small AS SELECT l.id AS location_id, w.code AS waste_type_code, w.name AS waste_type_name, s.name AS sector_name , wcd.date AS col_date FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN sector s ON lw.sector_id = s.id INNER JOIN sector__waste_col_date swd ON s.id=swd.sector_id INNER JOIN waste_col_date wcd ON swd.waste_col_date_id = wcd.id;



DROP VIEW IF EXISTS v_week_patterns;
CREATE VIEW v_week_patterns AS SELECT l.id AS location_id,l.type_street,l.name_street,l.min_house_no AS no_from,l.max_house_no AS no_to , w.code AS waste_type_code, wp.* FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN week_pattern wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_week_patterns v WHERE v.location_id=30

DROP VIEW IF EXISTS v_week_patterns_small;
CREATE VIEW v_week_patterns_small AS SELECT l.id AS location_id , w.code AS waste_type_code, w.name AS waste_type_name, wp.* FROM location l INNER JOIN location__waste_type lw ON l.id = lw.location_id INNER JOIN waste_type w ON lw.waste_type_id=w.id INNER JOIN week_pattern wp ON lw.week_pattern_id = wp.id;
SELECT * FROM v_week_patterns_small v WHERE v.location_id=30

SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, s.name AS sector_name , s.name FROM waste_type w JOIN location__waste_type lw JOIN sector s ON lw.sector_id=s.id WHERE lw.location_id=4

SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, s.name AS sector_name FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id WHERE lw.location_id=4

//21-1-17 added info
DROP VIEW IF EXISTS v_waste_types;
CREATE VIEW v_waste_types AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, w.info AS waste_type_info, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id;
SELECT * FROM v_waste_types WHERE location_id=80;

//6-12-17 added sector_weekday
DROP VIEW IF EXISTS v_waste_types;
DROP VIEW IF EXISTS v_waste_types;
CREATE VIEW v_waste_types AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, s.name AS sector_name, s.PM AS sector_PM, s.weekday AS sector_weekday FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id;
SELECT * FROM v_waste_types WHERE location_id=80;

//26-11-17 added sector_PM
DROP VIEW IF EXISTS v2_waste_types;
DROP VIEW IF EXISTS v_waste_types;
CREATE VIEW v_waste_types AS SELECT lw.location_id, w.code AS waste_type_code, w.name AS waste_type_name, s.name AS sector_name, s.PM AS sector_PM FROM location__waste_type lw JOIN waste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id



DROP VIEW IF EXISTS v_sectors;
CREATE VIEW v_sectors AS SELECT s.name AS sector_name, s.PM as sector_PM , wcd.date AS waste_col_date , WEEKDAY(wcd.date) AS weekday_col_date FROM sector as s LEFT JOIN sector__waste_col_date AS s_wcd ON s.id=s_wcd.sector_id JOIN waste_col_date AS wcd ON wcd.id=s_wcd.waste_col_date_id GROUP BY (sector_name);

DROP VIEW IF EXISTS v_sectors_waste_types;
CREATE VIEW v_sectors_waste_types AS SELECT v_wt.location_id, v_s.sector_name,v_s.sector_PM, v_s.weekday_col_date, v_s.waste_col_date, v_wt.waste_type_code FROM v_waste_types AS v_wt INNER JOIN v_sectors as v_s ON v_wt.sector_name=v_s.sector_name;
SELECT * FROM v_sectors_waste_types WHERE location_id=9 

SELECT v_wt.waste_type_code,v_wt.sector_name,v_wt.sector_PM, v_s.weekday_col_date FROM v_waste_types AS v_wt INNER JOIN v_sectors as v_s ON v_wt.sector_name=v_s.sector_name WHERE location_id=7





//See also:
SELECT * FROM v_waste_types WHERE sector_PM IS NOT NULL

//To show the SQL of a view. NB!! Under Options enable full text
SHOW CREATE VIEW 'v_col_dates_smaller'

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_col_dates_smaller` AS select `l`.`id` AS `location_id`,`w`.`code` AS `waste_type_code`,date_format(`wcd`.`date`,'%d-%m-%y') AS `col_date` from (((((`location` `l` join `location__waste_type` `lw` on((`l`.`id` = `lw`.`location_id`))) join `waste_type` `w` on((`lw`.`waste_type_id` = `w`.`id`))) join `sector` `s` on((`lw`.`sector_id` = `s`.`id`))) join `sector__waste_col_date` `swd` on((`s`.`id` = `swd`.`sector_id`))) join `waste_col_date` `wcd` on((`swd`.`waste_col_date_id` = `wcd`.`id`)))

DROP VIEW IF EXISTS v_col_dates_smaller2;
DROP VIEW IF EXISTS v_col_dates_smaller;
CREATE VIEW `v_col_dates_smaller` AS select `l`.`id` AS `location_id`,`w`.`code` AS `waste_type_code`,date_format(`wcd`.`date`,'%d-%m-%y') AS `col_date`, wcd.date AS col_date_as_date from (((((`location` `l` join `location__waste_type` `lw` on((`l`.`id` = `lw`.`location_id`))) join `waste_type` `w` on((`lw`.`waste_type_id` = `w`.`id`))) join `sector` `s` on((`lw`.`sector_id` = `s`.`id`))) join `sector__waste_col_date` `swd` on((`s`.`id` = `swd`.`sector_id`))) join `waste_col_date` `wcd` on((`swd`.`waste_col_date_id` = `wcd`.`id`))) ORDER BY col_date_as_date

SELECT lw.sector_id FROM sector__waste_col_date sd JOIN col_datewaste_type w ON lw.waste_type_id=w.id  LEFT JOIN sector s ON s.id=lw.sector_id WHERE lw.location_id=76

https://github.com/openvenues/libpostal




exit;
//test();exit;
exit;
*/





?>

</body>

</html>

