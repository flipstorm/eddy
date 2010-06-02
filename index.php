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
	$controllerName = ucwords ( str_replace ( '/', '_', $EddyFC [ 'requestpath' ] ) ) . '_Controller';
	
	// Instantiate a controller
	if ( class_exists ( $controllerName ) ) {
		$controller = new $controllerName();
		
		if ( method_exists ( $controller, $EddyFC [ 'requestmethod' ] ) ) {
			call_user_func ( array ( $controller, $EddyFC [ 'requestmethod' ] ) );
		}
	}
	else {
		// Load the default and call the 404 method?
		$controller = new Default_Controller();
		$controller->error404();
	}
	
	if ( isset ( $controller ) && $controller instanceof EddyController ) {
		$EddyFC [ 'skin' ] = $controller->getSkin();
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
			
			echo json_encode ( $json );
			
			break;
		default:
			if ( $controller instanceof EddyController ) {
				foreach ( $controller->getData() as $var => $val ) {
					$$var = $val;
				}
			}
			
			if ( $EddyFC [ 'view' ] == '' ) {
				$EddyFC [ 'view' ] = $EddyFC [ 'request' ];
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
				// Load the default view
				include_once ( 'views/default.phtml' );
			}
	}
	
	##################### Debug #####################
	if ( DEBUG ) {
		// This should be a table
		FB::info ( count ( EddyDB::$queries ) . ' Queries', EddyDB::$queries );
		FB::info ( '$EddyFC', $EddyFC );
	}
	
	ob_end_flush();
?>