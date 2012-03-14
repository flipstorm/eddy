<?php
	abstract class EddyBase {
		public function __get( $name ) {
			if ( method_exists( $this, '_get_' . $name ) ) {
				return call_user_func( array( $this, '_get_' . $name ) );
			}
		}
		
		public function __set( $name, $value ) {
			if ( method_exists( $this, '_set_' . $name ) ) {
				call_user_func( array( $this, '_set_' . $name ), $value );
			}
		}
		
		public function __isset( $name ) {
			$value = $this->$name;
			
			return isset( $value );
		}
		
		public function __unset( $name ) {
			if ( method_exists( $this, '_unset_' . $name ) ) {
				call_user_func( array( $this, '_unset_' . $name ), $value );
			}
		}

		// XXX: When are we ever using these?
		public function __call( $name, $arguments ) {
			if ( method_exists( $this, '_call_' . $name ) ) {
				return call_user_func_array( array( $this, '_call_' . $name ), $arguments );
			}
			else {
				throw new Exception( "You tried to call an object method that doesn't exist" );
			}
		}
		
		/*
		public static function __callStatic( $name, $arguments ) {
			$class = get_called_class();
			
			if ( method_exists( $class, '_call_' . $name ) ) {
				return call_user_func_array( array( $class, '_call_' . $name ), $arguments );
			}
			else {
				throw new Exception( "You tried to call a static method that doesn't exist" );
			}
		}
		*/

		public static function __set_state( $array ) {
			$class = get_called_class();
			$obj = new $class;
			
			foreach ( $array as $field => $val ) {
				$obj->$field = $val;
			}
			
			return $obj;
		}
		
		protected static function getConstants() {
			$refl = new \ReflectionClass( get_called_class() );
			
			return $refl->getConstants();
		}
	}