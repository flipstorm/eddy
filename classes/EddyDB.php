<?php
	final class EddyDB extends mysqli {
		private $db = '';
		private $host = 'localhost';
		private $pass = 'armageddon';
		private $port;
		private $socket;
		private $table_prefix;
		private $user = 'root';
		
		private static $instance;
	
		public static $totalQueryTime = 0;
		public static $insertId;
		public static $queries;
	
		public function __construct() {
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