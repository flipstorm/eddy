<?php
	class EddyRequest extends EddyBase {
		public $actual;
		public $controller_filename;
		public $controller;
		public $extensions;
		public $fixed;
		public $format;
		public $full;
		public $method;
		public $original;
		public $params;
		public $path;
		
		public function __construct( $uri = null ) {
			if ( $uri ) {
				$uri = self::fix_url( $uri );
			}
			else {
				$uri = self::get_current();
			}
			
			$path = pathinfo( $uri[ 'fixed' ] );
			
			$this->original = $uri[ 'original' ];
			$this->actual = $uri[ 'actual' ];
			$this->full = $uri[ 'full' ];
			$this->fixed = ( $path[ 'dirname' ] != '.' && $path[ 'dirname' ] ? $path[ 'dirname' ] . '/' : '' ) . ( $path[ 'filename' ] ? $path[ 'filename' ] : 'index' );

			// Take the URL, and do the usual Controller calcs
			$this->method = self::rm_cleaner( $path[ 'filename' ] );
			$this->format = strtolower( $path[ 'extension' ] );
			$extensions[] = strtolower( $path[ 'extension' ] );

			// In case pathinfo() extension hasn't captured a multiple extension
			while ( strpos( $this->method, '.' ) !== false ) {
				$rm = pathinfo( $this->method );
				$extensions[] = strtolower( $rm[ 'extension' ] );
				$this->method = self::rm_cleaner( $rm[ 'filename' ] );
				$this->format = strtolower( $rm[ 'extension' ] );
			}

			$this->extensions = array_reverse( $extensions );
			$this->path = trim( $path[ 'dirname' ], '.' );

			if ( !$this->path ) {
				$this->path = 'default';
			}

			// Calculate the controller class naming convention
			$url = urldecode( $this->path );

			// Clean up the request and cycle through controllers until we find one
			$controllerPath = explode( '/', preg_replace( '/[^a-z0-9\\/_\\-\\.]+/i', '', $url ) );

			$controllerName = '\\Controllers\\' . self::cn_cleaner( $controllerPath );

			while ( !class_exists( $controllerName . '_Controller' ) ) {
				// Cycle up until we find a class that does exist

				// TODO: Consider looking for a Default controller at every level...
				if ( count( $controllerPath ) > 0 ) {
					$this->method = self::rm_cleaner( array_pop( $controllerPath ) );

					$controllerName = '\\Controllers\\' . self::cn_cleaner( $controllerPath );
				}
				else {
					$controllerName = '\\Controllers\\Default';
				}
			}

			// Finish controller naming
			$this->controller_filename = strtolower( str_replace( array( '\\Controllers\\', '\\' ), array( '', '/' ), $controllerName ) );

			// TODO: Make this _Controller extension optional - it's only there for 'unsafe' controller names - i.e. reserved words in PHP
			$this->controller = $controllerName . '_Controller';
		}

		public function is_ajax() {
			return ( strtoupper( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'XMLHTTPREQUEST' );
		}
		
		public static function get_current() {
			if ( isset( $_SERVER[ 'REDIRECT_URL' ] ) ) {
				$qs = ( $_SERVER[ 'QUERY_STRING' ] ) ? '?' . $_SERVER[ 'QUERY_STRING' ] : '';
				$requestURI = $_SERVER[ 'REDIRECT_URL' ] . $qs;
			}
			else {
				$requestURI = $_SERVER[ 'REQUEST_URI' ];
			}

			return self::fix_url( $requestURI );
		}

		/**
		 * This creates multiple versions of the given URL for later use. 
		 */
		private static function fix_url( $url ) {
			$request[ 'original' ] = str_replace( '^' . str_replace( 'index.php', '', $_SERVER[ 'PHP_SELF' ] ), '', '^' . $url );
			
			// Need to remove suffixes here
			$original_sans_qs = str_replace( '?' . $_SERVER[ 'QUERY_STRING' ], '', $request[ 'original' ] );
			$ext = strstr( $original_sans_qs, '.' );
			$original_sans_qs = str_replace( $ext, '', $original_sans_qs );
			$original_avec_qs = $original_sans_qs . '?' . $_SERVER[ 'QUERY_STRING' ];

			// TODO: Route through a single call. Waiting for Routes to fully support query strings, until then we have to do this twice (not ideal)
			$request[ 'full' ] = Routes::route( $original_avec_qs );
			$request[ 'actual' ] = Routes::route( $original_sans_qs ) . $ext;

			$request_rev = strrev( $request[ 'actual' ] );

			$request[ 'fixed' ] = $request[ 'actual' ];

			if ( !$request[ 'actual' ] || $request_rev[0] == '/' ) {
				$request[ 'fixed' ] .= 'index' . $ext;
			}
			else {
				$request[ 'fixed' ] .= $ext;
			}

			return $request;
		}
		
		/**
		 * Request Method cleaner - cleans up the given string to make a valid method name
		 * @param str $rm Request method name
		 * @return str
		 */
		private static function rm_cleaner( $rm ) {
			//return preg_replace( array( '/[^a-z0-9_]+/i', '/__/', array( '_', '_' ), strtolower( $rm ) );
			return preg_replace( '/[^a-z0-9_]+/i', '_', strtolower( $rm ) );
		}
		
		/**
		 * Controller Name cleaner - cleans up the controller name from a given path array
		 * @param array $path_array The path for the controller as an array
		 * @return str
		 */
		private static function cn_cleaner( $path_array ) {
			//return preg_replace( array( '/[^a-z0-9_\\\\]+/i', '__'), array( '_', '_' ), str_replace( ' ', '\\', ucwords( implode( ' ', $path_array ) ) ) );
			return preg_replace( '/[^a-z0-9_\\\\]+/i', '_', str_replace( ' ', '\\', ucwords( implode( ' ', $path_array ) ) ) );
		}
	}
