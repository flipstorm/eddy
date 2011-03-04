<?php
	class URI_Helper {
		/**
		 * Amend the query string
		 * @param array $params Array of $_GET params you want to adjust and their new values
		 * @param boolean[optional] $toggle Force to switch item on or off. Default: false
		 * @param boolean[optional] $clearCurrent Use existing query string or start from scratch. Default: false
		 * @return mixed New query string or false
		 */
		public static function amend_qs( $params, $toggle = false, $clearCurrent = false ) {
			if ( !$clearCurrent && $_SERVER[ 'QUERY_STRING' ] != '' ) {
				$newQueryString = String_Helper::explode_with_keys( '&', $_SERVER[ 'QUERY_STRING' ] );
			}

			if ( is_array( $params ) ) {
				foreach ( $params as $key => $value ) {
					if ( $value == $newQueryString[ $key ] && $toggle ) {
						unset ( $newQueryString[ $key ] );
					}
					else {
						$newQueryString[ $key ] = $value;
					}
				}
			}

			if ( is_array( $newQueryString ) ) {
				$qs = String_Helper::implode_with_keys( '&', $newQueryString );

				if ( $qs ) {
					return '?' . $qs;
				}
			}

			return false;
		}

		public static function get_current( $remove_qs = true ) {
			if ( isset( $_SERVER[ 'REDIRECT_URL' ] ) ) {
				$qs = ( $_SERVER[ 'QUERY_STRING' ] ) ? '?' . $_SERVER[ 'QUERY_STRING' ] : '';
				$requestURI = $_SERVER[ 'REDIRECT_URL' ] . $qs;
			}
			else {
				$requestURI = $_SERVER[ 'REQUEST_URI' ];
			}

			$request[ 'actual' ] = str_replace( '^' . str_replace( 'index.php', '', $_SERVER[ 'PHP_SELF' ] ), '', '^' . $requestURI );

			if ( $remove_qs && strpos( $request[ 'actual' ], '?' ) !== false ) {
				$request[ 'actual' ] = str_replace( '?' . $_SERVER[ 'QUERY_STRING' ], '', $request[ 'actual' ] );
			}

			$request_rev = strrev( $request[ 'actual' ] );

			$request[ 'fixed' ] = $request[ 'actual' ];

			if ( !$request[ 'actual' ] || $request_rev{0} == '/' ) {
				$request[ 'fixed' ] .= 'index';
			}

			return $request;
		}

		public static function handle_request( $url = null ) {
			$request = self::get_current();
			$return[ 'request' ] = $request;

			// Take the URL, and do the usual Controller calcs
			$path = pathinfo( $request[ 'fixed' ] );
			$return[ 'requestmethod' ] = $path[ 'filename' ];
			$return[ 'requestformat' ] = strtolower( $path[ 'extension' ] );
			$extensions[] = strtolower( $path[ 'extension' ] );

			// In case pathinfo() extension hasn't captured a multiple extension
			while ( strpos( $return[ 'requestmethod' ], '.' ) !== false ) {
				$rm = pathinfo( $return[ 'requestmethod' ] );
				$extensions[] = strtolower( $rm[ 'extension' ] );
				$return[ 'requestmethod' ] = $rm[ 'filename' ];
				$return[ 'requestformat' ] = strtolower( $rm[ 'extension' ] );
			}

			$return[ 'requestextensions' ] = array_reverse( $extensions );
			$return[ 'requestpath' ] = trim( $path[ 'dirname' ], '.' );

			if ( !$return[ 'requestpath' ] ) {
				$return[ 'requestpath' ] = 'default';
			}

			// Calculate the controller class naming convention
			$url = urldecode( $return[ 'requestpath' ] );

			// Clean up the request and cycle through controllers until we find one
			$controllerPath = explode( '/', preg_replace( array( '/\s/', '/[^a-z0-9\\/_\\-\\.]+/i' ), array( '', '' ), $url ) );

			$controllerNameCleaner = function( $path ){
				return preg_replace( '/[^a-z0-9_\\/]+/i', '_', str_replace( ' ', '_', ucwords( implode( ' ', $path ) ) ) );
			};

			$controllerName = $controllerNameCleaner( $controllerPath );

			while ( !class_exists( $controllerName . '_Controller' ) ) {
				// Cycle up until we find a class that does exist
				if ( count( $controllerPath ) > 0 ) {
					$return[ 'requestmethod' ] = strtolower( array_pop( $controllerPath ) );

					$upperLevelControllerName = $controllerNameCleaner( $controllerPath );
				}
				else {
					$upperLevelControllerName = 'Default';
				}

				if ( isset( $upperLevelControllerName ) ) {
					$controllerName = $upperLevelControllerName;
				}
			}

			// Finish controller naming
			$return[ 'controllerfilename' ] = strtolower( $controllerName );
			$return[ 'requestcontroller' ] = str_replace( '/', '_', $controllerName ) . '_Controller';

			return $return;
		}

		/**
		 * Make a string URL friendly
		 */
		public static function urlize( $str ) {
			return trim( preg_replace( array( '/\'/', '/[^a-z0-9-]/i', '/-{2,}/' ), array( '', '-', '-' ), html_entity_decode( $str, ENT_QUOTES ) ), '-' );
		}
	}