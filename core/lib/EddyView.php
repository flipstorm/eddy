<?php
	class EddyView extends EddyBase {
		public static $data;
		public static $skin = 'default';
		public static $view;

		public static function load_partial( $path ) {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}

			$partial_path = 'app/views/partials/' . $path . '.part.phtml';
			$partial_file = APP_ROOT . '/' . $partial_path;

			if ( file_exists( $partial_file ) ) {
				include( $partial_path );
			}
			elseif ( DEBUG ) {
				echo '{Partial doesn\'t exist: ' . $partial_file . '}';
			}
		}

		public static function load_skin() {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}

			if ( file_exists( APP_ROOT . '/app/views/templates/' . self::$skin . '.tmpl.phtml' ) ) {
				include 'app/views/templates/' . self::$skin . '.tmpl.phtml';
			}
			elseif ( file_exists( APP_ROOT . '/public/skins/' . self::$skin . '/template.phtml' ) ) {
				include 'public/skins/' . self::$skin . '/template.phtml';
			}
			else {
				FB::info( self::$skin, "Requested skin doesn't exist" );
			}
		}

		public static function load() {
			if ( is_array( self::$data ) ) {
				extract( self::$data );
			}

			$view_path = 'app/views/' . self::$view . '.phtml';
			$view_file = APP_ROOT . '/' . $view_path;

			if ( file_exists( $view_file ) ) {
				include_once( $view_path );
			}
			elseif ( DEBUG ) {
				echo '{View doesn\'t exist: ' . $view_file . '}';
			}
		}
	}