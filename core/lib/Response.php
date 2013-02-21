<?php
	class Response extends EddyResponse {
		public static function xml( $data ) {
			header( 'Content-Type: text/xml; charset=UTF-8' );

			if ( empty( $data[ 'xml' ] ) ) {
				header( 'HTTP/1.1 404 Not Found', false );
				exit;
			}

			echo $data[ 'xml' ];
		}
		
		public static function json( $data ) {
			header( 'Content-Type: application/json; charset=UTF-8' );

			if ( empty( $data[ 'json' ] ) ) {
				header( 'HTTP/1.1 204 No Content', false );
				exit;
			}

			$jsonResponse = json_encode( $data[ 'json' ] );

			// JSONP
			if ( isset( $_REQUEST[ 'callback' ] ) ) {
				// Switch content type to application/javascript
				header( 'Content-Type: application/javascript; charset=UTF-8' );
				$jsonResponse = $_REQUEST[ 'callback' ] . '(' . $jsonResponse . ');';
			}

			echo $jsonResponse;
		}
	}