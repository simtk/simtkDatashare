<?php

# Copyright 2020-2023, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

include_once("../../user/checkuser.php");

$dirStudy = "/usr/local/mobilizeds/study/";

// Get parameters.
$studyid = $_REQUEST["studyid"];
$idxRowHead = $_REQUEST["headerRow"];
$idxColSubj = $_REQUEST["subjectColumn"];
$subjPrefix = $_REQUEST["subjectPrefix"];
$nameMetadataCSVFile = $_REQUEST["nameMetadataCSVFile"];

if (isset($_REQUEST["isSave"])) {
	$isSave = (int) $_REQUEST["isSave"];
	if ($isSave === 1) {
		$isSave = true;
	}
	else {
		$isSave = false;
	}
}
else {
	$isSave = false;
}

$arrRes = array();
$arrRes['status'] = 'Failure';
$arrRes['isSave'] = $isSave;
$arrRes['num_of_subjects_avail'] = 0;
$arrRes['num_of_subjects_save'] = 0;

// Validate permission to access to study.
if ($perm <= 2) {
	// Does not have permission to 
	// import and parse metadata into study.
	$arrRes['err_log'] = "Insufficient permissions.";
	echo json_encode($arrRes);
	return false;
}

if (!is_numeric($studyid)) {
	// Invalid study id.
	$arrRes['err_log'] = "Invalid study.";
	echo json_encode($arrRes);
	return false;
}

if (!is_numeric($idxRowHead)) {
	// Invalid header row.
	$arrRes['err_log'] = "Invalid header row.";
	echo json_encode($arrRes);
	return false;
}
$idxRowHead = intval($idxRowHead);
$idxRowHead--;
if ($idxRowHead < 0) {
	// Invalid header row.
	$arrRes['err_log'] = "Invalid row header index.";
	echo json_encode($arrRes);
	return false;
}

if (!is_numeric($idxColSubj)) {
	// Invalid subject column.
	$arrRes['err_log'] = "Invalid subject column.";
	echo json_encode($arrRes);
	return false;
}
$idxColSubj = intval($idxColSubj);
$idxColSubj--;
if ($idxColSubj < 0) {
	// Invalid subject column index.
	$arrRes['err_log'] = "Invalid subject column index.";
	echo json_encode($arrRes);
	return false;
}

// Test for: empty subject prefix, long subject prefix,
// non-alphabetic values in subject prefix.
if (!$subjPrefix || trim($subjPrefix) == "" ||
	strlen(trim($subjPrefix)) > 80 ||
	ctype_alpha(trim($subjPrefix)) === false) {
	$arrRes['err_log'] = "Invalid subject prefix.";
	echo json_encode($arrRes);
	return false;
}

$tmpName = preg_replace("/[-A-Z0-9+_\. ~\/]/i", "", $nameMetadataCSVFile);
if (!empty($tmpName) || strstr($nameMetadataCSVFile, "..")) {
        // Invalid filename.
	$arrRes['err_log'] = "Invalid file name.";
	echo json_encode($arrRes);
	return false;
}

// Generate full path to metadata CSV file.
$fullPathCsvFileName = $dirStudy . "study" . $studyid . 
	"/files/" . $nameMetadataCSVFile . ".csv";
if (!is_file($fullPathCsvFileName)) {
	// Cannot open CSV file.
	$arrRes['err_log'] = "Invalid file.";
	echo json_encode($arrRes);
	return false;
}

// Get info on all subjects.
$arrSubjInfo = getSubjInfo($fullPathCsvFileName,
	$idxRowHead,
	$idxColSubj,
	$subjPrefix,
	$strErrLog);
if ($arrSubjInfo == null) {
	// Error in parsing of metadata CSV file.
	$arrRes['err_log'] = $strErrLog;
	echo json_encode($arrRes);
	return false;
}

// Write metadata.json files.
// Get path to directory of metadata CSV File.
$idxEnd = strrpos($fullPathCsvFileName, "/");
if ($idxEnd === false) {
	// Cannot get path to directory of metadata CSV File.
	$arrRes['err_log'] = $strErrLog;
	$arrRes['num_of_subjects_avail'] = count($arrSubjInfo);
	echo json_encode($arrRes);
	return false;
}
$fullPathCsvDir = substr($fullPathCsvFileName, 0, $idxEnd);
$cntJsonFiles = writeMetadataJsonFiles($fullPathCsvDir, $arrSubjInfo, $isSave, $strErrLog);

if (!$isSave) {
	// Done. Parse metadata file only.
	// No need to save metadata.json files or to import the data.
	$arrRes['status'] = 'Success';    
	$arrRes['err_log'] = $strErrLog;
	$arrRes['num_of_subjects_avail'] = count($arrSubjInfo);
	$arrRes['num_of_subjects_save'] = $cntJsonFiles;
	echo json_encode($arrRes);
	return true;
}

// Get path to data repository of study.
$fullPathStudy = $dirStudy . "study" . $studyid . "/files";
// Import metadata.json files and update study.
// Old metadata is cleaned up.
// If metadata is not found for import, study is updated to not contain any metadata.
$status = exec("/usr/local/mobilizeds/bin/index/study $fullPathStudy", $arrImport);
if ($status === false) {
	// Cannot import study.
	$arrRes['err_log'] = $strErrLog;
	$arrRes['num_of_subjects_avail'] = count($arrSubjInfo);
	$arrRes['num_of_subjects_save'] = $cntJsonFiles;
	echo json_encode($arrRes);
	return false;
}

// Count metadata after population.
include_once("../../checkMetadata.php");

$arrRes['status'] = 'Success';    
$arrRes['err_log'] = $strErrLog;
$arrRes['num_of_subjects_avail'] = count($arrSubjInfo);
$arrRes['num_of_subjects_save'] = $cntJsonFiles;
$arrRes['total_metadata'] = $cntMetaData;
echo json_encode($arrRes);


// Write metadata.json files.
function writeMetadataJsonFiles($fullPathCsvDir, $arrSubjInfo, $isSave, &$strErrLog) {

	$isExists = is_dir($fullPathCsvDir);
	if (!$isExists) {
		// Cannot open destination directory for writing.
		$strErrLog .= "Cannot access destination folder.\n";
		return 0;
	}

	// Clean up existing metadata.json files from subject subdirectories.
	// Otherwise, old metadata.json files from subjects will be included.
	$arrSubDirs = glob($fullPathCsvDir . "/*", GLOB_ONLYDIR);
	foreach ($arrSubDirs as $subDir) {
		// Get metadata file from subject subdirectory.
		$fileMetadata = $subDir . "/metadata.json";
		$isExists = file_exists($fileMetadata);
		if ($isExists) {
			// Remove old metadata file.
			unlink($fileMetadata);
		}
	}

	$idxCount = 0;
	foreach ($arrSubjInfo as $nameSubj=>$subjInfo) {

		$fullPathWithNameSubj = $fullPathCsvDir . "/" . $nameSubj;
		$isExists = is_dir($fullPathWithNameSubj);
		if (!$isExists) {
			// Cannot open destination subject directory for writing.
			$strErrLog .= "Cannot access $nameSubj destination folder.\n";
			continue;
		}

		$fhJsonFile = fopen($fullPathWithNameSubj . "/metadata.json", "w+");
		if (!$fhJsonFile) {
			// Cannot open destination file for writing.
			$strErrLog .= "Cannot write metadata for $nameSubj.\n";
			continue;
		}

		if ($isSave) {
			// JSON-encoded subject info.
			$resStr = json_encode($subjInfo);

			// Save to metadata.json file.
			fwrite($fhJsonFile, $resStr . "\n");
			fclose($fhJsonFile);

			// Update ownership of file.
			chown($fullPathWithNameSubj . "/metadata.json", "www-data");
			chgrp($fullPathWithNameSubj . "/metadata.json", "www-data");
		}

		$idxCount++;
	}

	return $idxCount;
}


// Get info on all subjects from metadata CSV file.
function getSubjInfo($fullPathCsvFileName,
	$idxRowHead,
	$idxColSubj,
	$subjPrefix="subject",
	&$strErrLog) {

	$strErrLog = "";
	$arrHead = array();
	$arrSubjInfo = array();
	$cntRow = 0;

	$fhCsvFile = fopen($fullPathCsvFileName, "r");
	if (!$fhCsvFile) {
		// Cannot open destination file for reading.
		return null;
	}

	$arrTypes = array();

	// Read each line of CSV.
	while (($arrRead = fgetcsv($fhCsvFile, 0, ",")) !== FALSE) {
		// Get header row.
		if ($cntRow == $idxRowHead) {
			// Header population.
			foreach ($arrRead as $key=>$strHead) {
				$strHead = trim($strHead);
				if ($strHead != "") {
					// Get header from column.
					$strHead = str_replace('"', "'", $strHead);
					$arrHead[$key] = $strHead;
				}
			}

			// Header row. Done processing header.
			$cntRow++;
			continue;
		}

		// Check if the row contains a subject at the expected column.
		if (!isset($arrRead[$idxColSubj]) ||
			($strSubject = trim($arrRead[$idxColSubj])) == "") {
			// This row does not contain a subject column. Ignore this row.
			$cntRow++;
			continue;
		}

		// Check if subject read matched the expected subject prefix.
		if (strpos($strSubject, $subjPrefix) !== 0) {
			// Subject column not matched to subject prefix. Ignore this row.
			$cntRow++;
			continue;
		}

		// Types in column across all subjects must be consistent.
		// Otherwise, query does not function properly.
		// 
		// Check if data associated with the subject is complete, matching
		// to populated header columns.
		// NOTE: This check for data completeness, which results in ignoring
		// subject row with empty text cells, is important, because 
		// otherwise, numeric cells from subject rows with real data 
		// will become mismatched to text type cells which are 
		// read from empty text cells.
		$isDataComplete = true;
		foreach ($arrHead as $key=>$val) {
			if (trim($arrRead[$key]) == "") {
				// Data at the column is empty. Flag to ignore this row.
				$isDataComplete = false;
				break;
			}
		}
		if ($isDataComplete === false) {
			// Data row is incomplete. Ignore this row.
			$cntRow++;
			continue;
		}

		// Data row is complete. Use this row.
		$theSubject = array();
		// Go through each column according to populated header.
		foreach ($arrHead as $key=>$val) {

			// Get subject name from data row.
			if ($key == $idxColSubj) {
				$nameSubj = $arrRead[$key];
				$nameSubj = str_replace('"', "'", $nameSubj);
				continue;
			}

			$theCell = array();

			// Get each data column.
			$theVal = $arrRead[$key];
			if (is_numeric($theVal)) {
				// Treat int and float value as a double.
				$floatVal = floatval($theVal);
				$theVal = $floatVal;
				$theCell["type"] = "double";
				$theCell["value"] = $theVal;

				// Flag mismatched cell type.
				if (!isset($arrTypes[$key])) {
					$arrTypes[$key] = $theCell["type"];
				}
				else {
					if ($arrTypes[$key] != $theCell["type"]) {
						$strErrLog .= "$strSubject" .
							", column " . ($key + 1) .
							", inconsistent data type: " . 
							$theCell["type"] . 
							", expecting " . $arrTypes[$key] .
							".\n";
					}
				}
			}
			else {
				// Value is non-numeric; treat value as text.
				$theCell["type"] = "text";
				$theVal = str_replace('"', "'", $theVal);
				$theCell["value"] = $theVal;

				// Flag mismatched cell type.
				if (!isset($arrTypes[$key])) {
					$arrTypes[$key] = $theCell["type"];
				}
				else {
					if ($arrTypes[$key] != $theCell["type"]) {
						$strErrLog .= "$strSubject" .
							", column " . ($key + 1) .
							", inconsistent data type: " . 
							$theCell["type"] . 
							", expecting " . $arrTypes[$key] .
							".\n";
					}
				}
			}

			$theSubject[$arrHead[$key]] = $theCell;
		}

		// Fill in subject info.
		$arrSubjInfo[$nameSubj] = $theSubject;

		$cntRow++;
	}

	// Done.
	fclose($fhCsvFile);

	if ($strErrLog !== "") {
		// Inconsistent data type present.
		// Import aborted.
		return null;
	}
	else if (count($arrSubjInfo) == 0) {
		// Metadata not found.
		// Import aborted.
		$strErrLog = "Metadata not found.\n";
		return null;
	}

	return $arrSubjInfo;
}

?>


