<?php

date_default_timezone_set('America/Phoenix');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
$shortopts = "";
$shortopts .= "f:";
$options = getopt($shortopts);

$filename = $options['f'];
$filelocation = 'data/' . $filename;
echo ("File location: " . $filelocation);
var_dump($options);

// Get config properties
$ini = parse_ini_file("ezrets.ini", true);
$rets_config = $ini['rets_config'];
$db_config = $ini['db_config'];

$file = fopen($filelocation, 'r');
$header = fgetcsv($file);
$file_name_errors = "output/importerrors.csv";
$fh_errors = fopen($file_name_errors, "w");

// Create database
$dbusername = $db_config['db_username'];
$dbpassword = $db_config['db_password'];
$dburl = $db_config['db_url'];
$pdo = new PDO($dburl, $dbusername, $dbpassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbname = $db_config['db_name'];
$pdo->query("use $dbname");


	$sql = "INSERT INTO residential (";
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
			$sqlupdate = "UPDATE residential SET ";
			foreach($header as $field) {
				$sqlupdate.= " $field = ?,";
			}
			$sqlupdate = rtrim($sqlupdate, ',');
			$sqlupdate.= " WHERE LIST_1 = $result[0] ";
			echo "SQL Update: " . $sqlupdate . "\n";
			$queryupdate = $pdo->prepare($sqlupdate);
			try {
				$queryupdate->execute($result);
				$countupdate = $queryupdate->rowCount();
				echo "Update count: " . $countupdate . "\n";
			} catch (PDOException $e) {
				fputcsv($fh_errors, $result);
				echo "Exception: " . $e;			
			}
		}
	}


?>


