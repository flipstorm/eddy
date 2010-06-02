<?php
class Users extends EddyModel {
	public $customers_id;
	public $displayname;
	public $lastlogin_date;
	public $usergroups_id;
	public $username;

	private $isAdmin;

	public function getIsAdmin() {
		if ( !isset( $this->isAdmin ) ) {
			// Find out whether we should treat this user as an Admin
			if ( $this->usergroups_id < 2 ) {
				$this->isAdmin = true;
			}
			else {
				$this->isAdmin = false;
			}
		}

		return $this->isAdmin;
	}
	
	public static function doLogin ( $username, $password ) {
		$result = self::find ( array ( 'WHERE' => 'username = "' . $username . '" AND password = "' . $password . '" AND deleted = 0' ) );

		if ( count ( $result ) == 1 ) {
			$user = $result[0];
			$_SESSION [ 'User' ] = serialize ( $user );
			$_SESSION [ 'UserName' ] = $user->displayname;

			$usergroup = new Usergroups ( $user->usergroups_id );
			$_SESSION [ 'UsergroupRank' ] = $usergroup->rank;

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
}