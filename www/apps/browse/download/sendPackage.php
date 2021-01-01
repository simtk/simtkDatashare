<?php

include_once('../../../user/session.php');
include_once('../../../user/checkuser.php');
require_once("fileUtils.php");

$strConf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($strConf);

$theURL = $_SERVER['REQUEST_URI'];

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
	$tokenDownloadProgress = $_REQUEST["tokenDownloadProgress"];
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


// Generate package filename.
$pathPackage = false;
$strFileName = false;
if (isset($_GET["namePackage"])) {
	// Get path of package.
	$pathPackage = $_GET["namePackage"];
	$idx = strrpos($pathPackage, "/");
	if ($idx !== false) {
		// Get name of package.
		$strFileName = substr($pathPackage, $idx + 1);
	}
}

// Check path and name of package.
if ($pathPackage === false || $strFileName === false) {
	echo "<h1 class='text-primary'>Invalid package to download: $strFileName.</h1>";
	return;
}

$strFilePath = $conf->data->docroot. "/downloads/" . $pathPackage;

// Check validity of file to be downloaded.
$res = checkDownloadFile($strFilePath, $strFileName, $fileSize);
if ($res === false) {
	// Invalid file.
	echo "<h1 class='text-primary'>Invalid package to download: " . $strFileName . ".</h1>";
	return;
}

// Log download.
logStats($arrDbConf, $strFileName, $fileSize, 5);

// Download file.
sendFile($strFilePath, $strFileName, $tokenDownloadProgress);

?>


