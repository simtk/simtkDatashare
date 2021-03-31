<?php

/**
 * Copyright 2020-2021, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

	# ===== REDIRECT TO HTTPS
	# There seems to be some sort of cache that is preventing Apache from following
	# redirects or other configuration changes.
	$not_https = ( empty( $_SERVER[ 'HTTPS' ] ) || $_SERVER[ 'HTTPS' ] == 'off' );
	$not_local = $_SERVER[ 'HTTP_HOST' ] != "localhost" && $_SERVER[ 'HTTP_HOST' ] != '127.0.0.1';
	if( $not_local && $not_https ) {
		$https = 'https://' . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
		header( 'HTTP/1.1 301 Moved Permanently' );
		header( 'Location: ' . $https );
		exit();
	}

	session_name( 'mobilizeds' );
	session_save_path( '/usr/local/mobilizeds/tokens' );
	session_start();
?>
