<?php
	class EddyController {
		protected $data = array();
		protected $jsonData = array();
		protected $usergroupRank = 9999;
		protected $view;
		protected $skin = 'default';
		
		public function getData() {
			return $this->data;
		}
		
		public function getJsonData() {
			return $this->jsonData;
		}
		
		public function getUsergroupRank() {
			return $this->usergroupRank;
		}
		
		public function getView() {
			global $EddyFC;
			
			if ( isset ( $this->view ) ) {
				return $this->view;
			}
			else {
				// Use the method name
				if ( $EddyFC [ 'requestpath' ] != 'default' ) {
					$requestpath = $EddyFC [ 'requestpath' ] . '/';
				}
				
				return $requestpath . $EddyFC [ 'requestmethod' ];
			}
		}
		
		public function getSkin() {
			return $this->skin;
		}
	}