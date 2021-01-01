<head>

<?php
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
$urlDownload = str_replace("/sendDownloadConfirm.php", "/sendDownload.php", $theURL);

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

		window.open("<?php echo urldecode($urlDownload); ?>" +
			"&agreed=1" +
			"&tokenDownloadProgress=" + tokenDownloadProgress,
			"_self");
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

	window.open("<?php echo urldecode($urlDownload); ?>" +
		"&agreed=1" +
		"&tokenDownloadProgress=" + tokenDownloadProgress,
		"_self");

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

