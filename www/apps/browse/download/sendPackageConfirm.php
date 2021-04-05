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

// NOTE: The parameter custData is sent as a JSON-encoded string.
// $custData is an object in JSON format. 
if (isset($_REQUEST["custData"])) {
	$custData = $_REQUEST["custData"];
}

// Remove the parameter custData before proceeding.
// Otherwise, window.open() would fail.
$urlFront = "";
$urlBack = "";
$idxStart = stripos($theURL, "custData="); 
if ($idxStart !== false) {
	$urlFront = substr($theURL, 0, $idxStart);
	$urlTmp = substr($theURL, $idxStart);
	$idxEnd = stripos($urlTmp, "&");
	if ($idxEnd !== false) {
		$urlBack = substr($urlTmp, $idxEnd);
		$theURL = $urlFront . $urlBack;
	}
}

if (strpos($theURL, "?") === false) {
	$theURL .= "?";
}
else {
	$theURL .= "&";
}
foreach ($_REQUEST as $k=>$v) {
	if ($k == "custData") {
		continue;
	}
	$theURL .= $k . "=" . $v . "&";
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
$urlPackage = str_replace("/sendPackageConfirm.php", "/sendPackage.php", $theURL);

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
$urlPackage .= "agreement=" . $strAgreement;

?>

<div class="msgDownload"></div>
<div class="divLicense">

<?php

if ($useAgreement != 0) {

	// Using license agreement.
	// Display and prompt for acknowledgement.

	echo "<div class='divAgreement'><strong>$strAgreement License Agreement:</strong></div>";
	echo "<Textarea disabled rows='25' cols='80' >" .
		html_entity_decode($response_study->custom_agreement) . 
		"</Textarea><br/>";

?>

</div>

<script>

$(document).ready(function() {

	$("#mySubmit").click(function() {

		event.preventDefault();

		// Hide the license div once submitted.
		$(".divLicense").hide();
		// Hide the submit button.
		$("#mySubmit").hide();

		// Disable browse button.
		$("#myBrowse").prop("disabled", true);
		$("#myBrowse").css("opacity", 0.5);

		// Show packaging message.
		$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Preparing data. Please wait: Do not navigate away from this page until data retrieval is complete.</b></div>');
		$(".msgDownload")[0].scrollIntoView(false);

		// Generate package and get URL.
		var urlDownload = '<?= $conf->apache->baseurl ?>/request/download';
		var urlStats = '<?= $conf->apache->baseurl ?>/request/insertStats';
		$.ajax({
			type: 'POST',
			crossDomain: true,
			url: urlDownload,
			dataType: 'json',
			data: <?php echo json_encode($custData); ?>,
			success: function(response) {
				if (typeof response.error != "undefined") {
					console.log(response);
				}
				else {
					if (!response.emailed) {
						var packageUrl =  response.package.url;
						downloadPackage(packageUrl);
					}
					else {
						// Large file for logged-in user. Package emailed.
						$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Your query is being packaged.<br/>When it' + "'" + 's ready, an email will be sent<?php

if (isset($_SESSION["email"]) && 
	trim($_SESSION["email"]) != "") {
	// User is logged in and email address is present.
	echo " to " . $_SESSION["email"];
}

?> to download the results</b></div>');
						$(".msgDownload")[0].scrollIntoView(false);

						// Re-enable browse button.
						$("#myBrowse").prop("disabled", false);
						$("#myBrowse").css("opacity", 1.0);
					}
				}
			},
			error: function(response) {
				console.log("AJAX Error: Unknown AJAX error.");
			},
		});

		// Download package.
		function downloadPackage(packageUrl) {
			if (packageUrl !== "") {
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

				window.open("<?php echo urldecode($urlPackage); ?>" +
					"&agreed=1" +
					"&namePackage=" + packageUrl +
					"&tokenDownloadProgress=" + tokenDownloadProgress,
					"_self");
			}
		}
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

	// Not using license.
	// Proceed to download.
?>

<script>

$(document).ready(function() {

	// Disable browse button.
	$("#myBrowse").prop("disabled", true);
	$("#myBrowse").css("opacity", 0.5);

	// Show packaging message.
	$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Preparing data. Please wait: Do not navigate away from this page until data retrieval is complete.</b></div>');
	$(".msgDownload")[0].scrollIntoView(false);

	// Generate package and get URL.
	var urlDownload = '<?= $conf->apache->baseurl ?>/request/download';
	var urlStats = '<?= $conf->apache->baseurl ?>/request/insertStats';
	$.ajax({
		type: 'POST',
		crossDomain: true,
		url: urlDownload,
		dataType: 'json',
		data: <?php echo json_encode($custData); ?>,
		success: function(response) {
			if (typeof response.error != "undefined") {
				console.log(response);
			}
			else {
				if (!response.emailed) {
					var packageUrl =  response.package.url;
					downloadPackage(packageUrl);
				}
				else {
					// Large file for logged-in user. Package emailed.
					$(".msgDownload").html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Your query is being packaged.<br/>When it' + "'" + 's ready, an email will be sent<?php

if (isset($_SESSION["email"]) && 
	trim($_SESSION["email"]) != "") {
	// User is logged in and email address is present.
	echo " to " . $_SESSION["email"];
}

?> to download the results</b></div>');
					$(".msgDownload")[0].scrollIntoView(false);

					// Re-enable browse button.
					$("#myBrowse").prop("disabled", false);
					$("#myBrowse").css("opacity", 1.0);
				}
			}
		},
		error: function(response) {
			console.log("AJAX Error: Unknown AJAX error.");
		},
	})
	.fail(function(response) {
		console.log("AJAX Error: Unknown AJAX error.");
	});

	$("#myBrowse").click(function() {
		event.preventDefault();
		window.location.href = "<?php echo urldecode($urlBrowse); ?>";
	});

	// Download package.
	function downloadPackage(packageUrl) {
		if (packageUrl !== "") {
			window.open("<?php echo urldecode($urlPackage); ?>&agreed=1&namePackage=" + 
				packageUrl, "_self");
		}
	}

	// Download package.
	function downloadPackage(packageUrl) {
		if (packageUrl !== "") {
			// Generate a token using remote address, user id, and timestamp.
			var tokenDownloadProgress = "download_" +
				"<?php echo $_SERVER["REMOTE_ADDR"]; ?>" + "." +
				"<?php echo $userId; ?>" + "." +
				"<?php echo microtime(true); ?>";

			// Start tracking of download progress.
			trackDownloadProgress("msgDownload",
				"myBrowse",
				"mySubmit", // NOTE: not present.
				tokenDownloadProgress);

			window.open("<?php echo urldecode($urlPackage); ?>" +
				"&agreed=1" +
				"&namePackage=" + packageUrl +
				"&tokenDownloadProgress=" + tokenDownloadProgress,
				"_self");
		}
	}
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

