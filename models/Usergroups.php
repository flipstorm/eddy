<?php
	class Usergroups extends EddyModel {
		public $description;
		public $name;
		public $rank;
		
		public function __construct ( $id = null ) {
			parent::__construct ( $id, __CLASS__ );
		}

		public static function find ( $args ) {
			return parent::find ( __CLASS__, $args );
		}
	}