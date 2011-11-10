<?php
	final class Eddy {
		private static $path_to_core;
		private static $path_to_app;
		
		public static $public_folder = 'public';

		public static $routes;
		public static $request;
		public static $controller;
		
		public static function init( $path ) {
			self::app( $path );
			
			self::set_paths();
			
			self::setup();
			
			self::config();
			
			if ( DEBUG ) {
				error_reporting( E_ALL ^ E_NOTICE );
				ob_start( 'ob_gzhandler' );
			}

			session_start();

			require_once 'functions.php';
			
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
		
		private static function set_paths() {
			if ( !self::$path_to_core ) {
				self::core();
			}

			define( 'APP_ROOT', self::$path_to_app );
			define( 'CORE_ROOT', self::$path_to_core );
			define( 'PUBLIC_FOLDER', self::$public_folder );

			$inc_path = array(
					APP_ROOT,
					CORE_ROOT,
					get_include_path()
				);

			set_include_path( implode( PATH_SEPARATOR, $inc_path ) );
		}
		
		public static function app( $path ) {			
			self::$path_to_app = realpath( $path );
		}
		
		public static function core( $path = null ) {
			if ( !$path ) {
				$path = __DIR__;
			}
			
			self::$path_to_core = realpath( $path );
		}
		
		private static function setup() {
			spl_autoload_register(function( $class ) {
				// XXX: There may be unexpected behaviour on case-insensitive filesystems

				// TODO: Start using namespaces for Helpers and Models - can then unify class loading
				// This will mean using the nasty \namespace\class syntax, but it will be more future-proof

				$isController = false;
				if ( strpos( '^' . $class, '^\\Controllers\\' ) !== false || strpos( '^' . $class, '^Controllers\\' ) !== false ) {
					// This is a controller
					$isController = true;
					$classFile = strtolower( str_ireplace( array( '^\\', '^Controllers\\', '\\', '_Controller$' ), array( '^', '', '/', '' ), '^' . $class . '$' ) ) . '.php';
					@include_once( 'controllers/' . $classFile );
				}
				elseif  ( strpos( '^' . $class, '^\\Models\\' ) !== false || strpos( '^' . $class, '^Models\\' ) !== false ) {
					$classFile =  str_ireplace( array( '^\\', '^Models\\', '\\' ), array( '^', '', '/' ), '^' . $class) ;

					$classPI = pathinfo( $classFile );
					$classPath = $classPI[ 'dirname' ];
					$singular = \Helpers\Inflector::singularize( $classPI[ 'filename' ] );
					$plural = \Helpers\Inflector::pluralize( $classPI[ 'filename' ] );

					// See if it's a model first
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
				}
				elseif ( strpos( '^' . $class, '^\\Helpers\\' ) !== false || strpos( '^' . $class, '^Helpers\\' ) !== false ) {
					// TODO: Helpers can go into /lib/helpers/ when namespacing works
					// This is a helper

					$classFile = str_ireplace( array( '^\\', '^Helpers\\', '\\' ), array( '^', '', '/' ), '^' . $class ) . '.php';
					@include_once( 'helpers/' . $classFile );
				}
				else {
					// This is any other class (including models and core classes)

					// XXX: This is going to be the cause of some possible naming collisions... Models ought to be namespaced

					$singular = \Helpers\Inflector::singularize( $class );
					$plural = \Helpers\Inflector::pluralize( $class );

					// Old model code DEPRECATED
					if ( file_exists( APP_ROOT . '/models/' . $class . '.php' ) ) {
						include_once( 'models/' . $class . '.php' );
					}
					elseif ( file_exists( APP_ROOT . '/models/' . $plural . '.php' ) ) {
						include_once( 'models/' . $plural . '.php' );

						$is_plural = true;
					}
					elseif ( file_exists( APP_ROOT . '/models/' . $singular . '.php' ) ) {
						include_once( 'models/' . $singular . '.php' );
					}

					// Otherwise, check for a standard lib or extra
					else {
						// TODO: Allow for namespaces here too!
						$classFile = str_replace( '_', '/', $class ) . '.php';
						$pluralFile = str_replace( '_', '/', $plural ) . '.php';
						$singularFile = str_replace( '_', '/', $singular ) . '.php';

						if ( file_exists( APP_ROOT . '/lib/' . $pluralFile ) || file_exists( CORE_ROOT . '/lib/' . $pluralFile ) ) {
							include_once( 'lib/' . $pluralFile );

							$is_plural = true;
						}
						elseif ( file_exists( APP_ROOT . '/lib/' . $singularFile ) || file_exists( CORE_ROOT . '/lib/' . $singularFile ) ) {
							include_once( 'lib/' . $singularFile );
						}
						// XXX: Consider removing extras... just have it as a repository?
						elseif ( file_exists( CORE_ROOT . '/extras/' . $classFile ) ) {
							include_once( 'extras/' . $classFile );
						}
					}
				}

				// Create a dynamic subclass for plural/singular class names
				if ( $class == $singular && $is_plural ) {
					eval( 'class ' . $singular . ' extends ' . $plural . ' {}' );
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

			register_shutdown_function(function(){
				if ( DEBUG ) {
					@FB::table( count( EddyDB::$queries ) . ' Queries', array_merge( array( array( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );

					FB::info( Eddy::$request, 'Eddy::$request' );
					FB::info( Eddy::$controller, 'Eddy::$controller' );
					FB::info( $_SERVER, '$_SERVER' );
					FB::info( $_SESSION, '$_SESSION' );
					FB::info( $_GET, '$_GET' );
					FB::info( $_POST, '$_POST' );
					//FB::info( 'Page took ' . ( microtime(true) - $EddyFC[ 'start' ] ) . 's to prepare' );
				}

				if ( ob_get_level() > 0 ) {
					ob_end_flush();
				}
			});

			set_exception_handler(function( Exception $e ) {
				echo '<p>There was an Exception. See FireBug</p>';

				FB::error($e);
			});
		}
		
		private static function config() {
			require_once 'config.php';

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
				$strstr = explode("/", self::$request->fixed);
				$strstr = array_reverse($strstr);
				$strstr = implode("/", $strstr);
				$strstr = stristr( $strstr, '/'.self::$request->method  , true );
				$strstr = explode("/", $strstr);
				$strstr = array_reverse($strstr);
				$params = implode("/", $strstr);

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