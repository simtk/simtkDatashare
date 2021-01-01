<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
ini_set('display_errors', 'on');
error_reporting(E_ALL);

include 'server-info.php';

// Get configuration parameters.
$arrDbConf = array();
$strConf = file_get_contents("/usr/local/mobilizeds/conf/mobilizeds.conf");
$jsonConf = json_decode($strConf, true);
foreach ($jsonConf as $key => $value) {
	if (is_array($value)) {
		if ($key == "postgres") {
			foreach ($value as $key => $val) {
				$arrDbConf[$key] = $val;
			}
		}
	}
}

// Check validity of configuration parameters.
if (!isset($arrDbConf["db"]) ||
	!isset($arrDbConf["user"]) ||
	!isset($arrDbConf["pass"])) {
	echo "Invalid db configuration";
	exit;
}

if ($_REQUEST['apikey'] == "$apikey" &&
	isset($_REQUEST['studyid'])) {

	// Get db connection.
	$db_connection = pg_connect("host=localhost " .
		"dbname=" . $arrDbConf["db"] . " " .
		"user=" . $arrDbConf["user"] . " " .
		"password=" . $arrDbConf["pass"]);

	date_default_timezone_set('America/Los_Angeles');

	$studyid = $_REQUEST['studyid'];
	$typeId = 0;
	if (isset($_REQUEST['typeid'])) {
		$typeId = (int) $_REQUEST['typeid'];
	}

	if ($typeId == 1) {
		$strTypeQuery = "AND (typeid=1 AND " .
			"(params_list IS NOT NULL AND trim(params_list) !='')) ";
	}
	else {
		// Ignore check on params_list if it should not be present.
		$strTypeQuery = "AND typeid=$typeId ";
		$strTypeQuery = "AND ((typeid=2 AND " .
			"(params_list IS NOT NULL AND trim(params_list) !='')) " .
			"OR typeid=3 OR typeid=4 OR typeid=5) ";
	}

	if (!empty($_REQUEST['datefrom']) && 
		!empty($_REQUEST['dateto'])) {

		// Use date range.
		$datefrom = $_REQUEST['datefrom'];
		$dateto = $_REQUEST['dateto'];

		$strQuery = "SELECT * FROM statistics " .
			"WHERE studyid=$1 " .
			$strTypeQuery .
			"AND dateentered::date >= $2 " .
			"AND dateentered::date <= $3 " .
			"ORDER BY dateentered DESC";

		$result = pg_query_params($db_connection, $strQuery,
			array($studyid, $datefrom, $dateto));
	}
	else {
		// Get all history.
		$strQuery = "SELECT * FROM statistics " .
			"WHERE studyid=$1 " .
			$strTypeQuery .
			"ORDER BY dateentered DESC";

		$result = pg_query_params($db_connection, $strQuery,
			array($studyid));
	}

	$rows = array();
	while($r = pg_fetch_assoc($result)) {
		$rows[] = $r;
	}

	echo json_encode($rows);

	pg_close($db_connection);
}
else {
	echo "invalid key or studyid ";
}

?>

