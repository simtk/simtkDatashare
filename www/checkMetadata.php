<?php

// Get configuration parameters.
$arrDbConf = array();
$strConf = file_get_contents("/usr/local/mobilizeds/conf/mobilizeds.conf");
$jsonConf = json_decode($strConf, true);
foreach ($jsonConf as $key => $value) {
	if (is_array($value)) {
		if ($key == "postgres") {
			foreach ($value as $key => $val) {
				$arrDbConf[$key] = $val;
			}
		}
	}
}

// Check validity of configuration parameters.
if (!isset($arrDbConf["db"]) ||
	!isset($arrDbConf["user"]) ||
	!isset($arrDbConf["pass"])) {
	return;
}

// Check for valid value of studyid.
if (!isset($studyid) || 
	$studyid == false ||
	!is_numeric($studyid) ||
	$studyid <= 0) {
	return;
}

// Get db connection.
$db_connection = pg_connect("host=localhost " .
	"dbname=" . $arrDbConf["db"] . " " .
	"user=" . $arrDbConf["user"] . " " .
	"password=" . $arrDbConf["pass"]);

date_default_timezone_set('America/Los_Angeles');

$strQuery = "SELECT count(*) as cnt_metadata FROM study". $studyid . ".metadata";
$result = pg_query_params($db_connection, $strQuery, array());
$cntMetaData = 0;
while($theRow = pg_fetch_assoc($result)) {
	$cntMetaData = $theRow["cnt_metadata"];
}

pg_close($db_connection);

?>
