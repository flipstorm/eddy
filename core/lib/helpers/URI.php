<?php
	namespace Helpers;
	
	class URI {
		/**
		 * Amend the query string
		 * @param array $params Array of $_GET params you want to adjust and their new values
		 * @param boolean[optional] $toggle Force to switch item on or off. Default: false
		 * @param boolean[optional] $clearCurrent Use existing query string or start from scratch. Default: false
		 * @return mixed New query string or false
		 */
		public static function amend_qs( $params, $toggle = false, $clearCurrent = false ) {
			if ( !$clearCurrent && $_SERVER[ 'QUERY_STRING' ] != '' ) {
				$newQueryString = String::explode_with_keys( '&', $_SERVER[ 'QUERY_STRING' ] );
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
				$qs = String::implode_with_keys( '&', $newQueryString );

				if ( $qs ) {
					return '?' . $qs;
				}
			}

			return false;
		}

		/**
		 * Make a string URL friendly
		 */
		public static function urlize( $str ) {
			// Transliterate UTF-8 characters to ASCII equivalents
			$str = iconv( 'UTF-8', 'ASCII//TRANSLIT', $str );
			return trim( preg_replace( array( '/\'/', '/[^a-z0-9-]/i', '/-{2,}/' ), array( '', '-', '-' ), html_entity_decode( $str, ENT_QUOTES ) ), '-' );
		}
		
		public static function subdomain( $domain = null ) {
			if ( strpos( $_SERVER[ 'HTTP_HOST' ], $domain ) !== false ) {
				$subdomain = preg_replace( '/^(?:([^\.]+)\.)?' . $domain . '$/', '\1', $_SERVER[ 'HTTP_HOST' ] );
			}
			
			return $subdomain;
		}
	}