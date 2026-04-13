<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for testing already existing content against each other.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TPost_Control_Attributes,TPost_Alternative_Attributes>
 */
class Existing_Alternative_Post_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'nab_alternative_urls', array( $this, 'get_alternative_urls' ) );
		add_filter( 'nab_use_control_url_in_multi_url_alternative', array( $this, 'use_control_url' ) );
	}

	/**
	 * Callback to replace alternative URLs.
	 *
	 * @return list<string>
	 */
	public function get_alternative_urls() {
		$experiment = nab_get_experiment( $this->experiment_id );
		assert( ! is_wp_error( $experiment ) );
		$alts = $experiment->get_alternatives();
		$alts = array_map( fn( $a )=> absint( $a['attributes']['postId'] ?? 0 ), $alts );
		return array_values( array_filter( array_map( 'get_permalink', $alts ) ) );
	}

	/**
	 * Callback to use control URL on all alternatives.
	 *
	 * @param bool $enabled Enabled.
	 *
	 * @return bool
	 */
	public function use_control_url( $enabled ) {
		return ! empty( $this->control['useControlUrl'] ) ? true : $enabled;
	}
}
