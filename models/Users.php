<?php
class Users extends EddyModel {
	public $customers_id;
	public $displayname;
	public $lastlogin_date;
	public $usergroups_id;
	public $username;

	private $_isAdmin;

	public function __construct ( $id = null ) {
		parent::__construct ( $id, __CLASS__ );
	}

	public function isAdmin() {
		if ( !isset( $this->_isAdmin ) ) {
			// Find out whether we should treat this user as an Admin
			if ( $this->usergroups_id < 2 ) {
				$this->_isAdmin = true;
			}
			else {
				$this->_isAdmin = false;
			}
		}

		return $this->_isAdmin;
	}

	public function changePassword ( &$response, $msg = 'Password successfully changed', $formWidth = '400px' ) {
		$passwordForm = new HTMLControls_Form('change_password', array ( 'submit' => 'Change Password' ) );

		$passwordForm->addChild ( new HTMLControls_Password ( 'oldpass', 'Old Password', true ) );
		$passwordForm->addChild ( new HTMLControls_Password ( 'newpass', 'New Password', true ) );

		$confirm = new HTMLControls_Password ( 'confirm', 'Confirm Password', true );
		$confirm->_validationRules[] = new ValidationRule ( 'equalTo', '#newpass', 'Must match your new password above' );
		$passwordForm->addChild ( $confirm );

		$passwordForm->_width = $formWidth;

		$user = unserialize ( $_SESSION ['User'] );

		if ( $passwordForm->isPostback() ) {
			if ( $user->password == $_POST [ 'oldpass' ] ) {
				$user->password = $_POST [ 'newpass' ];

				if ( $user->save() ) {
					$response = userSuccess ( $msg );
					$_SESSION [ 'User' ] = serialize ( $user );
				}
				else {
					$response = userError ( 'Sorry, there\'s been a problem and we couldn\'t change your password right now.
						Please try again later' );
				}
			}
			else {
				$response = userError ( 'Please enter your original password correctly!' );
			}
		}

		return $passwordForm;
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

	public static function generatePassword() {
		$adj = array ( 'Happy', 'Sad', 'Wet', 'Cold', 'Hot', 'Dry', 'Cool', 'Wicked', 'Sweet', 'Sour' );
		$subj = array ( 'Dog', 'Cat', 'Fish', 'Bear', 'Horse', 'Monkey', 'Mouse', 'Rat', 'Pig' );

		$num1 = rand ( 1, 9 );
		$num2 = rand ( 1, 9 );
		$p1 = array_rand ( $adj, 1 );
		$p2 = array_rand ( $subj, 1 );

		return $adj [ $p1 ] . $subj [ $p2 ] . $num1 . $num2;
	}
	
	public static function find ( $args = null ) {
		return parent::find ( __CLASS__, $args );
	}
}