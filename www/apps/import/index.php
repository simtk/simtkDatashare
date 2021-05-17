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

	$(document).ready(function() {
		// Adjust container width.
		// Otherwise, the container size does not match after manual resizing.
		$(".panel-body").resize(function() {
			if ($(this).width() > 0) {
				// Adjust only if width is greater than zero.
				// During initial loading, this width may be negative. Ignore.
				$(".panel-primary").width($(this).width() + 2);
			}
		});
	});
</script>
</head>

<body>
<div class="container">

	<?php $relative_url = "../../"; include( $relative_url . "banner.php" ); ?>

	<br/>
	<b>File Upload:</b><br/>
	<p>Upload files by dragging and dropping them in the "Import and Edit Data" window below.  <b>Note: Filenames that start with a "." cannot be uploaded.</b> SimTK will automatically expand files with the following suffixes: .zip, .tar.gz, .tar.</p>
	<b>Enabling Query:</b><br/>
	<p>To enable querying of your dataset, you need to <a style="color:#f75236;" href="metadata.php" target="_blank">provide metadata</a>. Metadata can be provided explicitly via a file or implicitly via your directory structure.</p><br/>
	<div id="importStatus"></div>
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
		$('#elfinder').elfinder( elfinder_options );

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
				$("div.container").find("#importStatus").html("<span><b>Import Status:</b><br/>" + res + "</span>");
			}
		}).fail(function() {
		});
	});

</script>

</div>

<br/>
<br/>
<br/>
<br/>
<br/>
<br/>
<br/>

<!---
<p><a href="" onclick="return popitup('/apps/import/getlog.php?studyid=<?= $studyid;?>')">Admin Debugging Log (Remove before release)</a></p>
--->

</div>

</body>
</html>
