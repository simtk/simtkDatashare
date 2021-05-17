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

?>
			<div class="banner">
				<div class="clearfix">

				</div>
			</div>

<?php require_once "checkMetadata.php"; ?>
<?php require_once "user/server.php"; ?>

<?php

// Get type of download confirmation and parameters, if any.
if (isset($_REQUEST['typeConfirm']) && $_REQUEST['typeConfirm']) {
	$typeConfirm = (int) $_REQUEST['typeConfirm'];
}
else {
	$typeConfirm = 0;
}

if (isset($_REQUEST['nameDownload']) && trim($_REQUEST['nameDownload']) != "") {
	$nameDownload = htmlspecialchars(trim($_REQUEST['nameDownload']));
}
else {
	$nameDownload = "";
}

if (isset($_REQUEST['filesHash']) && trim($_REQUEST['filesHash']) != "") {
	$strFilesHash = trim($_REQUEST['filesHash']);
}
else {
	$strFilesHash = "";
}

if (isset($_REQUEST['pathSelected']) && trim($_REQUEST['pathSelected']) != "") {
	$pathSelected = htmlspecialchars(trim($_REQUEST['pathSelected']));
}
else {
	$pathSelected = "";
}

if (isset($_REQUEST['namePackage']) && trim($_REQUEST['namePackage']) != "") {
	$namePackage = htmlspecialchars(trim($_REQUEST['namePackage']));
}
else {
	$namePackage = "";
}

// Set up parameters.
if (isset($_SESSION["section"]) &&
	isset($_SESSION["group_id"]) &&
	isset($_SESSION["userid"]) &&
	isset($_SESSION["study_id"]) &&
	isset($_SESSION["isDOI"]) &&
	isset($_SESSION["doi_identifier"]) &&
	isset($_SESSION["token"]) &&
	isset($_SESSION["private"]) &&
	isset($_SESSION["member"]) &&
	isset($_SESSION["firstname"]) &&
	isset($_SESSION["lastname"])) {
	$urlSendFileParams = "section=" . $_SESSION["section"] .
		"&groupid=" . $_SESSION["group_id"] .
		"&userid=" . $_SESSION["userid"] .
		"&studyid=" . $_SESSION["study_id"] .
		"&isDOI=" . $_SESSION["isDOI"] .
		"&doi_identifier=" . $_SESSION["doi_identifier"] .
		"&token=" . $_SESSION["token"] .
		"&private=" . $_SESSION["private"] .
		"&member=" . $_SESSION["member"] .
		"&firstname=" . $_SESSION["firstname"] .
		"&lastname=" . $_SESSION["lastname"];
}

if ($nameDownload != "") {
	$urlSendFileParams .= "&nameDownload=" . $nameDownload;
}
if ($strFilesHash != "") {
	$urlSendFileParams .= "&filesHash=" . urlencode($strFilesHash);
}
if ($namePackage != "") {
	$urlSendFileParams .= "&namePackage=" . $namePackage;
}

?>

<script>
	var typeConfirm = "<?php echo $typeConfirm; ?>";
</script>

			<!-- NOTE: use GET, not POST, in FORM. Otherwise, back button has problem! -->
			<form name="form-browse" action="<?= $relative_url ?>apps/browse/" method="get">
				<input type="hidden" name="studyid" value="<?= $studyid ?>">
				<input type="hidden" name="groupid" value="<?= $groupid ?>">
				<input type="hidden" name="perm" value="<?= $perm ?>">
				<input type="hidden" name="download" value="<?= $download ?>">
				<input type="hidden" name="templateid" value="<?= $templateid ?>">
				<input type="hidden" name="userid" value="<?= $userid ?>">
				<input type="hidden" name="email" value="<?= $email ?>">
				<input type="hidden" name="pathSelected" value="<?= $pathSelected ?>">
			</form>
			<form name="form-search" action="<?= $relative_url ?>apps/query/" method="get">
				<input type="hidden" name="studyid" value="<?= $studyid ?>">
				<input type="hidden" name="groupid" value="<?= $groupid ?>">
				<input type="hidden" name="perm" value="<?= $perm ?>">
				<input type="hidden" name="download" value="<?= $download ?>">
				<input type="hidden" name="templateid" value="<?= $templateid ?>">
				<input type="hidden" name="userid" value="<?= $userid ?>">
				<input type="hidden" name="email" value="<?= $email ?>">
			</form>
			<form name="form-import" action="<?= $relative_url ?>apps/import/" method="get">
				<input type="hidden" name="studyid" value="<?= $studyid ?>">    
				<input type="hidden" name="groupid" value="<?= $groupid ?>">
				<input type="hidden" name="perm" value="<?= $perm ?>">
				<input type="hidden" name="download" value="<?= $download ?>">
				<input type="hidden" name="templateid" value="<?= $templateid ?>">
				<input type="hidden" name="userid" value="<?= $userid ?>">
				<input type="hidden" name="email" value="<?= $email ?>">
				<input type="hidden" name="pathSelected" value="<?= $pathSelected ?>">
			</form>
			<form name="form-filefilter" action="<?= $relative_url ?>apps/filefilter/" method="get">
				<input type="hidden" name="studyid" value="<?= $studyid ?>">    
				<input type="hidden" name="groupid" value="<?= $groupid ?>">
				<input type="hidden" name="perm" value="<?= $perm ?>">
				<input type="hidden" name="download" value="<?= $download ?>">
				<input type="hidden" name="templateid" value="<?= $templateid ?>">
				<input type="hidden" name="userid" value="<?= $userid ?>">
				<input type="hidden" name="email" value="<?= $email ?>">
			</form>

<?php if ($typeConfirm == 0): ?>
<?php if (isset($perm)): ?>
			<div class="row">
<?php if ($perm): ?>
				<div id="browseDiv" class="col-sm-3" ><a class="btn btn-block btn-lg btn-success" href="#" onclick="document.forms['form-browse'].submit();"><span class="glyphicon glyphicon-search"></span> Browse Data</a></div>

				<div id="queryDiv" class="col-sm-3" ><a class="btn btn-block btn-lg btn-success" href="#"><span class="glyphicon glyphicon-search"></span> Query Data</a></div>

<?php if ($perm > 2): ?>

<?php if (!isset($_SESSION['isDOI']) || !$_SESSION['isDOI'] || !isset($_SESSION['doi_identifier']) || empty($_SESSION['doi_identifier'])) { ?>
				<div id="importDiv" class="col-sm-3" ><a class="btn btn-block btn-lg btn-warning" href="#" onclick="document.forms['form-import'].submit();"><span class="glyphicon glyphicon-cloud-upload"></span> Import/Edit Data</a></div>

				<div id="configDiv" class="col-sm-3" ><a class="btn btn-block btn-lg btn-warning" href="#" onclick="document.forms['form-filefilter'].submit();"><span class="glyphicon glyphicon-wrench"></span> Query Config</a></div>

<?php } ?>

<?php endif ?>

<?php elseif ($login_required): ?>
				<h3 class="text-primary">Login is required.</h3>
<?php else: ?>
				<h3 class="text-primary">You do not have permission to access this study.</h3>
<?php endif ?>
			</div>
<?php else: ?>
			<div class="row">
				<h3 class="text-primary">Error retrieving session permissions.</h3>
			</div>
<?php endif ?>
<?php endif ?>

<script>

	// Click "Browse Data" button if the class "panel-primary" for DataShare 
	// (".panel-primary") is not shown after the page is loaded.
	$(document).ready(function() {

		var theURL = "";
		if (typeConfirm == 1) {
			theURL = "/apps/browse/download/sendReleaseConfirm.php?";
		}
		else if (typeConfirm == 2) {
			//theURL = "/apps/browse/download/sendPackageConfirm.php?";
			document.forms['form-search'].submit();
			return;
		}
		else if (typeConfirm == 3) {
<?php
			if (isset($_REQUEST["filesHash"]) &&
				trim($_REQUEST["filesHash"]) != "") {
?>
				// Set up Zip download for files hash.
				theURL = "/apps/browse/download/sendZipDownloadConfirm.php?";
<?php
			}
			else {
?>
				theURL = "/apps/browse/download/sendDownloadConfirm.php?";
<?php
			}
?>
		}
		else if ("<?php echo trim($namePackage); ?>" != "") {
			theURL = "/apps/browse/download/sendPackageEmailed.php?";
		}
		if (theURL != "") {
			theURL += "<?php echo $urlSendFileParams; ?>";
			// Redirect to the confirmation page.
			window.location.href = theURL;
			return;
		}

		$("#queryDiv").click(function() {
			// Find userid by looking up from document.referrer.
			var theUserId = 0;
			var theReferrer = document.referrer;
			var simtkServer = "<?php echo $domain_name; ?>";
			// Try with leading "&".
			var idxStart = theReferrer.indexOf("&userid=");
			if (idxStart == -1) {
				// Not found. Try with leading "?".
				idxStart = theReferrer.indexOf("?userid=");
			}
			if (idxStart != -1) {
				// Found userid.
				var tmpStr = theReferrer.substring(idxStart + 8);
				// Find terminating "&" if any.
				var idxEnd = tmpStr.indexOf("&");
				if (idxEnd != -1) {
					theUserId = tmpStr.substring(0, idxEnd);
				}
				else {
					// No terminating "&".
					// userid is the last parameter.
				}
				theUserId = theUserId.trim();
				if (theUserId == "") {
					// The userid parameter is empty.
					theUserId = 0;
				}
				theUserId = parseInt(theUserId);
				if (!Number.isInteger(theUserId)) {
					// Invalid value.
					theUserId = 0;
				}
			}
			if (theUserId == 0) {
				// User not logged in.

				if (theReferrer.indexOf("&typeConfirm=2") == -1) {
					var urlLogin = "https://" + simtkServer +
						"/plugins/datashare/userLogin.php";
					// Change form action to prompt user log in.
					$("form[name='form-search']").attr("action", urlLogin);

					var inputParam = $("<input>").attr("type", "hidden").attr("name", "typeConfirm").val("2");
					$("form[name='form-search']").append($(inputParam));
				}
			}
			document.forms['form-search'].submit();
		});

		// Check whether permissions message is shown. 
		// If not, show elfinder if not shown already.
		if (!$(".text-primary").length && 
			!$(".panel-primary").is(":visible")) {

			// Click "Browse Data" button to show elfinder.
			document.forms['form-browse'].submit();
		}

// Update the buttons statuss based on existence of metadata.
// NOTE: The buttons, if present and found, will be updated.
// Depending on permissions, the buttons may not be present.
<?php if (isset($cntMetaData) && $cntMetaData > 0) { ?>
		// Has metadata.
		// Show query and config buttons if the buttons are present.
		$("#queryDiv").show();
		$("#configDiv").show();
<?php } else { ?>
		// No metadata.
		// Hide query and config buttons if the buttons are present.
		$("#queryDiv").hide();
		$("#configDiv").hide();
<?php } ?>

	});

</script>

