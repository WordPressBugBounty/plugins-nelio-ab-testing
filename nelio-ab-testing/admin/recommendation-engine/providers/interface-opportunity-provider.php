<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Opportunity;

interface Opportunity_Provider {

	/**
	 * Returns a list of opportunities.
	 *
	 * @return list<Opportunity>
	 */
	public function get_opportunities();
}
