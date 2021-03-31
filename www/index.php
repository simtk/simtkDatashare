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

	include_once("user/session.php");
	include_once("user/checkuser.php");
	if ($isStudyValid == false) {
		// Study is invalid.
		exit;
	}
	$relative_url = './';
?>
<!doctype html>
<html lang="us">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="Pragma" content="no-cache">
<?php
	include_once("baseIncludes.php");
?>
	</head>
	<body>
		<div class="container">
			
			<?php include( "banner.php" ); ?>

			<div class="row">
				
			</div>
		</div>
	</body>
</html>
