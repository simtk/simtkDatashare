package Mobilize::DB;

use Mobilize;
use DBI;

## @brief Adapter class to DB for querying 

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor

	my ($class) = map { ref || $_ } shift;
	my $self = bless {}, $class;
	$self->init( @_ );
	return $self;
}

# ============================================================
sub init {
# ============================================================
## @method private init ()
## @brief Initializes the new instance

	my $self = shift;
	my $db   = $Mobilize::Conf->{ postgres }{ db };
	my $host = $Mobilize::Conf->{ postgres }{ host };
	my $port = $Mobilize::Conf->{ postgres }{ port };
	my $user = $Mobilize::Conf->{ postgres }{ user };
	my $pass = $Mobilize::Conf->{ postgres }{ pass };
	$self->{ study } = shift;
	$self->{ db } = DBI->connect( "dbi:Pg:dbname=$db;host=$host;port=$port;", $user, $pass );
}

# ============================================================
sub clear {
# ============================================================
## @method public clear ( table )
## @brief Clears the given table
## @arg table name of the table to clear

	my $self  = shift;
	my $file  = shift;
	my $table = $self->{ study };
	my $sql   = "delete from \"$table\"";
	my $sth   = $self->{ db }->prepare( $sql );
	$sth->execute();
}

# ============================================================
sub execute {
# ============================================================
## @method public execute ( sql )
## @brief Executes the given SQL; intended for non-select statements
## @arg sql
## @returns DBI Statement handle of results

	my $self = shift;
	my $sql  = shift;
	my $sth = $self->{ db }->prepare( $sql );
	$sth->execute();
	return $sth;
}

# ============================================================
sub query {
# ============================================================
## @method public query ( sql )
## @brief Executes the given SQL query; intended for select statements
## @arg sql
## @returns list of hashrefs, each hashref contains one row result

	my $self    = shift;
	my $query   = shift;
	my $sth     = $self->execute( $query );
	my $results = [];
	while( my $row = $sth->fetchrow_hashref() ) {
		push @$results, $row;
	}
	return $results;
}

1;
