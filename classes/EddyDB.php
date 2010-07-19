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
	
		public function __construct ( $host = null, $user = null, $pass = null, $db = null, $port = null, $socket = null ) {
			foreach ( func_get_args() as $arg => $value ) {
				if ( $value != null ) {
					$this->$arg = $value;
				}
			}
			
			if ( !self::$instance instanceof DB ) {
				parent::__construct ( $this->host, $this->user, $this->pass, $this->db, $this->port, $this->socket );
	
			    if ( mysqli_connect_errno() ) {
			    	throw new Exception ( 'Connection to Database failed!' );
			    }
	
				self::$instance = $this;
			}
		}
	
		public static function getInstance() {
			if ( self::$instance instanceof DB ) {
				return self::$instance;
			}
			else {
				return new self();
			}
		}
		
		public static function getEscapeString ( $str ) {
			$db = self::getInstance();
			
			return $db->escape_string ( $str );
		}
		
		public function lite_query ( $query ) {
			return parent::query ( $query );
		}
		
		public static function q ( $query ) {
			$db = self::getInstance();
			
			return $db->query ( $query );
		}
	
		public function query ( $query ) {
			$startTime = microtime();
			$result = parent::query ( $query );
			$endTime = microtime();
	
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
				FB::error ( $query, 'Error in Query: ' . $this->error );
				FB::trace ( 'Stack Trace' );
			}
	
			return $result;
		}
	
		public static function getDatabaseName() {
			$instance = self::getInstance();
	
			return $instance->db;
		}
	}