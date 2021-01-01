<?php

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
