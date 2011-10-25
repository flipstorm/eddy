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
			// TODO: if a $uri is specified, then work out the details for that request
			$uri = self::get_current();
			
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
			$this->controller = $controllerName . '_Controller';
		}
		
		public static function get_current() {
			if ( isset( $_SERVER[ 'REDIRECT_URL' ] ) ) {
				$qs = ( $_SERVER[ 'QUERY_STRING' ] ) ? '?' . $_SERVER[ 'QUERY_STRING' ] : '';
				$requestURI = $_SERVER[ 'REDIRECT_URL' ] . $qs;
			}
			else {
				$requestURI = $_SERVER[ 'REQUEST_URI' ];
			}

			// Waiting for Routes to support query strings
			$request[ 'original' ] = str_replace( '^' . str_replace( 'index.php', '', $_SERVER[ 'PHP_SELF' ] ), '', '^' . $requestURI );
			$request[ 'full' ] = Routes::route( $request[ 'original' ] );
			$request[ 'actual' ] = Routes::route( str_replace( '?' . $_SERVER[ 'QUERY_STRING' ], '', $request[ 'full' ] ) );

			$request_rev = strrev( $request[ 'actual' ] );

			$request[ 'fixed' ] = $request[ 'actual' ];

			if ( !$request[ 'actual' ] || $request_rev{0} == '/' ) {
				$request[ 'fixed' ] .= 'index';
			}

			return $request;
		}
		
		private static function rm_cleaner( $rm ) {
			return preg_replace( '/[^a-z0-9_]+/i', '_', strtolower( $rm ) );
		}
		
		private static function cn_cleaner( $path_array ) {
			return preg_replace( '/[^a-z0-9_\\\\]+/i', '_', str_replace( ' ', '\\', ucwords( implode( ' ', $path_array ) ) ) );
		}
	}
