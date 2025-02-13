<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_action(
	'nab_nab/php_experiment_priority',
	function ( $_, $__, $experiment_id ) {
		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return 'custom';
		}//end if

		$scope = $experiment->get_scope();
		$type  = nab_array_get( $scope, '0.attributes.type' );
		if ( 'php-snippet' !== $type ) {
			return 'high';
		}//end if

		return nab_array_get( $scope, '0.attributes.value.priority' );
	},
	10,
	3
);

function load_alternative( $alternative ) {
	if ( empty( $alternative['snippet'] ) ) {
		return;
	}//end if

	if ( ! empty( $alternative['errorMessage'] ) ) {
		return;
	}//end if

	$snippet = $alternative['snippet'];
	\nab_eval_php( $snippet );
}//end load_alternative()
add_action( 'nab_nab/php_load_alternative', __NAMESPACE__ . '\load_alternative' );

add_filter( 'nab_nab/php_get_alternative_summary', '__return_empty_array' );
