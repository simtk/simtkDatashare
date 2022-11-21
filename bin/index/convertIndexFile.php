<?php

# Copyright 2020-2022, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

if (count($argv) != 2) {
	// Incorrect number of arguments.
	return;
}
$studyId = (int) $argv[1];
if ($studyId <= 0) {
	// Incorrect study id.
	return;
}

$pathFileSrc = "/home/mobilizeds/data/study" . $studyId . ".index.nosql";
$pathFileUpdate = "/home/mobilizeds/data/study" . $studyId . ".index.nosql_TMP";

$fhSrc = fopen($pathFileSrc, "r");
if (!$fhSrc) {
	// Cannot open source file for reading.
	return;
}
$fhUpdate = fopen($pathFileUpdate, "w+");
if (!$fhUpdate) {
	// Cannot open destination file for writing.
	return;
}

// Convert strings.
while (($content = fgets($fhSrc)) !== false) {
	$escContent = str_replace("'", "''", $content);
	$escContent = str_replace('\"', "''", $escContent);

	fwrite($fhUpdate, $escContent);
}
fclose($fhSrc);
fclose($fhUpdate);

// Replace source file with updated file.
rename($pathFileUpdate, $pathFileSrc);

?>
