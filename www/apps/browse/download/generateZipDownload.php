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

require_once('/var/www/user/server.php');
require_once("fileUtils.php");

function sendMsgZipFileCreation($status, $reason) {
	echo date("Y-m-d H:i:s") . ": $status: $reason\n";
}

if (!class_exists("ZipArchive")) {
	// ZipArchive class is not present. Cannot generate zip file
	sendMsgZipFileCreation("failed", "Missing ZipArchive class.");
	return;
}

// Get configuration parameters.
$strConf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($strConf);
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
	sendMsgZipFileCreation("failed", "Invalid db configuration.");
	return;
}



// Check whether there is zip file in progress.
$cntZipInProgress = countZipFileInProgress($arrDbConf, $startDate);
if ($cntZipInProgress > 0) {
	$now = time();
	$duration = $now - $startDate;
	// If zip file creation took more than 5 minutes, notify webmaster.
	if ($duration > 300) {
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		$headers[] = 'From: noreply@' . $domain_name;
		$emailAdmin = "webmaster@" . $domain_name;
		$theMsgBody = "Zip file generation in progress has taken more than $duration seconds.";
		mail($emailAdmin, 'A zip file for download is being generated.',
			$theMsgBody, 
			implode("\r\n", $headers));

		sendMsgZipFileCreation("ok", "Zip file generation in progress has taken more than $duration seconds.");
	}

	// Zip file creation is in progress. Do not proceed.
	return;
}

// Get zipfile id, study id and files hash of next zipfile to be created.
$zipfileId = false;
$groupId = false;
$studyId = false;
$userId = false;
$token = false;
$strFilesHash = false;
$email = false;
$status = getNextZipFileEntry($arrDbConf, 
	$zipfileId, 
	$groupId, 
	$studyId, 
	$userId, 
	$token, 
	$strFilesHash,
	$email);
if ($status == false) {
	// Done. No new entry available for processing.
	return;
}


// Record start time of zipfile creation.
$status = logZipFileStart($arrDbConf, $zipfileId);
if ($status == false) {
	sendMsgZipFileCreation("failed", "Cannot update start time: $zipfileId.");
	return;
}

// NOTE: files hash has to be url-decoded.
$strFilesHash = urldecode($strFilesHash);
$tmpArr = json_decode($strFilesHash);
if (is_array($tmpArr) && count($tmpArr) > 0) {
	$filesHash = $tmpArr[0];
}
else {
	// Files hash is not present.
	logZipFileError($arrDbConf, $zipfileId, -1);
	sendMsgZipFileCreation("failed", "Missing files parameter.");
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
		logZipFileError($arrDbConf, $zipfileId, -2);
		sendMsgZipFileCreation("failed", "Volume ID not found.");
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
	logZipFileError($arrDbConf, $zipfileId, -3);
	sendMsgZipFileCreation("failed", "Cannot create directory: " . $nameRandDir . ".");
	return;
}
$strFileName = "study" . $studyId . "-" . date("Y-m-d-H-i") . ".zip";
$strFilePath = $dirBase . $strFileName;

// Get total bytes to add.
$bytesAllFiles = 0;
foreach ($arrFilesToAdd as $theFullPath=>$theFileName) {
	$bytesAllFiles += filesize($theFullPath);
}

// Generate a zip file of contents.
$zipFile = new ZipArchive();
if (!$zipFile->open($strFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true) {
	// Error. Cannot create zip file.
	logZipFileError($arrDbConf, $zipfileId, -4);
	sendMsgZipFileCreation("failed", "Cannot create zip file: " . $strFileName . ".");
	return;
}
$numFiles = count($arrFilesToAdd);
$sumFileSize = 0;
foreach ($arrFilesToAdd as $theFullPath=>$theFileName) {

	$sumFileSize += filesize($theFullPath);

	// Insert file into zip file.
	$zipFile->addFile($theFullPath, $theFileName);
	$zipFile->setCompressionName($theFullPath, ZipArchive::CM_STORE);
}

if (floor($sumFileSize / 1000000000) > 0) {
	$strSumFileSize = floor($sumFileSize / 1000000000) . "GB";
}
else if (floor($sumFileSize / 1000000) > 0) {
	$strSumFileSize = floor($sumFileSize / 1000000) . "MB";
}
else if (floor($sumFileSize / 1000) > 0) {
	$strSumFileSize = floor($sumFileSize / 1000) . "KB";
}
else {
	$strSumFileSize = $sumFileSize . "bytes";
}


$zipFile->close();

// Record stop time of zip file creation.
$status = logZipFileStop($arrDbConf, $zipfileId, $strFilePath, $strFileName);
if ($status == false) {
	sendMsgZipFileCreation("failed", "Cannot update stop time: $zipfileId.");
	return;
}


$urlDownload = "https://" . $domain_name .
	"/plugins/datashare/view.php?" .
	"section=datashare&" .
	"groupid=" . $groupId . "&" .
	"id=" . $groupId . "&" .
	"studyid=" . $studyId . "&" .
	"userid=" . $userId . "&" .
	"token=" . $token . "&" .
	"&namePackage=" . $nameRandDir . "/" . $strFileName;

$theMsgBody = 'Helllo' .
	'<br/><br/>' .
	'A zip file is ready for download by <a href="' .
	$urlDownload .
	'">clicking this link</a>. ' .
	'You may need to copy-and-paste the link into your browser. ' .
	'<br/><br/>' .
	'Note: if the zip file does not download, make sure you have allowed pop-ups. In most browsers, you can do this by selecting the small icon to "allow pop-ups..." in the URL bar. Once pop-ups are allowed, click the link again to download the zip file.' .
	'<br/><br/>' .
	'The link will expire in 2 days.' .
	'<br/><br/>' .
	'Best regards,' .
	'<br/>' .
	'The SimTK Team';

// Send email to user.
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=iso-8859-1';
$headers[] = 'From: noreply@' . $domain_name;
mail($email, 'A zip file is ready for download',
	$theMsgBody, 
	implode("\r\n", $headers));

// Send zip file creation complete message.
sendMsgZipFileCreation("ok", 
	$userId . " " . $email . ": " . 
	$nameRandDir . "/" . $strFileName . " " .
	"(" . $strSumFileSize . ")");

// Done with zip file generation.
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


