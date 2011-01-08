<?php
	##################### Init #####################
	$inc_path = array(
			realpath( '../' ),
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
	if ( class_exists( 'FB', false ) ) {
		FB::setEnabled( DEBUG );
	}
	
	set_exception_handler( 'exceptionHandler' );
	// TODO: Upgrade PHP errors to exceptions!

	$EddyFC[ 'root' ] = SITE_ROOT;
	$request = getCurrentURIPath();
	$EddyFC[ 'request' ] = $request[ 'fixed' ];
	$path = pathinfo( $EddyFC[ 'request' ] );
	$EddyFC[ 'requestmethod' ] = $path[ 'filename' ];
	$EddyFC[ 'requestformat' ] = strtolower( $path[ 'extension' ] );
	$EddyFC[ 'requestpath' ] = trim( $path[ 'dirname' ], '.' );

	if ( !$EddyFC[ 'requestpath' ] ) {
		$EddyFC[ 'requestpath' ] = 'default';
	}

	##################### Controller #####################
	// Calculate the class name convention
	$url = urldecode( strtolower( $EddyFC[ 'requestpath' ] ) );

	// Clean up the request
	$controllerName = str_replace( ' ', '_',
			ucwords (
				preg_replace( array( '/\s/', '/[^a-z0-9\\/]+/i', '@/@' ), array( '', '', ' ' ),
					$url
				)
			)
		);

	// Cycle through controllers until we find one
	$controllerPath = explode( '_', $controllerName );

	while ( !class_exists( $controllerName . '_Controller' ) ) {
		// Cycle up until we find a class that does exist
		if ( count( $controllerPath ) > 0 ) {
			$EddyFC[ 'requestmethod' ] = strtolower( array_pop( $controllerPath ) );

			$upperLevelControllerName = str_replace( ' ', '_', ucwords( implode( ' ', $controllerPath ) ) );
		}
		else {
			$upperLevelControllerName = 'Default';
		}

		if ( isset( $upperLevelControllerName ) ) {
			$controllerName = $upperLevelControllerName;
		}
	}

	// Finish controller naming
	$contName = strtolower( $controllerName );
	$controllerName = $controllerName . '_Controller';
	$EddyFC[ 'requestcontroller' ] = $controllerName;

	// Work out what method to call and what params to pass to it
	// Determine if the desired method exists, fallback on index and if that doesn't exist, give up

	if ( method_exists( $controllerName, $EddyFC[ 'requestmethod' ] ) ) {
		$strstr = stristr( $EddyFC[ 'request' ], $EddyFC[ 'requestmethod' ] . '/' );

		if ( $strstr ) {
			$params = str_replace( '^' . $EddyFC[ 'requestmethod' ] . '/', '', '^' . $strstr );
		}

		if ( strpos( $EddyFC[ 'requestpath' ], $EddyFC[ 'requestmethod' ] ) ) {
			$EddyFC[ 'requestpath' ] = str_replace( $EddyFC[ 'requestmethod' ] . '$', '', $EddyFC[ 'requestpath' ] . '$' );
		}
	}
	elseif ( method_exists( $controllerName, 'index' ) ) {
		$EddyFC[ 'requestmethod' ] = 'index';
		$params = trim( str_replace( str_replace( '_', '/', $contName ), '', $EddyFC[ 'request' ] ), '/' );
	}

	// Remove the format from the end of the paramaters
	if ( !empty( $params ) ) {
		$EddyFC[ 'requestparams' ] = str_replace( '.' . $EddyFC[ 'requestformat' ], '', $params );
	}

	// Instantiate the controller
	if ( class_exists( $controllerName ) ) {
		$controller = new $controllerName;

		if ( !method_exists( $controller, $EddyFC[ 'requestmethod' ] ) ) {
			// No method exists for this request, 404?
			FB::warn( 'Warning: ' . $controllerName . '::' . $EddyFC[ 'requestmethod' ] . '(' . $EddyFC[ 'requestparams' ] . ') : Method doesn\'t exist' );
		}
		elseif ( method_is( 'public', $EddyFC[ 'requestmethod' ], $controller ) ) {
			// Build the parameters to pass to the method
			$params = array();

			if ( isset( $EddyFC[ 'requestparams' ] ) ) {
				$params = explode( '/', $EddyFC[ 'requestparams' ] );
			}

			// If the final parameter is index, pop it off parameters
			if ( $params[ count( $params ) - 1 ] == 'index' && $request[ 'actual' ] != $request[ 'fixed' ] ) {
				array_pop( $params );
			}

			// Call the method
			call_user_func_array( array( $controller, $EddyFC[ 'requestmethod' ] ), $params );
		}
		else {
			// Method exists but isn't public so we can't call it... so why are we trying to access it?
			// if (!DEBUG) { 404 } else { show a helpful developer message? }
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
			// TODO: Might need to add security to these API-able datatypes so that they can't just be used externally
			header( 'Content-Type: text/xml; charset=UTF-8' );

			$data = $EddyFC[ 'viewdata' ];
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

			if ( DEBUG ) {
				$data[ 'debug' ][ 'Queries' ] = EddyDB::$queries;
				$data[ 'debug' ][ '$EddyFC' ] = $EddyFC;
				$data[ 'debug' ][ '$_SERVER' ] = $_SERVER;
			}

			if ( !is_array( $EddyFC[ 'viewdata' ] ) ) {
				header( 'HTTP/1.1 404 Not Found' );
			}

			$jsonResponse = @json_encode( $data );

			// JSONP
			if ( isset( $_REQUEST[ 'callback' ] ) ) {
				// Switch content type to application/javascript
				header( 'Content-Type: application/javascript; charset=UTF-8' );
				$jsonResponse = $_REQUEST[ 'callback' ] . '(' . $jsonResponse . ');';
			}

			echo $jsonResponse;

			break;
		default:
			if ( file_exists( '../public/skins/' . $EddyFC[ 'skin' ] . '/template.phtml' ) ) {
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