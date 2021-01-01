<?php

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
<h1>Adding Metadata to Your Dataset</h1>

<style>
a {
	color: #f75236;
}
</style>

<ul>
<li><a href="#why">Why Should I Create a Metadata File?</a></li>
<li><a href="#provide">How Do I Provide Metadata?</a></li>
<ul>
<li><a href="#creating">Creating a Metadata File</a></li>
<ul>
<li><a href="#file">File Naming and Format</a></li>
<li><a href="#example">Example Metadata Files</a></li>
<li><a href="#parameters">Parameters to Include as Metadata</a></li>
<li><a href="#how">How to Describe a Parameter</a></li>
<li><a href="#where">Where Do I Put the Metadata File?</a></li>
</ul>
<li><a href="#using">Using the Data Directory Structure Template for Implicit Metadata</a></li>
<ul>
<li><a href="#organized">Your data is organized by subjects</a></li>
<li><a href="#notorganized">Your data is not organized by subjects</a></li>
</ul>
</ul>
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

You can provide metadata in two ways:

<ul>
<li><b>Explicit metadata</b> is directly extracted from ﬁles deliberately created and labeled by users (see “Creating a Metadata File” below).</li>
<li><b>Implicit metadata</b> is created from naming and organizational conventions. (see “Data Directory Structure Templates” below)</li>
</ul>

<a name="creating"></a>
<h3>Creating a Metadata File</h3>

<a name="file"></a>
<ol>
<li><b>File Naming and Format:</b>  Metadata files can be JSON, XML, and/or YAML formatted files. These are all text files with a specified structure. See example files below. Files must be named “metadata”:
</li>
<br />
<ul>
<li>metadata.json</li>
<li>metadata.xml</li>
<li>metadata.yaml</li>
</ul>
<br />

<a name="example"></a>
<li><b>Example Metadata Files:</b>
Below are examples providing metadata for a 32-year-old male subject who is 1.75 m tall.
</li>

<br /><br />
<b>YAML example:</b>
<br /><br />

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
<br /><br />

<b>XML example:</b>
<br />

<pre lang="xml">
&lt;subject&gt;
       &lt;age type="integer" unit="year" value="32"&gt;&lt;/age&gt;
	   &lt;gender type="radio" value="female"&gt;&lt;/gender&gt;
       &lt;height type="double" unit="m" value="1.75"&gt;&lt;/height&gt;
&lt;/subject&gt;
</pre>
<br />
<br /><br />
<b>JSON example:</b>
<br />
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
<br />
<a name="parameters"></a>
<li><b>Parameters to Include as Metadata:</b> There are no set parameters that you must include in your metadata file. You should include the parameters that you think are most useful to describe your dataset.
A typical metadata file for a subject might include parameters for age, gender, weight, and height of the subject. Another example might describe the type of activity performed for the experimental trial, such as speed of running, carrying load or not, weight of load carried.
</li>
<br /><br />
<a name="how"></a>
<li><b>How to Describe a Parameter:</b>  There are four pieces of information you can provide for each parameter (see below). Some of the information is optional, though we highly recommend that you include them to ensure clarity for other users, as well as to obtain the ideal functionality from our automated querying system.
</li>

<br />
<ul>
<li><b>parameter name</b> (required) – examples include “age”, “gender”, “KLGrade”. <strong>Note: Only one value can be assigned to a given parameter</strong>.</li>
<li><b>value</b> (required) – the value for the parameter, e.g., 32 for “age”</li>
<li><b>type</b> (required) – This determines how selection choices for this parameter are presented to a user to query the dataset (see Table 1 below).</li>
<li><b>unit</b> (highly recommended) – the units for the value given, e.g., “year” for “age”</li>
<br />
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
<br />
Example 1: if “type” is set to “integer,” the user is allowed to specify a range of numbers to search over.
<br /><br />
Example 2: if “type” is set to “select,” the user is presented with a drop-down menu of all values observed in the dataset (e.g., January, February, March, …)
<br /><br />
</ul>

<!---
<b>Default Parameter Settings</b>
<br /><br />

Some parameters commonly occur in many human studies. These are given default types and units as shown in Table 2.
<br /><br />
Table 2: Default parameter settings

<table class="table" border=2>
<tr>
<th>Field Name</th>
<th>Type</th>
<th>Unit</th>
</tr>
<tr><td>ID</td><td>integer</td><td>-</td></tr>
<tr><td>Age</td><td>integer</td><td>year</td></tr>
<tr><td>Gender</td><td>radio</td><td>-</td></tr>
<tr><td>Weight</td><td>double</td><td>kg</td></tr>
<tr><td>Height</td><td>double</td><td>cm</td></tr>
</table>
--->

<br /><br />
<a name="where"></a>
<li><b>Where Do I Put the Metadata File?</b></li>
<br /><br />

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

<a name="using"></a>
<h3>Using the Data Directory Structure Template for Implicit Metadata</h3>
<br />
Implicit metadata is metadata that arises from your natural interactions with the dataset, such as the ﬁle system structure and naming conventions you use. This makes it easy for you to provide metadata. An example of implicit metadata is separating data by enumerated subject directories (e.g., data/subject01/, data/subject02/ ). There is an implied relationship between subject01 and subject02. Another example is separating a subject’s data by testing conditions (e.g., subject01/walking, subject01/running, subject01/). The folder names are the testing conditions.
<br /><br />
To capture the metadata in these conventions, we use templates. The default template (Top Folder Template) is suitable for datasets which have been organized so that the top level folders contain similar data and metadata. We can also work with you to create custom templates.
<br/><br/>
<a name="organized"></a>
<ol>
<li><b>Top Folder Template (default option):</b> Use this template if 1) the top level folders in your dataset are described using similar parameters (metadata) and 2) you intend to have any queries search across this entire set of folders.
</li>
<br/>
<div style="padding-left:40px;">
<i>How to Use the Template:</i> The top level folders in your dataset need to be named using the format of prefix + a number, as shown here:
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

The prefix can be any combination of alphabetical characters, e.g., Experiment, subject, MRStudy. To set the prefix, enter the prefix in the text field labeled “Top Level Folder Prefix.” The prefix name is case-sensitive, so “Subject01” and “subject01” are considered different.
<br/><br/>

You can modify the prefix afterwards by going to Downloads -> Data Share -> Administration.  Select the “Edit” link for the study of interest, and then change the prefix text for the field labeled “Top Level Folder Prefix.” Click the “Update” button. Modifying the prefix will trigger the system to re-index your folders for the query.
<br/><br/>

<i>Query Behavior Using this Template:</i> Using this template, queries based on metadata from a top level folder with the prefix naming convention will be conducted across all folders similarly named. In the example above if the prefix is set to “Subject”, a search would be conducted across folders Subject01, Subject02, Subject03, etc.
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
</div>

<li><b>Custom templates:</b> We can also create custom templates to automatically extract metadata based on the way you organize your data. This will enable us to also better understand how to create implicit metadata from data organization. <a href="mailto:feedback@simtk.org">Contact us</a> to discuss your data.
</li>
<br/>
</ol>
</div>
</body>
</html>
