<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading URL alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TUrl_Control_Attributes,TUrl_Alternative_Attributes>
 */
class Alternative_Url_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		add_filter( 'nab_alternative_urls', array( $this, 'get_alternative_urls' ) );
		add_filter( 'nab_use_control_url_in_multi_url_alternative', array( $this, 'maybe_use_control_url_on_all_alternatives' ) );
	}

	/**
	 * Callback to return alternative URLs.
	 *
	 * @return list<string>
	 */
	public function get_alternative_urls() {
		$experiment = nab_get_experiment( $this->experiment_id );
		assert( ! ( $experiment instanceof \WP_Error ) );
		/** @var list<array{attributes:TUrl_Alternative_Attributes|TUrl_Control_Attributes}> */
		$alternatives = $experiment->get_alternatives();
		return array_map( fn( $a ) => $a['attributes']['url'], $alternatives );
	}

	/**
	 * Callback to use control URL on all alternatives.
	 *
	 * @param bool $use_control_url Use control URL.
	 *
	 * @return bool
	 */
	public function maybe_use_control_url_on_all_alternatives( $use_control_url ) {
		if ( ! empty( $this->control['useControlUrl'] ) ) {
			return true;
		}

		return $use_control_url;
	}
}
