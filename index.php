<?php
	ob_start ( 'ob_gzhandler' );
	
	include_once ( 'includes/config.php' );
	
	##################### Init #####################
	if ( DEBUG ) { error_reporting ( E_ALL ^ E_NOTICE ); }
	set_include_path ( dirname ( __FILE__ ) . PATH_SEPARATOR . get_include_path() );
	
	session_start();
	if ( !isset ( $_SESSION [ 'UsergroupRank' ] ) ) { $_SESSION [ 'UsergroupRank' ] = 9999; } // Default usergroup rank (lowest)

	include_once ( 'includes/functions.php' );
	FB::setEnabled ( DEBUG );
	set_exception_handler ( 'exceptionHandler' );

	$EddyFC [ 'root' ] = SITE_ROOT;
	$EddyFC [ 'request' ] = getCurrentURIPath();
	$path = pathinfo ( $EddyFC [ 'request' ] );
	$EddyFC [ 'requestmethod' ] = $path [ 'filename' ];
	$EddyFC [ 'requestformat' ] = $path [ 'extension' ];
	$EddyFC [ 'requestpath' ] = trim ( $path [ 'dirname' ], '.' );

	if ( !$EddyFC [ 'requestpath' ] ) {
		$EddyFC [ 'requestpath' ] = 'default';
	}
	
	##################### Controller #####################
	// Calculate the class name convention
	$url = urldecode ( strtolower ( $EddyFC [ 'requestpath' ] ) );
	
	// Clean up the request
	$controllerName = str_replace ( ' ', '_',
			ucwords (
				preg_replace ( array ( '/\s/', '@/@', '/[^a-z0-9]+/i', ), array ( '', ' ', '' ),
					$url
				)
			)
		);
	
	// Cycle through controllers until we find one
	$controllerPath = explode ( '_', $controllerName );
	
	while ( !class_exists ( $controllerName . '_Controller' ) ) {
		// Cycle up until we find a class that does exist
		if ( count ( $controllerPath ) > 0 ) {
			$EddyFC [ 'requestmethod' ] = strtolower ( array_pop ( $controllerPath ) );
			
			$upperLevelControllerName = str_replace ( ' ', '_', ucwords ( implode ( ' ', $controllerPath ) ) );
		}
		else {
			$upperLevelControllerName = 'Default';
			break;
		}
	
		if ( isset ( $upperLevelControllerName ) ) {
			$controllerName = $upperLevelControllerName;
		}
		
		// Work out what method to call and what params to pass to it
		// Determine if the desired method exists, fallback on index and if that doesn't exist, give up
		
		$params = str_replace ( $EddyFC [ 'requestmethod' ] . '/', '', stristr ( $EddyFC [ 'request' ], $EddyFC [ 'requestmethod' ] . '/' ) );
		$EddyFC [ 'requestparams' ] = str_replace ( '.' . $EddyFC [ 'requestformat' ], '', $params );
		$EddyFC [ 'requestpath' ] = '/' . str_replace ( $EddyFC [ 'requestmethod' ] . '$', '', $EddyFC [ 'requestpath' ] . '$' );
	}
	
	// Finish controller naming
	$controllerName = $controllerName . '_Controller';
	
	// Instantiate the controller
	if ( class_exists ( $controllerName ) ) {
		eval ( '$controller = new ' . $controllerName . '();' );
		
		// Build the parameters to pass to the method
		$params = array();

		if ( isset ( $EddyFC [ 'requestparams' ] ) ) {
			$params = explode ( '/', $EddyFC [ 'requestparams' ] );
		}
		
		if ( method_exists ( $controller, $EddyFC [ 'requestmethod' ] ) ) {
			// Continue
		}
		/*elseif ( method_exists ( $controller, 'index' ) ) {
			// Modify the method to call
			$EddyFC [ 'requestmethod' ] = 'index';
		}*/
		else {
			// No method exists for this request, 404?
			FB::warn ( 'Warning: ' . $controllerName . '::' . $EddyFC [ 'requestmethod' ] . '(' . $EddyFC [ 'requestparams' ] . ') : Method doesn\'t exist' );
			$dontCallMethod = true;
		}
		
		if ( !$dontCallMethod ) {
			call_user_func_array ( array ( $controller, $EddyFC [ 'requestmethod' ] ), $params );
		}
	}



	if ( isset ( $controller ) && $controller instanceof EddyController ) {
		$EddyFC [ 'skin' ] = $controller->getSkin();
		$EddyFC [ 'skinfolder' ] = SITE_ROOT . '/skins/' . $EddyFC [ 'skin' ];
		$EddyFC [ 'view' ] = $controller->getView();
		
		##################### Security #####################
		if ( $_SESSION [ 'UsergroupRank' ] > $controller->getUsergroupRank() ) {
			redirect ( $EddyFC [ 'root' ] . '/login', true );
		}
	}



	##################### View #####################
	switch ( $EddyFC [ 'requestformat' ] ) {
		case 'json':
			header( 'Content-Type: text/javascript; charset=utf8' );
			
			if ( DEBUG ) {
				$json [ 'debug' ][ 'queries' ] = EddyDB::$queries;
				$json [ 'debug' ][ 'eddyfc' ] = $EddyFC;
			}
			
			if ( $controller instanceof EddyController ) {
				foreach ( $controller->getData() as $var => $val ) {
					$json [ $var ] = $val;
				}
			}
			else {
				header ( 'HTTP/1.1 404 Not Found' );
			}
			
			$jsonResponse = @json_encode ( $json );
			
			// JSONP
			if ( isset ( $_GET [ 'callback' ] ) ) {
				$jsonResponse = $_GET [ 'callback' ] . '(' . $jsonResponse . ');';
			}
			
			echo $jsonResponse;
			
			break;
		default:
			if ( $controller instanceof EddyController ) {
				foreach ( $controller->getData() as $var => $val ) {
					$$var = $val;
				}
			}
			
			if ( file_exists ( 'skins/' . $EddyFC [ 'skin' ] . '/template.phtml' ) ) {
				// Load a skin (which will load the view)
				include_once ( 'skins/' . $EddyFC [ 'skin' ] . '/template.phtml' );
			}
			elseif ( file_exists ( 'views/' . $EddyFC [ 'view' ] . '.phtml' ) ) {
				// Just load the view
				include_once ( 'views/' . $EddyFC [ 'view' ] . '.phtml' );
			}
			else {
				// Load the default view (404?)
				include_once ( 'views/default.phtml' );
			}
	}
	
	##################### Debug #####################
	if ( DEBUG ) {
		// This should be a table
		//FB::table ( count ( EddyDB::$queries ) . ' Queries', array_merge ( array ( array ( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );
		FB::info ( $EddyFC, 'EDDY FC' );
		FB::info ( $_SERVER );
	}
	
	ob_end_flush();
?>