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

include_once('../../../user/session.php');
include_once('../../../user/checkuser.php');
require_once("fileUtils.php");

$strConf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($strConf);

$studyId = 0;
if (isset($_SESSION["study_id"])) {
	$studyId = (int) $_SESSION["study_id"];
}

// Validates user permission.
if (!$perm || $studyId == 0) {
	echo "<h1 class='text-primary'>Your permissions do not allow access to this study.</h1>";
	return;
}

// Get token filename for keeping tracking of download progress.
$tokenDownloadProgress = false;
if (isset($_REQUEST["tokenDownloadProgress"])) {
	$tokenDownloadProgress = htmlspecialchars($_REQUEST["tokenDownloadProgress"]);
}

// Get configuration parameters.
$arrDbConf = array();
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
	// Invalid db configuration.
	echo "<h1 class='text-primary'>Invalid db configuration.</h1>";
	return;
}

// Check download filename.
$theDownload = htmlspecialchars($_REQUEST['nameDownload']);
$tmpName = preg_replace("/[-A-Z0-9+_\. ~\/]/i", "", $theDownload);
if (!empty($tmpName) || strstr($theDownload, "..")) {
	// Invalid filename.
	echo "<h1 class='text-primary'>Invalid filename.</h1>";
	return;
}
// Get download filename.
$idx = strrpos($theDownload, "/");
if ($idx !== false) {
	// Get file name from path.
	$strFront = substr($theDownload, 0, $idx);
	$strFileName = substr($theDownload, $idx + 1);
}
else {
	// Only file name is present.
	$strFront = "";
	$strFileName = $theDownload;
}

// Get path.
$strFilePath = false;
if (isset($_REQUEST["pathDownload"])) {
	$dirDownload = $conf->data->docroot . "/" . 
		htmlspecialchars($_REQUEST["pathDownload"]);
}
else {
	$dirDownload = $conf->data->docroot. "/" . 
		"study/study" . $studyId . "/files";
}
// Check path.
if (!preg_match('/^[a-z\/0-9]+$/i', $dirDownload)) {
	// Invalid path.
	echo "<h1 class='text-primary'>Invalid directory.</h1>";
	return;
}
$strFilePath = $dirDownload . $strFront . "/" . $strFileName;

// Check validity of file to be downloaded.
$res = checkDownloadFile($strFilePath, $theDownload, $fileSize);
if ($res === false) {
	// Invalid file.
	echo "<h1 class='text-primary'>Invalid file to download: " . $theDownload . ".</h1>";
	return;
}

// Log download.
logStats($arrDbConf, $strFileName, $fileSize, 3);

// Download file.
sendFile($strFilePath, $strFileName, $tokenDownloadProgress);

?>



