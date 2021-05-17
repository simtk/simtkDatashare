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

	include_once( '../../user/session.php' );
	include_once('../../user/server.php');
	$conf = file_get_contents( '/usr/local/mobilizeds/conf/mobilizeds.conf' );
	$conf = json_decode( $conf );

	$studyid = 0;
	$groupid = 0;
	$perm = 0;
	$download = 0;
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
	if (isset($_SESSION['email'])) {
		$email = $_SESSION['email'];
	}
	if (isset($_SESSION['userid'])) {
		$userid = $_SESSION['userid'];
	}
	if (isset($_REQUEST['templateid'])) {
		$templateid = (int) $_REQUEST['templateid'];
	}
	if (isset($_SESSION['subject_prefix']) && $_SESSION['subject_prefix'] != false) {
		$subject_prefix = $_SESSION['subject_prefix'];
	}
	else if (isset($_REQUEST['subject_prefix']) && $_REQUEST['subject_prefix'] != false) {
		$subject_prefix = htmlspecialchars($_REQUEST['subject_prefix']);
	}
	else {
		$subject_prefix = "unknown";
	}

	// ===== REDIRECT USER TO LOGIN IF NOT CURRENTLY LOGGED IN OR INSUFFICIENT PERMISSIONS
	/*
	if ( ! isset($_SESSION[ "is_auth" ])) { header( "location: /mobilizeds/index.php" ); exit; }
	$can_read = array_key_exists( 'read',  $_SESSION[ "permissions" ] ) && $_SESSION[ "permissions" ][ "read" ];
	$is_admin = array_key_exists( 'admin', $_SESSION[ "permissions" ] ) && $_SESSION[ "permissions" ][ "admin" ];
	if ( ! ($can_read || $is_admin )) { header( "location: /mobilizeds/index.php" ); exit; }
	*/

	// ===== NAVIGATION BREADCRUMB
	$breadcrumb = ' &gt; <a class="btn disabled">Search and Download Data</a>';

	// ===== DAILY SNAPSHOT FILE SIZE
	$study       = $conf->study->id;

?>

<!doctype html>
<html lang="us">
<head>

<meta charset="utf-8" />

<?php
include_once("../../baseIncludes.php");
?>

<script src="/include/js/mobilize.js"></script>
<script src="/include/js/study<?=$studyid;?>-fields.js?<?= time();?>"></script>
<script src="/include/js/add-ons/moment.min.js"></script>
<link href="/include/jquery/add-ons/query-builder/query-builder.default.min.css" rel="stylesheet" id="qb-theme" />
<script src="/include/jquery/add-ons/query-builder/query-builder.standalone.min.js"></script>
<link href="/include/bootstrap/add-ons/select/css/bootstrap-select.min.css" rel="stylesheet" />
<script src="/include/bootstrap/add-ons/select/js/bootstrap-select.min.js"></script>
<script src="/include/bootstrap/add-ons/bootbox/bootbox.min.js"></script>

<?php
	$directory = '/usr/local/mobilizeds/study/study' . $studyid . '/files';
	if (count(glob("$directory/*")) === 0) {
		$data = 0;
	}
	else {
		$data = 1;
	}
	$js_file = 1;
	$js_file_path = '/usr/local/mobilizeds/html/include/js/study' . $studyid . '-fields.js';
	$mystring = file_get_contents( "/usr/local/mobilizeds/html/include/js/study$studyid-fields.js" );

	if (strstr($mystring,'filters": []')) {
		$js_file = 0;
	}
?>

<?php if ($data): ?>

<script>

//Check if browser is IE
if (navigator.userAgent.toLowerCase().indexOf(".net") != -1) {
	// insert conditional IE code here
	//alert(navigator.userAgent);
	alert("The Query Data functionality is not supported on IE.  We recommend using Chrome, Firefox, or Edge instead.");
}


// Display directory names for user selection.
function showUserSelectableDirectoryCheckboxes(dirnamesUser, dirnamesAdmin) {

	var subject_prefix = "<?php echo $subject_prefix; ?>";
	var strDirs = "<br/>";
	strDirs += "<div class='query-builder form-inline'>";
	strDirs += "<dl class='rules-group-container'>";
	strDirs += "<span><b>Filter Query Results for Download</b></span>";
	strDirs += "<dt class='rules-group-header'>";
	strDirs += "<dd class='rules-group-body'>";

	strDirs += "<div><input type='radio' id='selAll' name='selType' value='all' onclick='radioAllClickHandler()' checked>&nbsp;<label for='selAll'><span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>Return all files for each '" + subject_prefix + "' that matches the query</span></label></div>";
	strDirs += "<div><input type='radio' id='selMatched' name='selType' value='all' onclick='radioMatchedClickHandler()'>&nbsp;<label for='selMatched'><span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>Return only the specific sub-directories that match the query</span></label></div>";

	if (dirnamesUser.length > 0 && dirnamesUser[0] != "") {
		strDirs += "<div><input type='radio' id='selSpecific' name='selType' value='specific' onclick='radioSpecificClickHandler()'>&nbsp;<label for='selSpecific'><span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>Include only the following directories in the download</span></label></div>";

		// Directories for user to choose from.
		strDirs += "<div style='margin-left:15px;' class='row'>";
		for (var cnt = 0; cnt < dirnamesUser.length; cnt++) {

			// User-selected checkbox.
			var strCheckboxUser = "<input type='checkbox' " +
				"class='cbUser' " +
				"name='" + dirnamesUser[cnt] + "' " +
				"id='" + dirnamesUser[cnt] + "_user' " +
				"value='" + dirnamesUser[cnt] + "' ";
			strCheckboxUser += "onclick='cbUserClickHandler()'>&nbsp;" + "<span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>" + dirnamesUser[cnt] + "</span>";
			strDirs += "<div class='col-md-3'>" + strCheckboxUser + "</div>";
		}
		strDirs += "</div>";
	}

	if (dirnamesAdmin != false && 
		dirnamesAdmin.length > 0 && 
		dirnamesAdmin[0] != "") {

		// Administrator-selected directories.
		strDirs += "<div style='margin-left:15px;' class='row'>";
		strDirs += "<span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>Note:  The directories ";

		for (var cnt = 0; cnt < dirnamesAdmin.length; cnt++) {

			if (cnt > 0) {
				strDirs += ", ";
			}
			strDirs += dirnamesAdmin[cnt];
/*
			// Admin-selected checkbox.
			var strCheckboxAdmin = "<input type='checkbox' " +
				"class='cbAdmin' " +
				"name='" + dirnamesAdmin[cnt] + "' " +
				"id='" + dirnamesAdmin[cnt] + "_user' " +
				"value='" + dirnamesAdmin[cnt] + "' " +
				"checked disabled";
			strCheckboxAdmin += ">&nbsp;" + "<span style='font-weight:400; font-family:Helvetica,Arial,sans-serif;'>" + dirnamesAdmin[cnt] + "</span>";

			strDirs += "<div class='col-md-3'>" + strCheckboxAdmin + "</div>";
*/
		}

		strDirs += " will always be included as part of the download.</span>";
		strDirs += "</div>";
	}
	strDirs += "</dd></dl></div>";

	$("#panelDirs").html(strDirs);
}

$(document).ready(function() {

	$("#builder_group_0").prepend("<span><b>Set Query Rules</b></span><div style='height:15px;'> </div>");

	// On page load, use AJAX to get user-selectable directory names.
	var urlGetFileFilters = '<?= $conf->apache->baseurl ?>/request/getFileFilters';
	request = {
		application: '<?= $conf->study->description ?> Datashare',
		url:         '<?= $conf->apache->baseurl ?>/request/getFileFilters',
		session:     '<?= session_id() ?>',
		study:       'study<?= $studyid ?>',
	};

	$.ajax( {
		crossDomain: true,
		data:        JSON.stringify(request),
		dataType:    'json',
		url:         urlGetFileFilters,
		type:        'POST',
		success:     function(response) {
			if (defined(response.error)) {
				console.log(response );
			}
			else {
				// Display user-selectable directory name checkboxes.
				//console.log(response );
				var dirnamesUser = false;
				var dirnamesAdmin = false;
				if (response.hasOwnProperty('dirnames_user')) {
					// Get dirnames_user specified.
					// That the first element contains "" means 
					// user-selectable directory is not available for choosing.
					dirnamesUser = response['dirnames_user'];
					if (response.hasOwnProperty('dirnames_admin')) {
						dirnamesAdmin = response['dirnames_admin'];
					}
				}
				showUserSelectableDirectoryCheckboxes(dirnamesUser, dirnamesAdmin);
			}
		},
		error:       function( response ) {
			console.log(response);
		},
	});

});

</script>

<?php endif; ?>

</head>
<body>

<div class="container">

<?php $relative_url = "../../"; include( $relative_url . "banner.php" ); ?>

	<br /><br />

<?php if (!$js_file): ?>
	<!-- Data Status -->
	<div><p><br /><b>* This study currently has no metadata associated with it and cannot be queried.</b></p></div>

<?php endif; ?>

<?php if ($perm): ?>

<?php if (!$data): ?>
	<!-- Data Status -->
	<div><p><b>* This study currently has no data to query.</b></p></div>
<?php else: ?>

	<!-- DATA SELECTOR -->
	<div class="panel panel-primary">
		<div class="panel-heading" id="query-builder-heading">
			<h4 class="panel-title">Query</h4>
		</div>
		<div id="msgSave"></div>
		<div class="panel-body">

			<!-- QUERY BUILDER -->
			<div id="builder-panel">
				<div id="builder"></div>
				<div id="panelDirs"></div>
				<button id="submit-query" type="button" class="btn btn-success btn-lg pull-right"><span class="glyphicon glyphicon-search" style="font-size: 16pt; margin-top: 2px;"></span> Search</button>
				<div class="clearfix"></div>
				<div id="search-results-summary"></div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php else: ?>
	<h1 class="text-primary">Your permissions do not allow access to this study.</h1>
<?php endif ?>

<?php if ($download): ?>

	<div id="get-data-panel" class="panel panel-primary panel-collapse collapse">
		<div class="panel-heading">
			<h4 class="panel-title">Get the Data</h4>
		</div>
		<div class="panel-body">
			<div class="input-group input-group-lg">
				<span class="input-group-addon">Comments</span>
				<input type="text" class="form-control" aria-label="User comments" id="comments" placeholder="Optional. Your comments will be stored in a README file as a helpful reminder">
			</div>

			<div class="input-group input-group-lg" style="margin-top: 20px;">
				<span class="input-group-addon" style="width: 122.667px;">Filename</span>
				<input type="text" class="form-control" aria-label="Filename" id="filename" placeholder="<?= $study ?>">
				<span class="input-group-addon">.zip</span>
				<span class="input-group-btn"><button id="download" type="button" class="btn btn-success btn-lg"><span class="glyphicon glyphicon-circle-arrow-down" style="font-size: 16pt; margin-top: 2px;"></span> Get Data</button></span>
			</div>

			<div class="clearfix" style="margin-top: 20px;"></div>
		</div>
	</div>

<?php endif ?>

</div>

<?php if ($data): ?>

<script>

	// ===== INITIALIZE FILETREE AND BUILDER COMPONENTS
	$( '#elfinder' ).elfinder({ url : '../import/php/connector.mobilizeds.readonly.php?study=<?php echo $studyid; ?>' });
	if ( defined( options )) { $( '#builder' ).queryBuilder( options ); }
	// ===== BEHAVIOR FOR ADDING UNITS TO THE RULE
	$( '#builder' ).on( 'afterUpdateRuleFilter.queryBuilder', ( ev, rule ) => {
		var input  = $( '#' + rule.id ).find( '.rule-value-container input.form-control' );
		var button = $( '#' + rule.id ).find( '.rule-filter-container .btn-group' );
		var span   = $( '#' + rule.id ).find( '.input-prefix' );
		var option = options.units[ rule.filter.id ]; if ( ! defined( option )) { return; }
		var unit   = option.unit;
		var prefix = option.prefix;
		if ( prefix ) {
			var newSpan = '<span class="input-prefix text-muted">' + prefix + ' </span> ';
			if ( span.length ) {
				span.html( prefix );
			}
			else {
				button.before( newSpan );
			} // Replace existing span prefix value or create new span
		}
		else {
			if ( span.length ) {
				span.empty();
			}
		} // No prefix, empty out the span
		if ( unit ) {
			input.after( ' ' + unit );
		}
		else {
			input.after( '' );
		}
	});

	$('#builder').on('validationError.queryBuilder', function(e, rule, error, value) {
		var errMsg = "Invalid value for query" + ": " + value;
		$("#msgSave").html('<div style="background-color:#ffd297;margin-top:5px;" ' +
			'class="alert alert-custom alert-dismissible">' +
			'<a href="#" class="close" ' +
			'data-dismiss="alert" ' +
			'aria-label="close">&times;</a>' +
			'<b>' + errMsg + '</b>' +
			'</div>');
		$("#msgSave")[0].scrollIntoView(false);
	});

	// ===== BEHAVIOR FOR BROWSE, QUERY, OR SELECT ALL
	$( '#select-mode' ).find( 'input' ).change( function() {
		$( '#select-mode' ).find( 'label' ).removeClass( 'btn-success' );
		$( '#select-mode' ).find( 'label' ).addClass( 'btn-default' ).prop( 'checked', false );
		$( this ).parent().addClass( 'btn-success' ).prop( 'checked', true );
		var mode = $( this ).prop( 'id' );
		if ( mode == 'query' ) {
			$( '#builder-panel' )  .collapse( 'show' );
			$( '#filetype-panel' ) .collapse( 'show' );
			$( '#get-all-panel' )  .collapse( 'hide' );
			$( '#get-data-panel' ) .collapse( 'hide' );
		}
		else {
			$( '#builder-panel' )  .collapse( 'hide' );
			$( '#filetype-panel' ) .collapse( 'show' );
			$( '#get-all-panel' )  .collapse( 'show' );
			$( '#get-data-panel' ) .collapse( 'hide' );
		}

		updatePlaceholder();
	});

	// ===== BEHAVIOR FOR GET DATA BUTTON
	var updatePlaceholder = function() {
		var filename = 'study<?= $studyid ?>-' + moment().format( 'YYYY-MM-DD-hh-mm' );
		$( '#filename' ).attr( 'placeholder', filename );
	};

	updatePlaceholder();
	var request = undefined;
	var request_stats = undefined;
	var query_hold;


	// ===== BEHAVIOR FOR SUBMITTING A QUERY
	$( '#submit-query' ).click( function() {
		var rules = $( '#builder' ).queryBuilder( 'getRules' );
			var url   = '<?= $conf->apache->baseurl ?>/request/query';
			var url_stats   = '<?= $conf->apache->baseurl ?>/request/insertStats';
			var mode  = $( '#select-mode' ).find( 'input:checked' ).prop( 'id' );

			// NOTE: request contains results here after QueryData button clicked.
			// The var rules contains the query parameters.
			request = {
				application: '<?= $conf->study->description ?> Datashare',
				url:         '<?= $conf->apache->baseurl ?>/apps/query',
				session:     '<?= session_id() ?>',
				study:       'study<?= $studyid ?>',
				subject_prefix:       '<?= $subject_prefix ?>',
				comments:    comments,
				filename:    filename,
				rules:       rules,
				thanks:      'Thank you for using the <?= $conf->study->description ?> Datashare and your interest in <?= $conf->study->description ?>!'
			};

		// Get whether to return all directories or select specific directories.
		// NOTE: variable is global to be passed between query and download..
		useCBUser = false;
		if ($('#selSpecific').length > 0) {
			useCBUser = $('#selSpecific').prop('checked');
			if (useCBUser) {
				var cntChecked = $('.cbUser:checked').length;
				if (cntChecked == 0) {
					$("#msgSave").html('<div style="background-color:#ffd297;margin-top:5px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>Please include at least one directory in the download.</b></div>');
					$("#msgSave")[0].scrollIntoView(false);
				}
			}
		}

		isGetAllSubjDirs = false;
		if ($('#selAll').length > 0) {
			isGetAllSubjDirs = $('#selAll').prop('checked');
		}

		// Get all checked User Selection checkboxes.
		// NOTE: variables are global to be passed between query and download..
		arrFiltersUser = [];
		arrFiltersAdmin = [];
		$('.cbUser:checked').each(function() {
			arrFiltersUser.push(this.name);
		});

		//console.log( request );

		// NOTE: AJAX populates request with contents (e.g. results) from response.
		$.ajax( {
			type:        'POST',
			crossDomain: true,
			url:         url,
			dataType:    'json',
			data:        JSON.stringify( request ),
			success:     function( response ) {
				if ( defined( response.error )) {
					console.log( response );
				}
				else {
					$( '#get-data-panel' ).collapse( 'show' );

					//console.log(response);

					// Get Administrator-Selected directories.
					// NOTE: variable is global to be passed between query and download..
					arrFiltersAdmin = response.file_filters_admin;

					// Filter for selection.
					// Perform file filtering on DataShare query result.
					var myResults = dataShareQueryFileFiltering(response, 
						useCBUser, 
						isGetAllSubjDirs,
						arrFiltersUser,
						arrFiltersAdmin,
						request.subject_prefix);

					// IMPORTANT: Forward the query response to the download request.
					request.results = myResults.results;

					request.nosql          = response.nosql;
					request.readable_query = response.readable_query;
					request.summary        = { bytes: myResults.sumFileSizes, subjects: response.subjects, count: response.count, estimated_size: myResults.estimatedZipFileSize };

					request.uniqueSubjectsLength  = myResults.uniqueSubjectsLength;
					request.uniqueFilesLength  = myResults.uniqueFilesLength;
					request.estimatedZipFileSize  = myResults.estimatedZipFileSize;
					request.bytes = myResults.sumFileSizes;
					request.isGetAllSubjDirs = isGetAllSubjDirs;
					request.allFilesSubjs = response.all_files_subjs;

					query_hold = response.readable_query;

					var paramsExtracted = extractParamsFromQuery(request.readable_query);
					// Check if parameters have been specified by user.
					if (paramsExtracted != false) {
						// NOTE: Pass parameters to request for showing in downloads email.
						request.paramslist = paramsExtracted;

						request_stats = {
							session:    '<?= session_id() ?>',
							userid:     '<?= $userid ?>',
							studyid:     '<?= $studyid ?>',
							groupid:     '<?= $_SESSION['group_id']; ?>',
							firstname:     '<?php echo urlencode($_SESSION['firstname']); ?>',
							lastname:     '<?php echo urlencode($_SESSION['lastname']); ?>',
							email:       '<?= $email; ?>',
							typeid:      '1',
							paramslist:  paramsExtracted,
							bytes:  myResults.sumFileSizes,
							info:        encodeURI(request.readable_query.replace(/'/g, "%27"))
						};
						//console.log( request_stats );

						if (useCBUser != false) {
							if (arrFiltersUser.length > 0) {

								// Add user filters property.
								var strFiltersUser = arrFiltersUser.join();
								request_stats.filters_user = strFiltersUser;

								// NOTE: Pass parameters to request
								// for showing in downloads email.
								request.filters_user = strFiltersUser;
							}
							if (typeof arrFiltersAdmin != "undefined" &&
								arrFiltersAdmin.length > 0) {

								// Add admin filters property.
								var strFiltersAdmin = arrFiltersAdmin.join();
								request_stats.filters_admin = strFiltersAdmin;

								// NOTE: Pass parameters to request
								// for showing in downloads email.
								request.filters_admin = strFiltersAdmin;
							}
						}

						// send ajax command for statistics
						$.ajax( {
							type:        'POST',
							crossDomain: true,
							url:         url_stats,
							dataType:    'json',
							data:        JSON.stringify( request_stats ),
							success:     function( response ) {
								//console.log( response );
							},
						});
					}
				}
			},
			error:       function( response ) {
				console.log( "AJAX Error: Unknown AJAX error." );
			},
		});
	});

	// ===== BEHAVIOR FOR DOWNLOADING
	$('#download').click(function() {
		if (typeof request_stats != "undefined") {
			$("#builder-panel").remove();

			// Hide primary query panel.
			$(".panel-primary").hide("slow");

			// Update fields in request_stats for download for logging the download.
			request_stats.typeid = 2;
			request_stats.bytes = request.bytes;

			// Invoke handler of Get Data button click.
			downloadHandler(request, request_stats);
		}
	});


// User-selectable checkbox click handler.
// Select selSpecific radio button upon click on the checkbox.
function cbUserClickHandler() {
	if ($('#selSpecific').length > 0) {
		$("#selSpecific").prop("checked", true);
	}
}

// Radio button click handlers.
function radioAllClickHandler() {
	// Deselect checkboxes upon click on the radio button.
	$(".cbUser").each(function() {
		$(this).prop("checked", false);
	});
}
function radioMatchedClickHandler() {
	// Deselect checkboxes upon click on the radio button.
	$(".cbUser").each(function() {
		$(this).prop("checked", false);
	});
}
function radioSpecificClickHandler() {
	// Select all checkboxes upon click on the radio button.
	$(".cbUser").each(function() {
		$(this).prop("checked", true);
	});
}


// Perform file filtering on DataShare query result.
function dataShareQueryFileFiltering(response, useCBUser, isGetAllSubjDirs,
	arrFiltersUser, arrFiltersAdmin, subjectPrefix) {

	// Create clone of response.results and delete files to return.
	var myUniquePaths = [];
	var myUniqueSubjects = [];
	var myUniqueFiles = [];
	var mySumFileSizes = 0;
	myResponseResults = JSON.parse(JSON.stringify(response.results));
	//console.log(myResponseResults);
	for (var i in myResponseResults) {
		if (myResponseResults[i].files.length == 0) {
			// Delete files.
			delete myResponseResults[i].files;
		}
	}

	if (useCBUser == false) {

		// No filtering is done. All files resulted from the query are returned.
		// Return all directories.
		// NOTE: response.results is unchanged.
		if (isGetAllSubjDirs == false) {
			$("#search-results-summary").html(`<p><b>${response.subjects.length}</b> ${subjectPrefix} match (${response.subjects.join( ', ' )}). <b>${response.count}</b> Files match. <b>${response.estimated_size}</b> Estimated download file size</p>`);
			response.uniqueFilesLength = response.count;
			response.estimatedZipFileSize = response.estimated_size;
			response.sumFileSizes = response.bytes;
		}
		else {
			$("#search-results-summary").html(`<p><b>${response.subjects.length}</b> ${subjectPrefix} match (${response.subjects.join( ', ' )}). <b>${response.count_all_files}</b> Files match. <b>${response.estimated_size_all_files}</b> Estimated download file size</p>`);
			response.uniqueFilesLength = response.count_all_files;
			response.estimatedZipFileSize = response.estimated_size_all_files;
			response.sumFileSizes = response.bytes_all_files;
		}
		response.uniqueSubjectsLength = response.subjects.length;
		return response;

/*
		// User-selected checkboxes are not present.

		if (typeof arrFiltersAdmin == "undefined") {
			// NOTE: administrator-selected filter is not defined.
			// No filtering is done. All files resulted from the query are returned.
			// NOTE: response.results is unchanged.
			if (isGetAllSubjDirs == false) {
				$("#search-results-summary").html(`<p><b>${response.subjects.length}</b> ${subjectPrefix} match (${response.subjects.join( ', ' )}). <b>${response.count}</b> Files match. <b>${response.estimated_size}</b> Estimated download file size</p>`);
				response.uniqueFilesLength = response.count;
				response.estimatedZipFileSize = response.estimated_size;
				response.sumFileSizes = response.bytes;
			}
			else {
				$("#search-results-summary").html(`<p><b>${response.subjects.length}</b> ${subjectPrefix} match (${response.subjects.join( ', ' )}). <b>${response.count_all_files}</b> Files match. <b>${response.estimated_size_all_files}</b> Estimated download file size</p>`);
				response.uniqueFilesLength = response.count_all_files;
				response.estimatedZipFileSize = response.estimated_size_all_files;
				response.sumFileSizes = response.bytes_all_files;
			}
			response.uniqueSubjectsLength = response.subjects.length;
			return response;
		}
		else {
			// Return using administrator-selected filters only.

			for (var i in response.results) {
				// Initialize each result with an empty array.
				myResponseResults[i].files = [];

				// Check each file in the response for match to filter.
				for (var j in response.results[i].files) {
					//console.log("File " + j + ": " + response.results[i].files[j]);

					var theFileName = response.results[i].files[j];

					// Get array of names from directory levels of each file.
					var arrNameCheck = theFileName.split("/");
					var strForwardPath = "";
					for (var cntName = 0; cntName < arrNameCheck.length; cntName++) {
						// Name at each directory level.
						var theNameCheck = arrNameCheck[cntName];

						if (cntName > 0) {
							// Generate forward path.
							strForwardPath += arrNameCheck[cntName - 1] + "/";
						}

						var matchAdminSelected = false;
						for (var cntFiltersAdmin = 0; cntFiltersAdmin < arrFiltersAdmin.length; cntFiltersAdmin++) {

							// Each value from the administrator-selected filters.
							var theAdminFilter = arrFiltersAdmin[cntFiltersAdmin];

							if (theNameCheck != theAdminFilter) {
								// Not matched. Skip.
								continue;
							}

							// Found a matching name from file with filter.
							// NOTE: Use exact match of folder name and not indexOf().
							// Otherwise, files containing the filter as a substring will be matched. 

							// Add file if not added already.
							if (myUniqueFiles.indexOf(theFileName) == -1) {
								myResponseResults[i].files.push(theFileName);
								myUniqueFiles.push(theFileName);
								mySumFileSizes += response.file_sizes[theFileName];
							}

							// Look up the associated subject.
							var idx = response.results[i].path.indexOf("/");
							if (idx != -1) {
								// Extract subject name from pathname.
								// Subject name is before first "/".
								// Add subject if not added already.
								var theSubject = response.results[i].path.substring(0, idx);
								if (myUniqueSubjects.indexOf(theSubject) == -1) {
									myUniqueSubjects.push(theSubject);
								}
							}
							else {
								// Subject name is at directly at path.
								// Add subject if not added already.
								var theSubject = response.results[i].path;
								if (myUniqueSubjects.indexOf(theSubject) == -1) {
									myUniqueSubjects.push(theSubject);
								}
							}

							// Add forward path if not added already.
							if (myUniquePaths.indexOf(strForwardPath) == -1) {
								myUniquePaths.push(strForwardPath);
							}
						}
					}
				}
			}

			//console.log(myResponseResults);
			//console.log(mySumFileSizes);

			// Multiply by a factor of 0.6 to estimate zip file size.
			var estimatedZipFileSize = mySumFileSizes * 0.6;
			var unit = 'B';
		        if (estimatedZipFileSize > 1024 ) {
				estimatedZipFileSize /= 1024;
				unit = 'KB';
			}
		        if (estimatedZipFileSize > 1024 ) {
				estimatedZipFileSize /= 1024;
				unit = 'MB';
			}
		        if (estimatedZipFileSize > 1024 ) {
				estimatedZipFileSize /= 1024;
				unit = 'GB';
			}
			// Show with 1 decimal.
			estimatedZipFileSize = estimatedZipFileSize.toFixed(1);

			$("#search-results-summary").html(`<p><b>${myUniqueSubjects.length}</b> ${subjectPrefix} match (${myUniqueSubjects.join( ', ' )}). <b>${myUniqueFiles.length}</b> Files match. <b>${estimatedZipFileSize} ${unit}</b> Estimated download file size</p>`);

			// response.results is updated.
			response.uniqueSubjectsLength = myUniqueSubjects.length;
			response.uniqueFilesLength = myUniqueFiles.length;
			response.estimatedZipFileSize = estimatedZipFileSize + unit;
			response.sumFileSizes = mySumFileSizes;
			response.results = myResponseResults;
			return response;
		}
*/
	}

	// Use filter for selection.

	// Populate with files matching the filter.

	for (var i in response.results) {
		// Initialize each result with an empty array.
		myResponseResults[i].files = [];

		// Check each file in the response for match to filter.
		for (var j in response.results[i].files) {
			//console.log("File " + j + ": " + response.results[i].files[j]);

			var theFileName = response.results[i].files[j];

			// Get array of names from directory levels of each file.
			var arrNameCheck = theFileName.split("/");
			var strForwardPath = "";
			for (var cntName = 0; cntName < arrNameCheck.length; cntName++) {
				// Name at each directory level.
				var theNameCheck = arrNameCheck[cntName];

				if (cntName > 0) {
					// Generate forward path.
					strForwardPath += arrNameCheck[cntName - 1] + "/";
				}

				var matchUserSelected = false;
				for (var cntFiltersUser = 0; cntFiltersUser < arrFiltersUser.length; cntFiltersUser++) {

					// Each value from the user-selected filters.
					var theUserFilter = arrFiltersUser[cntFiltersUser];

					if (theNameCheck != theUserFilter) {
						// Not matched. Skip.
						continue;
					}

					// Found a matching name from file with filter.
					// NOTE: Use exact match of folder name and not indexOf().
					// Otherwise, files containing the filter as a substring will be matched. 

					// Add file if not added already.
					if (myUniqueFiles.indexOf(theFileName) == -1) {
						myResponseResults[i].files.push(theFileName);
						myUniqueFiles.push(theFileName);
						mySumFileSizes += response.file_sizes[theFileName];
					}

					// Look up the associated subject.
					var idx = response.results[i].path.indexOf("/");
					if (idx != -1) {
						// Extract subject name from pathname.
						// Subject name is before first "/".
						// Add subject if not added already.
						var theSubject = response.results[i].path.substring(0, idx);
						if (myUniqueSubjects.indexOf(theSubject) == -1) {
							myUniqueSubjects.push(theSubject);
						}
					}
					else {
						// Subject name is directly at path.
						// Add subject if not added already.
						var theSubject = response.results[i].path;
						if (myUniqueSubjects.indexOf(theSubject) == -1) {
							myUniqueSubjects.push(theSubject);
						}
					}

					// Add forward path if not added already.
					if (myUniquePaths.indexOf(strForwardPath) == -1) {
						myUniquePaths.push(strForwardPath);
					}
				}
			}
		}
	}


	// Check administrator-selected filters.

	for (var i in response.results) {

/*
		// If user checkboxes have been selected, process unique subjects.
		// Otherwise, select all administrator selected directories.
		if (arrFiltersUser.length != 0) {
			// Look up the associated subject.
			// Subject name at path.
			var theSubject = response.results[i].path;
			var idx = response.results[i].path.indexOf("/");
			if (idx != -1) {
				// Extract subject name from pathname. Subject name is before first "/".
				theSubject = response.results[i].path.substring(0, idx);
			}
			if (myUniqueSubjects.indexOf(theSubject) == -1) {
				// Subject not matched in user-selected filter.
				// Ignore this subject.
				continue;
			}
		}

		// Subject matched in user-selected filter.
		// Include administrator-selected filter.
*/

		// Check each file in the response for match to filter.
		for (var j in response.results[i].files) {
			//console.log("File " + j + ": " + response.results[i].files[j]);

			var theFileName = response.results[i].files[j];

			// Get array of names from directory levels of each file.
			var arrNameCheck = theFileName.split("/");
			var strForwardPath = "";
			for (var cntName = 0; cntName < arrNameCheck.length; cntName++) {
				// Name at each directory level.
				var theNameCheck = arrNameCheck[cntName];

				if (cntName > 0) {
					// Generate forward path.
					strForwardPath += arrNameCheck[cntName - 1] + "/";
				}

				// If user checkboxes have been selected, process unique paths.
				// Otherwise, select all administrator selected directories.
				if (arrFiltersUser.length != 0) {
					// Check if forward path match any selected by user.
					if (myUniquePaths.indexOf(strForwardPath) == -1) {
						// Does not match path to user-selected directories. Skip.
						continue;
					}
				}

				var matchAdminSelected = false;
				for (var cntFiltersAdmin = 0; cntFiltersAdmin < arrFiltersAdmin.length; cntFiltersAdmin++) {

					// Each value from the administrator-selected filters.
					var theAdminFilter = arrFiltersAdmin[cntFiltersAdmin];

					if (theNameCheck != theAdminFilter) {
						// Not matched. Skip.
						continue;
					}

					// Found a matching name from file with filter.
					// NOTE: Use exact match of folder name and not indexOf().
					// Otherwise, files containing the filter as a substring will be matched. 

					// Add file if not added already.
					if (myUniqueFiles.indexOf(theFileName) == -1) {
						myResponseResults[i].files.push(theFileName);
						myUniqueFiles.push(theFileName);
						mySumFileSizes += response.file_sizes[theFileName];
					}

					// Look up the associated subject again, because the subject may not
					// have been retrieved when there are only administrator-selected directories..
					var idx = response.results[i].path.indexOf("/");
					if (idx != -1) {
						// Extract subject name from pathname.
						// Subject name is before first "/".
						// Add subject if not added already.
						var theSubject = response.results[i].path.substring(0, idx);
						if (myUniqueSubjects.indexOf(theSubject) == -1) {
							myUniqueSubjects.push(theSubject);
						}
					}
					else {
						// Subject name is at directly at path.
						// Add subject if not added already.
						var theSubject = response.results[i].path;
						if (myUniqueSubjects.indexOf(theSubject) == -1) {
							myUniqueSubjects.push(theSubject);
						}
					}
				}
			}
		}
	}

	//console.log(myResponseResults);
	//console.log(mySumFileSizes);

	// Multiply by a factor of 0.6 to estimate zip file size.
	var estimatedZipFileSize = mySumFileSizes * 0.6;
	var unit = 'B';
        if (estimatedZipFileSize > 1024 ) {
		estimatedZipFileSize /= 1024;
		unit = 'KB';
	}
        if (estimatedZipFileSize > 1024 ) {
		estimatedZipFileSize /= 1024;
		unit = 'MB';
	}
        if (estimatedZipFileSize > 1024 ) {
		estimatedZipFileSize /= 1024;
		unit = 'GB';
	}
	// Show with 1 decimal.
	estimatedZipFileSize = estimatedZipFileSize.toFixed(1);

	$("#search-results-summary").html(`<p><b>${myUniqueSubjects.length}</b> ${subjectPrefix} match (${myUniqueSubjects.join( ', ' )}). <b>${myUniqueFiles.length}</b> Files match. <b>${estimatedZipFileSize} ${unit}</b> Estimated download file size</p>`);

	// response.results is updated.
	response.uniqueSubjectsLength = myUniqueSubjects.length;
	response.uniqueFilesLength = myUniqueFiles.length;
	response.estimatedZipFileSize = estimatedZipFileSize + unit;
	response.sumFileSizes = mySumFileSizes;
	response.results = myResponseResults;
	return response;
}

// Generate verbose parameters list from the nosql query.
function extractParamsFromQuery(theQuery) {

	var strExtracted = theQuery;

	var idxStart = theQuery.toLowerCase().indexOf(" where (");
	if (idxStart == -1) {
		// Token not found.
		return false;
	}
	else {
		idxStart += 8;
	}
	var idxEnd = theQuery.lastIndexOf(")");
	if (idxEnd == -1) {
		// Token not found.
		return false;
	}
	var strWhere = theQuery.substring(idxStart, idxEnd);

	// Remove int and float from attribute.
	strWhere = strWhere.replace(/::int/g, "");
	strWhere = strWhere.replace(/::float/g, "");
	// Remove leading and trailing parentheses for attribute.
	strWhere = strWhere.replace(/\(\{/g, "{");
	strWhere = strWhere.replace(/\}\)/g, "}");
	// Use = rather than ~* for radio button.
	strWhere = strWhere.replace(/ \~\* /g, " = ");
	// Remove single and double quotes.
	strWhere = strWhere.replace(/\'/g, "");
	strWhere = strWhere.replace(/\"/g, "");
	strWhere = strWhere.replace(/%27/g, "");
	strWhere = strWhere.replace(/%22/g, "");
	// Remove special characters for attribute.
	strWhere = strWhere.replace(/\\\m/g, "");
	strWhere = strWhere.replace(/\\\M/g, "");
	// Remove leading "json->>" for attribute.
	strWhere = strWhere.replace(/json->>/g, "");

	return strWhere;
}


// Handle Get Data button click.
function downloadHandler(request, request_stats) {

	// Prepare request to send to confirmation page.
	request.filename   = $( '#filename' ).val() || $( '#filename' ).prop( 'placeholder' );
	request.comments   = $( '#comments' ).val();
	request.study_id    = '<?= $studyid; ?>';
	request.group_id  = '<?= $_SESSION['group_id']; ?>';
	request.group_name = '<?php echo str_replace("'", "%27", $_SESSION['group_name']); ?>';
	request.study_name  = '<?= $_SESSION['study_name']; ?>';
	var filters_dir = "";
	if ((typeof request.filters_user != "undefined" &&
		request.filters_user.trim() != "") ||
		(typeof request.filters_admin != "undefined" &&
                       request.filters_admin.trim() != "")) {
		filters_dir = "\nDirectories: ";
	};
	if (typeof request.filters_user != "undefined" &&
		request.filters_user.trim() != "") {
		filters_dir += request.filters_user;
	}
	if (typeof request.filters_admin != "undefined" &&
		request.filters_admin.trim() != "") {
		if (typeof request.filters_user != "undefined" &&
			request.filters_user.trim() != "") {
			filters_dir += ",";
		}
		filters_dir += request.filters_admin;
	}
	request.filters_dir = filters_dir;
	var user_email = '<?= $email; ?>';
	request.user_email = user_email;

<?php
	// Generate URL for loading file.
	$urlSendFileParams = "";
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
		// Construct URL from $_SESSION tokens since the 
		// HTTP_REFERER does not have the information.
		// This case happens when this page is navigated from
		// the Query, Import, or Query Config pages, rather than
		// loaded from the SimTK DataShare view page.
		$urlSendFileParams = "section=" . $_SESSION["section"] .
			"&groupid=" . $_SESSION["group_id"] .
			"&id=" . $_SESSION["group_id"] .
			"&userid=" . $_SESSION["userid"] .
			"&studyid=" . $_SESSION["study_id"] .
			"&isDOI=" . $_SESSION["isDOI"] .
			"&doi_identifier=" . $_SESSION["doi_identifier"] .
			"&token=" . $_SESSION["token"] .
			"&private=" . $_SESSION["private"] .
			"&member=" . $_SESSION["member"] .
			"&firstname=" . urlencode($_SESSION["firstname"]) .
			"&lastname=" . urlencode($_SESSION["lastname"]);
	}
?>

	// Destination.
	var urlStr = "<?php echo $urlSendFileParams; ?>";
	var theServer = "<?php echo $_SERVER["SERVER_NAME"]; ?>";
	var simtkServer = "<?php echo $domain_name; ?>";
	var userId = '<?php echo $_SESSION["userid"]; ?>';

	// Set up URL for downloading through email.
	request.urlDownload = "https://" + simtkServer +
		"/plugins/datashare/view.php?" + urlStr;

	// Dynamically set up form, submit data and redirect to the confirmation page.
	var formConfirm = $(document.createElement('form'));

	// NOTE: Use the GET method to ensure that parameters are passed
	// along this process, because the login page exited from the iframe
	// and uses the parent frame.
	$(formConfirm).attr("method", "POST");

	$(formConfirm).attr("action", 
		"https://" + theServer + 
		"/apps/browse/download/sendPackageConfirm.php");

	// JSON-encode the request info and append as a hidden input to the form.
	var jsonCustData = JSON.stringify(request);
	var inputCustData = $("<input>").attr("type", "hidden").attr("name", "custData").val(jsonCustData);
	$(formConfirm).append($(inputCustData));

<?php
	// Get parameters and append each to form as hidden input.
	$arrURL = explode("&", $urlSendFileParams);
	foreach ($arrURL as $theParam) {  
		$idx = strpos($theParam, "=");
		if ($idx !== false) {
			$theKey = substr($theParam, 0, $idx);
			$theVal = substr($theParam, $idx + 1);
?>
			var inputParam = $("<input>").attr("type", "hidden").attr("name", "<?php echo $theKey; ?>").val("<?php echo $theVal; ?>");
			$(formConfirm).append($(inputParam));
<?php
		}
	}
?>

	$("body").append(formConfirm);
	$(formConfirm).submit();
}

</script>

<?php endif; ?>

</body>
</html>

