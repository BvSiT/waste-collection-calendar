
# The waste calendar web site: a PHP application

The waste calendar web application provides easily accessible information about the waste collection calendar for Verrières-le-Buisson, a commune – more or less the equivalent of a UK county or district - located some 15 km south of Paris with a population of about 15.000 inhabitants.

In this commune there exist five different types of house-to-house collections. Most of the waste types are characterized by the color of the used garbage bin. E.g. the dark green bin is reserved for household garbage as the yellow bin is intended for the collection of paper and plastics. These two waste types are collected weekly. The other waste types are organized by sector. The commune is divided in sectors. The location of an address within a certain sector will determine day and frequency of the collection. All the information is provided for in a [PDF flyer](http://www.verrieres-le-buisson.fr/IMG/pdf/calendrier_verrieres_2018_bdef-2.pdf) and also announced in the local monthly magazine.

For new residents it can take some time before one has a general idea of collection days and times. The purpose of my web application is to provide quick per-address up-to-date information about each waste collection. The full working web site can be found at [www.bvsit.nl/calendrierdecollecte](http://www.bvsit.nl/calendrierdecollecte)

<div id="wrapper" style="width:100%; text-align:center; margin-top:40px">

![](http://www.bvsit.nl/Images/ScreenshotCalendrierDeCollecte.png)

### Used techniques

The application is created with PHP. No framework like e.g. Symfony was used. Front-end techniques used were JavaScript, JQuery, Bootstrap and CSS. A MySQL database provides the waste collection info. Information was gathered from the PDF flyer and added to the database by parsing it once with PHP.

A simple form of dependency injection as described in [Learn PHP 7 by Steve Prettyman](https://www.amazon.com/Learn-PHP-Oriented-Programming-JavaScript/dp/1484217292) was applied in an adapted form. All objects are created by using a Container object which also handles all other dependencies. The application uses two central classes. Once the user has chosen an address a waste_col_calendar object is created which collects all the relevant information for the particular address: collection day and time for each waste type and first coming waste collections.

To create the page with the year calendar and coming events for each month a month_cal object is created. This object creates the HTML code to present the months. Collection dates are represented as colored dots.

### Predicting the waste collection calendar for 2018

For the year 2017 the collection dates were extracted from an information leaflet and saved to the database. After analyzing the regularities it was relatively easy to design PHP code to predict all collection dates for 2018, thanks to the PHP classes [DateInterval]( http://php.net/manual/en/class.dateinterval.php) and [DatePeriod](http://php.net/manual/en/class.dateperiod.php) The collection schedule changed considerably for 2018 but the application needed only some minor adaptation in the predicting patterns to reflect these changes. Saving the collection dates in the database was not necessary any more. The application will predict also all collecton dates for 2019 and after if there are no changes in the schedule.

The function weekday_pattern() returns an array with all dates on a certain weekday and conforming to a certain pattern as defined by several constants.

<div class="code">

<pre class="brush:php;gutter: false;">const WEEKLY=1;
const WEEK1AND3=2;//1st and 3th week of the month
const WEEK2AND4=3;//2nd and 4th week of the month
const LAST_IN_MONTH=4;
const SPECIAL_SACS=5;//weekly starting from april 2018, only 1st date in Dec. 
const EVEN_WEEKS=6; //1 time a week on even weeks during all year
const UNEVEN_WEEKS=7; //1 time a week on uneven weeks during all year

public static function weekday_pattern($weekday,$flag_pattern=5,$start='2017-01-01',$end='2018-01-01'){
</pre>

</div>

</div>

</div>
