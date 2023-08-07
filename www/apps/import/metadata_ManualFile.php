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

<h1>Manually Generate Metadata File(s)</h1>

<ul>
<li><a href="#overview">Overview</a></li>
<li><a href="#file">Metadata File Name and Format</a></li>
<li><a href="#example">Example Metadata Files</a></li>
<li><a href="#parameters">Metadata Parameters</a></li>
<li><a href="#where">Where Do I Put the Metadata File?</a></li>
</ul>

<h2>Overview</h2>

<p>You can provide all the metadata for a given folder (e.g., subject15) by adding a text file in a prescribed format at the top-level of the folder. <a href="metadata_FAQ.php#faq">See the FAQ</a> for more information about the metadata file location. Follow the instructions below to generate the metadata text file manually.</p>

<a name="file"></a>
<h2>Metadata File Name and Format</h2>

<p>Metadata files can be JSON, XML, and/or YAML formatted files. These are all text files with a specified structure. See example files below. Files must be named “metadata”:
<br/>
<ul>
<li>metadata.json</li>
<li>metadata.xml</li>
<li>metadata.yaml</li>
</ul>
</p>

<a name="example"></a>
<h2>Example Metadata Files</h2>

<p> Below are examples providing metadata for a 32-year-old male subject who is 1.75 m tall.
<br/><br/>

<b>YAML example:</b>
<br/>

<pre>
age:
  type: integer
  unit: year
  value: 32
gender:
  type: radio
  value: M
height:
  type: double
  unit: m
  value: 1.75
</pre>
<br/>

<b>XML example:</b>
<br/>

<pre lang="xml">
&lt;subject&gt;
       &lt;age type="integer" unit="year" value="32"&gt;&lt;/age&gt;
	   &lt;gender type="radio" value="female"&gt;&lt;/gender&gt;
       &lt;height type="double" unit="m" value="1.75"&gt;&lt;/height&gt;
&lt;/subject&gt;
</pre>
<br/>

<b>JSON example:</b>
<br/>

<pre>
{
     "age":{
           "type":"integer",
           "unit":"year",
           "value":32
      },
      "gender":{
           "type":"radio",
           "value":"M"
       },
       "height":{
           "type":"double",
           "unit":"m", "value":1.75
       }
}
</pre>
</p>
<br/>

<a name="parameters"></a>
<h2>Metadata Parameters</h2>

<b>Parameters to Include as Metadata</b>
<br/>
There are no set parameters that you must include in your metadata file. You should include the parameters that you think are most useful to describe your dataset.
A typical metadata file for a subject might include parameters for age, gender, weight, and height of the subject. Another example might describe the type of activity performed for the experimental trial, such as speed of running, carrying load or not, weight of load carried.
<br/><br/>

<a name="how"></a>
<b>How to Describe a Parameter</b>
<br/>
There are four pieces of information you can provide for each parameter (see below). Some of the information is optional, though we highly recommend that you include them to ensure clarity for other users, as well as to obtain the ideal functionality from our automated querying system.

<br/>
<ul>
<li><b>parameter name</b> (required) – examples include “age”, “gender”, “KLGrade”. <strong>Note: Only one value can be assigned to a given parameter</strong>.</li>
<li><b>value</b> (required) – the value for the parameter, e.g., 32 for “age”</li>
<li><b>type</b> (required) – This determines how selection choices for this parameter are presented to a user to query the dataset (see Table 1 below).</li>
<li><b>unit</b> (highly recommended) – the units for the value given, e.g., “year” for “age”</li>
</ul>
<br/>

<b>Table 1: Field Types</b>

<table class="table" border=2>
<tr>
<th>Field Type</th>
<th>Description</th>
<th>Restrictions</th>
<th>Query Input Format</th>
</tr>
<tr><td>integer</td><td>Counting numbers (e.g., age)</td><td></td><td><img src="integer.jpg"></td></tr>
<tr><td>double</td><td>Real Numbers (e.g., weight)</td><td></td><td><img src="double.jpg"></td></tr>
<tr><td>text</td><td>Text data (e.g., experiment description)</td><td>For YAML files, do not use "type:", "unit:", "value:" which are reserved text.</td><td><img src="text.jpg"></td></tr>
<tr><td>radio</td><td>A short mutually exclusive list (e.g., gender)</td><td></td><td><img src="radio.jpg"></td></tr>
<tr><td>select</td><td>A longer mutually exclusive list (e.g., activities, months)</td><td></td><td><img src="select.jpg"></td></tr>
<tr><td>checkbox</td><td>A list where zero or more may apply (e.g., folders, pizza toppings)</td><td></td><td><img src="checkbox.jpg"></td></tr>
</table>
<br/>

Example 1: if “type” is set to “integer,” the user is allowed to specify a range of numbers to search over.
<br/><br/>

Example 2: if “type” is set to “select,” the user is presented with a drop-down menu of all values observed in the dataset (e.g., January, February, March, …)
<br/><br/>

<a name="where"></a>
<h2>Where Do I Put the Metadata File?</h2>

<p><a href="metadata_FAQ.php">See the FAQ</a> for more information about the metadata file location.</p>

</div>
</body>
</html>
