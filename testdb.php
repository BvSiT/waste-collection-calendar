<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	const ERROR_LOG = "./logs/Errors.log";
	require_once('autoload.php');
	require_once('functions_wastecalendar.php');
	echo '<br>'.env('DB_NAME');
	echo '<br>'.env('DB_USERNAME');
	echo '<br>'.env('DB_HOST');
	echo 'start connect_mydb 15:11<br>';
	connect_mydb(true);
?>