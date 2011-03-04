<?php
	abstract class EddyController extends EddyBase {
		protected $data = array();
		protected $view;
		protected $skin = 'default';
		protected $cache = OUTPUT_CACHE_ALL;

		//public $cache_path = OUTPUT_CACHE_PATH_DEFAULT;
		public $cache_compress = OUTPUT_CACHE_COMPRESS_DEFAULT;
		//public $cache_file;
		
		public $cancel_request;
		
		protected function _get_data() {
			return $this->data;
		}
		
		protected function _get_view() {
			global $EddyFC;
			
			if ( isset( $this->view ) ) {
				// Prefer explicitly-defined view
				$view = $this->view;
			}
			elseif ( $EddyFC[ 'requestparams' ] ) {
				// Use the current path without the parameters
				$viewpath = str_ireplace( $EddyFC[ 'requestparams' ], '', $EddyFC[ 'request' ][ 'fixed' ] );
				
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
		
		protected function _get_skin() {
			return $this->skin;
		}

		protected function _call_error404() {
			$this->error404();
		}

		protected function _call_error410() {
			$this->error410();
		}

		/*
		 * Force the 404 error page to show
		 */
		protected function error404() {
			$this->data[ 'title' ] = 'Not Found';
			header( 'HTTP/1.1 404 Not Found' );
			$this->view = 'errors/404';
			$this->cache = false;
		}
		
		protected function error410() {
			$this->data[ 'title' ] = 'Gone';
			header( 'HTTP/1.1 410 Gone' );
			$this->view = 'errors/410';
			$this->cache = false;
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
				$request = URI_Helper::get_current();
				$_SESSION[ 'destination' ] = $request[ 'actual' ];
			}

			// If this is an AJAX call, we'll have to handle the redirect on the front-end
			if ( !$_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) {
				ob_end_clean();
				header( 'Location: ' . $location );
				exit;
			}
			else {
				$this->data[ 'redirect' ] = $location;
				$this->cancel_request = true;
			}
		}

		protected function security( $barrel, $key, $redirect = '/login' ) {
			$unlocked = call_user_func( $barrel, $key );

			if ( !$unlocked ) {
				$this->redirect( $redirect, true );
			}
		}

		protected function _get_cacheable() {
			if ( strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) != 'POST' && OUTPUT_CACHE_ENABLED ) {
				return $this->cache;
			}
			
			return false;
		}
	}