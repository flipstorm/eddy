<?php
	abstract class EddyModel extends EddyBase {
		protected $id;
		protected $isDataBound = false;
		
		protected static $cache = array();
		
		private $table;
		
		public $_id;

		/**
		 * EddyModel constructor
		 * @param int $id Pass an id to get a saved object
		 */
		public function __construct( $id = null ) {
			$this->table = strtolower( get_class( $this ) );
	
			if ( $this->id ) {
				// $this->id was set before we got here (i.e. by MySQLi_Result->fetch_object() call)
				$this->isDataBound = true;
			}
			elseif ( is_id( $id ) && !$this->isDataBound ) {
				$cachedObj = self::$cache[ $this->table . $id ];

				if ( $cachedObj instanceof $this ) {
					// Object cached: Map the cached object onto the new one
					foreach ( get_object_vars( $this ) as $key => $value ) {
						$this->$key = $cachedObj->$key;
					}
				}
				else {
					// Object not cached: Create a new object and cache it
					$this->isDataBound = $this->findById( $id );
					
					self::$cache[ $this->table . $id ] = $this;
				}
			}
		}
	
		public function __call( $name, $args ) {
			if ( strpos( $name, 'has_' ) !== false ) {
				$table = str_replace( 'has_', '', $name );
				// This should be a simple check to see if there are any records in $table
				// where $this->table . '_id' = $this->id
			}
		}
		
		public function _getId() {
			return $this->id;
		}
		
		public function _getIsDataBound() {
			return $this->isDataBound;
		}

		/**
		 * Get a count of records - optionally matching a WHERE clause
		 * @param string $table Use __CLASS__
		 * @param <type> $where
		 * @return int
		 */
		protected static function count( $table, $where = null ) {
			$where = ( isset( $where ) ) ? ' WHERE ' . $where : '';
		
			if ( $result = EddyDB::q( 'SELECT COUNT(id) AS count FROM `' . strtolower( $table ) . '`' . $where ) ) {
				$row = $result->fetch_array();
			}
			
			return $row[ 'count' ];
		}

		/**
		 * Find a record in the database and map its data onto this instance's properties
		 * @param int $id
		 * @return bool Whether or not the object was successfully found and mapped
		 * @final
		 */
		final private function findById( $id ) {
			if ( is_id( $id ) ) {
				$result = EddyDB::q( 'SELECT * FROM `' . $this->table . '` WHERE id = ' . $id );

				if ( $result instanceof mysqli_result ) {
					$row = $result->fetch_array( MYSQLI_ASSOC );

					if ( is_array( $row ) ) {
						foreach ( $row as $fieldname => $data ) {
							$this->$fieldname = $data;
						}

						$this->_id = $this->id;

						return true;
					}
				}
			}
	
			return false;
		}

		/**
		 * Perform a basic search query
		 * @param string $table Use __CLASS__
		 * @param array $args The clauses to use in the query (valid keys: WHERE, ORDERBY, LIMIT)
		 * @return array An array of objects found
		 */
		protected static function find( $table, $args = null ) {
			$table = strtolower( $table );
			
			$query = 'SELECT * FROM ' . $table;

			$query .= ( !empty( $args[ 'WHERE' ] ) ) ? ' WHERE ' . $args[ 'WHERE' ] : '';
			$query .= ( !empty( $args[ 'ORDERBY' ] ) ) ? ' ORDER BY ' . $args[ 'ORDERBY' ] : '';
			$query .= ( !empty( $args[ 'LIMIT' ] ) ) ? ' LIMIT ' . $args[ 'LIMIT' ] : '';
			
			$result = EddyDB::q( $query );
	
			if ( $result->num_rows > 0 ) {
				while ( $row = $result->fetch_object( $table ) ) {
					$rows[] = $row;
				}
			}
	
			return $rows;
		}

		/**
		 * Save the current state of the object back to the database
		 * @param bool $asNew Set to true to save this data in a new record (force INSERT)
		 * @return mysqli_result Query result object
		 */
		public function save( $asNew = false ) {
			$db = EddyDB::getInstance();
	
			if ( !isset( $this->id ) ) {
				$asNew = true;
			}

			if ( array_key_exists( 'created_date', get_object_public_vars( $this ) ) ) {
				$this->created_date = now();
			}

			foreach ( get_object_public_vars( $this ) as $fieldname => $value ) {
				if ( !empty( $value ) || $value == 0 ) {
					$value = '"' . $db->escape_string( $value ) . '"';
					$insertFields[] = $fieldname;
					$insertValues[] = $value;
					$updateValues[] = $fieldname . ' = ' . $value;
				}
			}
	
			if ( $asNew ) {
				$result = $db->query( 'INSERT INTO `' . $this->table . '` ( ' . implode( ',', $insertFields ) . ' )
					VALUES ( ' . implode( ',', $insertValues ) . ' )' );
			
				$this->id = EddyDB::$insertId;
				$this->_id = $this->id;
			}
			else {
				$result = $db->query( 'UPDATE `' . $this->table . '`
					SET ' . implode( ',', $updateValues ) . '
					WHERE id = ' . $this->id );
			}

			// TODO: Refresh the object in memory with latest from DB?
			// This is only worthwile where the update itself causes a change to certain data
			// like an on_update CURRENT_TIMESTAMP for a TIMESTAMP field
			
			return $result;
		}

		/**
		 * Delete a record from the database. If $realDelete == false and the table has a column 'deleted'
		 * then the record will only be 'binned' not erased
		 * @param bool $realDelete Set to 'true' to erase this record
		 * @return int Number of rows affected
		 */
		public function delete( $realDelete = false ) {
			$db = EddyDB::getInstance();
			
			if ( $realDelete ) {
				$result = $db->query( 'DELETE FROM `' . $this->table . '` WHERE id = ' . $this->id );
			}
			else {
				if ( in_array( 'deleted', get_object_public_vars( $this ) ) ) {
					$result = $db->query( 'UPDATE `' . $this->table . '` SET deleted = 1 WHERE id = ' . $this->id );
				}
				else {
					FB::error( 'Could not pseudo-delete `' . $this->table . '` WHERE id = ' . $this->id . ', `deleted` field doesn\'t exist! Try calling $obj->delete(true)' );
				}
			}
	
			return $db->affected_rows;
		}

		/**
		 * Update a group of records setting the same values for each
		 * @param string $table The table to update (use __CLASS__)
		 * @param string $range Comma separated list of record IDs
		 * @param string $set The column name(s) and value(s) to set for the $range
		 * @return int The number of updated rows
		 */
		protected static function updateRange( $table, $range, $set ) {
			$db = EddyDB::getInstance();
			
			$result = $db->query( 'UPDATE ' . strtolower( $table ) . ' SET ' . $set . ' WHERE id IN (' . $db->escape_string( $range ) . ')' );

			return $db->affected_rows;
		}

		/**
		 * Reload the current object from the database
		 * @return void
		 */
		public function refresh() {
			$this->findById( $this->id );
		}

		/*
		 * Used to pass certain protected or private properties over to JSON that wouldn't otherwise make it
		public function __sleep() {
			// If we're JSON encoding this object, we want some of the methods to be available
		}
		*/
	}