<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

use Nelio_AB_Testing_Runtime;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to get preview link.
 *
 * @param string|false                                        $preview_link   Preview link.
 * @param TPhp_Alternative_Attributes|TPhp_Control_Attributes $alternative    Alternative.
 * @param TPhp_Control_Attributes                             $control        Control.
 * @param int                                                 $experiment_id  Experiment ID.
 * @param string                                              $alternative_id Experiment ID.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );
	$scope = $experiment->get_scope();
	return nab_get_preview_url_from_scope( $scope, $alternative_id );
}
add_filter( 'nab_nab/php_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

/**
 * Callback to add hooks to preview alternative content.
 *
 * @param TPhp_Alternative_Attributes $alternative   Alternative.
 * @param TPhp_Control_Attributes     $control       Control.
 * @param int                         $experiment_id Experiment ID.
 *
 * @return void
 */
function load_preview( $alternative, $control, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	assert( ! ( $experiment instanceof \WP_Error ) );

	$runtime = new Nelio_AB_Testing_Runtime();
	$context = array( 'url' => $runtime->get_untested_url() );

	$scope = $experiment->get_scope();
	$scope = $scope[0]['attributes'] ?? false;
	if ( false !== $scope && 'php-snippet' === $scope['type'] ) {
		$rule = array(
			'type'  => 'exact',
			'value' => $scope['value']['previewUrl'],
		);
		if ( ! nab_does_rule_apply_to_url( $rule, $context['url'] ) ) {
			return;
		}
	} elseif ( ! nab_is_experiment_relevant( $context, $experiment ) ) {
		return;
	}

	load_alternative( $alternative );
}
add_action( 'nab_nab/php_preview_alternative', __NAMESPACE__ . '\load_preview', 10, 3 );
