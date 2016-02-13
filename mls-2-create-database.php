<?php

use Monolog\Handler\StreamHandler;

date_default_timezone_set('America/Phoenix');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
$log->pushHandler(new StreamHandler('output/mls-2-create-database.log'));

// Get config properties
$ini = parse_ini_file("ezrets.ini", true);
$rets_config = $ini['rets_config'];
$db_config = $ini['db_config'];

// Loop through csv file
$records = array();
$file = fopen('output/Property-A-fields.csv', 'r');

$header = fgetcsv($file);

// Create database
$dbusername = $db_config['db_username'];
$dbpassword = $db_config['db_password'];
$dburl = $db_config['db_url'];
$pdo = new PDO($dburl, $dbusername, $dbpassword);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbname = $db_config['db_name'];
$pdo->query("CREATE DATABASE IF NOT EXISTS $dbname");
$pdo->query("use $dbname");

while (($result = fgetcsv($file)) !== false)
{	
	$newresult = array();
	$i = 0;
	foreach($header as $field) {
		$newresult[$field] = $result[$i];
		$i = $i + 1;
	}
	
	$records[] = $newresult;
}

$fields = array();

foreach ($records as $record) {
	$field = array();
	if ($record['SystemName'] != null) {
		$field['name'] = $record['SystemName'];
		$field['comment'] = $record['LongName'];
		$field['unique'] = $record['Unique'];
		if ($record['DataType'] == 'Character') {
			$field['type'] = 'VARCHAR';
			$field['length'] = $record['MaximumLength'];
		} elseif ($record['DataType'] == 'DateTime') {
			$field['type'] = 'DATETIME';
		} elseif ($record['DataType'] == 'Int') {
			$field['type'] = 'INT';
		} elseif ($record['DataType'] == 'Boolean') {
			$field['type'] = 'BOOLEAN';
		} elseif ($record['DataType'] == 'Date') {
			$field['type'] = 'DATE';
		} elseif ($record['DataType'] == 'Decimal') {
			$field['type'] = 'DECIMAL';
			$field['precision'] = $record['MaximumLength'];
			$field['scale'] = $record['Precision'];
		}
		$fields[] = $field;
	}
	
}

createdbtable('residential', $fields, $pdo);

fclose($file);
$pdo = null;
echo "  - done<br>\n";

function createdbtable($table, $fields, $pdo) {
	$sql = "CREATE TABLE IF NOT EXISTS $table (";
	$pk = '';
	foreach($fields as $field)
	{
		$name = $field['name'];
		$type = $field['type'];
		$primarykey = ($field['unique'] == 1) ? 'PRIMARY KEY' : '';
		$comment = $pdo->quote($field['comment']);
		if ($type == 'VARCHAR') {
			$length = $field['length'];
			$sql.= "$name VARCHAR($length) $primarykey COMMENT $comment,";
		} elseif ($type == 'DECIMAL') {
			$scale = $field['scale'];
			$precision = $field['precision'];
			$sql.= "$name DECIMAL($precision, $scale) $primarykey COMMENT $comment,";
		} else {
			$sql.= "$name $type $primarykey COMMENT $comment,";
		}
	}
	$sql = rtrim($sql, ',');
	$sql .= ") CHARACTER SET utf8 COLLATE utf8_general_ci";
	var_dump($sql);
	$pdo->query($sql);
}

?>


