<?php
	$localserver=false;
	if (isset($_SERVER)){
		if ($_SERVER['SERVER_NAME']=='localhost'){
			$localserver=true;	
		}
	}
	
	if ($localserver) {
		$host='localhost';
		$user='root';
		$password='';
		$dbname = 'wastecollection';
	}
	else {
		$host='DB_HOST';
		$user='DB_USERNAME';
		$password='DB_PASSWORD';
		$dbname = 'DB_NAME';
	}
	

	$variables = [
		'DB_HOST' => $host,
		'DB_USERNAME' => $user,
		'DB_PASSWORD' => $password,
		'DB_NAME' => $dbname
		//,'DB_PORT' => '3306',
	];

	foreach ($variables as $key => $value) {
		putenv("$key=$value");
	}
?>