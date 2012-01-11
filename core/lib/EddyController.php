<?php
	abstract class EddyController extends EddyBase {
		protected $data = array();
		protected $view;
		protected $template = 'default';
		protected $cache = OUTPUT_CACHE_ALL;
		protected $args;

		//public $cache_path = OUTPUT_CACHE_PATH_DEFAULT;
		public $cache_compress = OUTPUT_CACHE_COMPRESS_DEFAULT;
		//public $cache_file;

		public $cancel_request;
		
		// Only used when debugging
		public function empty_data() {
			unset( $this->data );
		}

		protected function _get_data() {
			return $this->data;
		}

		protected function _get_view() {
			if ( isset( $this->view ) ) {
				// Prefer explicitly-defined view
				$view = $this->view;
			}
			elseif ( Eddy::$request->params ) {
				// Use the current path without the parameters
				$viewpath = str_ireplace( implode( '/', Eddy::$request->params ), '', Eddy::$request->fixed );

				if ( empty( $viewpath ) ) {
					$viewpath = Eddy::$request->method;
				}
				elseif ( strpos( $viewpath, Eddy::$request->method ) === false ) {
					$viewpath .= Eddy::$request->method;
				}

				$view = trim( $viewpath, '/' );
			}
			else {
				// Use the path and method name
				if ( Eddy::$request->path != 'default' ) {
					$requestpath = Eddy::$request->path . '/';
				}

				$view = $requestpath . Eddy::$request->method;
			}

			return $view;
		}

		protected function _get_template() {
			return $this->template;
		}

		protected function _call_error404() {
			$this->error404();
		}

		protected function _call_error410() {
			$this->error410();
		}

		/**
		 * Force the 404 error page to show with a 404 header
		 */
		protected function error404() {
			//$this->data[ 'title' ] = 'Not Found';
			header( 'HTTP/1.1 404 Not Found' );
			$this->view = 'errors/404';
			$this->cache = false;
		}

		protected function error410() {
			//$this->data[ 'title' ] = 'Gone';
			header( 'HTTP/1.1 410 Gone' );
			$this->view = 'errors/410';
			$this->cache = false;
		}

		/**
		 * Programmatic redirection method. Call this in your controller methods at any point to
		 * halt the current request and redirect. If the original request that results in this
		 * redirect was made with an AJAX call, you will need to handle the redirect client side
		 * 
		 * @param str[optional] $location The URL you want to redirect to. Relative URLs work as expected
		 * @param bool[optional] $recordDestination Do you want to track where the this require was going before we redirect?
		 * @return str The new location
		 */
		protected function redirect( $location = '/', $recordDestination = false ) {
			if ( $location[0] == '/' ) {
				$location = SITE_ROOT . $location;
			}

			if ( $recordDestination ) {
				$_SESSION[ 'destination' ] = Eddy::$request->original;
			}

			// If this is an AJAX call, we'll have to handle the redirect on the front-end
			if ( Eddy::$request->is_ajax() ) {
				// This is in case a redirect is called in the controller constructor (e.g. security)
				// It stops us from calling the action method, but still continues with a response
				$this->cancel_request = true;
			}
			else {
				ob_end_clean();
				header( 'Location: ' . $location );
				exit;
			}
			
			return $location;
		}

		/**
		 * Generally used in a controller's __construct method, you can lock access to a whole controller,
		 * a single method, or if used in a base class, a whole fleet of controllers that inherit from the
		 * base controller
		 * 
		 * You can specify a function name (using the same format you would for call_user_func) or an
		 * anonymous function. Your function should take one parameter which will accept the value of $key
		 * that you pass into this method and MUST return a boolean value, either true or false in all cases.
		 * 
		 * Here's an example inside a controller constructor:
		 * 
		 * <code>
		 * public function __construct() {
		 *   $this->security(
		 *     function( $key ) {
		 *       if ( $key > 10 ) {
		 *         return true;
		 *       }
		 * 
		 *       return false;
		 *     },
		 *     $user_level,
		 *     '/admin/login'
		 *   );
		 * }
		 * </code>
		 * 
		 * @param func $barrel The method you will use to unlock this security check
		 * @param str $key This is the value you are checking in your function to unlock the barrel
		 * @param mixed[optional] $redirect Where we should redirect to if the secuirty isn't passed. Passing (bool) false will not redirect
		 * @return bool Whether or not the $barrel was unlocked by the given $key
		 */
		protected function security( $barrel, $key, $redirect = '/login' ) {
			$unlocked = call_user_func( $barrel, $key );

			if ( !$unlocked && $redirect !== false ) {
				$this->redirect( $redirect, true );
			}

			return $unlocked;
		}

		/**
		 * Determines whether this controller is cacheable. POST requests are never cached
		 * 
		 * @return bool Cache is enabled and $this->cache is true
		 */
		protected function _get_cacheable() {
			if ( strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) != 'POST' && defined( 'OUTPUT_CACHE_ENABLED' ) && OUTPUT_CACHE_ENABLED ) {
				return (bool) $this->cache;
			}

			return false;
		}

		protected function get_actions() {
			return get_class_public_methods( $this );
		}

		protected function get_url_to_action( $action, $controller = null ) {
			if ( !$controller ) {
				$controller = get_class( $this );
			}

			// Clean up the Controller
			$controller_to_path = strtolower( str_ireplace( array( '^\\', '^Controllers\\', '^Default', '^', '\\', '_Controller$' ), array( '^', '^', '', '', '/', '' ), '^' . $controller . '$' ) );
			$in_app_path = '/' . ( ( $controller_to_path ) ? $controller_to_path . '/' : '' ) . $action;
			$url = SITE_ROOT . $in_app_path;

			// if the action method doesn't exist warn the dev
			if ( !method_exists( $controller, $action ) ) {
				FB::warn( $in_app_path, "Action doesn't exist" );
			}

			return $url;
		}
	}