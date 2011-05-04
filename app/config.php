<?php
	$host = strtolower( $_SERVER[ 'HTTP_HOST' ] );

	// Set overridable constants here
	switch ( $host ) {
		case 'localhost':
			$config = array(
				// Basic config
				'DEBUG' => true,
				'SITE_ROOT' => '/~simonhamp/eddy2.0/public',

				'ENVIRONMENT' => 'dev',
				
				// MySQL
				'MYSQL_DB'	=> '',
				'MYSQL_HOST' => '',
				'MYSQL_USER' => '',
				'MYSQL_PASSWORD' => '',
				'MYSQL_PORT' => null,
				'MYSQL_SOCKET' => null,
				'MYSQL_TBLPREF' => null,

				// Output Caching
				// Turn output caching on or off
				'OUTPUT_CACHE_ENABLED' => true,

				// Default caching rule: everything/nothing
				'OUTPUT_CACHE_ALL' => false,
				//'OUTPUT_CACHE_PATH_DEFAULT' => 'app_cache',
				'OUTPUT_CACHE_COMPRESS_DEFAULT' => null,

				// Cache lifetime in seconds. use null to never expire automatically
				'OUTPUT_CACHE_TTL' => null
			);

			break;
		default:
			die( 'Invalid environment: ' . $_SERVER[ 'HTTP_HOST' ] );
	}