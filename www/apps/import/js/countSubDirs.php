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

$theResult = array();

$conf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($conf);

$studyId = false;
$theDir = false;
// Get study id.
if (isset($_POST["StudyId"])) {
	$studyId = (int) $_POST["StudyId"];
}
// Get current directory.
if (isset($_POST["NameDir"])) {
	$theDir = $_POST["NameDir"];
}

if ($studyId !== false && $theDir !== false) {

	// Get full path to current directory.
	$pathName = $conf->data->docroot. "/study/study" . $studyId . "/" . $theDir;
	// Validate the full path to the current directory.
	if (checkDirectory($pathName) === false) {
		$theResult["NumSubDirs"] = false;
	}
	else {
		// Get number of subdirectories.
		$arrDirNames = array();
		findSubDirs($pathName, $arrDirNames);
		$numDirs = count($arrDirNames);
		if ($numDirs > 0) {
			$theResult["NumSubDirs"] = $numDirs;
		}
		else {
			$theResult["NumSubDirs"] = false;
		}
	}
}

// Encode the result.
$strRes = json_encode($theResult);
echo $strRes;


// Check validity of directory.
function checkDirectory($pathName) {

	// Test for "..".
	if (strpos($pathName, "..") !== false) {
		return false;
	}
	// Test for backslash.
	if (strpos($pathName, "\\") !== false) {
		return false;
	}

	// Test the pathname of the directory.
	if (is_dir($pathName)) {
		// Pathname is a valid directory.
		return true;
	}
	else {
		return false;
	}
}


// Find all subdirectories.
function findSubDirs($strDirName, &$allDirNames) {
	// Look up contents in directory.
	$resDir = scandir($strDirName);
	foreach ($resDir as $fname) {
		$fullPath = realpath($strDirName . "/" . $fname);
		// Go through each subdirectory.
		// Exclude "/." directories like "/.trash" and "/.quarantine".
		if (is_dir($fullPath) &&
			$fname != "." &&
			$fname != ".." &&
			strpos($fullPath, "/.") === false) {
			$allDirNames[] = $fullPath;
			findSubDirs($fullPath, $allDirNames);
		}
	}
}

?>
