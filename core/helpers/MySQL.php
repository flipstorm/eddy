<?php
	namespace Helpers;
	
	class MySQL {
		public static function buildSqlOrderBy( $column, $direction = 'ASC' ) {
			if ( !empty( $column ) ) {
				return EddyDB::esc_str( $column . ' ' . strtoupper( $direction ) );
			}

			return null;
		}

		/*
		 * Returns the opposite
		 */
		public static function getOppositeOrderBy( $column, $getOrderByParam = 'ob', $getOrderParam = 'o' ) {
			if ( $_GET[ $getOrderByParam ] == $column ) {
				switch ( strtoupper( $_GET[ $getOrderParam ] ) ) {
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

		/**
		 * Determines if the given value looks like an ID
		 * @param mixed $id
		 * @return bool
		 */
		public static function is_id( $id = null ) {
			if ( isset( $id ) && is_numeric( $id ) ) {
				return true;
			}

			return false;
		}

		public static function datestamp( $adjust_by = null ) {
			return date( 'Y-m-d H:i:s', time() + $adjust_by );
		}

		public static function orderByHref( $var, $default = null, $getOrderByParam = 'ob', $getOrderParam = 'o' ) {
			if ( isset( $default ) && in_array( strtoupper( $default ), array( 'ASC', 'DESC' ) ) ) {
				$order = strtolower( $default );
			}
			else {
				$order = self::getOppositeOrderBy( $var );
			}

			return URI::amend_qs( array( $getOrderByParam => $var, $getOrderParam => $order ) );
		}
	}