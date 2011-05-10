<?php
	abstract class EddyResponse extends EddyBase {
		final public static function _default( $data, $template, $view ) {
			EddyView::$data = $data;

			if ( $template ) {
				// Load the skin (views will be loaded inside that)
				EddyView::load_template( $template );
			}
			elseif ( $view ) {
				// Just load a view
				EddyView::load( $view );
			}
		}
	}