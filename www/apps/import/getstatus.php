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

	include_once( '../../user/session.php' );
	$conf = file_get_contents( '/usr/local/mobilizeds/conf/mobilizeds.conf' );
	$conf = json_decode( $conf );

	$studyid = 0;
	if (isset($_REQUEST['StudyId'])) {
		$studyid = (int) $_REQUEST['StudyId'];
	}

	$mystring = file_get_contents( "/usr/local/mobilizeds/html/apps/import/logs/log_o$studyid.txt" );
	if ($mystring === false || trim($mystring) == "") {
		// Information from import log file not available.
		$status = "No data imported yet.";
	}
	else {
		// Get date from import log file and generate default status.
		$pos1 = stripos($mystring, '[');
		$pos2 = stripos($mystring, '-');
		$numchar = $pos2 - $pos1;
		$numchar = $numchar - 1;
		$date = substr($mystring, $pos1, $numchar);
		$status = "$date] ";
	}

	// Get parse result and make it more user-friendly.
	$strRes = getParseResult($mystring, $strInfo);

	// Get message from unzip operation.
	if ($strInfo != "") {
		$arrStrInfo = explode("<br/>", $strInfo);
		foreach ($arrStrInfo as $theInfo) {
			$theInfo = trim($theInfo);
			// Check for empty string.
			if ($theInfo == "") {
				continue;
			}
			if (strpos($theInfo, "UnzippedError:") !== false) {
				$status .= "<br/><b style='color:red;'>" . 
					substr($theInfo, strlen("UnzippedError:")) . 
					"</b>";
			}
			else {
				$status .= "<br/><b style='color:orange;'>" . $theInfo . "</b>";
			}
		}
	}

	if (strpos($mystring, "Error") !== false ||
		strpos($mystring, "parser") !== false ||
		strpos($mystring, "***error***") !== false) {

		$idxEnd = strpos($strRes, " at study");
		if ($idxEnd !== false) {
			// Strip " at study" from error message.
			$strRes = substr($strRes, 0, $idxEnd);
		} 

		// Substitute warning and error with leading message.
		$strRes = str_replace("***warning***",
			"<b style='color:orange;'>Check metadata file in folder ", 
			$strRes);
		$strRes = str_replace("***error***", 
			"<b style='color:red;'>Check metadata file in folder ", 
			$strRes);

		// Close tag and add line break.
		$strRes = str_replace("\n", "</b><br/>", $strRes);
		$status .= "<br/>" . $strRes;
	}
	else if (strpos($mystring, "expected") !== false) {
		$status .= "<br/><b style='color:red;'><br/>Check metadata files. An expected character error was encountered during import.<br/>";
	}
	else if (strpos($strRes, "***warning***") !== false) {
		$strRes = str_replace("***warning***", "<b style='color:orange;'>Check metadata file in folder ", $strRes);
		$strRes = str_replace("\n", "</b><br/>", $strRes);
		$status .= "<br/>" . $strRes;
	}
	else if (strpos($mystring, "RM: removed") !== false) {
		// Removal from trash.
		$status = "***DELETION***";
	}
	else if (($idx = strpos($mystring, "PASTE: changed(")) !== false) {
		$idx += strlen("PASTE: changed(");
		$mystring = substr($mystring, $idx);
		if (strpos($mystring, "/study" . $studyid ."/files/.trash)") !== false) {
			// Move into trash.
			$status = "***DELETION***";
		}
	}
	else {
		$status .= "<br/>Import completed.";
	}

	echo json_encode($status);


// Generate user-friendly parse result.
function getParseResult($strLog, &$strInfo) {

	$strInfo = "";
	$strRes = "";
	$strToken = " 2>&1";
	$idxStart = stripos($strLog, $strToken);
	if ($idxStart !== false) {
		$strTmp = trim(substr($strLog, $idxStart + strlen($strToken)));
		$idxEnd = stripos($strTmp, "CREATE TABLE");
		if ($idxEnd !== false) {
			$strRes = trim(substr($strTmp, 0, $idxEnd));
		}
	}

	// Get information message if present.
	if (strpos($strRes, "***information***") !== false) {
		// Skip leading "***information***".
		$idxInfoEnd = strpos($strRes, "***", 17);
		if ($idxInfoEnd !== false) {
			$strInfo = substr($strRes, 17, $idxInfoEnd - 17);

			// Advance parse result to after information message.
			$strRes = substr($strRes, $idxInfoEnd + 3);
		}
	}

	$strRes = str_replace("/usr/local/mobilizeds/bin/general-template/", "", $strRes);
	$strRes = str_replace("/usr/local/mobilizeds/bin/", "", $strRes);

	// Clean up YAML error message.
	$strTokenErr = "YAML Error: ";
	$idxErr = stripos($strRes, $strTokenErr);
	if ($idxErr !== false) {
		$strTokenCode = "Code: ";
		$idxCode = stripos($strRes, $strTokenCode);
		if ($idxCode !== false) {
			$strTokenDoc = "Document: ";
			$idxDoc = stripos($strRes, $strTokenDoc);
			if ($idxDoc !== false) {
				$strRes = $strTokenErr . strtolower(substr($strRes, 
					$idxCode + strlen($strTokenCode), 
					$idxDoc - $idxCode - strlen($strTokenCode)));
			}
		}
	}

	// Clean up other error messages.
	$strTokenErr = " at /usr/";
	$idxErr = stripos($strRes, $strTokenErr);
	if ($idxErr !== false) {
		//$strRes = "Error: " . strtolower(substr($strRes, 0, $idxErr));
		$strRes = substr($strRes, 0, $idxErr);
	}

	// Clean up strings.
	$strRes = str_replace("yaml_parse_err_", "", $strRes);
	$strRes = str_replace("yaml_load_err_", "", $strRes);
	$strRes = str_replace("/usr/local/share/", "", $strRes);
	$strRes = str_replace("/usr/local/lib/", "", $strRes);
	$strRes = str_replace("yaml::xs::", "", $strRes);
	$strRes = str_replace("_", " ", $strRes);

	$strRes = trim($strRes);

	// Split message by line breaks.
	$arrLines = explode("\n", $strRes);
	$strRes = "";
	foreach ($arrLines as $theLine) {
		// Examine each line.
		$theLine = trim($theLine);
		if (strpos($theLine, "at ") === 0) {
			// Ignore extra error messages
			// with lines starting with "at ".
			continue;
		}
		if (strpos($theLine, "Unhandled promise rejection") !== false) {
			// Ignore extra error messages
			// with lines containing with "Unhandled promise rejection".
			continue;
		}
		if (strpos($theLine, "mismatched ") === 0 ||
			strpos($theLine, "(node:") === 0 ||
			strpos($theLine, "chown:") === 0) {
			// Add leading error message.
			$strRes .= "<b style='color:red;'>Check metadata files. " . $theLine . "</b>\n";
		}
		else {
			// No change in line.
			$strRes .= $theLine . "\n";
		}
	}

	return $strRes;
}


?>
