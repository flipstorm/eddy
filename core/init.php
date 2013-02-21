<?php
	final class Eddy {
		private static $path_to_core;
		private static $path_to_app;
		private static $path_to_public;

		public static $public_folder = 'public';

		public static $routes;
		public static $request;
		public static $controller;
		
		// Convenience for global data storage
		public static $data;

		public static function init( $app_path ) {
			self::set_paths( $app_path );

			self::setup();

			self::config();

			if ( DEBUG ) {
				error_reporting( E_ALL ^ E_NOTICE );
				ob_start( 'ob_gzhandler' );
			}

			session_start();

			require_once 'functions.php';
			
			// All of our setup is ready, config is loaded, session ready and core functions parsed
			if ( function_exists( 'application_start' ) ) {
				call_user_func( 'application_start' );
			}

			FB::setEnabled( DEBUG );
			Routes::add( self::$routes );

			self::$request = new EddyRequest();

			if ( !self::get_cached() ) {
				$start = microtime(true);
				self::run_action();
				FB::info( microtime(true) - $start, 'Run Action' );

				$start = microtime(true);
				self::render_view();
				FB::info( microtime(true) - $start, 'Render View' );
			}
		}

		private static function set_paths( $app_path ) {
			self::path_to_app( $app_path );

			if ( !self::$path_to_core ) {
				self::path_to_core();
			}

			if ( !self::$path_to_public ) {
				self::path_to_public();
			}

			define( 'APP_ROOT', self::$path_to_app );
			define( 'CORE_ROOT', self::$path_to_core );
			define( 'PUBLIC_ROOT', self::$path_to_public );
			define( 'PUBLIC_FOLDER', self::$public_folder );

			$inc_path = array(
					APP_ROOT,
					CORE_ROOT,
					get_include_path()
				);

			set_include_path( implode( PATH_SEPARATOR, $inc_path ) );
		}

		private static function path_to_app( $path ) {
			self::$path_to_app = realpath( $path );
		}

		private static function path_to_core( $path = null ) {
			if ( !$path ) {
				$path = __DIR__;
			}

			self::$path_to_core = realpath( $path );
		}

		private static function path_to_public() {
			self::$path_to_public = realpath( self::$path_to_app . '/../' . self::$public_folder );
		}

		private static function setup() {
			// Default autoload
			spl_autoload_register(function( $class ) {
				// XXX: There may be unexpected behaviour on case-insensitive filesystems

				if ( strpos( '^' . $class, '^\\Controllers\\' ) !== false || strpos( '^' . $class, '^Controllers\\' ) !== false ) {
					// This is a controller
					$classFile = strtolower( str_ireplace( array( '^\\', '^Controllers\\', '\\', '_Controller$', '$' ), array( '^', '', '/', '', '' ), '^' . $class . '$' ) ) . '.php';
					@include_once( 'controllers/' . $classFile );
				}
				elseif  ( strpos( '^' . $class, '^\\Models\\' ) !== false || strpos( '^' . $class, '^Models\\' ) !== false ) {
					// This is a model
					$classFile =  str_ireplace( array( '^\\', '^Models\\', '\\' ), array( '^', '', '/' ), '^' . $class) ;

					$classPI = pathinfo( $classFile );
					$classPath = $classPI[ 'dirname' ];
					$singular = \Helpers\Inflector::singularize( $classPI[ 'filename' ] );
					$plural = \Helpers\Inflector::pluralize( $classPI[ 'filename' ] );
					
					// Need to get rid of these file_exists() calls to work better with APC

					if ( file_exists( APP_ROOT . '/models/' . $classFile .'.php' ) ) {
						include_once( 'models/' . $classFile .'.php');
					}
					elseif ( file_exists( APP_ROOT . '/models/' . $classPath . '/' . $plural .'.php' ) ) {
						include_once( 'models/' . $classPath . '/' . $plural .'.php');

						$is_plural = true;
					}
					elseif ( file_exists( APP_ROOT . '/models/' . $classPath . '/' . $singular .'.php' ) ) {
						include_once( 'models/' . $classPath . '/' . $singular .'.php' );
					}

					// Create a dynamic subclass for plural/singular class names
					if ( $class == $singular && $is_plural ) {
						eval( 'class ' . $singular . ' extends ' . $plural . ' {}' );
					}
				}
				else {
					$classFile = str_replace( '\\', '/', $class );
					$classPI = pathinfo( $classFile );
					$classPath = strtolower( $classPI[ 'dirname' ] );

					@include_once( 'lib/' . $classPath . '/' . $classPI[ 'filename' ] . '.php' );
				}

				// These are non-crucial classes that are used in the core, but not necessary
				$ignoreClasses = array( 'FB', 'FirePHP' );

				if ( !class_exists( $class, false ) && in_array( $class, $ignoreClasses ) ) {
					// Fudge it
					$prototype = 'class ' . $class . ' {
							public function __get($var){}
							public function __set($var, $val){}
							public function __call($method, $params){}
							public static function __callStatic($method, $params){}
						}';

					/* TODO: Test how to check for eval() (i.e. if disabled/safe-mode etc)
					if ( !function_exists( 'eval' ) ) {
						$code = '<?php ' . $prototype;
						include( 'data://text/plain;base64,' . base64_encode($code) );
					}
					else {
						eval( $prototype );
					}
					*/

					eval( $prototype );
				}
			});

			// Shutdown
			register_shutdown_function(function(){
				if ( DEBUG ) {
					if ( EddyDB::$debugActualQueryCount > EddyDB::$debugQueryCount ) {
						$queries_label = (int) EddyDB::$debugQueryCount . ' of ' . EddyDB::$debugActualQueryCount . ' queries shown.';
					}
					else {
						$queries_label = (int) EddyDB::$debugQueryCount . ' queries.';
					}
					
					if ( EddyDB::$debugActualQueryCount > 0 ) {
						$queries_label .= ' Total query time: ' . number_format( EddyDB::$totalQueryTime, 3 ) . 's';
						FB::table( $queries_label, array_merge( array( array( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );
					}
					else {
						FB::info( $queries_label );
					}

					FB::info( Eddy::$request, 'Eddy::$request' );
					
					// Empty the data array before we do this... can get pretty hairy!
					if (Eddy::$controller instanceof \EddyController) {
						Eddy::$controller->empty_data();
						FB::info( Eddy::$controller, 'Eddy::$controller' );
					}
					
					FB::info( $_SERVER, '$_SERVER' );
					
					if ( !empty( $_SESSION ) ) {
						FB::info( $_SESSION, '$_SESSION' );
					}
					
					if ( !empty( $_GET ) ) {
						FB::info( $_GET, '$_GET' );
					}
					
					if ( !empty( $_POST ) ) {
						FB::info( $_POST, '$_POST' );
					}
				}

				if ( ob_get_level() > 0 ) {
					ob_end_flush();
				}
			});

//			set_error_handler(function( $errno, $errstr, $errfile, $errline ) {
//				throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
//			});

			// Default exception handler
			set_exception_handler(function( Exception $e ) {
				echo '<p>There was an Exception. See FireBug</p>';

				FB::error( $e );
			});

		}

		private static function config() {
			require_once 'config.php';

			foreach ( $config as $const => $value ) {
				if ( !defined( $const ) ) {
					define( $const, $value );
				}
			}
			
			@include_once 'routes.php';
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
				// XXX: All of this just to get the params off the end of the request! Is there a faster way?
				//$params = timeme(function(){
					//$strstr = explode( '/', self::$request->fixed );
					$strstr = explode( '/', Eddy::$request->fixed );
					$strstr = array_reverse( $strstr );
					$strstr = implode( '/', $strstr );
					// $strstr = stristr( $strstr, '/' . self::$request->method, true );
					$strstr = stristr( $strstr, '/' . Eddy::$request->method, true );
					$strstr = explode( '/', $strstr );
					$strstr = array_reverse( $strstr );
					$params = implode( '/', $strstr );
				//	return $params;
				//}, 'Get params');

				if ( strpos( self::$request->path . '$', self::$request->method . '$' ) !== false ) {
					self::$request->path = str_replace( self::$request->method . '$', '', self::$request->path . '$' );
				}
			}
			elseif ( method_exists( self::$request->controller, 'index' ) && defined( 'NO_404' ) && NO_404 ) {
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

				// If redirect happens in the constructor on an AJAX request, we don't want to run the action
				if ( !$controller->cancel_request ) {
					
					$http_method = strtoupper( $_SERVER[ 'REQUEST_METHOD' ] );
					
					// Fetch method based on HTTP request method
					if ( method_exists( $controller, $http_method . '_' . self::$request->method . '_action' ) ) {
						// [get|post ...]_[self::$request->method]_action
						self::$request->method_full = $http_method . '_' . self::$request->method . '_action';
					}
					else if ( method_exists( $controller, $http_method . '_' . self::$request->method ) ) {
						// [get|post ...]_[self::$request->method]
						self::$request->method_full = $http_method . '_' . self::$request->method;
					}
					else if ( method_exists( $controller, self::$request->method . '_action' ) ) {
						// [self::$request->method]_action
						self::$request->method_full = self::$request->method . '_action';
					}
					else if ( method_exists( $controller, self::$request->method ) ) {
						// [self::$request->method]
						self::$request->method_full = self::$request->method;
					}

					if ( self::$request->method_full ) {
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
						call_user_func_array( array( $controller, self::$request->method_full ), $params );

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
			// Fire the controller prerender method
			self::$controller->prerender();
			
			$format = self::$request->format;
			$params = array( self::$controller->data, self::$controller->template, self::$controller->view );

			if ( method_exists( 'Response', $format ) ) {
				call_user_func_array( "Response::$format", $params );
			}
			else {
				call_user_func_array( 'Response::_default', $params );
			}

			// ### CACHING - CACHE THE CURRENT RESOURCE ###
			// Write the buffered output to file if we are allowed to
			if ( self::$controller->cacheable ) {
				$cache_file = ( self::$controller->cache_file ) ? self::$controller->cache_file . '.cache' : md5( self::$request->full ) . '.cache';
				$filename = APP_ROOT . '/cache/' . $cache_file;
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

			// Content-Disposition
			// TODO: Move this out into it's own separate library e.g. Downloads
			// This xml.gz implementation relies on output buffering with ob_gzhandler
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