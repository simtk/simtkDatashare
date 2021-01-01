package Mobilize::Study::GeneralTemplate;
use base Mobilize::Study;
use Mobilize;
use File::Find;
use JSON::XS;
use Data::Dumper;

## @brief General Template class support for querying and packaging

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor

	my $self  = shift;
	my $study = shift;

	$self->{ name } = $study;
	return $self->SUPER::new( $study );
}

1;
