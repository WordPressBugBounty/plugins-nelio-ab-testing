<?php
/**
 * Nelio A/B Testing helper functions to evaluate test scopes.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/scope
 * @since      7.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the given experiment overlaps with an already-running experiment (and which one) or not.
 *
 * @param Nelio_AB_Testing_Experiment $experiment The experiment to test.
 *
 * @return Nelio_AB_Testing_Experiment|false The overlapping running experiment or `false` if there’s no overlap.
 *
 * @since 7.3.0
 */
function nab_does_overlap_with_running_experiment( $experiment ) {
	$running_exps = nab_get_running_experiments();
	$running_exps = array_filter(
		$running_exps,
		fn( $e ) => $e->get_id() !== $experiment->get_id()
	);
	$running_exps = array_values( $running_exps );

	foreach ( $running_exps as $running_exp ) {
		if ( \Nelio_AB_Testing\Hooks\Experiment_Scope\Evaluate\do_scopes_overlap( $experiment, $running_exp ) ) {
			return $running_exp;
		}//end if
	}//end foreach

	return false;
}//end nab_does_overlap_with_running_experiment()

/**
 * Whether the given experiment is relevant for the current request.
 *
 * @param array                       $context    Information about the current request.
 *                                                In particular, the current untested `url`, its query `args`, and the queried object ID as `postId`.
 * @param Nelio_AB_Testing_Experiment $experiment The given experiment.
 *
 * @return bool Whether the experiment is relevant or not.
 *
 * @since 7.3.0
 */
function nab_is_experiment_relevant( $context, $experiment ) {
	$defaults = array(
		'postId' => 0,
		'url'    => '',
		'args'   => array(),
	);
	$context  = wp_parse_args( $context, $defaults );

	$control         = $experiment->get_alternative( 'control' );
	$experiment_id   = $experiment->get_id();
	$experiment_type = $experiment->get_type();
	$scope           = $experiment->get_scope();

	/**
	 * Short-circuits whether the given experiment should be relevant or not.
	 *
	 * @param mixed   $value         whether the given experiment should be relevant or not.
	 *                               Default: `null`.
	 * @param int     $experiment_id the ID of the experiment.
	 * @param string  $url           the URL of the current request.
	 *
	 * @since 6.5.0
	 */
	$check = apply_filters( "nab_is_{$experiment_type}_relevant_in_url", null, $experiment_id, $context['url'] );
	if ( null !== $check ) {
		return ! empty( $check );
	}//end if

	if ( empty( $scope ) ) {
		/**
		 * Whether the experiment is relevant in the current request or not.
		 *
		 * @param boolean $is_excluded   whether the experiment is relevant from the current request or not.
		 *                               Default: `true`.
		 * @param array   $control       original version.
		 * @param int     $experiment_id id of the experiment.
		 *
		 * @since 7.0.0
		 */
		return apply_filters( "nab_is_{$experiment_type}_php_scope_relevant", true, $control['attributes'], $experiment_id );
	}//end if

	foreach ( $scope as $rule ) {
		switch ( nab_array_get( $rule, 'attributes.type' ) ) {
			case 'tested-post':
				$tested_ids = $experiment->get_tested_posts();
				$is_tested  = is_singular() && in_array( $context['postId'], $tested_ids, true );
				/**
				 * Filters whether the current request is a single post that’s tested by the given experiment.
				 *
				 * @param boolean $is_tested whether the current request is a single post that’s tested by the given experiment.
				 * @param number  $post_id   post ID of the current post (if any).
				 * @param array   $control   original version.
				 * @param number  $exp_id    ID of the experiment.
				 *
				 * @since 5.2.11
				 */
				if ( apply_filters( "nab_is_tested_post_by_{$experiment->get_type()}_experiment", $is_tested, $context['postId'], $control['attributes'], $experiment_id ) ) {
					return true;
				}//end if
				break;

			default:
				if ( nab_does_rule_apply_to_url( nab_array_get( $rule, 'attributes', array() ), $context['url'], $context['args'] ) ) {
					return true;
				}//end if
		}//end switch
	}//end foreach

	return false;
}//end nab_is_experiment_relevant()

/**
 * Whether the rule applies to the given URL or not.
 *
 * @param array  $rule   The attributes of an experiment scope rule.
 * @param string $url    A URL.
 * @param array  $args   Optional. Query arguments in the URL. Default: `[]`.
 *
 * @return bool whether the rule applies to the given URL or not.
 *
 * @since 7.3.0
 */
function nab_does_rule_apply_to_url( $rule, $url, $args = array() ) {
	return \Nelio_AB_Testing\Hooks\Experiment_Scope\Evaluate\does_rule_apply_to_url( $rule, $url, $args );
}//end nab_does_rule_apply_to_url()

/**
 * Returns the URLs for which testing query args should be preloaded, or `false` if the feature
 * is disabled.
 *
 * @return array|false the URLs for which testing query args should be preloaded, or `false` if the feature is disabled.
 *
 * @since 7.3.0
 */
function nab_get_preload_query_arg_urls() {
	if ( 'cookie' === nab_get_variant_loading_strategy() ) {
		return false;
	}//end if

	$settings = \Nelio_AB_Testing_Settings::instance();
	if ( ! $settings->get( 'preload_query_args' ) ) {
		return false;
	}//end if

	return \Nelio_AB_Testing\Hooks\Experiment_Scope\Preload_Query_Args\generate();
}//end nab_get_preload_query_arg_urls()

/**
 * Given a test scope, it returns the appropriate preview URL for the given alternative ID.
 *
 * @param array  $scope          Test scope.
 * @param string $alternative_id Alternative ID.
 *
 * @return string|false Preview URL.
 *
 * @since 7.3.0
 */
function nab_get_preview_url_from_scope( $scope, $alternative_id ) {
	return \Nelio_AB_Testing\Hooks\Experiment_Scope\Preview\get_preview_url_from_scope( $scope, $alternative_id );
}//end nab_get_preview_url_from_scope()
