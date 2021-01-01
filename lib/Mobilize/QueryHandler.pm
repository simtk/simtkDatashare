package Mobilize::QueryHandler;
use Data::Dumper;

## @brief Interface for classes that can support metadata querying

# ============================================================
sub query {
# ============================================================
## @method abstract query ()
## @brief An abstract method with stub implementation
## @details
# This method should be overridden to manage queries

	my $self = shift;
}

# ============================================================
sub package {
# ============================================================
## @method abstract package ()
## @brief An abstract method with stub implementation
## @details
# This method should be overridden to manage query results file packaging

	my $self = shift;
}

# ============================================================
sub rules_to_nosql {
# ============================================================
## @method public rules_to_nosql ( rules )
## @brief Converts jQuery QueryBuilder's rules to a NoSQL select statement

	my $self  = shift;
	my $rules = shift;

	# ===== BRANCH NODE WITH 0 OR MORE SIBLINGS (RULE GROUP)
	# Recurse through each member
	if( ref $rules eq 'HASH' && exists( $rules->{ condition } )) {
		my @sql = map { $self->rules_to_nosql( $_ ); } @{ $rules->{ rules }};
		my $conjunction = ' ' . lc( $rules->{ condition } ) . ' ';
		return '(' . join( $conjunction, @sql ) . ')';

	# ===== TERMINAL NODE (RULE)
	} else {
		my $operator_map = {
			less             => '<',
			less_or_equal    => '<=',
			equal            => '=',
			greater          => '>',
			greater_or_equal => '>=',
			not_equal        => '<>',
		};
		my $name  = lc( $rules->{ id } );
		my $value = $rules->{ value };

		# ===== APPLY MAPPING
		# If there is a study mapping human-readable entities to DB entities, apply that mapping
		# e.g. "Gender"  with values (Male,Female) presented in the UI might be
		# stored as "gender" with values (m,f) in the database
		if( exists $self->{ ui_to_db_map } && exists $self->{ ui_to_db_map }{ $name } ) {
			my $map = $self->{ ui_to_db_map }{ $name };
			if( ref( $map ) eq 'CODE' ) {
				($name, $value) = $map->( $value );
			
			} elsif( ! ref( $value ) && exists $map->{ $value } ) {
				($name, $value) = @{$map->{ $value }};

			}
		}

		# ===== CONVERT RULE TO NOSQL
		# Subquery expression 'in'
		if( $rules->{ operator } eq 'in' ) {
			return "json->>'$name' in (" . join( ", ", map { "'$_'"; } @$value ) . ")";

		# Pattern matching 'like'
		} elsif( $rules->{ operator } eq 'contains' ) {
			return "json->>'$name' ~* '\\m$value\\M'";

		# Pattern matching 'not like'
		} elsif( $rules->{ operator } eq 'not_contains' ) {
			return "json->'$name' !~* '\\m$value\\M'";

		# Some mathematical comparator
		} elsif( $name ) {
			$name = "json->>'$name'";
			my $operator   = $operator_map->{ $rules->{ operator }} || die "No mapping for operator '$rules->{ operator }' $!";
			my $is_numeric = $rules->{ type } =~ /^(?:integer|double)$/;
			$value = $is_numeric ? $value : "'$value'";
			if   ( $rules->{ type } eq 'integer' ) { $name = "($name)::int";   }
			elsif( $rules->{ type } eq 'double'  ) { $name = "($name)::float"; }

			return "$name $operator $value";

		} else {
			return ();
		}
	}
}

1;
