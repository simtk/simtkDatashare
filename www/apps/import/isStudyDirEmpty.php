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
		$dirName = "/usr/local/mobilizeds/study/study" . $studyId . "/files/";
		$arrRes = scandir($dirName);
		if ($arrRes && isset($arrRes[5])) {
			// The study directory is not empty.
			// NOTE: $arrRes[5] is the real entry.
			// The other 5 are essential directories.
			$theResult["isEmpty"] = false;
		}
		else {
			// The study directory cannot be retrieved or is empty.
			$theResult["isEmpty"] = true;
		}
	}
	else {
		// Invalid study.
		$theResult["isEmpty"] = true;
	}
}
else {
	// Study not specified.
	$theResult["isEmpty"] = true;
}

// Encode the result.
$strRes = json_encode($theResult);
echo $strRes;

?>
