<?php

date_default_timezone_set('America/Phoenix');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));

// Get config properties
$ini = parse_ini_file("ezrets.ini", true);
$rets_config = $ini['rets_config'];
$db_config = $ini['db_config'];

$file = fopen('data/property_a_data.csv', 'r');
$header = fgetcsv($file);

// Create database
$dbusername = $db_config['db_username'];
$dbpassword = $db_config['db_password'];
$dburl = $db_config['db_url'];
$pdo = new PDO($dburl, $dbusername, $dbpassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbname = $db_config['db_name'];
$pdo->query("use $dbname");


	$sql = "INSERT INTO RESIDENTIAL (";
	foreach($header as $field) {
		$sql.= "$field,";
	}
	$sql = rtrim($sql, ',');
	$sql.=") VALUES (";
	foreach($header as $field) {
		$sql.= "?,";
	}	
	$sql = rtrim($sql, ',');
	$sql.=")";
	
	$query = $pdo->prepare($sql);
	while (($result = fgetcsv($file)) !== false) {
		try {
			$query->execute($result);
			$count = $query->rowCount();
			echo "Count: " . $count . "\n";
		} catch (PDOException $e) {
			echo "Exception: " . $e;
		}
	}


?>


