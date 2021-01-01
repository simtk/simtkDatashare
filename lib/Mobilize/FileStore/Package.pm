package Mobilize::FileStore::Package;
use Mobilize;
use File::Slurp qw(read_dir);

## @brief Class for packaging query results into a downloadable zipfile

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

	my $self      = shift;
	my $request   = shift;
	my $filestore = shift;
	my $hashchars = [ ('A' .. 'Z', 'a' .. 'z', 0 .. 9) ];
	my $n         = int( @$hashchars );

	my $filename = $self->{ filename } = $request->{ filename };
	$self->{ filestore } = $filestore;
	do {
		my $hash = join "", map { my $i = int( rand() * $n ); $hashchars->[ $i ] } ( 1 .. 8 );
		$self->{ path } = $filestore->downloads() . '/' . $hash;
	} while( -d $self->{ path } );
	mkdir $self->{ path };
	mkdir "$self->{ path }/$filename";
}

# ============================================================
sub add {
# ============================================================
## @method public add ( path, file )
## @brief Adds the given file to the package
## @arg path full path to the file
## @arg file filename of the file

	my $self = shift;
	my $path = shift;
	my $file = shift;

	my $fullpath = "$path/$file";
	$self->{ paths }{ $path }++;
	$self->{ files }{ $fullpath }++;
}

# ============================================================
sub send {
# ============================================================
## @method public send ( request )
## @brief Adds the given file to the package
## @arg request
## @returns hashref with the count of files, size of the uncompressed package, and URL to the package

	my $self      = shift;
	my $request   = shift;
	my $filestore = $self->{ filestore };

	# ===== RECREATE ORIGINAL FILE STRUCTURE
	foreach my $path (sort keys %{ $self->{ paths }}) {
		my $fullpath = "$self->{ path }/$self->{ filename }/$path";
		`mkdir -p $fullpath` unless -d $fullpath;
	}

	# Fill hash with subdirectories.
	my $baseDir = $filestore->data() . $request->{ study } . '/files';
	my $allDirNames = [];
	my %mydirNames;

	# Find all subdirectories under the base directory.
	$self->findSubDirs($baseDir, $allDirNames);

	foreach my $path (keys %{ $self->{ paths }}) {
		my $pathTerminated = $path . "/";

		# Find all subdirectories.
		my $lastIdx = 0;
		my $idx = index($pathTerminated, "/", $lastIdx);
		while ($idx != -1) {
			if ($idx == 0) {
				# Ignore string if it has leading "/" (i.e. 2 consecutive "/").
			}
			else {
				# Get full path of subdirectory, including the terminating "/".
				my $strSubPath = substr($pathTerminated, 0, $idx + 1);
				# Add the full path to hash.
				%mydirNames = $self->findMatchedDirs($allDirNames, $strSubPath);
			}

		        # Get next subdirectory downstream.
		        $lastIdx = $idx + 1;
		        $idx = index($pathTerminated, "/", $lastIdx);
		}

	}

	# Find matching files in each subdirectory.
	my $updatedBaseDir;
	my $idxUpdatedBaseDir = -1;
	foreach my $strSubPath (keys %mydirNames) {
		my $strFullPath = $mydirNames{$strSubPath};
		if ($idxUpdatedBaseDir == -1) {
			# Append trailing "/" to full path before search.
			my $tmpFullPath = $strFullPath . "/";
			my $tmpSubPath = "/" . $strSubPath;
			$idxUpdatedBaseDir = index($tmpFullPath, $tmpSubPath);
			if ($idxUpdatedBaseDir != -1) {
				# Include trailing "/".
				$updatedBaseDir = substr($tmpFullPath, 0, $idxUpdatedBaseDir + 1);
			}
		}
		opendir(my $dh, $strFullPath) or die $!;
		while (readdir $dh) {
			# Look up files.
			if (-f $strFullPath . $_ ) {
				# Look up METADATA and README files.
				if (($_ =~ /^metadata/i) ||
					($_ =~ /^readme/i)) {
					# Found matching file.
					my $srcFullPathFile = $strFullPath . $_;
					my $dstFullPathFile = "$self->{ path }/$self->{ filename }/$strSubPath" . $_;

					# Copy file to destination directory.
					my $error = `cp '$srcFullPathFile' '$dstFullPathFile' 2>&1`;
					print STDERR "cp $srcFullPathFile $dstFullPathFile\n$error\n" if( $error );
				}
			}
		}
		closedir($dh);
	}

	# ===== COPY ALL RELEVANT FILES TO THE PACKAGE
	foreach my $file (sort keys %{ $self->{ files }}) {
		#my $fullpath = $filestore->data() . '/' . $file;
                my $fullpath = $filestore->data() . $request->{ study } . '/files/' . $file;
		if ($idxUpdatedBaseDir != -1) {
                	$fullpath = $updatedBaseDir . $file;
		}
		my $error = `cp '$fullpath' '$self->{ path }/$self->{ filename }/$file' 2>&1`;
		print STDERR "cp $fullpath $self->{ path }/$self->{ filename }/$file\n$error\n" if( $error );
	}
	my @files = split /\n/, `find $self->{ path }/$self->{ filename } -type f`;
	my $size = `du -sh $self->{ path }/$self->{ filename }`; $size =~ s/\t.*//g; $size =~ s/\s//g;
	my $n  = int( @files );

	print STDERR "Packaged download $self->{ path }/$self->{ filename }.zip; $n files and $size\n";

	# ===== PRODUCE THE README FILE
	my $date  = localtime();
	open FILE, ">$self->{ path }/$self->{ filename }/README.txt" or die "Can't create provenance file '$self->{ path }/$self->{ filename }/README.txt' $!";
	print FILE <<EOF;
Query details
Datetime: $date
Project:  $request->{ group_name }  ($request->{ group_id })
Study:  $request->{ study_name }  ($request->{ study_id })
Selected data:  $request->{ paramslist } $request->{ filters_dir }
User comments:  $request->{ comments }

Results details
Included files: $n files
Transfer size:  $size

EOF
	close FILE;

	# ===== ZIP THE FILES
	`cd $self->{ path } && zip -r $self->{ filename }.zip $self->{ filename }`;

	my $file = "$self->{ path }/$self->{ filename }.zip";
	my $url  = $file;
	$url =~ s/^$Mobilize::Conf->{ data }{ docroot }\/downloads//;

	return { files => int( @files ), size => $size, url => $url, file => $file };
}

# ============================================================
sub findSubDirs {
# ============================================================
## @method public findSubDirs ()
## @brief Find subdirectories contained.
	my $self = shift;
	my $strDirName = shift;
	my $allDirNames = shift;

	my @resDir = read_dir($strDirName);
	foreach my $dname (@resDir) {
		my $fullPathName = $strDirName . "/" . $dname;
		# Exclude directories.
		if (index(lc($fullPathName), "/.") != -1) {
			next;
		}
		if (-d $fullPathName) {
			push(@{ $allDirNames }, $fullPathName);
			$self->findSubDirs($fullPathName, $allDirNames);
		}
	}
}

# ============================================================
sub findMatchedDirs {
# ============================================================
## @method public findMatchedDirs ()
## @brief Find matching subdirectories.
	my $self = shift;
	my $allDirNames = shift;
	my $strSubdirName = shift;
	my %mydirNames;

	foreach my $fullPathName (@{ $allDirNames }) {
		# Ignore directories starting with ".".
		if (index(lc($fullPathName), "/.") != -1) {
			next;
		}

		# Matched if full pathname ends with given subdirectory name.
		my $strSearch = lc("/" . $strSubdirName);
		my $idx = index(lc($fullPathName . "/"), $strSearch);
		if ($idx != -1 &&
			$idx == length($fullPathName . "/") - length($strSearch)) {
			# Add to hash.
			$mydirNames{$strSubdirName} = $fullPathName;
		}
	}

	return %mydirNames;
}
	


1;
