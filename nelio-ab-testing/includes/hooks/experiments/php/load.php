<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_filter(
	'nab_nab/php_experiment_priority',
	function ( $priority, $control, $experiment_id ) {
		/** @var 'low'|'mid'|'high'|'custom' $priority      */
		/** @var TPhp_Control_Attributes     $control       */
		/** @var int                         $experiment_id */

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return 'custom';
		}

		$scope = $experiment->get_scope();
		$scope = $scope[0]['attributes'] ?? false;
		if ( empty( $scope ) || 'php-snippet' !== $scope['type'] ) {
			return 'high';
		}

		return $scope['value']['priority'];
	},
	10,
	3
);

/**
 * Callback to load alternative.
 *
 * @param TPhp_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function load_alternative( $alternative ) {
	if ( empty( $alternative['snippet'] ) ) {
		return;
	}

	if ( ! empty( $alternative['errorMessage'] ) ) {
		return;
	}

	$snippet = $alternative['snippet'];
	\nab_eval_php( $snippet );
}
add_action( 'nab_nab/php_load_alternative', __NAMESPACE__ . '\load_alternative' );

add_filter( 'nab_nab/php_get_alternative_summary', '__return_empty_array' );
