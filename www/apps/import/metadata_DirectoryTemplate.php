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
ol {
	padding-left: 20px;
}
</style>

<br/><br/>
&lt;&lt; <a href="metadata.php">Back to <i>Adding Metadata to Your Dataset</i></a>
<br/><br/>

<h1>Using the Data Directory Structure Template</h1>

<p>Metadata can be encoded via your natural interactions with the dataset, such as the ﬁle system structure and naming conventions you use. This makes it easy for you to provide metadata. An example is separating data by enumerated subject directories (e.g., data/subject01/, data/subject02/ ). There is an implied relationship between subject01 and subject02. Another example is separating a subject’s data by testing conditions (e.g., subject01/walking, subject01/running). The folder names walking and running are the testing conditions.</p>

<p>To capture the metadata in these conventions, we use templates. The default template (Top Folder Template) is suitable for datasets which have been organized so that the top level folders contain similar data and metadata. We can also work with you to create custom templates.</p>
<br/>

<a name="organized"></a>
<h2>Top Folder Template (default option)</h2>

<p>Use this template if 1) the top level folders in your dataset are described using similar parameters (metadata) and 2) you intend to have any queries search across this entire set of folders.</p>
<br/>

<b>How to Use the Template</b>
<br/>

The top level folders in your dataset need to be named using the format of prefix + a number, as shown here:
<br/>
Subject01
<br/>
Subject02
<br/>
Subject03
<br/>
…
<br/>
…
<br/><br/>

The prefix is:
<ul>
<li>Any combination of alphabetical characters, e.g., Experiment, subject, MRStudy.</li>
<li>Case-sensitive, so “Subject01” and “subject01” are considered different.</li>
</ul>
<br/>

<b>Set or Edit Prefix</b>
<br/>

To set the prefix, enter the prefix in the text field labeled “Top Level Folder Prefix.”
<br/><br/>

You can modify the prefix afterwards by going to Downloads -> Data Share -> Administration.  Select the “Edit” link for the study of interest, and then change the prefix text for the field labeled “Top Level Folder Prefix.” Click the “Update” button. Modifying the prefix will trigger the system to re-index your folders for the query.
<br/><br/>

<b>Query Behavior Using this Template</b>
<br/>

Using this template, queries based on metadata from a top level folder with the prefix naming convention will be conducted across all folders similarly named. In the example above if the prefix is set to “Subject”, a search would be conducted across folders Subject01, Subject02, Subject03, etc.
<br/><br/>

Data folders that are independent would use different folder prefix names. In the example below, if the prefix was set to “Subject,” queries would search across Subject01 and Subject02. Queries would not search across Test01 nor Test02. To search across Test01 and Test02, the prefix would need to be changed to “Test”. No prefix setting would enable queries to search across all 4 folders.
<br/><br/>

Example:
<br/><br/>

Subject01
<br/>
Subject02
<br/>
Test01
<br/>
Test02
<br/><br/>

<h2>Custom templates</h2>

<p>We can also create custom templates to automatically extract metadata based on the way you organize your data. This will enable us to also better understand how to create implicit metadata from data organization. <a href="mailto:feedback@simtk.org">Contact us</a> to discuss your data.</p>

</div>
</body>
</html>
