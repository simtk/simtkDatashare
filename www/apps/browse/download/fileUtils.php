<?php

/**
 * Copyright 2020-2023, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

// Set threshold at 200MB for files to be considered as "too big" to be handled differently.
defined('MAXBYTES_ALL_FILES') or define('MAXBYTES_ALL_FILES', 200 * 1024 * 1024);

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
		fflush($fp);
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
				fflush($fp);
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


// Record zipfile creation entry for cronjob.
function recordZipFileEntry($arrDbConf,
	$groupId, 
	$studyId, 
	$userId, 
	$token, 
	$filesHash, 
	$email,
	$firstName,
	$lastName,
	$groupName,
	$studyName) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	date_default_timezone_set('America/Los_Angeles');

	$groupId = (int) $groupId;
	$studyId = (int) $studyId;
	$userId = (int) $userId;
	$token = htmlspecialchars($token);
	$filesHash = htmlspecialchars($filesHash);
	$email = htmlspecialchars($email);
	$firstName = htmlspecialchars($firstName);
	$lastName = htmlspecialchars($lastName);
	$groupName = htmlspecialchars($groupName);
	$studyName = htmlspecialchars($studyName);

	$strInsert = "INSERT INTO zipfile_job " .
		"(group_id, study_id, user_id, token, fileshash, email, add_date, firstname, lastname, groupname, studyname) " .
		"VALUES " .
		"($1, $2, $3, $4, $5, $6, NOW(), $7, $8, $9, $10)"; 
	$result = pg_query_params($db_connection, $strInsert,
		array(
			$groupId,
			$studyId,
			$userId,
			$token,
			$filesHash,
			$email,
			$firstName,
			$lastName,
			$groupName,
			$studyName
		)
	);
	pg_close($db_connection);
}


// Get next zip file entry to be processed from database.
function getNextZipFileEntry($arrDbConf, 
	&$zipfileId, 
	&$groupId,
	&$studyId, 
	&$userId,
	&$token,
	&$strFilesHash,
	&$email,
	&$firstName,
	&$lastName,
	&$groupName,
	&$studyName) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	// Status value of 0 means zipfile is to be created.
	$strQuery = "SELECT zipfile_id, group_id, study_id, user_id, token, fileshash, email, firstname, lastname, groupname, studyname " .
		"FROM zipfile_job " .
		"WHERE status=0 " .
		"ORDER BY zipfile_id " .
		"LIMIT 1";
	$result = pg_query_params($db_connection, $strQuery, array());
	if (pg_num_rows($result) == 0) {
		// Entry not available. Free resultset.
		pg_free_result($result);
		pg_close($db_connection);

		return false;
        }

	while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
		$zipfileId = $row["zipfile_id"];
		$groupId = $row["group_id"];
		$studyId = $row["study_id"];
		$userId = $row["user_id"];
		$token = $row["token"];
		$strFilesHash = $row["fileshash"];
		$email = $row["email"];
		$firstName = $row["firstname"];
		$lastName = $row["lastname"];
		$groupName = $row["groupname"];
		$studyName = $row["studyname"];
	}

	pg_free_result($result);
	pg_close($db_connection);

	return true;
}

// Log start time of zipfile creation.
function logZipFileStart($arrDbConf, $zipfileId, $strFilePath, $strFileName) {

	$strFilePath = htmlspecialchars($strFilePath);
	$strFileName = htmlspecialchars($strFileName);

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	// Status value of 1 means zipfile creation is in progress.
	$strUpdate = "UPDATE zipfile_job SET " .
		"status=1, " .
		"filepath=$2, " .
		"filename=$3, " .
		"start_date=NOW() " .
		"WHERE zipfile_id=$1";
	$result = pg_query_params($db_connection, $strUpdate, 
		array(
			$zipfileId,
			$strFilePath, 
			$strFileName
		)
	);
	if (!$result || pg_affected_rows($result) != 1) {
		// Entry not updated.
		pg_free_result($result);
		pg_close($db_connection);

		return false;
        }

	pg_free_result($result);
	pg_close($db_connection);

	return true;
}

// Log success and stop time of zipfile creation.
function logZipFileStop($arrDbConf, $zipfileId) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	// Status value of 2 means zipfile creation is done.
	$strUpdate = "UPDATE zipfile_job SET " .
		"status=2, " .
		"stop_date=NOW() " .
		"WHERE zipfile_id=$1";
	$result = pg_query_params($db_connection, $strUpdate, 
		array(
			$zipfileId
		)
	);
	if (!$result || pg_affected_rows($result) != 1) {
		// Entry not updated.
		pg_free_result($result);
		pg_close($db_connection);

		return false;
        }

	pg_free_result($result);
	pg_close($db_connection);

	return true;
}


// Get count of zipfile creation in progress.
function countZipFileInProgress($arrDbConf, 
	&$startDate,
	&$userId,
	&$groupName,
	&$studyName,
	&$studyId,
	&$fileName) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	$startDate = false;
	$strQuery = "SELECT FLOOR(EXTRACT(EPOCH FROM start_date)) as sd, " .
		"user_id, groupname, studyname, study_id, filename " .
		"FROM zipfile_job " .
		"WHERE status=1";
	$result = pg_query_params($db_connection, $strQuery, array());
	$count = pg_num_rows($result);
	while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
		$startDate = $row["sd"];
		$userId = $row["user_id"];
		$groupName = $row["groupname"];
		$studyName = $row["studyname"];
		$studyId = $row["study_id"];
		$fileName = $row["filename"];
	}

	pg_free_result($result);
	pg_close($db_connection);

	return $count;
}

// Log error status in zipfile creation.
function logZipFileError($arrDbConf, $zipfileId, $status) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	$strUpdate = "UPDATE zipfile_job SET " .
		"status=$2 " .
		"WHERE zipfile_id=$1";
	$result = pg_query_params($db_connection, $strUpdate, 
		array($zipfileId, $status));
	if (!$result || pg_affected_rows($result) != 1) {
		// Entry not updated.
		pg_free_result($result);
		pg_close($db_connection);

		return false;
        }

	pg_free_result($result);
	pg_close($db_connection);

	return true;
}

// Get information of files under a directory.
function getDirInfo($fullPathName, &$totalBytes, &$lastModified){

	$totalBytes = 0;
	$lastModified = false;

	// Recursively find all files under the directory to get total size.
	if (is_dir($fullPathName) && file_exists($fullPathName)) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPathName,
			FilesystemIterator::SKIP_DOTS)) as $theObj) {

			// Found a file.
			$theSize = $theObj->getSize();
			$totalBytes += $theSize;

			// Get last modified time.
			$mtime = $theObj->getMTime();
			if (!$lastModified || $mtime > $lastModified) {
				$lastModified = $mtime;
			}
		}
	}
}

// Save directory information.
function saveDirInfo($arrDbConf,
	$userId,
	$token,
	$groupId,
	$studyId,
	$totalBytes,
	$lastModified) {

	include dirname(__FILE__) . "/../../../user/server.php";

	$conf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
	$conf = json_decode($conf);

	// Save disk usage.
	$url = "https://$domain_name/plugins/api/index.php?key=$api_key" .
		"&userid=" . $userId .
		"&token=" . $token . 
		"&groupid=" . $groupId .
		"&studyid=" . $studyId .
		"&totalbytes=" . $totalBytes .
		"&lastmodified=" . $lastModified .
		"&action=21" .
		"&tool=datashare";

	$context = array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);
	$response_study_json = file_get_contents($url, false, stream_context_create($context));
	$response_study = json_decode($response_study_json);

	if ($response_study == null || !$response_study->status) {
		// Failed.
		return false;
	}
	else {
		// Status.
		return $response_study->status;
	}
}

?>



