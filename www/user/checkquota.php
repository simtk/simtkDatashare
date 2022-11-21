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

require_once "checkDiskQuota.php";

$conf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($conf);
include 'server.php';

if (isset($_REQUEST['userid']) && $_REQUEST['userid']) {
	$userid = (int) $_REQUEST['userid'];
}
else {
	$userid = false;
}
if (isset($_REQUEST['token']) && $_REQUEST['token']) {
	$token = htmlspecialchars($_REQUEST['token']);
}
else if (isset($_SESSION['token']) && $_SESSION['token']) {
	$token = htmlspecialchars($_SESSION['token']);
}
else {
	$token = false;
}
if (isset($_REQUEST['studyid']) && $_REQUEST['studyid']) {
	$studyid = (int) $_REQUEST['studyid'];
}
else {
	$studyid = false;
}
if (isset($_REQUEST['groupid']) && $_REQUEST['groupid']) {
	$groupid = (int) $_REQUEST['groupid'];
}
else {
	$groupid = false;
}
if (isset($_REQUEST['section']) && $_REQUEST['section']) {
	$section = htmlspecialchars($_REQUEST['section']);
}
else {
	$section = false;
}

// Check validity of user and study. Retrieve information from study.
$ok_diskusage = false;
$total_bytes = false;
$allowed_bytes = false;
$isStudyValid = checkDiskQuota($domain_name, 
	$api_key,
	$userid,
	$token,
	$studyid,
	$groupid,
	$section,
	$response_study);
if ($isStudyValid) {
	// Total disk usage is ok.
	$ok_diskusage = $response_study->ok_diskusage;
	$total_bytes = $response_study->total_bytes;
	$allowed_bytes = $response_study->allowed_bytes;
}

// Send JSON-encoded result.
$theResult = array();
$theResult["status"] = $isStudyValid;
$theResult["ok_diskusage"] = $ok_diskusage;
$theResult["total_bytes"] = $total_bytes;
$theResult["allowed_bytes"] = $allowed_bytes;
echo json_encode($theResult);

?>
