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

// Send file to browser.
function sendFile($filePath, $fileName, $tokenDownloadProgress=false) {

	if (!file_exists($filePath)) {
		// File does not exist.
		return;
	}

	header('Content-disposition: attachment; filename="'.
		str_replace('"', '', $fileName) .
		'"');
	header("Content-type: application/binary");

	// Get file size.
	$theFileSize = filesize($filePath);
	header("Content-length: $theFileSize");

	// Note: ob_clean() and flush() are needed here!!!
	// Otherwise, the zip file downloaded to MAC cannot be opened.
	// See: http://stackoverflow.com/questions/23668293/php-dynamically-generated-zip-file-by-ziparchive-cant-be-opened
	// See: http://stackoverflow.com/questions/19963382/php-zip-file-download-error-when-opening
	ob_clean();
	flush();

	// Read and send file in chunks.
	$status = sendFileChunked($filePath, $theFileSize, $tokenDownloadProgress);

	if ($tokenDownloadProgress !== false) {
		// Token file is used for tracking download progress.
		$fp = fopen("/var/www/apps/browse/download/tokens/" . $tokenDownloadProgress, "w+");
		fwrite($fp, "done\n");
		fclose($fp);
	}
}


// Send file in chunks.
function sendFileChunked($fileName, $fileSize, $tokenDownloadProgress=false) {

	// Report download progress every 20MB.
	$lastCounterChange = 0;
	$thresholdReport = 20*(1024*1024);

	// 1MB chunks
	$chunksize = 1*(1024*1024);

	$buffer = '';
	$byteCounter = 0;

	$handle = fopen($fileName, 'rb');
	if ($handle === false) {
		return false;
	}

	ob_start();
	while (!feof($handle)) {
		$buffer = fread($handle, $chunksize);
		echo $buffer;
		ob_flush();
		flush();
		$byteCounter += strlen($buffer);

		if ($tokenDownloadProgress !== false) {
			// Token file is used for tracking download progress.
			if ($byteCounter - $lastCounterChange > $thresholdReport) {
				$fp = fopen("/var/www/apps/browse/download/tokens/" . $tokenDownloadProgress, "w+");
				fwrite($fp, ((int) ($byteCounter * 100 / $fileSize)) . "%\n");
				fclose($fp);

				// Move tracking counter to last reported value.
				$lastCounterChange = $byteCounter;
			}
		}
	}
	ob_end_flush();

	$status = fclose($handle);
	if ($status) {
		// Return number of bytes delivered like readfile() does.
		return $byteCounter;
	}

	return $status;
}


// Check validity of file.
function checkDownloadFile($pathName, $fileName, &$fileSize) {

	$fileSize = 0;

	// Test for "..".
	if (strpos($fileName, "..") !== false) {
		return false;
	}
	// Test for backslash.
	if (strpos($fileName, "\\") !== false) {
		return false;
	}

	// Test for file existence.
	if (file_exists($pathName)) {
		// Get file size.
		$fileSize = filesize($pathName);
		return true;
	}
	else {
		return false;
	}
}

// Log to statistics to db.
function logStats($arrDbConf, $fileName, $fileSize, $typeId) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	date_default_timezone_set('America/Los_Angeles');

	$studyId = (int) $_REQUEST['studyid'];
	$groupId = (int) $_REQUEST['groupid'];
	if (isset($_REQUEST['userid']) &&
		trim($_REQUEST['userid']) != "") {
		$userId = (int) $_REQUEST['userid'];
	}
	else {
		// User not logged in.
		$userId = -1;
	}
	$agreement = htmlspecialchars($_REQUEST['agreement']);

	$strInsert = "INSERT INTO statistics " .
		"(studyid, groupid, userid, email, typeid, info, dateentered, bytes, agreement) " .
		"VALUES " .
		"($1, $2, $3, $4, $5, $6, NOW(), $7, $8)"; 
	$result = pg_query_params($db_connection, $strInsert,
		array(
			$studyId,
			$groupId,
			$userId,
			"",
			$typeId,
			$fileName,
			$fileSize,
			$agreement
		)
	);

	pg_close($db_connection);
}

?>
