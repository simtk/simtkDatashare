<?php 
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
