<?php
	include_once 'environment.php';
	
	switch ( $EddyFC [ 'environment' ] ) {
		case 'dev':
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
		
		case 'test':
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
		
		case 'prod':
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
			die( 'Invalid environment: ' . $EddyFC[ 'environment' ] );
	}