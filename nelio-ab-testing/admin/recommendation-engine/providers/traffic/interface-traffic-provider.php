<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

use Nelio_AB_Testing\Recommendation_Engine\Content_Traffic;

interface Traffic_Provider {

	/**
	 * Whether the provider is available or not.
	 *
	 * @return boolean
	 */
	public static function is_available();

	/**
	 * Returns a list of opportunities.
	 *
	 * @param int $limit Number of pages.
	 *
	 * @return list<Content_Traffic>
	 */
	public function get_top_pages( $limit = 20 );
}
