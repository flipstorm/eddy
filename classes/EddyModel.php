<?php
	abstract class EddyModel {
		protected $id;
		protected $isDataBound = false;
		private $table;
	
		public function __construct ( $id = null ) {
			$this->table = strtolower ( get_class ( $this ) );
	
			if ( $this->id ) {
				$this->isDataBound = true;
			}
			elseif ( is_numeric ( $id ) && !$this->isDataBound ) {
				$this->isDataBound = $this->findById ( $id );
			}
		}
		
		public function __get ( $arg ) {
			// Assume the method collects the data we want!
			if ( method_exists ( $this, 'get' . ucfirst ( $arg ) ) ) {
				return call_user_method ( 'get' . ucfirst ( $arg ), $this );
			}
		}
	
		public function __call ( $name, $args ) {
			if ( strpos ( $name, 'has_' ) !== false ) {
				$table = str_replace ( 'has_', '', $name );
				// This should be a simple check to see if there are any records in $table
				// where $this->table . '_id' = $this->id
			}
		}
		
		public function getId() {
			return $this->id;
		}
		
		public function getIsDataBound() {
			return $this->isDataBound;
		}
	
		final private function findById ( $id ) {
			$result = self::query ( 'SELECT * FROM `' . $this->table . '` WHERE id = ' . $id );
	
			if ( $result instanceof mysqli_result ) {
				$row = $result->fetch_array ( MYSQLI_ASSOC );
	
				if ( is_array ( $row ) ) {
					foreach ( $row as $fieldname => $data ) {
						$this->$fieldname = $data;
					}
	
					return true;
				}
			}
	
			return false;
		}
	
		public static function find ( $args = null ) {
			$table = strtolower ( get_called_class() );
			
			$query = 'SELECT * FROM ' . $table;
			
			$query .= ( isset ( $args [ 'WHERE' ] ) ) ? ' WHERE ' . $args [ 'WHERE' ] : '';
			$query .= ( isset ( $args [ 'LIMIT' ] ) ) ? ' LIMIT ' . $args [ 'LIMIT' ] : ' LIMIT 20';
			
			$result = self::query ( $query );
	
			if ( $result->num_rows > 0 ) {
				while ( $row = $result->fetch_object ( $table ) ) {
					$rows[] = $row;
				}
			}
	
			return $rows;
		}
	
		public static function query ( $query ) {
			$db = EddyDB::getInstance();
	
			return $db->query ( $query );
		}
	
		public function save ( $asNew = false ) {
			$db = EddyDB::getInstance();
	
			if ( !isset ( $this->id ) ) {
				$asNew = true;
			}
	
			foreach ( get_object_public_vars ( $this ) as $fieldname => $value ) {
			if ( $fields == '' ) {
				$fields = $fieldname;
				
				if ( is_null ( $value ) ) {
					$values = 'NULL';
				}
				else {
					$values = '"' . $db->escape_string ( $value ) . '"';
				}
				
				$updateValues = $fields . ' = ' . $values;
			}
			else {
				$fields .= ', ' . $fieldname;
				
				if ( is_null ( $value ) ) {
					$values .= 'NULL';
				}
				else {
					$values .= '"' . $db->escape_string ( $value ) . '"';
				}

				$updateValues .= ', ' . $fieldname . ' = ';
				
				if ( is_null ( $value ) ) {
					$updateValues .= 'NULL';
				}
				else {
					$updateValues .= '"' . $db->escape_string ( $value ) . '"';
				}
			}
		}
	
			if ( $asNew ) {
				$result = $db->query ( 'INSERT INTO `' . $this->table . '` ( ' . $fields . ' ) VALUES ( ' . $values . ' )' );
			
				$this->id = EddyDB::$insertId;
			}
			else {
				$result = $db->query ( 'UPDATE `' . $this->table . '` SET ' . $updateValues . ' WHERE id = ' . $this->id );
			}
			
			return $result;
		}
	
		public function delete ( $realDelete = false ) {
			if ( $realDelete ) {
				$result = self::query ( 'DELETE FROM `' . $this->table . '` WHERE id = ' . $this->id );
			}
			else {
				if ( in_array ( 'deleted', get_object_public_vars ( $this ) ) ) {
					$result = self::query ( 'UPDATE `' . $this->table . '` SET deleted = 1 WHERE id = ' . $this->id );
				}
				else {
					FB::error ( 'Could not pseudo-delete `' . $this->table . '` WHERE id = ' . $this->id . ', `deleted` field doesn\'t exist! Try calling $obj->delete(true)' );
				}
			}
	
			return $result;
		}
	}