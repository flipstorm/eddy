<?php
	class Users extends EddyModel {
		public $name;
		public $lastlogin_date;
		public $rank;
		
		public static function doLogin( $username, $password ) {
			$result = self::find( 'name = "' . EddyDB::getEscapeString( $username ) . '" AND password = "' . EddyDB::getEscapeString( $password ) . '" AND deleted = 0' );

			if ( count( $result ) == 1 ) {
				$user = $result[0];
				$_SESSION[ 'User' ] = serialize( $user );
				$_SESSION[ 'UserRank' ] = $user->rank;
	
				// This is done after serialisation so we don't change it for this session in case we need to display their
				// last login timestamp!
				$user->lastlogin_date = now();
				$user->save();
	
				return true;
			}
			else {
				return false;
			}
		}
		
		public static function find( $where = null ) {
			return parent::find( __CLASS__, array( 'WHERE' => $where ) );
		}
	}