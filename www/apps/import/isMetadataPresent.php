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

if (isset($_POST["StudyId"])) {
	$studyId = (int) $_POST["StudyId"];
	if (is_int($studyId)) {
		$fileName = "/usr/local/mobilizeds/html/include/js/study" . $studyId . "-fields.js";
		$myString = file_get_contents($fileName);
		if (strstr($myString,'filters": []') == false) {
			// Has study metadata.
			$theResult["hasMetaData"] = true;
		}
		else {
			// No study metadata.
			$theResult["hasMetaData"] = false;
		}
	}
	else {
		// Invalid study.
		$theResult["hasMetaData"] = false;
	}
}
else {
	// Study not specified.
	$theResult["hasMetaData"] = false;
}

// Encode the result.
$strRes = json_encode($theResult);
echo $strRes;

?>
