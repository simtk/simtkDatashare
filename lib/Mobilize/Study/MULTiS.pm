package Mobilize::Study::MULTiS;
use base Mobilize::Study;
use Mobilize;
use File::Find;
use JSON::XS;
use Data::Dumper;

## @brief MULTiS Study class support for querying and packaging
## @details
# For the metadata parsing and indexing, see `&lt;prefix&gt;/bin/index/multis`

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor

	my $self = shift;
	return $self->SUPER::new( 'multis' );
}

# ============================================================
sub init {
# ============================================================
## @method public init ()
## @brief Initializes object
	my $self = shift;
	$self->{ name } = 'multis';
	$self->{ compression } = 0.8; # Compression estimate; MULTiS has a lot of binary data, which compresses poorly
}

1;
