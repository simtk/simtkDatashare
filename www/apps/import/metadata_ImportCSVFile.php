<?php

/**
 * Copyright 2020-2023, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

?>
<!doctype html>
<html lang="us">
	<head>
		<meta charset="utf-8" />

<?php
include_once("../../baseIncludes.php");
?>

		<script>
		// Check if browser is IE
		if (navigator.userAgent.toLowerCase().indexOf(".net") != -1) {
			// insert conditional IE code here
			//alert(navigator.userAgent);
			alert("The Query Data functionality is not supported on IE.  We recommend using Chrome or Firefox instead.");
		}
		</script>

	</head>
	<body>
		<div class="container">
<style>
a {
	color: #f75236;
}
</style>

<br/><br/>
&lt;&lt; <a href="metadata.php">Back to <i>Adding Metadata to Your Dataset</i></a>
<br/><br/>

<h1>Import Metadata from CSV File</h1>

<p>If your metadata are provided in a spreadsheet (e.g., Excel, Google Spreadsheet, CSV file), SimTK can semi-automatically generate metadata files to add to your data folders. The files are in a JSON format and follow the <a href="metadata_ManualFile.php#parameters">conventions</a> described for manually creating a metadata file.</p>

<h2>Spreadsheet Format</h2>

<p>SimTK requires that the spreadsheet follows the rules below:</p>

<ul>
<li>One of the rows contains the names of the metadata parameters (e.g., age, height). This is what we call the header row.</li>
<li>One of the columns contains the folder name or ID.</li>
</ul>

<p>In the example spreadsheet below, Row 1 is the header row. The metadata parameters are age, height, mass, gender. Column A is the folder name. In this case, each folder corresponds to a specific subject. The metadata values for subject 07 are given in cells B2, C2, D2, and E2.</p>
<br/>

<img src="MetadataImport1.jpg">
<br/><br/>

<ul>
<li>Content in the row header should only contain alphanumeric characters in addition to the following characters: ( ) [ ] / ^ _ space</li>
</ul>
<br/>

<h2>Add Metadata to SimTK Data Study</h2>

<ol>
<li>If needed, save your spreadsheet as a CSV (comma-separated) or CSV UTF-8 file. The file name must have the suffix .csv.</li> 
<li>Upload the CSV file to the same directory as the folders named in the spreadsheet. In the example below, the spreadsheet name is TestMetaData.csv and it has been uploaded to be in the same directory as the data folders subject07 and subject11. These are the folder names which appear in column A of the spreadsheet.</li>

<br/>

<img src="MetadataImport2.jpg">

<br/><br/>

<li>Select the CSV file, as shown above.</li>
<li>Open the “Populate from Metadata CSV File” section.</li>
<br/>

<img src="MetadataImport3.jpg">

<br/><br/>

<li>Specify the row number of the header and the column number for the folder ID. In the example, the row number is 1. The column with the folder ID is A, or the first column, so we enter the number 1.</li>
<li style="color:#f75236;">It can be hard to clean up the automatically generated metadata files, so FIRST click “Verify” to confirm that there are no errors with the spreadsheet.</li>
<li>If there are no errors, click “Process” to generate the metadata files.</li>
</ol>

</div>
</body>
</html>
