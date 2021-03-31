package Mobilize::Study::GeneralTemplate;

# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

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
