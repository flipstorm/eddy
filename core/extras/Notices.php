<?php
	class Notices {
		public $message;
		public $type;
		public $class;

		// Overridable type => class
		protected static $types = array(
			'notice' => 'info',
			'warning' => 'warning',
			'error' => 'error',
			'success' => 'success'
		);

		public function __construct( $message, $type = 'notice' ) {
			if ( array_key_exists( $type, static::$types ) ) {
				$this->type = $type;
				$this->message = $message;
				$this->class = static::$types[ $type ];

				$_SESSION[ 'notices' ][] = serialize( $this );
			}
			else {
				// throw exception?
			}
		}

		public function __toString() {
			return $this->message;
		}

		public static function add( $message, $type = 'notice' ) {
			return new self( $message, $type );
		}

		public static function fetch() {
			if ( is_array( $_SESSION[ 'notices' ] ) ) {
				foreach ( $_SESSION[ 'notices' ] as $notice ) {
					$notices[] = unserialize( $notice );
				}
			}

			unset( $_SESSION[ 'notices' ] );

			return (array) $notices;
		}
	}