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
include_once("../../user/server.php");

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
ul {
	padding-left: 20px;
}
</style>

<h1>Adding Metadata to Your Dataset</h1>

<ul>
<li><a href="#why">Why Should I Create a Metadata File?</a></li>
<li><a href="#provide">How Do I Provide Metadata?</a></li>
<li><a href="#questions">More Questions?</a></li>
</ul>

<a name="why"></a>
<h2>Why Should I Create a Metadata File?</h2>

<p>Metadata is information that describes your data, for example, describing the experimental conditions under which the data was collected. This is valuable information to help you and others properly analyze the data in the future. It is also useful when others are searching for datasets to re-use.
</p>

<p>
If you wish to enable the automatically generated querying tool for your dataset (Figure 1), you must provide metadata with your data.
</p>

<img src="QueryTool1.jpg">
<p>
<i>Figure 1: The Query tool enables others to easily search through your dataset. The parameters used for building the query are automatically extracted from metadata that you provide.</i>
</p>

<a name="provide"></a>
<h2>How Do I Provide Metadata?</h2>

<p>You can provide metadata in three ways. See the table below. <b>Most datasets use both a data directory structure template [1] AND one of the metadata file approaches [2 or 3].</b></p>

<a name="questions"></a>
<p>Questions? <a href="https://<?php echo $domain_name;?>/plugins/phpBB/indexPhpbb.php?group_id=11&pluginname=phpBB">Submit them to SimTK discussion forum</a>.</p>
<br/>

<table class="table table-bordered">
<thead>
<tr>
	<th>Approach</th>
	<th>Advantages</th>
	<th>Disdvantages</th>
</tr>
</thead>
<tbody>
<tr>
	<td><a href="metadata_ImportCSVFile.php">[1] Semi-automatic metadata extraction from CSV file</a></td>
	<td>
	<ul>
		<li>Can be less labor-intensive if metadata is already collected in a spreadsheet (e.g., Excel, Google Sheets).</li>
	</ul>
	</td>
	<td>
	<ul>
		<li>Only generates metadata files for the folders at the same directory level. Additional steps are required to add metadata at lower directory levels.</li>
	</ul>
	</td>
</tr>
<tr>
	<td><a href="metadata_ManualFile.php">[2] Manual creation and upload of metadata files</a></td>
	<td>
	<ul>
		<li>Most flexible</li>
		<li>Can provide units and other descriptive information for the metadata</li>
	</ul>
	</td>
	<td>
	<ul>
		<li>Most time-intensive to generate</li>
	</ul>
	</td>
</tr>
<tr>
	<td><a href="metadata_DirectoryTemplate.php">[3] Use data directory structure template</a></td>
	<td>
	<ul>
		<li>Templates that are customized to your directory structure means that you don’t need to create separate metadata files after data collection.</li>
	</ul>
	<td>
	<ul>
		<li>Must work with SimTK to develop custom templates.</li>
		<li>Only one simple template currently available.</li>
		<li>Data must be strictly organized. Changes to directory structure may break template.</li>
	</ul>
	</td>
</tr>
</tbody>
</table>
<br/>

<h2>Questions?</h2>

<p>Do you have other questions about providing or interpreting metadata shared with the datasets on SimTK? <a href="metadata_FAQ.php">Check out the FAQ</a>.</p>

</div>
</body>
</html>
