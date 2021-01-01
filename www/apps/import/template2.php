<?php
	
?>
		
<h1>Information for In Vitro</h1>
<br />
<h3>Filename Format</h3>
<br /><br />
Often studies use similarly named files and folders to indicate some relationship or enumeration between the contents of those files or folders.
<br /><br />
For the In Vitro Template, experiments that are related should use the folder name subject followed by a numeral.  This allows the query function to search within related subjects.
<br /><br />
Example:
<br /><br />
    CMULTIS01<br />
    CMULTIS02<br />
    CMULTIS03<br />
    ...<br />
    ...<br />
<br /><br />

Experiments that are independant and do not need to query within related subjects would use different folder names.   
In the example below, queries will search across CMULTIS01 and CMULTIS02, but would not search across Subject.  Search within Subject would only be within Subject.
<br /><br />
Example:
<br /><br />
   CMULTIS01<br />
   CMULTIS02<br />
   Subject<br />
   ...<br />
   ...<br />

