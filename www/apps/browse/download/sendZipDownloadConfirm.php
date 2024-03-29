<head>

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

include_once('../../../user/session.php');
include_once('../../../user/server.php');
include_once('../../../user/checkuser.php');
include_once('../../../baseIncludes.php');
require_once("fileUtils.php");
?>

<link href="../themeDownload.css" rel="stylesheet" />
<script src="utilsDownloadProgress.js"></script>
<link href="license.css" rel="stylesheet" />

</head>

<?php

$strConf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
$conf = json_decode($strConf);

$theURL = $_SERVER['REQUEST_URI'];

$serverName = $_SERVER["SERVER_NAME"];
if (stripos($theURL, "http://") === false &&
	stripos($theURL, "https://") === false &&
	stripos($theURL, $serverName) === false) {
	$theURL = "https://" . $serverName . $theURL;
}

$studyId = 0;
if (isset($_SESSION["study_id"])) {
	$studyId = (int) $_SESSION["study_id"];
}
$groupId = 0;
if (isset($_SESSION["group_id"])) {
	$groupId = (int) $_SESSION["group_id"];
}

$urlBrowse = "https://" . $domain_name . "/plugins/datashare/view.php?id=$groupId&studyid=$studyId";
$urlGenerateZip = str_replace("/sendZipDownloadConfirm.php", "/sendZipDownload.php", $theURL);
$urlDownload = str_replace("/sendZipDownloadConfirm.php", "/sendDownload.php", $theURL);

// Validates user permission.
if (!$perm || $studyId == 0) {
	echo "<h1 class='text-primary'>Your permissions do not allow access to this study.</h1>";
	return;
} 

// Get user id if present.
$userId = 0;
if (isset($_SESSION["userid"])) {
	$userId = (int) $_SESSION["userid"];
}

$strAgreement = "";
$useAgreement = $response_study->use_agreement;
if ($useAgreement == 2) {
	$strAgreement = "MIT";
}
else if ($useAgreement == 3) {
	$strAgreement = "LGPL";
}
else if ($useAgreement == 4) {
	$strAgreement = "GPL";
}
else if ($useAgreement == 6) {
	$strAgreement = "CC BY 4.0";
}
else if ($useAgreement == 7) {
	$strAgreement = "Apache 2.0";
}
else if ($useAgreement == 5) {
	$strAgreement = "Create Commons Attribution-Non-Commercial";
}
else if ($useAgreement == 1) {
	$strAgreement = "Custom";
}
$urlDownload .= "&agreement=" . $strAgreement;

?>

<div class="msgDownload"></div>
<div class="divLicense">

<?php

if ($useAgreement != 0) {
	echo "<div class='divAgreement'><strong>$strAgreement License Agreement:</strong></div>";
	echo "<Textarea disabled rows='25' cols='80' >" .
		html_entity_decode($response_study->custom_agreement) . 
		"</Textarea><br/>";
?>

</div>

<script>
$(document).ready(function() {

	$("#mySubmit").click(function() {

		// Hide the license div once submitted.
		$(".divLicense").hide();

		// Generate a token using remote address, user id, and timestamp.
		var tokenDownloadProgress = "download_" +
			"<?php echo $_SERVER["REMOTE_ADDR"]; ?>" + "." +
			"<?php echo $userId; ?>" + "." +
			"<?php echo microtime(true); ?>";

		// Start tracking of download progress.
		trackDownloadProgress("msgDownload",
			"myBrowse",
			"mySubmit",
			tokenDownloadProgress);

		event.preventDefault();

		// Use AJAX to launch action to:
		// (1) Generate Zip file and wait, or
		// (2) Add to queue to wait for zip generation and return right away.
		$.ajax({
			url: "<?php echo $urlGenerateZip .
				"&agreed=1" .
				"&tokenDownloadProgress=" .
				"download_" . 
				$_SERVER["REMOTE_ADDR"] . "." .
				$userId .  "." .
				microtime(true); ?>",
			type: "POST"
		}).done(function(res) {
			var theResult = JSON.parse(res);

			if (theResult["status"] == "ok") {
				// NOTE: Do not decode URL before sending it out again because the 
				// filesHash parameter is a JSON-stringified array which needs
				// to remain URL-encoded.
				window.open("<?php echo $urlDownload; ?>" +
					"&agreed=1" +
					"&tokenDownloadProgress=" + tokenDownloadProgress +
					"&pathDownload=" + theResult["pathDownload"] +
					"&nameDownload=" + theResult["nameDownload"],
					"_self");
			}
			else if (theResult["status"] == "zip_too_big") {
				// Added to queue.
				$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>A large zip file is generated; will send email when the zip file is ready.<</b></div>');
			}
			else {
				// Zip file generation failed; show error message.
				$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>' + theResult["reason"] + '</b></div>');
			}
		}).fail(function() {
			console.log("Error retrieving download status");
		})
	});

	$("#myBrowse").click(function() {
		event.preventDefault();
		window.location.href = "<?php echo urldecode($urlBrowse); ?>";
	});

});
</script>

<div class="divButtons">
<form id="myForm">
<input type='hidden' name='agreed' value='1' /><br/>
<input type='submit' id='mySubmit' name='submit' value='I Agree & Download Now' class='btn-cta' />
<input type='submit' id='myBrowse' name='browse' value='Return to Study Data' class='btn-cta' />
</form>
</div>

<?php

}
else {
?>

<script>
$(document).ready(function() {

	// Generate a token using remote address, user id, and timestamp.
	var tokenDownloadProgress = "download_" +
		"<?php echo $_SERVER["REMOTE_ADDR"]; ?>" + "." +
		"<?php echo $userId; ?>" + "." +
		"<?php echo microtime(true); ?>";

	// Start tracking of download progress.
	trackDownloadProgress("msgDownload",
		"myBrowse",
		"mySubmit",
		tokenDownloadProgress);

	// Use AJAX to launch action to:
	// (1) Generate Zip file and wait, or
	// (2) Add to queue to wait for zip generation and return right away.
	$.ajax({
		url: "<?php echo $urlGenerateZip .
			"&agreed=1" .
			"&tokenDownloadProgress=" .
			"download_" . 
			$_SERVER["REMOTE_ADDR"] . "." .
			$userId .  "." .
			microtime(true); ?>",
		type: "POST"
	}).done(function(res) {
		var theResult = JSON.parse(res);

		if (theResult["status"] == "ok") {
			// NOTE: Do not decode URL before sending it out again because the 
			// filesHash parameter is a JSON-stringified array which needs
			// to remain URL-encoded.
			window.open("<?php echo $urlDownload; ?>" +
				"&agreed=1" +
				"&tokenDownloadProgress=" + tokenDownloadProgress +
				"&pathDownload=" + theResult["pathDownload"] +
				"&nameDownload=" + theResult["nameDownload"],
				"_self");
		}
		else if (theResult["status"] == "zip_too_big") {
			// Added to queue.
			$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>A large zip file is generated; will send email when the zip file is ready.<</b></div>');
		}
		else {
			// Zip file generation failed; show error message.
			$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>' + theResult["reason"] + '</b></div>');
		}
	}).fail(function() {
		console.log("Error retrieving download status");
	})

	$("#myBrowse").click(function() {
		event.preventDefault();
		window.location.href = "<?php echo urldecode($urlBrowse); ?>";
	});

});
</script>

<div class="divButtons">
<form id="myForm">
<input type='submit' id='myBrowse' name='browse' value='Return to Study Data' class='btn-cta' />
</form>
</div>

<?php
}

?>

