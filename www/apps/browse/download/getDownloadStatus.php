<?php

// Get token filename for file that keeps track of download progress. 
$tokenDownloadProgress = false;
if (isset($_REQUEST["tokenDownloadProgress"])) {
	$tokenDownloadProgress = $_REQUEST["tokenDownloadProgress"];
}

if ($tokenDownloadProgress === false) {
	// Token file not present. Done.
	echo json_encode("done");
	return;
}

if (file_exists("/var/www/apps/browse/download/tokens/" . $tokenDownloadProgress)) {
	// Open file for read only.
	$fpTokenDownloadProgress = fopen("/var/www/apps/browse/download/tokens/" . 
		$tokenDownloadProgress, 
		"r");
	// Content is perecentage of completion; hence, read only up to 20 characacters.
	$strCompletion = fread($fpTokenDownloadProgress, 20);
	fclose($fpTokenDownloadProgress);

	// Return % of completion, or the string "done".
	echo json_encode($strCompletion);
}
else {
	// File has not been created yet.
	echo json_encode("0%");
}

?>
