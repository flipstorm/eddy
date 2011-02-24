<?php
	$requeststart = microtime(true);

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

	$EddyFC = URI_Helper::handle_request();

	// ### CACHING - CHECK FOR CACHED RESOURCE ###
	$fr = URI_Helper::get_current( false );
	$full_request = $fr[ 'fixed' ];

	$cacheFile = validCache( $full_request );
	
	if ( $cacheFile && strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) != 'POST' && OUTPUT_CACHE_ENABLED ) {
		$cf = pathinfo( $cacheFile );

		if ( ob_get_level() == 1 && implode( '.', $EddyFC[ 'requestextensions' ] ) == 'xml.gz' ) {
			// We may need to buffer this output again for sending in binary format
			ob_start( 'ob_gzhandler' );
		}

		header( 'X-Eddy-Cache: ' . $cacheFile );

		// Serve the cached version
		if ( $cf[ 'extension' ] == 'gz' ) {
			readgzfile( $cacheFile );
		}
		else {
			echo file_get_contents( $cacheFile );
		}
	}
	// ### END CACHING ###
	else {
		// Work out what method to call and what params to pass to it
		// Determine if the desired method exists, fallback on index and if that doesn't exist, give up

		if ( method_exists( $EddyFC[ 'requestcontroller' ], $EddyFC[ 'requestmethod' ] ) ) {
			$strstr = stristr( $EddyFC[ 'request' ][ 'fixed' ], $EddyFC[ 'requestmethod' ] . '/' );

			if ( $strstr ) {
				$params = str_replace( '^' . $EddyFC[ 'requestmethod' ] . '/', '', '^' . $strstr );
			}

			if ( strpos( $EddyFC[ 'requestpath' ] . '$', $EddyFC[ 'requestmethod' ] . '$' ) !== false ) {
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

			if ( !$controller->cancel_request ) {
				if ( method_exists( $controller, $EddyFC[ 'requestmethod' ] ) ) {
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
					//timeme(function() use ($controller, $EddyFC, $params) {
						call_user_func_array( array( $controller, $EddyFC[ 'requestmethod' ] ), $params );
					//}, 'Run request method');

					// Start output buffering if we haven't already but need to
					if ( ob_get_level() == 1 && $controller->cacheable ) {
						ob_start( 'ob_gzhandler' );
					}
				}
				else {
					// Method exists but isn't public so we can't call it... so why are we trying to access it?
					// if (!DEBUG) { 404 } else { show a helpful developer message? }
					$controller->error404();
				}
			}
		}

		if ( isset( $controller ) && $controller instanceof EddyController ) {
			$EddyFC[ 'skin' ] = $controller->skin;
			$EddyFC[ 'skinfolder' ] = SITE_ROOT . '/skins/' . $EddyFC[ 'skin' ];
			$EddyFC[ 'view' ] = $controller->view;
			$EddyFC[ 'viewdata' ] = $controller->data;
		}

		switch ( $EddyFC[ 'requestformat' ] ) {
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
					extract( $EddyFC[ 'viewdata' ] );
					include 'public/skins/' . $EddyFC[ 'skin' ] . '/template.phtml';
				}
				else {
					// Just load a view
					include_view();
				}
		}

		// ### CACHING - CACHE THE CURRENT RESOURCE ###
		// Write the buffered output to file if we are allowed to
		if ( $controller->cacheable ) {
			$cache_file = ( $controller->cache_file ) ? $controller->cache_file . '.cache' : md5( $full_request ) . '.cache';
			$filename = realpath( './app_cache' ) . '/' . $cache_file;
			$filedata = ob_get_contents();

			switch ( strtolower( $controller->cache_compress ) ) {
				case 'gzip':
					$gz_fp = gzopen( $filename . '.gz', 'wb9' );
					$savetodisk = ( gzwrite( $gz_fp, $filedata ) && gzclose( $gz_fp ) );
					break;
				default:
					$savetodisk = @file_put_contents( $filename, $filedata );
			}

			FB::info( $savetodisk, 'Cached resource saved to disk?' );
		}
		// ### END CACHING ###
	}

	// Content-Disposition - relies on output buffering with ob_gzhandler
	switch ( implode( '.', $EddyFC[ 'requestextensions' ] ) ) {
		case 'xml.gz':
			$cd_filename = str_replace( '/', '_', $EddyFC[ 'request' ] );

			header( 'Content-Type: application/x-gzip' );
			header( 'Content-Disposition: attachment;filename="' . $cd_filename . '"' );
			header( 'Content-Transfer-Encoding: binary' );
			break;
	}

	if ( DEBUG ) {
		// This should be a table
		@FB::table( count( EddyDB::$queries ) . ' Queries', array_merge( array( array( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );

		unset( $EddyFC[ 'viewdata' ] );
		FB::info( $EddyFC, '$EddyFC' );
		FB::info( $_SERVER, '$_SERVER' );
		FB::info( $_SESSION, '$_SESSION' );
		FB::info( $_GET, '$_GET' );
		FB::info( $_POST, '$_POST' );
		FB::info( 'Page took ' . ( microtime(true) - $requeststart ) . 's to prepare' );
	}

	if ( ob_get_level() > 0 ) {
		ob_end_flush();
	}
?>