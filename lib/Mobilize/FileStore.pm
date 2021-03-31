package Mobilize::FileStore;

# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

use Cwd qw( getcwd );
use HTML::Entities;
use URI::Escape qw( uri_unescape );
use Mobilize;
use Mobilize::FileStore::Package;
use Date::Manip;
use JSON;
use warnings;

## @brief Class to help manage the different paths of each study's filestore

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor

	my ($class) = map { ref || $_ } shift;
	my $self    = bless {}, $class;
	$self->init( @_ );

	return $self;
}

# ============================================================
sub init {
# ============================================================
## @method private init ()
## @brief Initializes the new instance

	my $self         = shift;
	my $study        = shift;
	$self->{ study } = $study;
	$self->{ root }  = "$Mobilize::Conf->{ apache }{ docroot }/filestore";
}

# ============================================================
sub package {
# ============================================================
## @method public package ( request )
## @brief Creates a new package in response to a user query request
## @returns a new Mobilize::FileStore::Package object

	my $self     = shift;
	my $request  = shift;
	my $package = new Mobilize::FileStore::Package( $request, $self );
	return $package;
}

# ============================================================
sub root {
# ============================================================
## @method public root ()
## @returns The study's filestore root path

	my $self = shift;
	return $self->{ root };
}

# ============================================================
sub data {
# ============================================================
## @method public data ()
## @returns The study's filestore path to data

	my $self = shift;
	return "$Mobilize::Conf->{ data }{ docroot }/study/";
}

# ============================================================
sub downloads {
# ============================================================
## @method public downloads ()
## @returns The study's filestore path to downloads

	my $self = shift;
	return "$Mobilize::Conf->{ data }{ docroot }/downloads";
}

# ============================================================
sub releases {
# ============================================================
## @method public releases ()
## @returns The study's filestore path to releases (snapshots)

	my $self = shift;
	return "$Mobilize::Conf->{ data }{ docroot }/releases";
}

# ============================================================
sub release {
# ============================================================
## @method public release ( policy )
## @brief Creates a tarball snapshot of the study's data
## @arg @c policy (all|n|latest_only) Optional; default @c all; n is a counting number greater than 1
## @details Cleanup behavior depends on the @policy value; @e all means that all
## releases are retained, @e n means that @e n releases are retained,
## @e latest_only means that only the latest release is retained.
## @returns Path to the tarball snapshot

	my $self   = shift;
	my $policy = shift || 'all';

	# ===== CREATE THE LATEST RELEASE
	my $date   = new Date::Manip::Date( 'now' );
	my $date_string = $date->printf( "%Y-%m-%d" );
	my $tarball     = sprintf( "study%s-release.%s.tar.gz", $self->{ study }, $date_string );
	my $path        = "$Mobilize::Conf->{ data }{ docroot }/study/study$self->{ study }";
	my $cwd         = getcwd();
	my $release     = $self->releases();
	`cd $path && tar -czf $tarball files`;
	`cd $path && mv $tarball $release`;

	unlink "$release/study$self->{ study }-latest.tar.gz";
	`cd $release && ln -s $tarball study$self->{ study }-latest.tar.gz`;

	if( $policy eq 'all' ) { chdir $cwd; return $tarball; }

	# ===== CLEANUP
	$policy = $policy eq 'latest_only' ? 1 : int( $policy );
	die "Invalid release policy; should be 'all', 'latest_only', or n, where n is the number of latest releases to keep. $!" unless( $policy > 0 );

	opendir RELEASES, $release;
	my @releases = reverse sort grep { /\.tar\.gz$/ && !/latest/ } readdir RELEASES;
	closedir RELEASES;

	my @keep = splice @releases, 0, $policy; # Keep top n releases
	#foreach my $discard (@releases) { unlink "$release/$discard"; }

	chdir $cwd;
	return $tarball;
}

1;
