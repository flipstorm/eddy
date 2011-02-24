<?php
	// Set critical constants here
	define( 'APP_ROOT', $appRoot );
	define( 'CORE_ROOT', $coreRoot );

	$host = strtolower( $_SERVER[ 'HTTP_HOST' ] );

	// Set overridable constants here
	switch ( $host ) {
		case 'localhost':
			$config = array(
				// Basic config
				'DEBUG' => true,
				'ENVIRONMENT' => 'dev',
				'SITE_ROOT' => '/~simonhamp/eddy2.0/public',

				// MySQL
				'MYSQL_DB'	=> 'vixles',
				'MYSQL_HOST' => '127.0.0.1',
				'MYSQL_USER' => 'root',
				'MYSQL_PASSWORD' => 'root',
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

	foreach ( $config as $const => $value ) {
		if ( !defined( $const ) ) {
			define( $const, $value );
		}
	}