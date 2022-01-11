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

// Check validity of user and study. Retrieve information from study.
function checkUserAndStudy($theDomainName,
	$theApiKey,
	$theUserId,
	$theToken,
	$theStudyId,
	$theGroupId,
	$theSection,
	&$response_study) {

	$url = "https://$theDomainName/plugins/api/index.php?" .
		"key=$theApiKey" .
		"&userid=" . $theUserId .
		"&token=" . $theToken . 
		"&studyid=" . $theStudyId .
		"&groupid=" . $theGroupId .
		"&action=14" .
		"&tool=" . $theSection;
	$context = array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);
	$response_study_json = file_get_contents($url, false, stream_context_create($context));
	$response_study = json_decode($response_study_json);

	if ($response_study == null || !$response_study->study_valid) {
		// Invalid user or study.
		return false;
	}

	// User and study are valid.
	return true;
}


?>

