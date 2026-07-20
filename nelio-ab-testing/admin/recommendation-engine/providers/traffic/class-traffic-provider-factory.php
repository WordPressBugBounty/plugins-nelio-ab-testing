<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

final class Traffic_Provider_Factory {

	/**
	 * Returns the first available traffic provider.
	 *
	 * Providers are checked in priority order until one reports itself as
	 * available. If no supported traffic source is detected, a
	 * {@see Null_Traffic_Provider} instance is returned.
	 *
	 * The list of providers can be customized using the
	 * {@see 'nab_recommendation_engine_traffic_providers'} filter.
	 *
	 * @return Traffic_Provider
	 */
	public static function make() {
		/**
		* Filters traffic providers for the recommendation engine.
		*
		* @param list<string> $providers List of default providers.
		*
		* @since 8.5.0
		*/
		$providers = apply_filters(
			'nab_recommendation_engine_traffic_providers',
			array(
				Nelio_AB_Testing_Traffic_Provider::class,
				Site_Kit_Traffic_Provider::class,
				MonsterInsights_Traffic_Provider::class,
				Jetpack_Traffic_Provider::class,
			)
		);

		foreach ( $providers as $provider ) {
			/** @var mixed $provider */
			if (
				is_string( $provider ) &&
				is_subclass_of( $provider, Traffic_Provider::class ) &&
				$provider::is_available()
			) {
				return new $provider();
			}
		}

		return new Null_Traffic_Provider();
	}
}
