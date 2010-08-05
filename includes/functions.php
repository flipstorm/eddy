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
	
	function amendQueryString ( $params, $clearCurrent = false ) {
		if ( !$clearCurrent && $_SERVER [ 'QUERY_STRING' ] != '' ) {
			$newQueryString = explode_with_keys ( '&', $_SERVER [ 'QUERY_STRING' ] );
		}
	
		if ( is_array ( $params ) ) {
			foreach ( $params as $key => $value ) {
				$newQueryString [ $key ] = $value;
			}
		}
	
		if ( is_array ( $newQueryString ) ) {
			return '?' . implode_with_keys ( '&', $newQueryString );
		}
		
		return false;
	}
	
	function buildSqlOrderBy ( $column, $direction = 'ASC' ) {
		if ( !empty ( $column ) ) {
			return EddyDB::getEscapeString ( $column . ' ' . strtoupper ( $direction ) );
		}
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
	
	function getOppositeOrderBy ( $column ) {
		if ( $_GET [ 'ob' ] == $column ) {
			switch ( strtoupper ( $_GET [ 'o' ] ) ) {
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
	
	function include_partial ( $path ) {
		global $EddyFC;
		
		foreach ( $EddyFC [ 'viewdata' ] as $var => $val ) {
			$$var = $val;
		}
		
		@include_once ( 'views/' . $path . '.phtml' );
	}
	
	function include_view() {
		global $EddyFC;
		
		foreach ( $EddyFC [ 'viewdata' ] as $var => $val ) {
			$$var = $val;
		}

		@include_once ( 'views/' . $EddyFC [ 'view' ] . '.phtml' );
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
	
	function xof ( $item, $count ) {
		$x = 1;
		
		while ( $x <= $count ) {
			$items[] = $item;
			++$x;
		}
		
		return $items;
	}