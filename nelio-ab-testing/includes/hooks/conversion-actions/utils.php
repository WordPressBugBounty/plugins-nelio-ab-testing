<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes the attributes of a conversion action.
 *
 * @param TAttributes                       $attributes Conversion action’s attributes.
 * @param TConversion_Action                $action     Conversion action.
 * @param \Nelio_AB_Testing_Experiment|null $experiment Optional. Experiment. Default: `null`.
 *
 * @return TAttributes
 */
function sanitize_conversion_action_attributes( $attributes, $action, $experiment = null ) {
	/**
	 * Filters a conversion action’s attributes.
	 *
	 * @param TAttributes                       $attributes Conversion action’s attributes.
	 * @param TConversion_Action                $action     Conversion action.
	 * @param \Nelio_AB_Testing_Experiment|null $experiment Experiment.
	 *
	 * @since 6.0.4
	*/
	return apply_filters( 'nab_sanitize_conversion_action_attributes', $attributes, $action, $experiment );
}

/**
 * Sanitizes the scope of a conversion action.
 *
 * @param TConversion_Action_Scope $scope  Conversion action’s scope.
 * @param TConversion_Action       $action Conversion action.
 *
 * @return TConversion_Action_Scope
 */
function sanitize_conversion_action_scope( $scope, $action ) {
	/**
	 * Filters a conversion action’s scope.
	 *
	 * @param TConversion_Action_Scope $scope  Conversion action’s scope.
	 * @param TConversion_Action       $action Conversion action.
	 *
	 * @since 6.0.4
	 */
	return apply_filters( 'nab_sanitize_conversion_action_scope', $scope, $action );
}
