<?php
	class EddyController extends EddyBase {
		protected $data = array();
		private $min_user_rank;
		protected $view;
		protected $skin = 'default';
		
		public function getData() {
			return $this->data;
		}
		
		public function getMinUserRank() {
			return $this->min_user_rank;
		}
		
		protected function _setMin_user_rank( $value ) {
			$this->min_user_rank = $value;
			goSecure( $this->min_user_rank );
		}
		
		public function getView() {
			global $EddyFC;
			
			if ( isset( $this->view ) ) {
				$view = $this->view;
			}
			elseif ( $EddyFC[ 'requestparams' ] ) {
				// Use the current path without the parameters
				$viewpath = str_ireplace( $EddyFC[ 'requestparams' ], '', $EddyFC[ 'request' ] );
				
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
		
		public function getSkin() {
			return $this->skin;
		}
	}