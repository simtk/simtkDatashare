
/**
 * Copyright 2020-2021, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

// Update UI and track download progress.
function trackDownloadProgress(divDownload, 
	divBrowse, 
	divSubmit, 
	filenameDownloadProgress) {

	// Hide submit button once download started.
	$("#" + divSubmit).hide();

	// Disable browse button.
	$("#" + divBrowse).prop("disabled", true);
	$("#" + divBrowse).css("opacity", 0.5);

	// Show download started message.
	$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Downloading file... Please wait. Do not navigate away from this page until the download is complete.</b></div>');
	$("." + divDownload)[0].scrollIntoView(false);

	// Start tracking download progress.
	setTimeout(getDownloadStatus, 
		3000, 
		divDownload, 
		divBrowse, 
		filenameDownloadProgress);
}


// Update download progress.
function getDownloadStatus(divDownload,
	divBrowse,
	filenameDownloadProgress) {

	// Retrieve completion status.
	var theData = new Array();
	theData.push({name: "tokenDownloadProgress", value: filenameDownloadProgress});
	$.ajax({
		url: "/apps/browse/download/getDownloadStatus.php",
		type: "POST",
		data: theData,
		dataType: 'json',
	}).done(function(statusCompletion) {

		// Get completion status.
		statusCompletion = statusCompletion.trim();

		if (statusCompletion != "done") {
			// Not finished yet. Update message with download progress.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Downloading file... (' + statusCompletion + ')  Do not navigate away from this page until the download is complete.</b></div>');

			// Continue tracking download progress.
			setTimeout(getDownloadStatus,
				3000, 
				divDownload, 
				divBrowse, 
				filenameDownloadProgress);
		}
		else {
			// Done. Update UI.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Downloaded file</b></div>');

			$("#" + divBrowse).prop("disabled", false);
			$("#" + divBrowse).css("opacity", 1.0);
		}
	}).fail(function() {
		console.log("Error retrieving download status");
	})
}


