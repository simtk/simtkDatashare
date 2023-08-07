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

<h1>FAQ</h1>
<br/>

<ol>

<a name="faq"></a>
<li><b>How do I know which metadata apply to which files and folders?</b></li>
<br/>

<p>
A given metadata file is associated with all files at the same directory level as it, as well as its subdirectories.
</p>

<p>
Example 1:
</p>

<img src="metadata_toplevel1.jpg">
<br />
<p>
The metadata file (metadata.yaml) in this example appears at the top level of the folder subject16, so all variables in that metadata file will apply to any files that appeared with it (none in this example) as well as all files in the subdirectories loaded and noload. In this case, the variables describe the demographics of the subject, e.g., age, gender.
</p>

<p>
Example 2:
</p>

<img src="metadata_childlevel1.jpg">
<br /><br />
<p>
The metadata file (metadata.yaml) in this example appears in the subfolder loaded and contains information about the load that was applied. The information in this metadata file will apply to any files that appeared with it (none in this example) as well as all files in the subdirectories to loaded: free, matched and static.
</p>

</ol>

</div>
</body>
</html>
