<?php
	class EddyView extends EddyBase {
		public static $data;
		
		// Partials
		public static function load_partial( $path, $data = null ) {
			if ( is_array( $data ) ) {
				extract( $data );
			}
			elseif ( is_array( self::$data ) ) {
				extract( self::$data );
			}
			
			$partial_path = 'views/partials/' . $path . '.part.phtml';
			
			if ( self::partial_exists( $path ) ) {
				include( $partial_path );
			}
			elseif ( DEBUG ) {
				echo "{Partial doesn't exist: $partial_path}";
			}
		}
	
		public static function partial_exists( $path ) {
			return file_exists( APP_ROOT . '/views/partials/' . $path . '.part.phtml' );
		}


		// Templates
		public static function load_template( $template, $data = null ) {
			if ( is_array( $data ) ) {
				extract( $data );
			}
			elseif ( is_array( self::$data ) ) {
				extract( self::$data );
			}
			
			$template_path = 'views/templates/' . $template . '.tmpl.phtml';
			
			if ( self::template_exists( $template ) ) {
				include( $template_path );
			}
			elseif ( DEBUG ) {
				echo "{Template doesn't exist: $template_path}";
			}
		}

		public static function template_exists( $path ) {
			return file_exists( APP_ROOT . '/views/templates/' . $path . '.tmpl.phtml' );
		}
		
		
		// Main Views
		public static function load( $view = null, $data = null ) {
			if ( is_array( $data ) ) {
				extract( $data );
			}
			elseif ( is_array( self::$data ) ) {
				extract( self::$data );
			}
			
			if ( !$view ) {
				$view = Eddy::$controller->view;
			}
			
			$view_path = 'views/' . $view . '.phtml';

			if ( self::view_exists( $view ) ) {
				include_once( $view_path );
			}
			elseif ( DEBUG ) {
				echo "{View doesn't exist: $view_path}";
			}
		}

		public static function view_exists( $path ) {
			return file_exists( APP_ROOT . '/views/' . $path . '.phtml' );
		}
	}