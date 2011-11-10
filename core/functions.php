<?php
	function buffer_include( $filepath, $vars = array() ) {
		ob_start();
		
		extract( $vars );
		include( $filepath );

		return ob_get_clean();
	}
	
	function get_object_public_vars( $obj ) {
		$vars = get_object_vars( $obj );
		
		// Remove vars that begin with '_', although technically public, they're treated as private for the purposes of this function
		// XXX: This isn't very intuitive. Is there a better way to do this?
		foreach ( $vars as $key => $val ) {
			if ( $key{0} !== '_' ) {
				$cleanVars[ $key ] = $val;
			}
		}
		
		return $cleanVars;
	}
	
	function get_namespace( &$obj ) {
		$class = explode( '\\', get_class( $obj ) );
		array_pop( $class );

		return implode( '\\', $class );
	}

	// Move into Users model
	/**
	 * Creates an unguessable session-based key that must be present and empty to qualify
	 */
	function realUser() {
		// Use a word that bots might try to find in field names - try to trick them into filling this field in!
		$random_trigger_words = array( 'name', 'email', 'username', 'login', 'address' );
		$rtw_key = array_rand( $random_trigger_words );
		$trigger_word = $random_trigger_words[ $rtw_key ];

		// Randomly place this trigger word into the key
		if ( !isset( $_SESSION[ 'nobots' ] ) ) {
			$random_string = md5( mt_rand() );

			$start = mt_rand( 1, strlen( $random_string ) - 1 );

			$parts[] = substr( $random_string, 0, $start + 1 );
			$parts[] = substr( $random_string, $start );
			$key = implode( $trigger_word, $parts );

			$_SESSION[ 'nobots' ] = $key;
		}
		else {
			$key = $_SESSION[ 'nobots' ];
		}

		if ( array_key_exists( $key, $_POST ) && empty( $_POST[ $key ] ) ) {
			return true;
		}

		return false;
	}

	function timeme( $function, $name = '' ){
		$start = microtime(true);
		$return = $function();
		FB::info(microtime(true) - $start, $name);

		return $return;
	}

	function validCache( $request ) {
		// Only check automated caches
		$filename = realpath( APP_ROOT . '/cache' ) . '/' . md5( $request ) . '.cache';

		if ( file_exists( $filename . '.gz' ) ) {
			return $filename . '.gz';
		}
		elseif ( file_exists( $filename ) ) {
			return $filename;
		}

		return false;
	}

	// Nice but unnecessary?
	/**
	 * Reproduce a PHP item any number of times
	 * @param mixed $item The item to copy
	 * @param int $count The number of times to copy it
	 * @return array Array of copies of the original item
	 */
	function x_of( $item, $count ){
		$items = array();
	
		for ( $i = 1; $i <= $count; $i++ ){
			$items[] = is_object( $item ) ? clone $item : $item;
		}

		return $items;
	}