<?php
	function __autoload ( $class ) {
		if ( strpos ( $class, '_Controller' ) !== false ) {
			// This is a controller that isn't loaded
			$class = strtolower ( str_ireplace ( '_Controller$', '', $class . '$' ) );
			$classFile = str_replace ( '_', '/', $class );

			@include_once ( 'controllers/' . $classFile . '.php' );
		}
		else {
			// This is a standard class
			$classFile = str_replace ( '_', '/', $class );
	
			if ( file_exists ( 'models/' . $classFile . '.php' ) ) {
				require_once ( 'models/' . $classFile . '.php' );
			}
			elseif ( file_exists ( 'classes/' . $classFile . '.php' ) ) {
				require_once ( 'classes/' . $classFile . '.php' );
			}
			
			if ( DEBUG && !class_exists ( $class, false ) ) {
				eval ( 'class ' . $class . ' extends EddyModel {}' );
			}
		}
	}
	
	function exceptionHandler ( Exception $e ) {
		echo 'Don\'t you know how to catch yet?';
	}
	
	function getCurrentURIPath ( $remQueryString = true ) {
		if ( isset ( $_SERVER [ 'REDIRECT_URL' ] ) ) {
			$requestURI = $_SERVER [ 'REDIRECT_URL' ];
		}
		else {
			$requestURI = $_SERVER [ 'REQUEST_URI' ];
		}
	
		$request = str_replace ( '^' . str_replace ( 'index.php', '', $_SERVER [ 'PHP_SELF' ] ), '', '^' . $requestURI );
	
		if ( $remQueryString && strpos ( $request, '?' ) !== false ) {
			$request = str_replace ( '?' . $_SERVER [ 'QUERY_STRING' ], '', $request );
		}
	
		$request_rev = strrev ( $request );
		
		if ( !$request || $request_rev{0} == '/' ) {
			$request .= 'index';
		}
	
		return $request;
	}
	
	function get_object_public_vars ( $obj ) {
		$vars = get_object_vars ( $obj );
		
		// Remove vars that begin with _
		foreach ( $vars as $key => $val ) {
			if ( $key{0} !== '_' ) {
				$cleanVars [ $key ] = $val;
			}
		}
		
		return $cleanVars;
	}
	
	function explode_with_keys ( $separator, $string ) {
		$array = explode ( $separator, $string );
	
		if ( count ( $array ) > 0 ) {
			foreach ( $array as $value ) {
				$row = explode ( '=', $value );
				$output [ $row[0] ] = $row[1];
			}
	
			return $output;
		}
		else {
			return null;
		}
	}
	
	function implode_with_keys ( $glue, $array ) {
		if ( is_array ( $array ) ) {
			foreach( $array as $key => $item ) {
				if ( is_array ( $item ) ) {
					$item = $item[0];
				}
	
				if ( $item != '' ) {
					$output[] = $key . '=' . $item;
				}
			}
	
			if ( is_array ( $output ) ) {
				return implode ( $glue, $output );
			}
		}
	
		return false;
	}
	
	function now() {
		return date ( "Y-m-d H:i:s" );
	}
	
	function redirect ( $location = '', $recordDestination = false, $loginBypass = false ) {
		ob_end_clean();
	
		if ( $recordDestination ) {
			$_SESSION [ 'Destination' ] = getCurrentURIPath();
		}
	
		header ( 'Location: ' . $location );
	
		exit;
	}