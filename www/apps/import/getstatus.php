<?php
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
		$status = "$date] Import completed.";
	}

	// Get parse result and make it more user-friendly.
	$strRes = getParseResult($mystring);
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
		$status = "$date] <br/>" . $strRes;
	}
	else if (strpos($mystring, "expected") !== false) {
		$status = "$date] <b style='color:red;'><br/>Check metadata files. An expected character error was encountered during import.<br/>";
	}
	else if (strpos($strRes, "***warning***") !== false) {
		$status = "$date] <br/>";
		$strRes = str_replace("***warning***", "<b style='color:orange;'>Check metadata file in folder ", $strRes);
		$strRes = str_replace("\n", "</b><br/>", $strRes);
		$status .= $strRes;
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

	echo json_encode($status);


// Generate user-friendly parse result.
function getParseResult($strLog) {

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
