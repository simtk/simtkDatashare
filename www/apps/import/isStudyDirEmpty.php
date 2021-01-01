<?php

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
