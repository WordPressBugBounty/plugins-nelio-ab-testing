<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

use Nelio_AB_Testing_Runtime;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	$scope      = $experiment->get_scope();
	return nab_get_preview_url_from_scope( $scope, $alternative_id );
}//end get_preview_link()
add_filter( 'nab_nab/php_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

function load_preview( $alternative, $_, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return;
	}//end if

	$runtime = new Nelio_AB_Testing_Runtime();
	$context = array( 'url' => $runtime->get_untested_url() );

	if ( 'php-snippet' === nab_array_get( $experiment->get_scope(), '0.attributes.type' ) ) {
		$rule = array(
			'type'  => 'exact',
			'value' => nab_array_get( $experiment->get_scope(), '0.attributes.value.previewUrl' ),
		);
		if ( ! nab_does_rule_apply_to_url( $rule, $context['url'] ) ) {
			return;
		}//end if
	} elseif ( ! nab_is_experiment_relevant( $context, $experiment ) ) {
		return;
	}//end if

	load_alternative( $alternative );
}//end load_preview()
add_action( 'nab_nab/php_preview_alternative', __NAMESPACE__ . '\load_preview', 10, 3 );
