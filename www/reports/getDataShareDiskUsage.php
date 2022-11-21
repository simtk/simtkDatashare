<?php

/**
 * Copyright 2020-2022, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */


header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
ini_set('display_errors', 'on');
error_reporting(E_ALL);

include 'server-info.php';
include '../apps/browse/download/fileUtils.php';

// Get configuration parameters.
$arrDbConf = array();
$strConf = file_get_contents("/usr/local/mobilizeds/conf/mobilizeds.conf");
$conf = json_decode($strConf);

if ($_REQUEST['apikey'] == "$apikey" &&
	isset($_REQUEST['studyid'])) {

	date_default_timezone_set('America/Los_Angeles');

	$studyId = (int) $_REQUEST['studyid'];

	// Get directory information given study id.
	$fullPathName = $conf->data->docroot .
		"/study/study" . $_REQUEST["studyid"] . "/files";
	getDirInfo($fullPathName, $totalBytes, $lastModifiedTime);

	$res = array();
	$res["study_id"] = $_REQUEST["studyid"];
	$res["total_bytes"] = $totalBytes;
	$res["last_modified"] = $lastModifiedTime;

	echo json_encode($res);
}
else {
	echo "invalid key or studyid ";
}

?>

