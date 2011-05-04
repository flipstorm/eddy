<?php
	class Eddy {
		private static $path_to_core;
		private static $path_to_app;
		
		public static $app_folder = 'app';
		public static $public_folder = 'public';
		
		public static $request;
		public static $controller;
		
		public static function init() {
			self::set_paths();
			
			self::config();
			
			if ( DEBUG ) {
				error_reporting( E_ALL ^ E_NOTICE );
				ob_start( 'ob_gzhandler' );
			}

			session_start();

			require_once 'core/functions.php';
			
			FB::setEnabled( DEBUG );

			self::$request = new EddyRequest();
			
			if ( !self::get_cached() ) {
				self::run_action();
				
				FB::info( Eddy::$controller );
				self::render_view();
			}
		}
		
		private static function set_paths() {
			if ( !self::$path_to_app ) {
				self::app();
			}
			
			if ( !self::$path_to_core ) {
				self::core();
			}

			define( 'APP_ROOT', self::$path_to_app );
			define( 'CORE_ROOT', self::$path_to_core );
			define( 'PUBLIC_FOLDER', self::$public_folder );
			define( 'APP_FOLDER', self::$app_folder );

			$inc_path = array(
					APP_ROOT,
					CORE_ROOT,
					get_include_path()
				);
			set_include_path( implode( PATH_SEPARATOR, $inc_path ) );
		}
		
		public static function app( $path = null ) {
			if ( !$path ) {
				$path = '../';
			}
			
			self::$path_to_app = realpath( $path );
		}
		
		public static function core( $path = null ) {
			if ( !$path ) {
				$path = __DIR__;
			}
			
			self::$path_to_core = realpath( $path );
		}
		
		private static function config() {
			require_once( self::$app_folder . '/config.php' );

			foreach ( $config as $const => $value ) {
				if ( !defined( $const ) ) {
					define( $const, $value );
				}
			}
		}
		
		private static function get_cached() {
			$cacheFile = validCache( self::$request->full );

			if ( $cacheFile && strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) != 'POST' && OUTPUT_CACHE_ENABLED ) {
				$cf = pathinfo( $cacheFile );

				if ( ob_get_level() == 1 && implode( '.', self::$request->extensions ) == 'xml.gz' ) {
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
				
				return true;
			}
			
			return false;
		}
		
		private static function run_action() {
			// Work out what method to call and what params to pass to it
			// Determine if the desired method exists, fallback on index and if that doesn't exist, give up

			if ( method_exists( self::$request->controller, self::$request->method ) ) {
				$strstr = stristr( self::$request->fixed, self::$request->method . '/' );

				if ( $strstr ) {
					$params = str_replace( '^' . self::$request->method . '/', '', '^' . $strstr );
				}

				if ( strpos( self::$request->path . '$', self::$request->method . '$' ) !== false ) {
					self::$request->path = str_replace( self::$request->method . '$', '', self::$request->path . '$' );
				}
			}
			elseif ( method_exists( self::$request->controller, 'index' ) ) {
				self::$request->method = 'index';
				$params = trim( str_replace( '^' . self::$request->controller_filename . '/', '', '^' . self::$request->actual ), '/^' );
			}

			// Remove the format from the end of the paramaters
			if ( !empty( $params ) ) {
				self::$request->params = explode( '/', trim( str_replace( '.' . self::$request->format . '$', '', $params . '$' ), '$' ) );
			}

			// Instantiate the controller
			if ( class_exists( self::$request->controller ) ) {
				$controller = new self::$request->controller;

				if ( !$controller->cancel_request ) {
					if ( method_exists( $controller, self::$request->method ) ) {
						// Build the parameters to pass to the method
						$params = array();

						if ( isset( self::$request->params ) ) {
							$params = self::$request->params;
						}

						// If the final parameter is index, pop it off parameters (we don't need it)
						if ( $params[ count( $params ) - 1 ] == 'index' && self::$request->actual != self::$request->fixed ) {
							array_pop( $params );
						}

						// Call the method
						//timeme(function() use ($controller, $EddyFC, $params) {
							call_user_func_array( array( $controller, self::$request->method ), $params );
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
				
				self::$controller = $controller;
			}
		}
		
		private static function render_view() {
			// TODO: Need a response class associated with each format - this is too rigid

			switch ( self::$request->format ) {
				case 'xml':
					header( 'Content-Type: text/xml; charset=UTF-8' );

					$data = self::$controller->data;

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
					EddyView::$skin = self::$controller->skin;
					EddyView::$view = self::$controller->view;
					EddyView::$data = extract( self::$controller->data );
					
					if ( self::$controller->skin ) {
						// Load the skin (views will be loaded inside that)
						EddyView::load_skin();
					}
					elseif ( self::$controller->view ) {
						// Just load a view
						EddyView::load();
					}
			}

			// ### CACHING - CACHE THE CURRENT RESOURCE ###
			// Write the buffered output to file if we are allowed to
			if ( self::$controller->cacheable ) {
				$cache_file = ( self::$controller->cache_file ) ? self::$controller->cache_file . '.cache' : md5( self::$request->full ) . '.cache';
				$filename = realpath( './app/cache' ) . '/' . $cache_file;
				$filedata = ob_get_contents();

				switch ( strtolower( self::$controller->cache_compress ) ) {
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

			// Content-Disposition - relies on output buffering with ob_gzhandler
			switch ( implode( '.', self::$request->extensions ) ) {
				case 'xml.gz':
					$cd_filename = str_replace( '/', '_', self::$request->actual );

					header( 'Content-Type: application/x-gzip' );
					header( 'Content-Disposition: attachment;filename="' . $cd_filename . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					break;
			}
		}
	}
?>