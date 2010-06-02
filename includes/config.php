<?php
	$EddyFC [ 'environment' ] = 'dev';
	
	switch ( $EddyFC [ 'environment' ] ) {
		case 'dev':
			define ( 'DEBUG',			true					);
			define ( 'SITE_ROOT',		'/~simonhamp/eddy2.0'	);
			define ( 'MYSQL_DB',		'excelsior_optimise'	);
			define ( 'MYSQL_HOST',		'localhost'				);
			define ( 'MYSQL_USER',		'root'					);
			define ( 'MYSQL_PASSWORD',	'armageddon'			);
			define ( 'MYSQL_PORT',		null					);
			define ( 'MYSQL_SOCKET',	null					);
			define ( 'MYSQL_TBLPREF',	null					);
			break;
		
		case 'test':
			define ( 'DEBUG',			true					);
			define ( 'SITE_ROOT',		''						);
			define ( 'MYSQL_DB',		'excelsior_optimise'	);
			define ( 'MYSQL_HOST',		'localhost'				);
			define ( 'MYSQL_USER',		'root'					);
			define ( 'MYSQL_PASSWORD',	'armageddon'			);
			define ( 'MYSQL_PORT',		null					);
			define ( 'MYSQL_SOCKET',	null					);
			define ( 'MYSQL_TBLPREF',	null					);
			break;
		
		case 'prod':
			define ( 'DEBUG',			false					);
			define ( 'SITE_ROOT',		''						);
			define ( 'MYSQL_DB',		'excelsior_optimise'	);
			define ( 'MYSQL_HOST',		'localhost'				);
			define ( 'MYSQL_USER',		'excelsior'				);
			define ( 'MYSQL_PASSWORD',	'armageddon'			);
			define ( 'MYSQL_PORT',		null					);
			define ( 'MYSQL_SOCKET',	null					);
			define ( 'MYSQL_TBLPREF',	null					);
			break;
		
		default:
			die ( 'Invalid environment: ' . $EddyFC [ 'environment' ] );
	}