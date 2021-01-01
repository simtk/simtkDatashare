<?php
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
	$mystring = file_get_contents( "/usr/local/mobilizeds/html/apps/import/logs/log_o$studyid.txt" );
	$pos = strpos($mystring, 'Error');

	$today = date("F j, Y, g:i a");
	if ($pos === false) {
		echo "$today: No errors encountered during import.";
	}
	else {
		echo "$today: An error was encountered during import.";
	}
?>
		</textarea>
		<div>
	</body>
</html>
