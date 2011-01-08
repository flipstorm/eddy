<?php
	abstract class EddyBase {
		public function __get( $arg ) {
			// Assume the method collects the data we want!
			if ( method_exists( $this, '_get' . ucfirst( $arg ) ) ) {
				return call_user_func( array( $this, '_get' . ucfirst( $arg ) ) );
			}
		}
		
		public function __set( $arg, $value ) {
			if ( method_exists( $this, '_set' . ucfirst( $arg ) ) ) {
				return call_user_func( array( $this, '_set' . ucfirst( $arg ) ), $value );
			}
		}
	}