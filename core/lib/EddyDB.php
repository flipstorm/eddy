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

		public static $debugQueryCount;
		public static $debugActualQueryCount;
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

		/**
		 * Runs MySQLi::escape_string() against the given string
		 * @param string $str
		 * @return string The escaped string
		 */
		public static function esc_str( $str ) {
			$db = self::getInstance();
			
			return $db->escape_string( $str );
		}

		/**
		 * Static wrapper for running queries against the DB singleton
		 * @param string $query
		 * @return mixed
		 * @see MySQLi::query()
		 */
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
				if ( DEBUG && self::$debugQueryCount < 100 ) {
					self::$queries[] = array( $query, $execTime );
					self::$debugQueryCount++;
				}

				self::$debugActualQueryCount++;
	
				if ( $this->insert_id ) {
					self::$insertId = $this->insert_id;
				}
			}
			else {
				//FB::error( $query, 'Error in Query: ' . $this->error );
				//FB::trace( 'Stack Trace' );
			}
	
			return $result;
		}

		public static function get_value( $query ) {
			$result = self::q( $query );
			$row = $result->fetch_row();

			return $row[0];
		}
	
		public static function getDatabaseName() {
			$instance = self::getInstance();
	
			return $instance->db;
		}
	}