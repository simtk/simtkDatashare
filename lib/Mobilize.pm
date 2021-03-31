package Mobilize;
use Mobilize::Study;
use File::Slurp;
use JSON::XS;

# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

## @brief Mobilize global configuration settings
## @details
# Configuration settings are found at <code>&lt;prefix&gt;/conf/mobilizeds.conf</code>
#
#	Example:
# <pre>
#	    $Mobilize::Conf->{ apache }{ docroot }; # /var/www/html
# </pre>

our $Prefix = '/usr/local/mobilizeds';
my  $json   = new JSON::XS();
my  $file   = read_file( "$Prefix/conf/mobilizeds.conf" );

our $Conf   = $json->decode( $file );
## @var public static $Conf Global configuration settings

1;
