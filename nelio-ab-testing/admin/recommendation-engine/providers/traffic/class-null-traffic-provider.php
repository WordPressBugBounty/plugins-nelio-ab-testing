<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

final class Null_Traffic_Provider implements Traffic_Provider {

	// @Implements
	public static function is_available() {
		return true;
	}

	// @Implements
	public function get_top_pages( $limit = 20 ) {
		return array();
	}
}
