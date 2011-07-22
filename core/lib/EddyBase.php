<?php
	abstract class EddyBase {
		public function __get( $name ) {
			// Assume the method collects the data we want!
			if ( method_exists( $this, '_get_' . $name ) ) {
				return call_user_func( array( $this, '_get_' . $name ) );
			}
		}
		
		public function __set( $name, $value ) {
			if ( method_exists( $this, '_set_' . $name ) ) {
				return call_user_func( array( $this, '_set_' . $name ), $value );
			}
		}

		public function __call( $name, $arguments ) {
			if ( method_exists( $this, '_call_' . $name ) ) {
				return call_user_func_array( array( $this, '_call_' . $name ), $arguments );
			}
			else {
				throw new Exception( "You tried to call an object method that doesn't exist" );
			}
		}
		
		public static function __callStatic( $name, $arguments ) {
			$class = get_called_class();
			
			if ( method_exists( $class, '_call_' . $name ) ) {
				return call_user_func_array( array( $class, '_call_' . $name ), $arguments );
			}
			else {
				throw new Exception( "You tried to call a static method that doesn't exist" );
			}
		}
	}