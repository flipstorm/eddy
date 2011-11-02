<?php
	/**
	 * Attempts to load the requested class from a variety of sources
	 * @param string $class Class name
	 */
	function __autoload ( $class ) {
		// XXX: There may be unexpected behaviour on case-insensitive filesystems
		
		// TODO: Start using namespaces for Helpers and Models - can then unify class loading
		// This will mean using the nasty \namespace\class syntax, but it will be more future-proof

		$isController = false;
		if ( strpos( '^' . $class, '^\\Controllers\\' ) !== false || strpos( '^' . $class, '^Controllers\\' ) !== false ) {
			// This is a controller
			$isController = true;
			$classFile = strtolower( str_ireplace( array( '^\\', '^Controllers\\', '\\', '_Controller$' ), array( '^', '', '/', '' ), '^' . $class . '$' ) ) . '.php';
			
			if ( file_exists( APP_ROOT . '/controllers/' . $classFile ) ) {
				include_once( 'controllers/' . $classFile );
			}
		}
		elseif  ( strpos( '^' . $class, '^\\Models\\' ) !== false || strpos( '^' . $class, '^Models\\' ) !== false ) {
			$classFile =  str_ireplace( array( '^\\', '^Models\\', '\\' ), array( '^', '', '/' ), '^' . $class) ;
			
			$singular = Inflector_Helper::singularize( $classFile );
			$plural = Inflector_Helper::pluralize( $classFile );
			
			// See if it's a model first
			if ( file_exists( APP_ROOT . '/models/' . $classFile .'.php' ) ) {
				include_once( 'models/' . $classFile .'.php');
			}
			elseif ( file_exists( APP_ROOT . '/models/' . $plural .'.php' ) ) {
				include_once( 'models/' . $plural .'.php');
				
				$is_plural = true;
			}
			elseif ( file_exists( APP_ROOT . '/models/' . $singular .'.php' ) ) {
				include_once( 'models/' . $singular .'.php' );
			} else {
				call_user_func(DYNAMIC_MODEL_CALLBACK, $classFile);
			}
		}
		elseif ( strpos( $class . '$', '_Helper$' ) !== false ) {
			// This is a helper
			$classFile = str_ireplace( '_Helper$', '', $class . '$' ) . '.php';

			if ( file_exists( APP_ROOT . '/helpers/' . $classFile ) || file_exists( CORE_ROOT . '/helpers/' . $classFile ) ) {
				include_once( 'helpers/' . $classFile );
			}
		}
		else {
			// This is any other class (including models and core classes)

			// XXX: This is going to be the cause of some possible naming collisions... Models ought to be namespaced
			
			$singular = Inflector_Helper::singularize( $class );
			$plural = Inflector_Helper::pluralize( $class );
			
			// See if it's a model first
			if ( file_exists( APP_ROOT . '/models/' . $class . '.php' ) ) {
				include_once( 'models/' . $class . '.php' );
			}
			elseif ( file_exists( APP_ROOT . '/models/' . $plural . '.php' ) ) {
				include_once( 'models/' . $plural . '.php' );
				
				$is_plural = true;
			}
			elseif ( file_exists( APP_ROOT . '/models/' . $singular . '.php' ) ) {
				include_once( 'models/' . $singular . '.php' );
			}
			
			// Otherwise, check for a standard lib or extra
			else {
				$classFile = str_replace( '_', '/', $class ) . '.php';
				$pluralFile = str_replace( '_', '/', $plural ) . '.php';
				$singularFile = str_replace( '_', '/', $singular ) . '.php';

				if ( file_exists( APP_ROOT . '/lib/' . $pluralFile ) || file_exists( CORE_ROOT . '/lib/' . $pluralFile ) ) {
					include_once( 'lib/' . $pluralFile );
					
					$is_plural = true;
				}
				elseif ( file_exists( APP_ROOT . '/lib/' . $singularFile ) || file_exists( CORE_ROOT . '/lib/' . $singularFile ) ) {
					include_once( 'lib/' . $singularFile );
				}
				// XXX: Consider removing extras... just have it as a repository?
				elseif ( file_exists( CORE_ROOT . '/extras/' . $classFile ) ) {
					include_once( 'extras/' . $classFile );
				}
			}
			
			// Create a dynamic subclass for plural/singular class names
			if ( $class == $singular && $is_plural ) {
				eval( 'class ' . $singular . ' extends ' . $plural . ' {}' );
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
	
	register_shutdown_function(function(){
		if ( DEBUG ) {
			@FB::table( count( EddyDB::$queries ) . ' Queries', array_merge( array( array( 'Query', 'Query Time (s)' ) ), EddyDB::$queries ) );

			FB::info( Eddy::$request, 'Eddy::$request' );
			FB::info( Eddy::$controller, 'Eddy::$controller' );
			FB::info( $_SERVER, '$_SERVER' );
			FB::info( $_SESSION, '$_SESSION' );
			FB::info( $_GET, '$_GET' );
			FB::info( $_POST, '$_POST' );
			//FB::info( 'Page took ' . ( microtime(true) - $EddyFC[ 'start' ] ) . 's to prepare' );
		}

		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
	});
	
	set_exception_handler(function( Exception $e ) {
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
	});
	
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