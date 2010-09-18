<?php
	/**
	 * Attempts to load the requested class from a variety of sources
	 * @param string $class Class name
	 */
	function __autoload ( $class ) {
		if ( strpos( $class, '_Controller' ) !== false ) {
			// This is a controller
			$class = strtolower( str_ireplace( '_Controller$', '', $class . '$' ) );
			$classFile = str_replace( '_', '/', $class );

			if ( file_exists( 'controllers/' . $classFile . '.php' ) ) {
				include_once( 'controllers/' . $classFile . '.php' );
			}
		}
		/*
		elseif ( strpos( $class, '_Model' ) !== false ) {
			// This is a model
			$class = strtolower( str_ireplace( '_Model$', '', $class . '$' ) );
			$classFile = str_replace( '_', '/', $class );

			if ( file_exists( 'models/' . $classFile . '.php' ) ) {
				include_once( 'models/' . $classFile . '.php' );
			}
		}
		else {
			$classFile = str_replace( '_', '/', $class );

			if ( file_exists( 'classes/' . $classFile . '.php' ) ) {
				include_once( 'classes/' . $classFile . '.php' );
			}
		}
		*/
		else {
			// This is a standard class
			$classFile = str_replace( '_', '/', $class );
	
			if ( file_exists( 'models/' . $classFile . '.php' ) ) {
				include_once( 'models/' . $classFile . '.php' );
			}
			elseif ( file_exists( 'classes/' . $classFile . '.php' ) ) {
				include_once( 'classes/' . $classFile . '.php' );
			}
			
			if ( DEBUG && !class_exists( $class, false ) ) {
				$prototype = 'class ' . $class . ' extends EddyModel {
						public static function find( $args = null ) {
							return parent::find( __CLASS__, $args );
						}
					}';
		
				eval( $prototype );
			}
		}
	}
	
	function amendQueryString( $params, $toggle = false, $clearCurrent = false ) {
		if ( !$clearCurrent && $_SERVER[ 'QUERY_STRING' ] != '' ) {
			$newQueryString = explode_with_keys( '&', $_SERVER[ 'QUERY_STRING' ] );
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
			$qs = implode_with_keys( '&', $newQueryString );
			
			if ( $qs ) {
				return '?' . $qs;
			}
		}
		
		return false;
	}
	
	function buildSqlOrderBy( $column, $direction = 'ASC' ) {
		if ( !empty( $column ) ) {
			return EddyDB::getEscapeString( $column . ' ' . strtoupper( $direction ) );
		}
	}
	
	function explode_with_keys( $separator, $string ) {
		$array = explode( $separator, $string );
	
		if ( is_array( $array ) ) {
			foreach ( $array as $value ) {
				$row = explode( '=', $value );
				$output [ $row[0] ] = $row[1];
			}
	
			return $output;
		}
		else {
			return null;
		}
	}
	
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
	
	function getCurrentURIPath( $remQueryString = true ) {
		if ( isset( $_SERVER[ 'REDIRECT_URL' ] ) ) {
			$requestURI = $_SERVER[ 'REDIRECT_URL' ];
		}
		else {
			$requestURI = $_SERVER[ 'REQUEST_URI' ];
		}
	
		$request[ 'actual' ] = str_replace( '^' . str_replace( 'index.php', '', $_SERVER[ 'PHP_SELF' ] ), '', '^' . $requestURI );
	
		if ( $remQueryString && strpos( $request[ 'actual' ], '?' ) !== false ) {
			$request[ 'actual' ] = str_replace( '?' . $_SERVER[ 'QUERY_STRING' ], '', $request[ 'actual' ] );
		}
	
		$request_rev = strrev( $request[ 'actual' ] );
		
		$request[ 'fixed' ] = $request[ 'actual' ];
		
		if ( !$request[ 'actual' ] || $request_rev{0} == '/' ) {
			$request[ 'fixed' ] .= 'index';
		}
	
		return $request;
	}

	/*
	 * Returns the opposite
	 */
	function getOppositeOrderBy( $column, $getOrderByParam = 'ob', $getOrderParam = 'o' ) {
		if ( $_GET[ $getOrderByParam ] == $column ) {
			switch ( strtoupper( $_GET[ $getOrderParam ] ) ) {
				case 'ASC':
						return 'desc';
					break;
				default:
					return 'asc';
			}
		}
		else {
			return 'asc';
		}
	}

	function getPageNumber( $pageString = null, $strToRemove = 'page' ) {
		if ( !empty( $pageString ) ) {
			$page = str_ireplace( $strToRemove, '', $pageString );
		}
		else {
			$page = 1;
		}

		if ( $page < 1 || !is_numeric( $page ) ) {
			redirect( './' );
		}

		return $page;
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
	
	function googleAnalyticsScript() {
		if ( defined( 'GA_UAID' ) && !$_SESSION[ 'notrack' ] ) {
			echo "<script type=\"text/javascript\">
					var _gaq = _gaq || [];
					_gaq.push(['_setAccount', '" . GA_UAID . "']);
					_gaq.push(['_trackPageview']);
					
					(function() {
					  var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					  ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
					})();
				</script>";
		}
	}

	/*
	 * Favour controller object method 'EddyController->goSecure()' - this should only be used outside controller context
	 */
	function goSecure( $minUserRank, $redirect = '/login' ) {
		if ( $_SESSION[ 'UserRank' ] < $minUserRank ) {
			redirect( $redirect, true );
		}
	}
	
	function imploder( $glue, $pieces ) {
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

	function implode_with_keys( $glue, $array ) {
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
	
	function include_partial( $path ) {
		global $EddyFC;
		
		foreach ( $EddyFC[ 'viewdata' ] as $var => $val ) {
			$$var = $val;
		}
		
		if ( file_exists( 'views/' . $path . '.part.phtml' ) ) {
			include_once( 'views/' . $path . '.part.phtml' );
		}
	}
	
	function include_view() {
		global $EddyFC;
		
		foreach ( $EddyFC[ 'viewdata' ] as $var => $val ) {
			$$var = $val;
		}

		if ( file_exists( 'views/' . $EddyFC[ 'view' ] . '.phtml' ) ) {
			include_once( 'views/' . $EddyFC[ 'view' ] . '.phtml' );
		}
		else {
			include_once( 'views/404.phtml' );
		}
	}

	/**
	 * Determines if the given value looks like an ID
	 * @param mixed $id
	 * @return bool
	 */
	function is_id( $id = null ) {
		if ( isset( $id ) && is_numeric( $id ) ) {
			return true;
		}

		return false;
	}

	function method_is( $type, $method, $class ) {
		$refl = new ReflectionMethod( $class, $method );
		
		switch ( strtolower( $type ) ) {
			case 'static':
				return $refl->isStatic();
				break;
			case 'public':
				return $refl->isPublic();
				break;
			case 'private':
				return $refl->isPrivate();
				break;
		}
	} 
	
	function now() {
		return date( 'Y-m-d H:i:s' );
	}
	
	function orderByHref( $var, $default = null, $getOrderByParam = 'ob', $getOrderParam = 'o' ) {
		if ( isset( $default ) && in_array( strtoupper( $default ), array( 'ASC', 'DESC' ) ) ) {
			$order = strtolower( $default );
		}
		else {
			$order = getOppositeOrderBy( $var );
		}
		
		return amendQueryString( array( $getOrderByParam => $var, $getOrderParam => $order ) );
	}

	/**
	 * Simple function to determine whether a plural or singular word should be used with a number
	 * @param int $count The number of things
	 * @param string $singular The term to use for 1 thing
	 * @param string $plural The term to use for multiple (or no) things
	 * @return string The correct formation of the number and term
	 */
	function pluralize( $count, $singular, $plural ) {
		if ( $count < 1 ) {
			return 'No ' . $plural;
		}
		elseif ( $count > 1 ) {
			return $count . ' ' . $plural;
		}
		else {
			return $count . ' ' . $singular;
		}
	}

	/**
	 * Creates an unguessable session-based key that must be present and empty to qualify
	 */
	function realUser() {
		if ( !isset( $_SESSION[ 'nobots' ] ) ) {
			$_SESSION[ 'nobots' ] = md5( mt_rand() );
		}

		if ( array_key_exists( $_SESSION[ 'nobots' ] , $_POST ) && empty( $_POST[ $_SESSION[ 'nobots' ] ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Favour controller object method 'EddyController->redirect()'
	 * This one doesn't handle AJAX redirects, only use outside controller context
	 */
	function redirect( $location = '/', $recordDestination = false ) {
		if ( $location{0} == '/' ) {
			$location = SITE_ROOT . $location;
		}
		
		if ( $recordDestination ) {
			$request = getCurrentURIPath();
			$_SESSION[ 'Destination' ] = $request[ 'actual' ];
		}
		
		// If this is an AJAX call, we'll have to handle the redirect on the front-end
		if ( !$_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) {
			ob_end_clean();
			header( 'Location: ' . $location );
			exit;
		}
	}

	/**
	 * Make a string URL friendly
	 */
	function urlize( $str ) {
		$str = preg_replace( array( '/\'/', '/[^a-z0-9-]/i', '/-{2,}/' ), array( '', '-', '-' ), html_entity_decode( $str, ENT_QUOTES ) );

		return trim( $str, '-' );
	}

	/**
	 * Reproduce a PHP item any number of times
	 * @param mixed $item The item to copy
	 * @param int $count The number of times to copy it
	 * @return array Array of copies of the original item
	 */
	function xof( $item, $count ) {
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