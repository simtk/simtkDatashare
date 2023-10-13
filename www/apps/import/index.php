<?php

/**
 * Copyright 2020-2023, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

	include_once( '../../user/session.php' );
	$conf = file_get_contents( '/usr/local/mobilizeds/conf/mobilizeds.conf' );
	$conf = json_decode( $conf );

	$studyid = 0;
	if (isset($_REQUEST['studyid'])) {
		$studyid = (int) $_REQUEST['studyid'];
	}
	if (isset($_REQUEST['groupid'])) {
		$groupid = (int) $_REQUEST['groupid'];
	}
	if (isset($_REQUEST['perm'])) {
		$perm = (int) $_REQUEST['perm'];
	}
	if (isset($_REQUEST['download'])) {
		$download = (int) $_REQUEST['download'];
	}
	if (isset($_REQUEST['templateid'])) {
		$templateid = (int) $_REQUEST['templateid'];
	}
	if ($templateid == 2) {
		$template_name = "In Vitro";
	}
	else {
		$template_name = "General";
	}
	if (isset($_SESSION['userid'])) {
		$userid = $_SESSION['userid'];
	}
	if (isset($_SESSION['email'])) {
		$email = $_SESSION['email'];
	}
	if (isset($_SESSION['subject_prefix'])) {
		$subjectPrefix = $_SESSION['subject_prefix'];
	}
	// ===== REDIRECT USER TO LOGIN IF NOT CURRENTLY LOGGED IN OR INSUFFICIENT PERMISSIONS
	/*
	if( ! isset($_SESSION[ "is_auth" ])) { header( "location: /mobilizeds/index.php" ); exit; }
	$can_write = array_key_exists( 'write', $_SESSION[ "permissions" ] ) && $_SESSION[ "permissions" ][ "write" ];
	$is_admin  = array_key_exists( 'admin', $_SESSION[ "permissions" ] ) && $_SESSION[ "permissions" ][ "admin" ];
	if( ! ($can_write || $is_admin )) { header( "location: /mobilizeds/index.php" ); exit; }
	*/

	// ===== NAVIGATION BREADCRUMB
	$breadcrumb = ' &gt; <a class="btn disabled">Import and Edit Data</a>';
?>
<!doctype html>
<html lang="us">
<head>
<meta charset="utf-8" />

<?php
include_once("../../baseIncludes.php");
?>

<script>
	//Check if browser is IE
	if (navigator.userAgent.toLowerCase().indexOf(".net") != -1) {
		// insert conditional IE code here
		//alert(navigator.userAgent);
		alert("The Import/Edit Data functionality is not supported on IE.  We recommend using Chrome, Firefox, or Edge instead.");
	}

	function handleSubmit(submit) {
		// Clear message area.
		$("#msgImportMetadata").html('');

		// Get selected file.
		var volumeId = "l1_";
		var fileType = ".csv";
		var files = fm.selectedFiles();
		if (files.length == 0) {
			// File not selected.
			//console.log("File not selected");
			$("#msgImportMetadata").html('<div class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Please select a Metadata CSV file to process.</b></div>');
			$("#msgImportMetadata")[0].scrollIntoView(false);
			return;
		}
		if (files.length > 1) {
			// More than 1 file selected.
			//console.log("More than 1 file selected");
			$("#msgImportMetadata").html('<div class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Please select only 1 Metadata CSV file to process.</b></div>');
			$("#msgImportMetadata")[0].scrollIntoView(false);
			return;
		}
		if (files[0].hash.indexOf(volumeId) == -1) {
			// Incorrect hash.
			//console.log("Incorrect file hash");
			$("#msgImportMetadata").html('<div class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Incorrect Metadata CSV file hash.</b></div>');
			$("#msgImportMetadata")[0].scrollIntoView(false);
			return;
		}
		var theHash = files[0].hash.substr(volumeId.length);
		theHash = theHash.replace(/-/g, "+");
		theHash = theHash.replace(/_/g, "/");
		theHash = theHash.replace(/\./g, "=");
		// Decode file path from file hash.
		var decodedFilePath = atob(theHash);
		if (decodedFilePath.endsWith(fileType) == false) {
			// Incorrect file type.
			//console.log("Incorrect file type");
			$("#msgImportMetadata").html('<div class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Incorrect file type. Please select Metadata CSV file to process.</b></div>');
			$("#msgImportMetadata")[0].scrollIntoView(false);
			return;
		}
		// Get metadata CSV file path and name, excluding extension.
		var theCSVFile = decodedFilePath.substr(0, decodedFilePath.length - 4);

		// Get values from form.
		var theData = new Array();
		var theStudyId = $("#studyId").val();
		var theHeaderRow = $("#headerRow").val();
		var theSubjectColumn = $("#subjectColumn").val();
		var theSubjectPrefix = $("#subjectPrefix").val();

		var theData = new Array();
		theData.push({name: "nameMetadataCSVFile", value: theCSVFile});
		theData.push({name: "headerRow", value: theHeaderRow});
		theData.push({name: "subjectColumn", value: theSubjectColumn});
		theData.push({name: "subjectPrefix", value: theSubjectPrefix});

		if (submit.value == "Process") {
			// Import selected metadata CSV file.
			// Generate metadata.json files for subjects.
			theData.push({name: "isSave", value: 1});
		}
		else {
			// Verify selected metadata CSV file.
			// Do not generate metadata.json files for subjects.
			theData.push({name: "isSave", value: 0});
		}

		var theURL = 'parseMetadataCSV.php?' + 
			'templateid=<?php echo $templateid; ?>&' +
			'section=<?php echo $_SESSION["section"]; ?>&' +
			'groupid=<?php echo $_SESSION["group_id"]; ?>&' +
			'userid=<?php echo $_SESSION["userid"]; ?>&' +
			'studyid=<?php echo $_SESSION["study_id"]; ?>&' +
			'isDOI=<?php echo $_SESSION["isDOI"]; ?>&' +
			'doi_identifier=<?php echo $_SESSION["doi_identifier"]; ?>&' +
			'token=<?php echo $_SESSION["token"]; ?>&' +
			'private=<?php echo $_SESSION["private"]; ?>&' +
			'member=<?php echo $_SESSION["member"]; ?>&' +
			'firstname=<?php echo urlencode($_SESSION["firstname"]); ?>&' +
			'lastname=<?php echo urlencode($_SESSION["lastname"]); ?>';
		$.ajax({
			type: "POST",
			data: theData,
			dataType: "json",
			url: theURL,
			async: false,
		}).done(function(res) {
			// Success in parse and import CSV file.

			// Retrieve first 2 columns of header of CSV file..
			var strHead = "";
			if (res.hasOwnProperty("header")) {
				var sizeHead = res.header.length;
				if (theSubjectColumn - 1 < sizeHead) {
					strHead += "Column with subject ID: " + 
						res.header[theSubjectColumn - 1];
				}
				if (0 < sizeHead) {
					strHead += "\nFirst subject column: " + res.header[0];
				}
				if (1 < sizeHead) {
					strHead += "\nSecond subject column: " + res.header[1];
				}
				if (strHead != "") {
					strHead += "\n\n";
				}
			}

			var errStatus = "";
			var strErrLog = res.err_log.trim();
			if (strErrLog.indexOf("***ERROR***") != -1) {
				strErrLog = strErrLog.substr(11);
				errStatus = "ERROR - ";
			}
			else if (strErrLog.indexOf("***WARNING***") != -1) {
				strErrLog = strErrLog.substr(13);
				errStatus = "WARNING - ";
			}

			// Show first 2 columns of header if available.
			strErrLog = strHead + strErrLog;

			if (res.status == "Success") {
				// Display result in modal dialog.
				if (res.isSave) {
					// Process.
					if (res.num_of_subjects_save > 0) {
						var strMsg = res.num_of_subjects_avail + 
							" subjects available from metadata CSV file. " +
							res.num_of_subjects_save + 
							" subjects processed.";
						if (strErrLog != "") {
							$("#modalMsgImportMetadata").html(
								"<PRE>" +strErrLog + "</PRE>"
							);
							strMsg += "<br/>" + errStatus +
								"<a href='metadata_ImportCSVFile.php '" +
								"target='_blank'>Click here</a> for instructions to populate from metadata CSV file.";
						}
						else {
							$("#modalMsgImportMetadata").html("");
						}
						$("#modalTitleImportMetadata").html(strMsg);
					}
					else {
						// Subjects not found to populate with metadata.
						var strMsg = res.num_of_subjects_avail + 
							" subjects available from metadata CSV file. " +
							res.num_of_subjects_save + 
							" subjects processed.";
						if (strErrLog != "") {
							$("#modalMsgImportMetadata").html(
								"<PRE>" + strErrLog + "</PRE>"
							);
							strMsg += "<br/>" + errStatus +
								"<a href='metadata_ImportCSVFile.php '" +
								"target='_blank'>Click here</a> for instructions to populate from metadata CSV file.";
						}
						else {
							$("#modalMsgImportMetadata").html("");
						}
						$("#modalTitleImportMetadata").html(strMsg);
					}

					// Update Import Status.
					var theData = new Array();
					theData.push({name: "StudyId", value: <?php echo $studyid; ?>});
					$.ajax({
						type: "POST",
						data: theData,
						dataType: "json",
						url: "getstatus.php",
						async: false,
					}).done(function(res) {
						if (res.indexOf("***DELETION***") != -1) {
							$("div.container").find("#importStatus").html("");
						}
						else {
							$("div.container").find("#importStatus").html("<span><b>Import Status</b><br/>" + res + "</span>");
						}
					}).fail(function() {
					});

					// Display modal message dialog.
					$("#modalImportMetadata").modal("show");
					$("#modalImportMetadata")[0].scrollIntoView(false);

					// Check if metadata are present after metadata import.
					if (res.total_metadata > 0) {
						// Show query and config DIVs.
						$("#queryDiv").show();
						$("#configDiv").show();
					}
					else {
						// Hide query and config DIVs.
						$("#queryDiv").hide();
						$("#configDiv").hide();
					}
					$("#browseDiv")[0].scrollIntoView(false);
				}
				else {
					// Verify.
					var strMsg = res.num_of_subjects_avail + 
						" subjects available from metadata CSV file. " +
						res.num_of_subjects_save + 
						" subjects will be processed.";
					if (strErrLog != "") {
						$("#modalMsgImportMetadata").html(
							"<PRE>" + strErrLog + "</PRE>"
						);
						strMsg += "<br/>" + errStatus +
							"<a href='metadata_ImportCSVFile.php '" +
							"target='_blank'>Click here</a> for instructions to populate from metadata CSV file.";
					}
					else {
						$("#modalMsgImportMetadata").html("");
					}
					$("#modalTitleImportMetadata").html(strMsg);

					// Display modal message dialog.
					$("#modalImportMetadata").modal("show");
					$("#modalImportMetadata")[0].scrollIntoView(false);
				}
			}
			else {
				// Display result in modal dialog.
				if (res.isSave) {
					var strMsg = res.num_of_subjects_avail + 
						" subjects available from metadata CSV file. " +
						res.num_of_subjects_save + 
						" subjects processed.";
					if (strErrLog != "") {
						$("#modalMsgImportMetadata").html(
							"<PRE>" + strErrLog + "</PRE>"
						);
						strMsg += "<br/>" + errStatus +
							"<a href='metadata_ImportCSVFile.php '" +
							"target='_blank'>Click here</a> for instructions to populate from metadata CSV file.";
					}
					else {
						$("#modalMsgImportMetadata").html("");
					}
					$("#modalTitleImportMetadata").html(strMsg);
				}
				else {
					var strMsg = res.num_of_subjects_avail + 
						" subjects available from metadata CSV file. " +
						res.num_of_subjects_save + 
						" subjects will be processed.";
					if (strErrLog != "") {
						$("#modalMsgImportMetadata").html(
							"<PRE>" + strErrLog + "</PRE>"
						);
						strMsg += "<br/>" + errStatus +
							"<a href='metadata_ImportCSVFile.php '" +
							"target='_blank'>Click here</a> for instructions to populate from metadata CSV file.";
					}
					else {
						$("#modalMsgImportMetadata").html("");
					}
					$("#modalTitleImportMetadata").html(strMsg);
				}

				// Update Import Status.
				var theData = new Array();
				theData.push({name: "StudyId", value: <?php echo $studyid; ?>});
				$.ajax({
					type: "POST",
					data: theData,
					dataType: "json",
					url: "getstatus.php",
					async: false,
				}).done(function(res) {
					if (res.indexOf("***DELETION***") != -1) {
						$("div.container").find("#importStatus").html("");
					}
					else {
						$("div.container").find("#importStatus").html("<span><b>Import Status</b><br/>" + res + "</span>");
					}
				}).fail(function() {
				});

				// Display modal message dialog.
				$("#modalImportMetadata").modal("show");
				$("#modalImportMetadata")[0].scrollIntoView(false);
			}
		}).fail(function(res) {
			console.log("Error: " + JSON.stringify(res));
		});
	}

	$(document).ready(function() {
		// Clear message.
		$("#msgImportMetadata").html('');

		// Handle popover show and hide.
		$(".myPopOver").hover(function() {
			$(this).find(".popoverLic").popover("show");
		});
		$(".myPopOver").mouseleave(function() {
			$(this).find(".popoverLic").popover("hide");
		});
	});
</script>

<link href="import.css" rel="stylesheet" />
</head>

<body>
<div class="container">

	<div id="msgImportMetadata"></div>

	<?php $relative_url = "../../"; include( $relative_url . "banner.php" ); ?>

	<br/>
	<b>File Upload</b><br/>
<ul>
<li>Upload files by dragging and dropping them in the "Import and Edit Data" window below.  <b>Note: Filenames that start with a "." cannot be uploaded.</b></li>
<li><b>Compressed files (.zip, .tar.gz, .tar):</b> SimTK will automatically expand compressed files. <b>For the automatic expansion to work propoerly, none of the file and directory names can start with a "."</b></li>
</ul>
	<b>Provide Metadata & Enable Query Feature</b><br/>
	To enable querying of your dataset, you need to <a style="color:#f75236;" href="metadata.php" target="_blank">provide metadata</a>. Options to add metadata:
	<ul>
	<li>SimTK can semi-automatically generate metadata files from a CSV file (See "Populate from Metatadata CSV File" section below).</li>
	<li>You can also explicitly provide metadata via files in each data folder or implicitly via your directory structure. <a style="color:#f75236;" href="metadata.php#provide" target="_blank">More details</a></li>
	</ul>
	<div id="importStatus"></div>
	<br/>

	<div id="modalImportMetadata" 
		class="modal fade" 
		data-keyboard="false" 
		data-backdrop="static" 
		role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div id="modalHeaderImportMetadata" class="modal-header"><b><span id="modalTitleImportMetadata"></span></b><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div id="modalMsgImportMetadata" class="modal-body">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<form id="parseMetadataCSV" 
		method="post" 
		enctype="multipart/form-data">

		<div id="handleMetadataCsvFile"
			class="panel-heading"
			data-toggle="collapse"
			data-target="#importMetadataCsvFile"
			aria-expanded="false"
			aria-controls="importMetadataCsvFile">
			<span>
				<span class="panel-title">Populate from Metadata CSV File</span>
				<span class="arrow-down"></span>
			</span>
		</div>

		<div class="collapse" id="importMetadataCsvFile">
			<div class="card-body" id="cardMetadataCsvFile">
				<div class="containerMetadataCsvFile">
					<div class="row">
						<span class="hdrImport"><b>1. Import CSV file</b></span>
						<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="File must contain a header row. Import file to the same directory level as folders to which metadata files will be added.">?</a></span>
					</div>
					<div class="row">
						<span class="hdrImport"><b>2. Select CSV file from "Import and Edit Data" section</b></span>
					</div>
					<div class="row">
						<span class="hdrImport""><b>3. Specify the following parameters</b></span>
					</div>
					<div class="row">
						<span class="msgImport"">Row number of header </span>
						<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Row where header information is located. Content in the row header should only contain alphanumeric characters in addition to the following characters: ( ) [ ] / ^ _ space">?</a></span>
						<input type="number" id="headerRow" name="headerRow" min="1" max="999" value="1">
					</div>
					<div class="row">
						<span class="msgImport">Column number of column with subject ID </span>
						<span class="myPopOver"><a href="javascript://" class="popoverLic" data-html="true" data-toggle="popover" data-placement="right" data-content="Column mapping: 1 = Column A (e.g., in Excel, Google Spreadsheet), 2 = Column B, 3 = Column C, etc.">?</a></span>
						<input type="number" id="subjectColumn" name="subjectColumn" min="1" max="999" value="1">
					</div>
				</div>
			</div>

			<div class="card-footer">
				<div>
					<button id="btnVerify" class="btn btn-success"
						name="verify_meta" 
						value="Verify"
						onclick="event.preventDefault(); handleSubmit(this)">
						<span class="glyphicon glyphicon-check"></span>
						Verify
					</button>
					<button id="btnProcess" class="btn btn-success"
						name="import_meta" 
						value="Process"
						onclick="event.preventDefault(); handleSubmit(this)">
						<span class="glyphicon glyphicon-cog"></span>
						Process
					</button>
				</div>
			</div>
		</div>

		<input type="hidden" 
			id="studyId" 
			name="studyId" 
			value="<?php echo $studyid; ?>"
		/>
		<input type="hidden" 
			id="subjectPrefix" 
			name="subjectPrefix" 
			value="<?php echo $subjectPrefix; ?>"
		/>
	</form>

	<br/>

	<div class="panel panel-primary">
		<div class="panel-heading">
			<h4 class="panel-title">Import and Edit Data</h4>
		</div>
		<div class="panel-body" id="elfinder"></div>
	</div>
</div>

<script>
	$(window).on("load", function() {
		$(".ui-state-disabled").each(function(index) {
			$(this).css("background-color", "gray");
		});
	});

	var elfinder_options = {
		url: 'php/connector.mobilizeds.php?' +
			'study=<?php echo $studyid; ?>&' +
			'templateid=<?php echo $templateid; ?>&' +
			'section=<?php echo $_SESSION["section"]; ?>&' +
			'groupid=<?php echo $_SESSION["group_id"]; ?>&' +
			'userid=<?php echo $_SESSION["userid"]; ?>&' +
			'studyid=<?php echo $_SESSION["study_id"]; ?>&' +
			'isDOI=<?php echo $_SESSION["isDOI"]; ?>&' +
			'doi_identifier=<?php echo $_SESSION["doi_identifier"]; ?>&' +
			'token=<?php echo $_SESSION["token"]; ?>&' +
			'private=<?php echo $_SESSION["private"]; ?>&' +
			'member=<?php echo $_SESSION["member"]; ?>&' +
			'firstname=<?php echo urlencode($_SESSION["firstname"]); ?>&' +
			'lastname=<?php echo urlencode($_SESSION["lastname"]); ?>',

		uiOptions: {
			// Toolbar buttons.
			toolbar: [
				['home', 'back', 'forward', 'up', 'reload'],
				['mkdir', 'mkfile'],
				['open', 'getfile'],
				['undo', 'redo'],
				['copy', 'cut', 'paste'],
				['duplicate', 'rename', 'edit', 'resize', 'chmod'],
				['selectall', 'selectnone', 'selectinvert'],
				['quicklook', 'info'],
				['search'],
				['view', 'sort'],
				['help'],
				['fullscreen']
			]
		},

		contextmenu : {
			// current directory menu
			cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],

			// current directory file menu
			files  : ['link', '|', 'getfile', '|', 'quicklook', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename', 'resize', '|', 'info']
		}
	};

	$(function() {
		// Generate instance of elfinder and keep handle to the instance.
		fm = $('#elfinder').elfinder( elfinder_options ).elfinder('instance');

		// Update Import Status.
		var theData = new Array();
		theData.push({name: "StudyId", value: <?php echo $studyid; ?>});
		$.ajax({
			type: "POST",
			data: theData,
			dataType: "json",
			url: "getstatus.php",
			async: false,
		}).done(function(res) {
			if (res.indexOf("***DELETION***") != -1) {
				$("div.container").find("#importStatus").html("");
			}
			else {
				$("div.container").find("#importStatus").html("<span><b>Import Status</b><br/>" + res + "</span>");
			}
		}).fail(function() {
		});
	});

</script>

</div>

</div>

</body>
</html>
