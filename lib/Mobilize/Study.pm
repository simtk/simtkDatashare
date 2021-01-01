package Mobilize::Study;
use Mobilize;
use Mobilize::DB;
use Mobilize::FileStore;
use JSON::XS;
use File::Slurp qw(read_dir);

use base Mobilize::QueryHandler;

## @brief Base class for Studies

# ============================================================
sub new {
# ============================================================
## @method public new ()
## @brief Constructor and initializer
## @details
# To register a new study, create a perl module for the study implementing
# the query(), package(), and browse() methods, have it inherit from
# `Mobilize::Study` and place it under the `Mobilize/Study` directory.

	my ($class) = map { ref || $_ } shift;
	my $study   = shift;
	my $request = shift;

	# ===== DYNAMICALLY LOAD ALL MODULES IN THE STUDY SUBDIRECTORY
	opendir STUDIES, "$Mobilize::Prefix/lib/Mobilize/Study" or die "Can't load Study classes $!";
	my @studies = grep { !/^\./ } readdir STUDIES;
	closedir STUDIES;
	my $factory = {};

	foreach my $module (@studies) {
		my $name                 = $module; $name =~ s/\.pm$//;
		my $shortname            = lc $name;
		$factory->{ $shortname } = "Mobilize::Study::$name";
		require "Mobilize/Study/$module";
	}

	# ===== DEFAULT TO GENERAL TEMPLATE
	my $instance = { name => $study };
	$factory->{ $study } = "Mobilize::Study::GeneralTemplate" unless exists $factory->{ $study };

	# ===== INSTANTIATE BASED ON FACTORY DISPATCH TABLE
	my $self               = bless $instance, $factory->{ $study };
	$self->{ db }          = new Mobilize::DB( $study );
	$self->{ filestore }   = new Mobilize::FileStore( $study );
	$self->{ json }        = new JSON::XS();
	$self->{ request }     = $request;
	$self->{ compression } = 0.6; # Assume even mix of binary and text data
	$self->init( @_ );

	#print STDERR "name: $name\n";
	#print STDERR "study: $study\n";
	#print STDERR "Instance: $instance\n";
	#print STDERR "Factory: $factory->{ $study }\n";
	#print STDERR "filestore: $self->{ filestore }\n";
	#print STDERR "request: $self->{ request }\n";
	
	return $self;
}

# ============================================================
sub init {
# ============================================================
## @method init ()
## @brief Abstract object initialization do-nothing stub; subclasses may override
}

# ============================================================
sub query {
# ============================================================
## @method public query ()
## @brief Transforms the request rules to a NoSQL query and returns the results
## @returns hashref with summary information, the results as a list of hashref, and the NoSQL query

	my $self  = shift;
	#my $study = $self->{ name };
	my $study = $self->{ request }{ study };
	my $subject_prefix = lc($self->{ request }{ subject_prefix });
	if ($subject_prefix eq "") {
		$subject_prefix = "unknown";
	}
	
	# ===== GENERATE THE QUERY, SUBMIT TO DB, AND STORE RESULTS
	my $db                      = $self->{ db };
	my $conditions              = $self->rules_to_nosql( $self->{ request }{ rules } );
	$self->{ request }{ nosql } = "select json from \"$study\".\"metadata\" where $conditions";
	$self->{ results }          = $db->query( $self->{ request }{ nosql } ); 

	#print STDERR "$self->{ request }{ nosql }\n";
	#print STDERR int( @{ $self->{ results }} ) . " rows returned\n";

	# Get files under matched subjects.
	my $filesFromSubjs = [];
	my $filestore = $self->{ filestore };
        my $data_path = $filestore->data() . $self->{ request }{ study } . "/files";
	my $uniq      = { file => {}, subject => {}, num_all_files => {}, size_all_files => {} };
	my $json      = new JSON::XS();
	my $myresults   = $self->{ results };
	# Get matched subjects and files.
	foreach my $myresult (@$myresults) {
		$myjson = $json->decode( $myresult->{ json } );
		my $myfiles = $myjson->{ files };
		foreach my $myfile (@$myfiles) {
			$uniq->{ file }{ $myfile }++; 
			# Look for subject descending from root.
			my $forwardPath = "";
			my @paths   = split /\//, $myfile;
			foreach my $mypath (@paths) {
				$forwardPath .= $mypath;
				if (index(lc($mypath), $subject_prefix) == 0) {
					# Found match to subject_prefix. Done.
					last;
				}
				$forwardPath .= "/";
			}
			$uniq->{ subject }{ $forwardPath }++ if $forwardPath && -d "$data_path/$forwardPath";
		}
	}
	# Get info on all files under subjects of matched files.
	foreach my $subject (keys %{$uniq->{ subject }}) {
		my $fullpathSubject = "$data_path/$subject";
		my $idxSubj = rindex($fullpathSubject, "/");

		# Get all files under subject.
		my $allDirNames = [];
		my $allFileNames = [];
		$self->findSubDirsFiles($fullpathSubject, $allDirNames, $allFileNames);
		foreach my $thePath (@{ $allFileNames }) {
			# Get path of each file matching format from query results.
			my $pathName = substr($thePath, $idxSubj + 1);
			push(@{ $filesFromSubjs }, $pathName);
		}
	}


	# ===== SUMMARIZE THE QUERY AND RESULTS NICELY
	my $readable = $self->readable_query();
	my $summary  = $self->query_results_summary();

	# Get file filters.
	my $query_selected_dirs = "SELECT dirnames_selection_admin FROM file_filter WHERE metadata_name='$study.metadata'";
	my $res_selected_dirs = $db->query( $query_selected_dirs );
	my $file_filters_admin = [];
	foreach my $selected_dirs (@{ $res_selected_dirs }) {
		@arr_dirname = split(",", $selected_dirs->{ dirnames_selection_admin });
		foreach my $dirname (@arr_dirname) {
			$dirname =~ s/^\s+|\s+$//g;
			push(@{ $file_filters_admin }, $dirname);
		}
		if (scalar( @arr_dirname ) == 0) {
			push(@{ $file_filters_admin }, "");
		}
	}
	if (int( @{ $file_filters_admin } ) > 0) {
		# File filters found.
		return { %$summary, readable_query => $readable, results => $self->{ results }, nosql => $self->{ request }{ nosql }, file_filters_admin => $file_filters_admin, all_files_subjs => $filesFromSubjs };
	}
	else {
		# File filters are not present. Select all files.
		return { %$summary, readable_query => $readable, results => $self->{ results }, nosql => $self->{ request }{ nosql }, all_files_subjs => $filesFromSubjs };
	}
}

# ============================================================
sub insertStats {
# ============================================================
## @method public insertStats ()
## @brief Insert statistics and return the results
## @return affected rows.

	my $self  = shift;
	my $userid = $self->{ request }{ userid };
	if ($userid eq "") {
		$userid = -1;
	}
	my $studyid = $self->{ request }{ studyid };
	my $groupid = $self->{ request }{ groupid };
	my $firstname = $self->{ request }{ firstname };
	my $lastname = $self->{ request }{ lastname };
	my $email = $self->{ request }{ email };
	my $typeid = $self->{ request }{ typeid };
	my $info = $self->{ request }{ info };
	my $paramslist = $self->{ request }{ paramslist };
	my $bytes = $self->{ request }{ bytes };
	if (!$bytes) {
		$bytes = -1;
	}
	my $filters_user = $self->{ request }{ filters_user };
	if (!$filters_user) {
		$filters_user = "";
	}
	my $filters_admin = $self->{ request }{ filters_admin };
	if (!$filters_admin) {
		$filters_admin = "";
	}
	my $db = $self->{ db };
	my $json = new JSON::XS();

	# Insert stats.
	my $query_insert_stats = "INSERT INTO statistics (studyid, groupid, userid, email, typeid, info, dateentered, firstname, lastname, params_list, filters_user, filters_admin, bytes) VALUES ($studyid, $groupid, $userid, '$email', $typeid, '$info', current_timestamp, '$firstname', '$lastname', '$paramslist', '$filters_user', '$filters_admin', $bytes)";
	my $handle_insert_stats = $db->execute( $query_insert_stats );
	my $affected_rows = $handle_insert_stats->rows;

	return {affected_rows => $affected_rows};
}

# ============================================================
sub setFileFilters {
# ============================================================
## @method public setFileFilters ()
## @brief Set study selected subdirectory names and return the results
## @return affected rows.

	my $self  = shift;
	my $study = $self->{ request }{ study };
	my $file_filters_user = $self->{ request }{ file_filters_user };
	my $file_filters_admin = $self->{ request }{ file_filters_admin };
	my $db = $self->{ db };
	my $json = new JSON::XS();

	# Set file filters.
	# Try update first.
	my $query_update_selected_dirs = "UPDATE file_filter SET dirnames_selection_admin='$file_filters_admin', dirnames_selection_user='$file_filters_user'  WHERE metadata_name='$study.metadata'";
	my $handle_update_selected_dirs = $db->execute( $query_update_selected_dirs );
	$affected_rows = $handle_update_selected_dirs->rows;
	if ($affected_rows == 0) {
		# Row not present. Insert.
		my $query_insert_selected_dirs = "INSERT INTO file_filter (metadata_name, dirnames_selection_admin, dirnames_selection_user) VALUES ('$study.metadata', '$file_filters_admin', '$file_filters_user')";
		my $handle_insert_selected_dirs = $db->execute( $query_insert_selected_dirs );
		$affected_rows = $handle_insert_selected_dirs->rows;
	}

	return {affected_rows => $affected_rows};
}

# ============================================================
sub getFileFilters {
# ============================================================
## @method public getFileFilters ()
## @brief Get study subdirectory names and return the results
## @return subdirectory names in the study.

	my $self  = shift;
	my $study = $self->{ request }{ study };
	my $db = $self->{ db };
	my $json = new JSON::XS();
	
	# Get unique directory names under the study.
	my $query_json = "SELECT json FROM $study.metadata";
	my $res_json = $db->query( $query_json );
	my $dirnames = [];
	my %hash_dirs = ();
	foreach my $row (@{ $res_json }) {
		my $the_json = $json->decode( $row->{ json } );
		# Look up full pathnames of files.
		my $files = $the_json->{ files };
		foreach my $the_path (@$files) { 
			my @arr_dirname = split("/", $the_path );
			my $num_dirs = scalar( @arr_dirname );
			for (my $cnt = 0; $cnt < $num_dirs; $cnt++) {
				# Discard first and last names to get intermediate directory names.
				if (($cnt > 0) and ($cnt < $num_dirs - 1)) {
					$the_dir = $arr_dirname[$cnt];
					# Discard duplicate directory names.
					$hash_dirs{$the_dir} = $the_dir;
				}
			}
		}
	}
	# Generate array of unique directory names.
	while ( ($k, $v) = each %hash_dirs) {
		push(@{ $dirnames }, $v);
	}

	# Get file filters.
	my $query_selected_dirs = "SELECT dirnames_selection_admin, dirnames_selection_user FROM file_filter WHERE metadata_name='$study.metadata'";
	my $res_selected_dirs = $db->query( $query_selected_dirs );
	my $dirnames_admin = [];
	my $dirnames_user = [];
	foreach my $selected_dirs (@{ $res_selected_dirs }) {
		@arr_dirname_admin = split(",", $selected_dirs->{ dirnames_selection_admin });
		foreach my $dirname (@arr_dirname_admin) {
			$dirname =~ s/^\s+|\s+$//g;
			push(@{ $dirnames_admin }, $dirname);
		}
		if (scalar( @arr_dirname_admin ) == 0) {
			push(@{ $dirnames_admin }, "");
		}

		@arr_dirname_user = split(",", $selected_dirs->{ dirnames_selection_user });
		foreach my $dirname (@arr_dirname_user) {
			$dirname =~ s/^\s+|\s+$//g;
			push(@{ $dirnames_user }, $dirname);
		}
		if (scalar( @arr_dirname_user ) == 0) {
			push(@{ $dirnames_user }, "");
		}
	}

	if (int( @{ $dirnames_admin } ) > 0) {
		if (int( @{ $dirnames_user } ) > 0) {
			return { dirs => $dirnames, dirnames_admin => $dirnames_admin, dirnames_user => $dirnames_user };
		}
		else {
			return { dirs => $dirnames, dirnames_admin => $dirnames_admin };
		}
	}
	else {
		if (int( @{ $dirnames_user } ) > 0) {
			return { dirs => $dirnames, dirnames_user => $dirnames_user };
		}
		else {
			return { dirs => $dirnames};
		}
	}
}

# ============================================================
sub package {
# ============================================================
## @method public package ( results, isGetAllSubjDirs, allFilesSubjs )
## @brief Packages the results into a zipfile
## @returns The package information

	my $self      = shift;
	my $results   = shift;
	my $isGetAllSubjDirs = shift;
	my $allFilesSubjs = shift;

	my $request   = $self->{ request };
	my $json      = $self->{ json };
	my $filestore = $self->{ filestore };
	my $package   = $filestore->package( $request );
	my $rules     = $request->{ rules };
	$request->{ selected_data } = '';

	if ($isGetAllSubjDirs) {
		# Package files under matched subjects.
		foreach my $fileFullPath (@$allFilesSubjs) {
			my @pathfile = split /\//, $fileFullPath;
			my $file     = pop @pathfile;
			my $path     = join "/", @pathfile;
			$package->add( $path, $file ); 
		}
	}
	else {
		# Package files for query match.
		foreach my $result (@$results) {
			my $files = $result->{ files };
			foreach my $fileFullPath (@$files) {
				my @pathfile = split /\//, $fileFullPath;
				my $file     = pop @pathfile;
				my $path     = join "/", @pathfile;
				$package->add( $path, $file ); 
			}
		}
	}
	return $package->send( $self->{ request } );
}

# ============================================================
sub release {
# ============================================================
## @method release ( policy )
## @brief Create a snapshot of all data in the study's filestore
## @arg @c policy (all|n|latest_only) Optional; default @c all; n is a counting number greater than 1

	my $self   = shift;
	my $policy = shift;
	$self->{ filestore }->release( $policy );
}

# ============================================================
sub query_results_summary {
# ============================================================
## @method public query_results_summary ()
## @brief Summarizes the query results
## @returns hashref with count, bytes, estimated compressed size of files in package, and a list of relevant subjects

	my $self      = shift;
	my $results   = $self->{ results } || return {};
	my $size      = 0;
	my $size_all_files = 0;
	my $count_all_files = 0;
	my $uniq      = { file => {}, subject => {}, num_all_files => {}, size_all_files => {} };
	my $json      = new JSON::XS();
	my $filestore = $self->{ filestore };
	my $subject_prefix = lc($self->{ request }{ subject_prefix });
	my $data_path = $filestore->data();
	my $the_sizes = {};
	my $conditions = $self->rules_to_nosql( $self->{ request }{ rules } );
	if ($subject_prefix eq "") {
		$subject_prefix = "unknown";
	}
        $data_path = $data_path . $self->{ request }{ study } . "/files";

	# Get info on matched files.
	my %hashDirMatchedFiles;
	foreach my $result (@$results) {
		$result = $json->decode( $result->{ json } );
		my $files = $result->{ files };
		foreach my $file (@$files) { 
			$uniq->{ file }{ $file }++; 

			# Get directories that have matched files.
			$idxDirMatchedFile = rindex($file, "/");
			if ($idxDirMatchedFile != -1) {
				# NOTE: Include the ending "/" character to mark end of the directory path.
				$dirMatchedFile = substr($file, 0, $idxDirMatchedFile + 1);
				$hashDirMatchedFiles{$dirMatchedFile} = $dirMatchedFile;
			}

			# Look for subject descending from root.
			my $forwardPath = "";
			my @paths   = split /\//, $file;
			foreach my $mypath (@paths) {
				$forwardPath .= $mypath;
				if (index(lc($mypath), $subject_prefix) == 0) {
					# Found match to subject_prefix. Done.
					last;
				}
				$forwardPath .= "/";
			}
			$uniq->{ subject }{ $forwardPath }++ if $forwardPath && -d "$data_path/$forwardPath";
		}
	}
	foreach my $file (keys %{$uniq->{ file }}) {
		my $pathfile = "$data_path/$file";
		$size += int( -s $pathfile);
		$the_sizes->{ $file } = int( -s $pathfile);
	}
	my $unit  = 'B';
	my $bytes = $size;
	$size *= $self->{ compression };
	if( $size > 1024 ) { $size /= 1024; $unit = 'KB'; }
	if( $size > 1024 ) { $size /= 1024; $unit = 'MB'; }
	if( $size > 1024 ) { $size /= 1024; $unit = 'GB'; }

	# Get info on all files under subjects of matched files.
	my %hashMetaFile;
	foreach my $subject (keys %{$uniq->{ subject }}) {
		my $fullpathSubject = "$data_path/$subject";

		my $sizeFiles = 0;
		my $cntFiles = 0;
		# Get all files under subject.
		my $allDirNames = [];
		my $allFileNames = [];
		$self->findSubDirsFiles($fullpathSubject, $allDirNames, $allFileNames);
		foreach my $thePath (@{ $allFileNames }) {
			# Size information is at the 5th column.
			my $bytes = int( -s $thePath);
			# Sum all sizes.
			$sizeFiles += $bytes;
			$cntFiles++;

			# Get directories that have metadata files.
			$idxMetaFile = index(lc($thePath), "/metadata.");
			if ($idxMetaFile != -1) {
				# Found a metadata file.
				# NOTE: Include the ending "/" character to mark the end of the directory path.
				$dirFullMetaFile = substr($thePath, 0, $idxMetaFile + 1);
				$idxDir = index($dirFullMetaFile, $data_path);
				if ($idxDir != -1) {
					$dirMetaFile = substr($dirFullMetaFile, length($data_path) + 1);
					$pathMetaFile = substr($thePath, length($data_path) + 1);
					$hashMetaFile{$pathMetaFile} = $dirMetaFile;
				}
			}
		}

		$uniq->{ num_all_files } { $subject } = $cntFiles;
		$uniq->{ size_all_files } { $subject } = $sizeFiles;

		$size_all_files += $sizeFiles;
		$count_all_files += $cntFiles;
	}

	# Find directories with metadata files which should be included.
	foreach my $pathMetaFile (keys %hashMetaFile) {
		$dirMetaFile = $hashMetaFile{$pathMetaFile};

		foreach my $dirMatchedFile (keys %hashDirMatchedFiles) {
			if (index($dirMatchedFile, $dirMetaFile) == 0) {
				# This directory that has a metadata file 
				# that belongs to the query result.
				$uniq->{ file }{ $pathMetaFile }++; 
				last;
			}
		}
	}

	my $unit_all_files  = 'B';
	my $bytes_all_files = $size_all_files;
	$size_all_files *= $self->{ compression };
	if( $size_all_files > 1024 ) { $size_all_files /= 1024; $unit_all_files = 'KB'; }
	if( $size_all_files > 1024 ) { $size_all_files /= 1024; $unit_all_files = 'MB'; }
	if( $size_all_files > 1024 ) { $size_all_files /= 1024; $unit_all_files = 'GB'; }
	
	return { bytes => $bytes, estimated_size => sprintf( "%.1f %s", $size, $unit ), count => int( keys %{$uniq->{ file }} ), subjects => [ sort keys %{$uniq->{ subject }} ], file_sizes => $the_sizes, count_all_files => $count_all_files, bytes_all_files => $bytes_all_files, estimated_size_all_files => sprintf( "%.1f %s", $size_all_files, $unit_all_files ) };
}

# ============================================================
sub readable_query {
# ============================================================
## @method public readable_query ()
## @brief Converts field IDs to readable field names
## @returns Human-readable NoSQL query

	my $self     = shift;
	my $readable = $self->{ request }{ nosql };
	my $study = $self->{ request }{ study };
	my $field    = {};
	my $file     = "$Mobilize::Prefix/data/$study.human-readable.fields";
	open FILE, $file or die "Can't read human-readable field dictionary '$file' $!";
	while( <FILE> ) { chomp; my ($id, $name) = split /\t/; $field->{ $id } = $name; }
	close FILE;
	foreach my $id (keys %$field) { $readable =~ s/json->>?'$id'/\{$field->{ $id }\}/g; }

	return $readable;
}

# ============================================================
sub findSubDirsFiles {
# ============================================================
## @method public findSubDirsFiles ()
## @brief Find subdirectories and files contained.

	my $self = shift;
	my $strDirName = shift;
	my $allDirNames = shift;
	my $allFileNames = shift;

	my @resDir = read_dir($strDirName);
	foreach my $fname (@resDir) {
		my $fullPathName = $strDirName . "/" . $fname;
		chomp $thePath;
		# Exclude files.
		if (index(lc($fullPathName), "/.") != -1) {
			next;
		}
		if (-f $fullPathName) {
			push(@{ $allFileNames }, $fullPathName);
		}
		if (-d $fullPathName) {
			push(@{ $allDirNames }, $fullPathName);
			$self->findSubDirsFiles($fullPathName, $allDirNames, $allFileNames);
		}
	}
}


1;
