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

if (!class_exists("ZipArchive")) {
	// ZipArchive class is not present. Cannot generate zip file
	echo "<h1 class='text-primary'>Missing ZipArchive class.</h1>";
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

// Generate download filename.
$theFilesHash = $_REQUEST['filesHash'];

$tmpArr = json_decode($_REQUEST["filesHash"]);
if (is_array($tmpArr) && count($tmpArr) > 0) {
	$filesHash = $tmpArr[0];
}
else {
	// Files hash is not present.
	echo "<h1 class='text-primary'>Missing files parameter.</h1>";
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
		echo "<h1 class='text-primary'>Volume ID not found.</h1>";
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

// Generate a randomized directory name.
$nameRandDir = genRandDirName();
$dirBase = $conf->data->docroot . "/downloads/" . $nameRandDir . "/";
if (!mkdir($dirBase)) {
	// Cannot create directory.
	echo "<h1 class='text-primary'>Cannot create directory: " . $nameRandDir . ".</h1>";
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
			echo "<h1 class='text-primary'>Cannot open zip file: " . $strFileName . ".</h1>";
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
			echo "<h1 class='text-primary'>Cannot create zip file: " . $strFileName . ".</h1>";
			return;
		}
	}
}

// Check validity of file to be downloaded.
$res = checkDownloadFile($strFilePath, $strFileName, $fileSize);
if ($res === false) {
	// Invalid file.
	echo "<h1 class='text-primary'>Invalid file to download: " . $strFileName . ".</h1>";
	return;
}

// Log download.
logStats($arrDbConf, $strFileName, $fileSize, 3);

// Download file.
sendFile($strFilePath, $strFileName, $tokenDownloadProgress);


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

// Generate a randomized directory name.
function genRandDirName() {

	$arrChars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
	$cntChars = count($arrChars);

	$nameRandDir = "";
	for ($cnt=0; $cnt<8; $cnt++) {
		$nameRandDir .= $arrChars[rand(0, $cntChars - 1)];
	}

	return $nameRandDir;
}

?>



