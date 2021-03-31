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

    // =====
    $studyid = 0;
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

?>
<!doctype html>
<html lang="us">
	<head>
		<meta charset="utf-8" />
	</head>
	<body>
		
		<div class="container">
		<textarea rows="80" cols="125">
<?php
	echo file_get_contents( "/usr/local/mobilizeds/html/apps/import/logs/log$studyid.txt" );
?>
		</textarea>
		<div>
	</body>
</html>
