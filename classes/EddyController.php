<?php
	class EddyController {
		protected $data = array();
		protected $usergroupRank = 9999;
		protected $view;
		protected $skin = 'default';
		
		public function getData() {
			return $this->data;
		}
		
		public function getUsergroupRank() {
			return $this->usergroupRank;
		}
		
		public function getView() {
			return $this->view;
		}
		
		public function getSkin() {
			return $this->skin;
		}
	}