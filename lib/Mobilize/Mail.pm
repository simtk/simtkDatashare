package Mobilize::Mail;

# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

use Mobilize;
use Mojo::SMTP::Client;

## @brief Class to manage sending e-mail to users
## @details Also see user/register.php and user/forgot.php

# ============================================================
sub send_mail {
# ============================================================
## @function public send_mail ()
## @brief Sends e-mail via SMTP using Mobilize configuration for host and credentials

	my $from    = "$Mobilize::Conf->{ smtp }{ user }";
	my $to      = shift;
	my $subject = shift;
	my $body    = shift;
	my $smtp    = new Mojo::SMTP::Client( address => $Mobilize::Conf->{ smtp }{ host }, tls => 1, autodie => 1 );
	my @headers = ();
	push @headers, "From: $from", "To: $to", "Subject: $subject", "Content-Type: text/html; charset=UTF-8", '', $body;

	$smtp->send( 
		from => $from,
		to   => $to,
		data => join( "\r\n", @headers ),
		quit => 1
	);
}

# ============================================================
sub send_link {
# ============================================================
## @function public send_link ()
## @brief Sends a link to the package generated in response to a user query request

	my $session  = shift;
	my $request  = shift;
	my $package  = shift;

	my $to       = "$session->{ firstname } $session->{ lastname } <$session->{ email }>";
	my $subject  = "Query Results";
	my $comments = $request->{ comments } ? "Your comments about the query results:\n\n$request->{ comments }\n\n" : '';
	my $n        = int( @{$request->{ summary }{ subjects }});
	my $uniqueSubjectsLength = $request->{ uniqueSubjectsLength };
	my $uniqueFilesLength = $request->{ uniqueFilesLength };
	my $strSubjects = $uniqueSubjectsLength . " subjects";
	if ($uniqueSubjectsLength == 1) {$strSubjects = $uniqueSubjectsLength . " subject";}
	my $estimatedZipFileSize = $request->{ estimatedZipFileSize };
	my $paramslist = $request->{ paramslist };
	my $filters_dir = $request->{ filters_dir };
	my $body     = <<EOF;
<p>Hello $session->{ firstname },</p>

<p>Your query of the dataset "$session->{ study_name }" with the following parameters:</p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;${ paramslist } ${ filters_dir }</p>

<p>has returned ${ uniqueFilesLength } files over ${ strSubjects }. You can retrieve those results by <a href="$request->{ urlDownload }&namePackage=$package->{ url }&agreed=1">clicking this link</a>. You may need to copy-and-paste the link into your browser.</p>

<p>Note: if your results do not download, make sure you have allowed pop-ups. In most browsers, you can do this by selecting the small icon to "allow pop-ups..." in the URL bar.  Once pop-ups are allowed, click the link again to download the results.</p>

<p>${ comments }</p>
<p><b>The link will expire in 2 days.</b></p>

<p>Best regards,
<br/>
The SimTK Team</p>
<br/>

EOF

	send_mail( $to, $subject, $body );
}

# ============================================================
sub send_notification_approval {
# ============================================================
## @function public send_notification_approval ()
## @brief Sends a notification that the account has been approved

	my $session  = shift;
	my $user     = shift;

	my $to       = "$user->{ fname } $user->{ lname } <$user->{ email }>";
	my $subject  = "Account Approved";
	my $n        = int( @{$request->{ results }{ subjects }});
	my $body     = <<EOF;
Hello $user->{ fname },
<br/><br/>

Your account has been approved. Welcome to $Mobilize::Conf->{ study }{ description }!
<br/><br/>

You can start browsing and searching the data at: $Mobilize::Conf->{ apache }{ baseurl };
<br/><br/>

Best regards,
<br/><br/>

The $Mobilize::Conf->{ study }{ description } Team
<br/>
$Mobilize::Conf->{ apache }{ baseurl };
<br/>
$Mobilize::Conf->{ smtp }{ user };
<br/><br/>

EOF

	send_mail( $to, $subject, $body );
}



1;
