<?php
	final class EddyDB extends mysqli {
		private $db = MYSQL_DB;
		private $host = MYSQL_HOST;
		private $pass = MYSQL_PASSWORD;
		private $port = MYSQL_PORT;
		private $socket = MYSQL_SOCKET;
		private $table_prefix = MYSQL_TBLPREF;
		private $user = MYSQL_USER;
		
		private static $instance;
	
		public static $totalQueryTime = 0;
		public static $insertId;
		public static $queries;
	
		public function __construct( $host = null, $user = null, $pass = null, $db = null, $port = null, $socket = null ) {
			foreach ( func_get_args() as $arg => $value ) {
				if ( $value != null ) {
					$this->$arg = $value;
				}
			}
			
			if ( !self::$instance instanceof EddyDB ) {
				parent::__construct( $this->host, $this->user, $this->pass, $this->db, $this->port, $this->socket );
	
			    if ( mysqli_connect_errno() ) {
			    	throw new Exception( 'Connection to Database failed!' );
			    }
	
				self::$instance = $this;
			}
		}
	
		public static function getInstance() {
			if ( self::$instance instanceof EddyDB ) {
				return self::$instance;
			}
			else {
				return new self();
			}
		}
		
		public static function esc_str( $str ) {
			return self::getEscapeString( $str );
		}
		
		public static function getEscapeString( $str ) {
			$db = self::getInstance();
			
			return $db->escape_string( $str );
		}
		
		public static function q( $query ) {
			$db = self::getInstance();
			
			return $db->query( $query );
		}
	
		public function query( $query ) {
			$startTime = microtime( true );
			$result = parent::query( $query );
			$endTime = microtime( true );
	
			$execTime = $endTime - $startTime;
			self::$totalQueryTime += $execTime;
	
			if ( $result ) {
				if ( DEBUG ) {
					self::$queries[] = array ( 'string' => $query, 'time' => $execTime );
				}
	
				if ( $this->insert_id ) {
					self::$insertId = $this->insert_id;
				}
			}
			else {
				FB::error( $query, 'Error in Query: ' . mysqli_error( $this ) );
				FB::trace( 'Stack Trace' );
			}
	
			return $result;
		}
	
		public static function getDatabaseName() {
			$instance = self::getInstance();
	
			return $instance->db;
		}
	}