<?php
	abstract class EddyModel extends EddyBase {
		public $_id;

		protected $id;
		protected $isDataBound = false;
		protected $table;
		protected $original;
		protected $onDuplicateUpdate;

		// Per-request caching. Enable in subclasses and override the $cache property for safer encapsulation
		protected static $cacheable = false;
		protected static $cache = array();
		protected static $query_cache;
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
				if ( static::$cacheable ) {
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
		 * @param bool $force
		 * @param bool $with_ignore Uses MySQL IGNORE statement
		 * @return mysqli_result Query result object
		 */
		public function save( $asNew = false, $force = false, $with_ignore = false ) {
			$db = EddyDB::getInstance();

			// !$this->isDataBound
			if ( !isset( $this->id ) ) {
				$asNew = true;
			}

			$this_public_vars = get_object_public_vars( $this );

			// TODO: only allow this to be a field reference, not a value too. That way, we can set it to save object properties that are otherwise outside the public scope
			// unless... we may just want to change a value at the last minute for some reason
			foreach ( $this->additional_save_fields as $field => $value ) {
				//if ( $this->$field ) {
				//	$value = $this->$field;
				//}

				$this_public_vars[ $field ] = $value;
			}

			foreach ( $this_public_vars as $fieldname => $value ) {
				$ignore = false;

				if ( ( $force && !is_null( $value ) ) || ( $this->original[ $fieldname ] !== $value && $this->original[ $fieldname ] != $value ) || ( $asNew && !is_null( $value ) ) ) {
					if ( $this->original[ $fieldname ] !== $value && is_null( $value ) ) {
						$value = 'NULL';
					}

					if ( $value !== 'NULL' ) {
						if ( is_bool( $value ) ) {
							// Convert to simplest true or false (works best with MySQL TINYINT(1) UNSIGNED NOT NULL)
							$value = ( $value ) ? 1 : 0;
						}

						// Escape all other values and put them in quotation marks
						elseif ( is_string( $value ) || is_numeric( $value ) ) {
							$value = '"' . $db->escape_string( $value ) . '"';
						}
						elseif ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
							// Try to extract a value from an object
							$value = '"' . $db->escape_string( $value->__toString() ) . '"';
						}

						// Ignore unusable values
						//elseif ( is_array( $value ) ) {
							// XXX: Throw an error?
						//}
						else {
							// We don't know what the value is or how to get it
							FB::warn( $value, 'Invalid value for ' . $this->table . '.' . $fieldname );
							$ignore = true;
						}
					}

					if ( !$ignore ) {
						$insertFields[] = '`' . $fieldname . '`';
						$insertValues[] = $value;
						$updateValues[] = $fieldname . ' = ' . $value;
					}
				}
			}

			if ( $with_ignore ) {
				$ignore = ' IGNORE';
			}

			if ( !$this->isDataBound || $asNew ) {
				if ( $this->onDuplicateUpdate ) {
					$append = ' ON DUPLICATE KEY UPDATE ' . $this->onDuplicateUpdate;
				}

				$result = $db->query(
					'INSERT' . $ignore . ' INTO `' . $this->table . '` ( ' . implode( ',', $insertFields ) . ' )
					VALUES ( ' . implode( ',', $insertValues ) . ' )' . $append
				);

				$this->id = EddyDB::$insertId;
				$this->_id = $this->id;
				$this->isDataBound = true;
			}
			elseif ( !empty( $updateValues ) ) {
				$result = $db->query(
					'UPDATE' . $ignore . ' `' . $this->table . '`
					SET ' . implode( ', ', $updateValues ) . '
					WHERE id = ' . $this->id
				);

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
					return false;
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
		/**
		 * Get a count of records - optionally matching a WHERE clause
		 * @param string $where
		 * @param string[optional] $count_col Column to use for count. Default: id
		 * @return int
		 */
		public static function count( $where = null, $count_col = 'id' ) {
			$table = self::getTableName( get_called_class() );

			$where = ( isset( $where ) ) ? ' WHERE ' . $where : '';

			if ( $result = EddyDB::q( 'SELECT COUNT(' . $count_col . ') AS count FROM `' . $table . '`' . $where ) ) {
				$row = $result->fetch_array();
			}

			return $row[ 'count' ];
		}

		// Super function to self::find that saves having to write a 'find' method in each model
		public static function get( $args = array() ) {
			return self::find( get_called_class(), $args );
		}

		public static function get_one( $args = array() ) {
			$results = self::find( get_called_class(), $args + array( 'limit' => 1 ) );

			return $results[0];
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
		 * Perform a basic search query
		 * @param string $table Use __CLASS__
		 * @param array $args The clauses to use in the query (valid keys: SELECT => ['field1[,field2]'], WHERE, ORDERBY, LIMIT, GROUPBY, SUBQUERY => [group, order])
		 * @return array An array of objects found
		 */
		protected static function find( $table = null, $args = array() ) {
			$table = self::getTableName( $table );

			// Uppercase all keys in the args
			$args = array_change_key_case( $args, CASE_UPPER );

			// Only allow SELECT to be used when we're returning an array or the query
			if ( $args[ 'SELECT' ] ) {
				$fields = $args[ 'SELECT' ];

				$basic_objects = true;
			}
			else {
				$fields = '*';
			}

			if ( static::$cacheable && !$basic_objects ) {
				$query = 'SELECT `id` FROM `' . $table . '`';
			}
			else {
				$query = 'SELECT ' . $fields . ( $fields ? '' : ', 1 as isDataBound' ) . ' FROM `' . $table . '`';
			}

			// Build WHERE clause
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

						if ( preg_match( '/^(>=|<=|<|>|!=|LIKE )/', $value, $comparisons ) ) {
							$comparison = $comparisons[1];
							$value = preg_replace( '/^' . $comparison . '/', '', $value );
						}

						if ( preg_match( '/^(!|NOT\s)?IN\((.+)\)/', $value, $set ) ) {
							$comparison = $set[1] ? 'NOT ' : '';
							$value = $set[2];
							$in_set = true;
						}

						if ( is_null( $value ) || strtoupper( $value ) === 'NULL' ) {
							$value = 'NULL';

							if ( $comparison == '!=' ) {
								$comparison = 'IS NOT';
							}
							else {
								$comparison = 'IS';
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

							$comparison .= 'IN (';
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

			// TODO: improve SELECT, ORDER BY and GROUP BY, parsing the field names out and escaping with backticks?
			$order_by = ( !empty( $args[ 'ORDERBY' ] ) ) ? ' ORDER BY ' . $args[ 'ORDERBY' ] : '';
			$limit = ( !empty( $args[ 'LIMIT' ] ) ) ? ' LIMIT ' . $args[ 'LIMIT' ] : '';
			$group_by = ( !empty( $args[ 'GROUPBY' ] ) ) ? ' GROUP BY ' . $args[ 'GROUPBY' ] : '';

			switch ( $args[ 'SUBQUERY' ] ) {
				case 'group':
					$query = 'SELECT ' . $fields . ' FROM ( ' . $query . $order_by . ' ) AS ' . $table . $group_by . $limit;
					break;
				case 'order':
					$query = 'SELECT ' . $fields . ' FROM ( ' . $query . $group_by . ' ) AS ' . $table . $order_by . $limit;
					break;
				default:
					$query .= $group_by . $order_by . $limit;
			}

			// Check to see if we have a cached result for this query (only works for identical queries!)
			$query_key = md5( $query );
			$query_cache = static::$query_cache[ $query_key ];

			if ( !$basic_objects ) {
				$class = '\\Models\\' . str_replace( ' ', '_', ucwords( str_replace( '_', ' ', str_replace( MYSQL_TBLPREF, '', $table ) ) ) );

				if ( static::$cacheable && is_array( $query_cache ) && !empty( $query_cache ) ) {
					// Cache hit!
					foreach ( $query_cache as $cached_id ) {
						$rows[] = new $class( $cached_id );
					}
				}
				else {
					// Cache miss, hit the DB
					$result = EddyDB::q( $query );

					if ( $result->num_rows > 0 ) {
						if ( static::$cacheable ) {
						 	while( $row = $result->fetch_array() ) {
						 		$rows[] = new $class( $row[ 'id' ] );

								// Cache the query itself, so we get the ID from the cache!
								static::$query_cache[ $query_key ][] = $row[ 'id' ];
						 	}
						}
						else {
						 	while ( $row = $result->fetch_object( $class ) ) {
								$rows[] = $row;
							}
						}
					}
				}
			}
			else {
				// TODO: do result caching here too?
				$rows = EddyDB::q_into_array( $query );
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

			$result = $db->query( 'UPDATE ' . $table . ' SET ' . $set . ' WHERE `id` IN (' . $db->esc_str( $range ) . ')' );

			return $db->affected_rows;
		}

		protected static function deleteRange( $range, $realDelete = false ) {
			$db = EddyDB::getInstance();
			$table = self::getTableName( get_called_class() );

			if ( $realDelete ) {
				$result = $db->query( 'DELETE FROM `' . $table . '` WHERE `id` IN (' . $range . ')' );
			}
			else {
				//$result = self::updateRange( $range, '`deleted` = 1');
				$result = $db->query( 'UPDATE `' . $table . '` SET `deleted` = 1 WHERE `id` IN (' . $range . ')' );
			}

			return $db->affected_rows;
		}

		final protected static function getTableName( $table ) {
			$table = strtolower( MYSQL_TBLPREF . str_ireplace( array( '^\\', '^Models\\', '\\', '^' ), array( '^', '', '_', '' ), '^' . $table ) );

			if ( static::$db_table ) {
				$table = MYSQL_TBLPREF . static::$db_table;
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
			$this->original = null;
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