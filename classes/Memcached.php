<?php
	define ( 'MEMCACHED_SERVER', 'localhost' );
	define ( 'MEMCACHED_PORT', 11211 );
	define ( 'MEMCACHED_KEY_TOKEN', 'tgp' );
	
	/**
	 * Basic Memcache manager class
	 *
	 */
	class Memcached {
		private static $singleton = null;
	
		/**
		 * Sets up a singleton if it doesn't exist
		 *
		 * @return Memcache
		 */
		private static function getMem() {
			if ( self::$singleton === null ) {
				$memcache = new Memcache();
	
				if ( !@$memcache->connect ( MEMCACHED_SERVER, MEMCACHED_PORT ) ) {
					FB::warn ( 'Connection to memcached cannot be completed. Check server and port and that the daemon is running.' );
					self::$singleton = false;
				}
				else {
					self::$singleton = $memcache;
				}
			}
	
			return self::$singleton;
		}
	
		/**
		 * Get's cached data if $key exists
		 *
		 * @param string $key
		 * @return mixed
		 */
		public static function getMemCache ( $key = '' ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				$data = $memcache->get ( self::generateSecureMemCacheKey ( $key ) );
	
				if ( $data ) {
					return $data;
				}
			}
	
			return false;
		}
	
		/**
		 * Generates a unique Memcache key for this app
		 *
		 * @param string $key
		 * @return string
		 */
		private static function generateSecureMemCacheKey ( $key ) {
			return md5 ( MEMCACHED_KEY_TOKEN . $key );
		}
	
		/**
		 * Maintains an index of all keys used for a given domain
		 *
		 * @param string $key
		 */
		private static function addKeyToIndex ( $key ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				$keys = self::getMemCache ( '' );
	
				if ( !is_array ( $keys ) ) {
					$keys = array();
				}
	
				if ( !in_array ( $key, $keys ) ) {
					$keys[] = $key;
				}
	
				self::updateMemCache ( '', $keys, 0 );
			}
		}
	
		/**
		 * Adds data to cache. Default expiry = 1 week
		 *
		 * @param string $key
		 * @param mixed $data
		 * @param int[optional] $expires
		 * @return bool
		 */
		public static function addMemCache ( $key, $data, $expires = 604800 ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				$uniqueKey = self::generateSecureMemCacheKey ( $key );
	
				if ( $memcache->add ( $uniqueKey, $data, null, $expires ) ) {
					// Append new key to the index of used keys for this domain
					self::addKeyToIndex ( $uniqueKey );
	
					return true;
				}
			}
	
			return false;
		}
	
		public static function removeMemCache ( $key ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				if ( $memcache->delete ( self::generateSecureMemCacheKey ( $key ) ) ) {
					return true;
				}
			}
	
			return false;
		}
	
		public static function updateMemCache ( $key, $data, $expires ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				if ( $memcache->set ( self::generateSecureMemCacheKey ( $key ), $data, null, $expires ) ) {
					return true;
				}
			}
	
			return false;
		}
	
		public static function deleteMemCache ( $key = '', $timeout = 0 ) {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				if ( $memcache->delete ( self::generateSecureMemCacheKey ( $key ), $timeout ) ) {
					return true;
				}
			}
	
			return false;
		}
	
		/**
		 * Flushes the cache only for the current domain
		 *
		 */
		public static function flush() {
			$memcache = self::getMem();
	
			// Cycle through all of the keys registered in the index and delete them
			foreach ( self::getMemCache() as $uniqueKey ) {
				$memcache->delete ( $uniqueKey );
			}
	
			// Then delete the domain key
			self::deleteMemCache();
		}
	
		/**
		 * *WARNING* Performs a memcached flush, which flushes the entire server's cache
		 *
		 */
		public static function fullFlush() {
			$memcache = self::getMem();
	
			if ( $memcache ) {
				$memcache->flush();
	
				return true;
			}
			else {
				return false;
			}
		}
	}