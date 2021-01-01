<?php
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
