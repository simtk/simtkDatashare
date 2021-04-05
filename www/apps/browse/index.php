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

	include_once('../../user/session.php');
	include_once('../../user/server.php');
	$conf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
	$conf = json_decode( $conf );

	// =====
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
	if (isset($_REQUEST['templateid'])) {
		$templateid = $_REQUEST['templateid'];
	}
	if (isset($_REQUEST['pathSelected'])) {
		$pathSelected = $_REQUEST['pathSelected'];
	}
	if (isset($_SESSION['userid'])) {
		$userid = $_SESSION['userid'];
	}
	if (isset($_SESSION['email'])) {
		$email = $_SESSION['email'];
	}

	// ===== NAVIGATION BREADCRUMB
	$breadcrumb = ' &gt; <a class="btn disabled">Search and Download Data</a>';

	// ===== DAILY SNAPSHOT FILE SIZE
	$study       = $conf->study->id;
	$snapshot    = $conf->data->docroot . "/releases/study" . $studyid . "-latest.tar.gz";
	$filesize    = 0;
	$sizeunit    = 'B';
	if (is_link($snapshot)) {
		$snapshot = $conf->data->docroot . "/releases/" . readlink( $snapshot );
	}
	// Check for existence first before querying.
	if (file_exists($snapshot)) {
		$filesize = filesize($snapshot);
		$snapshotdate = "This archive was last generated at " .
			date("g:ia",filemtime( $snapshot )) . 
			" on " . 
			date("F j, Y",filemtime( $snapshot )) . 
			" and may not reflect the latest updates to the repository.";
		if ( $filesize > 1024 ) { $filesize = intval( $filesize/1024 ); $sizeunit = 'KB'; }
		if ( $filesize > 1024 ) { $filesize = intval( $filesize/1024 ); $sizeunit = 'MB'; }
		if ( $filesize > 1024 ) { $filesize = intval( $filesize/1024 ); $sizeunit = 'GB'; }
		if ( $filesize > 1024 ) { $filesize = intval( $filesize/1024 ); $sizeunit = 'TB'; }
	}
?>

<!doctype html>
<html lang="us">
<head>

<meta charset="utf-8" />

<?php
include_once("../../baseIncludes.php");
?>

<script>
	$(document).ready(function() {
		// Adjust container width.
		// Otherwise, the container size does not match after manual resizing.
		$(".panel-body").resize(function() {
			if ($(this).width() > 0) {
				// Adjust only if width is greater than zero.
				// During initial loading, this width may be negative. Ignore.
				$(".panel-primary").width($(this).width() + 2);
				$(".panel-primary").height($(this).height() + 40);
			}
		});

		// NOTE: Fixed bug in elfinder.
		// Need to re-adjust the heights of "panel-primary" and "panel-body"
		// to fix the elfinder layout because the vertical layout 
		// of elfinder is sometimes incorrect.
		// However, elfinder is not yet ready at this point, 
		// at $(document).ready() or $(window).on("load"); its components 
		// are not yet fully available. A delay needs to be added before any 
		// layout adjustment can be made.
		// This fix resize elfinder after a delay.
		$(this).delay(300).queue(function() {
			$(".panel-primary").height(440);
			$(".panel-body").height(400);
			$(window).trigger("resize");
			$(this).dequeue();
		});
	});
</script>

<?php
	$directory = $conf->data->docroot . "/study/study" . $studyid . '/files';
	if (count(glob("$directory/*")) === 0) {
		$data = 0;
	}
	else {
		$data = 1;
	}
?>

</head>

<body>
<div class="container">

<?php $relative_url = "../../"; include( $relative_url . "banner.php" ); ?>

	<br/><br/>

<?php if (!$data): ?>
	<!-- Data Status -->
	<div><p><br /><b>* This study currently has no data to browse.</b></p></div>
<?php endif; ?>

<?php

if ($perm):

?>
	<!-- DATA SELECTOR -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h4 class="panel-title">Browse Data</h4>
		</div>
		<div class="panel-body" id="elfinder"></div>
	</div>

	<!-- DOWNLOAD LATEST RELEASE -->
	<div id="get-all-panel" class="panel-collapse collapse in">

<?php

	// Generate URL for loading file.
	$urlSendFileParams = "";
	$theToken = "/?section=datashare&";
	if (isset($_SERVER["HTTP_REFERER"])) {
		$theReferer = $_SERVER["HTTP_REFERER"];
		$idx = strpos($theReferer, $theToken);
		if ($idx !== false) {
			// Get the parameters part of the URL.
			$strUrlBack = substr($theReferer, $idx + 2);
			// Insert sendRelease.php.
			$urlSendFileParams = $strUrlBack;
		}
	}
	if ($urlSendFileParams == "" &&
		isset($_SESSION["section"]) &&
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

	if ($download && $filesize != 0 && $data && $urlSendFileParams != "") {

		echo "<a id='get-all-button' class='btn btn-lg btn-success pull-right' href='";
		echo "download/sendReleaseConfirm.php?" . $urlSendFileParams . "'>";
		echo "<span class='glyphicon glyphicon-circle-arrow-down'></span> ";

		if (!isset($_SESSION['isDOI']) || 
			!$_SESSION['isDOI'] || 
			!isset($_SESSION['doi_identifier']) || 
			empty($_SESSION['doi_identifier'])) {
			echo "Download Daily Archive (" . $filesize . " " . $sizeunit . ")" . "</a>";
			echo $snapshotdate;
		}
		else {
			echo "Download Archive (" . $filesize . " " . $sizeunit . ")" . "</a>";
		}
	}

	echo "</div>";

else:
	echo "<h1 class='text-primary'>Your permissions do not allow access to this study.</h1>";
endif;

?>

</div>

<?php

$fileSelected = false;
$thePathHash = false;
$numFilesInDir = false;

// Check if a selected path to show is specified.
if (isset($pathSelected) && $pathSelected != false && trim($pathSelected) != "") {

	// Default volume.
	$volumeId = "l1_";

	// Generate full path of start directory.
	$pathBase = $conf->data->docroot . "/study/study" . $studyid . "/";
	$pathSelected = trim($pathSelected);
	$fullPath = $pathBase . $pathSelected;
	$dirPath = $fullPath;

	// Check for directory/file existence first.
	if (file_exists($fullPath)) {
		if (is_dir($fullPath)) {
			// Directory.
			// Remove any trailing "/".
			if (stripos(strrev($fullPath), "/") === 0) {
				$dirPath = substr($fullPath, 0, strlen($fullPath) - 1);
			}
		}
		else {
			// File.
			// Include only up to the last directory, because
			// elfinder start directory can only handle directory,
			// but not a file.
			$idxLast = strripos($fullPath, "/");
			if ($idxLast !== false) {
				// Get name of selected file.
				$fileSelected = substr($fullPath, $idxLast + 1);

				// Get full path of the directory.
				$dirPath = substr($fullPath, 0, $idxLast);

				// Find the number of files in the specified directory.
				$theFiles = scandir($dirPath);
				// Exclude "." and "..".
				$numFilesInDir = count($theFiles) - 2;
			}
		}

		// Generate start path hash from the start directory for elfinder.
		$thePathHash = $volumeId . rtrim(strtr(base64_encode($dirPath), '+/=', '-_.'), '.');
	}
}
?>

<script>
	$("#get-all-button").click(function() {
		var userId = '<?php echo $_SESSION["userid"]; ?>';
		var groupId = '<?php echo $_SESSION["group_id"]; ?>';
		var studyId = '<?php echo $_SESSION["study_id"]; ?>';
	
		if (userId == false) {
			event.preventDefault();

			// User is not logged in.
			// Prompt user to login.

			// Dynamically set up a form, submit data, and redirect to a login page.
			var formConfirm = $(document.createElement('form'));

			// NOTE: Use the GET method to ensure that parameters are passed
			// along this process, because the login page exited from the iframe
			// and uses the parent frame.
			$(formConfirm).attr("method", "GET");

			// Destination.
			var urlStr = "<?php echo $urlSendFileParams; ?>";
			var simtkServer = "<?php echo $domain_name; ?>";
			$(formConfirm).attr("action", "https://" + simtkServer +
				"/plugins/datashare/userLogin.php");

			var inputGroupId = $("<input>").attr("type", "hidden").attr("name", "groupid").val(groupId);
			$(formConfirm).append($(inputGroupId));

			var inputStudyId = $("<input>").attr("type", "hidden").attr("name", "studyid").val(studyId);
			$(formConfirm).append($(inputStudyId));

			var inputTypeConfirm = $("<input>").attr("type", "hidden").attr("name", "typeConfirm").val("1");
			$(formConfirm).append($(inputTypeConfirm));

			$("body").append(formConfirm);
			$(formConfirm).submit();
		}
	});

	$(window).on("load", function() {
		$(".ui-state-disabled").each(function(index) {
			$(this).css("background-color", "gray");
		});
		// Disable downloading message if present.
		parent.postMessage({event_id: "DownloadFinished"}, "*");
	});


	// ===== INITIALIZE FILETREE AND BUILDER COMPONENTS
	var fm = $( '#elfinder' ).elfinder({
		url: '../import/php/connector.mobilizeds.readonly.php?' +
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
			'firstname=<?php echo $_SESSION["firstname"]; ?>&' +
			'lastname=<?php echo $_SESSION["lastname"]; ?>',

		// Send SimTK server name to elfinder as parameter in options.
		simtkServer: "<?php echo $domain_name; ?>",

<?php
if ($thePathHash !== false) {
?>
		// Send start path hash to elfinder as parameter in options.
		startPathHash: "<?php echo $thePathHash; ?>",
<?php
}
?>

<?php
if ($numFilesInDir !== false && $numFilesInDir > 0) {
?>
		// If a file is specifed, ensure that the file is shown by
		// updating number of files to show in elFinder first, because
		// elFinder uses lazy loading and displays a default of 50 files.
		// Files beyond the first 50 files are not shown unless
		// the panel is vertically scrolled down by the user.
		showFiles: "<?php echo $numFilesInDir; ?>",
<?php
}
?>
		contextmenu : {
			// current directory menu
			cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],
			// current directory file menu
			files  : ['link', '|', 'getfile', '|', 'quicklook', '|', 'download', '|', '|', 'info']
		}
	}).elfinder("instance");

<?php
if ($fileSelected !== false) {
?>
	// File has been specified.
	// Scroll to file and highlight it.
	fm.one("cwdrender", function() {
		// Try to locate element with icon view first.
		var theSelector = $("div[title='" + "<?php echo $fileSelected; ?>" + "']");
		if (theSelector.length == 0) {
			// Cannot locate element with icon view; try list view.
			theSelector = $("span").filter(function() {
				return $(this).text() == '<?php echo $fileSelected; ?>';
			});
		}
		if (theSelector.length > 0) {

			// Get offset and animate scroll to the component first.
			// Otherwise, the component is sometimes shown in the
			// scroll height interval above.
			var compCwd = $(".elfinder-cwd-wrapper");
			compCwd.animate({scrollTop: theSelector.offset().top}, 0);

			// Found element.
			// Select element.
			// NOTE: Click element also to show the complete dropdown menu items.
			theSelector.trigger('scrolltoview').trigger("select").click();
		}
	});
<?php
}
?>

</script>
</body>

</html>



