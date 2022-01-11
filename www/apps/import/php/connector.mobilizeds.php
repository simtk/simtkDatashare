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


include_once('../../../user/server.php');
include_once('../../browse/download/fileUtils.php');

error_reporting(0); // Set E_ALL for debuging

// load composer autoload before load elFinder autoload If you need composer
//require './vendor/autoload.php';

// elFinder autoload
require './autoload.php';
// ===============================================

// Enable FTP connector netmount
elFinder::$netDrivers['ftp'] = 'FTP';
// ===============================================

$conf = file_get_contents( '/usr/local/mobilizeds/conf/mobilizeds.conf' );
$conf = json_decode( $conf );

// // Required for Dropbox network mount
// // Installation by composer
// // `composer require kunalvarma05/dropbox-php-sdk`
// // Enable network mount
// elFinder::$netDrivers['dropbox2'] = 'Dropbox2';
// // Dropbox2 Netmount driver need next two settings. You can get at https://www.dropbox.com/developers/apps
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=dropbox2&host=1"
// define('ELFINDER_DROPBOX_APPKEY',    '');
// define('ELFINDER_DROPBOX_APPSECRET', '');
// ===============================================

// // Required for Google Drive network mount
// // Installation by composer
// // `composer require google/apiclient:^2.0`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'GoogleDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// // Required case of without composer
// define('ELFINDER_GOOGLEDRIVE_GOOGLEAPICLIENT', '/path/to/google-api-php-client/vendor/autoload.php');
// ===============================================

// // Required for Google Drive network mount with Flysystem
// // Installation by composer
// // `composer require nao-pon/flysystem-google-drive:~1.1 nao-pon/elfinder-flysystem-driver-ext`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'FlysystemGoogleDriveNetmount';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// ===============================================

// // Required for One Drive network mount
// //  * cURL PHP extension required
// //  * HTTP server PATH_INFO supports required
// // Enable network mount
// elFinder::$netDrivers['onedrive'] = 'OneDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://dev.onedrive.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL/netmount/onedrive/1"
// define('ELFINDER_ONEDRIVE_CLIENTID',     '');
// define('ELFINDER_ONEDRIVE_CLIENTSECRET', '');
// ===============================================

// // Required for Box network mount
// //  * cURL PHP extension required
// // Enable network mount
// elFinder::$netDrivers['box'] = 'Box';
// // Box Netmount driver need next two settings. You can get at https://developer.box.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL"
// define('ELFINDER_BOX_CLIENTID',     '');
// define('ELFINDER_BOX_CLIENTSECRET', '');
// ===============================================

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 *
 * @param  string    $attr    attribute name (read|write|locked|hidden)
 * @param  string    $path    absolute file path
 * @param  string    $data    value of volume option `accessControlData`
 * @param  object    $volume  elFinder volume driver object
 * @param  bool|null $isDir   path is directory (true: directory, false: file, null: unknown)
 * @param  string    $relpath file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume, $isDir, $relpath) {
	$basename = basename($path);
	return $basename[0] === '.'                  // if file/folder begins with '.' (dot)
			 && strlen($relpath) !== 1           // but with out volume root
		? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
		:  null;                                 // else elFinder decide it itself
}

function handleFileChange($cmd, &$result, $args, $elfinder) {
	global $conf;
	$study   = $conf->study->id;
	$token   = $conf->prefix . '/data/study' . $_REQUEST['study'] . '.need-new-snapshot';
	$indexer = $conf->prefix . '/bin/index/study';
	shell_exec( 'touch ' . $token );
	$log = sprintf('[%s] %s:', date('r'), strtoupper($cmd));

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

	// Array of compressed files added.
	$compressedFilesAdded = array();

	foreach ($result as $key => $value) {
		if (empty($value)) {
			continue;
		}
		$data = array();
		if (in_array($key, array('error', 'warning'))) {
			array_push($data, implode(' ', $value));
		}
		else {
			if (is_array($value)) {
				foreach ($value as $file) {
					$filepath = (isset($file['realpath']) ? $file['realpath'] : $elfinder->realpath($file['hash']));
					array_push($data, $filepath);
				}
			}
			else {
				array_push($data, $value);
			}
		}
		$log .= sprintf(' %s(%s)', $key, implode(', ', $data));

		if ($key == "added") {
			// Track compresssed files added.
			// Use associative array to prevent any potential duplicate full paths.
			foreach ($data as $theFullFilePath) {

				// Track .zip, .tar.gz, and .tar files.
				$idxStartZIP = strlen($theFullFilePath) - strlen(".zip");
				$isZIP = stripos($theFullFilePath, ".zip", $idxStartZIP);
				$idxStartTARGZ = strlen($theFullFilePath) - strlen(".tar.gz");
				$isTARGZ = stripos($theFullFilePath, ".tar.gz", $idxStartTARGZ);
				$idxStartTAR = strlen($theFullFilePath) - strlen(".tar");
				$isTAR = stripos($theFullFilePath, ".tar", $idxStartTAR);
				if (($idxStartZIP >= 0 && isZIP !== false) ||
					($idxStartTARGZ >= 0 && isTARGZ !== false) ||
					($idxStartTAR >= 0 && isTAR !== false)) {

					$compressedFilesAdded[$theFullFilePath] = $theFullFilePath;
				}
			}
		}
	}
	$log .= "\n";

	if ($cmd == "upload") {

		// cd command.
		// Files have been uploaded.
		foreach ($result as $theStatus=>$theValue) {

			// Having the status of "changed" in the $result array 
			// means the files upload has been completed.
			// NOTE: This handleFileChange() method is invoked multiple times
			// during upload and only its invocation with $result array
			// containing the status of "changed" is when the upload is complete.
			if ($theStatus == "changed") {
				// Get directory information after the upload.
				$fullPathName = $conf->data->docroot . 
					"/study/study" . $_REQUEST["study"] .
					"/files";
				getDirInfo($fullPathName, $totalBytes, $lastModified);

				// Save disk usage info.
				saveDirInfo($arrDbConf, 
					$_REQUEST['userid'],
					$_REQUEST['token'],
					$_REQUEST["groupid"],
					$_REQUEST["study"],
					$totalBytes,
					$lastModified);

				// Upload has completed.

				// Expand compressed files.
				foreach ($compressedFilesAdded as $theFullFilePath) {

					// Get the containing directory.
					$idxLast = strrpos($theFullFilePath, "/");
					if ($idxLast === false) {
						// Cannot find last "/".
						continue;
					}
					$theDir = substr($theFullFilePath, 0, $idxLast);
					if (!is_dir($theDir)) {
						// Not a directory.
						continue;
					}
					// Generate the cd command for each containing directory.
					$commandCd = "cd " . $theDir;

					if (($idxStart = strlen($theFullFilePath) - strlen(".zip")) >= 0 && 
						stripos($theFullFilePath, ".zip", $idxStart) !== false) {

						// Handle .zip file expansion.

						// Unzip file command
						$commandUnzip = "; /usr/bin/unzip " . $theFullFilePath;

						// Extract file.
						shell_exec( $commandCd . $commandUnzip);
					}
					else if (($idxStart = strlen($theFullFilePath) - strlen(".tar.gz")) >= 0 && 
						stripos($theFullFilePath, ".tar.gz", $idxStart) !== false) {

						// Handle .tar.gz file expansion.

						// gunzip file command
						$commandGunzip = "; /bin/gunzip -c " . $theFullFilePath . " | tar xf -";

						// Extract file.
						shell_exec( $commandCd . $commandGunzip);
					}
					else if (($idxStart = strlen($theFullFilePath) - strlen(".tar")) >= 0 && 
						stripos($theFullFilePath, ".tar", $idxStart) !== false) {

						// Handle .tar file expansion.

						// untar file command
						$commandUntar = "; /bin/tar -xf " . $theFullFilePath;

						// Extract file.
						shell_exec( $commandCd . $commandUntar);
					}
				}
			}
		}
	}
	else if ($cmd == "rm") {
		foreach ($result as $theStatus=>$theValue) {
			if ($theStatus == "changed") {
				// $cmd with value of "rm" and $theStatus value 
				// of "changed" indiate item(s) deleted and
				// trash has been emptied.
				// Get directory information after the deletion.
				$fullPathName = $conf->data->docroot . 
					"/study/study" . $_REQUEST["study"] .
					"/files";
				getDirInfo($fullPathName, $totalBytes, $lastModified);

				// Save disk uage info.
				saveDirInfo($arrDbConf, 
					$_REQUEST['userid'],
					$_REQUEST['token'],
					$_REQUEST["groupid"],
					$_REQUEST["study"],
					$totalBytes,
					$lastModified);
			}
		}
	}

	$command = $indexer . ' ' . $conf->data->docroot . '/study/study'.$_REQUEST['study'].'/files 2>&1';
	$log .= $command;
	$log .= shell_exec( $command );
	//append log file
	$logfile = '../logs/log'.$_REQUEST['study'].'.txt';
	//overwrite log file
	$logfile_o = '../logs/log_o'.$_REQUEST['study'].'.txt';
	$dir = dirname( $logfile ); 
	$dir_o = dirname( $logfile_o ); 
	if (!is_dir($dir) && !mkdir($dir)) {
		return; 
	} 
	if (!is_dir($dir_o) && !mkdir($dir_o)) {
		return; 
	} 
	if (($fp = fopen($logfile, 'a'))) {
		fwrite($fp, $log);
		fclose($fp); 
	}
	if (($fp = fopen($logfile_o, 'w'))) {
		fwrite($fp, $log);
		fclose($fp); 
	}

	// Remove file_filter table entry of associated study.
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
	if (isset($arrDbConf["db"]) &&
		isset($arrDbConf["user"]) &&
		isset($arrDbConf["pass"])) {
		// Has db configuration parameters.
		$db_connection = pg_connect("host=localhost " .
			"dbname=" . $arrDbConf["db"] . " " .
			"user=" . $arrDbConf["user"] . " " .
			"password=" . $arrDbConf["pass"]);
		if ($db_connection !== false) {
			$strQuery = "DELETE FROM file_filter WHERE metadata_name=$1";
			$res = pg_query_params($db_connection, $strQuery,
				array("study" . $_REQUEST['study'] . ".metadata"));
			pg_close($db_connection);
		}
	} 


	if ($cmd === 'open' || ! empty($result['added'])) {
		// Update the value of $result to force a refresh to display expanded file(s).
		$result['sync'] = 1;
	}
}


$urlSendFileParams = 'section=' . $_REQUEST['section'] . '&' .
	'groupid=' . $_REQUEST['groupid'] . '&' .
	'userid=' . $_REQUEST['userid'] . '&' .
	'studyid=' . $_REQUEST['studyid'] . '&' .
	'isDOI=' . $_REQUEST['isDOI'] . '&' .
	'doi_identifier=' . $_REQUEST['doi_identifier'] . '&' .
	'token=' . $_REQUEST['token'] . '&' .
	'private=' . $_REQUEST['private'] . '&' .
	'member=' . $_REQUEST['member'] . '&' .
	'firstname=' . $_REQUEST['firstname'] . '&' .
	'lastname=' . $_REQUEST['lastname'] . '&' .
	'nameDownload=';
$theURL = '../browse/download/sendDownloadConfirm.php?' . $urlSendFileParams;
if ($_REQUEST['userid'] == 0) {
	$theURL = "https://" . $domain_name . "/plugins/datashare/userLogin.php?";
	$theURL .= "groupid=" . $_REQUEST['groupid'] . "&" .
		"studyid=" . $_REQUEST['studyid'] . "&" .
		"nameDownload=";
}

// Documentation for connector options:
// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
$opts = array(
	'debug' => true,
        'bind'  => [ 'rename upload rm duplicate paste put' => 'handleFileChange' ],

	'roots' => array(
		// Items volume
		array(
			'driver'        => 'LocalFileSystem',           // driver for accessing file system (REQUIRED)
			'path'          => $conf->data->docroot . '/study/study'.$_REQUEST['study'].'/files/',  // path to files (REQUIRED)

			// Set up URL for download of file.
			'URL'		=> $theURL,
			'trashHash'     => 't1_Lw',                     // elFinder's hash of trash folder

			// Disable download of directory with zip.
			'disabled'     => array('zipdl'),
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
			'uploadDeny'    => array('none'),                // All Mimetypes not allowed to upload
			'uploadAllow'   => array('all'),        // Mimetype `image` and `text/plain` allowed to upload
			'uploadOrder'   => array('allow'),      // allowed Mimetype `image` and `text/plain` only
			'accessControl' => 'access'                     // disable and hide dot starting files (OPTIONAL)
		),
		// Trash volume
		array(
			'id'            => '1',
			'driver'        => 'Trash',
			'path'          => $conf->data->docroot . '/study/study'.$_REQUEST['study'].'/files/.trash/',
			'tmbURL'        => dirname($_SERVER['PHP_SELF']) . '../study/study'.$_REQUEST['study'].'/files/.trash/.tmb/',
			// Disable download of directory with zip.
			'disabled'     => array('zipdl'),
			'winHashFix'    => DIRECTORY_SEPARATOR !== '/', // to make hash same to Linux one on windows too
			'uploadDeny'    => array('none'),  // Recomend the same settings as the original volume that uses the trash
			'uploadAllow'   => array('all'),                // Same as above
			'uploadOrder'   => array('allow'),      // Same as above
			'accessControl' => 'access',                    // Same as above
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

