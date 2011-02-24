<?php
	class Default_Controller extends EddyController {
		public function index() {
			$this->data[ 'title' ] = 'Welcome to Eddy!';
		}
	}