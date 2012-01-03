<?php
	abstract class EddyModel extends EddyBase {
		public $_id;

		protected $id;
		protected $isDataBound = false;
		protected $table;
		protected $original;

		// Per-request caching. Enable in subclasses and override the $cache property for safer encapsulation
		protected $cacheable = false;
		//protected static $cacheable = false;
		
		protected static $cache = array();
		protected static $db_table;
		
		private $additional_save_fields = array();



		/*** MAGIC METHODS ***/
		/**
		 * EddyModel constructor
		 * @param int $id Pass an id to get a saved object
		 */
		public function __construct( $id = null ) {
			$this->table = self::getTableName( get_class( $this ) );
			
			if ( $this->id ) {
				// $this->id was set before we got here (i.e. by MySQLi_Result->fetch_object() call)
				$this->isDataBound = true;
				$this->original = get_object_public_vars( $this );
				$this->_id = $this->id;
			}
			elseif ( \Helpers\MySQL::is_id( $id ) && !$this->isDataBound ) {
				// static::$cacheable
				if ( $this->cacheable ) {
					// NOTE: Caching will not work for objects created using 'find'
					// XXX: unless, all find did was get IDs which we then create new objects for individually
					// instead of using fetch_object( CURRENT_CLASS ). Would *possibly* be more queries...
					// But would only benefit from it if $this->cacheable... so wouldn't need it instead,
					// could use it AS WELL AS the current method.
					$cachedObj = unserialize( static::$cache[ $id ] );

					if ( $cachedObj instanceof $this ) {
						// Object cached: retrieve it
						foreach ( get_object_vars( $this ) as $key => $value ) {
							$this->$key = $cachedObj->$key;
						}
						
						unset( $cachedObj );
					}
					else {
						// Object not cached: Create a new object and cache it
						$this->isDataBound = $this->findById( $id );

						static::$cache[ $id ] = serialize( $this );
					}
				}
				else {
					// Just get the data
					$this->isDataBound = $this->findById( $id );
				}
			}
		}

		public function __clone() {
			$this->new_row();
		}



		/*** PUBLIC ***/
		/**
		 * Save the current state of the object back to the database
		 * @param bool $asNew Set to true to save this data in a new record (force INSERT)
		 * @return mysqli_result Query result object
		 */
		public function save( $asNew = false, $force = false ) {
			$db = EddyDB::getInstance();

			if ( !isset( $this->id ) ) {
				$asNew = true;
			}

			$this_public_vars = get_object_public_vars( $this );

			// XXX: This isn't such a good idea!
			//if ( array_key_exists( 'created_date', $this_public_vars ) ) {
			//	$this->created_date = \Helpers\MySQL::datestamp();
			//}

			// Should this overwrite any set values? Or should it only save if it doesn't already exist?
			// Or should we enforce properties that we want to save should be public?
			foreach ( $this->additional_save_fields as $field => $value ) {
				//if ( $this->$field ) {
				//	$value = $this->$field;
				//}
				
				$this_public_vars[ $field ] = $value;
			}

			foreach ( $this_public_vars as $fieldname => $value ) {
				if ( ( $force && !is_null( $value ) ) || $this->original[ $fieldname ] !== $value || ( $asNew && !is_null( $value ) ) ) {
					// TODO: Test that this handles integers and decimals ok

					if ( $this->original[ $fieldname ] !== $value && is_null( $value ) ) {
						$value = 'NULL';
					}

					if ( $value !== 'NULL' ) {
						if ( is_bool( $value ) ) {
							$value = ( $value ) ? 1 : 0;
						}
						elseif ( is_string( $value ) ) {
							$value = '"' . $db->escape_string( $value ) . '"';
						}
					}

					$insertFields[] = $fieldname;
					$insertValues[] = $value;
					$updateValues[] = $fieldname . ' = ' . $value;
				}
			}

			if ( !$this->isDataBound || $asNew ) {
				$result = $db->query( 'INSERT INTO `' . $this->table . '` ( ' . implode( ',', $insertFields ) . ' )
					VALUES ( ' . implode( ',', $insertValues ) . ' )' );

				$this->id = EddyDB::$insertId;
				$this->_id = $this->id;
				$this->isDataBound = true;
			}
			elseif ( !empty( $updateValues ) ) {
				$result = $db->query( 'UPDATE `' . $this->table . '`
					SET ' . implode( ', ', $updateValues ) . '
					WHERE id = ' . $this->id );
					
				// TODO: should we also update the original to reflect that this has been updated now?
			}
			else {
				//throw new Exception( 'Data is unchanged, record not updated' );
				$result = false;
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
				$this->new_row();
			}
			else {
				if ( array_key_exists( 'deleted', get_object_public_vars( $this ) ) ) {
					$this->deleted = true;
					$result = $db->query( 'UPDATE `' . $this->table . '` SET deleted = 1 WHERE id = ' . $this->id );
				}
				else {
					FB::error( 'Could not pseudo-delete `' . $this->table . '` WHERE id = ' . $this->id . ', `deleted` field doesn\'t exist! Try calling $obj->delete(true)' );
				}
			}

			return $db->affected_rows;
		}
		
		// XXX: would this be better/more efficient as a hash comparison on a serialized version of the object?
		public function has_changed() {
			$this_public_vars = get_object_public_vars( $this );
			
			foreach ( $this_public_vars as $fieldname => $value ) {
				// Try to grab a string if $value is an object when
				if ( !is_object( $this->original[ $fieldname ] ) && is_object( $value ) && method_exists( $value, '__toString' ) ) {
					$value = $value->__toString();
				}
				
				if ( $this->original[ $fieldname ] !== $value && $this->original[ $fieldname ] != $value ) {
					//\FB::info($value, 'has_changed');
					//\FB::info(gettype($value), '$value');
					//\FB::info(gettype($this->original[ $fieldname ]), '$this->original[ ' . $fieldname . ' ]');
					return true;
				}
			}
			
			return false;
		}

		/**
		 * Reload the current object from the database
		 * @return void
		 */
		public function refresh() {
			$this->findById( $this->id );
		}



		/*** PUBLIC STATIC ***/
		// Super function to self::find that saves having to write a 'find' method in each model
		public static function get( $args = array() ) {
			return self::find( get_called_class(), $args );
		}



		/*** PROTECTED ***/
		protected function _get_id() {
			return $this->id;
		}

		protected function _get_isDataBound() {
			return $this->isDataBound;
		}
		
		protected function _get_original() {
			return $this->original;
		}

		protected function add_data( $field, $value = null ) {
			$this->additional_save_fields[ $field ] = $value;
		}



		/*** PROTECTED STATIC ***/
		/**
		 * Get a count of records - optionally matching a WHERE clause
		 * @param string $where
		 * @param string[optional] $count_col Column to use for count. Default: id
		 * @return int
		 */
		protected static function count( $where = null, $count_col = 'id' ) {
			$table = self::getTableName( get_called_class() );
			
			$where = ( isset( $where ) ) ? ' WHERE ' . $where : '';

			if ( $result = EddyDB::q( 'SELECT COUNT(' . $count_col . ') AS count FROM `' . $table . '`' . $where ) ) {
				$row = $result->fetch_array();
			}

			return $row[ 'count' ];
		}

		/**
		 * Perform a basic search query
		 * @param string $table Use __CLASS__
		 * @param array $args The clauses to use in the query (valid keys: WHERE, ORDERBY, LIMIT, GROUPBY)
		 * @return array An array of objects found
		 */
		protected static function find( $table = null, $args = array(), $subquery = false ) {
			$table = self::getTableName( $table );

			// Uppercase all keys in the args
			$args = array_change_key_case( $args, CASE_UPPER );
			
			/* if ( static::$cacheable ) {
			 * 	$query = 'SELECT id FROM `' . $table . '`';
			 * }
			 * else {
			 * 	$query = 'SELECT *, 1 as isDataBound FROM `' . $table . '`';
			 * }
			 */

			$query = 'SELECT *, 1 as isDataBound FROM `' . $table . '`';

			if ( !empty( $args[ 'WHERE' ] ) ) {
				$query .= ' WHERE ';

				if ( is_array( $args[ 'WHERE' ] ) ) {
					$first = true;

					foreach( $args[ 'WHERE' ] as $field => $value ) {
						$in_set = false;

						if ( !$first ) {
							if ( preg_match( '/^[\|\|].+/', $field ) ) {
								$field = trim( preg_replace( '/^\|\|/', '', $field ) );
								$query .= ' OR ';
							}
							else {
								$query .= ' AND ';
							}
						}

						$comparison = '=';

						if ( preg_match( '/^(>=|<=|<|>|!=)/', $value, $comparisons ) ) {
							$comparison = $comparisons[1];
							$value = preg_replace( '/^' . $comparison . '/', '', $value );
						}

						if ( preg_match( '/^IN\((.+)\)/', $value, $set ) ) {
							$value = $set[1];
							$in_set = true;
						}

						if( is_null( $value ) || $value == 'NULL' ) {
							$value = 'NULL';

							$comparison = 'IS';

							if ( $comparison == '!=' ) {
								$comparison .= ' NOT';
							}
						}
						elseif ( is_string( $value ) && !$in_set ) {
							$value = '"' . EddyDB::esc_str( $value ) . '"';
						}
						elseif ( $in_set ) {
							// Set items must look like: 1,2,3,4,5,6
							foreach ( explode( ',', $value ) as $item ) {
								$values[] = EddyDB::esc_str( trim( $item ) );
							}

							$value = implode( ', ', $values );

							$comparison = 'IN (';
							$value .= ' )';
						}
						elseif( is_bool( $value ) ) {
							$value = (int) $value;
						}

						$query .= '`' . $field . '` ' . $comparison . ' ' . $value;

						$first = false;
					}
				}
				else {
					// DEPRECATED: Where clauses as a string should be phased out?
					$query .= $args[ 'WHERE' ];
				}
			}

			// TODO: improve ORDER BY and GROUP BY, parsing the field names out and escaping with backticks?
			$order_by = ( !empty( $args[ 'ORDERBY' ] ) ) ? ' ORDER BY ' . $args[ 'ORDERBY' ] : '';
			$limit = ( !empty( $args[ 'LIMIT' ] ) ) ? ' LIMIT ' . $args[ 'LIMIT' ] : '';
			$group_by = ( !empty( $args[ 'GROUPBY' ] ) ) ? ' GROUP BY ' . $args[ 'GROUPBY' ] : '';

			switch ( $subquery ) {
				case 'group':
					$query = 'SELECT * FROM ( ' . $query . $order_by . ' ) AS ' . $table . $group_by . $limit;
					break;
				case 'order':
					$query = 'SELECT * FROM ( ' . $query . $group_by . ' ) AS ' . $table . $order_by . $limit;
					break;
				default:
					$query .= $group_by . $order_by . $limit;
			}

			$result = EddyDB::q( $query );

			if ( $result->num_rows > 0 ) {
				$class = '\\Models\\' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $table ) ) );
				
				/* if ( static::$cacheable ) {
				 * 	while( $row = $result->fetch_array() ) {
				 * 		$rows[] = new $class( $row[ 'id' ] );
				 * 	}
				 * }
				 * else {
				 * 	while ( $row = $result->fetch_object( $class ) ) {
				 *		$rows[] = $row;
				 *	}
				 * }
				 */
				
				while ( $row = $result->fetch_object( $class ) ) {
					$rows[] = $row;
				}
			}

			return $rows;
		}

		/**
		 * Update a group of records setting the same values for each
		 * @param string $table The table to update (use __CLASS__)
		 * @param string $range Comma separated list of record IDs
		 * @param string $set The column name(s) and value(s) to set for the $range
		 * @return int The number of updated rows
		 */
		protected static function updateRange( $range, $set ) {
			$db = EddyDB::getInstance();
			$table = self::getTableName( get_called_class() );

			$result = $db->query( 'UPDATE ' . $table . ' SET ' . $set . ' WHERE id IN (' . $db->esc_str( $range ) . ')' );

			return $db->affected_rows;
		}

		final protected static function getTableName( $table ) {
			$table = strtolower( str_ireplace( array( '^\\', '^Models\\', '\\', '^' ), array( '^', '', '_', '' ), '^' . $table ) );

			if ( static::$db_table ) {
				$table = static::$db_table;
			}
			else {
				$table = strtolower( \Helpers\Inflector::pluralize( $table ) );
			}

			return $table;
		}
		
		
		
		/*** PRIVATE ***/
		private function new_row() {
			$this->id = null;
			$this->_id = null;
			$this->isDataBound = false;
		}

		/**
		 * Find a record in the database and map its data onto this instance's properties.
		 * Should only be called from constructor
		 * @param int $id
		 * @return bool Whether or not the object was successfully found and mapped
		 * @final
		 */
		final private function findById( $id ) {
			if ( \Helpers\MySQL::is_id( $id ) ) {
				$result = EddyDB::q( 'SELECT * FROM `' . $this->table . '` WHERE `id` = ' . $id );

				if ( $result instanceof mysqli_result ) {
					$row = $result->fetch_array( MYSQLI_ASSOC );

					if ( is_array( $row ) ) {
						foreach ( $row as $fieldname => $data ) {
							$this->$fieldname = $data;
						}

						$this->original = $row;
						$this->_id = $this->id;

						return true;
					}
				}
			}

			return false;
		}
	}