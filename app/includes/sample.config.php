<?php
	// Use this file to set any app-wide constants
	define( 'APP_ROOT',		$appRoot	);
	define( 'CORE_ROOT',	$coreRoot	);

	// Environment is determined based on HTTP_HOST matching
	switch ( strtolower( $_SERVER[ 'HTTP_HOST' ] ) ) {
		case 'localhost':
			define( 'DEBUG',			true					);
			define( 'SITE_ROOT',		''						);
			define( 'MYSQL_DB',			''						);
			define( 'MYSQL_HOST',		'localhost'				);
			define( 'MYSQL_USER',		''						);
			define( 'MYSQL_PASSWORD',	''						);
			define( 'MYSQL_PORT',		null					);
			define( 'MYSQL_SOCKET',		null					);
			define( 'MYSQL_TBLPREF',	null					);
			break;
		
		case 'test.domain.com':
			define( 'DEBUG',			true					);
			define( 'SITE_ROOT',		''						);
			define( 'MYSQL_DB',			''						);
			define( 'MYSQL_HOST',		'localhost'				);
			define( 'MYSQL_USER',		''						);
			define( 'MYSQL_PASSWORD',	''						);
			define( 'MYSQL_PORT',		null					);
			define( 'MYSQL_SOCKET',		null					);
			define( 'MYSQL_TBLPREF',	null					);
			break;
		
		case 'www.domain.com':
			define( 'DEBUG',			false					);
			define( 'SITE_ROOT',		''						);
			define( 'MYSQL_DB',			''						);
			define( 'MYSQL_HOST',		'localhost'				);
			define( 'MYSQL_USER',		''						);
			define( 'MYSQL_PASSWORD',	''						);
			define( 'MYSQL_PORT',		null					);
			define( 'MYSQL_SOCKET',		null					);
			define( 'MYSQL_TBLPREF',	null					);
			break;
		
		default:
			die( 'Invalid environment: ' . $_SERVER[ 'HTTP_HOST' ] );
	}