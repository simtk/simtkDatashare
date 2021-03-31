<?php

/**
 * Copyright 2020-2021, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

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
