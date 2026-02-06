<?php

namespace Nelio_AB_Testing\Compat\Elementor\Posts;

defined( 'ABSPATH' ) || exit;

use Elementor\Core\Files\CSS\Post as Post_CSS;

use function add_action;

/**
 * Generates all CSS files.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 */
function generate_all_css_files( $experiment ) {

	if ( ! in_array( $experiment->get_type(), array( 'nab/page', 'nab/post', 'nab/custom-post-type' ), true ) ) {
		return;
	}

	$control_id = $experiment->get_tested_post();
	if ( ! get_post_meta( $control_id, '_elementor_edit_mode', true ) ) {
		return;
	}

	$alternatives = $experiment->get_alternatives();
	foreach ( $alternatives as $alternative ) {
		$post_id = absint( $alternative['attributes']['postId'] ?? 0 );
		if ( empty( $post_id ) ) {
			continue;
		}

		$aux = new Post_CSS( $post_id );
		$aux->update();

	}
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'nab_save_experiment', __NAMESPACE__ . '\generate_all_css_files' );
	}
);
