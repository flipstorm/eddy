<?php
	abstract class EddyController extends EddyBase {
		protected $data = array();
		protected $view;
		protected $skin = 'default';

		private $min_user_rank;
		
		public function getData() {
			return $this->data;
		}
		
		public function getMinUserRank() {
			return $this->min_user_rank;
		}
		
		protected function _setMin_user_rank( $value ) {
			$this->min_user_rank = $value;
			$this->goSecure();
		}
		
		public function getView() {
			global $EddyFC;
			
			if ( isset( $this->view ) ) {
				// Prefer explicitly-defined view
				$view = $this->view;
			}
			elseif ( $EddyFC[ 'requestparams' ] ) {
				// Use the current path without the parameters
				$viewpath = str_ireplace( $EddyFC[ 'requestparams' ], '', $EddyFC[ 'request' ] );
				
				if ( empty( $viewpath ) ) {
					$viewpath = $EddyFC[ 'requestmethod' ];
				}
				elseif ( strpos( $viewpath, $EddyFC[ 'requestmethod' ] ) === false ) {
					$viewpath .= $EddyFC[ 'requestmethod' ];
				}

				$view = trim( $viewpath, '/' );
			}
			else {
				// Use the path and method name
				if ( $EddyFC[ 'requestpath' ] != 'default' ) {
					$requestpath = $EddyFC[ 'requestpath' ] . '/';
				}
				
				$view = $requestpath . $EddyFC[ 'requestmethod' ];
			}
			
			return $view;
		}
		
		public function getSkin() {
			return $this->skin;
		}

		/*
		 * Force the 404 error page to show
		 */
		public function error404() {
			$this->data ['title'] = 'Page Not Found';
			header( 'HTTP/1.1 404 Not Found' );
			$this->view = '404';
		}

		/*
		 * Programmatic redirection method. Call this in your controller methods at any point to halt the current request and redirect
		 * If the original request that results in this redirect was made with an AJAX call, you will need to handle the redirect client side
		 */
		protected function redirect( $location = '/', $recordDestination = false ) {
			if ( $location{0} == '/' ) {
				$location = SITE_ROOT . $location;
			}

			if ( $recordDestination ) {
				$request = getCurrentURIPath();
				$_SESSION[ 'Destination' ] = $request[ 'actual' ];
			}

			// If this is an AJAX call, we'll have to handle the redirect on the front-end
			if ( !$_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) {
				ob_end_clean();
				header( 'Location: ' . $location );
				exit;
			}
			else {
				$this->data[ 'redirect' ] = $location;
			}
		}

		private function goSecure( $redirect = '/login' ) {
			if ( $_SESSION[ 'UserRank' ] < $this->min_user_rank ) {
				$this->redirect( $redirect, true );
			}
		}
	}