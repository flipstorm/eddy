<?php
	/**
	 * Attempts to load the requested class from a variety of sources
	 * @param string $class Class name
	 */
	function __autoload ( $class ) {
		// XXX: There may be unexpected behaviour on case-insensitive filesystems


		if ( strpos( $class . '$', '_Controller$' ) !== false ) {
			// This is a controller
			$classFile = strtolower( str_ireplace( '_Controller$', '', $class . '$' ) ) . '.php';

			if ( file_exists( APP_ROOT . '/app/controllers/' . $classFile ) ) {
				include_once 'app/controllers/' . $classFile;
			}

			$isController = true;
		}
		elseif ( strpos( $class . '$', '_Helper$' ) !== false ) {
			// This is a helper
			$classFile = str_ireplace( '_Helper$', '', $class . '$' ) . '.php';

			if ( file_exists( APP_ROOT . '/app/helpers/' . $classFile ) ) {
				include_once 'app/helpers/' . $classFile;
			}
			elseif ( file_exists( CORE_ROOT . '/helpers/' . $classFile ) ) {
				include_once 'helpers/' . $classFile;
			}
		}
		else {
			// This is any other class (including models and core classes)

			// See if it's a model first
			if ( file_exists( APP_ROOT . '/app/models/' . $class . '.php' ) ) {
				include_once 'app/models/' . $class . '.php';
			}
			else {
				$classFile = str_replace( '_', '/', $class ) . '.php';

				// Override core classes simply by creating a class with the same filename in /app/lib
				if ( file_exists( APP_ROOT . '/app/lib/' . $classFile ) ) {
					include_once 'app/lib/' . $classFile;
				}
				elseif ( file_exists( CORE_ROOT . '/lib/' . $classFile ) ) {
					include_once 'lib/' . $classFile;
				}
				elseif ( file_exists( CORE_ROOT . '/extras/' . $classFile ) ) {
					include_once 'extras/' . $classFile;
				}
			}
		}

		// These are non-crucial classes that are used in the core, but not necessary
		$ignoreClasses = array( 'FB', 'FirePHP' );

		if ( !class_exists( $class, false ) ) {
			if ( in_array( $class, $ignoreClasses ) ) {
				// Fudge it
				$prototype = 'class ' . $class . ' {
						public function __get($var){}
						public function __set($var, $val){}
						public function __call($method, $params){}
						public static function __callStatic($method, $params){}
					}';

				/* TODO: Test how to check for eval() (i.e. if disabled/safe-mode etc)
				if ( !function_exists( 'eval' ) ) {
					$code = '<?php ' . $prototype;
					include( 'data://text/plain;base64,' . base64_encode($code) );
				}
				else {
					eval( $prototype );
				}
				*/

				eval( $prototype );
			}
			elseif ( DEBUG && !$isController ) {
				throw new Exception( "Couldn't load class: $class" );
			}
		}
	}

	// TODO: Some of these functions should be helper classes
	function exceptionHandler( Exception $e ) {
		echo '<h1>Don\'t you know how to Catch yet?</h1>';
		
		echo $e->getMessage() . '<h2>Stack Trace</h2>' ;
		FB::info($e->getTrace());
		
		foreach ( $e->getTrace() as $stack ) {
			if ( $stack[ 'class' ] ) {
				echo $stack[ 'class' ] . $stack[ 'type' ] . $stack[ 'function' ] . '(' . implode( ', ', $stack[ 'args' ] ) . ')<br />';
				echo 'Line ' . $stack[ 'line' ] . ' in ' . $stack[ 'file' ] . '<br /><br />';
			}
			else {
				echo 'Line ' . $stack[ 'line' ] . ' in ' . $stack[ 'file' ] . '<br /><br />';
			}
			
		}
	}

	
	function get_object_public_vars( $obj ) {
		$vars = get_object_vars( $obj );
		
		// Remove vars that begin with '_'
		foreach ( $vars as $key => $val ) {
			if ( $key{0} !== '_' ) {
				$cleanVars[ $key ] = $val;
			}
		}
		
		return $cleanVars;
	}


	// These should be part of a front controller class
	function include_partial( $path ) {
		global $EddyFC;

		if ( is_array( $EddyFC[ 'viewdata' ] ) ) {
			extract( $EddyFC[ 'viewdata' ] );
		}
		
		$partial_path = 'app/views/partials/' . $path . '.part.phtml';
		$partial_file = APP_ROOT . '/' . $partial_path;

		if ( file_exists( $partial_file ) ) {
			include ( $partial_path );
		}
		elseif ( DEBUG ) {
			echo '{Partial doesn\'t exist: ' . $partial_file . '}';
		}
	}
	
	function include_view() {
		global $EddyFC;

		if ( is_array( $EddyFC[ 'viewdata' ] ) ) {
			extract( $EddyFC[ 'viewdata' ] );
		}

		$view_path = 'app/views/' . $EddyFC[ 'view' ] . '.phtml';
		$view_file = APP_ROOT . '/' . $view_path;

		if ( file_exists( $view_file ) ) {
			include_once ( $view_path );
		}
		elseif ( DEBUG ) {
			echo '{View doesn\'t exist: ' . $view_file . '}';
		}
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
	function x_of( $item, $count ) {
		while ( count( $items ) < $count ) {
			if ( is_object( $item ) ) {
				$items[] = clone $item;
			}
			else {
				$items[] = $item;
			}
		}
		
		return $items;
	}