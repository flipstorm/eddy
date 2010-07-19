<?php
	abstract class EddyModel {
		protected $id;
		protected $isDataBound = false;
		
		private $table;
		
		public $_id;
	
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
		
		public static function count ( $where = null ) {
			$where = ( isset ( $where ) ) ? ' WHERE ' . $where : '';
		
			if ( $result = EddyDB::q ( 'SELECT COUNT(id) AS count FROM ' . strtolower ( get_called_class() ) . $where ) ) {
				$row = $result->fetch_array();
			}
			
			return $row [ 'count' ];
		}
	
		final private function findById ( $id ) {
			$result = EddyDB::q ( 'SELECT * FROM `' . $this->table . '` WHERE id = ' . $id );
	
			if ( $result instanceof mysqli_result ) {
				$row = $result->fetch_array ( MYSQLI_ASSOC );
	
				if ( is_array ( $row ) ) {
					foreach ( $row as $fieldname => $data ) {
						$this->$fieldname = $data;
					}
					
					$this->_id = $this->id;
	
					return true;
				}
			}
	
			return false;
		}
	
		public static function find ( $args = null ) {
			$table = strtolower ( get_called_class() );
			
			$query = 'SELECT * FROM ' . $table;
			
			$query .= ( isset ( $args [ 'WHERE' ] ) ) ? ' WHERE ' . $args [ 'WHERE' ] : '';
			$query .= ( isset ( $args [ 'LIMIT' ] ) ) ? ' LIMIT ' . $args [ 'LIMIT' ] : '';
			
			$result = EddyDB::q ( $query );
	
			if ( $result->num_rows > 0 ) {
				while ( $row = $result->fetch_object ( $table ) ) {
					$rows[] = $row;
				}
			}
	
			return $rows;
		}
	
		public function save ( $asNew = false ) {
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
						$values = '"' . EddyDB::getEscapeString ( $value ) . '"';
					}
					
					$updateValues = $fields . ' = ' . $values;
				}
				else {
					$fields .= ', ' . $fieldname;
					
					if ( is_null ( $value ) ) {
						$values .= ', NULL';
					}
					else {
						$values .= ', "' . EddyDB::getEscapeString ( $value ) . '"';
					}
	
					$updateValues .= ', ' . $fieldname . ' = ';
					
					if ( is_null ( $value ) ) {
						$updateValues .= 'NULL';
					}
					else {
						$updateValues .= '"' . EddyDB::getEscapeString ( $value ) . '"';
					}
				}
			}
	
			if ( $asNew ) {
				$result = EddyDB::q ( 'INSERT INTO `' . $this->table . '` ( ' . $fields . ' ) VALUES ( ' . $values . ' )' );
			
				$this->id = EddyDB::$insertId;
				$this->_id = $this->id;
			}
			else {
				$result = EddyDB::q ( 'UPDATE `' . $this->table . '` SET ' . $updateValues . ' WHERE id = ' . $this->id );
				
				// TODO: Refresh the object in memory with latest from DB?
				// This is only worthwile where the update itself causes a change to certain data
				// like an on_update CURRENT_TIMESTAMP for a TIMESTAMP field
			}
			
			return $result;
		}
	
		public function delete ( $realDelete = false ) {
			if ( $realDelete ) {
				$result = EddyDB::q ( 'DELETE FROM `' . $this->table . '` WHERE id = ' . $this->id );
			}
			else {
				if ( in_array ( 'deleted', get_object_public_vars ( $this ) ) ) {
					$result = EddyDB::q ( 'UPDATE `' . $this->table . '` SET deleted = 1 WHERE id = ' . $this->id );
				}
				else {
					FB::error ( 'Could not pseudo-delete `' . $this->table . '` WHERE id = ' . $this->id . ', `deleted` field doesn\'t exist! Try calling $obj->delete(true)' );
				}
			}
	
			return $result;
		}
		
		public function refresh() {
			$this->findById ( $this->id );
		}
	}