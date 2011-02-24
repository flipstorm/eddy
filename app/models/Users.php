<?php
	class Users extends EddyModel {
		public $name;
		public $lastlogin_date;
		public $rank;
		
		public static function doLogin( $username, $password ) {
			$result = self::find(
					'name = "' . EddyDB::esc_str( $username ) . '"
					AND password = "' . EddyDB::esc_str( sha1( $password ) ) . '"
					AND deleted = 0'
				);

			if ( count( $result ) == 1 ) {
				$user = $result[0];
				$_SESSION[ 'user' ] = serialize( $user );
				$_SESSION[ 'user_rank' ] = $user->rank;
	
				$user->lastlogin_date = now();
				$user->save();
	
				return true;
			}

			return false;
		}

		public static function find( $where = null ) {
			return parent::find( __CLASS__, array( 'WHERE' => $where ) );
		}
	}