<?php
	class Pagination {
		private $includeQueryString = true;
		private $limit = 20;
		private $stages = 4;
		private $startBtns = 2;
		private $endBtns = 2;
		private $spacer = '<span>...</span>';
		private $targetpage;
		private $page = 1;
		private $itemCount;
		private $_start = 0;
		
		/*
			array (
				'limit' => 20,
				'stages' => 3,
				'startBtns' => 3,
				'endBtns' => 3,
				'spacer' => '<span>...</span>',
				'targetpage' => './page',
				'page' => 1,
				'itemCount' => 200,
				'firstPageTarget' => './page1'
			);
		*/
		
		public function __construct( $settings = array() ) {
			foreach ( $settings as $key => $value ) {
				if ( $key{0} != '_' && isset( $value ) ) {
					$this->$key = $value;
				}
			}
			
			// TODO: Get current page number based on the targetpage template
			/*
				if ( !empty ( $this->page ) ) {
					$this->page = str_ireplace( 'page', '', $page );
				}
			*/

			if ( !is_numeric( $this->page ) ) {
				$this->page = 1;
			}
			
			if ( $this->page && $this->page > 0 ) {
				$this->_start = ( $this->page - 1 ) * $this->limit;
			}
		}
		
		public function getSQLLimit() {
			return $this->_start . ', ' . $this->limit;
		}
		
		public function getResultsSummary() {
			$pageEnd = $this->_start + $this->limit;
			
			if ( $pageEnd > $this->itemCount ) {
				$pageEnd = $this->itemCount;
			}
			
			return 'Showing ' . number_format( $this->_start + 1, 0, '.', ',' ) . ' to ' . number_format( $pageEnd, 0, '.', ',' ) . ' of ' . number_format( $this->itemCount, 0, '.', ',' );
		}
		
		public function __toString() {
			return $this->render();
		}
		
		public function render() {
			// Initial page num setup
			$prev = $this->page - 1;
			$next = $this->page + 1;
			$lastpage = ceil( $this->itemCount / $this->limit );
			$stages2 = $this->stages * 2;
			
			// Append the query string
			if ( $this->includeQueryString == true && $_SERVER[ 'QUERY_STRING' ] ) {
				$hrefSuffix = '?' . $_SERVER[ 'QUERY_STRING' ];
			}
			
			// If there is more than one page
			if ( $lastpage > 1 ) {
				// Start Buttons
				if ( $this->startBtns > 0 ) {
					for ( $i = 1; $i <= $this->startBtns; $i++ ) {
						if ( $i == 1 ) {
							$startButtons .= '<a href="./' . $hrefSuffix . '">' . $i . '</a>';
						}
						else {
							$startButtons .= '<a href="' . $this->targetpage . $i . $hrefSuffix . '">' . $i . '</a>';
						}
					}

					$startButtons .= $this->spacer;
				}
				
				// End Buttons
				if ( $this->endBtns > 0 ) {
					$endButtons = $this->spacer;
					
					for ( $i = $lastpage - $this->endBtns + 1; $i <= $lastpage; $i++ ) {
						$endButtons .= '<a href="' . $this->targetpage . $i . $hrefSuffix . '">' . $i . '</a>';
					}
				}
			
				$paginate = '<div class="pagination">';
				
				// Previous Button
				$prevbtn = '&laquo; Previous';
				if ( $this->page > 1 ) {
					if ( $prev == 1 ) {
						$paginate .= '<a href="./' . $hrefSuffix . '">' . $prevbtn . '</a>';
					}
					else {
						$paginate .= '<a href="' . $this->targetpage . $prev . $hrefSuffix . '">' . $prevbtn . '</a>';
					}
				}
				else {
					$paginate .= '<span class="disabled">' . $prevbtn . '</span>';
				}
				
				if ( $lastpage < ( 7 + $stages2 ) ) {
					// Not enough pages to break it up
					for ( $counter = 1; $counter <= $lastpage; $counter++ ) {
						if ( $counter == $this->page ) {
							$paginate .= '<span class="current">' . $counter . '</span>';
						}
						elseif ( $counter == 1 ) {
							$paginate .= '<a href="./' . $hrefSuffix . '">' . $counter . '</a>';
						}
						else {
							$paginate .= '<a href="' . $this->targetpage . $counter . $hrefSuffix . '">' . $counter . '</a>';
						}
					}
				}
				elseif ( $lastpage > ( 5 + $stages2 ) ) {
					// Enough pages to hide a few
	
					if ( $this->page < ( 1 + $stages2 ) ) {
						// We're near the beginning so hide ones towards the end
						for ( $counter = 1; $counter < ( 4 + $stages2 ); $counter++ ) {
							if ( $counter == $this->page ) {
								$paginate .= '<span class="current">' . $counter . '</span>';
							}
							elseif ( $counter == 1 ) {
								$paginate .= '<a href="./' . $hrefSuffix . '">' . $counter . '</a>';
							}
							else {
								$paginate .= '<a href="' . $this->targetpage . $counter . $hrefSuffix . '">' . $counter . '</a>';
							}
						}
						
						$paginate .= $endButtons;
					}
					elseif ( ( $lastpage - $stages2 ) > $this->page && $this->page > $stages2 ) {
						// We're in the middle so hide some either side
						$paginate .= $startButtons;
	
						for ( $counter = $this->page - $this->stages; $counter <= $this->page + $this->stages; $counter++ ) {
							if ( $counter == $this->page ) {
								$paginate .= '<span class="current">' . $counter . '</span>';
							}
							else {
								$paginate .= '<a href="' . $this->targetpage . $counter . $hrefSuffix . '">' . $counter . '</a>';
							}
						}
						
						$paginate .= $endButtons;
					}
					else {
						// We're near the end so hide ones towards the beginning
						$paginate .= $startButtons;
	
						for ( $counter = $lastpage - ( 2 + $stages2 ); $counter <= $lastpage; $counter++ ) {
							if ( $counter == $this->page ) {
								$paginate .= '<span class="current">' . $counter . '</span>';
							}
							else {
								$paginate .= '<a href="' . $this->targetpage . $counter . $hrefSuffix . '">' . $counter . '</a>';
							}
						}
					}
				}
				
				// Next Button
				$nextbtn = 'Next &raquo;';
				if ( $this->page < $counter - 1 ) { 
					$paginate .= '<a href="' . $this->targetpage . $next . $hrefSuffix . '">' . $nextbtn . '</a>';
				}
				else {
					$paginate .= '<span class="disabled">' . $nextbtn . '</span>';
				}
				
				$paginate .= '</div>';
			}
			
			return $paginate;
		}
	}