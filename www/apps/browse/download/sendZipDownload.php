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

function echoJsonMsg($tokenDownloadProgress, $status, $reason) {
	$theResult = array();
	$theResult["status"] = $status;
	$theResult["reason"] = $reason;
	echo json_encode($theResult);

	// Update status in token file.
	$fp = fopen("/var/www/apps/browse/download/tokens/" . $tokenDownloadProgress, "w+");
        fwrite($fp, $status . ": " . $reason . "\n");
        fflush($fp);
        fclose($fp);
}

$strConf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($strConf);


$studyId = 0;
if (isset($_SESSION["study_id"])) {
	$studyId = (int) $_SESSION["study_id"];
}

$userId = 0;
if (isset($_SESSION["userid"])) {
	$userId = (int) $_SESSION["userid"];
}

// Validates user permission.
if (!$perm || $studyId == 0) {
	echoJsonMsg($tokenDownloadProgress, 
		"failed", 
		"Your permissions do not allow access to this study.");
	return;
}

if (!class_exists("ZipArchive")) {
	// ZipArchive class is not present. Cannot generate zip file
	echoJsonMsg($tokenDownloadProgress, 
		"failed", 
		"Missing ZipArchive class.");
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
	echoJsonMsg($tokenDownloadProgress, 
		"failed", 
		"Invalid db configuration.");
	return;
}

// Generate download filename.
$theFilesHash = $_REQUEST['filesHash'];

$tmpArr = json_decode($_REQUEST["filesHash"]);
if (is_array($tmpArr) && count($tmpArr) > 0) {
	$filesHash = $tmpArr[0];
}
else {
	// Files hash is not present.
	echoJsonMsg($tokenDownloadProgress, 
		"failed", 
		"Missing files parameter.");
	return;
}

$volumeId = "l1_";
$pathBase = $conf->data->docroot . "/study/study" . $studyId . "/files/";
$arrFilesToAdd = array();
foreach ($filesHash as $key=>$fileHash) {
	if (strpos($fileHash, $volumeId) === 0) {
		// Remove volume id.
		$theHash = substr($fileHash, strlen($volumeId));
	}
	else {
		// Volume id is always the prefix.
		// If not, hash is invalid. Do not proceed.
		echoJsonMsg($tokenDownloadProgress, 
			"failed", 
			"Volume ID not found.");
		return;
	}

	// Decode hash to get file path.
	$fileDecoded = base64_decode(strtr($theHash, '-_.', '+/='));
	$fullPath = $pathBase . $fileDecoded;

	if (!file_exists($fullPath)) {
		// File does not exist. Ignore.
		continue;
	}

	if (is_file($fullPath)) {
		// Found a file.
		// Add file to result.
		$arrFilesToAdd[$fullPath] = $fileDecoded;
	}
	else if (is_dir($fullPath)) {
		// Found a directory.
		// Find all files in directory to add to result.
		findFilesInDir($fullPath, $arrFilesToAdd, $fileDecoded);
	}
}

// Get total bytes to add.
$bytesAllFiles = 0;
foreach ($arrFilesToAdd as $theFullPath=>$theFileName) {
	$bytesAllFiles += filesize($theFullPath);
}

// User is logged in (i.e. not anonymous user).
// If zip file to generate is large, email the zip file to user.
if ($userId > 0 &&
	$bytesAllFiles > MAXBYTES_ALL_FILES) {

	// Calculate zip file size.
	if (floor($bytesAllFiles / 1000000000) > 0) {
		$strFileSize = floor($bytesAllFiles / 1000000000) . "GB";
	}
	else if (floor($bytesAllFiles / 1000000) > 0) {
		$strFileSize = floor($bytesAllFiles / 1000000) . "MB";
	}

	// Send message and stop.
	// Let cronjob handle zip file generation.
	echoJsonMsg($tokenDownloadProgress, 
		"zip_too_big", 
		"A large zip file (" . 
		$strFileSize . 
		") will be generated.<br/>When it is ready, an email will be sent to " .
		$_SESSION["email"] . 
		".");

	// Record entry for cronjob.
	recordZipFileEntry($arrDbConf,
		$_SESSION['group_id'],
		$_SESSION['study_id'],
		$_SESSION['userid'],
		$_SESSION['token'],
		urlencode(urldecode($_REQUEST['filesHash'])),
		$_SESSION['email']);

	return;
}


// Generate a randomized directory name.
$nameRandDir = genRandDirName();
$dirBase = $conf->data->docroot . "/downloads/" . $nameRandDir . "/";
if (!mkdir($dirBase)) {
	// Cannot create directory.
	echoJsonMsg($tokenDownloadProgress, 
		"failed", 
		"Cannot create directory: " . $nameRandDir . ".");
	return;
}
$strFileName = "study" . $studyId . "-" . date("Y-m-d-H-i") . ".zip";
$strFilePath = $dirBase . $strFileName;

// Generate a zip file of contents.
$zipFile = new ZipArchive();
$numFiles = count($arrFilesToAdd);
$cntFiles = 0;
foreach ($arrFilesToAdd as $theFullPath=>$theFileName) {

	$theFileSize = filesize($theFullPath);
	if (floor($theFileSize / 1000000000) > 0) {
		$strFileSize = floor($theFileSize / 1000000000) . "GB";
	}
	else if (floor($theFileSize / 1000000) > 0) {
		$strFileSize = floor($theFileSize / 1000000) . "MB";
	}
	else if (floor($theFileSize / 1000) > 0) {
		$strFileSize = floor($theFileSize / 1000) . "KB";
	}
	else {
		$strFileSize = $theFileSize . "bytes";
	}

	// Progress of zip file addition.
	$cntFiles++;
	$fp = fopen("/var/www/apps/browse/download/tokens/" . $tokenDownloadProgress, "w+");
	fwrite($fp, "preparing $cntFiles of $numFiles ($strFileSize)\n");
	fflush($fp);
	fclose($fp);

	if (file_exists($strFilePath)) {
		// Zip file exists; open it for appending.
		if ($zipFile->open($strFilePath) === true) {
			// Insert file into zip file.
			$zipFile->addFile($theFullPath, $theFileName);
			$zipFile->close();
		}
		else {
			// Error. Cannot open zip file.
			echoJsonMsg($tokenDownloadProgress, 
				"failed", 
				"Cannot open zip file: " . $strFileName . ".");
			return;
		}
	}
	else {
		// Zip file does not exist; create it.
		if ($zipFile->open($strFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
			// Insert file into zip file.
			$zipFile->addFile($theFullPath, $theFileName);
			$zipFile->close();
		}
		else {
			// Error. Cannot open zip file.
			echoJsonMsg($tokenDownloadProgress, 
				"failed", 
				"Cannot create zip file: " . $strFileName . ".");
			return;
		}
	}
}

// Done with zip file generation.
$theResult = array();
$theResult["status"] = "ok";
$theResult["pathDownload"] = "downloads/" . $nameRandDir;
$theResult["nameDownload"] = $strFileName;
echo json_encode($theResult);
return;


// Recursively find all files in directory and add to array.
// Accumulate subpath along the way.
function findFilesInDir($inDir, &$arrFilesToAdd, $subPath="") {

	if (!is_dir($inDir)) {
		// Not a directory. Ignore.
		return;
	}

	// Look up all contents in directory.
	$theContents = scandir($inDir);
	foreach ($theContents as $key=>$val) {

		// Ignore "." and ".." retrieved.
		if (in_array($val, array(".", ".."))) {
			continue;
		}

		// Generate path to file or directory.
		$thePath = $inDir . "/" . $val;
		if (is_file($thePath)) {
			// Found a file.
			// Add file to result.
			$arrFilesToAdd[$thePath] = $subPath . "/" . $val;
		}
		else if (is_dir($thePath)) {
			// Found a directory.
			// Find all files in directory to add to result.
			findFilesInDir($thePath, $arrFilesToAdd, $subPath . "/" . $val);
		}
	}
}

?>



