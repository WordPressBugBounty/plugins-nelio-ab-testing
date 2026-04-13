<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading pattern alternatives in preview.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TSynced_Pattern_Control_Attributes,TSynced_Pattern_Alternative_Attributes>
 */
class Alternative_Pattern_Preview_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'template_include', array( $this, 'get_preview_template' ) );
		add_action( 'nab_preview_synced_pattern', array( $this, 'render_pattern' ) );
	}

	/**
	 * Callback to get preview template.
	 *
	 * @return string
	 */
	public function get_preview_template() {
		return nelioab()->plugin_path . '/includes/hooks/experiments/synced-pattern/preview-template.php';
	}

	/**
	 * Callback to render pattern.
	 *
	 * @return void
	 */
	public function render_pattern() {
		$post = get_post( $this->alternative['patternId'] );
		if ( is_null( $post ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo do_blocks( $post->post_content );
	}
}
