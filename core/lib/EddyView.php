<?php
	class EddyView extends EddyBase {
		public static $data;

		public static function load_partial( $path ) {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}

			$partial_path = 'views/partials/' . $path . '.part.phtml';
			$partial_file = APP_ROOT . '/' . $partial_path;

			if ( file_exists( $partial_file ) ) {
				include( $partial_path );
			}
			elseif ( DEBUG ) {
				echo "{Partial doesn't exist: $partial_file}";
			}
		}

		public static function load_template( $template ) {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}
			
			$template_path = 'views/templates/' . $template . '.tmpl.phtml';
			$template_file = APP_ROOT . '/' . $template_path;

			if ( file_exists( $template_file ) ) {
				include( $template_path );
			}
			elseif ( DEBUG ) {
				echo "{Template doesn't exist: $template}";
			}
		}

		public static function load( $view = null ) {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}
			
			if ( !$view ) {
				$view = Eddy::$controller->view;
			}
			
			$view_path = 'views/' . $view . '.phtml';
			$view_file = APP_ROOT . '/' . $view_path;

			if ( file_exists( $view_file ) ) {
				include_once( $view_path );
			}
			elseif ( DEBUG ) {
				echo "{View doesn't exist: $view_file}";
			}
			
			self::test();
		}
	}