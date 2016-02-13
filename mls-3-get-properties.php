<?php

date_default_timezone_set('America/Phoenix');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));

// Get config properties
$ini = parse_ini_file("ezrets.ini", true);

// use http://retsmd.com to help determine the SystemName of the DateTime field which
// designates when a record was last modified
$rets_modtimestamp_field = "LIST_87";

// use http://retsmd.com to help determine the names of the classes you want to pull.
// these might be something like RE_1, RES, RESI, 1, etc.
$property_classes = array("A");

// DateTime which is used to determine how far back to retrieve records.
// using a really old date so we can get everything
$previous_start_time = "2016-02-12T00:00:00";
//$previous_start_time = "2016-01-01T00:00:00";

$config = new \PHRETS\Configuration;

$rets_config = $ini['rets_config'];
$config->setLoginUrl($rets_config['loginurl']);
$config->setUsername($rets_config['username']);
$config->setPassword($rets_config['password']);
$config->setRetsVersion($rets_config['rets-version']);

$config->setHttpAuthenticationMethod('digest');
$config->setOption('disable_follow_location', false);
$config->setOption('use_post_method', true);

$rets = new \PHRETS\Session($config);
$rets->setLogger($log);

$connect = $rets->Login();

$file = fopen('output/Property-A-fields.csv', 'r');
$header = fgetcsv($file);
$fields = array();
while (($result = fgetcsv($file)) !== false) {
	$fields[] = $result[0];
}

foreach ($property_classes as $class) {

        echo "+ Property:{$class}<br>\n";

        $file_name = strtolower("data/property_{$class}_data.csv");
        $fh = fopen($file_name, "w+");
		fputcsv($fh, $fields);
	
        $maxrows = true;
        $offset = 1;
        $limit = 500;
        $fields_order = array();
		$resource = "Property";

		$query = "({$rets_modtimestamp_field}={$previous_start_time}+)";
		
		while ($maxrows) {
			// run RETS search
			echo "   + Query: {$query}  Limit: {$limit}  Offset: {$offset}<br>\n";
			$results = $rets->Search(
				$resource,
				$class,
				$query,
				[
					'QueryType' => 'DMQL2',
					'Count' => 1, // count and records
					'Format' => 'COMPACT-DECODED',
					'Limit' => $limit,
					'Offset' => $offset,
					'StandardNames' => 0, // give system names
				]
			);
			
			$properties = array();
			
			foreach ($results as $record) {
				$property = array();
				foreach ($fields as $field) {
					$property[$field] = $record[$field];
				}
				$properties[] = $property; 
				fputcsv($fh, $property);
			}
			
			// update offset
			$offset = ($offset + count($results));
			echo 'offset is now ' . $offset;
			
			$maxrows = $results->isMaxRowsReached();
		
		}
		


		
		var_dump($properties);
		
        fclose($fh);

        echo "  - done<br>\n";

}

echo "+ Disconnecting<br>\n";
$rets->Disconnect();

?>


