
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

	// Start tracking download progress.
	setTimeout(getDownloadStatus, 
		1000, 
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

		if (statusCompletion.startsWith("failed: ")) {
			// Failed. Update UI.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>' + statusCompletion.substring(8) + '</b></div>');
			$("." + divDownload)[0].scrollIntoView(false);

			$("#" + divBrowse).prop("disabled", false);
			$("#" + divBrowse).css("opacity", 1.0);
		}
		else if (statusCompletion.startsWith("zip_too_big: ")) {
			// Zip file too big. Update UI.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>' + statusCompletion.substring(13) + '</b></div>');
			$("." + divDownload)[0].scrollIntoView(false);

			$("#" + divBrowse).prop("disabled", false);
			$("#" + divBrowse).css("opacity", 1.0);
		}
		else if (statusCompletion == "done") {
			// Done. Update UI.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Downloaded file</b></div>');
			$("." + divDownload)[0].scrollIntoView(false);

			$("#" + divBrowse).prop("disabled", false);
			$("#" + divBrowse).css("opacity", 1.0);
		}
		else if (statusCompletion.startsWith("preparing")) {
			// Still preparing file.
			if (statusCompletion == "preparing") {
				$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Preparing file. Please wait: Do not navigate away from this page until the download is complete.</b></div>');
				$("." + divDownload)[0].scrollIntoView(false);
			}
			else {
				var idx = statusCompletion.indexOf("preparing ");
				if (idx != -1) {
					var percentComplete = statusCompletion.substr(idx + 9);
					$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Preparing file' + percentComplete + '. Please wait: Do not navigate away from this page until the download is complete.</b></div>');
					$("." + divDownload)[0].scrollIntoView(false);
				}
			}

			// Continue tracking download progress.
			setTimeout(getDownloadStatus,
				3000, 
				divDownload, 
				divBrowse, 
				filenameDownloadProgress);
		}
		else {
			// Not finished yet. Update message with download progress.
			$("." + divDownload).html('<div style="background-color:#ffd297;margin-top:5px;max-width:954px;" class="alert alert-custom"><b>Downloading file (' + statusCompletion + '). Please wait: Do not navigate away from this page until the download is complete.</b></div>');
			$("." + divDownload)[0].scrollIntoView(false);

			// Continue tracking download progress.
			setTimeout(getDownloadStatus,
				3000,
				divDownload,
				divBrowse,
				filenameDownloadProgress);
		}
	}).fail(function() {
		console.log("Error retrieving download status");
	})
}

// Retrieve results returned from AJAX call.
function getResults(res) {
	var arrRes = [];
	$.each(res, function(key, value) {
		arrRes[key] = value;
		if ($.isArray(value)) {
			$.each(value, function(key1, value1) {
				arrRes[key1] = value1;
			});
		}
	});

	return arrRes;
}



