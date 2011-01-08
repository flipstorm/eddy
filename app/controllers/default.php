<?php
	class Default_Controller extends EddyController {
		private $user;
		
		public function __construct() {
			$this->user = unserialize ( $_SESSION [ 'User' ] );
		}
		
		public function index() {
			//Users::find();
		}
		
		final public function login() {
			if ( $_POST [ 'username' ] || $_POST [ 'password' ] ) {
				if ( !Users::doLogin ( $_POST [ 'username' ], $_POST [ 'password' ] ) ) {
					$this->data [ 'errors' ] = 'Invalid username or password.';
				}
			}
			else {
				$this->data [ 'errors' ] = 'Please enter a username and password.';
			}

			if ( $_SESSION [ 'UsergroupRank' ] <= $this->usergroupRank ) {
				redirect ( SITE_ROOT . '/' );
			}
			else {
				$this->usergroupRank = 9999; // This makes sure we can always see the login page no matter what!
			}
		}
	
		final public function logout() {
			session_unset();
			redirect ( SITE_ROOT . '/' );
		}
		
		final public function error404() {
			$this->data ['title'] = 'Page Not Found';
			header ( 'HTTP/1.1 404 Page Not Found' );
			$this->view = 'default';
		}
	}