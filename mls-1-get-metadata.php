<?php

use Monolog\Handler\StreamHandler;

date_default_timezone_set('America/Phoenix');

require_once("vendor/autoload.php");

$log = new \Monolog\Logger('PHRETS');
$log->pushHandler(new StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
$log->pushHandler(new StreamHandler('output/mls-1-get-metadata.log'));

// Get config properties
$ini = parse_ini_file("ezrets.ini", true);

// DateTime which is used to determine how far back to retrieve records.
// using a really old date so we can get everything
$previous_start_time = "1980-01-01T00:00:00";

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

$log->addInfo('Connecting...');
$connect = $rets->Login();

$system = $rets->GetSystemMetadata();

$resources = $system->getResources();

$file_name_class = "output/mls_classes.csv";
$fh_class = fopen($file_name_class, "w");

$class_header = array("Resource","Class", "ClassName", "VisibleName", "StandardName", "Description", "TableVersion", "TableDate", "UpdateVersion", "UpdateDate", "ClassTimeStamp", "DeletedFlagField",
	"DeletedFlagValue", "HasKeyIndex", "Version", "Date", "Resource");
$class_fields_header = array("SystemName","StandardName", "LongName", "DBName", "ShortName", "MaximumLength", "DataType", "Precision", "Searchable", 
	"Interpretation", "Alignment", "UseSeparator", "EditMaskID", "LookupName", "MaxSelect", "Units", "Index", "Minimum", "Maximum", "Default",
	"Required", "SearchHelpID", "Unique", "MetadataEntryID", "ModTimeStamp", "ForeignKeyName", "ForeignField", "InKeyIndex", "Version", "Date",
	"Resource", "Class");

fputcsv($fh_class, $class_header);

foreach ($resources as $resource) {
	$resourceID = $resource->getResourceID();
	echo "Resource: " . $resourceID . "\n";
	$classes = $resource->getClasses();
	foreach ($classes as $class) {
		$class_data = array($resourceID,
			$class->getClass(),
			$class->getClassName(),
			$class->getVisibleName(),
			$class->getStandardName(),
			$class->getDescription(),
			$class->getTableVersion(),
			$class->getTableDate(),
			$class->getUpdateVersion(),
			$class->getUpdateDate(),
			$class->getClassTimeStamp(),
			$class->getDeletedFlagField(),
			$class->getDeletedFlagValue(),
			$class->getHasKeyIndex(),
			$class->getVersion(),
			$class->getDate(),
			$class->getResource());	
		fputcsv($fh_class, $class_data);
		
		$file_name_class_fields = "output/{$resourceID}-{$class->getClassName()}-fields.csv";
		$fh_class_fields = fopen($file_name_class_fields, "w");
		fputcsv($fh_class_fields, $class_fields_header);
		
		$fields = $class->getTable();
		foreach ($fields as $field) {
			$class_fields_data = array($field->getSystemName(),
				$field->getStandardName(),
				$field->getLongName(),
				$field->getDBName(),
				$field->getShortName(),
				$field->getMaximumLength(),
				$field->getDataType(),
				$field->getPrecision(),
				$field->getSearchable(),
				$field->getInterpretation(),
				$field->getAlignment(),
				$field->getUseSeparator(),
				$field->getEditMaskID(),
				$field->getLookupName(),
				$field->getMaxSelect(),
				$field->getUnits(),
				$field->getIndex(),
				$field->getMinimum(),
				$field->getMaximum(),
				$field->getDefault(),
				$field->getRequired(),
				$field->getSearchHelpID(),
				$field->getUnique(),
				$field->getMetadataEntryID(),
				$field->getModTimeStamp(),
				$field->getForeignKeyName(),
				$field->getForeignField(),
				$field->getInKeyIndex(),
				$field->getVersion(),
				$field->getDate(),
				$field->getResource(),
				$field->getClass());
			fputcsv($fh_class_fields, $class_fields_data);
		}
		fclose($fh_class_fields);
	}
}

fclose($fh_class);

echo "  - done<br>\n";

echo "+ Disconnecting<br>\n";
$rets->Disconnect();

?>


