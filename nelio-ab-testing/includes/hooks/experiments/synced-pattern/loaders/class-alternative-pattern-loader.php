<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading pattern alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TSynced_Pattern_Control_Attributes,TSynced_Pattern_Alternative_Attributes>
 */
class Alternative_Pattern_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var list<int> */
	private $tested_pattern_ids = array();

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pre_render_block', array( $this, 'maybe_replace_synced_pattern' ), 10, 2 );
	}

	/**
	 * Sets list of tested pattern IDs.
	 *
	 * @param list<int> $tested_pattern_ids Tested pattern IDs.
	 *
	 * @return void
	 */
	public function set_tested_pattern_ids( $tested_pattern_ids ) {
		$this->tested_pattern_ids = $tested_pattern_ids;
	}

	/**
	 * Callback to replace tested synced patterns.
	 *
	 * @param string           $result       Result.
	 * @param TWP_Parsed_Block $parsed_block Parsed block.
	 *
	 * @return string
	 */
	public function maybe_replace_synced_pattern( $result, $parsed_block ) {
		$name = $parsed_block['blockName'];

		if ( 'core/block' !== $name ) {
			return $result;
		}

		$pattern_id = absint( $parsed_block['attrs']['ref'] ?? 0 );
		if ( ! in_array( $pattern_id, $this->tested_pattern_ids, true ) ) {
			return $result;
		}

		if ( $pattern_id === $this->alternative['patternId'] ) {
			return $result;
		}

		$alternative_pattern = get_post( $this->alternative['patternId'] );
		if ( empty( $alternative_pattern ) ) {
			return '';
		}

		return do_blocks( $alternative_pattern->post_content );
	}
}
