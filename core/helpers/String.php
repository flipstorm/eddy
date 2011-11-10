<?php
	namespace Helpers;
	
	class String {
		public static function explode_with_keys( $separator, $string ) {
			$array = explode( $separator, $string );

			if ( is_array( $array ) ) {
				foreach ( $array as $value ) {
					$row = explode( '=', $value );
					$output[ $row[0] ] = $row[1];
				}

				return $output;
			}
			else {
				return null;
			}
		}

		// Recursive implode function
		public static function imploder( $glue, $pieces ) {
			foreach ( $pieces as $piece ) {
				if ( is_array( $piece ) ) {
					$retVal[] = imploder( $glue, $piece );
				}
				else {
					$retVal[] = $piece;
				}
			}

			return implode( $glue, $retVal );
		}

		public static function implode_with_keys( $glue, $array ) {
			if ( is_array( $array ) ) {
				foreach ( $array as $key => $item ) {
					if ( is_array( $item ) ) {
						$item = $item[0];
					}

					if ( $item != '' ) {
						$output[] = $key . '=' . $item;
					}
				}

				if ( is_array( $output ) ) {
					return implode( $glue, $output );
				}
			}

			return false;
		}

		public static function truncate( $phrase, $max_words, $more_text = '...' ) {
			$phrase_array = explode( ' ', $phrase );

			if ( count( $phrase_array ) > $max_words && $max_words > 0 ) {
				$phrase = implode( ' ', array_slice( $phrase_array, 0, $max_words ) ) . $more_text;
			}

			return $phrase;
		}
	}