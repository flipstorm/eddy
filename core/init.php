<?php
	##################### Init #####################
	$coreRoot = realpath( '../core/' );
	
	$inc_path = array(
			$appRoot,
			$coreRoot,
			get_include_path()
		);
	set_include_path( implode( PATH_SEPARATOR, $inc_path ) );

	include_once 'app/includes/config.php';

	if ( DEBUG ) {
		error_reporting( E_ALL ^ E_NOTICE );
		ob_start( 'ob_gzhandler' );
	}

	session_start();

	include_once 'core/functions.php';

	// Set up FirePHP if it's installed
	FB::setEnabled( DEBUG );
	
	set_exception_handler( 'exceptionHandler' );
	// TODO: Upgrade PHP errors to exceptions!

	$EddyFC = handleRequest();
	
	// Work out what method to call and what params to pass to it
	// Determine if the desired method exists, fallback on index and if that doesn't exist, give up
	
	if ( method_exists( $EddyFC[ 'requestcontroller' ], $EddyFC[ 'requestmethod' ] ) ) {
		$strstr = stristr( $EddyFC[ 'request' ][ 'fixed' ], $EddyFC[ 'requestmethod' ] . '/' );

		if ( $strstr ) {
			$params = str_replace( '^' . $EddyFC[ 'requestmethod' ] . '/', '', '^' . $strstr );
		}

		if ( strpos( $EddyFC[ 'requestpath' ], $EddyFC[ 'requestmethod' ] ) ) {
			$EddyFC[ 'requestpath' ] = str_replace( $EddyFC[ 'requestmethod' ] . '$', '', $EddyFC[ 'requestpath' ] . '$' );
		}
	}
	elseif ( method_exists( $EddyFC[ 'requestcontroller' ], 'index' ) ) {
		$EddyFC[ 'requestmethod' ] = 'index';
		$params = trim( str_replace( str_replace( '_', '/', $EddyFC[ 'controllerfilename' ] ), '', $EddyFC[ 'request' ][ 'fixed' ] ), '/' );
	}

	// Remove the format from the end of the paramaters
	if ( !empty( $params ) ) {
		$EddyFC[ 'requestparams' ] = str_replace( '.' . $EddyFC[ 'requestformat' ], '', $params );
	}

	// Instantiate the controller
	if ( class_exists( $EddyFC[ 'requestcontroller' ] ) ) {
		$controller = new $EddyFC[ 'requestcontroller' ];
		
		if ( method_is( 'public', $EddyFC[ 'requestmethod' ], $controller ) ) {		
			// Build the parameters to pass to the method
			$params = array();

			if ( isset( $EddyFC[ 'requestparams' ] ) ) {
				$params = explode( '/', $EddyFC[ 'requestparams' ] );
			}

			// If the final parameter is index, pop it off parameters
			if ( $params[ count( $params ) - 1 ] == 'index' && $EddyFC[ 'request' ][ 'actual' ] != $EddyFC[ 'request' ][ 'fixed' ] ) {
				array_pop( $params );
			}

			// Call the method
			call_user_func_array( array( $controller, $EddyFC[ 'requestmethod' ] ), $params );
		}
		else {
			header( 'HTTP/1.1 404 Not Found' );
		}
	}

	if ( isset( $controller ) && $controller instanceof EddyController ) {
		$EddyFC[ 'skin' ] = $controller->getSkin();
		$EddyFC[ 'skinfolder' ] = SITE_ROOT . '/skins/' . $EddyFC[ 'skin' ];
		$EddyFC[ 'view' ] = $controller->getView();
	}

	##################### View #####################
	// Capture the view variables passed from the controller
	if ( $controller instanceof EddyController ) {
		$EddyFC[ 'viewdata' ] = $controller->getData();
	}

	switch ( $EddyFC[ 'requestformat' ] ) {
		case 'css':
			header( 'Content-Type: text/css; charset=UTF-8' );
			include_once( $EddyFC[ 'view' ] . '.css.php' );

			break;
		case 'js':
			header( 'Content-Type: application/javascript; charset=UTF-8' );
			include_once( $EddyFC[ 'view' ] . '.js.php' );

			break;
		case 'xml':
			header( 'Content-Type: text/xml; charset=UTF-8' );

			$data = $EddyFC[ 'viewdata' ];

			if ( !is_array( $EddyFC[ 'viewdata' ] ) ) {
				header( 'HTTP/1.1 404 Not Found' );
				exit;
			}
			
			$xml = $data[ 'xml' ];

			if ( $xml instanceof SimpleXMLElement ) {
				echo $xml->asXML();
			}
			else {
				echo $xml;
			}

			break;
		case 'json':
			// Switch content type to application/json
			header( 'Content-Type: application/json; charset=UTF-8' );

			$data = $EddyFC[ 'viewdata' ];

			if ( !is_array( $EddyFC[ 'viewdata' ] ) ) {
				header( 'HTTP/1.1 404 Not Found' );
				exit;
			}

			if ( $data[ 'json' ] ) {
				$jsonResponse = $data[ 'json' ];
			}
			else {
				$jsonResponse = @json_encode( $json );
			}

			// JSONP
			if ( isset( $_REQUEST[ 'callback' ] ) ) {
				// Switch content type to application/javascript
				header( 'Content-Type: application/javascript; charset=UTF-8' );
				$jsonResponse = $_REQUEST[ 'callback' ] . '(' . $jsonResponse . ');';
			}

			echo $jsonResponse;

			break;
		default:
			if ( file_exists( APP_ROOT . '/public/skins/' . $EddyFC[ 'skin' ] . '/template.phtml' ) ) {
				// Load a skin (which will load the view)
				foreach ( $EddyFC[ 'viewdata' ] as $var => $val ) {
					$$var = $val;
				}

				include_once 'public/skins/' . $EddyFC[ 'skin' ] . '/template.phtml';
			}
			else {
				// Just load a view
				include_view();
			}
	}

	##################### Debug #####################
	if ( DEBUG ) {
		// This should be a table
		@FB::table( count( EddyDB::$queries ) . ' Queries', array_merge( array( array( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );

		unset( $EddyFC[ 'viewdata' ] );
		FB::info( $EddyFC, '$EddyFC' );
		FB::info( $_SERVER, '$_SERVER' );
		FB::info( $_SESSION, '$_SESSION' );
		FB::info( $_GET, '$_GET' );
		FB::info( $_POST, '$_POST' );

		ob_end_flush();
	}

?>