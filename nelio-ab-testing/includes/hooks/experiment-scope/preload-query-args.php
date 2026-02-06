<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preload_Query_Args;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the URLs for which testing query args should be preloaded, or `false` if the feature
 * is disabled.
 *
 * @return list<TPreload_Query_Arg_Url> the URLs for which testing query args should be preloaded, or `false` if the feature is disabled.
 *
 * @since 7.3.0
 */
function generate() {
	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, fn( $e ) => false !== $e->get_inline_settings() );
	return array_map( __NAMESPACE__ . '\get_experiment_urls', array_values( $experiments ) );
}

/**
 * Returns the URLs affected by the given experiment.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return TPreload_Query_Arg_Url
 */
function get_experiment_urls( $experiment ) {
	$experiment_type = $experiment->get_type();

	/**
		* Filters whether query args preloading should be skipped for the given test.
		*
		* @param boolean                      $skip       Skip preloading. Default: `false`.
		* @param \Nelio_AB_Testing_Experiment $experiment The experiment.
		*
		* @since 7.5.0
		*/
	if ( apply_filters( "nab_{$experiment_type}_disable_query_arg_preloading", false, $experiment ) ) {
		return array(
			'type'     => 'scope',
			'scope'    => array(),
			'altCount' => 0,
		);
	}

	$control = $experiment->get_alternative( 'control' );
	$alts    = wp_list_pluck( $experiment->get_alternatives(), 'attributes' );
	if ( ! empty( $control['attributes']['testAgainstExistingContent'] ) ) {
		/** @var list<int> */
		$alts = wp_list_pluck( $alts, 'postId' );
		/** @var list<string> */
		$urls = array_values( array_filter( array_map( 'get_permalink', $alts ) ) );
		return array(
			'type'     => 'alt-urls',
			'altUrls'  => $urls,
			'altCount' => count( $urls ),
		);
	}

	$rules = $experiment->get_scope();
	$rules = array_map( fn( $r ) => $r['attributes'], $rules );
	if ( empty( $rules ) ) {
		return array(
			'type'     => 'scope',
			'scope'    => array( '**' ),
			'altCount' => count( $alts ),
		);
	}

	if (
		'tested-url-with-query-args' === $rules[0]['type'] &&
		empty( $rules[0]['value']['args'] )
	) {
		$urls = $rules[0]['value']['urls'];
		return array(
			'type'     => 'alt-urls',
			'altUrls'  => $urls,
			'altCount' => count( $urls ),
		);
	}

	$main = $experiment->get_tested_post();
	$urls = array_map(
		function ( $rule ) use ( $main ): string {
			switch ( $rule['type'] ) {
				case 'tested-post':
					$permalink = get_permalink( $main );
					return is_string( $permalink ) ? $permalink : '';
				case 'tested-url-with-query-args':
					// This case is already controlled a few lines above.
					return '';
				case 'exact':
					return $rule['value'];
				case 'partial':
					return "*{$rule['value']}*";
				case 'partial-not-included':
					return "!*{$rule['value']}*";
				case 'different':
					return "!{$rule['value']}";
				default:
					return '';
			}
		},
		$rules
	);
	$urls = array_values( array_filter( $urls ) );
	return array(
		'type'     => 'scope',
		'scope'    => $urls,
		'altCount' => count( $alts ),
	);
}
