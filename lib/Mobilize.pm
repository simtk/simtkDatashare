package Mobilize;
use Mobilize::Study;
use File::Slurp;
use JSON::XS;


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
