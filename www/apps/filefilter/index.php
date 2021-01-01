<?php
	include_once( '../../user/session.php' );
	$conf = file_get_contents( '/usr/local/mobilizeds/conf/mobilizeds.conf' );
	$conf = json_decode( $conf );

	$studyid = 0;
	$groupid = 0;
	$perm = 0;
	$download = 0;
	if (isset($_REQUEST['studyid'])) {
		$studyid = $_REQUEST['studyid'];
	}
	if (isset($_REQUEST['groupid'])) {
		$groupid = $_REQUEST['groupid'];
	}
	if (isset($_REQUEST['perm'])) {
		$perm = $_REQUEST['perm'];
	}
	if (isset($_REQUEST['download'])) {
		$download = $_REQUEST['download'];
	}
	if (isset($_SESSION['email'])) {
		$email = $_SESSION['email'];
	}
	if (isset($_SESSION['userid'])) {
		$userid = $_SESSION['userid'];
	}
	if (isset($_REQUEST['templateid'])) {
		$templateid = $_REQUEST['templateid'];
	}

	// ===== NAVIGATION BREADCRUMB
	$breadcrumb = ' &gt; <a class="btn disabled">Administration</a>';
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

<?php

$directory = '/usr/local/mobilizeds/study/study' . $studyid . '/files';
if (count(glob("$directory/*")) === 0) {
	$data = 0;
}
else {
	$data = 1;
}
$mystring = file_get_contents( "/usr/local/mobilizeds/html/include/js/study" . $studyid . "-fields.js" );
if (strstr($mystring,'filters": []') == false) {
	// Has study metadata.
	$hasMetaData = true;
}
else {
	// No study metadata.
	$hasMetaData = false;
}

?>

<script>

//Check if browser is IE
if (navigator.userAgent.toLowerCase().indexOf(".net") != -1) {
	// insert conditional IE code here
	//alert(navigator.userAgent);
	alert("The Query Data functionality is not supported on IE.  We recommend using Chrome, Firefox, or Edge instead.");
}

</script>

</head>
<body>

<div class="container">

<?php $relative_url = "../../"; include( $relative_url . "banner.php" ); ?>

	<br /><br />

<?php if ($perm): ?>

	<!-- DATA SELECTOR -->
	<div class="panel panel-primary">
		<div class="panel-heading" id="query-builder-heading">
			<h4 class="panel-title">Query Configuration</h4>
		</div>
		<div id="msgSave"></div>
<?php if (!$hasMetaData): ?>
		<!-- Data Status -->
		<div><p><br /><b>* This study currently has no metadata associated with it and cannot be queried.</b></p></div>
<?php else: ?>
		<div class="panel-body">
			<!-- QUERY BUILDER -->
			<div id="builder-panel">
				<div id="panelDirs"></div>
				<br/><br/>
				<div class="clearfix"></div>
				<div id="file-filters"></div>
			</div>
		</div>
<?php endif; ?>

	</div>

<?php else: ?>
	<h1 class="text-primary">Your permissions do not allow access to this study.</h1>
<?php endif ?>
</div>

<script>

$(document).ready(function() {

	// On page load, use AJAX to get directory names and selections.
	var urlGetFileFilters = '<?= $conf->apache->baseurl ?>/request/getFileFilters';
	request = {
		application: '<?= $conf->study->description ?> Datashare',
		url:         '<?= $conf->apache->baseurl ?>/request/getFileFilters',
		session:     '<?= session_id() ?>',
		study:       'study<?= $studyid ?>',
	};
	//console.log(request);
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
				// Display directory name checkboxes and selections.
				showDirectoryCheckboxes(response);
			}
		},
		error:       function( response ) {
			console.log(response);
		},
	});
});


// Based on response, show directory names and select as specified.
var showDirectoryCheckboxes = function(resGetFileFilters) {
	//console.log(resGetFileFilters );

	// Clear display panel.
	$('#panelDirs').html("");

	// Get directory names.
	var theDirs = resGetFileFilters['dirs'];
	// Sort directory names.
	theDirs.sort();

	// Initialize to undefined.
	var dirnamesAdmin;
	var dirnamesUser;
	if (resGetFileFilters.hasOwnProperty('dirnames_admin')) {
		// dirnames_admin specified.
		dirnamesAdmin = resGetFileFilters['dirnames_admin'];
	}
	if (resGetFileFilters.hasOwnProperty('dirnames_user')) {
		// dirnames_user specified.
		dirnamesUser = resGetFileFilters['dirnames_user'];
	}

	var strDirs = "<div class='table-responsive'>" +
		"<table id='theDirs' class='table'>";

	strButtonsUser = '<button type="button" class="selectAll_user btn btn-success btn-lg pull-left"><span class="glyphicon" style="font-size: 16pt; margin-top: 2px;"></span> Select All</button>';
	strButtonsUser += '<button type="button" style="margin-left:5px;" class="deselectAll_user btn btn-success btn-lg pull-left"><span class="glyphicon" style="font-size: 16pt; margin-top: 2px;"></span> Deselect All</button>';

	strButtonsAdmin = '<button type="button" class="selectAll_admin btn btn-success btn-lg pull-left"><span class="glyphicon" style="font-size: 16pt; margin-top: 2px;"></span> Select All</button>';
	strButtonsAdmin += '<button type="button" style="margin-left:5px;" class="deselectAll_admin btn btn-success btn-lg pull-left"><span class="glyphicon" style="font-size: 16pt; margin-top: 2px;"></span> Deselect All</button>';
	strButtonsAdmin += '<button type="button" style="margin-left:5px;" class="submitFileFilters btn btn-success btn-lg pull-right"><span class="glyphicon" style="font-size: 16pt; margin-top: 2px;"></span> Submit</button>';

	strDirs += "<tr><td>" + strButtonsUser + "</td>" +
		"<td>" + strButtonsAdmin + "</td></tr>";

	strDirs += "<td><b>Select directories user can choose from for filtering</b><br/>If no directories are selected in this column, all directories will be returned.</td><td><b>Always include these directories in query results<ab/></td></tr>";

	// Go through all directory names.
	for (cnt = 0; cnt < theDirs.length; cnt++) {
		//console.log(theDirs[cnt]);

		var isCheckedAdmin = false;
		var isCheckedUser = false;

		if (typeof dirnamesAdmin === "undefined" &&
			typeof dirnamesUser === "undefined") {

			// NOTE: file_filter table is empty.
			// Include directory in download. Do not let user choose directory.
			isCheckedAdmin = true;
		}
		if (typeof dirnamesAdmin !== "undefined") {
			// Get admin-selected directory names to match with the directory name..
			isCheckedAdmin = false;
			for (var cntSelected = 0; cntSelected < dirnamesAdmin.length; cntSelected++) {
				if (dirnamesAdmin[cntSelected] == theDirs[cnt]) {
					// Found a matched admin-selected directory name.
					isCheckedAdmin = true;
					break;
				}
			}
		}
		if (typeof dirnamesUser !== "undefined") {
			// Get user-selected directory names to match with the directory name..
			isCheckedUser = false;
			for (var cntSelected = 0; cntSelected < dirnamesUser.length; cntSelected++) {
				if (dirnamesUser[cntSelected] == theDirs[cnt]) {
					// Found a matched user-selected directory name.
					isCheckedUser = true;
					break;
				}
			}
		}

		// User-selected checkbox.
		var strCheckboxUser = "<input type='checkbox' " +
			"class='cbUser' " +
                        "name='" + theDirs[cnt] + "' " +
                        "id='" + theDirs[cnt] + "_user' " +
                        "value='" + theDirs[cnt] + "' ";
		if (isCheckedUser == true) {
			strCheckboxUser += "checked='checked' ";
		}
		strCheckboxUser += ">&nbsp;" + theDirs[cnt];

		// Admin-selected checkbox
		var strCheckboxAdmin = "<input type='checkbox' " +
			"class='cbAdmin' " +
                        "name='" + theDirs[cnt] + "' " +
                        "id='" + theDirs[cnt] + "_admin' " +
                        "value='" + theDirs[cnt] + "' ";
		if (isCheckedAdmin == true) {
			strCheckboxAdmin += "checked='checked' ";
		}
		strCheckboxAdmin += ">&nbsp;" + theDirs[cnt];

		strDirs += "<tr>";
		strDirs += "<td>" + strCheckboxUser + "</td>";
		strDirs += "<td>" + strCheckboxAdmin + "</td>";
		strDirs += "</tr>";
	}

	strDirs += "<tr><td>" + strButtonsUser + "</td>" +
		"<td>" + strButtonsAdmin + "</td></tr>";

	strDirs += "</table></div>";

	$('#panelDirs').append(strDirs);

	// Select all directory names.
	$('.selectAll_user').click(function() {
		$('.cbUser').each(function() {
			this.checked = true;
		});
		$('.cbAdmin').each(function() {
			this.checked = false;
		});
	});

	// Deselect all directory names.
	$('.deselectAll_user').click(function() {
		$('.cbUser').each(function() {
			this.checked = false;
		});
		// Do not need to check Administrator Selection checkboxes.
	});

	// Select all directory names.
	$('.selectAll_admin').click(function() {
		$('.cbAdmin').each(function() {
			this.checked = true;
		});
		$('.cbUser').each(function() {
			this.checked = false;
		});
	});

	// Deselect all directory names.
	$('.deselectAll_admin').click(function() {
		$('.cbAdmin').each(function() {
			this.checked = false;
		});
		// Do not need to check User Selection checkboxes.
	});

	$('.cbUser').click(function() {
		var cbAdmin = "#" + $(this).attr('name') + "_admin";
		if (this.checked) {
			$(cbAdmin).each(function() {
				this.checked = false;
			});
		}
		else {
			// Do not need to check Administrator Selection checkbox.
		}
	});

	$('.cbAdmin').click(function() {
		var cbUser = "#" + $(this).attr('name') + "_user";
		if (this.checked) {
			$(cbUser).each(function() {
				this.checked = false;
			});
		}
		else {
			// Do not need to check User Selection checkbox.
		}
	});

	// Save selected directory names.
	$('.submitFileFilters').click(function() {

		// Get all checked User Selection checkboxes.
		var strFileFiltersUser = "";
		$('.cbUser:checked').each(function() {
			if (strFileFiltersUser == "") {
				strFileFiltersUser = this.name;
			}
			else {
				strFileFiltersUser += ", " + this.name;
			}
		});

		// Get all checked Admin Selection checkboxes.
		var strFileFiltersAdmin = "";
		$('.cbAdmin:checked').each(function() {
			if (strFileFiltersAdmin == "") {
				strFileFiltersAdmin = this.name;
			}
			else {
				strFileFiltersAdmin += ", " + this.name;
			}
		});

		var urlSetFileFilters = '<?= $conf->apache->baseurl ?>/request/setFileFilters';
		request = {
			application: '<?= $conf->study->description ?> Datashare',
			url:         '<?= $conf->apache->baseurl ?>/request/setFileFilters',
			session:     '<?= session_id() ?>',
			study:       'study<?= $studyid ?>',
			file_filters_user : strFileFiltersUser,
			file_filters_admin : strFileFiltersAdmin,
		};
		//console.log(request);
		$.ajax( {
			crossDomain: true,
			data:        JSON.stringify(request),
			dataType:    'json',
			url:         urlSetFileFilters,
			type:        'POST',
			success:     function(response) {
				if (defined(response.error)) {
					console.log(response );
				}
				else {
					// Display directory name checkboxes and selections.
					//console.log(response);
					//alert("The query configuration has been saved.");
					$("#msgSave").html('<div style="background-color:#ffd297;margin-top:5px;" class="alert alert-custom alert-dismissible"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>The query configuration has been saved.</b></div>');
					$("#msgSave")[0].scrollIntoView(false);
				}
			},
			error:       function( response ) {
				console.log(response);
			},
		});
	});
};

</script>

</body>
</html>

