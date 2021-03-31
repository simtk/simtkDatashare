package Mobilize::Users;

# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

use Mobilize::DB;
use Mobilize::Mail;
use List::Util qw( first );
use Digest::SHA qw( sha256_hex );

## @brief Class for user management

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor

	my ($class) = map { ref || $_ } shift;
	my $self = bless {}, $class;

	$self->{ db }    = new Mobilize::DB();
	$self->{ users } = $self->{ db }->query( "SELECT * FROM public.users ORDER BY permissions DESC" );

	return $self;
}

# ============================================================
sub list { my $self = shift; return $self->{ users }; }
# ============================================================
## @method public list ()
## @brief Retrieves the list of all users
## @returns A list of all users

# ============================================================
sub sanitized_list { 
# ============================================================
## @method public sanitized_list ()
## @brief Retrieves a safe list of all users
## @returns A list of all users, without DB IDs or hashed passwords

	my $self  = shift;
	my @users = map { _sanitize( $_ ); } @{ $self->{ users }};
	return \@users;
}

# ============================================================
sub update {
# ============================================================
## @method public update ( edit )
## @brief Updates the given user
## @returns A hashref of the updated user
## @arg edit a hashref including the username key/value, and other key/values to update

	my $self = shift;
	my $edit = shift;
	my $user = first { $_->{ email } eq $edit->{ username } } @{ $self->{ users }};

	if( exists $edit->{ permissions } ) {
		my $sql = "UPDATE public.users SET permissions=$edit->{ permissions } WHERE email='$user->{ email }'";
		$self->{ db }->execute( $sql );
	}
	if( exists $edit->{ invitations } ) {
		my $sql = "UPDATE public.users SET invitations=$edit->{ invitations } WHERE email='$user->{ email }'";
		$self->{ db }->execute( $sql );
	}

	return $user;
}

# ============================================================
sub _sanitize {
# ============================================================
## @method private _sanitize ( user )

	my $user = shift;
	delete $user->{ id }; # No need to send the database ID
	delete $user->{ hashpass }; # Never send passwords, even hashed, to the frontend
	return $user;
}

1;
